<?php
$_SERVER['REQUEST_METHOD'] = 'POST';
$_GET['action'] = 'register';
$_SERVER['HTTP_ORIGIN'] = 'http://localhost:5173';
$_SERVER['REMOTE_ADDR'] = '127.0.0.1';
require 'api/config.php';

try {
    $user_id = bin2hex(random_bytes(16));
    $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
    $id = bin2hex(random_bytes(16));
    $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
    $email = 'test999@example.com';
    $hashed_password = password_hash('123', PASSWORD_BCRYPT);
    $name = 'Test';
    $target_ptn = '';
    $tenant_id = '553af312-fb50-4e24-93ee-0d1abd52a62d';
    $referred_by = null;

    $pdo->beginTransaction();
    $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $email, $hashed_password]);

    $stmt = $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, tenant_id, referred_by) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $stmt->execute([$id, $name, $email, $hashed_password, $target_ptn, $tenant_id, $referred_by]);

    $ur_id = bin2hex(random_bytes(16));
    $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
    $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)");
    $stmt->execute([$ur_id, $user_id, $tenant_id, $id]);

    $pdo->rollBack();
    echo "SUCCESS\n";
} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
