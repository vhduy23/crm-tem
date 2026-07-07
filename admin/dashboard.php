<?php
require 'auth.php';
checkLogin();

require '../lib/db.php';
require 'partials/header.php';

$userName = htmlspecialchars($_SESSION['user']['name'] ?? 'Admin');

// ===== 1. SUMMARY STATS =====
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$productsThisMonth = $pdo->query("SELECT COUNT(*) FROM products WHERE MONTH(created_at) = MONTH(CURRENT_DATE()) AND YEAR(created_at) = YEAR(CURRENT_DATE())")->fetchColumn();

// Sparkline logic (last 7 days counts for products)
$sparkData = [];
for($i=6; $i>=0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $cnt = $pdo->query("SELECT COUNT(*) FROM products WHERE DATE(created_at) = '$d'")->fetchColumn();
    $sparkData[] = (int)$cnt;
}

// ===== 2. BAR CHART DATA (Designs by category) =====
$barData = $pdo->query("SELECT c.name, COUNT(p.id) as cnt FROM categories c JOIN products p ON c.id = p.category_id GROUP BY c.id ORDER BY cnt DESC LIMIT 4")->fetchAll(PDO::FETCH_ASSOC);
$barLabels = array_column($barData, 'name');
$barValues = array_column($barData, 'cnt');

// ===== 3. DONUT CHART DATA (Users by role) =====
$donutData = $pdo->query("SELECT role_id, COUNT(*) as cnt FROM users GROUP BY role_id")->fetchAll(PDO::FETCH_ASSOC);
$roleNames = [0 => 'Super Admin', 1 => 'Admin', 9 => 'Customer'];
$donutLabels = [];
$donutValues = [];
foreach ($donutData as $row) {
    $rId = (int)$row['role_id'];
    $donutLabels[] = $roleNames[$rId] ?? 'Khác';
    $donutValues[] = (int)$row['cnt'];
}
$donutTotal = array_sum($donutValues) ?: 1;

// ===== 4. PROGRESS BARS DATA (Designs by status) =====
$statusData = $pdo->query("SELECT status, COUNT(*) as cnt FROM products GROUP BY status")->fetchAll(PDO::FETCH_ASSOC);
$statusMap = [0 => 0, 1 => 0, 2 => 0];
foreach($statusData as $row) {
    $statusMap[(int)$row['status']] = (int)$row['cnt'];
}
$maxStatus = array_sum($statusMap) ?: 1;
$pct0 = round(($statusMap[0] / $maxStatus) * 100);
$pct1 = round(($statusMap[1] / $maxStatus) * 100);
$pct2 = round(($statusMap[2] / $maxStatus) * 100);

