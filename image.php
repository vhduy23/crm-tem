<?php
$src = $_GET['src'] ?? '';

if (!$src) {
    http_response_code(400);
    exit('Missing src');
}

// ===== PATH FIX =====
$source = $_SERVER['DOCUMENT_ROOT'] . $src;
$cache  = $_SERVER['DOCUMENT_ROOT'] . '/cache' . $src;

// ===== SECURITY =====
$uploadsDir = realpath($_SERVER['DOCUMENT_ROOT'] . '/uploads');

if (!$uploadsDir || strpos(realpath($source), $uploadsDir) !== 0) {
    http_response_code(403);
    exit('Forbidden');
}

if (!file_exists($source)) {
    http_response_code(404);
    exit('Not found');
}

// ===== CACHE =====
if (file_exists($cache)) {
    header('Content-Type: image/webp');
    readfile($cache);
    exit;
}

// ===== CREATE CACHE DIR =====
if (!is_dir(dirname($cache))) {
    mkdir(dirname($cache), 0777, true);
}

// ===== LOAD =====
$image = imagecreatefromwebp($source);

if (!$image) exit('Error load image');

// ===== WATERMARK =====
$font = $_SERVER['DOCUMENT_ROOT'] . '/lib/Roboto-Italic.ttf';

if (file_exists($font)) {
    $color = imagecolorallocatealpha($image, 80, 80, 80, 80);

    for ($x = 0; $x < imagesx($image); $x += 350) {
        for ($y = 0; $y < imagesy($image); $y += 280) {
            imagettftext($image, 18, 45, $x, $y, $color, $font, 'achaugroup');
        }
    }
}

// ===== SAVE CACHE =====
imagewebp($image, $cache, 80);

// ===== OUTPUT =====
header('Content-Type: image/webp');
imagewebp($image);

imagedestroy($image);