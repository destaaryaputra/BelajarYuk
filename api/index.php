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

// Load environment first
require_once __DIR__ . '/../src/Config/lingkungan.php';

// Custom PSR-4 Autoloader for Vercel (Case-Sensitive friendly)
spl_autoload_register(function ($class) {
    $prefix = 'App\\';
    $base_dir = __DIR__ . '/../src/';
    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) return;
    $relative_class = substr($class, $len);
    
    // Map App\Controllers\AuthController to src/Controllers/AuthController.php
    $file = $base_dir . str_replace('\\', '/', $relative_class) . '.php';
    if (file_exists($file)) require $file;
});

// Load composer if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

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
