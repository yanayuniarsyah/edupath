<?php
require 'api/config.php';
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
echo "Tables: " . implode(", ", $tables) . "\n";
echo "Plans schema:\n";
print_r($pdo->query("SHOW CREATE TABLE plans")->fetch(PDO::FETCH_ASSOC));
echo "Orders schema:\n";
print_r($pdo->query("SHOW CREATE TABLE orders")->fetch(PDO::FETCH_ASSOC));
