<?php
require '../lib/db.php';

echo json_encode(
    $pdo->query("SELECT * FROM roles")->fetchAll()
);