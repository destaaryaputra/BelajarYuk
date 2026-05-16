<?php
/**
 * Belajaryuk - Frontend Loader
 * Memisahkan logika PHP dari template HTML murni
 */
require_once __DIR__ . '/../src/Config/bootstrap_web.php';

// Load template murni
$layout = file_get_contents(__DIR__ . '/layout.html');

// Injeksi variabel PHP ke template (One Language per File)
$scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
$publicDir = dirname($scriptName);

// Deteksi lingkungan Vercel atau Production
$isProduction = (getenv('APP_ENV') === 'production' || strpos($scriptName, '/api/') !== false);

if ($isProduction) {
    // Di Vercel/Production, aset biasanya ada di root domain
    $assetPath = '';
} else {
    // Di Lokal (XAMPP), aset ada di subfolder
    $assetPath = rtrim($publicDir, '/\\');
}

$output = str_replace('{{CSRF_TOKEN}}', get_csrf_token(), $layout);
$output = str_replace('{{ASSET_PATH}}', $assetPath, $output);

echo $output;
