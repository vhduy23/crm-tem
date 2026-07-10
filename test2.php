<?php
require 'lib/db.php';
$stmt = $pdo->query('SELECT slug FROM products LIMIT 1');
echo $stmt->fetchColumn();
