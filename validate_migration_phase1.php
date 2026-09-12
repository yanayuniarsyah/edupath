<?php
require_once __DIR__ . '/api/config.php';

$results = [
    'plans' => [
        'count' => 0,
        'null_prices' => 0,
        'fractional_prices' => 0
    ],
    'orders' => [
        'count' => 0,
        'null_amounts' => 0,
        'fractional_amounts' => 0,
        'orphan_students' => 0
    ]
];

// 1. Validate plans
$plans = $pdo->query("SELECT id, price FROM plans")->fetchAll(PDO::FETCH_ASSOC);
$results['plans']['count'] = count($plans);
foreach ($plans as $p) {
    if ($p['price'] === null) {
        $results['plans']['null_prices']++;
    } else {
        // check if fractional part has more than 2 digits
        $val = (float)$p['price'];
        $rounded = round($val, 2);
        if (abs($val - $rounded) > 0.001) {
            $results['plans']['fractional_prices']++;
        }
    }
}

// 2. Validate orders
$orders = $pdo->query("SELECT o.id, o.amount, o.student_id, s.id as sid FROM orders o LEFT JOIN students s ON o.student_id = s.id")->fetchAll(PDO::FETCH_ASSOC);
$results['orders']['count'] = count($orders);
foreach ($orders as $o) {
    if ($o['amount'] === null) {
        $results['orders']['null_amounts']++;
    } else {
        $val = (float)$o['amount'];
        $rounded = round($val, 2);
        if (abs($val - $rounded) > 0.001) {
            $results['orders']['fractional_amounts']++;
        }
    }
    
    if (empty($o['sid'])) {
        $results['orders']['orphan_students']++;
    }
}

// Ensure there is a tenant_id logic map possible
// For plans: do we randomly assign a tenant? Actually, if there are existing plans, how do we assign tenant_id?
// By default, maybe assign to the first tenant (admin's tenant) or NULL (but plans.tenant_id usually shouldn't be NULL if strict B2B).
// Let's check how many tenants exist.
$tenants = $pdo->query("SELECT COUNT(*) FROM tenants")->fetchColumn();
$results['tenants_count'] = $tenants;

echo json_encode($results, JSON_PRETTY_PRINT);
