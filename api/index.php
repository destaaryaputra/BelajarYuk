<?php

// API & Frontend router for Vercel
error_reporting(E_ALL);
ini_set('display_errors', '1');

// Global Shutdown Handler for Fatal Errors
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) {
            header('Content-Type: application/json');
            http_response_code(500);
        }
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
require_once dirname(__DIR__) . '/src/Config/lingkungan.php';

// Use standard Composer Autoloader (It's already mapped App\ -> src/)
if (file_exists(dirname(__DIR__) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__) . '/vendor/autoload.php';
}

// Re-enforce error reporting if lingkungan.php changed it
if (defined('ENV') && ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
}

$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API Route Detection
if (strpos($request_uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// Frontend Fallback
$frontend_php = dirname(__DIR__) . '/public/index.php';
if (file_exists($frontend_php)) {
    require $frontend_php;
} else {
    echo "<h2>Belajaryuk</h2><p>Halaman utama tidak ditemukan.</p>";
}
