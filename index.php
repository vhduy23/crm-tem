<?php
require_once 'lib/categories.php';
require 'front/header.php';

// ===== GET PARAM =====

$keyword = $_GET['q'] ?? '';
$category_id = isset($_GET['cat']) ? (int)$_GET['cat'] : 0;
$brand_id = isset($_GET['brand']) ? (int)$_GET['brand'] : 0;
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$page = max(1, $page);
$limit = 12;
$offset = ($page - 1) * $limit;
$sort = $_GET['sort'] ?? 'newest';

// ===== HELPER: build filter URL =====

function buildFilterUrl($overrides = []) {
    global $keyword, $category_id, $brand_id, $sort;
    $p = [
        'q'     => $overrides['q']     ?? $keyword,
        'cat'   => $overrides['cat']   ?? $category_id,
        'brand' => $overrides['brand'] ?? $brand_id,
        'page'  => $overrides['page']  ?? null,
        'sort'  => $overrides['sort']  ?? $sort,
    ];
    $parts = [];
    if ($p['q'])     $parts[] = 'q=' . urlencode($p['q']);
    if ($p['cat'])   $parts[] = 'cat=' . (int)$p['cat'];
    if ($p['brand']) $parts[] = 'brand=' . (int)$p['brand'];
    if ($p['sort'] && $p['sort'] !== 'newest') $parts[] = 'sort=' . urlencode($p['sort']);
    if ($p['page'] && $p['page'] > 1) $parts[] = 'page=' . (int)$p['page'];
    return '?' . implode('&', $parts);
}
// ===== WHERE =====
$conditions = [];
$params = [];

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
$conditions[] = "p.status = 2"; // Chỉ show công khai ở front
if ($keyword) {
    $conditions[] = "p.name LIKE ?";
    $params[] = "%$keyword%";
}
if ($category_id > 0) {
    $catIds = getCategoryFilterIds($pdo, $category_id);
    $catPlaceholders = implode(',', array_fill(0, count($catIds), '?'));
    $conditions[] = "p.category_id IN ($catPlaceholders)";
    $params = array_merge($params, $catIds);
}
if ($brand_id > 0) {
    $conditions[] = "p.brand_id = ?";
    $params[] = $brand_id;
}
$where = $conditions ? 'WHERE ' . implode(' AND ', $conditions) : '';

$orderBy = "ORDER BY p.id DESC";
if ($sort === 'oldest') {
    $orderBy = "ORDER BY p.id ASC";
} elseif ($sort === 'name_asc') {
    $orderBy = "ORDER BY p.name ASC";
} elseif ($sort === 'name_desc') {
    $orderBy = "ORDER BY p.name DESC";
}

// ===== TOTAL =====
$stmt = $pdo->prepare("SELECT COUNT(*) FROM products p $where");
$stmt->execute($params);
$total = $stmt->fetchColumn();
$totalPages = ceil($total / $limit);

