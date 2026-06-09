<?php
require '../../lib/db.php';

$id = $_GET['id'] ?? 0;

// lấy ảnh
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE id=?");
$stmt->execute([$id]);
$img = $stmt->fetch();

if ($img) {

    $cacheFile = __DIR__ . '/../../cache' . $img['image_path'];
    if (file_exists($cacheFile)) unlink($cacheFile);

    // xóa file vật lý
    $file = __DIR__ . '/../../' . ltrim($img['image_path'], '/');
    if (file_exists($file)) {
        unlink($file);
    }

    // xóa DB
    $stmt = $pdo->prepare("DELETE FROM product_images WHERE id=?");
    $stmt->execute([$id]);
}

echo 'ok';
?>


<?php
require '../../lib/db.php';
require '../../admin/auth.php';
// Kiểm tra đăng nhập trước khi cho phép xóa ảnh
checkLogin();

// if (!isAdmin()) {
//     http_response_code(403);
//     echo json_encode(['error' => 'Bạn không có quyền thực hiện thao tác này']);
//     exit;
// }

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    echo json_encode(['error' => 'ID ảnh không hợp lệ']);
    exit;
}
// Lấy thông tin ảnh
$stmt = $pdo->prepare("SELECT * FROM product_images WHERE id=?");
$stmt->execute([$id]);
$img = $stmt->fetch();
if ($img) {
    // Kiểm tra path traversal — chỉ cho phép xóa file trong thư mục uploads
    $uploadsDir = realpath(__DIR__ . '/../../uploads');
    $file       = realpath(__DIR__ . '/../../' . ltrim($img['image_path'], '/'));
    if ($file && str_starts_with($file, $uploadsDir)) {
        // Xóa cache nếu có
        $cacheFile = __DIR__ . '/../../cache' . $img['image_path'];
        if (file_exists($cacheFile)) unlink($cacheFile);
        // Xóa file vật lý
        unlink($file);
    }
    // Nếu path traversal bị phát hiện, log lỗi và không xóa file
    else {
        error_log('[SECURITY] delete_img.php: path traversal attempt, image_path=' . $img['image_path']);
    }
    // Xóa DB (luôn xóa DB dù file có tồn tại hay không)
    $pdo->prepare("DELETE FROM product_images WHERE id=?")->execute([$id]);
}
header('Content-Type: application/json');
echo json_encode(['success' => true]);