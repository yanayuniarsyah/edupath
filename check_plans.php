<?php
require 'api/config.php';
$stmt = $pdo->query("SHOW CREATE TABLE plans");
$row = $stmt->fetch(PDO::FETCH_ASSOC);
echo $row['Create Table'] . ";\n";
