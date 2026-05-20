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

$assetVersion = getenv('APP_ASSET_VERSION');
if (!$assetVersion) {
    $assetVersion = getenv('VERCEL_GIT_COMMIT_SHA') ?: getenv('VERCEL_DEPLOYMENT_ID');
}
if (!$assetVersion) {
    $versionSources = [
        __DIR__ . '/layout.html',
        __DIR__ . '/assets/css/gaya.css',
        __DIR__ . '/assets/css/responsif.css',
        __DIR__ . '/js/app.js',
        __DIR__ . '/js/init.js'
    ];
    $hashSeed = '';
    foreach ($versionSources as $file) {
        if (file_exists($file)) {
            $hashSeed .= $file . ':' . sha1_file($file) . ';';
        }
    }
    $assetVersion = $hashSeed ? substr(sha1($hashSeed), 0, 12) : (string) time();
}

$output = str_replace('{{CSRF_TOKEN}}', get_csrf_token(), $layout);
$output = str_replace('{{ASSET_PATH}}', $assetPath, $output);
$output = str_replace('{{ASSET_VERSION}}', $assetVersion, $output);

echo $output;
