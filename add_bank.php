<?php
$envContent = file_get_contents('.env');
preg_match('/DB_HOST=(.*)/', $envContent, $host);
preg_match('/DB_NAME=(.*)/', $envContent, $db);
preg_match('/DB_USER=(.*)/', $envContent, $user);
preg_match('/DB_PASSWORD=(.*)/', $envContent, $pass);

$h = trim($host[1]??'');
$d = trim($db[1]??'');
$u = trim($user[1]??'');
$p = trim($pass[1]??'');

$pdo = new PDO("mysql:host=$h;dbname=$d;charset=utf8mb4", $u, $p);
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

$cols = ['bank_name' => 'VARCHAR(50)', 'bank_account' => 'VARCHAR(50)', 'bank_owner' => 'VARCHAR(100)'];

foreach ($cols as $col => $type) {
    try {
        $pdo->exec("ALTER TABLE affiliates ADD COLUMN $col $type NULL");
        echo "Added $col\n";
    } catch (Exception $e) {
        echo "Error $col: " . $e->getMessage() . "\n";
    }
}
