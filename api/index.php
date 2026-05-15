<?php

/**
 * Belajaryuk - Vercel Ultimate Bootloader
 * Force-loads all application files to bypass casing/autoload issues
 */

// 1. Error Visibility
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 2. Fatal Error Handler with Filesystem Debugging
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Critical Boot Error', 
            'debug' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

$root = dirname(__DIR__);

// 3. JURUS SAPU JAGAT: Force-load every PHP file in src/
// Ini memastikan SEMUA class (Controller, Model, Utils) sudah termuat di awal.
function forceLoadDir($dir) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . DIRECTORY_SEPARATOR . $item;
        if (is_dir($path)) {
            forceLoadDir($path);
        } elseif (substr($item, -4) === '.php') {
            require_once $path;
        }
    }
}

// Load folders in specific order to avoid dependency issues
$folders = ['Utils', 'Config', 'Models', 'Services', 'Middlewares', 'Controllers'];
foreach ($folders as $folder) {
    // Coba load folder dengan casing besar maupun kecil
    forceLoadDir($root . '/src/' . $folder);
    forceLoadDir($root . '/src/' . strtolower($folder));
}

// 4. Load Third-Party (Composer)
if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}

// 5. Dynamic CORS (Fixes your CORS error)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 6. Router Dispatch
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// 7. Frontend Fallback
$frontend = $root . '/public/index.php';
if (file_exists($frontend)) {
    require $frontend;
} else {
    echo "<h2>Belajaryuk Ready</h2><p>Check public/index.php</p>";
}
