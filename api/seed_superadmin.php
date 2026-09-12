<?php
// api/seed_superadmin.php
require_once 'config.php';

try {
    $pdo->beginTransaction();

    $email = 'superadmin@edupath.local';
    $password = 'superadmin123';
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    $user_id = 'sa-0000-0000-0000-000000000000';

    // 1. Cek apakah superadmin sudah ada di tabel users
    $stmt = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        // Update password jika sudah ada
        $update = $pdo->prepare("UPDATE users SET password = ? WHERE identity_key = ?");
        $update->execute([$hashed_password, $email]);
        echo json_encode(["success" => true, "message" => "Password superadmin berhasil direset menjadi 'superadmin123'."]);
    } else {
        // Insert user baru
        $insertUser = $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)");
        $insertUser->execute([$user_id, $email, $hashed_password]);

        // Berikan role superadmin (tanpa tenant)
        $insertRole = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (UUID(), ?, NULL, 'superadmin', ?)");
        $insertRole->execute([$user_id, $user_id]);

        echo json_encode(["success" => true, "message" => "Akun superadmin berhasil dibuat. Email: superadmin@edupath.local, Password: superadmin123"]);
    }
    
    $pdo->commit();
} catch (PDOException $e) {
    $pdo->rollBack();
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
