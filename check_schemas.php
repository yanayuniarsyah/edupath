<?php
require 'api/config.php';
$tables = ['affiliates', 'commissions', 'orders', 'students', 'payment_events_history'];
foreach ($tables as $t) {
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE $t");
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        echo $row['Create Table'] . ";\n\n";
    } catch(Exception $e) {
        echo "Table $t not found or error: " . $e->getMessage() . "\n";
    }
}
