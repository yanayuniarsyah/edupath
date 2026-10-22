<?php
// api/test_status.php — Diagnostic probe to diagnose 503 / DB / Env issues safely
header('Content-Type: application/json');

$results = [
    'timestamp' => date('c'),
    'php_version' => phpversion(),
    'os' => PHP_OS,
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
];

// 1. Check .env files
$root_env = dirname(__DIR__) . '/.env';
$api_env = __DIR__ . '/.env';

$results['env_check'] = [
    'root_env_exists' => file_exists($root_env),
    'root_env_readable' => is_readable($root_env),
    'api_env_exists' => file_exists($api_env),
    'api_env_readable' => is_readable($api_env),
];

// Read env variables safely
require_once __DIR__ . '/env.php';
load_env($api_env);
load_env($root_env);

$db_host = env('DB_HOST', 'localhost');
$db_user = env('DB_USER', 'none');
$db_name = env('DB_NAME', 'none');
$port = env('DB_PORT', '3306');
$jwt_secret = env('JWT_SECRET', '');

$results['env_vars'] = [
    'DB_HOST' => $db_host,
    'DB_USER' => $db_user ? substr($db_user, 0, 4) . '***' : 'EMPTY',
    'DB_NAME' => $db_name ? substr($db_name, 0, 4) . '***' : 'EMPTY',
    'DB_PORT' => $port,
    'JWT_SECRET_SET' => !empty($jwt_secret),
    'APP_ENV' => env('APP_ENV', 'production')
];

// 2. Test DB connections with explicit try/catch and short timeout
$db_pass = env('DB_PASSWORD', '');

// Test Socket / localhost
try {
    $t0 = microtime(true);
    $pdo1 = new PDO("mysql:host=localhost;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);
    $stmt = $pdo1->query("SELECT 1");
    $results['db_localhost'] = [
        'status' => 'OK',
        'time_ms' => round((microtime(true) - $t0) * 1000, 2)
    ];
} catch (\Throwable $e) {
    $results['db_localhost'] = [
        'status' => 'FAIL',
        'error' => $e->getMessage()
    ];
}

// Test TCP 127.0.0.1
try {
    $t0 = microtime(true);
    $pdo2 = new PDO("mysql:host=127.0.0.1;port=$port;dbname=$db_name;charset=utf8mb4", $db_user, $db_pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 2
    ]);
    $stmt = $pdo2->query("SELECT 1");
    $results['db_127_0_0_1'] = [
        'status' => 'OK',
        'time_ms' => round((microtime(true) - $t0) * 1000, 2)
    ];
} catch (\Throwable $e) {
    $results['db_127_0_0_1'] = [
        'status' => 'FAIL',
        'error' => $e->getMessage()
    ];
}

echo json_encode($results, JSON_PRETTY_PRINT);
