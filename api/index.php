<?php

/**
 * Belajaryuk - Vercel Optimized Entry Point
 * Designed for maximum resilience and deep diagnostics
 */

// 1. Absolute Visibility
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 2. The "Truth-Teller" Shutdown Handler
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        
        $base = dirname(__DIR__);
        $searched = $base . '/src/Controllers/AuthController.php';
        
        // Scan for the real file to see what Vercel actually sees
        $realFile = "NOT_FOUND";
        $dirToScan = $base . '/src/Controllers';
        if (is_dir($dirToScan)) {
            $files = scandir($dirToScan);
            $realFile = "Found in " . basename($dirToScan) . ": " . implode(', ', $files);
        } elseif (is_dir(strtolower($dirToScan))) {
            $files = scandir(strtolower($dirToScan));
            $realFile = "Found in " . basename(strtolower($dirToScan)) . ": " . implode(', ', $files);
        }

        echo json_encode([
            'success' => false, 
            'message' => 'Backend Initialization Failed', 
            'debug' => $error['message'],
            'diagnostics' => [
                'expected_path' => $searched,
                'filesystem_check' => $realFile,
                'cwd' => getcwd(),
                'base' => $base
            ]
        ]);
    }
});

$base = dirname(__DIR__);

// 3. Ultimate Case-Insensitive Autoloader
spl_autoload_register(function ($class) use ($base) {
    if (strpos($class, 'App\\') !== 0) return;
    
    $relative = str_replace('App\\', '', $class);
    $relative = str_replace('\\', '/', $relative);
    
    $variations = [
        $base . '/src/' . $relative . '.php',
        $base . '/src/' . strtolower($relative) . '.php',
        strtolower($base . '/src/' . $relative . '.php')
    ];
    
    foreach ($variations as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
}, true, true);

// 4. Load Dependencies
if (file_exists($base . '/vendor/autoload.php')) {
    require_once $base . '/vendor/autoload.php';
}

// 5. Load Config (Hard load to ensure availability)
$configFile = $base . '/src/Config/lingkungan.php';
if (!file_exists($configFile)) $configFile = $base . '/src/config/lingkungan.php';

if (file_exists($configFile)) {
    require_once $configFile;
}

// 6. Router Dispatch
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

if (strpos($uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// 7. Frontend Fallback
$frontend = $base . '/public/index.php';
if (file_exists($frontend)) {
    require $frontend;
} else {
    echo "<h2>Belajaryuk Ready</h2><p>Please check your public folder.</p>";
}
