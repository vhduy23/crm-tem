<?php 
require_once __DIR__ . '/../lib/db.php';
require_once __DIR__ . '/../lib/function.php';
require_once __DIR__ . '/../lib/categories.php';

session_start();

// lấy danh mục
$catTree = buildCategoryTree(fetchCategories($pdo));
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
                <?php foreach($catTree as $parent): ?>
                    <div class="relative group">
                        <a href="/?cat=<?= $parent['id'] ?>"
                           class="hover:text-[#e1aa58] text-white font-medium">
                            <?= htmlspecialchars($parent['name']) ?>
                        </a>
                        <?php if (!empty($parent['children'])): ?>
                        <div class="absolute left-0 top-full pt-2 hidden group-hover:block z-50">
                            <div class="bg-white rounded-lg shadow-lg py-2 min-w-[180px]">
                                <?php foreach ($parent['children'] as $child): ?>
                                <a href="/category.php?id=<?= $child['id'] ?>"
                                   class="block px-4 py-2 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700">
                                    <?= htmlspecialchars($child['name']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </nav>
            <!-- SEARCH & USER -->
            <div class="flex flex-col sm:flex-row items-center gap-4 justify-center">
                <div class="flex items-center gap-3">
                    <?php if (isset($_SESSION['member'])): ?>
                        <div class="flex items-center gap-2">
                            <span class="w-7 h-7 rounded-full bg-blue-600/30 text-[#e1aa58] flex items-center justify-center text-xs border border-[#e1aa58]/20">
                                <i class="fa-solid fa-user-check"></i>
                            </span>
                            <span class="text-white text-xs sm:text-sm">
                                Chào, <strong class="text-[#e1aa58]"><?= htmlspecialchars($_SESSION['member']['name']) ?></strong>
                            </span>
                            <span class="text-white/20">|</span>
                            <a href="/logout.php" class="text-white/70 hover:text-red-400 text-xs sm:text-sm font-medium transition-colors">
                                Đăng xuất
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="flex items-center gap-3">
                            <a href="/login.php" class="text-white/80 hover:text-white text-sm font-medium transition-colors">
                                Đăng nhập
                            </a>
                            <span class="text-white/20">|</span>
                            <a href="/register.php" class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded-lg text-xs font-semibold tracking-wide transition-colors">
                                Đăng ký
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</header>
<!-- CONTENT -->
<!-- <main class="max-w-6xl mx-auto p-4"></main>  -->
