<?php
require_once 'api/config.php';
$stmt = $pdo->query('SHOW CREATE TABLE plans');
print_r($stmt->fetch(PDO::FETCH_ASSOC));

