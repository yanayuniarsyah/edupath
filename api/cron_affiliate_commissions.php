<?php
// Run from cron, never from the public web without the shared secret.
require_once __DIR__ . '/config.php';

$provided = $_SERVER['HTTP_X_CRON_SECRET'] ?? ($_GET['secret'] ?? '');
$expected = (string)env('CRON_SECRET', '');
if ($expected === '' || !hash_equals($expected, (string)$provided)) {
    http_response_code(403);
    echo json_encode(['error' => 'Forbidden']);
    exit;
}

$lock = $pdo->query("SELECT GET_LOCK('edupath_affiliate_commissions', 30)")->fetchColumn();
if ((int)$lock !== 1) {
    http_response_code(409);
    echo json_encode(['error' => 'Job sedang berjalan']);
    exit;
}

$processed = 0;
try {
    $orders = $pdo->query("
        SELECT o.id, o.student_id, o.tenant_id, o.affiliate_id, o.amount
        FROM orders o
        WHERE o.status = 'paid' AND o.affiliate_id IS NOT NULL
          AND NOT EXISTS (SELECT 1 FROM commissions c WHERE c.order_id = o.id)
        ORDER BY o.created_at ASC
        LIMIT 500
    ")->fetchAll();
    foreach ($orders as $order) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT tenant_id FROM affiliates WHERE id = ? FOR UPDATE");
        $stmt->execute([$order['affiliate_id']]);
        $affiliate = $stmt->fetch();
        if (!$affiliate || $affiliate['tenant_id'] !== $order['tenant_id']) {
            $pdo->rollBack();
            continue;
        }
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE student_id = ? AND tenant_id = ? AND status = 'paid' AND id <> ?");
        $stmt->execute([$order['student_id'], $order['tenant_id'], $order['id']]);
        $rate = ((int)$stmt->fetchColumn() === 0) ? '20.00' : '10.00';
        $amount = bcmul((string)$order['amount'], bcdiv($rate, '100', 10), 2);
        $id = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
        $stmt = $pdo->prepare("INSERT IGNORE INTO commissions (id, affiliate_id, tenant_id, order_id, amount, commission_rate_snapshot, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$id, $order['affiliate_id'], $order['tenant_id'], $order['id'], $amount, $rate]);
        $processed += $stmt->rowCount();
        $pdo->commit();
    }
    echo json_encode(['success' => true, 'processed' => $processed]);
} finally {
    $pdo->query("SELECT RELEASE_LOCK('edupath_affiliate_commissions')");
}
