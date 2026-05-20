<?php

namespace App\Controllers;

use App\Models\Progress;
use App\Models\Material;
use App\Utils\Response;
use App\Middlewares\AuthMiddleware;
use App\Middlewares\CSRFMiddleware;
use Exception;

/**
 * Progress Controller
 * Handle learning progress operations
 */

class ProgressController {
    private ?Progress $progressModel;

    public function __construct() {
        $this->progressModel = new Progress();
    }

    /**
     * Get consolidated dashboard data (Optimized for speed)
     */
    public function getDashboardData(): void {
        AuthMiddleware::requireAuth();

        try {
            $user = AuthMiddleware::getAuthUser();
            
            // Sync Learning Streak
            $userModel = new \App\Models\User();
            $userModel->updateStreak($user['id']);

            // Parallel fetching at DB level or sequential but in one request
            $summary = $this->progressModel->getUserProgressSummary($user['id']) ?: [];
            $streakData = $this->progressModel->getLearningStreak($user['id']) ?: [];
            $lastMaterial = $this->progressModel->getLastViewedMaterial($user['id']);
            
            $materialModel = new Material();
            $recentMaterials = $materialModel->getAllMaterials(1, 4) ?: [];
            
            $leaderboard = $this->progressModel->getLeaderboard(5) ?: [];

            $dashboardData = [
                'summary' => [
                    'completed' => intval($summary['materials_completed'] ?? 0),
                    'total' => intval($summary['total_materials'] ?? 0),
                    'avg_score' => isset($summary['average_quiz_score']) ? round($summary['average_quiz_score']) : 0,
                    'total_points' => intval($summary['total_points'] ?? 0),
                    'streak' => intval($streakData['active_days'] ?? 0),
                    'last_material' => $lastMaterial
                ],
                'recent_materials' => $recentMaterials,
                'leaderboard' => $leaderboard
            ];

            Response::success('Data dasbor berhasil dimuat', $dashboardData);
        } catch (Exception $e) {
            error_log("Get dashboard data error: " . $e->getMessage());
            Response::error('Gagal mengambil data dasbor', null, 500);
        }
    }

    /**
     * Get user's learning progress summary (protected)
     */
    public function getSummary(): void {
        AuthMiddleware::requireAuth();

        try {
            $user = AuthMiddleware::getAuthUser();
            $summary = $this->progressModel->getUserProgressSummary($user['id']) ?: [];
            $streakData = $this->progressModel->getLearningStreak($user['id']) ?: [];
            $lastMaterial = $this->progressModel->getLastViewedMaterial($user['id']);

            $formattedSummary = [
                'completed' => intval($summary['materials_completed'] ?? 0),
                'total' => intval($summary['total_materials'] ?? 0),
                'avg_score' => isset($summary['average_quiz_score']) ? round($summary['average_quiz_score']) : 0,
                'total_points' => intval($summary['total_points'] ?? 0),
                'streak' => intval($streakData['active_days'] ?? 0),
                'last_material' => $lastMaterial
            ];

            Response::success('Ringkasan progres berhasil diambil', $formattedSummary);
        } catch (Exception $e) {
            error_log("Get progress summary error: " . $e->getMessage());
            Response::error('Gagal mengambil ringkasan progres', null, 500);
        }
    }

    /**
     * Get progress per kategori (protected)
     */
    public function getByCategory(): void {
        AuthMiddleware::requireAuth();

        try {
            $user = AuthMiddleware::getAuthUser();
            $progressRaw = $this->progressModel->getProgressByCategory($user['id']) ?: [];
            $materialProgress = $this->progressModel->getDetailedMaterialProgress($user['id']) ?: [];

            $categories = array_map(function($cat) {
                return [
                    'category' => $cat['category'],
                    'total' => intval($cat['total_materials']),
                    'completed' => intval($cat['completed_materials']),
                    'percentage' => floatval($cat['completion_percentage'])
                ];
            }, $progressRaw);

            Response::success('Data progres berhasil diambil', [
                'categories' => $categories,
                'materials' => $materialProgress
            ]);
        } catch (Exception $e) {
            error_log("Get progress by category error: " . $e->getMessage());
            Response::error('Gagal mengambil data progres', null, 500);
        }
    }

