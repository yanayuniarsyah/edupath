<?php
require 'config.php';

try {
    $pdo->beginTransaction();

    $hash_password123 = password_hash('password123', PASSWORD_BCRYPT);
    $hash_demo123 = password_hash('demo123', PASSWORD_BCRYPT);

    // Update UAT accounts
    $emails_password123 = [
        'superadmin@uat.edupath.local',
        'admin@uat.edupath.local',
        'student@uat.edupath.local'
    ];

    foreach ($emails_password123 as $email) {
        $stmt = $pdo->prepare("UPDATE users SET password = :pwd WHERE identity_key = :email");
        $stmt->execute(['pwd' => $hash_password123, 'email' => $email]);
        echo "Updated $email with password123. Rows affected: " . $stmt->rowCount() . "\n";
    }

    // Also update admins and students tables if they duplicate password
    $stmt = $pdo->prepare("UPDATE admins SET password = :pwd WHERE username IN ('admin@uat.edupath.local', 'tutor@uat.edupath.local')");
    $stmt->execute(['pwd' => $hash_password123]);
    echo "Updated admins table. Rows affected: " . $stmt->rowCount() . "\n";

    $stmt = $pdo->prepare("UPDATE students SET password = :pwd WHERE email = 'student@uat.edupath.local'");
    $stmt->execute(['pwd' => $hash_password123]);
    echo "Updated students table. Rows affected: " . $stmt->rowCount() . "\n";


    // Update Demo account
    $stmt = $pdo->prepare("UPDATE users SET password = :pwd WHERE identity_key = 'demo@edupath.id'");
    $stmt->execute(['pwd' => $hash_demo123]);
    echo "Updated demo@edupath.id with demo123 in users. Rows affected: " . $stmt->rowCount() . "\n";

    $stmt = $pdo->prepare("UPDATE students SET password = :pwd WHERE email = 'demo@edupath.id'");
    $stmt->execute(['pwd' => $hash_demo123]);
    echo "Updated demo@edupath.id with demo123 in students. Rows affected: " . $stmt->rowCount() . "\n";

    $pdo->commit();
    echo "Done updating passwords.\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}
