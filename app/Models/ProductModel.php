<?php
namespace App\Models;

use PDO;

class ProductModel {
    protected $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
    }

    public function getFilteredProducts($filters, $userContext) {
        $conditions = [];
        $params = [];

        $roleId = $userContext['role_id'];
        $userId = $userContext['user_id'];
        $isLogin = $userContext['is_login'];

        $assignedSqlP = $userId > 0 ? " OR p.id IN (SELECT product_id FROM user_product_access WHERE user_id = $userId)" : "";

        if ($isLogin) {
            if ($roleId === 0) {
                // Super admin sees all
            } elseif ($roleId !== 9) {
                $conditions[] = "(p.status IN (1, 2) $assignedSqlP)";
            } else {
                $conditions[] = "(p.status = 2 $assignedSqlP)";
            }
        } else {
            $conditions[] = "p.status = 2";
        }

        if (!empty($filters['keyword'])) {
            $conditions[] = "p.name LIKE ?";
            $params[] = "%" . $filters['keyword'] . "%";
        }

        if (!empty($filters['category_id'])) {
            // Need getCategoryFilterIds, which is in lib/categories.php
            require_once __DIR__ . '/../../lib/categories.php';
            $catIds = getCategoryFilterIds($this->pdo, $filters['category_id']);
            $catPlaceholders = implode(',', array_fill(0, count($catIds), '?'));
            $conditions[] = "p.category_id IN ($catPlaceholders)";
            $params = array_merge($params, $catIds);
        }

        if (!empty($filters['brand_id'])) {
            $conditions[] = "p.brand_id = ?";
            $params[] = $filters['brand_id'];
        }

        $where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

        $internalSort = "";
        if ($isLogin) {
            $internalSort = "(p.status = 1 $assignedSqlP) DESC, ";
        }

        $orderBy = "ORDER BY {$internalSort}p.id DESC";
        if ($filters['sort'] === 'oldest') {
            $orderBy = "ORDER BY {$internalSort}p.id ASC";
        } elseif ($filters['sort'] === 'name_asc') {
            $orderBy = "ORDER BY {$internalSort}p.name ASC";
        } elseif ($filters['sort'] === 'name_desc') {
            $orderBy = "ORDER BY {$internalSort}p.name DESC";
        }

        // Count total
        $stmt = $this->pdo->prepare("SELECT COUNT(*) FROM products p $where");
        $stmt->execute($params);
        $total = $stmt->fetchColumn();

        // Get data
        $offset = ($filters['page'] - 1) * $filters['limit'];
        $sql = "
            SELECT 
                p.*,
                b.name as brand_name,
                c.id as cate_id,
                CASE
                    WHEN cp.name IS NOT NULL THEN CONCAT(cp.name, ' › ', c.name)
                    ELSE c.name
                END as cate_name,
                (
                    SELECT GROUP_CONCAT(image_path ORDER BY sort_order ASC, id ASC)
                    FROM product_images 
                    WHERE product_id = p.id
                ) as images,
                (
                    SELECT image_path 
                    FROM product_images 
                    WHERE product_id=p.id 
                    ORDER BY sort_order ASC, id ASC
                    LIMIT 1
                ) as thumb
            FROM products p
            LEFT JOIN brands b ON p.brand_id = b.id
            LEFT JOIN categories c ON p.category_id = c.id
            LEFT JOIN categories cp ON c.parent_id = cp.id
            $where 
            $orderBy
            LIMIT {$filters['limit']} OFFSET $offset
        ";
        
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        $products = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return [
            'total' => $total,
            'products' => $products
        ];
    }

    public function getProductBySlug($slug, $userContext) {
        $stmt = $this->pdo->prepare("SELECT * FROM products WHERE slug=?");
        $stmt->execute([$slug]);
        $product = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$product) {
            return ['error' => 'not_found'];
        }

        $hasAccess = false;
        $roleId = $userContext['role_id'];
        $userId = $userContext['user_id'];
        $isLogin = $userContext['is_login'];

        if ($product['status'] == 2) {
            $hasAccess = true;
        } elseif ($userId > 0 && $roleId !== 9 && $product['status'] == 1) {
            $hasAccess = true;
        } elseif ($userId > 0) {
            $accessStmt = $this->pdo->prepare("SELECT 1 FROM user_product_access WHERE user_id = ? AND product_id = ?");
            $accessStmt->execute([$userId, $product['id']]);
            if ($accessStmt->fetchColumn()) {
                $hasAccess = true;
            }
        }

        if (!$hasAccess) {
            if ($product['status'] == 1 && !$isLogin) {
                return ['error' => 'internal_login_required'];
            }
            return ['error' => 'no_access'];
        }

        // Get images
        $stmt = $this->pdo->prepare("SELECT * FROM product_images WHERE product_id=? ORDER BY sort_order ASC, id ASC");
        $stmt->execute([$product['id']]);
        $images = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        $imageList = array_map(function($img){
            return $img['image_path'];
        }, $images);

        // Get brand
        $stmt = $this->pdo->prepare("SELECT name FROM brands WHERE id=?");
        $stmt->execute([$product['brand_id']]);
        $brand = $stmt->fetch(\PDO::FETCH_ASSOC);

        // Get category
        $stmt = $this->pdo->prepare("
            SELECT c.name, cp.name AS parent_name
            FROM categories c
            LEFT JOIN categories cp ON c.parent_id = cp.id
            WHERE c.id = ?
        ");
        $stmt->execute([$product['category_id']]);
        $category = $stmt->fetch(\PDO::FETCH_ASSOC);

        return [
            'product' => $product,
            'images' => $images,
            'imageList' => $imageList,
            'brand' => $brand,
            'category' => $category
        ];
    }
}
