<?php

/**
 * BELAJARYUK - MASTER CONTROLLER V1.0 (VERCEL OPTIMIZED)
 * Perbaikan Total: Autoloading, Routing, & DB Connection
 */

// 1. Monitor Eror Sangat Ketat
error_reporting(E_ALL);
ini_set('display_errors', '1');

register_shutdown_function(function() {
    $error = error_get_last();
    if ($error !== NULL && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR])) {
        if (!headers_sent()) header('Content-Type: application/json');
        echo json_encode([
            'success' => false, 
            'message' => 'Vercel Engine Error', 
            'version' => '1.0.Final',
            'debug' => $error['message'],
            'file' => basename($error['file']),
            'line' => $error['line']
        ]);
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

// 4. Pengaturan CORS & Security
$origin = $_SERVER['HTTP_ORIGIN'] ?? '*';
header('Access-Control-Allow-Origin: ' . $origin);
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

// 5. Unified Router (Langsung di sini agar tidak ada require gagal)
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = str_replace('/api', '', $uri); // Bersihkan /api
if (empty($uri) || $uri === '/') $uri = '/';
else $uri = rtrim($uri, '/');

// Jika request adalah API
if (strpos($_SERVER['REQUEST_URI'], '/api') !== false) {
    $routes = require __DIR__ . '/routes.php';
    $method = $_SERVER['REQUEST_METHOD'];
    $found = false;

    foreach ($routes as $route => $handler) {
        list($rMethod, $rPath) = explode(' ', $route);
        if ($method === $rMethod && $uri === rtrim($rPath, '/')) {
            $found = true;
            $controllerClass = $handler[0];
            $action = $handler[1];
            
            if (class_exists($controllerClass)) {
                $instance = new $controllerClass();
                $instance->$action();
            } else {
                header('Content-Type: application/json');
                echo json_encode(['success' => false, 'message' => "Class {$controllerClass} tidak ditemukan di server."]);
            }
            break;
        }
    }

    if (!$found) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Route API tidak terdaftar.']);
    }
    exit;
}

// 6. Jalankan Frontend
$frontend = $root . '/public/index.php';
if (file_exists($frontend)) {
    require $frontend;
} else {
    echo "<h1>Belajaryuk Ready</h1><p>Versi 1.0.Final</p>";
}
