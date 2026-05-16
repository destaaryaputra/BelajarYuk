<?php
/**
 * Belajaryuk - Frontend Loader
 * Memisahkan logika PHP dari template HTML murni
 */
require_once __DIR__ . '/../src/Config/bootstrap_web.php';

// Load template murni
$layout = file_get_contents(__DIR__ . '/layout.html');

// Injeksi variabel PHP ke template (One Language per File)
$output = str_replace('{{CSRF_TOKEN}}', get_csrf_token(), $layout);

echo $output;
