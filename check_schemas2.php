<?php
require 'api/config.php';
$tables = ['orders', 'payments', 'subscriptions', 'invoices', 'entitlements', 'plan_entitlements'];
foreach($tables as $t) {
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE $t");
        echo $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'] . ";\n\n";
    } catch(Exception $e) {}
}
