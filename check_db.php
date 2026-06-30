<?php
require 'lib/db.php';
$stmt = $pdo->query('SHOW COLUMNS FROM products');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
