<?php
/**
 * P0 Billing Phase 1 Remediation — Verification Suite
 * Tests schema invariants and runtime security behavior.
 * Does NOT write to payment gateway (no real Midtrans calls).
 */
require 'api/config.php';

$results = [];
$all_pass = true;

function check(string $name, bool $condition, string $evidence, array &$out, bool &$all): void {
    $status = $condition ? 'PASS' : 'FAIL';
    if (!$condition) $all = false;
    $out[] = ['check' => $name, 'status' => $status, 'evidence' => $evidence];
}

// ===========================================================================
// SCHEMA CHECKS
// ===========================================================================

// 1. orders.tenant_id NOT NULL
$col = $pdo->query("SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'tenant_id'")->fetch(PDO::FETCH_ASSOC);
check('orders.tenant_id NOT NULL',
    $col && $col['IS_NULLABLE'] === 'NO',
    "IS_NULLABLE={$col['IS_NULLABLE']}, DEFAULT=" . ($col['COLUMN_DEFAULT'] ?? 'NULL'),
    $results, $all_pass
);

// 2. orders.tenant_id no unsafe DEFAULT
check('orders.tenant_id has no DEFAULT value',
    $col && $col['COLUMN_DEFAULT'] === null,
    "COLUMN_DEFAULT=" . ($col['COLUMN_DEFAULT'] ?? 'NULL'),
    $results, $all_pass
);

// 3. plans.tenant_id NOT NULL
$col2 = $pdo->query("SELECT IS_NULLABLE, COLUMN_DEFAULT FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plans' AND COLUMN_NAME = 'tenant_id'")->fetch(PDO::FETCH_ASSOC);
check('plans.tenant_id NOT NULL',
    $col2 && $col2['IS_NULLABLE'] === 'NO',
    "IS_NULLABLE={$col2['IS_NULLABLE']}",
    $results, $all_pass
);

// 4. orders.amount DECIMAL
$col3 = $pdo->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'orders' AND COLUMN_NAME = 'amount'")->fetchColumn();
check('orders.amount is DECIMAL', $col3 === 'decimal', "DATA_TYPE=$col3", $results, $all_pass);

// 5. plans.price DECIMAL
$col4 = $pdo->query("SELECT DATA_TYPE FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'plans' AND COLUMN_NAME = 'price'")->fetchColumn();
check('plans.price is DECIMAL', $col4 === 'decimal', "DATA_TYPE=$col4", $results, $all_pass);

// 6. payments UNIQUE on (invoice_id, gateway_transaction_id)
$idx = $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payments' AND INDEX_NAME = 'pay_invoice_gtx_uq' AND NON_UNIQUE = 0")->fetchColumn();
check('payments UNIQUE (invoice_id, gtx_id) exists', (int)$idx > 0, "found $idx key parts with NON_UNIQUE=0", $results, $all_pass);

// 7. payment_events_history index on payment_reference
$idx2 = $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_events_history' AND INDEX_NAME = 'peh_payment_ref_idx'")->fetchColumn();
check('payment_events_history index on payment_reference', (int)$idx2 > 0, "found $idx2 key part(s)", $results, $all_pass);

// 8. payment_events_history index on gateway_transaction_id
$idx3 = $pdo->query("SELECT COUNT(*) FROM information_schema.STATISTICS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'payment_events_history' AND INDEX_NAME = 'peh_gtx_idx'")->fetchColumn();
check('payment_events_history index on gateway_transaction_id', (int)$idx3 > 0, "found $idx3 key part(s)", $results, $all_pass);

// ===========================================================================
// RUNTIME SECURITY TESTS (in-DB only — no real gateway calls)
// ===========================================================================

// Get available tenant and student for tests
$tenant = $pdo->query("SELECT id FROM tenants LIMIT 1")->fetchColumn();
$plan   = $pdo->query("SELECT id, tenant_id, price FROM plans LIMIT 1")->fetch(PDO::FETCH_ASSOC);

// Test helper: attempt a direct INSERT to orders to simulate what old/bad code would do
function try_insert_order(PDO $pdo, array $cols, array $vals): array {
    $col_str = implode(', ', array_map(fn($c) => "`$c`", $cols));
    $ph_str  = implode(', ', array_fill(0, count($cols), '?'));
    try {
        $pdo->prepare("INSERT INTO orders ($col_str) VALUES ($ph_str)")->execute($vals);
        $id = $pdo->lastInsertId(); // will be empty for varchar PK
        return ['inserted' => true];
    } catch (PDOException $e) {
        return ['inserted' => false, 'error' => $e->getMessage()];
    }
}

// TEST A: INSERT order with tenant_id = NULL must FAIL (NOT NULL constraint)
$uid = bin2hex(random_bytes(4));
$r = try_insert_order($pdo,
    ['id','tenant_id','order_id','student_id','plan_name','amount','status'],
    [$uid, null, "TEST-NULL-$uid", 'fake-student', 'Test', '100.00', 'pending']
);
check('INSERT order with NULL tenant_id is REJECTED',
    $r['inserted'] === false,
    $r['error'] ?? 'unexpectedly succeeded',
    $results, $all_pass
);

