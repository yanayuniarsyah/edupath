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
if (!empty($_SERVER['DOCUMENT_ROOT'])) {
    load_env($_SERVER['DOCUMENT_ROOT'] . '/.env');
    load_env($_SERVER['DOCUMENT_ROOT'] . '/api/.env');
}
load_env(dirname(dirname(__DIR__)) . '/.env');

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
        "success" => false,
        "error" => "Konfigurasi server tidak lengkap: DB_USER atau DB_NAME belum terbaca di .env cPanel.",
        "status" => "error",
        "debug_paths" => [
            "api_dir" => __DIR__,
            "root_dir" => dirname(__DIR__),
            "api_env_exists" => file_exists(__DIR__ . '/.env'),
            "root_env_exists" => file_exists(dirname(__DIR__) . '/.env')
        ]
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
            "success" => false,
            "error" => "Koneksi database gagal. Periksa kredensial MySQL di .env hosting.",
            "detail" => $eFallback->getMessage(),
            "host_tried" => $primaryHost,
            "fallback_tried" => $fallbackHost
        ]);
        exit;
    }
}

// ----------------------------------------------------------------
// AUTO-HEAL CORE SCHEMA (Ensures critical tables & columns exist)
// ----------------------------------------------------------------
function ensure_core_schema_sync($pdo) {
    static $synced = false;
    if ($synced) return;
    $synced = true;

    try {
        // 1. users table & display_name
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS users (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                identity_key VARCHAR(150) NOT NULL UNIQUE,
                display_name VARCHAR(150) NULL,
                password VARCHAR(255) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $userCols = $pdo->query("DESCRIBE users")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('display_name', $userCols)) {
            $pdo->exec("ALTER TABLE users ADD COLUMN display_name VARCHAR(150) NULL AFTER identity_key");
        }
    } catch (\Throwable $e) {}

    try {
        // 2. tenants table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tenants (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                name VARCHAR(150) NOT NULL,
                slug VARCHAR(100) NOT NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        // 3. user_roles table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS user_roles (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                user_id VARCHAR(36) NOT NULL,
                tenant_id VARCHAR(36) NULL,
                role VARCHAR(50) NOT NULL,
                reference_id VARCHAR(36) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (user_id),
                INDEX (tenant_id),
                INDEX (role)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        // 4. admins table columns
        $admCols = $pdo->query("DESCRIBE admins")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('name', $admCols)) {
            $pdo->exec("ALTER TABLE admins ADD COLUMN name VARCHAR(100) NULL");
        }
        if (!in_array('tenant_id', $admCols)) {
            $pdo->exec("ALTER TABLE admins ADD COLUMN tenant_id VARCHAR(36) NULL");
        }
    } catch (\Throwable $e) {}

    try {
        // 5. students table columns
        $stuCols = $pdo->query("DESCRIBE students")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('tenant_id', $stuCols)) {
            $pdo->exec("ALTER TABLE students ADD COLUMN tenant_id VARCHAR(36) NULL");
        }
        if (!in_array('school_id', $stuCols)) {
            $pdo->exec("ALTER TABLE students ADD COLUMN school_id VARCHAR(36) NULL");
        }
    } catch (\Throwable $e) {}

    try {
        // 6. audit_logs table
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS audit_logs (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                actor_id VARCHAR(36) NULL,
                tenant_id VARCHAR(36) NULL,
                action VARCHAR(100) NOT NULL,
                target_id VARCHAR(36) NULL,
                metadata JSON NULL,
                ip_address VARCHAR(45) NULL,
                user_agent TEXT NULL,
                level VARCHAR(20) DEFAULT 'INFO',
                signature VARCHAR(64) NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                INDEX (actor_id),
                INDEX (action)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}
}
ensure_core_schema_sync($pdo);

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
    echo json_encode([
        "success" => false,
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
