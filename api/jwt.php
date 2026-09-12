<?php
// api/jwt.php — EduPath JWT Handler
// Mendukung dua mekanisme token: HttpOnly Cookie (primary) + Bearer token (fallback untuk mobile/API)
// CSRF Protection: menggunakan Double Submit Cookie pattern untuk cookie-based auth

require_once 'config.php';

// ----------------------------------------------------------------
// JWT GENERATION
// ----------------------------------------------------------------
function generate_jwt(array $payload): string {
    $header = json_encode(['typ' => 'JWT', 'alg' => 'HS256']);
    $payload['iat'] = time();
    // TTL dikonfigurasi via JWT_ACCESS_TTL di environment (default: 86400 = 24 jam)
    $payload['exp'] = time() + (defined('JWT_ACCESS_TTL') ? JWT_ACCESS_TTL : 86400);

    $b64Header    = rtrim(strtr(base64_encode($header), '+/', '-_'), '=');
    $b64Payload   = rtrim(strtr(base64_encode(json_encode($payload)), '+/', '-_'), '=');
    $signature    = hash_hmac('sha256', "$b64Header.$b64Payload", JWT_SECRET, true);
    $b64Signature = rtrim(strtr(base64_encode($signature), '+/', '-_'), '=');

    return "$b64Header.$b64Payload.$b64Signature";
}

// ----------------------------------------------------------------
// JWT VERIFICATION
// ----------------------------------------------------------------
function verify_jwt(string $jwt): object|false {
    $parts = explode('.', $jwt);
    if (count($parts) !== 3) return false;

    [$b64Header, $b64Payload, $b64Sig] = $parts;

    $expectedSig = hash_hmac(
        'sha256',
        "$b64Header.$b64Payload",
        JWT_SECRET,
        true
    );
    $expectedB64 = rtrim(strtr(base64_encode($expectedSig), '+/', '-_'), '=');

    // Timing-safe comparison to prevent timing attacks
    if (!hash_equals($expectedB64, $b64Sig)) return false;

    $payload = json_decode(base64_decode(strtr($b64Payload, '-_', '+/')));
    if (!$payload) return false;

    // Check expiration
    if (isset($payload->exp) && $payload->exp < time()) return false;

    return $payload;
}

// ----------------------------------------------------------------
// TOKEN EXTRACTION — Cookie-first, Bearer fallback
// Strategy: HttpOnly cookie for browser clients, Bearer for mobile/API
// ----------------------------------------------------------------
function get_token(): ?string {
    // 1. Prioritas utama: Bearer token di Authorization header (dikirim eksplisit oleh admin/mobile)
    $authHeader = $_SERVER['HTTP_AUTHORIZATION']
        ?? $_SERVER['Authorization']
        ?? (function_exists('apache_request_headers') ? (apache_request_headers()['Authorization'] ?? null) : null)
        ?? null;

    if ($authHeader && preg_match('/Bearer\s+(\S+)/i', $authHeader, $m)) {
        return $m[1];
    }

    // 2. Fallback: Ambil dari HttpOnly cookie (browser client student)
    if (!empty($_COOKIE['ep_access_token'])) {
        return $_COOKIE['ep_access_token'];
    }

    return null;
}

// ----------------------------------------------------------------
// CSRF PROTECTION — Double Submit Cookie Pattern
// Hanya diperlukan untuk permintaan yang mengubah data (POST/PUT/DELETE)
// dan ketika menggunakan cookie-based auth.
// GET requests dan Bearer token tidak memerlukan CSRF check.
// ----------------------------------------------------------------
function verify_csrf(): bool {
    // Tidak perlu CSRF check jika menggunakan Bearer token (bukan cookie)
    if (empty($_COOKIE['ep_access_token'])) return true;

    // GET/HEAD/OPTIONS tidak memerlukan CSRF check
    $method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
    if (in_array($method, ['GET', 'HEAD', 'OPTIONS'], true)) return true;

    $headerToken = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
    $cookieToken = $_COOKIE['ep_csrf_token'] ?? '';

    if (empty($headerToken) || empty($cookieToken)) return false;

    return hash_equals($cookieToken, $headerToken);
}

// ----------------------------------------------------------------
// SET AUTH COOKIES (dipanggil saat login/register/refresh)
// ----------------------------------------------------------------
function set_auth_cookies(string $access_token, string $csrf_token): void {
    $is_production = (defined('APP_ENV') && APP_ENV === 'production')
        || (defined('MIDTRANS_IS_PRODUCTION') && MIDTRANS_IS_PRODUCTION);

    $ttl = defined('JWT_ACCESS_TTL') ? JWT_ACCESS_TTL : 86400;

    // Access token — HttpOnly Secure SameSite
    setcookie('ep_access_token', $access_token, [
        'expires'  => time() + $ttl,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_production,   // Secure: true hanya di production HTTPS
        'httponly' => true,              // Tidak bisa diakses JavaScript
        'samesite' => 'Lax',            // Lax: aman untuk kebanyakan use case
    ]);

    // CSRF token — Readable oleh JavaScript (tidak HttpOnly)
    setcookie('ep_csrf_token', $csrf_token, [
        'expires'  => time() + $ttl,
        'path'     => '/',
        'domain'   => '',
        'secure'   => $is_production,
        'httponly' => false,             // JavaScript perlu baca ini untuk X-CSRF-Token header
        'samesite' => 'Lax',
    ]);
}

// ----------------------------------------------------------------
// CLEAR AUTH COOKIES (dipanggil saat logout)
// ----------------------------------------------------------------
function clear_auth_cookies(): void {
    $past = time() - 3600;
    foreach (['ep_access_token', 'ep_csrf_token'] as $name) {
        setcookie($name, '', ['expires' => $past, 'path' => '/', 'httponly' => true, 'samesite' => 'Lax']);
    }
}

// ----------------------------------------------------------------
// GENERATE CSRF TOKEN
// ----------------------------------------------------------------
function generate_csrf_token(): string {
    return bin2hex(random_bytes(32));
}

// ----------------------------------------------------------------
// AUTHENTICATE — Verifikasi token & CSRF, return payload
// ----------------------------------------------------------------
function authenticate(): object {
    // CSRF check untuk non-GET cookie-based requests
    if (!verify_csrf()) {
        http_response_code(403);
        echo json_encode(['error' => 'CSRF token tidak valid.']);
        exit;
    }

    $token = get_token();
    if (!$token) {
        http_response_code(401);
        echo json_encode(['error' => 'Token tidak ditemukan. Silakan login kembali.']);
        exit;
    }

    $payload = verify_jwt($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(['error' => 'Token tidak valid atau sudah kadaluarsa. Silakan login kembali.']);
        exit;
    }

    return $payload;
}

// ----------------------------------------------------------------
// LEGACY ALIAS (backward compat — jangan hapus)
// ----------------------------------------------------------------
function get_bearer_token(): ?string {
    return get_token();
}
