<?php
require_once __DIR__ . '/api/config.php';

echo "<h1>EduPath Database Installer</h1>";

try {
    // 1. Read JSON file
    $json = file_get_contents(__DIR__ . '/schema_dump.json');
    if (!$json) {
        throw new Exception("File schema_dump.json tidak ditemukan atau tidak bisa dibaca.");
    }

    $schema = json_decode($json, true);
    if (!$schema) {
        throw new Exception("Format schema_dump.json tidak valid.");
    }

    echo "<h3>Membuat tabel-tabel database...</h3><ul>";

    // 2. Disable foreign key checks temporarily so order doesn't matter
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 0;");

    // 3. Loop through and execute each CREATE TABLE statement
    foreach ($schema as $table_name => $sql) {
        try {
            // Check if table exists
            $result = $pdo->query("SHOW TABLES LIKE '$table_name'");
            if ($result->rowCount() > 0) {
                echo "<li>⚠️ Tabel <b>$table_name</b> sudah ada. (Dilewati)</li>";
            } else {
                $pdo->exec($sql);
                echo "<li>✅ Tabel <b>$table_name</b> berhasil dibuat.</li>";
            }
        } catch (PDOException $e) {
            echo "<li>❌ Gagal membuat tabel <b>$table_name</b>: " . htmlspecialchars($e->getMessage()) . "</li>";
        }
    }

    // 4. Re-enable foreign key checks
    $pdo->exec("SET FOREIGN_KEY_CHECKS = 1;");

    echo "</ul>";

    // 5. Insert default tenant (required for B2C)
    $tenant_id = "default_tenant";
    $stmt = $pdo->prepare("SELECT id FROM tenants WHERE id = ?");
    $stmt->execute([$tenant_id]);
    if (!$stmt->fetch()) {
        $pdo->prepare("INSERT INTO tenants (id, name, slug) VALUES (?, 'Default EduPath Tenant', 'default')")->execute([$tenant_id]);
        echo "<p>✅ Default Tenant berhasil ditambahkan.</p>";
    }

    // 6. Insert default admin (if table admins exists and is empty)
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM admins");
        if ($stmt->fetchColumn() == 0) {
            $admin_id = "ADM-UAT-" . bin2hex(random_bytes(4));
            $admin_email = "admin.uat@edupath.id";
            $admin_pass = password_hash("admin123", PASSWORD_BCRYPT);
            $pdo->prepare("INSERT INTO admins (id, username, password_hash, role) VALUES (?, ?, ?, 'superadmin')")
                ->execute([$admin_id, $admin_email, $admin_pass]);
            echo "<p>✅ Superadmin dibuat: <b>$admin_email</b> (Pass: admin123)</p>";
        }
    } catch (PDOException $e) {
        // Table admins might not exist in the old dump, ignore.
    }

    echo "<h3>Instalasi Database Selesai!</h3>";
    echo "<p style='color:red;'><b>PENTING:</b> Harap segera hapus file <code>install_db.php</code> ini untuk keamanan!</p>";

} catch (Exception $e) {
    echo "<p style='color:red;'><b>Error:</b> " . htmlspecialchars($e->getMessage()) . "</p>";
}
