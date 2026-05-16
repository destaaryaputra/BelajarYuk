<?php

/**
 * Belajaryuk - API Router (Local/XAMPP Optimized)
 * Menangani routing API dengan autoloader dan session support
 */

// 1. Setup Environment & Autoloader
$root = dirname(__DIR__);

// Autoloader PSR-4 manual fallback jika vendor/autoload belum jalan sempurna
spl_autoload_register(function ($class) use ($root) {
    if (strpos($class, 'App\\') !== 0) return;
    $rel = str_replace(['App\\', '\\'], ['', '/'], $class);
    $path = $root . '/src/' . $rel . '.php';
    if (file_exists($path)) {
        require_once $path;
    }
}, true, true);

// Composer Autoloader
if (file_exists($root . '/vendor/autoload.php')) {
    require_once $root . '/vendor/autoload.php';
}

// Load Environment Config
$config = $root . '/src/Config/lingkungan.php';
if (file_exists($config)) {
    require_once $config;
}

// 2. Start Session (Penting untuk CSRF)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

use App\Utils\Response;

// Parse URL properly
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove base path and /api to get the actual route
$base_path = getenv('APP_BASE_PATH') ?: '';
if (!empty($base_path)) {
    $request_uri = str_replace($base_path, '', $request_uri);
}
$request_uri = str_replace('/api', '', $request_uri);

// Clean up path
if (empty($request_uri) || $request_uri === '/') {
    $request_uri = '/';
} else {
    // Remove query string and extra slashes
    $request_uri = strtok($request_uri, '?');
    $request_uri = rtrim($request_uri, '/') ?: '/';
}

// Route matching
$routes = require __DIR__ . '/routes.php';

// Find matching route
$matched = false;
foreach ($routes as $route => $handler) {
    list($method, $path) = explode(' ', $route);
    
    // Tambahkan '/?' agar mendukung URL dengan atau tanpa trailing slash di akhir
    if ($request_method === $method && preg_match('#^' . preg_quote($path, '#') . '/?$#', $request_uri)) {
        $matched = true;
        $controller_class = $handler[0];
        $action = $handler[1];
        
        $controller = new $controller_class();
        $controller->$action();
        break;
    }
}

if (!$matched) {
    Response::error('Route not found', null, 404);
}


