<?php
require_once 'config.php';
require_once 'jwt.php';

function affiliate_json(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function affiliate_uuid(): string {
    $hex = bin2hex(random_bytes(16));
    return substr($hex, 0, 8) . '-' . substr($hex, 8, 4) . '-' . substr($hex, 12, 4) . '-' . substr($hex, 16, 4) . '-' . substr($hex, 20, 12);
}

function affiliate_request_body(): array {
    $raw = file_get_contents('php://input');
    $json = json_decode($raw ?: '{}', true);
    return is_array($json) ? $json : [];
}

function affiliate_clean_string($value, int $max = 120): string {
    $value = trim((string) $value);
    return mb_substr($value, 0, $max);
}

function affiliate_get_user_name(PDO $pdo, string $userId, string $tenantId): string {
    $stmt = $pdo->prepare('SELECT name FROM students WHERE user_id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$userId, $tenantId]);
    $name = $stmt->fetchColumn();
    if (!empty($name)) {
        return $name;
    }

    $stmt = $pdo->prepare('SELECT name FROM students WHERE id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$userId, $tenantId]);
    $name = $stmt->fetchColumn();
    if (!empty($name)) {
        return $name;
    }

    return 'USER';
}

$method = strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
$body = affiliate_request_body();
$action = strtolower(affiliate_clean_string($_GET['action'] ?? ($body['action'] ?? 'profile'), 32));

if ($method === 'POST' && $action === 'track') {
    $code = strtoupper(affiliate_clean_string($body['referral_code'] ?? $_GET['referral_code'] ?? '', 50));
    if (!preg_match('/^[A-Z0-9_-]{4,50}$/', $code)) {
        affiliate_json(['success' => false, 'error' => 'Kode referral tidak valid.'], 422);
    }

    $stmt = $pdo->prepare("SELECT id, status FROM affiliates WHERE referral_code = ? LIMIT 1");
    $stmt->execute([$code]);
    $affiliate = $stmt->fetch();
    if (!$affiliate || (($affiliate['status'] ?? '') !== 'active' && ($affiliate['status'] ?? '') !== '')) {
        affiliate_json(['success' => true, 'status' => 'ignored']);
    }

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $visitorHash = hash('sha256', ($ip !== '' ? $ip : 'anon') . '|' . date('Y-m-d'));
    $userAgentHash = hash('sha256', $ua ?: 'unknown-agent');
    $landingPath = affiliate_clean_string($body['landing_path'] ?? $_GET['landing_path'] ?? '/', 255);
    $utmSource = affiliate_clean_string($body['utm_source'] ?? $_GET['utm_source'] ?? '', 64);
    $utmMedium = affiliate_clean_string($body['utm_medium'] ?? $_GET['utm_medium'] ?? '', 64);
    $utmCampaign = affiliate_clean_string($body['utm_campaign'] ?? $_GET['utm_campaign'] ?? '', 64);
    $sessionId = affiliate_clean_string($body['session_id'] ?? $_GET['session_id'] ?? '', 64);

    $stmt = $pdo->prepare(
        'INSERT INTO affiliate_clicks (id, affiliate_id, referral_code, landing_path, visitor_hash, user_agent_hash, utm_source, utm_medium, utm_campaign, session_id, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())'
    );
    $stmt->execute([
        affiliate_uuid(),
        $affiliate['id'],
        $code,
        $landingPath,
        $visitorHash,
        $userAgentHash,
        $utmSource,
        $utmMedium,
        $utmCampaign,
        $sessionId,
    ]);

    affiliate_json(['success' => true, 'status' => 'tracked']);
}

if ($method === 'POST' && $action === 'join') {
    $payload = authenticate();
    $role = (string) ($payload->role ?? '');
    if (!in_array($role, ['student', 'affiliate', 'admin', 'superadmin'], true)) {
        affiliate_json(['success' => false, 'error' => 'Akses affiliate tidak diizinkan.'], 403);
    }

    $userId = affiliate_clean_string((string) ($payload->user_id ?? ''), 36);
    $tenantId = affiliate_clean_string((string) ($payload->tenant_id ?? ''), 36);
    if ($userId === '') {
        affiliate_json(['success' => false, 'error' => 'User context tidak ditemukan.'], 401);
    }

    $stmt = $pdo->prepare('SELECT id, referral_code, status FROM affiliates WHERE user_id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$userId, $tenantId]);
    $existing = $stmt->fetch();
    if ($existing) {
        affiliate_json([
            'success' => true,
            'status' => $existing['status'] ?? 'joined',
            'referral_code' => $existing['referral_code'],
        ], 200);
    }

    $name = affiliate_get_user_name($pdo, $userId, $tenantId);
    $base = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($name ?: 'USER')), 0, 5) ?: 'USER';

    do {
        $code = $base . strtoupper(bin2hex(random_bytes(3)));
        $check = $pdo->prepare('SELECT id FROM affiliates WHERE referral_code = ? LIMIT 1');
        $check->execute([$code]);
    } while ($check->fetch());

    $insert = $pdo->prepare(
        'INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate, acquisition_rate, recurring_rate, status, created_at, updated_at) VALUES (?, ?, ?, ?, 20.00, 20.00, 10.00, ?, NOW(), NOW())'
    );
    $insert->execute([affiliate_uuid(), $userId, $tenantId, $code, 'active']);
    log_audit($pdo, $userId, $tenantId, 'affiliate.joined', null, ['referral_code' => $code]);

    affiliate_json(['success' => true, 'status' => 'joined', 'referral_code' => $code], 201);
}

$payload = authenticate();
$role = (string) ($payload->role ?? '');
if (!in_array($role, ['student', 'affiliate', 'admin', 'superadmin'], true)) {
    affiliate_json(['success' => false, 'error' => 'Akses affiliate tidak diizinkan.'], 403);
}

$userId = affiliate_clean_string((string) ($payload->user_id ?? ''), 36);
$tenantId = affiliate_clean_string((string) ($payload->tenant_id ?? ''), 36);
if ($userId === '') {
    affiliate_json(['success' => false, 'error' => 'User context tidak ditemukan.'], 401);
}

$stmt = $pdo->prepare('SELECT * FROM affiliates WHERE user_id = ? AND tenant_id = ? LIMIT 1');
$stmt->execute([$userId, $tenantId]);
$affiliate = $stmt->fetch();
if (!$affiliate) {
    affiliate_json(['success' => true, 'status' => 'not_joined']);
}

$affiliateId = $affiliate['id'];

$stmt = $pdo->prepare('SELECT COUNT(*) FROM affiliate_clicks WHERE affiliate_id = ?');
$stmt->execute([$affiliateId]);
$clicks = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE referred_by = ?');
$stmt->execute([$affiliateId]);
$referrals = (int) $stmt->fetchColumn();

$stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0) AS total_paid, COALESCE(SUM(CASE WHEN status IN ('pending','approved','available') THEN amount ELSE 0 END), 0) AS total_pending FROM commissions WHERE affiliate_id = ?");
$stmt->execute([$affiliateId]);
$commissionSummary = $stmt->fetch(PDO::FETCH_ASSOC);
$paid = (float) ($commissionSummary['total_paid'] ?? 0);
$pending = (float) ($commissionSummary['total_pending'] ?? 0);

$stmt = $pdo->prepare('SELECT id, amount, commission_type, status, available_at, paid_at, created_at FROM commissions WHERE affiliate_id = ? ORDER BY created_at DESC LIMIT 25');
$stmt->execute([$affiliateId]);
$history = $stmt->fetchAll(PDO::FETCH_ASSOC);

affiliate_json([
    'success' => true,
    'status' => 'joined',
    'data' => [
        'affiliate_id' => $affiliateId,
        'referral_code' => $affiliate['referral_code'],
        'status' => $affiliate['status'] ?? 'active',
        'commission_rates' => [
            'acquisition' => (float) ($affiliate['acquisition_rate'] ?? $affiliate['commission_rate'] ?? 20),
            'recurring' => (float) ($affiliate['recurring_rate'] ?? 10),
        ],
        'stats' => [
            'clicks' => $clicks,
            'referrals' => $referrals,
            'total_paid' => $paid,
            'total_pending' => $pending,
        ],
        'history' => $history,
    ],
]);
