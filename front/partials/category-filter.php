<?php
/**
 * @var PDO $pdo
 * @var array $categoryTree
 * @var int $category_id
 * @var int $totalPro
 * @var string $filterPrefix
 */
$filterPrefix = $filterPrefix ?? 'desktop';
?>
<div class="flex flex-col gap-1 cat-filter-list" data-filter-prefix="<?= htmlspecialchars($filterPrefix) ?>">
    <a href="<?= buildFilterUrl(['cat' => 0]) ?>"
       class="cat-filter-link flex items-center justify-between p-[9px_12px] rounded-[9px] transition-colors border-[1.5px] no-underline
       <?= $category_id == 0 ? 'is-active bg-[#e8edf8] border-[#1a52b5]/25' : 'border-transparent hover:bg-[#e8edf8]' ?>">
        <div class="flex items-center gap-2.5 min-w-0">
            <span class="w-2.5 h-2.5 rounded-full bg-[#1558c0] shrink-0"></span>
            <span class="text-[13.5px] font-medium text-[#0B2558]">Tất cả</span>
        </div>
        <span class="cat-filter-count"><?= $totalPro ?></span>
    </a>

    <?php foreach ($categoryTree as $parent):
        $hasChildren = !empty($parent['children']);
        $parentActive = $category_id === (int) $parent['id'];
        $parentCount = countCategoryProducts($pdo, (int) $parent['id']);
        $isExpanded = $hasChildren && categoryGroupIsExpanded($parent, $category_id);
    ?>
        <?php if (!$hasChildren): ?>
        <a href="<?= buildFilterUrl(['cat' => $parent['id']]) ?>"
           class="cat-filter-link flex items-center justify-between p-[9px_12px] rounded-[9px] transition-colors border-[1.5px] no-underline
           <?= $parentActive ? 'is-active bg-[#e8edf8] border-[#1a52b5]/25' : 'border-transparent hover:bg-[#e8edf8]' ?>">
            <div class="flex items-center gap-2.5 min-w-0">
                <span class="w-2.5 h-2.5 rounded-full tag-<?= (int) $parent['id'] ?> shrink-0"></span>
                <span class="text-[13.5px] font-semibold text-[#0B2558] truncate"><?= htmlspecialchars($parent['name']) ?></span>
            </div>
            <span class="cat-filter-count <?= $parentActive ? 'is-active' : '' ?>"><?= $parentCount ?></span>
        </a>
        <?php else: ?>
        <div class="cat-filter-group <?= $isExpanded ? 'is-expanded' : '' ?>"
             data-group-id="<?= htmlspecialchars($filterPrefix) ?>-<?= (int) $parent['id'] ?>">
             <div class="flex items-center p-[9px_12px] rounded-[9px] transition-colors border-[1.5px] no-underline
                        <?= $parentActive ? 'bg-[#e8edf8] border-[#1a52b5]/25' : 'border-transparent hover:bg-[#e8edf8]' ?>">
                <a href="<?= buildFilterUrl(['cat' => $parent['id']]) ?>"
                   class="cat-filter-link cat-filter-link--parent flex-1 flex items-center gap-2.5 min-w-0 no-underline">
                    <span class="w-2.5 h-2.5 rounded-full tag-<?= (int) $parent['id'] ?> shrink-0"></span>
                    <span class="text-[13.5px] font-semibold text-[#0B2558] truncate"><?= htmlspecialchars($parent['name']) ?></span>
                </a>                
                <button type="button"
                        class="cat-filter-toggle shrink-0 flex items-center justify-center w-6 h-6 rounded-full hover:bg-[#0B2558]/10 transition-colors"
                        aria-expanded="<?= $isExpanded ? 'true' : 'false' ?>"
                        aria-controls="<?= htmlspecialchars($filterPrefix) ?>-children-<?= (int) $parent['id'] ?>"
                        title="Mở / thu gọn danh mục con">
                    <svg class="cat-filter-chevron" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="9 18 15 12 9 6"/></svg>
                </button>
                <span class="cat-filter-count <?= $parentActive ? 'is-active' : '' ?>"><?= $parentCount ?></span>
            </div>
            <div id="<?= htmlspecialchars($filterPrefix) ?>-children-<?= (int) $parent['id'] ?>"
                 class="cat-filter-children flex flex-col gap-0.5 mt-0.5 mb-0.5"
                 <?= $isExpanded ? '' : 'style="display:none"' ?>>
                <?php foreach ($parent['children'] as $child):
                    $childActive = $category_id === (int) $child['id'];
                    $childCount = countCategoryProducts($pdo, (int) $child['id'], false);
                ?>
                <a href="<?= buildFilterUrl(['cat' => $child['id']]) ?>"
                   class="cat-filter-link cat-filter-link--child flex items-center justify-between p-[7px_12px_7px_34px] rounded-[9px] transition-colors border-[1.5px] no-underline
                   <?= $childActive ? 'is-active bg-[#e8edf8] border-[#1a52b5]/25' : 'border-transparent hover:bg-[#e8edf8]' ?>">
                    <div class="flex items-center gap-2.5 min-w-0">
                        <span class="w-1.5 h-1.5 rounded-full bg-[#8892AA] shrink-0"></span>
                        <span class="text-[13px] font-medium text-[#374368] truncate"><?= htmlspecialchars($child['name']) ?></span>
                    </div>
                    <span class="cat-filter-count <?= $childActive ? 'is-active' : '' ?>"><?= $childCount ?></span>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    <?php endforeach; ?>
</div>
