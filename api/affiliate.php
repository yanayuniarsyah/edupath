<?php
require_once 'config.php';
require_once 'jwt.php';

/**
 * Affiliate API.
 * Financial values and ownership are always resolved server-side.
 * Public action=track only records a referral click; dashboard actions require auth.
 */
function affiliate_json(array $data, int $status = 200): void {
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function affiliate_uuid(): string {
    $hex = bin2hex(random_bytes(16));
    return substr($hex, 0, 8).'-'.substr($hex, 8, 4).'-'.substr($hex, 12, 4).'-'.substr($hex, 16, 4).'-'.substr($hex, 20, 12);
}

function request_body(): array {
    $raw = file_get_contents('php://input');
    $body = json_decode($raw ?: '{}', true);
    return is_array($body) ? $body : [];
}

function clean_string($value, int $max = 120): string {
    return mb_substr(trim((string)$value), 0, $max);
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$body = request_body();
$action = clean_string($_GET['action'] ?? $body['action'] ?? 'profile', 30);

// Referral click tracking is intentionally public and stores no raw IP or PII.
if ($action === 'track' && $method === 'POST') {
    $code = strtoupper(clean_string($body['referral_code'] ?? $_GET['referral_code'] ?? '', 50));
    if (!preg_match('/^[A-Z0-9_-]{4,50}$/', $code)) {
        affiliate_json(['success' => false, 'error' => 'Kode referral tidak valid.'], 422);
    }

    $stmt = $pdo->prepare("SELECT id FROM affiliates WHERE referral_code = ? AND status = 'active' LIMIT 1");
    $stmt->execute([$code]);
    $affiliateId = $stmt->fetchColumn();
    if (!$affiliateId) affiliate_json(['success' => true]); // Do not disclose valid codes.

    $ip = $_SERVER['REMOTE_ADDR'] ?? '';
    $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $stmt = $pdo->prepare('INSERT INTO affiliate_clicks (id, affiliate_id, referral_code, landing_path, visitor_hash, user_agent_hash) VALUES (?, ?, ?, ?, ?, ?)');
    $stmt->execute([
        affiliate_uuid(),
        $affiliateId,
        $code,
        clean_string($body['landing_path'] ?? '/', 255),
        hash('sha256', $ip . '|' . date('Y-m-d')),
        hash('sha256', $ua),
    ]);
    affiliate_json(['success' => true]);
}

$payload = authenticate();
$role = (string)($payload->role ?? '');
if (!in_array($role, ['student', 'affiliate', 'admin', 'superadmin'], true)) {
    affiliate_json(['success' => false, 'error' => 'Akses affiliate tidak diizinkan.'], 403);
}
$userId = clean_string($payload->user_id ?? '', 36);
$tenantId = clean_string($payload->tenant_id ?? '', 36);
if ($userId === '') affiliate_json(['success' => false, 'error' => 'User context tidak ditemukan.'], 401);

if ($method === 'GET') {
    $stmt = $pdo->prepare('SELECT * FROM affiliates WHERE user_id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$userId, $tenantId]);
    $affiliate = $stmt->fetch();
    if (!$affiliate) affiliate_json(['success' => true, 'status' => 'not_joined']);

    $affiliateId = $affiliate['id'];
    $stmt = $pdo->prepare('SELECT COUNT(*) FROM affiliate_clicks WHERE affiliate_id = ?');
    $stmt->execute([$affiliateId]);
    $clicks = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM students WHERE referred_by = ?');
    $stmt->execute([$affiliateId]);
    $referrals = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COALESCE(SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END), 0), COALESCE(SUM(CASE WHEN status IN ('pending','approved') THEN amount ELSE 0 END), 0) FROM commissions WHERE affiliate_id = ?");
    $stmt->execute([$affiliateId]);
    [$paid, $pending] = $stmt->fetch(PDO::FETCH_NUM);

    $stmt = $pdo->prepare('SELECT id, amount, commission_type, status, available_at, paid_at, created_at FROM commissions WHERE affiliate_id = ? ORDER BY created_at DESC LIMIT 25');
    $stmt->execute([$affiliateId]);
    affiliate_json([
        'success' => true,
        'status' => 'joined',
        'data' => [
            'affiliate_id' => $affiliateId,
            'referral_code' => $affiliate['referral_code'],
            'commission_rates' => [
                'acquisition' => (float)($affiliate['acquisition_rate'] ?? $affiliate['commission_rate'] ?? 20),
                'recurring' => (float)($affiliate['recurring_rate'] ?? 10),
            ],
            'stats' => ['clicks' => $clicks, 'referrals' => $referrals, 'total_paid' => (float)$paid, 'total_pending' => (float)$pending],
            'history' => $stmt->fetchAll(),
        ],
    ]);
}

if ($method === 'POST' && $action === 'join') {
    $stmt = $pdo->prepare('SELECT id, name FROM students WHERE user_id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$userId, $tenantId]);
    $student = $stmt->fetch();
    $name = clean_string($student['name'] ?? 'USER', 80);

    $stmt = $pdo->prepare('SELECT id, referral_code FROM affiliates WHERE user_id = ? AND tenant_id = ? LIMIT 1');
    $stmt->execute([$userId, $tenantId]);
    if ($existing = $stmt->fetch()) affiliate_json(['success' => true, 'status' => 'joined', 'referral_code' => $existing['referral_code']]);

    $base = substr(preg_replace('/[^A-Z0-9]/', '', strtoupper($name)), 0, 5) ?: 'USER';
    $code = $base . strtoupper(bin2hex(random_bytes(3)));
    $stmt = $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate, acquisition_rate, recurring_rate, status) VALUES (?, ?, ?, ?, 20, 20, 10, 'active')");
    $stmt->execute([affiliate_uuid(), $userId, $tenantId, $code]);
    log_audit($pdo, $userId, $tenantId, 'affiliate.joined', null, ['referral_code' => $code]);
    affiliate_json(['success' => true, 'status' => 'joined', 'referral_code' => $code], 201);
}

affiliate_json(['success' => false, 'error' => 'Method atau action tidak didukung.'], 405);
