<?php
// api/test_diag.php — Always returns HTTP 200 with full diagnostics
header('Content-Type: application/json');
http_response_code(200);

$res = [
    'time' => date('Y-m-d H:i:s'),
    'php' => PHP_VERSION,
    'pdo_drivers' => extension_loaded('pdo') ? PDO::getAvailableDrivers() : 'pdo_not_loaded',
];

require_once __DIR__ . '/env.php';

$root_env = dirname(__DIR__) . '/.env';
$api_env = __DIR__ . '/.env';

load_env($api_env);
load_env($root_env);

$res['env_files'] = [
    'root_path' => $root_env,
    'root_exists' => file_exists($root_env),
    'root_readable' => is_readable($root_env),
    'api_path' => $api_env,
    'api_exists' => file_exists($api_env),
    'api_readable' => is_readable($api_env),
];

$raw_pass = env('DB_PASSWORD', '');
$res['config_read'] = [
    'DB_HOST' => env('DB_HOST'),
    'DB_USER' => env('DB_USER'),
    'DB_NAME' => env('DB_NAME'),
    'DB_PASS_LEN' => strlen($raw_pass),
    'DB_PASS_MASKED' => strlen($raw_pass) > 4 ? substr($raw_pass, 0, 2) . '***' . substr($raw_pass, -2) : 'EMPTY_OR_SHORT',
    'JWT_SECRET_LEN' => strlen(env('JWT_SECRET', '')),
];

// Test 1: PDO localhost
try {
    $t0 = microtime(true);
    $pdo = new PDO(
        "mysql:host=localhost;dbname=" . env('DB_NAME') . ";charset=utf8mb4",
        env('DB_USER'),
        $raw_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
    );
    $q = $pdo->query("SELECT 1 as ping, DATABASE() as db, USER() as usr")->fetch(PDO::FETCH_ASSOC);
    $res['test_localhost'] = ['status' => 'OK', 'ms' => round((microtime(true)-$t0)*1000, 1), 'data' => $q];
} catch (\Throwable $e) {
    $res['test_localhost'] = ['status' => 'FAIL', 'error' => $e->getMessage(), 'code' => $e->getCode()];
}

// Test 2: PDO 127.0.0.1
try {
    $t0 = microtime(true);
    $pdo = new PDO(
        "mysql:host=127.0.0.1;port=3306;dbname=" . env('DB_NAME') . ";charset=utf8mb4",
        env('DB_USER'),
        $raw_pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_TIMEOUT => 3]
    );
    $q = $pdo->query("SELECT 1 as ping")->fetch(PDO::FETCH_ASSOC);
    $res['test_127_0_0_1'] = ['status' => 'OK', 'ms' => round((microtime(true)-$t0)*1000, 1)];
} catch (\Throwable $e) {
    $res['test_127_0_0_1'] = ['status' => 'FAIL', 'error' => $e->getMessage(), 'code' => $e->getCode()];
}

// Test 3: Standard MySQL Unix Sockets
$common_sockets = [
    '/var/lib/mysql/mysql.sock',
    '/tmp/mysql.sock',
    '/var/run/mysqld/mysqld.sock'
];
$res['sockets_found'] = [];
foreach ($common_sockets as $sock) {
    if (file_exists($sock)) {
        $res['sockets_found'][] = $sock;
    }
}

echo json_encode($res, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
