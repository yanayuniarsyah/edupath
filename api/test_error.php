<?php
// api/test_error.php — Bulletproof diagnostic script with top-level error handlers
error_reporting(E_ALL);
ini_set('display_errors', 1);

set_exception_handler(function(\Throwable $e) {
    echo "\n[UNHANDLED EXCEPTION] " . get_class($e) . ": " . $e->getMessage() . "\n";
    echo "File: " . $e->getFile() . ":" . $e->getLine() . "\n";
    exit(0);
});

set_error_handler(function($severity, $message, $file, $line) {
    echo "\n[PHP ERROR $severity] $message ($file:$line)\n";
    return true;
});

header('Content-Type: text/plain; charset=UTF-8');
http_response_code(200);

echo "=== STEP 1: PARSING .ENV ===\n";
require_once __DIR__ . '/env.php';
load_env(__DIR__ . '/.env');
load_env(dirname(__DIR__) . '/.env');

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER');
$pass = env('DB_PASSWORD');
$db   = env('DB_NAME');

echo "DB_HOST: " . ($host ?: 'EMPTY') . "\n";
echo "DB_USER: " . ($user ?: 'EMPTY') . "\n";
echo "DB_NAME: " . ($db ?: 'EMPTY') . "\n";
echo "DB_PASS length: " . strlen($pass) . "\n";

echo "\n=== STEP 2: TESTING PDO LOCALHOST ===\n";
try {
    $t0 = microtime(true);
    $pdo = new PDO("mysql:host=localhost;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3
    ]);
    $ms = round((microtime(true) - $t0) * 1000, 1);
    echo "SUCCESS in {$ms}ms!\n";
    $stmt = $pdo->query("SELECT VERSION() as ver, USER() as usr, DATABASE() as db");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (\Throwable $t) {
    echo "PDO LOCALHOST FAILED: [" . get_class($t) . "] " . $t->getMessage() . "\n";
}

echo "\n=== STEP 3: TESTING PDO 127.0.0.1 ===\n";
try {
    $t0 = microtime(true);
    $pdo = new PDO("mysql:host=127.0.0.1;port=3306;dbname=$db;charset=utf8mb4", $user, $pass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_TIMEOUT => 3
    ]);
    $ms = round((microtime(true) - $t0) * 1000, 1);
    echo "SUCCESS in {$ms}ms!\n";
    $stmt = $pdo->query("SELECT 1 as ping");
    print_r($stmt->fetch(PDO::FETCH_ASSOC));
} catch (\Throwable $t) {
    echo "PDO 127.0.0.1 FAILED: [" . get_class($t) . "] " . $t->getMessage() . "\n";
}

echo "\n=== STEP 4: TESTING MYSQLI WITH EXCEPTION HANDLING ===\n";
try {
    $t0 = microtime(true);
    mysqli_report(MYSQLI_REPORT_OFF); // Disable fatal uncaught exceptions
    $m = mysqli_init();
    mysqli_options($m, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $ok = @mysqli_real_connect($m, "localhost", $user, $pass, $db);
    if ($ok) {
        $ms = round((microtime(true) - $t0) * 1000, 1);
        echo "MYSQLI SUCCESS in {$ms}ms! Server: " . mysqli_get_server_info($m) . "\n";
        mysqli_close($m);
    } else {
        echo "MYSQLI FAILED: " . mysqli_connect_error() . " (error code: " . mysqli_connect_errno() . ")\n";
    }
} catch (\Throwable $t) {
    echo "MYSQLI THREW: [" . get_class($t) . "] " . $t->getMessage() . "\n";
}

echo "\n=== ALL TESTS FINISHED ===\n";
