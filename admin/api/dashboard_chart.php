<?php
require '../../lib/session.php';
require '../../lib/db.php';
require '../auth.php';

checkLogin();

$filter = $_GET['filter'] ?? 'month';
header('Content-Type: application/json');

$labels = [];
$dateFormat = '';
$startDate = '';
$endDate = date('Y-m-d 23:59:59');

if ($filter === 'week') {
    // Current week (Monday to Sunday)
    $dayOfWeek = date('N') - 1; // 0 = Mon, 6 = Sun
    $startDate = date('Y-m-d 00:00:00', strtotime("-$dayOfWeek days"));
    for ($i = 0; $i < 7; $i++) {
        $labels[] = date('d/m', strtotime("$startDate +$i days"));
    }
} elseif ($filter === 'month') {
    // Current month
    $startDate = date('Y-m-01 00:00:00');
    $daysInMonth = date('t');
    for ($i = 1; $i <= $daysInMonth; $i++) {
        $labels[] = sprintf('%02d/%02d', $i, date('m'));
    }
} elseif ($filter === 'quarter') {
    // Current quarter
    $currentMonth = date('n');
    $startMonth = floor(($currentMonth - 1) / 3) * 3 + 1;
    $startDate = date('Y-' . sprintf('%02d', $startMonth) . '-01 00:00:00');
    
    // Labels are the 3 months of the quarter
    for ($i = 0; $i < 3; $i++) {
        $m = $startMonth + $i;
        $labels[] = 'Tháng ' . $m;
    }
}

// Function to fetch and map data
function getStats($pdo, $table, $filter, $startDate, $endDate, $labels) {
    $sql = "";
    if ($filter === 'week' || $filter === 'month') {
        $sql = "SELECT DATE_FORMAT(created_at, '%d/%m') as lbl, COUNT(*) as cnt FROM $table WHERE created_at >= ? AND created_at <= ? GROUP BY lbl";
    } else if ($filter === 'quarter') {
        $sql = "SELECT CONCAT('Tháng ', MONTH(created_at)) as lbl, COUNT(*) as cnt FROM $table WHERE created_at >= ? AND created_at <= ? GROUP BY lbl";
    }
    
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$startDate, $endDate]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $dataMap = [];
    foreach ($results as $row) {
        $dataMap[$row['lbl']] = (int)$row['cnt'];
    }
    
    $data = [];
    foreach ($labels as $lbl) {
        $data[] = $dataMap[$lbl] ?? 0;
    }
    return $data;
}

$datasets = [
    [
        'label' => 'Thiết kế',
        'data' => getStats($pdo, 'products', $filter, $startDate, $endDate, $labels),
        'borderColor' => '#3b82f6', // blue-500
        'backgroundColor' => 'rgba(59, 130, 246, 0.1)',
        'tension' => 0.4,
        'fill' => true
    ],
    [
        'label' => 'Người dùng',
        'data' => getStats($pdo, 'users', $filter, $startDate, $endDate, $labels),
        'borderColor' => '#10b981', // emerald-500
        'backgroundColor' => 'rgba(16, 185, 129, 0.1)',
        'tension' => 0.4,
        'fill' => true
    ],
    [
        'label' => 'Thương hiệu',
        'data' => getStats($pdo, 'brands', $filter, $startDate, $endDate, $labels),
        'borderColor' => '#8b5cf6', // violet-500
        'backgroundColor' => 'rgba(139, 92, 246, 0.1)',
        'tension' => 0.4,
        'fill' => true
    ],
    [
        'label' => 'Danh mục',
        'data' => getStats($pdo, 'categories', $filter, $startDate, $endDate, $labels),
        'borderColor' => '#f59e0b', // amber-500
        'backgroundColor' => 'rgba(245, 158, 11, 0.1)',
        'tension' => 0.4,
        'fill' => true
    ]
];

echo json_encode([
    'labels' => $labels,
    'datasets' => $datasets
]);
