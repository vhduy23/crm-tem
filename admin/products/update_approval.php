<?php
require '../../lib/db.php';
require '../auth.php';
checkLogin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = $_POST['id'] ?? 0;
    $status = $_POST['status'] ?? 0;
    
    $stmt = $pdo->prepare("UPDATE products SET approval_status = ? WHERE id = ?");
    if ($stmt->execute([$status, $id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Lỗi cập nhật']);
    }
}
