<?php
require 'lib/db.php';
$pdo->exec('CREATE TABLE IF NOT EXISTS user_product_access (user_id INT NOT NULL, product_id INT NOT NULL, PRIMARY KEY (user_id, product_id))');
echo "Table created.";
