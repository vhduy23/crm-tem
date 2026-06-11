<?php
require '../lib/db.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];

function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

if ($method === 'GET') {
    echo json_encode($pdo->query("SELECT * FROM brands ORDER BY id DESC")->fetchAll());
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true) ?: [];
    $name = trim($data['name'] ?? '');
    $id = !empty($data['id']) ? (int) $data['id'] : null;

    if ($name === '') {
        jsonError('Tên thương hiệu không được để trống.');
    }

    if ($id) {
        $stmt = $pdo->prepare("UPDATE brands SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO brands (name) VALUES (?)");
        $stmt->execute([$name]);
    }

    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        jsonError('ID không hợp lệ.');
    }

    // Kiểm tra xem thương hiệu có sản phẩm nào không
    $productCheck = $pdo->prepare("SELECT COUNT(*) FROM products WHERE brand_id = ?");
    $productCheck->execute([$id]);
    if ((int) $productCheck->fetchColumn() > 0) {
        jsonError('Không thể xóa thương hiệu đang có sản phẩm liên kết.');
    }

    $pdo->prepare("DELETE FROM brands WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);