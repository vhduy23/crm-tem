<?php
require 'auth.php';
checkLogin();

require '../lib/db.php';
require 'partials/header.php';

$userName = htmlspecialchars($_SESSION['user']['name'] ?? 'Admin');


// ===== STATS =====
$totalProducts = $pdo->query("SELECT COUNT(*) FROM products")->fetchColumn();
$totalUsers    = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$totalBrands   = $pdo->query("SELECT COUNT(*) FROM brands")->fetchColumn();
$totalCats     = $pdo->query("SELECT COUNT(*) FROM categories")->fetchColumn();

// Removed LATEST PRODUCTS query as it is now replaced by a chart

$stats = [
    [
        'label'   => 'Thiết kế',
        'value'   => $totalProducts,
        'icon'    => 'fa-paintbrush',
        'href'    => '/admin/products',
        'bg'      => 'bg-blue-500/10',
        'icon_bg' => 'bg-blue-500',
        'text'    => 'text-blue-600',
    ],
    [
        'label'   => 'Người dùng',
        'value'   => $totalUsers,
        'icon'    => 'fa-circle-user',
        'href'    => '/admin/users',
        'bg'      => 'bg-emerald-500/10',
        'icon_bg' => 'bg-emerald-500',
        'text'    => 'text-emerald-600',
    ],
    [
        'label'   => 'Thương hiệu',
        'value'   => $totalBrands,
        'icon'    => 'fa-building-columns',
        'href'    => '/admin/brands',
        'bg'      => 'bg-violet-500/10',
        'icon_bg' => 'bg-violet-500',
        'text'    => 'text-violet-600',
    ],
    [
        'label'   => 'Danh mục',
        'value'   => $totalCats,
        'icon'    => 'fa-layer-group',
        'href'    => '/admin/categories',
        'bg'      => 'bg-amber-500/10',
        'icon_bg' => 'bg-amber-500',
        'text'    => 'text-amber-600',
    ],
];
?>

<!-- WELCOME BANNER -->
<div class="relative overflow-hidden rounded-2xl bg-gradient-to-br from-blue-600 via-blue-700 to-indigo-800 p-6 sm:p-8 mb-8 text-white shadow-lg">
    <div class="absolute -right-8 -top-8 h-40 w-40 rounded-full bg-white/10 blur-2xl"></div>
    <div class="absolute -bottom-12 -left-6 h-32 w-32 rounded-full bg-indigo-400/20 blur-xl"></div>
    <div class="relative flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
        <div>
            <p class="text-blue-200 text-sm font-medium mb-1">
                Hôm nay, <?= date('d/m/Y') ?>
            </p>
            <h1 class="text-2xl sm:text-3xl font-bold tracking-tight">
                Xin chào, <?= $userName ?> 
            </h1>
            <!-- <p class="text-blue-100 mt-2 text-sm sm:text-base max-w-lg">
                Tổng quan hệ thống quản lý thiết kế — theo dõi nhanh số liệu và thao tác thường dùng.
            </p> -->
        </div>
        <a href="/" target="_blank"
           class="inline-flex items-center gap-2 self-start sm:self-center bg-white/15 hover:bg-white/25 backdrop-blur-sm border border-white/20 px-5 py-2.5 rounded-xl text-sm font-medium transition-all duration-200">
            <i class="fa-solid fa-arrow-up-right-from-square text-xs"></i>
            Xem website
        </a>
    </div>
</div>

