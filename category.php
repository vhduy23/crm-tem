<?php
require 'lib/db.php';
require_once 'lib/categories.php';
include 'front/header.php';
$cat_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$catIds = $cat_id > 0 ? getCategoryFilterIds($pdo, $cat_id) : [];
$placeholders = $catIds ? implode(',', array_fill(0, count($catIds), '?')) : '0';
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.gc_maxlifetime', 28800);
    session_set_cookie_params(28800);
    session_start();
}
$roleId = (int)($_SESSION['member']['role_id'] ?? $_SESSION['user']['role_id'] ?? 0);
$userId = (int)($_SESSION['member']['id'] ?? $_SESSION['user']['id'] ?? 0);

if ($userId > 0) {
    $assignedSqlP = $userId > 0 ? " OR p.id IN (SELECT product_id FROM user_product_access WHERE user_id = $userId)" : "";
    if ($roleId !== 9) {
        $statusFilter = "(p.status IN (1, 2) $assignedSqlP)";
    } else {
        $statusFilter = "(p.status = 2 $assignedSqlP)";
    }
} else {
    $statusFilter = "p.status = 2";
}

$userAssignedProducts = [];
if ($userId > 0) {
    $userAssignedProducts = $pdo->query("SELECT product_id FROM user_product_access WHERE user_id = $userId")->fetchAll(PDO::FETCH_COLUMN);
}

$stmt = $pdo->prepare("
    SELECT p.*,
    (SELECT image_path FROM product_images WHERE product_id=p.id ORDER BY sort_order ASC, id ASC LIMIT 1) as thumb
    FROM products p
    WHERE category_id IN ($placeholders) AND $statusFilter
    ORDER BY id DESC
");
$stmt->execute($catIds);
?>
<div class="max-w-6xl mx-auto p-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php while($p = $stmt->fetch()): ?>
        <div class="bg-white p-3 rounded shadow hover:shadow-lg transition">
            <a href="/thiet-ke/<?= $p['slug'] ?>" class="relative block">
                <img loading="lazy" src="<?= $p['thumb'] ?>"
                    class="w-full h-50 object-cover mb-2 rounded">
                <?php 
                    $isCustomer = (isset($roleId) && $roleId == 9);
                    $isAssigned = isset($userAssignedProducts) && in_array($p['id'], $userAssignedProducts);
                    $starColor = ($isCustomer && $isAssigned) ? 'text-yellow-500' : 'text-[#0B2558]';
                ?>
                <span class="absolute bottom-2.5 left-2.5 text-[14px] font-semibold tracking-wide px-2.5 py-[3px] rounded-full backdrop-blur-[4px] <?= $starColor ?> pointer-events-none">
                    <?= ($p['status'] == 1 || $isAssigned) ? '<i class="fa-solid fa-star drop-shadow-sm"></i>' : '' ?>
                </span>
            </a>
            <div class="flex items-center justify-between">
                <a href="/thiet-ke/<?= $p['slug'] ?>">
                    <h3 class="text-center font-medium">
                        <?= htmlspecialchars($p['name']) ?>
                    </h3>
                </a>
                <button
                    class="add-print text-black py-1 rounded z-99 text-xl"
                    data-id="<?= $p['id'] ?>"
                    data-name="<?= htmlspecialchars($p['name']) ?>"
                    data-img="<?= $p['thumb'] ?>"
                >
                <i class="fa-solid fa-folder-plus"></i>
                </button>
            </div>
        </div>
    <?php endwhile; ?>
    </div>
</div>
<?php include 'front/footer.php'; ?>
