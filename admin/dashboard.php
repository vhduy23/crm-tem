<?php
require 'auth.php';
checkLogin(); 

require '../lib/db.php';
require 'partials/header.php';


// ===== STATS =====
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBrands   = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
$totalCats     = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// ===== LATEST PRODUCTS =====
$latest = $pdo->query("
    SELECT p.*, 
        (
            SELECT image_path 
            FROM product_images 
            WHERE product_id = p.id 
            LIMIT 1
        ) as image_path
    FROM products p
    ORDER BY p.id DESC
    LIMIT 8
")->fetchAll();
?>

<h1 class="text-2xl font-bold mb-6">Dashboard</h1>

<!-- STATS -->
<div class="grid grid-cols-4 gap-6 mb-8">

    <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between hover:shadow-lg cursor-pointer">
        <div>
            <p class="text-gray-500 text-sm">Thiết kế</p>
            <h2 class="text-2xl font-bold"><?= $totalProducts ?></h2>
        </div>
        <div class="text-3xl"><i class="fa-solid fa-paintbrush"></i></div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between hover:shadow-lg cursor-pointer">
        <div>
            <p class="text-gray-500 text-sm">Người dùng</p>
            <h2 class="text-2xl font-bold"><?= $totalUsers ?></h2>
        </div>
        <div class="text-3xl"><i class="fa-regular fa-circle-user"></i></div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between hover:shadow-lg cursor-pointer">
        <div>
            <p class="text-gray-500 text-sm">Thương hiệu</p>
            <h2 class="text-2xl font-bold"><?= $totalBrands ?></h2>
        </div>
        <div class="text-3xl"><i class="fa-solid fa-building-columns"></i></div>
    </div>

    <div class="bg-white p-5 rounded-xl shadow flex items-center justify-between hover:shadow-lg cursor-pointer">
        <div>
            <p class="text-gray-500 text-sm">Danh mục</p>
            <h2 class="text-2xl font-bold"><?= $totalCats ?></h2>
        </div>
        <div class="text-3xl"><i class="fa-solid fa-layer-group"></i></div>
    </div>

</div>

<!-- QUICK ACTION -->
<div class="flex flex-wrap gap-3 mb-8">

    <a href="/admin/products/create.php"
        class="bg-green-500 hover:bg-green-600 text-white px-5 py-2 rounded-lg shadow">
        + Thêm thiết kế
    </a>

    <a href="/admin/products"
        class="bg-gray-700 hover:bg-gray-800 text-white px-5 py-2 rounded-lg shadow">
        Quản lý thiết kế
    </a>

    <a href="/" target="_blank"
        class="bg-blue-500 hover:bg-blue-600 text-white px-5 py-2 rounded-lg shadow">
        Xem website
    </a>

</div>

<!-- LATEST PRODUCTS -->
<div class="flex justify-between items-center mb-3">
    <h2 class="text-xl font-bold">Thiết kế mới</h2>

    <a href="/admin/products" class="text-blue-500 text-sm hover:underline">
        Xem tất cả →
    </a>
</div>

<div class="grid grid-cols-4 gap-6">

<?php foreach($latest as $p): ?>

    <div class="bg-white rounded-xl shadow hover:shadow-lg transition overflow-hidden">

        <!-- IMAGE -->
        <div class="h-50 bg-gray-100">
            <?php if($p['image_path']): ?>
                <img src="<?= $p['image_path'] ?>" 
                     class="w-full h-full object-cover">
            <?php else: ?>
                <div class="flex items-center justify-center h-full text-gray-400">
                    No Image
                </div>
            <?php endif; ?>
        </div>

        <!-- CONTENT -->
        <div class="p-4">
            <h3 class="font-semibold mb-2 line-clamp-2">
                <?= $p['name'] ?>
            </h3>

            <div class="flex justify-between items-center">
                <a href="/admin/products/edit.php?id=<?= $p['id'] ?>" 
                   class="text-blue-500 text-sm hover:underline">
                    Edit
                </a>

                <span class="text-xs text-gray-400">
                    #<?= $p['id'] ?>
                </span>
            </div>
        </div>

    </div>

<?php endforeach; ?>

</div>

<?php require 'partials/footer.php'; ?>