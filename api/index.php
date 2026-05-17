<?php

/**
 * BELAJARYUK - MASTER CONTROLLER V1.0 (VERCEL OPTIMIZED)
 * Perbaikan Total: Autoloading, Routing, & DB Connection
 */

// 1. Monitor Eror Sangat Ketat
error_reporting(E_ALL);
ini_set('display_errors', '0');

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        $payload = [
            'success' => false,
            'message' => 'Vercel Engine Error',
            'version' => '1.0.Final'
        ];

        if (defined('DEBUG') && DEBUG) {
            $payload['debug'] = $error['message'];
            $payload['file'] = basename($error['file']);
            $payload['line'] = $error['line'];
        }

        echo json_encode($payload);
    }
});

$root = dirname(__DIR__);

// 2. Autoloader Cerdas (Mencari di semua variasi folder)
spl_autoload_register(function ($class) use ($root) {
    if (strpos($class, 'App\\') !== 0) return;
    $rel = str_replace(['App\\', '\\'], ['', '/'], $class);
    
    $paths = [
        $root . '/src/' . $rel . '.php',
        $root . '/src/' . strtolower($rel) . '.php',
        $root . '/src/' . ucfirst($rel) . '.php'
    ];
    
    foreach ($paths as $path) {
        if (file_exists($path)) {
            require_once $path;
            return;
        }
    }
}, true, true);

// 3. Load Dependencies & Config
if (file_exists($root . '/vendor/autoload.php')) require_once $root . '/vendor/autoload.php';
$config = $root . '/src/Config/lingkungan.php';
if (!file_exists($config)) $config = $root . '/src/config/lingkungan.php';
if (file_exists($config)) require_once $config;

// 4. Jalankan Session (Penting untuk CSRF token di API)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Sync CSRF Cookie with Session
if (isset($_SESSION['csrf_token'])) {
    if (!isset($_COOKIE['csrf_token']) || $_COOKIE['csrf_token'] !== $_SESSION['csrf_token']) {
        setcookie('csrf_token', $_SESSION['csrf_token'], [
            'expires' => time() + 3600,
            'path' => '/',
            'secure' => true,
            'httponly' => false,
            'samesite' => 'Lax'
        ]);
    }
}

// 5. Pengaturan CORS & Security
// CORS headers dikelola di src/Config/lingkungan.php (allowlist).
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 6. Unified Router (Vercel & XAMPP Friendly)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Deteksi apakah ini request API (Lebih fleksibel untuk subfolder)
$isApiRequest = (strpos($uri, '/api') !== false);

if ($isApiRequest) {
    $routes = require __DIR__ . '/routes.php';
    
    // Bersihkan URI untuk pencocokan route
    // Cari posisi /api dan ambil setelahnya
    $apiPos = strpos($uri, '/api');
    $cleanUri = substr($uri, $apiPos + 4); // +4 untuk melewati '/api'
    if (empty($cleanUri)) $cleanUri = '/';
    $cleanUri = rtrim($cleanUri, '/') ?: '/';

    $found = false;
    foreach ($routes as $route => $handler) {
        list($rMethod, $rPath) = explode(' ', $route);
        if ($method === $rMethod && $cleanUri === rtrim($rPath, '/')) {
            $found = true;
            $controllerClass = $handler[0];
            $action = $handler[1];
            
            if (class_exists($controllerClass)) {
                $instance = new $controllerClass();
                $instance->$action();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Class {$controllerClass} tidak ditemukan."]);
            }
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Route API tidak terdaftar.'
        ]);
    }
    exit;
}

// 7. Jalankan Frontend (SPA Fallback)
// Jika bukan API, maka tampilkan halaman utama
$frontend = $root . '/public/index.php';
if (file_exists($frontend)) {
    require $frontend;
} else {
    echo "<h1>Belajaryuk Ready</h1><p>Versi 1.1.Vercel</p>";
}
