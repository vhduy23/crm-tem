<?php
namespace App\Controllers;

use Core\Controller;
use App\Models\ProductModel;

class ProductController extends Controller {
    public function category() {
        global $pdo;
        require_once __DIR__ . '/../../lib/db.php';
        require_once __DIR__ . '/../../lib/session.php';

        $categoryId = isset($_GET['id']) ? (int)$_GET['id'] : 0;
        
        $filters = [
            'keyword'     => '',
            'category_id' => $categoryId,
            'brand_id'    => 0,
            'page'        => max(1, isset($_GET['page']) ? (int)$_GET['page'] : 1),
            'limit'       => 12,
            'sort'        => 'newest'
        ];

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
        
        $userAssignedProducts = [];
        if ($userId > 0) {
            $userAssignedProducts = $pdo->query("SELECT product_id FROM user_product_access WHERE user_id = $userId")->fetchAll(\PDO::FETCH_COLUMN);
        }

        $data = [
            'products' => $result['products'],
            'roleId' => $roleId,
            'userAssignedProducts' => $userAssignedProducts
        ];

        $this->view('Product/category', $data);
    }

    public function detail($slug) {
        global $pdo;
        require_once __DIR__ . '/../../lib/db.php';
        require_once __DIR__ . '/../../lib/session.php';

        $isLogin = isset($_SESSION['member']) || isset($_SESSION['user']);
        $roleId = (int)($_SESSION['member']['role_id'] ?? $_SESSION['user']['role_id'] ?? 0);
        $userId = (int)($_SESSION['member']['id'] ?? $_SESSION['user']['id'] ?? 0);
        
        $userContext = [
            'is_login' => $isLogin,
            'role_id'  => $roleId,
            'user_id'  => $userId
        ];

        $productModel = new ProductModel();
        $result = $productModel->getProductBySlug($slug, $userContext);

        if (isset($result['error'])) {
            if ($result['error'] === 'not_found') {
                die('Không tìm thấy sản phẩm');
            } elseif ($result['error'] === 'internal_login_required') {
                die('Sản phẩm nội bộ. Vui lòng đăng nhập để xem thiết kế này.');
            } else {
                die('Sản phẩm không khả dụng hoặc bạn không có quyền xem thiết kế này.');
            }
        }

        $data = $result;
        $data['isLoggedIn'] = $isLogin;

        $this->view('Product/detail', $data);
    }
}
