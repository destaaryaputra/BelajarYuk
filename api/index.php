<?php

/**
 * Ultimate Vercel PHP Entry Point - Belajaryuk
 * Case-Insensitive Class Mapping & Robust Bootstrapping
 */

// 1. Force absolute error visibility
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 2. Fatal Error Diagnostics
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        
        echo json_encode([
            'success' => false, 
            'message' => 'Critical System Failure', 
            'debug' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line'],
            'server_os' => PHP_OS,
            'php_version' => PHP_VERSION
        ]);
    }
});

// 3. Project Root Resolution
$projectRoot = realpath(__DIR__ . '/..');

/**
 * 4. The "Ultimate Autoloader"
 * Scans the src folder and maps classes to files regardless of casing
 */
spl_autoload_register(function ($class) use ($projectRoot) {
    if (strpos($class, 'App\\') !== 0) return;
    
    static $classMap = null;
    if ($classMap === null) {
        $classMap = [];
        $srcPath = realpath($projectRoot . '/src');
        
        if ($srcPath && is_dir($srcPath)) {
            $directory = new RecursiveDirectoryIterator($srcPath);
            $iterator = new RecursiveIteratorIterator($directory);
            foreach ($iterator as $file) {
                if ($file->isFile() && $file->getExtension() === 'php') {
                    $fullPath = realpath($file->getPathname());
                    // Remove src base path and extension
                    $relative = str_replace([$srcPath, '.php'], '', $fullPath);
                    // Normalize separators to \
                    $relative = str_replace(['/', '\\'], '\\', $relative);
                    // Remove leading \ if exists
                    $relative = ltrim($relative, '\\');
                    
                    $className = 'App\\' . $relative;
                    $classMap[strtolower($className)] = $fullPath;
                }
            }
        }
    }
    
    $lookup = strtolower($class);
    if (isset($classMap[$lookup])) {
        require_once $classMap[$lookup];
    }
}, true, true);

// 5. Load Composer (Third-party libraries)
if (file_exists($projectRoot . '/vendor/autoload.php')) {
    require_once $projectRoot . '/vendor/autoload.php';
}

// 6. Load Config
if (file_exists($projectRoot . '/src/Config/lingkungan.php')) {
    require_once $projectRoot . '/src/Config/lingkungan.php';
} else {
    // Fallback if casing is different
    $configFallback = glob($projectRoot . '/src/[Cc]onfig/lingkungan.php');
    if (!empty($configFallback)) require_once $configFallback[0];
}

// 7. Production Overrides
if (defined('ENV') && ENV === 'production') {
    ini_set('display_errors', '0');
    error_reporting(0);
}

// 8. Dynamic CORS (Fixes the CORS suggestion)
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 9. API & Frontend Dispatcher
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// Default to frontend index
$frontendIndex = $projectRoot . '/public/index.php';
if (file_exists($frontendIndex)) {
    require $frontendIndex;
} else {
    echo "<h2>Belajaryuk</h2><p>Application ready, but frontend not found.</p>";
}
