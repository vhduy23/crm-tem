<?php
require 'lib/db.php';
$stmt = $pdo->query('SELECT * FROM users WHERE role_id = 0');
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
