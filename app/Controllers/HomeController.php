<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\ProductModel;

class HomeController extends Controller {
    public function index() {
        // Initialize globals expected by older parts of the app for now
        global $pdo;

        require_once __DIR__ . '/../../lib/db.php';
        require_once __DIR__ . '/../../lib/session.php';

        // Filters from GET
        $filters = [
            'keyword'     => $_GET['q'] ?? '',
            'category_id' => isset($_GET['cat']) ? (int)$_GET['cat'] : 0,
            'brand_id'    => isset($_GET['brand']) ? (int)$_GET['brand'] : 0,
            'page'        => max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1),
            'limit'       => 12,
            'sort'        => $_GET['sort'] ?? 'newest'
        ];

        // User Context
        $isLogin = !empty($_SESSION['member']['id']) || !empty($_SESSION['user']['id']);
        $roleId = (int)($_SESSION['member']['role_id'] ?? $_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['member']['id'] ?? $_SESSION['user']['id'] ?? 0);
        
        $userContext = [
            'is_login' => $isLogin,
            'role_id'  => $roleId,
            'user_id'  => $userId
        ];

        $productModel = new ProductModel();
        $result = $productModel->getFilteredProducts($filters, $userContext);

        $totalPages = ceil($result['total'] / $filters['limit']);

        // Fetch categories
        require_once __DIR__ . '/../../lib/categories.php';
        $categories = fetchCategories($pdo);
        $categoryTree = buildCategoryTree($categories);
        $cateTotal = $pdo->query("SELECT COUNT(*) FROM categories WHERE parent_id IS NULL")->fetchColumn();

        // Fetch brands
        $whereBrand = "1=1";
        $assignedSqlP = $userId > 0 ? " OR p.id IN (SELECT product_id FROM user_product_access WHERE user_id = $userId)" : "";
        if ($isLogin) {
            if ($roleId === 0) {
                // super admin
            } elseif ($roleId !== 9) {
                $whereBrand = "(p.status IN (1, 2) $assignedSqlP)";
            } else {
                $whereBrand = "(p.status = 2 $assignedSqlP)";
            }
        } else {
            $whereBrand = "p.status = 2";
        }
        $brandSql = "
            SELECT 
                b.id, 
                b.name, 
                COUNT(p.id) as product_count 
            FROM brands b
            LEFT JOIN products p ON b.id = p.brand_id AND $whereBrand
            GROUP BY b.id
            ORDER BY b.name ASC
        ";
        $brands = $pdo->query($brandSql)->fetchAll();

        // Data to pass to view
        $data = [
            'filters'      => $filters,
            'products'     => $result['products'],
            'total'        => $result['total'],
            'totalPages'   => $totalPages,
            'isLogin'      => $isLogin,
            'roleId'       => $roleId,
            'userId'       => $userId,
            'categories'   => $categories,
            'categoryTree' => $categoryTree,
            'cateTotal'    => $cateTotal,
            'brands'       => $brands,
            'totalPro'     => $result['total'] // Total matching products
        ];

        // We render the view. We will create home.php in app/Views
        $this->view('home', $data);
    }
}
