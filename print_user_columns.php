<?php
require 'api/config.php';
$stmt = $pdo->query("SHOW COLUMNS FROM users");
$cols = $stmt->fetchAll(PDO::FETCH_COLUMN);
foreach ($cols as $c) {
    echo $c . "\n";
}
?>
