<?php

function ensureCategoryParentColumn(PDO $pdo): void
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;

    $cols = $pdo->query("SHOW COLUMNS FROM categories LIKE 'parent_id'")->fetchAll();
    if ($cols) {
        return;
    }

    $pdo->exec("
        ALTER TABLE categories
            ADD COLUMN parent_id INT NULL DEFAULT NULL AFTER name,
            ADD INDEX idx_categories_parent (parent_id)
    ");
}

function fetchCategories(PDO $pdo): array
{
    ensureCategoryParentColumn($pdo);

    return $pdo->query("
        SELECT c.*, p.name AS parent_name
        FROM categories c
        LEFT JOIN categories p ON c.parent_id = p.id
        ORDER BY COALESCE(c.parent_id, c.id), c.parent_id IS NOT NULL, c.name ASC
    ")->fetchAll();
}

function buildCategoryTree(array $categories): array
{
    $parents = [];
    $children = [];

    foreach ($categories as $cat) {
        if (empty($cat['parent_id'])) {
            $parents[$cat['id']] = $cat + ['children' => []];
        } else {
            $children[$cat['parent_id']][] = $cat;
        }
    }

    foreach ($children as $parentId => $items) {
        if (isset($parents[$parentId])) {
            $parents[$parentId]['children'] = $items;
        }
    }

    return array_values($parents);
}

function getCategoryFilterIds(PDO $pdo, int $categoryId): array
{
    ensureCategoryParentColumn($pdo);

    $stmt = $pdo->prepare("SELECT id, parent_id FROM categories WHERE id = ?");
    $stmt->execute([$categoryId]);
    $cat = $stmt->fetch();

    if (!$cat) {
        return [$categoryId];
    }

    if (empty($cat['parent_id'])) {
        $childStmt = $pdo->prepare("SELECT id FROM categories WHERE parent_id = ?");
        $childStmt->execute([$categoryId]);
        $ids = array_column($childStmt->fetchAll(), 'id');
        $ids[] = (int) $categoryId;
        return $ids;
    }

    return [(int) $categoryId];
}

function findCategoryById(array $categories, int $id): ?array
{
    foreach ($categories as $cat) {
        if ((int) $cat['id'] === $id) {
            return $cat;
        }
    }
    return null;
}

function getCategoryDisplayName(array $categories, int $id): string
{
    $cat = findCategoryById($categories, $id);
    if (!$cat) {
        return '';
    }

    if (!empty($cat['parent_id']) && !empty($cat['parent_name'])) {
        return $cat['parent_name'] . ' › ' . $cat['name'];
    }

    return $cat['name'];
}

function renderCategorySelectOptions(array $categories, $selectedId = null): void
{
    $tree = buildCategoryTree($categories);

    foreach ($tree as $parent) {
        $isParentSelected = (int) $selectedId === (int) $parent['id'];
        echo '<option value="' . (int) $parent['id'] . '"' . ($isParentSelected ? ' selected' : '') . '>';
        echo htmlspecialchars($parent['name']);
        echo '</option>';

        foreach ($parent['children'] as $child) {
            $isChildSelected = (int) $selectedId === (int) $child['id'];
            echo '<option value="' . (int) $child['id'] . '"' . ($isChildSelected ? ' selected' : '') . '>';
            echo '— ' . htmlspecialchars($child['name']);
            echo '</option>';
        }
    }
}

function categoryGroupIsExpanded(array $parent, int $activeCategoryId): bool
{
    if ((int) $parent['id'] === $activeCategoryId) {
        return true;
    }

    foreach ($parent['children'] as $child) {
        if ((int) $child['id'] === $activeCategoryId) {
            return true;
        }
    }

    return false;
}

function countCategoryProducts(PDO $pdo, int $categoryId, bool $includeChildren = true): int
{
    ensureCategoryParentColumn($pdo);

    $ids = $includeChildren ? getCategoryFilterIds($pdo, $categoryId) : [$categoryId];
    $placeholders = implode(',', array_fill(0, count($ids), '?'));

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM products WHERE category_id IN ($placeholders) AND status = 2");
    $stmt->execute($ids);

    return (int) $stmt->fetchColumn();
}
