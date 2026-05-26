<?php
/**
 * Belajaryuk Root Entry Point
 * Simple fallback if .htaccess routing fails
 */

$env = getenv('APP_ENV') ?: 'development';
ini_set('display_errors', $env === 'development' ? '1' : '0');
ini_set('display_startup_errors', $env === 'development' ? '1' : '0');
error_reporting($env === 'development' ? E_ALL : 0);

$request_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base_path = getenv('APP_BASE_PATH') ?: '';

// Remove base path to get relative path if it exists
if (!empty($base_path)) {
    $path = str_replace($base_path, '', $request_path);
} else {
    $path = $request_path;
}
$path = preg_replace('/\?.*/', '', $path); // Remove query string

// Serve public static assets from clean URLs, matching Vercel routing
$public_aliases = ['assets', 'js', 'pages', 'components', 'uploads'];
$first_segment = trim(explode('/', trim($path, '/'))[0] ?? '', '/');
if (in_array($first_segment, $public_aliases, true)) {
    $file = __DIR__ . '/public' . $path;
    if (file_exists($file) && is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            'html' => 'text/html',
        ];

        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }
        readfile($file);
        exit;
    }
}

// Route API requests to api/index.php
if (strpos($path, '/api/') === 0) {
    $_GET['_api_request'] = $path;
    require __DIR__ . '/api/index.php';
    exit;
}

// Serve static files from public
if (preg_match('/^\/public\//', $path)) {
    $file = __DIR__ . $path;
    if (file_exists($file) && is_file($file)) {
        // Set correct MIME types
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime_types = [
            'css' => 'text/css',
            'js' => 'application/javascript',
            'json' => 'application/json',
            'jpg' => 'image/jpeg',
            'jpeg' => 'image/jpeg',
            'png' => 'image/png',
            'gif' => 'image/gif',
            'svg' => 'image/svg+xml',
        ];
        
        if (isset($mime_types[$ext])) {
            header('Content-Type: ' . $mime_types[$ext]);
        }
        readfile($file);
        exit;
    }
}

// SPA Fallback: Cek apakah frontend menggunakan index.php atau index.html
if (file_exists(__DIR__ . '/public/index.php')) {
    require __DIR__ . '/public/index.php';
} elseif (file_exists(__DIR__ . '/public/index.html')) {
    readfile(__DIR__ . '/public/index.html');
} else {
    echo "<h2 style='font-family:sans-serif; text-align:center; margin-top:50px;'>Error 404: Halaman Frontend Tidak Ditemukan!</h2>";
    echo "<p style='text-align:center;'>Pastikan file <b>index.html</b> atau <b>index.php</b> ada di dalam folder <b>belajaryuk/public/</b>.</p>";
}

