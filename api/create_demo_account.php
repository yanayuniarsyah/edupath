<?php
require 'config.php';

try {
    $pdo->beginTransaction();

    $hash_demo123 = password_hash('demo123', PASSWORD_BCRYPT);
    $demo_email = 'demo@edupath.id';
    
    // Check if user exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
    $stmt->execute([$demo_email]);
    $user = $stmt->fetch();
    
    if (!$user) {
        // Create demo account as a student
        $demo_id = 'demo-u-01';
        $demo_ref = 'demo-stu-01';
        $tenant_id = 'uat-tenant-01'; // use UAT tenant or another?
        
        $pdo->exec("INSERT INTO users (id, identity_key, password, is_active) VALUES ('$demo_id', '$demo_email', '$hash_demo123', 1) ON DUPLICATE KEY UPDATE password='$hash_demo123'");
        $pdo->exec("INSERT INTO students (id, name, email, password, tenant_id) VALUES ('$demo_ref', 'Demo User', '$demo_email', '$hash_demo123', '$tenant_id') ON DUPLICATE KEY UPDATE password='$hash_demo123'");
        $pdo->exec("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES ('demo-ur-01', '$demo_id', '$tenant_id', 'student', '$demo_ref') ON DUPLICATE KEY UPDATE role=role");
        
        echo "Created demo account.\n";
    } else {
        echo "Demo account already exists. It was probably just using a different ID.\n";
    }

    $pdo->commit();
    echo "Done.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}
