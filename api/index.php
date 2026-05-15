<?php

/**
 * Vercel PHP Entry Point - Belajaryuk
 * MANUAL LOADING STRATEGY (Anti-Error Vercel)
 */

error_reporting(E_ALL);
ini_set('display_errors', '1');

// 1. Filesystem Spy & Error Handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        
        $base = realpath(__DIR__ . '/..');
        $files = [];
        if (is_dir($base . '/src')) {
            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base . '/src'));
            foreach ($it as $f) {
                if ($f->isFile()) $files[] = str_replace($base, '', $f->getPathname());
            }
        }
        
        echo json_encode([
            'success' => false, 
            'message' => 'Critical System Failure', 
            'debug' => $error['message'],
            'actual_file_tree' => $files, // Ini akan menunjukkan list file asli di Vercel
            'php_os' => PHP_OS
        ]);
    }
});

$root = realpath(__DIR__ . '/..');

// 2. Load Core Components Manually (Bypass Autoloader for Stability)
$coreFiles = [
    '/src/Utils/Response.php',
    '/src/Utils/Security.php',
    '/src/Config/lingkungan.php',
    '/src/Config/Database.php',
    '/src/Models/User.php',
    '/src/Services/AuthService.php',
    '/src/Controllers/AuthController.php',
    '/src/Controllers/AIController.php',
    '/src/Controllers/MaterialController.php',
    '/src/Controllers/ProgressController.php',
    '/src/Controllers/QuizController.php'
];

foreach ($coreFiles as $file) {
    // Coba load dengan casing asli, kalau gagal coba huruf kecil semua
    $path = $root . $file;
    if (file_exists($path)) {
        require_once $path;
    } else {
        $lowerPath = $root . strtolower($file);
        if (file_exists($lowerPath)) require_once $lowerPath;
    }
}

// 3. Load Composer for third-party libs
if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}

// 4. Standard Autoloader for remaining classes
spl_autoload_register(function ($class) use ($root) {
    if (strpos($class, 'App\\') !== 0) return;
    $relative = str_replace(['App\\', '\\'], ['', '/'], $class);
    $path = $root . '/src/' . $relative . '.php';
    if (file_exists($path)) require_once $path;
});

// 5. Dynamic CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 6. Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// 7. Frontend
$index = $root . '/public/index.php';
if (file_exists($index)) {
    require $index;
} else {
    echo "<h2>Belajaryuk</h2><p>Ready.</p>";
}
