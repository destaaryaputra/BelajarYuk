<?php

// API & Frontend router for Vercel
// Load environment first
require_once __DIR__ . '/../src/config/lingkungan.php';
require_once __DIR__ . '/../vendor/autoload.php';

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
