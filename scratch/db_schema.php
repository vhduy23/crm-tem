<?php
require __DIR__ . '/../lib/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM product_images');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