// ===== 5. LATEST PRODUCTS (List) =====
$latest = $pdo->query("
    SELECT p.id, p.name, p.status, 
        (SELECT image_path FROM product_images WHERE product_id = p.id ORDER BY sort_order ASC LIMIT 1) AS image_path
    FROM products p
    ORDER BY p.id DESC
    LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

?>
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<!-- HEADER -->
<div class="flex justify-between items-center mb-6">
    <h1 class="text-2xl font-bold text-gray-800">Tổng quan Hệ thống</h1>
    <!-- <span class="text-sm text-gray-500">Hôm nay, <?= date('d/m/Y') ?></span> -->
</div>

<div class="grid grid-cols-1 xl:grid-cols-12 gap-6 mb-8">
    
    <!-- LEFT MAIN COLUMN (8 Cols) -->
    <div class="xl:col-span-8 flex flex-col gap-6">
        
        <!-- TOP ROW: 3 SPARKLINE CARDS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
            <!-- Card 1 -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium mb-1">Tổng thiết kế</p>
                    <p class="text-3xl font-bold text-red-500"><?= number_format($totalProducts) ?></p>
                </div>
                <div class="h-16 mt-4">
                    <canvas id="spark1"></canvas>
                </div>
            </div>
            <!-- Card 2 -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium mb-1">Tổng người dùng</p>
                    <p class="text-3xl font-bold text-blue-500"><?= number_format($totalUsers) ?></p>
                </div>
                <div class="h-16 mt-4">
                    <canvas id="spark2"></canvas>
                </div>
            </div>
            <!-- Card 3 -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 flex flex-col justify-between">
                <div>
                    <p class="text-gray-500 text-sm font-medium mb-1">Thiết kế tháng này</p>
                    <p class="text-3xl font-bold text-amber-500"><?= number_format($productsThisMonth) ?></p>
                </div>
                <div class="h-16 mt-4">
                    <canvas id="spark3"></canvas>
                </div>
            </div>
        </div>

        <!-- MIDDLE ROW: LINE CHART -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5">
            <div class="flex justify-between items-center mb-4">
                <h2 class="text-lg font-bold text-gray-800">Tăng trưởng</h2>
                <select id="chartFilter" class="border border-gray-200 rounded-lg px-2 py-1 text-sm outline-none">
                    <option value="week">Tuần</option>
                    <option value="month" selected>Tháng</option>
                    <option value="quarter">Quý</option>
                </select>
            </div>
            <div class="h-64 w-full">
                <canvas id="dashboardChart"></canvas>
            </div>
        </div>

        <!-- BOTTOM ROW: BAR & DONUT -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Bar Chart -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100">
                <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Top Danh mục</h2>
                <div class="h-48 w-full flex justify-center">
                    <canvas id="barChart"></canvas>
                </div>
            </div>
            <!-- Donut Chart -->
            <div class="bg-white rounded-xl p-5 shadow-sm border border-gray-100 relative">
                <h2 class="text-lg font-bold text-gray-800 mb-4 text-center">Tỉ lệ Người dùng</h2>
                <div class="h-40 w-full flex justify-center">
                    <canvas id="donutChart"></canvas>
                </div>
                <div class="absolute inset-0 flex flex-col items-center justify-center pointer-events-none" style="margin-top: 2rem;">
                    <span class="text-2xl font-bold text-gray-600"><?= count($donutValues) ? round($donutValues[0]/$donutTotal*100) : 0 ?>%</span>
                </div>
                <div class="mt-4 text-center">
                    <span class="bg-red-500 text-white text-xs px-3 py-1 rounded-full uppercase font-bold">Quản trị viên</span>
                </div>
            </div>
        </div>

    </div>

    <!-- RIGHT SIDEBAR COLUMN (4 Cols) -->
    <div class="xl:col-span-4 flex flex-col gap-6">
        
        <!-- VERTICAL PROGRESS BARS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6">
            <h2 class="text-lg font-bold text-gray-800 mb-6 text-center">Trạng thái Thiết kế</h2>
            <div class="flex justify-around items-end h-40 mb-4">
                <!-- Bar 1 -->
                <div class="flex flex-col items-center gap-2 h-full">
                    <div class="w-4 bg-gray-100 rounded-full h-full flex flex-col justify-end overflow-hidden">
                        <div class="w-full bg-blue-500 rounded-full" style="height: <?= $pct2 ?>%"></div>
                    </div>
                    <span class="text-xs text-gray-500 font-medium"><?= $pct2 ?>%</span>
                    <span class="text-[10px] text-gray-400">Công khai</span>
                </div>
                <!-- Bar 2 -->
                <div class="flex flex-col items-center gap-2 h-full">
                    <div class="w-4 bg-gray-100 rounded-full h-full flex flex-col justify-end overflow-hidden">
                        <div class="w-full bg-red-500 rounded-full" style="height: <?= $pct1 ?>%"></div>
                    </div>
                    <span class="text-xs text-gray-500 font-medium"><?= $pct1 ?>%</span>
                    <span class="text-[10px] text-gray-400">Nội bộ</span>
                </div>
                <!-- Bar 3 -->
                <div class="flex flex-col items-center gap-2 h-full">
                    <div class="w-4 bg-gray-100 rounded-full h-full flex flex-col justify-end overflow-hidden">
                        <div class="w-full bg-amber-500 rounded-full" style="height: <?= $pct0 ?>%"></div>
                    </div>
                    <span class="text-xs text-gray-500 font-medium"><?= $pct0 ?>%</span>
                    <span class="text-[10px] text-gray-400">Khóa</span>
                </div>
            </div>
            <p class="text-xs text-gray-400 text-center leading-relaxed">
                Tỉ lệ phân bố hiển thị của tất cả thiết kế trong hệ thống.
            </p>
        </div>

        <!-- LIST ITEMS -->
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-5 flex-grow">
            <h2 class="text-lg font-bold text-gray-800 mb-4">Thiết kế mới nhất</h2>
            <div class="flex flex-col gap-3">
                <?php foreach($latest as $l): ?>
                <a href="/admin/products/edit.php?id=<?= $l['id'] ?>" class="flex items-center justify-between group hover:bg-gray-50 p-2 -mx-2 rounded-lg transition-colors">
                    <div class="flex items-center gap-3">
                        <?php if($l['image_path']): ?>
                            <img src="<?= $l['image_path'] ?>" class="w-10 h-10 rounded-md object-cover">
                        <?php else: ?>
                            <div class="w-10 h-10 rounded-md bg-gray-200 flex items-center justify-center">
                                <i class="fa-solid fa-image text-gray-400 text-xs"></i>
                            </div>
                        <?php endif; ?>
                        <div>
                            <p class="text-sm font-medium text-gray-700 group-hover:text-blue-600 truncate w-32 sm:w-48 xl:w-32"><?= htmlspecialchars($l['name']) ?></p>
                            <p class="text-[10px] text-gray-400">Mã: #<?= $l['id'] ?></p>
                        </div>
                    </div>
                    <div>
                        <?php if($l['status'] == 2): ?>
                            <span class="bg-blue-500 text-white text-[10px] px-2 py-0.5 rounded-sm font-bold">2</span>
                        <?php elseif($l['status'] == 1): ?>
                            <span class="bg-red-500 text-white text-[10px] px-2 py-0.5 rounded-sm font-bold">1</span>
                        <?php else: ?>
                            <span class="bg-gray-400 text-white text-[10px] px-2 py-0.5 rounded-sm font-bold">0</span>
                        <?php endif; ?>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>

    </div>
</div>

<script>
// --- SPARKLINE COMMON ---
const sparkOptions = {
    responsive: true,
    maintainAspectRatio: false,
    plugins: { legend: { display: false }, tooltip: { enabled: false } },
    scales: { x: { display: false }, y: { display: false, min: 0 } },
    layout: { padding: 0 }
};
const sparkLabels = ['1','2','3','4','5','6','7'];
const sparkDataArr = <?= json_encode($sparkData) ?>;

// Spark 1 (Red)
new Chart(document.getElementById('spark1').getContext('2d'), {
    type: 'line',
    data: {
        labels: sparkLabels,
        datasets: [{ data: sparkDataArr, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.2)', borderWidth: 2, pointRadius: 0, fill: true, tension: 0.4 }]
    },
    options: sparkOptions
});
// Spark 2 (Blue)
new Chart(document.getElementById('spark2').getContext('2d'), {
    type: 'line',
    data: {
        labels: sparkLabels,
        datasets: [{ data: [3,4,3,6,5,8,7], borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.2)', borderWidth: 2, pointRadius: 0, fill: true, tension: 0.4 }]
    },
    options: sparkOptions
});
// Spark 3 (Yellow)
new Chart(document.getElementById('spark3').getContext('2d'), {
    type: 'line',
    data: {
        labels: sparkLabels,
        datasets: [{ data: [1,2,1,4,2,3,4], borderColor: '#f59e0b', backgroundColor: 'rgba(245,158,11,0.2)', borderWidth: 2, pointRadius: 0, fill: true, tension: 0.4 }]
    },
    options: sparkOptions
});

// --- MAIN LINE CHART (AJAX) ---
let dashboardChart = null;
function fetchChartData(filter) {
    fetch(`/admin/api/dashboard_chart.php?filter=${filter}`)
        .then(res => res.json())
        .then(data => {
            const ctx = document.getElementById('dashboardChart').getContext('2d');
            if (dashboardChart) dashboardChart.destroy();
            dashboardChart = new Chart(ctx, {
                type: 'line',
                data: { labels: data.labels, datasets: data.datasets },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    interaction: { mode: 'index', intersect: false },
                    plugins: { legend: { position: 'top' } },
                    scales: { y: { beginAtZero: true, ticks: { stepSize: 1 } } }
                }
            });
        });
}
document.getElementById('chartFilter').addEventListener('change', e => fetchChartData(e.target.value));
fetchChartData('month');

// --- BAR CHART ---
new Chart(document.getElementById('barChart').getContext('2d'), {
    type: 'bar',
    data: {
        labels: <?= json_encode($barLabels) ?>,
        datasets: [{
            data: <?= json_encode($barValues) ?>,
            backgroundColor: ['#0ea5e9', '#ef4444', '#f59e0b', '#8b5cf6'],
            borderRadius: 4,
            barThickness: 20
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false } },
        scales: {
            x: { grid: { display: false } },
            y: { display: true, beginAtZero: true, ticks: { stepSize: 1 } }
        }
    }
});

// --- DONUT CHART ---
new Chart(document.getElementById('donutChart').getContext('2d'), {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($donutLabels) ?>,
        datasets: [{
            data: <?= json_encode($donutValues) ?>,
            backgroundColor: ['#ef4444', '#e5e7eb', '#3b82f6'],
            borderWidth: 0,
            cutout: '75%'
        }]
    },
    options: {
        responsive: true,
        maintainAspectRatio: false,
        plugins: { legend: { display: false }, tooltip: { enabled: true } }
    }
});
</script>

<?php require 'partials/footer.php'; ?>
