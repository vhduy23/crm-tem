<?php
require '../lib/db.php';
require '../lib/categories.php';

header('Content-Type: application/json; charset=utf-8');

$method = $_SERVER['REQUEST_METHOD'];
ensureCategoryParentColumn($pdo);

function jsonError(string $message, int $code = 400): void
{
    http_response_code($code);
    echo json_encode(['error' => $message]);
    exit;
}

function normalizeParentId($parentId): ?int
{
    if ($parentId === null || $parentId === '' || $parentId === 0 || $parentId === '0') {
        return null;
    }
    return (int) $parentId;
}

function validateCategoryParent(PDO $pdo, ?int $parentId, ?int $categoryId = null): void
{
    if ($parentId === null) {
        return;
    }

    $stmt = $pdo->prepare("SELECT id, parent_id FROM categories WHERE id = ?");
    $stmt->execute([$parentId]);
    $parent = $stmt->fetch();

    if (!$parent) {
        jsonError('Danh mục cha không tồn tại.');
    }

    if (!empty($parent['parent_id'])) {
        jsonError('Chỉ được chọn danh mục cha (cấp 1) làm cha.');
    }

    if ($categoryId !== null && $parentId === $categoryId) {
        jsonError('Danh mục không thể là cha của chính nó.');
    }

    if ($categoryId !== null) {
        $childCheck = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
        $childCheck->execute([$categoryId]);
        if ((int) $childCheck->fetchColumn() > 0) {
            jsonError('Danh mục đang có danh mục con, không thể chuyển thành danh mục con.');
        }
    }
}

if ($method === 'GET') {
    echo json_encode(fetchCategories($pdo));
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true) ?: [];
    $name = trim($data['name'] ?? '');
    $parentId = normalizeParentId($data['parent_id'] ?? null);
    $id = !empty($data['id']) ? (int) $data['id'] : null;

    if ($name === '') {
        jsonError('Tên danh mục không được để trống.');
    }

    validateCategoryParent($pdo, $parentId, $id);

    if ($id) {
        $stmt = $pdo->prepare("UPDATE categories SET name = ?, parent_id = ? WHERE id = ?");
        $stmt->execute([$name, $parentId, $id]);
    } else {
        $stmt = $pdo->prepare("INSERT INTO categories (name, parent_id) VALUES (?, ?)");
        $stmt->execute([$name, $parentId]);
    }

    echo json_encode(['ok' => true]);
    exit;
}

if ($method === 'DELETE') {
    $id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
    if ($id <= 0) {
        jsonError('ID không hợp lệ.');
    }

    $childCheck = $pdo->prepare("SELECT COUNT(*) FROM categories WHERE parent_id = ?");
    $childCheck->execute([$id]);
    if ((int) $childCheck->fetchColumn() > 0) {
        jsonError('Không thể xóa danh mục cha đang có danh mục con.');
    }

    $productCheck = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id = ?");
    $productCheck->execute([$id]);
    if ((int) $productCheck->fetchColumn() > 0) {
        jsonError('Không thể xóa danh mục đang có sản phẩm.');
    }

    $pdo->prepare("DELETE FROM categories WHERE id = ?")->execute([$id]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(405);
echo json_encode(['error' => 'Method not allowed']);
