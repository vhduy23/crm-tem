<?php
require_once __DIR__ . '/../auth.php';
checkLogin(); 
$current = $_SERVER['REQUEST_URI'];
$name = $_SESSION['user']['name'];

?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>Admin Panel</title>
<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
</head>

<body class="bg-gray-100">

<div class="flex min-h-screen">

    <!-- SIDEBAR -->
    <aside class="w-64 bg-white shadow-lg flex flex-col">

        <!-- Logo -->
        <div class="p-2.5 text-xl font-bold border-b text-center" >
            <a href="/admin/dashboard.php" style="display: block;width: 150px;"><img src="/uploads/260515.webp" alt="AChau Group"></a>
        </div>

        <!-- Menu -->
        <nav class="flex-1 p-3 space-y-1">

            <a href="/admin/products"
               class="block px-4 py-2 rounded hover:shadow-lg
               <?= strpos($current, '/products') !== false ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                <i class="fa-solid fa-paintbrush"></i> Thiết kế
            </a>

            <a href="/admin/brands"
               class="block px-4 py-2 rounded hover:shadow-lg
               <?= strpos($current, '/brands') !== false ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                <i class="fa-solid fa-building-columns"></i> Thương hiệu
            </a>

            <a href="/admin/categories"
               class="block px-4 py-2 rounded hover:shadow-lg
               <?= strpos($current, '/categories') !== false ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                <i class="fa-solid fa-layer-group"></i> Danh mục
            </a>

            <?php if(isAdmin()){ ?>
            <a href="/admin/users"
               class="block px-4 py-2 rounded hover:shadow-lg
               <?= strpos($current, '/users') !== false ? 'bg-blue-100 text-blue-600' : 'hover:bg-gray-100' ?>">
                <i class="fa-regular fa-circle-user"></i> Người dùng
            </a>

            <?php } ?>
        </nav>

        <!-- Footer sidebar -->
        <div class="p-4 border-t text-sm text-gray-500">
            © <?= date('Y') ?> ACGdev
        </div>
    </aside>

    <!-- MAIN -->
    <div class="flex-1 flex flex-col">

        <!-- TOPBAR -->
        <header class="bg-white shadow px-6 py-3 flex justify-between items-center" style="height: 67px;">

            <h1 class="font-semibold text-lg">
                Dashboard
            </h1>

            <div class="flex items-center gap-3">
                <span class="text-sm text-gray-600">Xin chào, <strong><?= $name ?></strong></span> |

                <a href="/admin/logout.php"
                   class="text-red-500 hover:underline text-sm">
                Đăng xuất
                </a>
            </div>
        </header>

        <!-- CONTENT -->
        <main class="p-6">