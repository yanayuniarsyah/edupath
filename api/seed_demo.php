<?php
// api/seed_demo.php
require_once 'config.php';

try {
    $email = 'demo@edupath.id';
    $password = 'demo123';
    $hashed_password = password_hash($password, PASSWORD_BCRYPT);
    
    // UUID v4 format
    $id = bin2hex(random_bytes(16));
    $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);

    $stmt = $pdo->prepare("SELECT id FROM students WHERE email = ?");
    $stmt->execute([$email]);
    
    if ($stmt->fetch()) {
        // Update
        $update = $pdo->prepare("UPDATE students SET password = ?, is_active = 1 WHERE email = ?");
        $update->execute([$hashed_password, $email]);
        echo json_encode(["success" => true, "message" => "Akun demo berhasil direset. Login dengan email: demo@edupath.id dan password: demo123"]);
    } else {
        // Insert
        $insert = $pdo->prepare("INSERT INTO students (id, name, email, password, target_ptn, is_active) VALUES (?, ?, ?, ?, ?, 1)");
        $insert->execute([$id, 'Siswa Demo', $email, $hashed_password, 'UI - Ilmu Komputer']);
        echo json_encode(["success" => true, "message" => "Akun demo berhasil dibuat. Login dengan email: demo@edupath.id dan password: demo123"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
