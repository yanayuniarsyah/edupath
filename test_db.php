<?php
require 'api/config.php';

$user_id = 'test-user-id';
$tenant_id = 'default';
$id = 'test-id-123';
$referral_code = 'TEST1234';

try {
    $stmt = $pdo->prepare("INSERT INTO affiliates (id, user_id, tenant_id, referral_code, commission_rate) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$id, $user_id, $tenant_id, $referral_code, 20.00]);
    echo "Success!";
} catch (PDOException $e) {
    echo "DB Error: " . $e->getMessage();
}
