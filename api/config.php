<?php
// api/config.php
// Konfigurasi utama backend EDUPath
// JANGAN masukkan secret asli ke file ini.
// Semua secret dibaca dari environment variable (file .env atau server config).

require_once __DIR__ . '/env.php';

// --- Load .env ---
load_env(__DIR__ . '/.env');

// ----------------------------------------------------------------
$allowed_origins_raw = env('ALLOWED_ORIGINS', 'http://localhost:5173');
$allowed_origins = array_map('trim', explode(',', $allowed_origins_raw));
$request_origin  = $_SERVER['HTTP_ORIGIN'] ?? '';

if (in_array($request_origin, $allowed_origins)) {
    header("Access-Control-Allow-Origin: $request_origin");
} else {
    // Default fallback for local dev if missing origin, or allow * in pure dev
    header("Access-Control-Allow-Origin: http://localhost:5173");
}

header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Authorization");
header("Access-Control-Allow-Credentials: true");

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
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

// Validasi: pastikan konfigurasi database ada
if (empty($db_user) || empty($db_name)) {
    http_response_code(500);
    // JANGAN ekspos detail error di production
    $is_dev = (env('APP_ENV', 'production') === 'development');
    echo json_encode([
        "error" => "Konfigurasi server tidak lengkap.",
        "detail" => $is_dev ? "DB_USER atau DB_NAME tidak dikonfigurasi di .env" : null
    ]);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host=$host;dbname=$db_name;charset=utf8mb4",
        $db_user,
        $db_pass
    );
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);
} catch (PDOException $e) {
    http_response_code(500);
    $is_dev = (env('APP_ENV', 'production') === 'development');
    echo json_encode([
        "error" => "Koneksi database gagal.",
        // Hanya tampilkan detail error di development
        "detail" => $is_dev ? $e->getMessage() : null
    ]);
    exit;
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
    $is_dev = (env('APP_ENV', 'production') === 'development');
    echo json_encode([
        "error" => "Konfigurasi autentikasi tidak lengkap.",
        "detail" => $is_dev ? "JWT_SECRET tidak dikonfigurasi di .env" : null
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
function log_audit(PDO $pdo, ?string $actor_id, ?string $tenant_id, string $action, ?string $target_id = null, array $metadata = []) {
    try {
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        // Cek secara aman apakah tabel audit_logs sudah ada (graceful fallback)
        $stmt = $pdo->prepare("INSERT INTO audit_logs (id, actor_id, tenant_id, action, target_id, metadata) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$id, $actor_id, $tenant_id, $action, $target_id, json_encode($metadata)]);
    } catch (Exception $e) {
        // Silently fail agar aplikasi tidak terganggu jika audit table belum ter-migrate
    }
}
