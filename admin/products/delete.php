<?php

require '../../lib/db.php';



$id = $_GET['id'];



$pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);

$pdo->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$id]);



header("Location: index.php");



require '../../lib/db.php';
require '../../admin/auth.php';

checkLogin();
// Chỉ admin mới được xóa sản phẩm
if (!isAdmin()) {
    http_response_code(403);
    die('Bạn không có quyền thực hiện thao tác này');
}
// Validate CSRF token 
$token = $_GET['csrf_token'] ?? '';
if (!validateCsrfToken($token)) {
    http_response_code(403);
    die('Yêu cầu không hợp lệ (CSRF token sai)');
}
// 
$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
    http_response_code(400);
    die('ID thiết kế không hợp lệ');
}
$pdo->prepare("DELETE FROM products WHERE id=?")->execute([$id]);
$pdo->prepare("DELETE FROM product_images WHERE product_id=?")->execute([$id]);
header("Location: /admin/products/index.php");
exit;