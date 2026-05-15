<?php

/**
 * Vercel PHP Entry Point - Belajaryuk
 * Definitive Absolute Path & PSR-4 Autoloader
 */

// 1. Strict Error Reporting
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 2. Fatal Error Handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        
        $base = realpath(__DIR__ . '/..');
        echo json_encode([
            'success' => false, 
            'message' => 'Fatal Server Error', 
            'debug' => $error['message'],
            'path' => $error['file'],
            'line' => $error['line'],
            'base_dir' => $base
        ]);
    }
});

// 3. Absolute Base Directory
$root = realpath(__DIR__ . '/..');

// 4. Standardized PSR-4 Autoloader for App\ namespace
spl_autoload_register(function ($class) use ($root) {
    if (strpos($class, 'App\\') !== 0) return;
    
    // Convert namespace to path (App\Controllers\AuthController -> Controllers/AuthController.php)
    $relativeClass = substr($class, 4);
    $path = str_replace('\\', '/', $relativeClass) . '.php';
    
    // Check in src folder with various casing just in case
    $files = [
        $root . '/src/' . $path,
        $root . '/src/' . strtolower($path),
        $root . '/src/' . ucfirst($path)
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// 5. Load Third Party Dependencies (Composer)
$composer = $root . '/vendor/autoload.php';
if (file_exists($composer)) {
    require_once $composer;
}

// 6. Load Config
$config = $root . '/src/Config/lingkungan.php';
if (file_exists($config)) {
    require_once $config;
}

// 7. Routing
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// Frontend
$index = $root . '/public/index.php';
if (file_exists($index)) {
    require $index;
} else {
    echo "<h1>Belajaryuk</h1><p>Frontend entry point not found.</p>";
}
