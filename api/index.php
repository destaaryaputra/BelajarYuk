<?php

// API & Frontend router for Vercel
// Enforce error reporting for boot phase
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
            'message' => 'Fatal Server Error during boot', 
            'debug' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
    }
});

$baseDir = realpath(__DIR__ . '/..');

// Custom PSR-4 Autoloader for Vercel (Ultra-Resilient Case Handling)
spl_autoload_register(function ($class) {
    if (strpos($class, 'App\\') !== 0) return;
    
    $relativeClass = str_replace('App\\', '', $class);
    $path = str_replace('\\', '/', $relativeClass) . '.php';
    
    // Daftar kemungkinan jalur (Huruf Besar vs Huruf Kecil)
    $possibleFiles = [
        __DIR__ . '/../src/' . $path,                               // Casing asli (PascalCase)
        __DIR__ . '/../src/' . strtolower($path),                  // Semua kecil
        __DIR__ . '/../src/' . ucfirst(strtolower($path)),         // Hanya folder pertama besar
    ];
    
    foreach ($possibleFiles as $file) {
        if (file_exists($file)) {
            require_once $file;
            return;
        }
    }
});

// Load environment
$envFile = $baseDir . '/src/Config/lingkungan.php';
if (file_exists($envFile)) {
    require_once $envFile;
}

// Load composer
$composerAutoload = $baseDir . '/vendor/autoload.php';
if (file_exists($composerAutoload)) {
    require_once $composerAutoload;
}

// Re-enforce error reporting if lingkungan.php changed it
if (defined('ENV') && ENV === 'production') {
    ini_set('display_errors', '0');
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
