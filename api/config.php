<?php
// api/config.php
// Konfigurasi utama backend EDUPath
// JANGAN masukkan secret asli ke file ini.
// Semua secret dibaca dari environment variable (file .env atau server config).

require_once __DIR__ . '/env.php';

// --- Load .env ---
error_reporting(E_ALL);
ini_set('display_errors', 0);

// Gunakan file api/.env terlebih dahulu (spesifik backend), lalu fallback ke root .env
load_env(__DIR__ . '/.env');
load_env(dirname(__DIR__) . '/.env');

// ----------------------------------------------------------------
$allowed_origins_raw = env('ALLOWED_ORIGINS', 'http://localhost:5173');
$allowed_origins     = array_map('trim', explode(',', $allowed_origins_raw));
$request_origin      = trim($_SERVER['HTTP_ORIGIN'] ?? '');

if ($request_origin !== '') {
    $origin_host = parse_url($request_origin, PHP_URL_HOST) ?? '';
    if (in_array($request_origin, $allowed_origins, true) || 
        str_ends_with($origin_host, 'edupath.co.id') ||
        str_ends_with($origin_host, 'elyana.biz.id') ||
        str_contains($origin_host, 'localhost') ||
        str_contains($origin_host, '127.0.0.1')) {
        header("Access-Control-Allow-Origin: $request_origin");
        header('Vary: Origin');
    }
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization, X-CSRF-Token");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if (isset($_SERVER['REQUEST_METHOD']) && $_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

header('Content-Type: application/json');

// ----------------------------------------------------------------
// DATABASE — Dari environment variable
// ----------------------------------------------------------------
$host    = env('DB_HOST', 'localhost');
$db_user = env('DB_USER');
$db_pass = env('DB_PASSWORD');
$db_name = env('DB_NAME');
$port    = env('DB_PORT', '3306');

// Validasi: pastikan konfigurasi database ada
if (empty($db_user) || empty($db_name)) {
    http_response_code(500);
    echo json_encode([
        "error" => "Konfigurasi server tidak lengkap: DB_USER atau DB_NAME belum dikonfigurasi di .env cPanel.",
        "status" => "error"
    ]);
    exit;
}

$pdo_options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
    PDO::ATTR_TIMEOUT => 2 // 2s timeout prevents LiteSpeed 503 worker crash
];

// On Linux/cPanel environments, MySQL is accessed via UNIX socket ('localhost')
// 127.0.0.1 forces TCP which is blocked/disabled on cPanel, causing hung processes and LiteSpeed 503
if (PHP_OS_FAMILY !== 'Windows' && $host === '127.0.0.1') {
    $primaryHost  = 'localhost';
    $fallbackHost = '127.0.0.1';
} else {
    $primaryHost  = $host;
    $fallbackHost = ($host === 'localhost') ? '127.0.0.1' : 'localhost';
}

try {
    $dsn = "mysql:host=$primaryHost;dbname=$db_name;charset=utf8mb4";
    if ($primaryHost !== 'localhost' && !empty($port)) {
        $dsn .= ";port=$port";
    }
    $pdo = new PDO($dsn, $db_user, $db_pass, $pdo_options);
} catch (PDOException $e) {
    try {
        $dsnFallback = "mysql:host=$fallbackHost;dbname=$db_name;charset=utf8mb4";
        if ($fallbackHost !== 'localhost' && !empty($port)) {
            $dsnFallback .= ";port=$port";
        }
        $pdo = new PDO($dsnFallback, $db_user, $db_pass, $pdo_options);
    } catch (PDOException $eFallback) {
        http_response_code(500);
        echo json_encode([
            "error" => "Koneksi database gagal. Periksa kredensial MySQL di .env hosting.",
            "detail" => $eFallback->getMessage()
        ]);
        exit;
    }
}

// ----------------------------------------------------------------
// MIDTRANS — Dari environment variable
// ----------------------------------------------------------------
$midtrans_server_key = env('MIDTRANS_SERVER_KEY', '');
$midtrans_is_production = env('MIDTRANS_IS_PRODUCTION', false);

define('MIDTRANS_SERVER_KEY', $midtrans_server_key);
define('MIDTRANS_IS_PRODUCTION', $midtrans_is_production);

// ----------------------------------------------------------------
// JWT — Dari environment variable
// ----------------------------------------------------------------
$jwt_secret = env('JWT_SECRET', '');

if (empty($jwt_secret)) {
    // JWT_SECRET WAJIB ada. Tanpa ini, auth tidak bisa berjalan.
    http_response_code(500);
    echo json_encode([
        "error" => "Konfigurasi autentikasi tidak lengkap: JWT_SECRET belum dikonfigurasi di .env hosting.",
        "status" => "error"
    ]);
    exit;
}

define('JWT_SECRET', $jwt_secret);
define('JWT_ACCESS_TTL', (int) env('JWT_ACCESS_TTL', 86400));         // 24 jam
define('JWT_REFRESH_TTL_DAYS', (int) env('JWT_REFRESH_TTL_DAYS', 30)); // 30 hari
define('APP_ENV', env('APP_ENV', 'production'));

// ----------------------------------------------------------------
// AUDIT LOGGING HELPER
// ----------------------------------------------------------------
function log_audit(PDO $pdo, ?string $actor_id, ?string $tenant_id, string $action, ?string $target_id = null, array $metadata = [], string $level = 'INFO') {
    try {
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        $ip = $_SERVER['REMOTE_ADDR'] ?? null;
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? null;
        // Optional HMAC signature for tamper‑proofing if secret is configured
        $signature = null;
        $secret = env('AUDIT_HMAC_SECRET', null);
        if ($secret) {
            $payload = json_encode([
                'id' => $id,
                'action' => $action,
                'target_id' => $target_id,
                'metadata' => $metadata,
                'ip_address' => $ip,
                'user_agent' => $ua,
                'level' => $level,
                'timestamp' => date('c')
            ]);
            $signature = hash_hmac('sha256', $payload, $secret);
        }
        $stmt = $pdo->prepare("INSERT INTO audit_logs (id, actor_id, tenant_id, action, target_id, metadata, ip_address, user_agent, level, signature) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([
            $id,
            $actor_id,
            $tenant_id,
            $action,
            $target_id,
            json_encode($metadata),
            $ip,
            $ua,
            $level,
            $signature
        ]);
    } catch (Exception $e) {
        // Silently fail agar aplikasi tidak terganggu jika audit table belum ter‑migrate
    }
}
?>
