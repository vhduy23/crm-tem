<?php
global $pdo;
require 'front/header.php';
?>
<div class="max-w-6xl mx-auto p-4">
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
    <?php foreach($products as $p): ?>
        <?php
            $isAssigned = !empty($userAssignedProducts) && in_array($p['id'], $userAssignedProducts);
            $isInternal = ($p['status'] == 1 || $isAssigned);
            $borderClass = $isInternal ? 'border-2 border-[#0B2558]' : 'border-2 border-transparent';
        ?>
        <div class="bg-white p-3 rounded shadow hover:shadow-lg transition <?= $borderClass ?>">
            <a href="/thiet-ke/<?= $p['slug'] ?>" class="relative block">
                <img loading="lazy" src="<?= $p['thumb'] ?>"
                    class="w-full h-50 object-cover mb-2 rounded">
                <?php 
                    $isCustomer = (isset($roleId) && $roleId == 9);
                    $isAssigned = isset($userAssignedProducts) && in_array($p['id'], $userAssignedProducts);
                    $starColor = ($isCustomer && $isAssigned) ? 'text-yellow-500' : 'text-[#0B2558]';
                ?>
                <span class="absolute bottom-2.5 left-2.5 text-[14px] font-semibold tracking-wide px-2.5 py-[3px] rounded-full backdrop-blur-[4px] <?= $starColor ?> pointer-events-none">
                    <?= ($p['status'] == 1 || $isAssigned) ? '<i class="fa-solid fa-star drop-shadow-sm"></i>' : '' ?>
                </span>
            </a>
            <div class="flex items-center justify-between">
                <a href="/thiet-ke/<?= $p['slug'] ?>">
                    <h3 class="text-center font-medium">
                        <?= htmlspecialchars($p['name']) ?>
                    </h3>
                </a>
                <button
                    class="add-print text-black py-1 rounded z-99 text-xl"
                    data-id="<?= $p['id'] ?>"
                    data-name="<?= htmlspecialchars($p['name']) ?>"
                    data-img="<?= $p['thumb'] ?>"
                >
                <i class="fa-solid fa-folder-plus"></i>
                </button>
            </div>
        </div>
    <?php endforeach; ?>
    </div>
</div>
<?php include 'front/footer.php'; ?>
