<?php
// api/test_probe.php — Isolated probe: NO DB CONNECTION
header('Content-Type: text/plain');

echo "1. PHP ALIVE\n";
echo "PHP Version: " . PHP_VERSION . "\n";
echo "PHP SAPI: " . php_sapi_name() . "\n";
echo "OS: " . PHP_OS . "\n";

echo "2. EXTENSIONS:\n";
$exts = ['pdo', 'pdo_mysql', 'mysqli', 'mysqlnd', 'openssl', 'json'];
foreach ($exts as $e) {
    echo "  - $e: " . (extension_loaded($e) ? 'YES' : 'NO') . "\n";
}

echo "3. ENV CHECK:\n";
require_once __DIR__ . '/env.php';
$root_env = dirname(__DIR__) . '/.env';
$api_env = __DIR__ . '/.env';

echo "  - root .env exists: " . (file_exists($root_env) ? 'YES' : 'NO') . "\n";
echo "  - api .env exists: " . (file_exists($api_env) ? 'YES' : 'NO') . "\n";

load_env($api_env);
load_env($root_env);

echo "  - DB_HOST: " . env('DB_HOST', 'NOT SET') . "\n";
echo "  - DB_USER: " . env('DB_USER', 'NOT SET') . "\n";
echo "  - DB_NAME: " . env('DB_NAME', 'NOT SET') . "\n";
echo "  - DB_PASSWORD set: " . (!empty(env('DB_PASSWORD')) ? 'YES' : 'NO') . "\n";
echo "  - JWT_SECRET set: " . (!empty(env('JWT_SECRET')) ? 'YES' : 'NO') . "\n";

echo "4. PROBE FINISHED SUCCESSFULLY\n";
