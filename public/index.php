<?php
/**
 * Belajaryuk - Frontend Loader
 * Memisahkan logika PHP dari template HTML murni
 */
require_once __DIR__ . '/../src/Config/bootstrap_web.php';

// Load template murni
$layout = file_get_contents(__DIR__ . '/layout.html');

// Injeksi variabel PHP ke template (One Language per File)
$basePath = (strpos($_SERVER['REQUEST_URI'], '/public/') !== false) ? '' : '';
// Better: auto-detect base path for assets
$scriptName = $_SERVER['SCRIPT_NAME']; // e.g. /belajaryuk/public/index.php
$publicDir = dirname($scriptName);     // e.g. /belajaryuk/public

// Fix: Jika di root, dirname mengembalikan '/' atau '\' yang memicu ERR_NAME_NOT_RESOLVED (//assets)
$assetPath = rtrim($publicDir, '/\\');

$output = str_replace('{{CSRF_TOKEN}}', get_csrf_token(), $layout);
$output = str_replace('{{ASSET_PATH}}', $assetPath, $output);

echo $output;
