<?php
require_once __DIR__ . '/api/config.php';
$stmt = $pdo->query('SHOW TABLES');
$tables = $stmt->fetchAll(PDO::FETCH_COLUMN);
$result = [];
foreach ($tables as $table) {
    $stmt2 = $pdo->query("SHOW CREATE TABLE `$table`");
    $create = $stmt2->fetch(PDO::FETCH_ASSOC);
    $result[$table] = $create['Create Table'] ?? '';
}
file_put_contents('schema_dump.json', json_encode($result, JSON_PRETTY_PRINT));
