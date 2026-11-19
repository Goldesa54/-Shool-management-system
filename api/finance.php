<?php
$pdo = require __DIR__ . '/db.php';
header('Content-Type: application/json');
$rows = $pdo->query('SELECT * FROM fees ORDER BY id')->fetchAll(PDO::FETCH_ASSOC);
echo json_encode($rows);
