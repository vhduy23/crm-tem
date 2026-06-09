<?php
require 'auth.php';
checkLogin();

require '../lib/db.php';
require 'partials/header.php';

$userName = htmlspecialchars($_SESSION['user']['name'] ?? 'Admin');

// ===== STATS =====
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBrands   = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
$totalCats     = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// ===== LATEST PRODUCTS =====
$latest = $pdo->query("
    SELECT p.*,
        b.name AS brand_name,
        c.name AS category_name,
        (
            SELECT image_path
            FROM product_images
            WHERE product_id = p.id
            LIMIT 1
        ) AS image_path
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    ORDER BY p.id DESC
    LIMIT 8
")->fetchAll();

$stats = [
    [
        'label'   => 'Thiết kế',
        'value'   => $totalProducts,
        'icon'    => 'fa-paintbrush',
        'href'    => '/admin/products',
        'bg'      => 'bg-blue-500/10',
        'icon_bg' => 'bg-blue-500',
        'text'    => 'text-blue-600',
    ],
    [
        'label'   => 'Người dùng',
        'value'   => $totalUsers,
        'icon'    => 'fa-circle-user',
        'href'    => '/admin/users',
        'bg'      => 'bg-emerald-500/10',
        'icon_bg' => 'bg-emerald-500',
        'text'    => 'text-emerald-600',
    ],
    [
        'label'   => 'Thương hiệu',
        'value'   => $totalBrands,
        'icon'    => 'fa-building-columns',
        'href'    => '/admin/brands',
        'bg'      => 'bg-violet-500/10',
        'icon_bg' => 'bg-violet-500',
        'text'    => 'text-violet-600',
    ],
    [
        'label'   => 'Danh mục',
        'value'   => $totalCats,
        'icon'    => 'fa-layer-group',
        'href'    => '/admin/categories',
        'bg'      => 'bg-amber-500/10',
        'icon_bg' => 'bg-amber-500',
        'text'    => 'text-amber-600',
    ],
];
?>

<!-- WELCOME BANNER -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-6 sm:p-8 mb-8 text-white shadow-lg">
    <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
    <div class="absolute -bottom-12 -left-6 h-32 w-32 rounded-full bg-indigo-400/20 blur-xl"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-blue-200 text-sm font-medium mb-1">
                Hôm nay, <?= date('d/m/Y') ?>
            </p>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                Xin chào, <?= $userName ?> 
            </h1>
            <!-- <p class="text-blue-100 mt-2 text-sm sm:text-base max-w-lg">
                Tổng quan hệ thống quản lý thiết kế — theo dõi nhanh số liệu và thao tác thường dùng.
            </p> -->
        </div>
        <a href="/" target="_blank"
           class="inline-flex items-center gap-2 self-start sm:self-center bg-white/15 hover:bg-white/25 backdrop-blur-sm border border-white/20 px-5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200">
            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
            Xem website
        </a>
    </div>
</div>

<!-- STATS -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    <?php foreach ($stats as $stat): ?>
    <a href="<?= $stat['href'] ?>"
       class="group relative bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all duration-200">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium"><?= $stat['label'] ?></p>
                <p class="text-3xl font-bold text-gray-900 mt-1 tabular-nums"><?= number_format($stat['value']) ?></p>
            </div>
            <div class="<?= $stat['icon_bg'] ?> w-11 h-11 rounded-xl flex items-center justify-center text-white shadow-sm group-hover:scale-110 transition-transform duration-200">
                <i class="fa-solid <?= $stat['icon'] ?>"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-1 text-xs font-medium <?= $stat['text'] ?> opacity-0 group-hover:opacity-100 transition-opacity">
            Xem chi tiết <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- QUICK ACTIONS -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 mb-8">
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Thao tác nhanh</h2>
    <div class="flex flex-wrap gap-3">
        <a href="/admin/products/create.php"
           class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm hover:shadow transition-all">
            <i class="fa-solid fa-plus"></i>
            Thêm thiết kế
        </a>
        <a href="/admin/products"
           class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm hover:shadow transition-all">
            <i class="fa-solid fa-paintbrush"></i>
            Quản lý thiết kế
        </a>
        <a href="/admin/brands"
           class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
            <i class="fa-solid fa-building-columns text-violet-500"></i>
            Thương hiệu
        </a>
        <a href="/admin/categories"
           class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
            <i class="fa-solid fa-layer-group text-amber-500"></i>
            Danh mục
        </a>
    </div>
</div>

<!-- LATEST PRODUCTS -->
<div class="flex justify-between items-center mb-5">
    <div>
        <h2 class="text-xl font-bold text-gray-900">Thiết kế mới nhất</h2>
        <p class="text-sm text-gray-500 mt-0.5"><?= count($latest) ?> thiết kế gần đây</p>
    </div>
    <a href="/admin/products"
       class="inline-flex items-center gap-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 transition-colors">
        Xem tất cả
        <i class="fa-solid fa-arrow-right text-xs"></i>
    </a>
</div>

<?php if (empty($latest)): ?>
<div class="bg-white rounded-2xl border border-dashed border-gray-200 p-12 text-center">
    <div class="w-16 h-16 bg-gray-100 rounded-2xl flex items-center justify-center mx-auto mb-4">
        <i class="fa-solid fa-paintbrush text-2xl text-gray-400"></i>
    </div>
    <p class="text-gray-600 font-medium">Chưa có thiết kế nào</p>
    <p class="text-gray-400 text-sm mt-1 mb-5">Bắt đầu bằng cách thêm thiết kế đầu tiên</p>
    <a href="/admin/products/create.php"
       class="inline-flex items-center gap-2 bg-blue-600 hover:bg-blue-700 text-white px-5 py-2.5 rounded-xl text-sm font-medium transition-colors">
        <i class="fa-solid fa-plus"></i>
        Thêm thiết kế
    </a>
</div>
<?php else: ?>
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
    <?php foreach ($latest as $p): ?>
    <div class="group bg-white rounded-2xl shadow-sm border border-gray-100 overflow-hidden hover:shadow-md hover:border-gray-200 transition-all duration-200">
        <div class="relative aspect-[4/3] bg-gray-100 overflow-hidden">
            <?php if ($p['image_path']): ?>
                <img src="<?= htmlspecialchars($p['image_path']) ?>"
                     alt="<?= htmlspecialchars($p['name']) ?>"
                     class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
            <?php else: ?>
                <div class="flex flex-col items-center justify-center h-full text-gray-300 gap-2">
                    <i class="fa-regular fa-image text-3xl"></i>
                    <span class="text-xs">Chưa có ảnh</span>
                </div>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-black/50 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-200"></div>
            <a href="/admin/products/edit.php?id=<?= (int) $p['id'] ?>"
               class="absolute bottom-3 right-3 bg-white/90 hover:bg-white text-gray-800 text-xs font-medium px-3 py-1.5 rounded-lg opacity-0 group-hover:opacity-100 translate-y-1 group-hover:translate-y-0 transition-all duration-200 shadow-sm">
                <i class="fa-solid fa-pen-to-square mr-1"></i> Sửa
            </a>
        </div>
        <div class="p-4">
            <h3 class="font-semibold text-gray-900 line-clamp-2 leading-snug mb-2">
                <?= htmlspecialchars($p['name']) ?>
            </h3>
            <?php if ($p['brand_name'] || $p['category_name']): ?>
            <div class="flex flex-wrap gap-1.5 mb-3">
                <?php if ($p['brand_name']): ?>
                <span class="inline-flex items-center gap-1 text-[11px] font-medium bg-violet-50 text-violet-600 px-2 py-0.5 rounded-md">
                    <i class="fa-solid fa-building-columns text-[9px]"></i>
                    <?= htmlspecialchars($p['brand_name']) ?>
                </span>
                <?php endif; ?>
                <?php if ($p['category_name']): ?>
                <span class="inline-flex items-center gap-1 text-[11px] font-medium bg-amber-50 text-amber-600 px-2 py-0.5 rounded-md">
                    <i class="fa-solid fa-layer-group text-[9px]"></i>
                    <?= htmlspecialchars($p['category_name']) ?>
                </span>
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="flex justify-between items-center pt-2 border-t border-gray-50">
                <span class="text-xs text-gray-400 font-mono">#<?= (int) $p['id'] ?></span>
                <a href="/admin/products/edit.php?id=<?= (int) $p['id'] ?>"
                   class="text-xs font-medium text-blue-600 hover:text-blue-700 transition-colors">
                    Chi tiết →
                </a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<?php require 'partials/footer.php'; ?>
