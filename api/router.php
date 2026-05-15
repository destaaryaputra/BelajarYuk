<?php

// API router
// Load configuration & dependencies (use parent src directory)
$env_file = __DIR__ . '/../src/config/lingkungan.php';
if (file_exists($env_file)) {
    require_once $env_file;
}

require_once __DIR__ . '/../vendor/autoload.php';

use App\Utils\Response;

// Suppress notices/warnings to keep JSON clean
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING & ~E_DEPRECATED);

// Start session if not already started
@session_start();

// Parse URL properly
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$request_method = $_SERVER['REQUEST_METHOD'];

// Remove base path /belajaryuk and /api to get the actual route
$request_uri = str_replace('/belajaryuk', '', $request_uri);
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


