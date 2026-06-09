<?php 
require __DIR__ . '../../lib/db.php';
require __DIR__ . '../../lib/function.php';

// lấy danh mục
$cats = $pdo->query("SELECT id, name FROM categories ORDER BY name ASC")->fetchAll();
if(isDetail()){
    $slug = $_GET['slug'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM products WHERE slug=?");
    $stmt->execute([$slug]);
    $product = $stmt->fetch();
}


?>

<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?php echo (isDetail()) ? $product['name'] : 'AChau Group';  ?></title>

<script src="https://cdn.tailwindcss.com"></script>
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/7.0.1/css/all.min.css">
<link rel="preconnect" href="https://fonts.googleapis.com"/>
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin/>
<link href="https://fonts.googleapis.com/css2?family=Lexend:wght@300;400;500;600;700&family=DM+Sans:ital,wght@0,300;0,400;0,500;1,400&display=swap" rel="stylesheet"/>
<link rel="stylesheet" href="../assets/css/style.css">
<?php if(isDetail()){ ?>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css"/>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<?php } ?>

</head>

<body>

<!-- HEADER -->
<header class="bg-blue-950 shadow">

    <div class="max-w-6xl mx-auto px-4">

        <div class="grid grid-cols-1 gap-[10px] md:flex items-center text-center justify-between py-4">

            <!-- LOGO -->
            <a href="/" class="flex justify-center text-xl font-bold text-blue-600">
                <img class="w-40" src="/uploads/260515.webp" alt="AchauGroup">
            </a>

            <!-- MENU -->
            <nav class="hidden md:flex gap-6">

                <a href="/" class="hover:text-[#e1aa58] text-white font-medium">Trang chủ</a>

                <?php foreach($cats as $c): ?>
                    <a href="/category.php?id=<?= $c['id'] ?>"
                       class="hover:text-[#e1aa58] text-white font-medium">
                        <?= $c['name'] ?>
                    </a>
                <?php endforeach; ?>

            </nav>

            <!-- SEARCH -->
            <form action="/index.php" method="GET" class="flex justify-center gap-2">

                <input 
                    type="text" 
                    name="q"
                    placeholder="Tìm thiết kế..."
                    class="border px-3 py-1 rounded"
                >

                <button class="bg-blue-500 text-white px-3 rounded">
                    Tìm
                </button>

            </form>

        </div>

    </div>

</header>

<!-- CONTENT -->
<!-- <main class="max-w-6xl mx-auto p-4"></main>  -->