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
// ===== HELPER: build filter URL =====
function buildFilterUrl($overrides = []) {
    global $keyword, $category_id, $brand_id;
    $p = [
        'q'     => $overrides['q']     ?? $keyword,
        'cat'   => $overrides['cat']   ?? $category_id,
        'brand' => $overrides['brand'] ?? $brand_id,
        'page'  => $overrides['page']  ?? null,
    ];
    $parts = [];
    if ($p['q'])     $parts[] = 'q=' . urlencode($p['q']);
    if ($p['cat'])   $parts[] = 'cat=' . (int)$p['cat'];
    if ($p['brand']) $parts[] = 'brand=' . (int)$p['brand'];
    if ($p['page'] && $p['page'] > 1) $parts[] = 'page=' . (int)$p['page'];
    return '?' . implode('&', $parts);
}
// ===== WHERE =====
$conditions = [];
$params = [];
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
    ORDER BY p.id DESC
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
?>
<div class="bg-[#F7F8FB] min-h-screen text-[#374368] font-sans ">
    <div class="bg-white border-b border-[#0B2558]/10 px-6 py-2.5">
        <div class="max-w-[1340px] mx-auto flex items-center gap-2 text-[12.5px] text-[#8892AA]">
            <a href="/" class="text-[#1a52b5] hover:underline">Trang chủ</a>
            <span class="text-[#0b255861]/[0.18] text-[13px] font-bold">›</span>
            <span class="text-[#374368] font-medium">Bộ sưu tập thiết kế</span>
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
                    <select class="px-3 py-2 rounded-lg border-[1.5px] border-[#0B2558]/[0.18] text-[13px] text-[#374368] bg-white outline-none focus:border-[#1a52b5] transition-colors cursor-pointer">
                        <option>Mới nhất</option>
                        <option>Cũ nhất</option>
                        <option>Tên A–Z</option>
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
                            <a href="/thiet-ke/<?= $p['slug'] ?>" class="block aspect-[4/3] bg-[#EFF1F7]">
                                <img src="<?= $p['thumb'] ?>" 
                                     class="w-full h-full object-cover group-hover:scale-[1.04] transition-transform duration-[380ms] ease-[cubic-bezier(0.4,0,0.2,1)]" 
                                     loading="lazy" 
                                     alt="<?= htmlspecialchars($p['name']) ?>"/>
                            </a>
                            <?php if(!empty($p['cate_name'])): ?>
                            <span class="absolute top-2.5 left-2.5 text-[10.5px] font-semibold tracking-wide px-2.5 py-[3px] rounded-full backdrop-blur-[4px] bg-[#0B2558]/72 text-white pointer-events-none tag-<?= $p['cate_id'] ?>">
                                <?= htmlspecialchars($p['cate_name']) ?>
                            </span>
                            <?php endif; ?>
                        </div>
                        <div class="p-3.5 md:p-4">
                            <a href="/thiet-ke/<?= $p['slug'] ?>">
                                <!-- <p class="text-[11px] font-semibold tracking-wider uppercase text-[#8892AA] mb-1.5">Sản phẩm</p> -->
                                <h3 class="text-[15px] font-semibold text-[#0B2558] mb-2.5 leading-snug whitespace-nowrap overflow-hidden text-ellipsis">
                                    <?= htmlspecialchars($p['name']) ?>
                                </h3>
                            </a>
                            
                            <div class="grid grid-cols-1 gap-2 mt-3 md:grid-cols-2 md:flex">
                                <a href="/thiet-ke/<?= $p['slug'] ?>" class="flex-1 px-3 py-2 bg-[#0B2558] text-white rounded-lg text-[13px] font-semibold text-center hover:bg-[#163580] transition-colors">
                                    Xem thiết kế
                                </a>
                                
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
<?php require 'front/footer.php'; ?>