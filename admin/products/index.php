<?php
require '../../lib/db.php';
require '../../lib/categories.php';
include '../partials/header.php';

// ===== GET FILTER =====
$keyword = $_GET['keyword'] ?? '';
$category_id = $_GET['category_id'] ?? '';

// ===== PAGINATION =====
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max($page, 1);

$limit = 10;
$offset = ($page - 1) * $limit;

// ===== WHERE BUILD =====
$where = [];
$params = [];

if ($keyword) {
    $where[] = "p.name LIKE :keyword";
    $params[':keyword'] = "%$keyword%";
}

if ($category_id) {
    $catIds = getCategoryFilterIds($pdo, (int) $category_id);
    $catPlaceholders = [];
    foreach ($catIds as $i => $catId) {
        $key = ':cat_' . $i;
        $catPlaceholders[] = $key;
        $params[$key] = $catId;
    }
    $where[] = 'p.category_id IN (' . implode(', ', $catPlaceholders) . ')';
}

if (!isAdmin()) {
    $where[] = "(p.status IN (1, 2) OR p.created_by = :current_user_id)";
    $params[':current_user_id'] = getCurrentUserId();
}

$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ===== TOTAL =====
$stmtTotal = $pdo->prepare("SELECT COUNT(*) FROM products p $whereSQL");
$stmtTotal->execute($params);
$total = $stmtTotal->fetchColumn();
$totalPages = ceil($total / $limit);

// ===== QUERY =====
$stmt = $pdo->prepare("
    SELECT p.*, 
           b.name as brand_name,
           CASE
               WHEN cp.name IS NOT NULL THEN CONCAT(cp.name, ' › ', c.name)
               ELSE c.name
           END as category_name,
           (
               SELECT image_path 
               FROM product_images 
               WHERE product_id = p.id 
               LIMIT 1
           ) as thumb
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN categories cp ON c.parent_id = cp.id
    $whereSQL
    ORDER BY p.id DESC
    LIMIT :limit OFFSET :offset
");

foreach ($params as $key => $val) {
    $stmt->bindValue($key, $val);
}

$stmt->bindValue(':limit', $limit, PDO::PARAM_INT);
$stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
$stmt->execute();

// ===== LOAD CATEGORY =====
$categories = fetchCategories($pdo);
?>

<div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 mb-6">
    <div>
        <h2 class="text-lg font-bold text-gray-900">Danh sách thiết kế</h2>
    </div>
    <a href="create.php" class="size-max inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-4 py-2.5 rounded-xl text-sm font-medium shadow-sm transition-colors">
        <i class="fa-solid fa-plus text-xs"></i> Thêm thiết kế
    </a>
</div>

<div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden mb-6">
    <div class="p-4 sm:p-6 border-b border-gray-100">

    <!-- FILTER -->
    <form method="GET" class="flex gap-3 mb-4">
        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>"
            placeholder="Tìm theo tên..."
            class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20 w-1/3">

        <select name="category_id" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            <option value="">-- Danh mục --</option>
            <?php renderCategorySelectOptions($categories, $category_id); ?>
        </select>

        <button class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-xl text-sm font-medium transition-colors">
            Lọc
        </button>

        <a href="index.php" class="bg-gray-100 hover:bg-gray-200 text-gray-600 px-4 py-2 rounded-xl text-sm font-medium transition-colors flex items-center">
            Reset
        </a>
    </form>
    </div>

    <!-- TABLE -->
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead>
                <tr class="bg-gray-50 text-gray-500 text-xs uppercase tracking-wider">
                    <th class="p-3 font-medium text-center">STT</th>
                    <th class="p-3 font-medium text-center">Ảnh</th>
                    <th class="p-3 font-medium text-center">Tên</th>
                    <th class="p-3 font-medium text-center">Thương hiệu</th>
                    <th class="p-3 font-medium text-center">Danh mục</th>
                    <th class="p-3 font-medium text-center">Trạng thái</th>
                    <th class="p-3 font-medium text-center">Thao tác</th>
                </tr>
            </thead>

            <tbody>
                <?php $i = $offset + 1; ?>
                <?php while($p = $stmt->fetch()): ?>
                <tr class="border-t border-gray-50 hover:bg-gray-50/80">
                    <td class="p-3 text-center"><?= $i++ ?></td>

                    <td class="p-3 justify-items-center">
                        <?php if($p['thumb']): ?>
                            <img src="<?= $p['thumb'] ?>" class="w-14 h-14 rounded object-cover">
                        <?php else: ?>
                            <div class="w-14 h-14 bg-gray-200 flex items-center justify-center text-xs">
                                N/A
                            </div>
                        <?php endif; ?>
                    </td>

                    <td class="p-3 text-center"><?= $p['name'] ?></td>
                    <td class="p-3 text-center"><?= $p['brand_name'] ?? '-' ?></td>
                    <td class="p-3 text-center"><?= $p['category_name'] ?? '-' ?></td>
                    <td class="p-3 text-center">
                        <?php 
                        if($p['status'] == 0) echo '<span class="px-2 py-1 bg-gray-200 text-gray-700 rounded text-xs">Không công khai</span>';
                        elseif($p['status'] == 1) echo '<span class="px-2 py-1 bg-blue-100 text-blue-700 rounded text-xs">Nội bộ</span>';
                        elseif($p['status'] == 2) echo '<span class="px-2 py-1 bg-green-100 text-green-700 rounded text-xs">Công khai</span>';
                        ?>
                    </td>

                    <td class="p-3 text-center">
                        <a href="edit.php?id=<?= $p['id'] ?>" class="text-blue-600 hover:underline text-sm">Sửa</a>
                        <span class="text-gray-300 mx-1">|</span>
                        <?php // if(($p['created_by'] == getCurrentUserId()) || isAdmin()){ ?>
                            <a href="delete.php?id=<?= $p['id'] ?>" 
                                onclick="return confirm('Xóa?')" 
                                class="text-red-500 hover:underline text-sm">
                                Xóa
                            </a>
                        <?php // } ?>
                    </td>
                </tr>
                <?php endwhile; ?>
            </tbody>
        </table>
    </div>

    <!-- PAGINATION -->
    <div class="flex justify-between items-center p-4 sm:p-6 border-t border-gray-100">

        <div class="text-sm text-gray-500">
            Trang <?= $page ?> / <?= $totalPages ?>
        </div>

        <div class="flex gap-1">
            <?php
            $query = $_GET;
            ?>

            <?php if($page > 1): 
                $query['page'] = $page - 1;
            ?>
                <a href="?<?= http_build_query($query) ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 text-sm">←</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);

            for($i = $start; $i <= $end; $i++):
                $query['page'] = $i;
            ?>
                <a href="?<?= http_build_query($query) ?>"
                   class="px-3 py-1.5 border border-gray-200 rounded-lg text-sm transition-colors <?= $i == $page ? 'bg-blue-600 text-white border-blue-600' : 'text-gray-600 hover:bg-gray-50' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if($page < $totalPages): 
                $query['page'] = $page + 1;
            ?>
                <a href="?<?= http_build_query($query) ?>" class="px-3 py-1.5 border border-gray-200 text-gray-600 rounded-lg hover:bg-gray-50 text-sm">→</a>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php include '../partials/footer.php'; ?>