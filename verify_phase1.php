<?php
require 'api/config.php';

$tables = ['plans', 'orders', 'invoices', 'payments', 'payment_events_history'];
$schema = [];

foreach ($tables as $t) {
    $stmt = $pdo->query("SHOW CREATE TABLE `$t`");
    if ($stmt) {
        $schema[$t] = $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'];
    }
}

// Check orders.tenant_id nullability explicitly
$orders_cols = $pdo->query("SHOW COLUMNS FROM `orders`")->fetchAll(PDO::FETCH_ASSOC);
// Check plans.tenant_id nullability explicitly
$plans_cols = $pdo->query("SHOW COLUMNS FROM `plans`")->fetchAll(PDO::FETCH_ASSOC);

$data = [
    'orders_count' => $pdo->query("SELECT COUNT(*) FROM orders")->fetchColumn(),
    'orders_null_tenant' => $pdo->query("SELECT COUNT(*) FROM orders WHERE tenant_id IS NULL")->fetchColumn(),
    'orders_orphan_tenant' => $pdo->query("SELECT COUNT(*) FROM orders o LEFT JOIN tenants t ON o.tenant_id = t.id WHERE o.tenant_id IS NOT NULL AND t.id IS NULL")->fetchColumn(),
    'orders_mismatch_student_tenant' => $pdo->query("SELECT COUNT(*) FROM orders o JOIN students s ON o.student_id = s.id WHERE o.tenant_id != s.tenant_id")->fetchColumn(),
    'plans_count' => $pdo->query("SELECT COUNT(*) FROM plans")->fetchColumn(),
    'plans_null_tenant' => $pdo->query("SELECT COUNT(*) FROM plans WHERE tenant_id IS NULL")->fetchColumn(),
    'plans_orphan_tenant' => $pdo->query("SELECT COUNT(*) FROM plans p LEFT JOIN tenants t ON p.tenant_id = t.id WHERE p.tenant_id IS NOT NULL AND t.id IS NULL")->fetchColumn(),
];

echo json_encode([
    'schema' => $schema,
    'orders_cols' => $orders_cols,
    'plans_cols' => $plans_cols,
    'data' => $data
], JSON_PRETTY_PRINT);
