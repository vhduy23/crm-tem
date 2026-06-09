<?php 

echo 'vào header!!';
exit();

require '../auth.php'; 

checkLogin(); ?>
<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<script src="https://cdn.tailwindcss.com"></script>

</head>

<body class="bg-gray-100">

<div class="flex">
    <!-- sidebar -->
    <div class="w-64 bg-white h-screen shadow">
        <div class="p-4 font-bold text-lg">ADMIN</div>
        <ul>
            <li><a href="/admin/products" class="block p-3 hover:bg-gray-200">Products</a></li>
            <li><a href="/admin/brands" class="block p-3 hover:bg-gray-200">Brands</a></li>
            <li><a href="/admin/categories" class="block p-3 hover:bg-gray-200">Categories</a></li>
            <li><a href="/admin/users" class="block p-3 hover:bg-gray-200">Users</a></li>
        </ul>
    </div>

    <!-- content -->
    <div class="flex-1 p-6">