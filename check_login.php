<?php
require 'api/config.php';
$stmt = $pdo->prepare("SELECT password FROM users WHERE identity_key = 'demo@edupath.id'");
$stmt->execute();
$user = $stmt->fetch();
echo $user ? "Demo Found: " . (password_verify('demo123', $user['password']) ? 'Password Match' : 'Password Mismatch') : 'Demo Not found';

echo "\n";

$stmt = $pdo->prepare("SELECT password FROM users WHERE identity_key = 'superadmin@uat.edupath.local'");
$stmt->execute();
$user = $stmt->fetch();
echo $user ? "Superadmin Found: " . (password_verify('password123', $user['password']) ? 'Password Match' : 'Password Mismatch') : 'Superadmin Not found';
echo "\n";
