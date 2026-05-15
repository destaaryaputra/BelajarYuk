<?php

/**
 * Belajaryuk - Vercel API Entry Point
 * High-Performance Class Mapping & Resilient Bootstrapping
 */

// 1. Setup Environment & Errors
error_reporting(E_ALL);
ini_set('display_errors', '1');

// 2. Fatal Error Diagnostics (JSON Output)
register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        
        $root = dirname(__DIR__);
        $scan = [];
        if (is_dir($root . '/src')) {
            $scan = array_diff(scandir($root . '/src'), ['.', '..']);
        }

        echo json_encode([
            'success' => false, 
            'message' => 'Backend Error', 
            'debug' => $error['message'],
            'diagnostics' => [
                'root' => $root,
                'src_content' => $scan,
                'file' => basename($error['file']),
                'line' => $error['line']
            ]
        ]);
    }
});

$root = dirname(__DIR__);

// 3. Build Case-Insensitive Class Map (The "Fix-Everything" Map)
$classMap = [];
function buildMap($dir, &$map, $rootPath) {
    if (!is_dir($dir)) return;
    $items = scandir($dir);
    foreach ($items as $item) {
        if ($item === '.' || $item === '..') continue;
        $path = $dir . '/' . $item;
        if (is_dir($path)) {
            buildMap($path, $map, $rootPath);
        } elseif (substr($item, -4) === '.php') {
            $relative = str_replace($rootPath . '/src/', '', $path);
            $className = 'App\\' . str_replace(['/', '.php'], ['\\', ''], $relative);
            $map[strtolower($className)] = $path;
        }
    }
}
buildMap($root . '/src', $classMap, $root);

// 4. Register the Resilient Autoloader
spl_autoload_register(function ($class) use ($classMap) {
    $lower = strtolower($class);
    if (isset($classMap[$lower])) {
        require_once $classMap[$lower];
    }
}, true, true);

// 5. Load Composer (Third-party)
if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}

// 6. Load Config (Force load)
$configPath = $root . '/src/Config/lingkungan.php';
if (!file_exists($configPath)) $configPath = $root . '/src/config/lingkungan.php';
if (file_exists($configPath)) require_once $configPath;

// 7. Dynamic CORS
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 8. Router
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
if (strpos($uri, '/api') === 0) {
    require __DIR__ . '/router.php';
    exit;
}

// 9. Frontend
$frontend = $root . '/public/index.php';
if (file_exists($frontend)) {
    require $frontend;
} else {
    echo "<h1>Belajaryuk</h1><p>Application ready.</p>";
}