    /**
     * Get quiz performance (protected)
     */
    public function getQuizPerformance(): void {
        AuthMiddleware::requireAuth();

        try {
            $user = AuthMiddleware::getAuthUser();
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $performance = $this->progressModel->getQuizPerformance($user['id'], $limit) ?: [];

            Response::success('Performa kuis berhasil diambil', $performance);
        } catch (Exception $e) {
            error_log("Get quiz performance error: " . $e->getMessage());
            Response::error('Gagal mengambil performa kuis', null, 500);
        }
    }

    /**
     * Get completed materials (protected)
     */
    public function getCompletedMaterials(): void {
        AuthMiddleware::requireAuth();

        try {
            $user = AuthMiddleware::getAuthUser();
            $limit = isset($_GET['limit']) ? max(1, intval($_GET['limit'])) : 10;
            $materials = $this->progressModel->getCompletedMaterials($user['id'], $limit) ?: [];

            Response::success('Materi selesai berhasil diambil', $materials);
        } catch (Exception $e) {
            error_log("Get completed materials error: " . $e->getMessage());
            Response::error('Gagal mengambil daftar materi selesai', null, 500);
        }
    }

    /**
     * Get learning leaderboard (protected)
     */
    public function getLeaderboard(): void {
        AuthMiddleware::requireAuth();

        try {
            $limit = isset($_GET['limit']) ? intval($_GET['limit']) : 10;
            $leaderboard = $this->progressModel->getLeaderboard($limit) ?: [];

            Response::success('Papan peringkat berhasil diambil', $leaderboard);
        } catch (Exception $e) {
            error_log("Get leaderboard error: " . $e->getMessage());
            Response::error('Gagal mengambil papan peringkat', null, 500);
        }
    }

    /**
     * Get learning streak (protected)
     */
    public function getLearningStreak(): void {
        AuthMiddleware::requireAuth();

        try {
            $user = AuthMiddleware::getAuthUser();
            $streak = $this->progressModel->getLearningStreak($user['id']) ?: ['active_days' => 0];

            Response::success('Streak belajar berhasil diambil', $streak);
        } catch (Exception $e) {
            error_log("Get learning streak error: " . $e->getMessage());
            Response::error('Gagal mengambil streak belajar', null, 500);
        }
    }

    /**
     * Get earned achievements (protected)
     */
    public function getAchievements(): void {
        AuthMiddleware::requireAuth();

        try {
            $user = AuthMiddleware::getAuthUser();
            $achievements = $this->progressModel->getAchievements($user['id']) ?: [];

            Response::success('Pencapaian berhasil diambil', $achievements);
        } catch (Exception $e) {
            error_log("Get achievements error: " . $e->getMessage());
            Response::error('Gagal mengambil pencapaian', null, 500);
        }
    }

    /**
     * Track user activity (last viewed material)
     */
    public function trackActivity(): void {
        AuthMiddleware::requireAuth();

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            Response::error('Aksi tidak diizinkan', null, 405);
        }

        CSRFMiddleware::verify();

        try {
            $data = json_decode(file_get_contents("php://input"), true);
            $materialId = intval($data['material_id'] ?? 0);

            if ($materialId <= 0) {
                Response::error('ID Materi tidak valid', null, 400);
            }

            $user = AuthMiddleware::getAuthUser();
            $this->progressModel->updateLastAccessed($user['id'], $materialId);

            Response::success('Aktivitas berhasil dicatat');
        } catch (Exception $e) {
            error_log("Track activity error: " . $e->getMessage());
            Response::error('Gagal mencatat aktivitas', null, 500);
        }
    }
}
