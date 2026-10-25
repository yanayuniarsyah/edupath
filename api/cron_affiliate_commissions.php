<?php
// Run from cron, never from the public web without the shared secret.
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/RevenueSplitEngine.php';
require_once __DIR__ . '/FinancialLedger.php';
require_once __DIR__ . '/RoyaltyRegistry.php';

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
        SELECT o.*
        FROM orders o
        WHERE o.status = 'paid' AND o.affiliate_id IS NOT NULL
          AND NOT EXISTS (SELECT 1 FROM commissions c WHERE c.order_id = o.id)
        ORDER BY o.created_at ASC
        LIMIT 500
    ")->fetchAll(PDO::FETCH_ASSOC);

    foreach ($orders as $order) {
        $pdo->beginTransaction();
        $stmt = $pdo->prepare("SELECT tenant_id FROM affiliates WHERE id = ? FOR UPDATE");
        $stmt->execute([$order['affiliate_id']]);
        $affiliate = $stmt->fetch();
        if (!$affiliate || $affiliate['tenant_id'] !== $order['tenant_id']) {
            $pdo->rollBack();
            continue;
        }

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM orders WHERE student_id = ? AND status = 'paid' AND id <> ?");
        $stmt->execute([$order['student_id'], $order['id']]);
        $isRenewal = ((int)$stmt->fetchColumn() > 0);

        $royaltyCalc = RoyaltyRegistry::calculateRoyalties($pdo, $order['plan_id'] ?? null, (string)$order['amount']);
        $splitResult = RevenueSplitEngine::calculate([
            'gross_amount'            => (string)$order['amount'],
            'channel'                 => 'AFFILIATE',
            'is_renewal'              => $isRenewal,
            'gateway_fee'             => $order['gateway_fee'] ?? null,
            'royalty_fee'             => $royaltyCalc['total_royalty'],
            'affiliate_id'            => $order['affiliate_id'],
            'branch_tenant_id'        => $order['branch_tenant_id'] ?? $affiliate['tenant_id'],
        ]);

        FinancialLedger::postOrderSplit($pdo, $order['id'], $order['tenant_id'], 'AFFILIATE', $splitResult);

        $rate = $isRenewal ? '10.00' : '20.00';
        $id = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
        $stmt = $pdo->prepare("INSERT IGNORE INTO commissions (id, affiliate_id, tenant_id, order_id, amount, commission_rate_snapshot, status) VALUES (?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([$id, $order['affiliate_id'], $order['tenant_id'], $order['id'], $splitResult['affiliate_amount'], $rate]);
        $processed += $stmt->rowCount();
        $pdo->commit();
    }
    echo json_encode(['success' => true, 'processed' => $processed]);
} finally {
    $pdo->query("SELECT RELEASE_LOCK('edupath_affiliate_commissions')");
}
