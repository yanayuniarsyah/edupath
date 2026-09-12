<?php
// Deep audit of Phase 2 pre-conditions
require 'api/config.php';

$audit = [];

// 1. Schema snapshot of all billing-relevant tables
$tables = ['plans','orders','invoices','payments','payment_events_history','subscriptions','entitlements','affiliates','commissions'];
$schemas = [];
foreach ($tables as $t) {
    $cols = $pdo->query("SELECT COLUMN_NAME, COLUMN_TYPE, IS_NULLABLE, COLUMN_DEFAULT, COLUMN_KEY, EXTRA
        FROM information_schema.COLUMNS
        WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$t'
        ORDER BY ORDINAL_POSITION")->fetchAll(PDO::FETCH_ASSOC);
    $schemas[$t] = $cols;
}

// 2. Data counts
$counts = [];
foreach ($tables as $t) {
    $counts[$t] = (int)$pdo->query("SELECT COUNT(*) FROM `$t`")->fetchColumn();
}

// 3. Check for columns that the webhook code references but may not exist
$webhook_refs = [
    'orders' => ['plan_id','paid_at','subscription_id'],
    'subscriptions' => ['student_id','plan_id','payment_reference','expires_at','plan_name_snapshot'],
    'affiliates' => ['commission_rate'],
    'commissions' => ['affiliate_id','tenant_id','order_id','amount','commission_rate_snapshot','status'],
];
$col_existence = [];
foreach ($webhook_refs as $table => $cols) {
    foreach ($cols as $col) {
        $exists = (bool)$pdo->query("SELECT COUNT(*) FROM information_schema.COLUMNS
            WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = '$table' AND COLUMN_NAME = '$col'")->fetchColumn();
        $col_existence[$table][$col] = $exists ? 'EXISTS' : 'MISSING';
    }
}

// 4. Check discount fields on plans (does plans have any discount/promo columns?)
$plan_extras = $pdo->query("SELECT COLUMN_NAME FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plans'
    ORDER BY ORDINAL_POSITION")->fetchAll(PDO::FETCH_COLUMN);

// 5. Check plan_entitlements content
$plan_entitlements_count = (int)$pdo->query("SELECT COUNT(*) FROM plan_entitlements")->fetchColumn();
$plan_ent_sample = $pdo->query("SELECT * FROM plan_entitlements LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);

// 6. Plans data
$plans_data = $pdo->query("SELECT * FROM plans")->fetchAll(PDO::FETCH_ASSOC);

$audit = [
    'schemas'           => $schemas,
    'data_counts'       => $counts,
    'webhook_col_refs'  => $col_existence,
    'plans_columns'     => $plan_extras,
    'plan_entitlements' => ['count' => $plan_entitlements_count, 'sample' => $plan_ent_sample],
    'plans_data'        => $plans_data,
];

file_put_contents('phase2_audit.json', json_encode($audit, JSON_PRETTY_PRINT));
echo "Audit written to phase2_audit.json\n";
