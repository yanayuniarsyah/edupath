<?php
// api/test_sock.php — Socket and Port probe via pure fsockopen (NO MYSQL DRIVER)
header('Content-Type: text/plain');

echo "=== 1. DEFAULT SOCKET INI ===\n";
echo "mysqli.default_socket: " . ini_get('mysqli.default_socket') . "\n";
echo "pdo_mysql.default_socket: " . ini_get('pdo_mysql.default_socket') . "\n";

echo "\n=== 2. SOCKET FILE CHECK ===\n";
$sockets = [
    '/var/lib/mysql/mysql.sock',
    '/tmp/mysql.sock',
    '/var/run/mysqld/mysqld.sock',
    '/tmp/mysql.sock.lock'
];
foreach ($sockets as $s) {
    echo "  - $s: exists=" . (file_exists($s) ? 'YES' : 'NO') . " readable=" . (is_readable($s) ? 'YES' : 'NO') . " writable=" . (is_writable($s) ? 'YES' : 'NO') . "\n";
}

echo "\n=== 3. FSOCKOPEN TCP (127.0.0.1:3306) ===\n";
$errno = 0; $errstr = '';
$t0 = microtime(true);
$fp = @fsockopen("127.0.0.1", 3306, $errno, $errstr, 2);
$elapsed = round((microtime(true) - $t0) * 1000, 1);
if ($fp) {
    echo "TCP 127.0.0.1:3306 OPEN ($elapsed ms)!\n";
    $banner = fread($fp, 64);
    echo "Server greeting banner: " . bin2hex(substr($banner, 0, 16)) . "...\n";
    fclose($fp);
} else {
    echo "TCP 127.0.0.1:3306 FAILED ($elapsed ms): [$errno] $errstr\n";
}

echo "\n=== 4. FSOCKOPEN UNIX SOCKETS ===\n";
foreach (['/var/lib/mysql/mysql.sock', '/tmp/mysql.sock'] as $sock) {
    $errno = 0; $errstr = '';
    $fp = @fsockopen("unix://$sock", 0, $errno, $errstr, 2);
    if ($fp) {
        echo "Socket $sock OPEN!\n";
        $banner = fread($fp, 64);
        echo "Banner: " . bin2hex(substr($banner, 0, 16)) . "...\n";
        fclose($fp);
    } else {
        echo "Socket $sock FAILED: [$errno] $errstr\n";
    }
}

echo "\nPROBE COMPLETE\n";
