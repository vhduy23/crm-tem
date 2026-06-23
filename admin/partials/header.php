<?php
require_once __DIR__ . '/../auth.php';
checkLogin();
$current = $_SERVER['REQUEST_URI'];
$name = $_SESSION['user']['name'] ?? 'Admin';
$initials = mb_strtoupper(mb_substr($name, 0, 1, 'UTF-8'), 'UTF-8');
if (preg_match('/\s+(\S)/u', $name, $m)) {
    $initials .= mb_strtoupper($m[1], 'UTF-8');
}

$menuItems = [
    ['href' => '/admin/products',    'icon' => 'fa-paintbrush',         'label' => 'Thiết kế',      'match' => '/products',    'admin' => false],
    ['href' => '/admin/brands',      'icon' => 'fa-building-columns',   'label' => 'Thương hiệu',   'match' => '/brands',      'admin' => false],
    ['href' => '/admin/categories',  'icon' => 'fa-layer-group',        'label' => 'Danh mục',      'match' => '/categories',  'admin' => false],
    ['href' => '/admin/users',       'icon' => 'fa-users',              'label' => 'Người dùng',    'match' => '/users',       'admin' => true],
];

if (strpos($current, '/products/create') !== false) {
    $pageTitle = 'Thêm thiết kế';
} elseif (strpos($current, '/products/edit') !== false) {
    $pageTitle = 'Sửa thiết kế';
} elseif (strpos($current, '/products') !== false) {
    $pageTitle = 'Quản lý thiết kế';
} elseif (strpos($current, '/brands') !== false) {
    $pageTitle = 'Thương hiệu';
} elseif (strpos($current, '/categories') !== false) {
    $pageTitle = 'Danh mục';
} elseif (strpos($current, '/users') !== false) {
    $pageTitle = 'Người dùng';
} elseif (strpos($current, '/dashboard') !== false) {
    $pageTitle = 'Dashboard';
} else {
    $pageTitle = 'Quản trị';
}

$userRole = isAdmin() ? 'Quản trị viên' : 'Nhân viên';
?>

<!DOCTYPE html>
<html lang="vi">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?> — Admin Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<link rel="stylesheet" href="/assets/css/style.css">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>

<body class="admin-body">

<div class="admin-overlay" id="adminOverlay" aria-hidden="true"></div>

<div class="admin-layout">

    <!-- SIDEBAR -->
    <aside class="admin-sidebar" id="adminSidebar">

        <div class="admin-sidebar__brand">
            <a href="/admin/dashboard.php">
                <img src="/uploads/260515.webp" alt="A Chau Group">
            </a>
        </div>

        <p class="admin-sidebar__label">Menu chính</p>

        <nav class="admin-sidebar__nav">
            <a href="/admin/dashboard.php"
               class="admin-nav-item <?= strpos($current, '/dashboard') !== false ? 'is-active' : '' ?>">
                <span class="admin-nav-item__icon"><i class="fa-solid fa-gauge-high"></i></span>
                <span>Dashboard</span>
            </a>

            <?php foreach ($menuItems as $item):
                if ($item['admin'] && !isAdmin()) continue;
                $active = strpos($current, $item['match']) !== false;
            ?>
            <a href="<?= $item['href'] ?>"
               class="admin-nav-item <?= $active ? 'is-active' : '' ?>">
                <span class="admin-nav-item__icon"><i class="fa-solid <?= $item['icon'] ?>"></i></span>
                <span><?= $item['label'] ?></span>
            </a>
            <?php endforeach; ?>
        </nav>

        <div class="admin-sidebar__footer">
            <div class="admin-sidebar__user">
                <div class="admin-sidebar__avatar"><?= htmlspecialchars($initials) ?></div>
                <div class="admin-sidebar__user-info">
                    <div class="admin-sidebar__user-name"><?= htmlspecialchars($name) ?></div>
                    <div class="admin-sidebar__user-role"><?= $userRole ?></div>
                </div>
            </div>
            <p class="admin-sidebar__copy">© <?= date('Y') ?> ACGdev</p>
        </div>
    </aside>

    <!-- MAIN -->
    <div class="admin-main">

        <!-- TOPBAR -->
        <header class="admin-topbar">

            <div class="admin-topbar__left">
                <button type="button" class="admin-topbar__toggle" id="sidebarToggle" aria-label="Mở menu">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="admin-topbar__title-wrap">
                    <div class="admin-topbar__breadcrumb">
                        <a href="/admin/dashboard.php">Trang chủ</a>
                        <i class="fa-solid fa-chevron-right"></i>
                        <span><?= htmlspecialchars($pageTitle) ?></span>
                    </div>
                    <h1 class="admin-topbar__title"><?= htmlspecialchars($pageTitle) ?></h1>
                </div>
            </div>

            <div class="admin-topbar__right">
                <div class="admin-topbar__date">
                    <i class="fa-regular fa-calendar"></i>
                    <?= date('d/m/Y') ?>
                </div>

                <a href="/" target="_blank" class="admin-topbar__action" title="Xem website">
                    <i class="fa-solid fa-arrow-up-right-from-square"></i>
                </a>

                <span class="admin-topbar__divider"></span>

                <div class="admin-topbar__profile" id="profileDropdown">
                    <div class="admin-topbar__profile-avatar"><?= htmlspecialchars($initials) ?></div>
                    <div class="admin-topbar__profile-info">
                        <div class="admin-topbar__profile-name"><?= htmlspecialchars($name) ?></div>
                        <div class="admin-topbar__profile-role"><?= $userRole ?></div>
                    </div>
                    <i class="fa-solid fa-chevron-down admin-topbar__profile-chevron"></i>

                    <div class="admin-topbar__dropdown">
                        <a href="/admin/dashboard.php" class="admin-topbar__dropdown-item">
                            <i class="fa-solid fa-gauge-high"></i> Dashboard
                        </a>
                        <a href="/admin/logout.php" class="admin-topbar__dropdown-item admin-topbar__dropdown-item--danger">
                            <i class="fa-solid fa-right-from-bracket"></i> Đăng xuất
                        </a>
                    </div>
                </div>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="admin-content">
