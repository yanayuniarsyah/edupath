<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'test';
require 'api/config.php';
$tables = ['users', 'students', 'user_roles'];
foreach($tables as $t) {
    try {
        $stmt = $pdo->query("SHOW CREATE TABLE $t");
        echo $stmt->fetch(PDO::FETCH_ASSOC)['Create Table'] . ";\n\n";
    } catch(Exception $e) {}
}
