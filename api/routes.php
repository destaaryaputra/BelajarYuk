<?php

/**
 * Belajaryuk API Routes
 * Standard: Full String Mapping (Prevent early class resolution)
 */

return [
    'POST /auth/register' => ['App\\Controllers\\AuthController', 'register'],
    'POST /auth/login' => ['App\\Controllers\\AuthController', 'login'],
    'POST /auth/logout' => ['App\\Controllers\\AuthController', 'logout'],
    'GET /auth/current-user' => ['App\\Controllers\\AuthController', 'getCurrentUser'],
    'PUT /auth/profile' => ['App\\Controllers\\AuthController', 'updateProfile'],
    'POST /auth/profile' => ['App\\Controllers\\AuthController', 'updateProfile'],
    'POST /auth/profile/update' => ['App\\Controllers\\AuthController', 'updateProfile'],
    'POST /auth/avatar' => ['App\\Controllers\\AuthController', 'updateAvatar'],
    'GET /auth/users' => ['App\\Controllers\\AuthController', 'getAllUsers'],
    'POST /auth/users/update-role' => ['App\\Controllers\\AuthController', 'updateUserRole'],
    'POST /auth/users/delete' => ['App\\Controllers\\AuthController', 'deleteUser'],

    'GET /materials' => ['App\\Controllers\\MaterialController', 'getAllMaterials'],
    'GET /materials/detail' => ['App\\Controllers\\MaterialController', 'getMaterial'],
    'GET /materials/categories' => ['App\\Controllers\\MaterialController', 'getCategories'],
    'POST /materials/mark-completed' => ['App\\Controllers\\MaterialController', 'markAsCompleted'],
    'POST /materials/create' => ['App\\Controllers\\MaterialController', 'createMaterial'],
    'POST /materials/update' => ['App\\Controllers\\MaterialController', 'updateMaterial'],
    'POST /materials/delete' => ['App\\Controllers\\MaterialController', 'deleteMaterial'],
    'GET /materials/sub' => ['App\\Controllers\\MaterialController', 'getSubMaterialsAdmin'],
    'POST /materials/sub/create' => ['App\\Controllers\\MaterialController', 'createSubMaterial'],
    'POST /materials/sub/update' => ['App\\Controllers\\MaterialController', 'updateSubMaterial'],
    'POST /materials/sub/delete' => ['App\\Controllers\\MaterialController', 'deleteSubMaterial'],
    'GET /materials/comments' => ['App\\Controllers\\MaterialController', 'getComments'],
    'POST /materials/comments/add' => ['App\\Controllers\\MaterialController', 'addComment'],
    'GET /materials/comments/admin' => ['App\\Controllers\\MaterialController', 'getAllCommentsAdmin'],
    'POST /materials/comments/delete' => ['App\\Controllers\\MaterialController', 'deleteCommentAdmin'],

    'GET /quiz' => ['App\\Controllers\\QuizController', 'getQuiz'],
    'GET /quiz/questions' => ['App\\Controllers\\QuizController', 'getQuestions'],
    'GET /quiz/list-admin' => ['App\\Controllers\\QuizController', 'listQuizzesAdmin'],
    'POST /quiz/submit' => ['App\\Controllers\\QuizController', 'submitQuiz'],
    'GET /quiz/results' => ['App\\Controllers\\QuizController', 'getUserResults'],
    'GET /quiz/admin-report' => ['App\\Controllers\\QuizController', 'getAdminReport'],
    'POST /quiz/create' => ['App\\Controllers\\QuizController', 'createQuizAdmin'],
    'POST /quiz/questions/add' => ['App\\Controllers\\QuizController', 'addQuestionAdmin'],
    'POST /quiz/questions/delete' => ['App\\Controllers\\QuizController', 'deleteQuestionAdmin'],
    'POST /quiz/delete' => ['App\\Controllers\\QuizController', 'deleteQuizAdmin'],

    'GET /progress/summary' => ['App\\Controllers\\ProgressController', 'getSummary'],
    'GET /progress/dashboard' => ['App\\Controllers\\ProgressController', 'getDashboardData'],
    'GET /progress/categories' => ['App\\Controllers\\ProgressController', 'getByCategory'],
    'GET /progress/completed-materials' => ['App\\Controllers\\ProgressController', 'getCompletedMaterials'],
    'GET /progress/quiz-performance' => ['App\\Controllers\\ProgressController', 'getQuizPerformance'],
    'GET /progress/streak' => ['App\\Controllers\\ProgressController', 'getLearningStreak'],
    'GET /progress/achievements' => ['App\\Controllers\\ProgressController', 'getAchievements'],
    'GET /progress/leaderboard' => ['App\\Controllers\\ProgressController', 'getLeaderboard'],
    'POST /progress/track' => ['App\\Controllers\\ProgressController', 'trackActivity'],

    'POST /ai/chat' => ['App\\Controllers\\AIController', 'chat'],
    'GET /ai/history' => ['App\\Controllers\\AIController', 'history'],
    'POST /ai/clear-history' => ['App\\Controllers\\AIController', 'clearHistory'],
    'POST /ai/generate-course' => ['App\\Controllers\\AIController', 'generateCourse'],
];
