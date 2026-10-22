<?php
// api/test_mysql.php — Tests DB connection via mysqli & PDO isolated
header('Content-Type: text/plain');

require_once __DIR__ . '/env.php';
load_env(__DIR__ . '/.env');
load_env(dirname(__DIR__) . '/.env');

$host = env('DB_HOST', 'localhost');
$user = env('DB_USER');
$pass = env('DB_PASSWORD');
$db   = env('DB_NAME');

echo "=== 1. MYSQLI TEST (host: $host) ===\n";
$m = @mysqli_init();
if ($m) {
    mysqli_options($m, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $ok = @mysqli_real_connect($m, $host, $user, $pass, $db);
    if ($ok) {
        echo "MYSQLI SUCCESS! Server version: " . mysqli_get_server_info($m) . "\n";
        mysqli_close($m);
    } else {
        echo "MYSQLI FAILED: " . mysqli_connect_error() . " (code " . mysqli_connect_errno() . ")\n";
    }
} else {
    echo "mysqli_init failed\n";
}

echo "\n=== 2. MYSQLI LOCALHOST SOCKET TEST ===\n";
$m2 = @mysqli_init();
if ($m2) {
    mysqli_options($m2, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $ok2 = @mysqli_real_connect($m2, "localhost", $user, $pass, $db);
    if ($ok2) {
        echo "MYSQLI LOCALHOST SUCCESS! Server version: " . mysqli_get_server_info($m2) . "\n";
        mysqli_close($m2);
    } else {
        echo "MYSQLI LOCALHOST FAILED: " . mysqli_connect_error() . " (code " . mysqli_connect_errno() . ")\n";
    }
}

echo "\n=== 3. MYSQLI 127.0.0.1 TCP TEST ===\n";
$m3 = @mysqli_init();
if ($m3) {
    mysqli_options($m3, MYSQLI_OPT_CONNECT_TIMEOUT, 3);
    $ok3 = @mysqli_real_connect($m3, "127.0.0.1", $user, $pass, $db, 3306);
    if ($ok3) {
        echo "MYSQLI 127.0.0.1 SUCCESS! Server version: " . mysqli_get_server_info($m3) . "\n";
        mysqli_close($m3);
    } else {
        echo "MYSQLI 127.0.0.1 FAILED: " . mysqli_connect_error() . " (code " . mysqli_connect_errno() . ")\n";
    }
}
