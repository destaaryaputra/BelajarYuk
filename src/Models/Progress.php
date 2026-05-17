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

    /**
     * Get user learning progress summary
     */
    public function getUserProgressSummary(int $user_id): ?array {
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

            // Quiz specific stats - BASED ON BEST ATTEMPTS for consistency
            $queryQuiz = "SELECT 
                            COUNT(DISTINCT quiz_id)::int as quizzes_completed,
                            COALESCE(AVG(max_percentage), 0)::float as average_quiz_score,
                            COALESCE(SUM(max_score), 0)::int as total_points
                          FROM (
                            SELECT quiz_id, MAX(percentage) as max_percentage, MAX(score) as max_score 
                            FROM hasil_kuis 
                            WHERE user_id = :uid 
                            GROUP BY quiz_id
                          ) t";

            $stmt = $this->db->prepare($queryQuiz);
            $stmt->execute(['uid' => $user_id]);
            $quizData = $stmt->fetch();

            // Overall percentage is now average of all material percentages
            $completion_percentage = ($total_materials > 0) ? ($sum_percentages / $total_materials) : 0;

            return [
                'materials_completed' => $materials_completed,
                'total_materials' => $total_materials,
                'quizzes_completed' => (int) ($quizData['quizzes_completed'] ?? 0),
                'average_quiz_score' => round((float) ($quizData['average_quiz_score'] ?? 0), 2),
                'total_points' => (int) ($quizData['total_points'] ?? 0),
                'completion_percentage' => round($completion_percentage, 0)
            ];
        } catch (Exception $e) {
            error_log("Get user progress summary error: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Get detailed progress for each material for a specific user
     */
    public function getDetailedMaterialProgress(int $user_id): array {
        try {
            // Logic: Count total sub_materials vs completed sub_materials for each active material
            $query = "SELECT 
                        m.id, m.title, m.category, m.thumbnail,
                        (SELECT COUNT(*)::int FROM sub_materi WHERE material_id = m.id) as total_episodes,
                        (SELECT COUNT(*)::int FROM progres_sub_materi psm 
                         JOIN sub_materi sm ON psm.sub_material_id = sm.id 
                         WHERE sm.material_id = m.id AND psm.user_id = :uid) as completed_episodes,
                        EXISTS(SELECT 1 FROM progres_materi WHERE user_id = :uid2 AND material_id = m.id AND completed_at IS NOT NULL) as main_completed
                     FROM materi m
                     WHERE m.status = 'active'
                     ORDER BY m.category, m.title";

            $stmt = $this->db->prepare($query);
            $stmt->execute(['uid' => $user_id, 'uid2' => $user_id]);
            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);

            return array_map(function($m) {
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
                        pm.progress_percentage
                      FROM progres_materi pm
                      JOIN materi m ON pm.material_id = m.id
                      WHERE pm.user_id = :uid AND m.status = 'active'
                      ORDER BY pm.completed_at DESC
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
                        SELECT user_id, SUM(max_score) as total_points
                        FROM (
                            SELECT user_id, quiz_id, MAX(score) as max_score
                            FROM hasil_kuis
                            GROUP BY user_id, quiz_id
                        ) t
                        GROUP BY user_id
                      ) utp ON u.id = utp.user_id
                      LEFT JOIN (
                        SELECT user_id, COUNT(*) as completed_count
                        FROM progres_materi
                        GROUP BY user_id
                      ) ucm ON u.id = ucm.user_id
                      WHERE u.role = 'student' AND u.is_active = TRUE
                      ORDER BY total_points DESC, materials_completed DESC, u.created_at ASC
                      LIMIT :limit";
            
            $stmt = $this->db->prepare($query);
            $stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
            $stmt->execute();

            return $stmt->fetchAll();
        } catch (Exception $e) {
            error_log("Get leaderboard error: " . $e->getMessage());
            return [];
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
