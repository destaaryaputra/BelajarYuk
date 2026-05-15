<?php

use App\Controllers\AuthController;
use App\Controllers\MaterialController;
use App\Controllers\QuizController;
use App\Controllers\ProgressController;
use App\Controllers\AIController;

return [
    'POST /auth/register' => [AuthController::class, 'register'],
    'POST /auth/login' => [AuthController::class, 'login'],
    'POST /auth/logout' => [AuthController::class, 'logout'],
    'GET /auth/current-user' => [AuthController::class, 'getCurrentUser'],
    'PUT /auth/profile' => [AuthController::class, 'updateProfile'],
    'GET /auth/users' => [AuthController::class, 'getAllUsers'],
    'POST /auth/users/update-role' => [AuthController::class, 'updateUserRole'],
    'POST /auth/users/delete' => [AuthController::class, 'deleteUser'],

    'GET /materials' => [MaterialController::class, 'getAllMaterials'],
    'GET /materials/detail' => [MaterialController::class, 'getMaterial'],
    'GET /materials/categories' => [MaterialController::class, 'getCategories'],
    'POST /materials/mark-completed' => [MaterialController::class, 'markAsCompleted'],
    'POST /materials/create' => [MaterialController::class, 'createMaterial'],
    'POST /materials/update' => [MaterialController::class, 'updateMaterial'],
    'POST /materials/delete' => [MaterialController::class, 'deleteMaterial'],
    'GET /materials/sub' => [MaterialController::class, 'getSubMaterialsAdmin'],
    'POST /materials/sub/create' => [MaterialController::class, 'createSubMaterial'],
    'POST /materials/sub/update' => [MaterialController::class, 'updateSubMaterial'],
    'POST /materials/sub/delete' => [MaterialController::class, 'deleteSubMaterial'],
    'GET /materials/comments' => [MaterialController::class, 'getComments'],
    'POST /materials/comments/add' => [MaterialController::class, 'addComment'],
    'GET /materials/comments/admin' => [MaterialController::class, 'getAllCommentsAdmin'],
    'POST /materials/comments/delete' => [MaterialController::class, 'deleteCommentAdmin'],

    'GET /quiz' => [QuizController::class, 'getQuiz'],
    'GET /quiz/questions' => [QuizController::class, 'getQuestions'],
    'POST /quiz/submit' => [QuizController::class, 'submitQuiz'],
    'GET /quiz/results' => [QuizController::class, 'getUserResults'],
    'GET /quiz/admin-report' => [QuizController::class, 'getAdminReport'],
    'POST /quiz/create' => [QuizController::class, 'createQuizAdmin'],
    'POST /quiz/questions/add' => [QuizController::class, 'addQuestionAdmin'],
    'POST /quiz/questions/delete' => [QuizController::class, 'deleteQuestionAdmin'],
    'POST /quiz/delete' => [QuizController::class, 'deleteQuizAdmin'],

    'GET /progress/summary' => [ProgressController::class, 'getSummary'],
    'GET /progress/dashboard' => [ProgressController::class, 'getDashboardData'],
    'GET /progress/categories' => [ProgressController::class, 'getByCategory'],
    'GET /progress/completed-materials' => [ProgressController::class, 'getCompletedMaterials'],
    'GET /progress/quiz-performance' => [ProgressController::class, 'getQuizPerformance'],
    'GET /progress/streak' => [ProgressController::class, 'getLearningStreak'],
    'GET /progress/achievements' => [ProgressController::class, 'getAchievements'],
    'GET /progress/leaderboard' => [ProgressController::class, 'getLeaderboard'],
    'POST /progress/track' => [ProgressController::class, 'trackActivity'],

    'POST /ai/chat' => [AIController::class, 'chat'],
    'GET /ai/history' => [AIController::class, 'history'],
    'POST /ai/clear-history' => [AIController::class, 'clearHistory'],
    'POST /ai/generate-course' => [AIController::class, 'generateCourse'],
];
