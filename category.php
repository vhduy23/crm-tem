<?php
require 'lib/db.php';
require 'lib/categories.php';
include 'front/header.php';

$cat_id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$catIds = $cat_id > 0 ? getCategoryFilterIds($pdo, $cat_id) : [];
$placeholders = $catIds ? implode(',', array_fill(0, count($catIds), '?')) : '0';

$stmt = $pdo->prepare("
    SELECT p.*,
    (SELECT image_path FROM product_images WHERE product_id=p.id LIMIT 1) as thumb
    FROM products p
    WHERE category_id IN ($placeholders)
    ORDER BY id DESC
");
$stmt->execute($catIds);
?>

<div class="max-w-6xl mx-auto p-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">

    <?php while($p = $stmt->fetch()): ?>
        <div class="bg-white p-3 rounded shadow hover:shadow-lg transition">
            <a href="/thiet-ke/<?= $p['slug'] ?>">
                <img loading="lazy" src="<?= $p['thumb'] ?>"
                    class="w-full h-50 object-cover mb-2 rounded">
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
