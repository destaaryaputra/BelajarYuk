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

// Parse URL properly - Support subfolder installation (XAMPP/Vercel)
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Deteksi base path secara otomatis
$script_name = $_SERVER['SCRIPT_NAME']; // e.g. /belajaryuk/api/router.php
$base_dir = dirname($script_name);     // e.g. /belajaryuk/api

// Bersihkan request_uri dari base_dir
if (strpos($request_uri, $base_dir) === 0) {
    $request_uri = substr($request_uri, strlen($base_dir));
}

// Tambahan fallback jika masih ada /api di depan (untuk Vercel/Production)
if (strpos($request_uri, '/api') === 0) {
    $request_uri = substr($request_uri, 4);
}

// Clean up path
if (empty($request_uri) || $request_uri === '/') {
    $request_uri = '/';
} else {
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


