<?php
function processImage($filePath) {
    // ini_set('memory_limit', '512M');

    // 1. Load ảnh gốc (hỗ trợ cả PNG và JPG cho linh hoạt)
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
    if ($extension === 'png') {
        $image = imagecreatefrompng($filePath);
    } else {
        $image = imagecreatefromjpeg($filePath);
    }

    if (!$image) return false;

    $width  = imagesx($image);
    $height = imagesy($image);

    $maxWidth = 1500;

    if ($width > $maxWidth) {

        $newHeight = ($maxWidth / $width) * $height;

        $resized = imagecreatetruecolor($maxWidth, $newHeight);

        // giữ alpha
        imagealphablending($resized, false);
        imagesavealpha($resized, true);

        $transparent = imagecolorallocatealpha($resized, 0, 0, 0, 127);
        imagefill($resized, 0, 0, $transparent);

        imagecopyresampled(
            $resized, $image,
            0, 0, 0, 0,
            $maxWidth, $newHeight,
            $width, $height
        );

        imagedestroy($image);
        $image = $resized;

        $width = $maxWidth;
        $height = $newHeight;
    }

    // trước khi save webp
    imagealphablending($image, true);
    imagesavealpha($image, true);


    // 2. Cấu hình Watermark
    $text = "achaugroup";
    $fontSize = 25;
    $angle = 45; // Độ nghiêng như trong hình image_afc362.jpg
    
    // Màu trắng với độ trong suốt cao (Alpha từ 0-127, 100 là khá mờ)
    $color = imagecolorallocatealpha($image, 255, 255, 255, 100);
    
    $font = __DIR__ . '/Roboto-Italic.ttf';

    if (file_exists($font)) {
        // Khoảng cách giữa các chữ (tùy chỉnh theo ý bạn)
        $stepX = 300; 
        $stepY = 250;

        // 3. Vòng lặp để phủ kín ảnh
        // Chạy từ giá trị âm để đảm bảo các góc không bị trống khi xoay chữ
        for ($x = -($width); $x < $width + $stepX; $x += $stepX) {
            for ($y = -($height); $y < $height + $stepY; $y += $stepY) {
                imagettftext($image, $fontSize, $angle, $x, $y, $color, $font, $text);
            }
        }
    }

    // 4. Lưu file WEBP
    $newPath = preg_replace('/\.(png|jpg|jpeg)$/i', '.webp', $filePath);
    imagewebp($image, $newPath, 80);

    imagedestroy($image);

    // 5. Xóa file gốc
    if (file_exists($filePath)) {
        unlink($filePath);
    }

    return $newPath;
}
