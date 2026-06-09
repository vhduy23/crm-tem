<?php
require '../lib/db.php';
require '../lib/image.php';
require '../admin/auth.php';

checkLogin();
// if (!isAdmin()) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Bạn không có quyền truy cập']);
//     exit;
// }

$product_id = (int)($_POST['product_id'] ?? 0);
if ($product_id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'product_id không hợp lệ']);
    exit;
}

$allowedMimes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];

$maxFileSize = 25 * 1024 * 1024;
$paths = [];
foreach ($_FILES['images']['tmp_name'] as $key => $tmp) {
    
    if ($_FILES['images']['error'][$key] !== UPLOAD_ERR_OK) {
        continue;
    }
    
    if ($_FILES['images']['size'][$key] > $maxFileSize) {
        http_response_code(400);
        echo json_encode(['error' => 'File quá lớn, tối đa 10MB mỗi ảnh']);
        exit;
    }
    
    $finfo    = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmp);
    if (!in_array($mimeType, $allowedMimes, true)) {
        http_response_code(400);
        echo json_encode(['error' => "File không hợp lệ (chỉ chấp nhận: jpg, png, gif, webp). Phát hiện: {$mimeType}"]);
        exit;
    }
    
    $name    = uniqid('img_', true) . '_' . bin2hex(random_bytes(4)) . '.png';
    $pngPath = "../uploads/products/$name";
    
    if (!move_uploaded_file($tmp, $pngPath)) {
        http_response_code(500);
        echo json_encode(['error' => 'Lỗi khi lưu file']);
        exit;
    }
    
    $webpPath = processImage($pngPath);
    $stmt = $pdo->prepare("
        INSERT INTO product_images(product_id, image_path)
        VALUES(?, ?)
    ");
    $stmt->execute([$product_id, $webpPath]);
    $paths[] = $webpPath;
}
header('Content-Type: application/json');
echo json_encode($paths);