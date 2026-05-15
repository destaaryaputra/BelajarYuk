<?php

/**
 * Vercel PHP Entry Point - Belajaryuk
 * Designed for Case-Sensitive Linux Environment
 */

// 1. Enforce strict error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 2. Fatal Error Handler (JSON Output)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        
        $baseDir = dirname(__DIR__);
        $srcTree = [];
        if (is_dir($baseDir . '/src')) {
            $iter = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($baseDir . '/src', RecursiveDirectoryIterator::SKIP_DOTS),
                RecursiveIteratorIterator::SELF_FIRST
            );
            foreach ($iter as $path => $dir) {
                $srcTree[] = str_replace($baseDir, '', $path);
            }
        }

        echo json_encode([
            'success' => false, 
            'message' => 'System Boot Error', 
            'debug' => $error['message'],
            'src_filesystem_snapshot' => $srcTree,
            'hint' => 'Check if your file is in the list above with the EXACT same casing.'
        ]);
    }
});

// 3. Absolute Path Setup
$baseDir = dirname(__DIR__);

// 4. Custom Robust PSR-4 Autoloader (Force App\ -> src/)
// This runs before composer to ensure our paths are prioritized
spl_autoload_register(function ($class) use ($baseDir) {
    if (strpos($class, 'App\\') !== 0) return;
    
    $relativeClass = str_replace('App\\', '', $class);
    $path = str_replace('\\', '/', $relativeClass) . '.php';
    
    // We try both PascalCase and lowercase because Windows/Git casing is tricky
    $files = [
        $baseDir . '/src/' . $path,
        $baseDir . '/src/' . strtolower($path),
        $baseDir . '/src/' . preg_replace_callback('/\/([a-z])/', function($m) { return strtoupper($m[0]); }, $path)
    ];
    
    foreach ($files as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}, true, true); // Prepend to the stack

// 5. Load Composer (Fallback for third party libs like Firebase JWT)
if (file_exists($baseDir . '/vendor/autoload.php')) {
    require_once $baseDir . '/vendor/autoload.php';
}

// 6. Load Environment Configuration
require_once $baseDir . '/src/Config/lingkungan.php';

// 7. Re-apply production settings if necessary
if (defined('ENV') && ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 8. API & Frontend Routing
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($request_uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// Default to frontend
$frontend_php = $baseDir . '/public/index.php';
if (file_exists($frontend_php)) {
    require $frontend_php;
} else {
    echo "<h2>Belajaryuk</h2><p>Application ready. Frontend index not found.</p>";
}