// ===== DATA =====
$stmt = $pdo->prepare("
    SELECT 
        p.*,
        b.name as brand_name,
        c.id as cate_id,
        CASE
            WHEN cp.name IS NOT NULL THEN CONCAT(cp.name, ' › ', c.name)
            ELSE c.name
        END as cate_name,
        (
            SELECT GROUP_CONCAT(image_path)
            FROM product_images 
            WHERE product_id = p.id
        ) as images,
        (
            SELECT image_path 
            FROM product_images 
            WHERE product_id=p.id 
            LIMIT 1
        ) as thumb
    FROM products p
    LEFT JOIN brands b ON p.brand_id = b.id
    LEFT JOIN categories c ON p.category_id = c.id
    LEFT JOIN categories cp ON c.parent_id = cp.id
    $where
    $orderBy
    LIMIT $limit OFFSET $offset
");
$totalPro = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();

// ===== CATEGORY =====
$categories = fetchCategories($pdo);
$categoryTree = buildCategoryTree($categories);
$cateTotal = $pdo->query("SELECT COUNT(*) FROM categories WHERE parent_id IS NULL")->fetchColumn();

// ===== BRAND =====
$brandSql = "
    SELECT 
        b.id, 
        b.name, 
        COUNT(p.id) as product_count 
    FROM brands b
    LEFT JOIN products p ON b.id = p.brand_id
    GROUP BY b.id
    ORDER BY b.name ASC
";
$brands = $pdo->query($brandSql)->fetchAll();
$stmt->execute($params);
$data = $stmt->fetchAll();
// convert string -> array images
foreach ($data as &$p) {
    $p['images'] = $p['images'] 
        ? explode(',', $p['images']) 
        : [];
}
unset($p); // MUST UNSET REFERENCE TO PREVENT OVERWRITING LAST ITEM LATER

?>
<div class="bg-[#F7F8FB] min-h-screen text-[#374368] font-sans ">
    <div class="bg-white border-b border-[#0B2558]/10 px-6 py-2.5">
        <div class="max-w-[1340px] mx-auto flex items-center gap-2 text-[12.5px] text-[#8892AA]">
            <a href="/" class="text-[#1a52b5] hover:underline">Trang chủ</a>
            <span class="text-[#0b255861]/[0.18] text-[13px] font-bold">›</span>
            <span class="text-[#374368] font-medium">Bộ sưu tập thiết kế</span>
            <span class="text-[#0b255861]/[0.18] text-[13px] font-bold">›</span>
        </div>
    </div>
    <div class="max-w-[1340px] mx-auto px-[10px] pt-7 grid grid-cols-1 lg:grid-cols-[264px_1fr] gap-7 items-start">
        
        <aside class="hidden lg:flex flex-col gap-4 sticky top-20">
            <div class="bg-[#e8edf8] rounded-[14px] p-4 mb-0.5">
                <div class="grid grid-cols-2 gap-2.5">
                    <div class="bg-[#EFF1F7] rounded-[10px] p-3 text-center">
                        <div class="text-[22px] font-bold text-[#0B2558] leading-none">120+</div>
                        <div class="text-[11px] text-[#8892AA] mt-1">Mẫu thiết kế</div>
                    </div>
                    <div class="bg-[#EFF1F7] rounded-[10px] p-3 text-center">
                        <div class="text-[22px] font-bold text-[#0B2558] leading-none"><?= $cateTotal ?></div>
                        <div class="text-[11px] text-[#8892AA] mt-1">Dòng mũ</div>
                    </div>
                </div>
            </div>
            <div class="bg-white border border-[#0B2558]/10 rounded-[14px] overflow-hidden">
                <div class="flex items-center justify-between p-[14px_18px_12px] border-b border-[#0B2558]/10 cursor-pointer">
                    <h3 class="text-[13px] font-semibold text-[#0B2558] flex items-center gap-2">
                        <svg width="15" height="15" class="text-[#1a52b5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                        Danh mục
                    </h3>
                </div>
                <div class="p-[14px_18px_16px]">
                    <?php $filterPrefix = 'desktop'; include __DIR__ . '/front/partials/category-filter.php'; ?>
                </div>
            </div>
            <div class="bg-white border border-[#0B2558]/10 rounded-[14px] overflow-hidden">
                <div class="flex items-center justify-between p-[14px_18px_12px] border-b border-[#0B2558]/10 cursor-pointer">
                    <h3 class="text-[13px] font-semibold text-[#0B2558] flex items-center gap-2">
                        <svg width="15" height="15" class="text-[#1a52b5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                        Thương hiệu
                    </h3>
                </div>
                <div class="p-[14px_18px_16px] flex flex-wrap gap-2">
                    <a href="<?= buildFilterUrl(['brand' => 0]) ?>"
                       class="px-3.5 py-1.5 rounded-full border-[1.5px] text-[12.5px] font-medium cursor-pointer transition-all no-underline
                       <?= $brand_id == 0 ? 'border-[#1a52b5] bg-[#1a52b5] text-white' : 'border-[#0B2558]/[0.18] bg-white text-[#374368] hover:border-[#1a52b5] hover:text-[#1a52b5] hover:bg-[#e8edf8]' ?>">
                        Tất cả
                    </a>
                    <?php foreach($brands as $brand): ?>
                    <a href="<?= buildFilterUrl(['brand' => $brand['id']]) ?>"
                       class="px-3.5 py-1.5 rounded-full border-[1.5px] text-[12.5px] font-medium cursor-pointer transition-all no-underline
                       <?= $brand_id == $brand['id'] ? 'border-[#1a52b5] bg-[#1a52b5] text-white' : 'border-[#0B2558]/[0.18] bg-white text-[#374368] hover:border-[#1a52b5] hover:text-[#1a52b5] hover:bg-[#e8edf8]' ?>">
                        <?= htmlspecialchars($brand['name']) ?>
                        <span class="text-[10.5px] opacity-70">(<?= $brand['product_count'] ?>)</span>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </aside>
        <!-- ===== MOBILE FILTER POPUP ===== -->
        <div id="mobileFilterOverlay" class="fixed inset-0 bg-black/50 z-[100] hidden opacity-0 transition-opacity duration-300 lg:!hidden" onclick="closeMobileFilter()"></div>
        <div id="mobileFilterDrawer" class="fixed bottom-0 left-0 right-0 z-[101] translate-y-full transition-transform duration-300 ease-[cubic-bezier(0.32,0.72,0,1)] lg:!hidden">
            <div class="bg-white rounded-t-[20px] max-h-[85vh] flex flex-col shadow-[0_-10px_40px_rgba(11,37,88,0.18)]">
                <!-- Header -->
                <div class="flex items-center justify-between p-4 pb-3 border-b border-[#0B2558]/10 shrink-0">
                    <h3 class="text-[16px] font-bold text-[#0B2558] flex items-center gap-2">
                        <svg width="18" height="18" class="text-[#1a52b5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        Bộ lọc sản phẩm
                    </h3>
                    <button onclick="closeMobileFilter()" class="w-8 h-8 flex items-center justify-center rounded-full bg-[#EFF1F7] hover:bg-red-50 hover:text-red-500 text-[#8892AA] transition-colors">
                        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </button>
                </div>
                <!-- Scrollable content -->
                <div class="overflow-y-auto p-4 flex flex-col gap-5">
                    <!-- Danh mục -->
                    <div>
                        <h4 class="text-[13px] font-semibold text-[#0B2558] flex items-center gap-2 mb-3">
                            <svg width="14" height="14" class="text-[#1a52b5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                            Danh mục
                        </h4>
                        <?php $filterPrefix = 'mobile'; include __DIR__ . '/front/partials/category-filter.php'; ?>
                    </div>
                    <!-- Thương hiệu -->
                    <div>
                        <h4 class="text-[13px] font-semibold text-[#0B2558] flex items-center gap-2 mb-3">
                            <svg width="14" height="14" class="text-[#1a52b5]" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M20.59 13.41l-7.17 7.17a2 2 0 0 1-2.83 0L2 12V2h10l8.59 8.59a2 2 0 0 1 0 2.82z"/><line x1="7" y1="7" x2="7.01" y2="7"/></svg>
                            Thương hiệu
                        </h4>
                        <div class="flex flex-wrap gap-2">
                            <a href="<?= buildFilterUrl(['brand' => 0]) ?>"
                               class="px-3.5 py-1.5 rounded-full border-[1.5px] text-[12.5px] font-medium transition-all no-underline
                               <?= $brand_id == 0 ? 'border-[#1a52b5] bg-[#1a52b5] text-white' : 'border-[#0B2558]/[0.18] bg-white text-[#374368]' ?>">
                                Tất cả
                            </a>
                            <?php foreach($brands as $brand): ?>
                            <a href="<?= buildFilterUrl(['brand' => $brand['id']]) ?>"
                               class="px-3.5 py-1.5 rounded-full border-[1.5px] text-[12.5px] font-medium transition-all no-underline
                               <?= $brand_id == $brand['id'] ? 'border-[#1a52b5] bg-[#1a52b5] text-white' : 'border-[#0B2558]/[0.18] bg-white text-[#374368]' ?>">
                                <?= htmlspecialchars($brand['name']) ?>
                                <span class="text-[10.5px] opacity-70">(<?= $brand['product_count'] ?>)</span>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <!-- Footer -->
                <div class="p-4 pt-3 border-t border-[#0B2558]/10 shrink-0">
                    <a href="?" class="block w-full py-2.5 text-center text-[13px] font-semibold text-[#8892AA] hover:text-red-500 transition-colors no-underline">
                        Xoá tất cả bộ lọc
                    </a>
                </div>
            </div>
        </div>
        <main class="min-w-0">
            
            <div class="flex flex-wrap items-center justify-between gap-3 mb-5">
                <div class="flex items-center gap-3 flex-wrap">
                    <p class="text-[18px] font-bold text-[#0B2558]">
                        Bộ sưu tập thiết kế <span class="text-[#1a52b5] font-normal text-[14px] ml-2">— <?= $total ?> mẫu</span>
                    </p>
                </div>
                
                <div class="flex items-center gap-2.5">
                    <!-- Mobile filter button -->
                    <button id="mobileFilterBtn" onclick="openMobileFilter()" 
                            class="lg:hidden flex items-center gap-2 px-4 py-2 rounded-lg border-[1.5px] border-[#1a52b5] bg-[#1a52b5] text-white text-[13px] font-semibold transition-all active:scale-95 shadow-[0_2px_8px_rgba(26,82,181,0.3)]">
                        <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><polygon points="22 3 2 3 10 12.46 10 19 14 21 14 12.46 22 3"/></svg>
                        Bộ lọc
                        <?php 
                            $activeFilters = 0;
                            if($category_id > 0) $activeFilters++;
                            if($brand_id > 0) $activeFilters++;
                        ?>
                        <?php if($activeFilters > 0): ?>
                        <span class="bg-white text-[#1a52b5] text-[11px] font-bold w-[18px] h-[18px] rounded-full flex items-center justify-center"><?= $activeFilters ?></span>
                        <?php endif; ?>
                    </button>
                    <select onchange="window.location.href=this.value" class="px-3 py-2 rounded-lg border-[1.5px] border-[#0B2558]/[0.18] text-[13px] text-[#374368] bg-white outline-none focus:border-[#1a52b5] transition-colors cursor-pointer">
                        <option value="<?= buildFilterUrl(['sort' => 'newest', 'page' => 1]) ?>" <?= $sort == 'newest' ? 'selected' : '' ?>>Mới nhất</option>
                        <option value="<?= buildFilterUrl(['sort' => 'oldest', 'page' => 1]) ?>" <?= $sort == 'oldest' ? 'selected' : ''  ?>>Cũ nhất</option>
                        <option value="<?= buildFilterUrl(['sort' => 'name_asc', 'page' => 1]) ?>" <?= $sort == 'name_asc' ? 'selected' : '' ?>>Tên A–Z</option>
                        <option value="<?= buildFilterUrl(['sort' => 'name_desc', 'page' => 1]) ?>" <?= $sort == 'name_desc' ? 'selected' : '' ?>>Tên Z–A</option>
                    </select>
                </div>
            </div>
            <div class="flex items-center mb-5 flex-wrap gap-2 mb-4.5">
                <?php if($keyword): ?>
                <span class="inline-flex items-center gap-1.5 bg-[#e8edf8] text-[#1a52b5] border border-[#1a52b5]/20 rounded-full px-3 py-1 text-[12.5px] font-medium">
                    Từ khoá: <?= htmlspecialchars($keyword) ?>
                    <a href="<?= buildFilterUrl(['q' => '']) ?>" class="text-[#1a52b5] hover:text-red-600 transition-colors">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                </span>
                <?php endif; ?>
                <?php if($category_id > 0):
                    $activeCatName = getCategoryDisplayName($categories, $category_id);
                ?>
                <span class="inline-flex items-center gap-1.5 bg-[#e8f5e9] text-[#0F6E56] border border-[#0F6E56]/20 rounded-full px-3 py-1 text-[12.5px] font-medium">
                    Danh mục: <?= htmlspecialchars($activeCatName) ?>
                    <a href="<?= buildFilterUrl(['cat' => 0]) ?>" class="text-[#0F6E56] hover:text-red-600 transition-colors">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                </span>
                <?php endif; ?>
                <?php if($brand_id > 0):
                    $activeBrandName = '';
                    foreach($brands as $br) { if($br['id'] == $brand_id) { $activeBrandName = $br['name']; break; } }
                ?>
                <span class="inline-flex items-center gap-1.5 bg-[#fff3e0] text-[#e65100] border border-[#e65100]/20 rounded-full px-3 py-1 text-[12.5px] font-medium">
                    Thương hiệu: <?= htmlspecialchars($activeBrandName) ?>
                    <a href="<?= buildFilterUrl(['brand' => 0]) ?>" class="text-[#e65100] hover:text-red-600 transition-colors">
                        <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                    </a>
                </span>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-2 lg:grid-cols-3 gap-2.5 md:gap-4.5">
                <?php if(!empty($data)): ?>

                    <?php foreach($data as $p): ?>

                    <article class="bg-white border border-[#0B2558]/10 rounded-[14px] overflow-hidden relative group cursor-pointer hover:shadow-[0_10px_36px_rgba(11,37,88,0.14)] hover:-translate-y-1 hover:border-[#1a52b5]/25 transition-all duration-300">
                        
                        <div class="relative overflow-hidden">
                            <button type="button" class="open-design-popup block w-full aspect-[4/3] bg-[#EFF1F7] p-0 border-0 cursor-pointer overflow-hidden" data-product-id="<?= $p['id'] ?>">
                                <img src="<?= $p['thumb'] ?>" 
                                     class="w-full h-full object-cover group-hover:scale-[1.04] transition-transform duration-[380ms] ease-[cubic-bezier(0.4,0,0.2,1)]" 
                                     loading="lazy" 
                                     alt="<?= htmlspecialchars($p['name']) ?>"/>
                            </button>
                            <?php if(!empty($p['cate_name'])): ?>
                            <span class="absolute top-2.5 left-2.5 text-[10.5px] font-semibold tracking-wide px-2.5 py-[3px] rounded-full backdrop-blur-[4px] bg-[#0B2558]/72 text-white pointer-events-none tag-<?= $p['cate_id'] ?>">
                                <?= htmlspecialchars($p['cate_name']) ?>
                            </span>
                            <span class="absolute top-2.5 right-2.5 text-[10.5px] font-semibold tracking-wide px-2.5 py-[3px] rounded-full backdrop-blur-[4px] bg-[#0B2558]/72 text-white pointer-events-none bg-red-600">Đã kiểm duyệt</span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3.5 md:p-4">
                            <button type="button" class="open-design-popup text-left w-full bg-transparent border-0 p-0 cursor-pointer" data-product-id="<?= $p['id'] ?>">
                                <!-- <p class="text-[11px] font-semibold tracking-wider uppercase text-[#8892AA] mb-1.5">Sản phẩm</p> -->
                                <h3 class="text-[15px] font-semibold text-[#0B2558] mb-2.5 leading-snug whitespace-nowrap overflow-hidden text-ellipsis">
                                    <?= htmlspecialchars($p['name']) ?>
                                </h3>
                            </button>
                            
                            <div class="grid grid-cols-1 gap-2 mt-3 md:grid-cols-2 md:flex">
                                <button type="button" class="open-design-popup flex-1 px-3 py-2 bg-[#0B2558] text-white rounded-lg text-[13px] font-semibold text-center hover:bg-[#163580] transition-colors border-0 cursor-pointer" data-product-id="<?= $p['id'] ?>">
                                    Xem thiết kế
                                </button>
                                
                                <button 
                                    class="add-print flex justify-center items-center gap-1.5 px-3 py-2 bg-[#EFF1F7] hover:bg-[#fdf3c0] hover:text-[#0B2558] text-[#374368] rounded-lg text-[12px] transition-colors whitespace-nowrap"
                                    data-id="<?= $p['id'] ?>"
                                    data-name="<?= htmlspecialchars($p['name']) ?>"
                                    data-images='<?= json_encode($p["images"]) ?>'
                                    aria-label="In PDF">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
                                    PDF
                                </button>
                            </div>
                        </div>
                    </article>
                    <?php endforeach; ?>
                    <?php unset($p) ?>
                <?php else: ?>
                    <div class="col-span-full py-16 text-center text-[#8892AA] bg-white rounded-[14px] border border-[#0B2558]/10">
                        <p class="text-lg font-medium">Không tìm thấy thiết kế!</p>
                        <p class="text-sm mt-1">Vui lòng thử lại với từ khoá khác.</p>
                    </div>
                <?php endif; ?>
            </div>
            <?php if($totalPages > 1): ?>
            <nav class="flex items-center justify-center gap-1.5 mt-9">
                <?php for($i=1; $i<=$totalPages; $i++): ?>
                    <a href="<?= buildFilterUrl(['page' => $i]) ?>"
                       class="min-w-[36px] h-[36px] px-3 rounded-[9px] border-[1.5px] 
                       <?= $i==$page 
                           ? 'bg-[#0B2558] border-[#0B2558] text-white' 
                           : 'bg-white border-[#0B2558]/[0.18] text-[#374368] hover:border-[#1a52b5] hover:text-[#1a52b5] hover:bg-[#e8edf8]' 
                       ?> 
                       text-[13.5px] font-medium flex items-center justify-center transition-all">
                       <?= $i ?>
                    </a>
                <?php endfor; ?>
            </nav>
            <?php endif; ?>
        </main>
    </div>
</div>
<?php
$_modalProducts = array_map(function($p) {
    return [
        'id'         => (int)$p['id'],
        'name'       => $p['name'],
        'slug'       => $p['slug'],
        'cate_name'  => $p['cate_name'] ?? '',
        'brand_name' => $p['brand_name'] ?? '',
        'images'     => array_values(array_filter((array)($p['images'] ?? []))),
        'thumb'      => $p['thumb'] ?? '',
    ];
}, $data ?? []);
?>
<script>const DESIGN_PRODUCTS = <?= json_encode(array_values($_modalProducts), JSON_UNESCAPED_UNICODE | JSON_HEX_TAG) ?>;</script><link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css">

<!-- ===== DESIGN POPUP MODAL ===== -->
<style>
#designModal { transition: opacity 0.22s ease; }
/* Custom zoom via CSS transform */
.designModalSwiper .swiper-slide { overflow:hidden !important; }
.designModalSwiper .swiper-slide > img { position:absolute; top:0; left:0; width:100%; height:100%; object-fit:contain; border-radius:10px; cursor:zoom-in; user-select:none; -webkit-user-drag:none; transform-origin:center center; transition:transform 0.18s ease; }
.designModalSwiper .swiper-slide.is-zoomed > img { cursor:grab; transition:none; }
.designModalSwiper .swiper-slide.is-zoomed > img.is-panning { cursor:grabbing; }
/* nav buttons */
.designModalSwiper .swiper-button-prev,
.designModalSwiper .swiper-button-next { color:#fff; --swiper-navigation-size:22px; background:rgba(255,255,255,0.1); width:40px; height:40px; border-radius:50%; }
.designModalSwiper .swiper-button-prev:hover,
.designModalSwiper .swiper-button-next:hover { background:rgba(255,255,255,0.22); }
.designModalSwiper .swiper-pagination-bullet { background:#fff; opacity:0.5; }
.designModalSwiper .swiper-pagination-bullet-active { opacity:1; }
/* zoom controls */
.zoom-ctrl-btn { display:flex; align-items:center; justify-content:center; width:32px; height:32px; border-radius:8px; border:1px solid rgba(255,255,255,0.18); background:rgba(255,255,255,0.10); color:#fff; cursor:pointer; font-size:18px; font-weight:600; line-height:1; transition:background .15s; }
.zoom-ctrl-btn:hover { background:rgba(255,255,255,0.22); }
#zoomLevelBadge { font-size:12px; font-weight:600; color:rgba(255,255,255,0.7); min-width:38px; text-align:center; }
</style>
<div id="designModal"
     style="display:none;opacity:0;"
     class="fixed inset-0 z-[500] flex flex-col"
     role="dialog" aria-modal="true" aria-labelledby="modalProductName">
  <!-- Backdrop -->
  <div class="absolute inset-0" style="background:rgba(5,10,25,0.90);backdrop-filter:blur(6px);" onclick="closeDesignModal()"></div>
  <!-- Content -->
  <div class="relative z-10 flex flex-col h-full">
    <!-- Top bar -->
    <div class="flex items-center justify-between px-4 md:px-6 py-3 md:py-4 shrink-0" style="border-bottom:1px solid rgba(255,255,255,0.10);">
      <div class="min-w-0 flex-1">
        <p id="modalCateBadge" class="text-[10.5px] font-semibold tracking-widest uppercase mb-0.5" style="color:rgba(255,255,255,0.45);"></p>
        <h2 id="modalProductName" class="text-[18px] md:text-[22px] font-bold text-white leading-tight" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap;max-width:58vw;"></h2>
      </div>
      <div class="flex items-center gap-2 md:gap-3 ml-4 shrink-0">
        <!-- Zoom controls -->
        <div class="flex items-center gap-1" title="Thu nhỏ / Phóng to (cuộn chuột, chụm tay, double-click)">
          <button class="zoom-ctrl-btn" onclick="dZoomOut()" title="Thu nhỏ (-)">&#8722;</button>
          <span id="zoomLevelBadge">100%</span>
          <button class="zoom-ctrl-btn" onclick="dZoomIn()" title="Phóng to (+)">&#43;</button>
          <button class="zoom-ctrl-btn" onclick="dZoomReset()" title="Đặt lại" style="width:auto;padding:0 8px;font-size:11px;font-weight:700;">reset</button>
        </div>
        <a id="modalDetailLink" href="#"
           class="hidden items-center gap-1.5 px-3.5 py-[7px] rounded-[9px] text-white text-[12.5px] font-medium transition-colors no-underline"
           style="background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);">
          <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
          Chi tiết
        </a>
        <button onclick="closeDesignModal()"
                class="w-9 h-9 flex items-center justify-center rounded-full text-white transition-all"
                style="background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);"
                onmouseover="this.style.background='rgba(220,38,38,0.75)'" onmouseout="this.style.background='rgba(255,255,255,0.10)'"
                title="Đóng (ESC)">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
        </button>
      </div>
    </div>
    <!-- Swiper -->
    <div class="flex-1 flex items-center justify-center p-3 md:p-6 min-h-0">
      <div class="designModalSwiper swiper w-full" style="max-width:900px;height:calc(100vh - 156px);max-height:calc(100vh - 156px);">
        <div class="swiper-wrapper" id="modalSwiperWrapper"></div>
        <div class="swiper-button-prev"></div>
        <div class="swiper-button-next"></div>
        <div class="swiper-pagination" style="bottom:0;"></div>
      </div>
    </div>
    <!-- Mobile: link to detail -->
    <div class="sm:hidden shrink-0 px-4 pb-4">
      <a id="modalDetailLinkMobile" href="#"
         class="flex items-center justify-center gap-2 w-full py-2.5 rounded-xl text-white text-[13px] font-medium transition-all no-underline"
         style="background:rgba(255,255,255,0.10);border:1px solid rgba(255,255,255,0.18);">
        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/><polyline points="15 3 21 3 21 9"/><line x1="10" y1="14" x2="21" y2="3"/></svg>
        Xem trang chi tiết
      </a>
    </div>
  </div>
</div>
<!-- Category filter + Mobile Filter JS -->
<script>
document.addEventListener('click', function (e) {
    const btn = e.target.closest('.cat-filter-toggle');
    if (!btn) return;
    e.preventDefault();
    e.stopPropagation();
    const group = btn.closest('.cat-filter-group');
    if (!group) return;
    const children = group.querySelector('.cat-filter-children');
    if (!children) return;
    const expanded = !group.classList.contains('is-expanded');
    group.classList.toggle('is-expanded', expanded);
    btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
    // Dùng style.display thay vì hidden attribute
    // vì Tailwind class 'flex' sẽ override [hidden]{display:none}
    children.style.display = expanded ? 'flex' : 'none';
    children.style.flexDirection = 'column';
});
function openMobileFilter() {
    const overlay = document.getElementById('mobileFilterOverlay');
    const drawer = document.getElementById('mobileFilterDrawer');
    overlay.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    requestAnimationFrame(() => {
        overlay.classList.remove('opacity-0');
        overlay.classList.add('opacity-100');
        drawer.classList.remove('translate-y-full');
        drawer.classList.add('translate-y-0');
    });
}
function closeMobileFilter() {
    const overlay = document.getElementById('mobileFilterOverlay');
    const drawer = document.getElementById('mobileFilterDrawer');
    overlay.classList.remove('opacity-100');
    overlay.classList.add('opacity-0');
    drawer.classList.remove('translate-y-0');
    drawer.classList.add('translate-y-full');
    setTimeout(() => {
        overlay.classList.add('hidden');
        document.body.style.overflow = '';
    }, 300);
}
</script>

<script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js"></script>
<script>
// ===== Design Popup =====
var _dSwiper = null;
function openDesignModal(productId) {
    var product = null;
    for (var i = 0; i < DESIGN_PRODUCTS.length; i++) {
        if (DESIGN_PRODUCTS[i].id == productId) { product = DESIGN_PRODUCTS[i]; break; }
    }
    if (!product) return;
    document.getElementById('modalProductName').textContent = product.name;
    document.getElementById('modalCateBadge').textContent = product.cate_name || '';
    var detailUrl = '/thiet-ke/' + product.slug;
    document.getElementById('modalDetailLink').href = detailUrl;
    document.getElementById('modalDetailLinkMobile').href = detailUrl;
    // Destroy previous Swiper synchronously before rebuilding slides
    if (_dSwiper) { try { _dSwiper.destroy(true, false); } catch(e){} _dSwiper = null; }

    // Build slides — plain img
    var wrapper = document.getElementById('modalSwiperWrapper');
    wrapper.innerHTML = '';
    var imgs = (product.images && product.images.length > 0) ? product.images : (product.thumb ? [product.thumb] : []);
    imgs.forEach(function(src) {
        if (!src) return;
        var slide = document.createElement('div');
        slide.className = 'swiper-slide';
        var img = document.createElement('img');
        img.src = src;
        img.alt = product.name;
        img.draggable = false;
        slide.appendChild(img);
        wrapper.appendChild(slide);
    });

    // Show modal first so container has dimensions
    var modal = document.getElementById('designModal');
    modal.style.display = 'flex';
    modal.style.flexDirection = 'column';
    document.body.style.overflow = 'hidden';

    // Init Swiper AFTER layout
    requestAnimationFrame(function() {
        _dSwiper = new Swiper('.designModalSwiper', {
            loop: imgs.length > 1,
            navigation: { nextEl: '.designModalSwiper .swiper-button-next', prevEl: '.designModalSwiper .swiper-button-prev' },
            pagination: { el: '.designModalSwiper .swiper-pagination', clickable: true },
            keyboard: { enabled: true },
            allowTouchMove: true,
            on: { slideChange: function() { _dzReset(); } },
        });
        _dzReset();
        requestAnimationFrame(function() { modal.style.opacity = '1'; });
    });
}

// ── Custom zoom state ──
var _dz = { scale:1, panX:0, panY:0, min:1, max:4, dragging:false, lx:0, ly:0, pinching:false, pd:0, ps:1 };
function _dzImg() {
    if (!_dSwiper) return null;
    // loop mode duplicates slides; find the active one by class
    var active = document.querySelector('.designModalSwiper .swiper-slide-active');
    return active ? active.querySelector('img') : null;
}
function _dzApply(noTransition) {
    var img = _dzImg();
    if (!img) return;
    var slide = img.parentElement;
    if (noTransition) img.style.transition = 'none';
    else img.style.transition = '';
    img.style.transform = 'translate('+_dz.panX+'px,'+_dz.panY+'px) scale('+_dz.scale+')';
    if (slide) slide.classList.toggle('is-zoomed', _dz.scale > 1.01);
    if (_dSwiper) _dSwiper.allowTouchMove = _dz.scale <= 1.01;
    _updateZoomBadge(_dz.scale);
}
function _dzSetScale(s, noTransition) {
    _dz.scale = Math.min(_dz.max, Math.max(_dz.min, s));
    if (_dz.scale <= 1.01) { _dz.panX = 0; _dz.panY = 0; _dz.scale = 1; }
    _dzApply(noTransition);
}
function _dzReset() { _dz.scale=1; _dz.panX=0; _dz.panY=0; _dzApply(false); _updateZoomBadge(1); }
function dZoomIn()    { _dzSetScale(_dz.scale + 0.5); }
function dZoomOut()   { _dzSetScale(_dz.scale - 0.5); }
function dZoomReset() { _dzReset(); }
// Attach zoom events once on the swiper element
(function() {
    var el = document.querySelector('.designModalSwiper');
    if (!el) return;
    // Scroll wheel
    el.addEventListener('wheel', function(e) {
        if (document.getElementById('designModal').style.display === 'none') return;
        e.preventDefault();
        _dzSetScale(_dz.scale + (e.deltaY < 0 ? 0.25 : -0.25), true);
    }, { passive: false });
    // Double-click toggle
    el.addEventListener('dblclick', function(e) {
        if (document.getElementById('designModal').style.display === 'none') return;
        _dz.scale > 1.01 ? _dzReset() : _dzSetScale(2);
    });
    // Mouse drag pan
    el.addEventListener('mousedown', function(e) {
        if (_dz.scale <= 1.01) return;
        _dz.dragging = true; _dz.lx = e.clientX; _dz.ly = e.clientY;
        var img = _dzImg(); if (img) img.classList.add('is-panning');
        e.preventDefault();
    });
    document.addEventListener('mousemove', function(e) {
        if (!_dz.dragging) return;
        _dz.panX += e.clientX - _dz.lx; _dz.panY += e.clientY - _dz.ly;
        _dz.lx = e.clientX; _dz.ly = e.clientY;
        var img = _dzImg();
        if (img) img.style.transform = 'translate('+_dz.panX+'px,'+_dz.panY+'px) scale('+_dz.scale+')';
    });
    document.addEventListener('mouseup', function() {
        if (!_dz.dragging) return;
        _dz.dragging = false;
        var img = _dzImg(); if (img) img.classList.remove('is-panning');
    });
    // Touch pinch + pan
    el.addEventListener('touchstart', function(e) {
        if (document.getElementById('designModal').style.display === 'none') return;
        if (e.touches.length === 2) {
            _dz.pinching = true;
            _dz.pd = Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY);
            _dz.ps = _dz.scale;
            e.preventDefault();
        } else if (e.touches.length === 1 && _dz.scale > 1.01) {
            _dz.dragging = true; _dz.lx = e.touches[0].clientX; _dz.ly = e.touches[0].clientY;
        }
    }, { passive: false });
    el.addEventListener('touchmove', function(e) {
        if (document.getElementById('designModal').style.display === 'none') return;
        if (_dz.pinching && e.touches.length === 2) {
            var d = Math.hypot(e.touches[0].clientX-e.touches[1].clientX, e.touches[0].clientY-e.touches[1].clientY);
            _dzSetScale(_dz.ps * (d / _dz.pd), true);
            e.preventDefault();
        } else if (_dz.dragging && e.touches.length === 1) {
            _dz.panX += e.touches[0].clientX - _dz.lx; _dz.panY += e.touches[0].clientY - _dz.ly;
            _dz.lx = e.touches[0].clientX; _dz.ly = e.touches[0].clientY;
            var img = _dzImg();
            if (img) img.style.transform = 'translate('+_dz.panX+'px,'+_dz.panY+'px) scale('+_dz.scale+')';
            e.preventDefault();
        }
    }, { passive: false });
    el.addEventListener('touchend', function(e) {
        if (e.touches.length < 2) _dz.pinching = false;
        if (e.touches.length === 0) _dz.dragging = false;
    });
})();
function closeDesignModal() {
    var modal = document.getElementById('designModal');
    modal.style.opacity = '0';
    setTimeout(function() {
        modal.style.display = 'none';
        document.body.style.overflow = '';
        // Don't destroy Swiper here — it corrupts the container.
        // Swiper is destroyed in openDesignModal before re-init.
    }, 220);
}
function _updateZoomBadge(scale) {
    var pct = Math.round((scale || 1) * 100);
    var el = document.getElementById('zoomLevelBadge');
    if (el) el.textContent = pct + '%';
}
// Event delegation: open popup
document.addEventListener('click', function(e) {
    var trigger = e.target.closest('.open-design-popup');
    if (trigger) {
        e.preventDefault();
        openDesignModal(trigger.dataset.productId);
    }
});
// Close on ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') closeDesignModal();
});
</script>
<?php require 'front/footer.php'; ?>