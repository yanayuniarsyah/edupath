<?php
// api/seed_admin.php
require_once 'config.php';

try {
    // Buat tabel admins jika belum ada
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS admins (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL UNIQUE,
            password VARCHAR(255) NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $username = 'admin';
    $password = 'demo123';
    $hashed_password = password_hash($password, PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("SELECT id FROM admins WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        // Update password jika sudah ada
        $update = $pdo->prepare("UPDATE admins SET password = ? WHERE username = ?");
        $update->execute([$hashed_password, $username]);
        echo json_encode(["success" => true, "message" => "Password admin berhasil direset menjadi 'demo123'."]);
    } else {
        // Insert admin baru
        $insert = $pdo->prepare("INSERT INTO admins (username, password) VALUES (?, ?)");
        $insert->execute([$username, $hashed_password]);
        echo json_encode(["success" => true, "message" => "Akun admin berhasil dibuat. Username: admin, Password: demo123"]);
    }

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
