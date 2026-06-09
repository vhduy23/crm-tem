<?php
require '../lib/db.php';
require '../admin/auth.php';

// checkLogin();
header('Content-Type: application/json');
$stmt = $pdo->query("
    SELECT p.*, 
    GROUP_CONCAT(pi.image_path) as images
    FROM products p
    LEFT JOIN product_images pi ON p.id = pi.product_id
    GROUP BY p.id
    ORDER BY p.id DESC
");
$data = [];
while ($row = $stmt->fetch()) {

    $row['images'] = $row['images'] ? explode(',', $row['images']) : [];
    $data[] = $row;
}
echo json_encode($data);