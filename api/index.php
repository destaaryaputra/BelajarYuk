<?php

// API & Frontend router for Vercel
// Error Handling for Diagnostics
ini_set('display_errors', '0');
error_reporting(E_ALL);

set_error_handler(function($errno, $errstr, $errfile, $errline) {
    if (!(error_reporting() & $errno)) return;
    error_log("PHP Error ($errno): $errstr in $errfile on line $errline");
    return false;
});

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        header('Content-Type: application/json');
        http_response_code(500);
        echo json_encode([
            'success' => false, 
            'message' => 'Fatal Server Error', 
            'debug' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

// Check for vendor folder (Vercel deployment common issue)
if (!file_exists(__DIR__ . '/../vendor/autoload.php')) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Folder VENDOR tidak ditemukan!', 
        'solution' => 'Vercel tidak menjalankan composer secara otomatis. Kamu harus menghapus /vendor/ dari .gitignore dan mengunggahnya ke GitHub, atau gunakan build step.'
    ]);
    exit;
}

// Check for database driver
if (!extension_loaded('pdo_pgsql')) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        'success' => false, 
        'message' => 'Ekstensi PHP pdo_pgsql tidak aktif di Vercel!',
        'solution' => 'Gunakan runtime vercel-php yang mendukung PostgreSQL atau hubungi support.'
    ]);
    exit;
}

// Load environment first
require_once __DIR__ . '/../src/config/lingkungan.php';
require_once __DIR__ . '/../vendor/autoload.php';

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Jika request dimulai dengan /api, gunakan router API
if (strpos($request_uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// Jika bukan API, maka ini adalah request Frontend
// Sajikan index.html dari public (karena ini SPA biasanya)
$frontend_index = __DIR__ . '/../public/index.html';
$frontend_php = __DIR__ . '/../public/index.php';

if (file_exists($frontend_php)) {
    require $frontend_php;
} elseif (file_exists($frontend_index)) {
    echo file_get_contents($frontend_index);
} else {
    echo "<h2>Frontend Not Found</h2><p>Please check your public/ folder.</p>";
}
