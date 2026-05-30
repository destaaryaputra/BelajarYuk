<?php

namespace App\Models;

use App\Config\Database;
use PDO;
use Exception;
use DateTime;

/**
 * Progress Model
 * Handle tracking perkembangan belajar user
 */

class Progress {
    private ?PDO $db;

    public function __construct() {
        $this->db = Database::getInstance();
    }

    public static function clearUserProgressCache(int $user_id): void {
        if (function_exists('apcu_delete')) {
            apcu_delete("progress_summary_{$user_id}");
            apcu_delete("progress_detail_{$user_id}");
            apcu_delete("user_rank_{$user_id}");
            foreach ([5, 10, 50] as $limit) {
                apcu_delete("leaderboard_{$limit}");
            }
        }
    }

    /**
     * Sync material progress percentage manually
     */
    public function syncMaterialProgress(int $user_id, int $material_id): void {
        try {
            // Count total sub materials
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM sub_materi WHERE material_id = ?");
            $stmt->execute([$material_id]);
            $totalSub = (int) $stmt->fetchColumn();

            // Count completed sub materials
            $stmt = $this->db->prepare("SELECT COUNT(*) FROM progres_sub_materi psm 
                                       JOIN sub_materi sm ON psm.sub_material_id = sm.id 
                                       WHERE sm.material_id = ? AND psm.user_id = ?");
            $stmt->execute([$material_id, $user_id]);
            $completedSub = (int) $stmt->fetchColumn();

            // Check if main material is completed
            $stmt = $this->db->prepare("SELECT 1 FROM progres_materi WHERE user_id = ? AND material_id = ? AND completed_at IS NOT NULL");
            $stmt->execute([$user_id, $material_id]);
            $mainDone = (bool) $stmt->fetchColumn();

            // Adjusted logic matching getDetailedMaterialProgress
            $adjTotal = $totalSub + 1;
            $adjCompleted = $completedSub + ($mainDone ? 1 : 0);
            $percentage = ($adjTotal > 0) ? round(($adjCompleted / $adjTotal) * 100) : 0;
            $percentage = min(100, $percentage);

            // Update progres_materi
            $query = "INSERT INTO progres_materi (user_id, material_id, progress_percentage, last_accessed_at) 
                      VALUES (:uid, :mid, :pct, NOW())
                      ON CONFLICT (user_id, material_id) 
                      DO UPDATE SET progress_percentage = :pct, last_accessed_at = NOW()";
            
            $this->db->prepare($query)->execute([
                'uid' => $user_id, 
                'mid' => $material_id, 
                'pct' => $percentage
            ]);

            self::clearUserProgressCache($user_id);
        } catch (Exception $e) {
            error_log("Sync material progress error: " . $e->getMessage());
        }
    }

    /**
     * Get user learning progress summary
     */
    public function getUserProgressSummary(int $user_id): ?array {
        // Simple APCu cache (30 seconds) to reduce DB load on frequent dashboard loads
        $cacheKey = "progress_summary_{$user_id}";
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_array($cached)) {
                return $cached;
            }
        }
        try {
            // New Sync Logic: Get all detailed material progress first
            $materials = $this->getDetailedMaterialProgress($user_id);
            
            $total_materials = count($materials);
            $materials_completed = 0;
            $sum_percentages = 0;
            
            foreach ($materials as $m) {
                if ($m['percentage'] >= 100) $materials_completed++;
                $sum_percentages += $m['percentage'];
            }

            // Quiz stats use the best earned score per quiz so dashboard, profile, and leaderboard match.
            $queryQuiz = "SELECT 
                            COUNT(*)::int as quizzes_completed,
                            COALESCE(AVG(percentage), 0)::float as average_quiz_score,
                            COALESCE(SUM(score), 0)::int as total_points
                          FROM (
                            SELECT DISTINCT ON (quiz_id) quiz_id, percentage, score
                            FROM hasil_kuis
                            WHERE user_id = :uid 
                            ORDER BY quiz_id, score DESC, percentage DESC, submitted_at DESC
                          ) t";

            $stmt = $this->db->prepare($queryQuiz);
            $stmt->execute(['uid' => $user_id]);
            $quizData = $stmt->fetch();

            // Overall percentage is now average of all material percentages
            $completion_percentage = ($total_materials > 0) ? ($sum_percentages / $total_materials) : 0;

            $summaryData = [
                'materials_completed' => $materials_completed,
                'total_materials' => $total_materials,
                'quizzes_completed' => (int) ($quizData['quizzes_completed'] ?? 0),
                'average_quiz_score' => round((float) ($quizData['average_quiz_score'] ?? 0), 2),
                'total_points' => (int) ($quizData['total_points'] ?? 0),
                'completion_percentage' => round($completion_percentage, 0)
            ];
            // Store in APCu cache for 30 seconds to reduce DB load on frequent dashboard calls
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $summaryData, 30);
            }
            return $summaryData;
        } catch (Exception $e) {
            error_log("Get user progress summary error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get detailed progress for each material for a specific user
     */
    public function getDetailedMaterialProgress(int $user_id): array {
        $cacheKey = "progress_detail_{$user_id}";
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_array($cached)) {
                return $cached;
            }
        }

        try {
            $query = "SELECT 
                        m.id, m.title, m.category, m.thumbnail,
                        COALESCE(te.total_episodes, 0) AS total_episodes,
                        COALESCE(ce.completed_episodes, 0) AS completed_episodes,
                        (pm.completed_at IS NOT NULL) AS main_completed
                     FROM materi m
                     LEFT JOIN (
                        SELECT material_id, COUNT(*)::int AS total_episodes
                        FROM sub_materi
                        GROUP BY material_id
                     ) te ON te.material_id = m.id
                     LEFT JOIN (
                        SELECT sm.material_id, COUNT(*)::int AS completed_episodes
                        FROM progres_sub_materi psm
                        JOIN sub_materi sm ON psm.sub_material_id = sm.id
                        WHERE psm.user_id = :uid
                        GROUP BY sm.material_id
                     ) ce ON ce.material_id = m.id
                     LEFT JOIN progres_materi pm ON pm.material_id = m.id AND pm.user_id = :uid2
                     WHERE m.status = 'active'
                     ORDER BY m.category, m.title";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['uid' => $user_id, 'uid2' => $user_id]);
            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

            $result = array_map(function($m) {
                $total = (int) $m['total_episodes'];
                $completed = (int) $m['completed_episodes'];
                $mainDone = (bool) $m['main_completed'];
                
                // Add 1 to total and completed for the 'Main Material' part
                $adjTotal = $total + 1;
                $adjCompleted = $completed + ($mainDone ? 1 : 0);
                
                $percentage = ($adjTotal > 0) ? ($adjCompleted / $adjTotal) * 100 : 0;
                
                // Clamp at 100% just in case
                $percentage = min(100, round($percentage, 0));

                return [
                    'id' => $m['id'],
                    'title' => $m['title'],
                    'category' => $m['category'],
                    'thumbnail' => $m['thumbnail'],
                    'percentage' => $percentage,
                    'is_fully_completed' => ($percentage >= 100)
                ];
            }, $materials);

            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $result, 30);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Get detailed material progress error: " . $e->getMessage());
            return [];
        }
    }

    public function getProgressByCategory(int $user_id): array {
        try {
            // Perfect Sync: Group material progress by category
            $materials = $this->getDetailedMaterialProgress($user_id);
            
            if (empty($materials)) return [];

            $categories = [];
            foreach ($materials as $m) {
                $catName = $m['category'];
                if (!isset($categories[$catName])) {
                    $categories[$catName] = [
                        'category' => $catName,
                        'total_materials' => 0,
                        'completed_materials' => 0,
                        'total_percentage' => 0
                    ];
                }
                $categories[$catName]['total_materials']++;
                $categories[$catName]['total_percentage'] += $m['percentage'];
                if ($m['percentage'] >= 100) {
                    $categories[$catName]['completed_materials']++;
                }
            }

            return array_map(function($cat) {
                return [
                    'category' => $cat['category'],
                    'total' => $cat['total_materials'],
                    'completed' => $cat['completed_materials'],
                    'percentage' => round($cat['total_percentage'] / $cat['total_materials'], 0)
                ];
            }, array_values($categories));
        } catch (Exception $e) {
            error_log("Get progress by category error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get user's quiz performance
     */
    public function getQuizPerformance(int $user_id, int $limit = 10): array {
        try {
            $query = "SELECT q.title, hk.score, hk.total_points, hk.percentage, hk.submitted_at, q.passing_score 
                     FROM hasil_kuis hk
                     JOIN kuis q ON hk.quiz_id = q.id
                     WHERE hk.user_id = :uid
                     ORDER BY hk.submitted_at DESC
                     LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get quiz performance error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get recently completed materials for a user.
     */
    public function getCompletedMaterials(int $user_id, int $limit = 10): array {
        try {
            $query = "SELECT 
                        m.id,
                        m.title,
                        m.category,
                        m.thumbnail,
                        pm.completed_at,
                        progress_live.live_percentage as progress_percentage
                      FROM progres_materi pm
                      JOIN materi m ON pm.material_id = m.id
                      LEFT JOIN (
                        SELECT
                            m2.id as material_id,
                            pm2.user_id,
                            LEAST(
                                100,
                                ROUND(
                                    (
                                        COALESCE(ps.completed_episodes, 0)
                                        + CASE WHEN pm2.completed_at IS NOT NULL THEN 1 ELSE 0 END
                                    )::numeric
                                    / NULLIF(COALESCE(ts.total_episodes, 0) + 1, 0)
                                    * 100
                                )
                            ) as live_percentage
                        FROM progres_materi pm2
                        JOIN materi m2 ON m2.id = pm2.material_id AND m2.status = 'active'
                        LEFT JOIN (
                            SELECT material_id, COUNT(*)::int as total_episodes
                            FROM sub_materi
                            GROUP BY material_id
                        ) ts ON ts.material_id = m2.id
                        LEFT JOIN (
                            SELECT sm.material_id, psm.user_id, COUNT(*)::int as completed_episodes
                            FROM progres_sub_materi psm
                            JOIN sub_materi sm ON sm.id = psm.sub_material_id
                            GROUP BY sm.material_id, psm.user_id
                        ) ps ON ps.material_id = m2.id AND ps.user_id = pm2.user_id
                      ) progress_live ON progress_live.material_id = m.id AND progress_live.user_id = pm.user_id
                      WHERE pm.user_id = :uid AND m.status = 'active' AND COALESCE(progress_live.live_percentage, 0) >= 100
                      ORDER BY COALESCE(pm.completed_at, pm.last_accessed_at) DESC
                      LIMIT :limit";

            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':uid', $user_id, PDO::PARAM_INT);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get completed materials error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Get achievements earned by a user.
     */
    public function getAchievements(int $user_id): array {
        try {
            $query = "SELECT id, name, description, icon, earned_at
                      FROM pencapaian
                      WHERE user_id = :uid
                      ORDER BY earned_at DESC";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['uid' => $user_id]);

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get achievements error: " . $e->getMessage());
            return [];
        }
    }

    /**
     * Papan Peringkat (Leaderboard) - Refactored for stability
     */
    public function getLeaderboard(int $limit = 10): array {
        $limit = max(1, min(50, $limit));
        $cacheKey = "leaderboard_{$limit}";
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_array($cached)) {
                return $cached;
            }
        }

        try {
            $query = "SELECT 
                        u.id,
                        u.username, 
                        u.full_name, 
                        u.avatar,
                        COALESCE(utp.total_points, 0) as total_points,
                        COALESCE(ucm.completed_count, 0) as materials_completed
                      FROM pengguna u
                      LEFT JOIN (
                        SELECT user_id, SUM(max_points) as total_points
                        FROM (
                            SELECT DISTINCT ON (user_id, quiz_id) user_id, quiz_id, score as max_points
                            FROM hasil_kuis
                            ORDER BY user_id, quiz_id, score DESC, percentage DESC, submitted_at DESC
                        ) t
                        GROUP BY user_id
                      ) utp ON u.id = utp.user_id
                      LEFT JOIN (
                        SELECT user_id, COUNT(*) as completed_count
                        FROM (
                            SELECT
                                m.id as material_id,
                                pm.user_id,
                                LEAST(
                                    100,
                                    ROUND(
                                        (
                                            COALESCE(ps.completed_episodes, 0)
                                            + CASE WHEN pm.completed_at IS NOT NULL THEN 1 ELSE 0 END
                                        )::numeric
                                        / NULLIF(COALESCE(ts.total_episodes, 0) + 1, 0)
                                        * 100
                                    )
                                ) as live_percentage
                            FROM progres_materi pm
                            JOIN materi m ON m.id = pm.material_id AND m.status = 'active'
                            LEFT JOIN (
                                SELECT material_id, COUNT(*)::int as total_episodes
                                FROM sub_materi
                                GROUP BY material_id
                            ) ts ON ts.material_id = m.id
                            LEFT JOIN (
                                SELECT sm.material_id, psm.user_id, COUNT(*)::int as completed_episodes
                                FROM progres_sub_materi psm
                                JOIN sub_materi sm ON sm.id = psm.sub_material_id
                                GROUP BY sm.material_id, psm.user_id
                            ) ps ON ps.material_id = m.id AND ps.user_id = pm.user_id
                        ) progress_live
                        WHERE live_percentage >= 100
                        GROUP BY user_id
                      ) ucm ON u.id = ucm.user_id

                      WHERE u.role = 'student' AND u.is_active = TRUE
                      ORDER BY total_points DESC, materials_completed DESC, u.created_at ASC
                      LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            $result = $stmt->fetchAll();
            if (function_exists('apcu_store')) {
                apcu_store($cacheKey, $result, 30);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Get leaderboard error: " . $e->getMessage());
            return [];
        }
    }

    public function getUserRank(int $user_id): ?array {
        $cacheKey = "user_rank_{$user_id}";
        if (function_exists('apcu_fetch')) {
            $cached = apcu_fetch($cacheKey, $success);
            if ($success && is_array($cached)) {
                return $cached;
            }
        }

        try {
            $query = "SELECT ranked.rank, ranked.total_points, ranked.materials_completed
                      FROM (
                        SELECT
                            u.id,
                            COALESCE(utp.total_points, 0) as total_points,
                            COALESCE(ucm.completed_count, 0) as materials_completed,
                            RANK() OVER (
                                ORDER BY COALESCE(utp.total_points, 0) DESC,
                                         COALESCE(ucm.completed_count, 0) DESC,
                                         u.created_at ASC
                            ) as rank
                        FROM pengguna u
                        LEFT JOIN (
                            SELECT user_id, SUM(max_points) as total_points
                            FROM (
                                SELECT DISTINCT ON (user_id, quiz_id) user_id, quiz_id, score as max_points
                                FROM hasil_kuis
                                ORDER BY user_id, quiz_id, score DESC, percentage DESC, submitted_at DESC
                            ) t
                            GROUP BY user_id
                        ) utp ON u.id = utp.user_id
                        LEFT JOIN (
                            SELECT user_id, COUNT(*) as completed_count
                            FROM (
                                SELECT
                                    m.id as material_id,
                                    pm.user_id,
                                    LEAST(
                                        100,
                                        ROUND(
                                            (
                                                COALESCE(ps.completed_episodes, 0)
                                                + CASE WHEN pm.completed_at IS NOT NULL THEN 1 ELSE 0 END
                                            )::numeric
                                            / NULLIF(COALESCE(ts.total_episodes, 0) + 1, 0)
                                            * 100
                                        )
                                    ) as live_percentage
                                FROM progres_materi pm
                                JOIN materi m ON m.id = pm.material_id AND m.status = 'active'
                                LEFT JOIN (
                                    SELECT material_id, COUNT(*)::int as total_episodes
                                    FROM sub_materi
                                    GROUP BY material_id
                                ) ts ON ts.material_id = m.id
                                LEFT JOIN (
                                    SELECT sm.material_id, psm.user_id, COUNT(*)::int as completed_episodes
                                    FROM progres_sub_materi psm
                                    JOIN sub_materi sm ON sm.id = psm.sub_material_id
                                    GROUP BY sm.material_id, psm.user_id
                                ) ps ON ps.material_id = m.id AND ps.user_id = pm.user_id
                            ) progress_live
                            WHERE live_percentage >= 100
                            GROUP BY user_id
                        ) ucm ON u.id = ucm.user_id
                        WHERE u.role = 'student' AND u.is_active = TRUE
                      ) ranked
                      WHERE ranked.id = :uid";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['uid' => $user_id]);
            $rank = $stmt->fetch(PDO::FETCH_ASSOC);

            $result = $rank ?: null;
            if ($result && function_exists('apcu_store')) {
                apcu_store($cacheKey, $result, 30);
            }

            return $result;
        } catch (Exception $e) {
            error_log("Get user rank error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get learning streak (consecutive days of activity)
     */
    public function getLearningStreak(int $user_id): array {
        try {
            $query = "SELECT streak_count, last_active_date FROM pengguna WHERE id = ?";
            $stmt = $this->db->prepare($query);
            $stmt->execute([$user_id]);
            $user = $stmt->fetch();

            if (!$user) return ['active_days' => 0];

            $today = date('Y-m-d');
            $yesterday = date('Y-m-d', strtotime('-1 day'));
            $last_date = $user['last_active_date'];
            $streak = (int) $user['streak_count'];

            // If user hasn't been active today OR yesterday, the streak shown to UI should be 0 (expired)
            // But we only reset it in database when they actually log in next time.
            if ($last_date !== $today && $last_date !== $yesterday) {
                return ['active_days' => 0];
            }

            return ['active_days' => $streak];
        } catch (Exception $e) {
            error_log("Get learning streak error: " . $e->getMessage());
            return ['active_days' => 0];
        }
    }

    /**
     * Update waktu terakhir akses materi
     */
    public function updateLastAccessed(int $user_id, int $material_id): bool {
        try {
            // Ensure record exists or update it
            $query = "INSERT INTO progres_materi (user_id, material_id, last_accessed_at, completed_at) 
                      VALUES (:uid, :mid, NOW(), NULL)
                      ON CONFLICT (user_id, material_id) 
                      DO UPDATE SET last_accessed_at = NOW()";
            
            $stmt = $this->db->prepare($query);
            return $stmt->execute(['uid' => $user_id, 'mid' => $material_id]);
        } catch (Exception $e) {
            error_log("Update last accessed error: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Mendapatkan materi terakhir yang diinteraksi oleh pengguna.
     */
    public function getLastViewedMaterial(int $user_id): ?array {
        try {
            $query = "SELECT m.id, m.title 
                      FROM materi m
                      JOIN progres_materi pm ON m.id = pm.material_id
                      WHERE pm.user_id = :uid AND m.status = 'active'
                      ORDER BY pm.last_accessed_at DESC NULLS LAST
                      LIMIT 1";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['uid' => $user_id]);
            $result = $stmt->fetch();

            return $result ?: null;
        } catch (Exception $e) {
            error_log("Get last viewed material error: " . $e->getMessage());
            return null;
        }
    }
}
