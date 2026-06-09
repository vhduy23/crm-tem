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

<div class="bg-white p-6 rounded-xl shadow-md">

    <!-- HEADER -->
    <div class="flex justify-between items-center mb-4">
        <h2 class="text-xl font-bold">Danh sách thiết kế</h2>

        <a href="create.php"
           class="bg-green-500 text-white px-4 py-2 rounded hover:bg-green-600">
            + Thêm thiết kế
        </a>
    </div>

    <!-- FILTER -->
    <form method="GET" class="flex gap-3 mb-4">

        <input type="text" name="keyword" value="<?= htmlspecialchars($keyword) ?>"
            placeholder="Tìm theo tên..."
            class="border p-2 rounded w-1/3">

        <select name="category_id" class="border p-2 rounded">
            <option value="">-- Danh mục --</option>
            <?php renderCategorySelectOptions($categories, $category_id); ?>
        </select>

        <button class="bg-blue-500 text-white px-4 rounded">
            Lọc
        </button>

        <a href="index.php" class="bg-gray-300 px-4 rounded flex items-center">
            Reset
        </a>
    </form>

    <!-- TABLE -->
    <div class="overflow-x-auto">
        <table class="w-full border border-gray-200 text-sm">
            <thead class="bg-gray-100">
                <tr>
                    <th class="p-3">STT</th>
                    <th class="p-3">Ảnh</th>
                    <th class="p-3">Tên</th>
                    <th class="p-3">Thương hiệu</th>
                    <th class="p-3">Danh mục</th>
                    <th class="p-3">Action</th>
                </tr>
            </thead>

            <tbody>
                <?php $i = $offset + 1; ?>
                <?php while($p = $stmt->fetch()): ?>
                <tr class="border-t hover:bg-gray-50">
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
                        <a href="edit.php?id=<?= $p['id'] ?>" class="text-blue-500">Sửa</a> |

                        <?php // if(($p['created_by'] == getCurrentUserId()) || isAdmin()){ ?>
                            <a href="delete.php?id=<?= $p['id'] ?>" 
                                onclick="return confirm('Xóa?')" 
                                class="text-red-500">
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
    <div class="flex justify-between items-center mt-4">

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
                <a href="?<?= http_build_query($query) ?>" class="px-3 py-1 border rounded">←</a>
            <?php endif; ?>

            <?php
            $start = max(1, $page - 2);
            $end = min($totalPages, $page + 2);

            for($i = $start; $i <= $end; $i++):
                $query['page'] = $i;
            ?>
                <a href="?<?= http_build_query($query) ?>"
                   class="px-3 py-1 border rounded <?= $i == $page ? 'bg-blue-500 text-white' : '' ?>">
                    <?= $i ?>
                </a>
            <?php endfor; ?>

            <?php if($page < $totalPages): 
                $query['page'] = $page + 1;
            ?>
                <a href="?<?= http_build_query($query) ?>" class="px-3 py-1 border rounded">→</a>
            <?php endif; ?>
        </div>

    </div>

</div>

<?php include '../partials/footer.php'; ?>