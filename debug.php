<?php
require 'api/config.php';
try {
    $stmt = $pdo->query("DESCRIBE tenants");
    var_dump($stmt->fetchAll(PDO::FETCH_COLUMN));
    
    $stmt = $pdo->query("SHOW TABLES LIKE 'affiliates'");
    if ($stmt->rowCount() > 0) {
        $stmt = $pdo->query("DESCRIBE affiliates");
        var_dump($stmt->fetchAll(PDO::FETCH_COLUMN));
    }
} catch (Exception $e) {
    echo $e->getMessage();
}