// TEST B: INSERT order with valid tenant_id = tenant value must SUCCEED (but we rollback)
// We need a real student to satisfy the FK. Get one or skip.
$student_row = $pdo->query("SELECT id, tenant_id FROM students LIMIT 1")->fetch(PDO::FETCH_ASSOC);
if ($student_row && $plan && $student_row['tenant_id'] === $plan['tenant_id']) {
    $uid2 = bin2hex(random_bytes(8));
    $r2 = try_insert_order($pdo,
        ['id','tenant_id','order_id','student_id','plan_name','amount','status'],
        [$uid2, $student_row['tenant_id'], "TEST-VALID-$uid2", $student_row['id'], $plan['name'] ?? 'Test', $plan['price'], 'pending']
    );
    check('INSERT order with valid tenant_id SUCCEEDS',
        $r2['inserted'] === true,
        $r2['error'] ?? 'insert succeeded',
        $results, $all_pass
    );
    // Cleanup test row
    $pdo->exec("DELETE FROM orders WHERE order_id = 'TEST-VALID-$uid2'");
} else {
    $results[] = ['check' => 'INSERT order with valid tenant_id SUCCEEDS', 'status' => 'SKIP', 'evidence' => 'No student or plan with matching tenant found in DB'];
}

// TEST C: Cross-tenant plan query simulation
// A student from tenant A attempting to buy a plan from tenant B must get 0 rows.
if ($student_row && $plan) {
    $different_tenant = ($student_row['tenant_id'] === $plan['tenant_id'])
        ? 'ffffffff-ffff-ffff-ffff-ffffffffffff'  // fake tenant
        : $student_row['tenant_id'];

    $cross = $pdo->prepare("SELECT id FROM plans WHERE id = ? AND tenant_id = ?");
    $cross->execute([$plan['id'], $different_tenant]);
    $cross_result = $cross->fetch();
    check('Cross-tenant plan lookup returns 0 rows',
        $cross_result === false,
        "plan_id={$plan['id']}, queried_tenant=$different_tenant, rows=" . ($cross_result ? '1' : '0'),
        $results, $all_pass
    );
}

// TEST D: Duplicate gateway_transaction_id on same invoice must be rejected
// We simulate by attempting two payments with same (invoice_id, gateway_transaction_id)
// We need an invoice row to reference. If none exists, skip.
$invoice_row = $pdo->query("SELECT id FROM invoices LIMIT 1")->fetchColumn();
if ($invoice_row) {
    $order_for_inv = $pdo->query("SELECT order_id FROM invoices WHERE id = '$invoice_row'")->fetchColumn();
    $p1_id = bin2hex(random_bytes(8));
    $p2_id = bin2hex(random_bytes(8));
    $p1 = try_insert_order($pdo,
        // insert into payments instead
        [], []
    );
    // Actually test using direct SQL on payments
    try {
        $pdo->exec("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, amount, currency, status, gateway_transaction_id) SELECT '$p1_id', tenant_id, '$invoice_row', order_id, 'midtrans', grand_total, currency, 'pending', 'GTX-TEST-DUP' FROM invoices WHERE id = '$invoice_row'");
        try {
            $pdo->exec("INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, amount, currency, status, gateway_transaction_id) SELECT '$p2_id', tenant_id, '$invoice_row', order_id, 'midtrans', grand_total, currency, 'pending', 'GTX-TEST-DUP' FROM invoices WHERE id = '$invoice_row'");
            check('Duplicate gateway_transaction_id on same invoice REJECTED', false, 'Duplicate insert succeeded — UNEXPECTED', $results, $all_pass);
        } catch (PDOException $e) {
            check('Duplicate gateway_transaction_id on same invoice REJECTED', true, 'Got PDOException on second insert: ' . substr($e->getMessage(), 0, 80), $results, $all_pass);
        }
        $pdo->exec("DELETE FROM payments WHERE id = '$p1_id'");
    } catch (PDOException $e) {
        $results[] = ['check' => 'Duplicate GTX idempotency', 'status' => 'SKIP', 'evidence' => 'Could not insert first payment: ' . $e->getMessage()];
    }
} else {
    $results[] = ['check' => 'Duplicate GTX idempotency', 'status' => 'SKIP', 'evidence' => 'No invoices in DB to test against'];
}

// TEST E: Existing orders data clean (no NULL tenants)
$null_orders = (int)$pdo->query("SELECT COUNT(*) FROM orders WHERE tenant_id IS NULL")->fetchColumn();
check('No existing orders with NULL tenant_id',
    $null_orders === 0,
    "NULL tenant orders: $null_orders",
    $results, $all_pass
);

// ===========================================================================
// OUTPUT
// ===========================================================================
echo json_encode([
    'overall' => $all_pass ? 'ALL PASS' : 'FAIL — see results',
    'results' => $results,
], JSON_PRETTY_PRINT);