<!-- STATS -->
<div class="grid grid-cols-1 sm:grid-cols-2 xl:grid-cols-4 gap-5 mb-8">
    <?php foreach ($stats as $stat): ?>
    <a href="<?= $stat['href'] ?>"
       class="group relative bg-white rounded-2xl p-5 shadow-sm border border-gray-100 hover:shadow-md hover:border-gray-200 transition-all duration-200">
        <div class="flex items-start justify-between">
            <div>
                <p class="text-gray-500 text-sm font-medium"><?= $stat['label'] ?></p>
                <p class="text-3xl font-bold text-gray-900 mt-1 tabular-nums"><?= number_format($stat['value']) ?></p>
            </div>
            <div class="<?= $stat['icon_bg'] ?> w-11 h-11 rounded-xl flex items-center justify-center text-white shadow-sm group-hover:scale-110 transition-transform duration-200">
                <i class="fa-solid <?= $stat['icon'] ?>"></i>
            </div>
        </div>
        <div class="mt-4 flex items-center gap-1 text-xs font-medium <?= $stat['text'] ?> opacity-0 group-hover:opacity-100 transition-opacity">
            Xem chi tiết <i class="fa-solid fa-arrow-right text-[10px]"></i>
        </div>
    </a>
    <?php endforeach; ?>
</div>

<!-- QUICK ACTIONS -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 mb-8">
    <h2 class="text-sm font-semibold text-gray-500 uppercase tracking-wider mb-4">Thao tác nhanh</h2>
    <div class="flex flex-wrap gap-3">
        <a href="/admin/products/create.php"
           class="inline-flex items-center gap-2 bg-emerald-500 hover:bg-emerald-600 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm hover:shadow transition-all">
            <i class="fa-solid fa-plus"></i>
            Thêm thiết kế
        </a>
        <a href="/admin/products"
           class="inline-flex items-center gap-2 bg-gray-800 hover:bg-gray-900 text-white px-5 py-2.5 rounded-xl text-sm font-medium shadow-sm hover:shadow transition-all">
            <i class="fa-solid fa-paintbrush"></i>
            Quản lý thiết kế
        </a>
        <a href="/admin/brands"
           class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
            <i class="fa-solid fa-building-columns text-violet-500"></i>
            Thương hiệu
        </a>
        <a href="/admin/categories"
           class="inline-flex items-center gap-2 bg-white hover:bg-gray-50 text-gray-700 border border-gray-200 px-5 py-2.5 rounded-xl text-sm font-medium transition-all">
            <i class="fa-solid fa-layer-group text-amber-500"></i>
            Danh mục
        </a>
    </div>
</div>

<!-- CHART SECTION -->
<div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-5 sm:p-6 mb-8">
    <div class="flex justify-between items-center mb-5">
        <div>
            <h2 class="text-xl font-bold text-gray-900">Biểu đồ</h2>
            <p class="text-sm text-gray-500 mt-0.5">Theo dõi sự phát triển của hệ thống</p>
        </div>
        <select id="chartFilter" class="border border-gray-200 rounded-xl px-3 py-2 text-sm focus:outline-none focus:border-blue-500 focus:ring-2 focus:ring-blue-500/20">
            <option value="week">Tuần này</option>
            <option value="month" selected>Tháng này</option>
            <option value="quarter">Quý này</option>
        </select>
    </div>
    <div class="relative h-80 w-full">
        <canvas id="dashboardChart"></canvas>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
    let dashboardChart = null;

    function fetchChartData(filter) {
        fetch(`/admin/api/dashboard_chart.php?filter=${filter}`)
            .then(res => res.json())
            .then(data => {
                const ctx = document.getElementById('dashboardChart').getContext('2d');
                
                if (dashboardChart) {
                    dashboardChart.destroy();
                }

                dashboardChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: data.labels,
                        datasets: data.datasets
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        interaction: {
                            mode: 'index',
                            intersect: false,
                        },
                        plugins: {
                            legend: {
                                position: 'top',
                            }
                        },
                        scales: {
                            y: {
                                beginAtZero: true,
                                ticks: {
                                    stepSize: 1
                                }
                            }
                        }
                    }
                });
            })
            .catch(err => console.error('Error fetching chart data:', err));
    }

    document.getElementById('chartFilter').addEventListener('change', function(e) {
        fetchChartData(e.target.value);
    });

    // Initial load
    fetchChartData('month');
</script>

<?php require 'partials/footer.php'; ?>
