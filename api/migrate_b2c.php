<?php
require 'config.php';

try {
    // 1. Create products table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS products (
            id VARCHAR(36) PRIMARY KEY,
            tenant_id VARCHAR(36) NOT NULL,
            code VARCHAR(50) NOT NULL,
            name VARCHAR(100) NOT NULL,
            description TEXT,
            status VARCHAR(20) DEFAULT 'active',
            UNIQUE KEY product_code_tenant_uq (code, tenant_id),
            CONSTRAINT prod_tenant_fk FOREIGN KEY (tenant_id) REFERENCES tenants(id) ON DELETE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");

    // 2. Add columns to plans
    try {
        $pdo->exec("ALTER TABLE plans ADD COLUMN product_id VARCHAR(36) NULL;");
    } catch(PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE plans ADD COLUMN billing_cycle VARCHAR(20) DEFAULT 'monthly';");
    } catch(PDOException $e) {}
    try {
        $pdo->exec("ALTER TABLE plans ADD CONSTRAINT fk_plan_product FOREIGN KEY (product_id) REFERENCES products(id) ON DELETE CASCADE;");
    } catch(PDOException $e) {}

    // 3. Add columns to orders
    try {
        $pdo->exec("ALTER TABLE orders ADD COLUMN billing_cycle_snapshot VARCHAR(20) NULL;");
    } catch(PDOException $e) {}

    // Seed data with transaction since it's DML
    $pdo->beginTransaction();

    $stmt = $pdo->query("SELECT id FROM tenants LIMIT 1");
    $tenant = $stmt->fetch();
    if ($tenant) {
        $tenant_id = $tenant['id'];
        
        $mkuuid = function() {
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
        };

        $products = [
            ['code' => 'MANDIRI', 'name' => 'Paket Mandiri', 'desc' => 'Paket belajar mandiri dengan akses soal latihan dasar.'],
            ['code' => 'UTAMA',   'name' => 'Paket Utama',   'desc' => 'Paket all-in-one paling direkomendasikan untuk akselerasi belajar adaptif.'],
            ['code' => 'VIP',     'name' => 'Paket VIP',     'desc' => 'Paket eksklusif dengan semua fitur premium.']
        ];

        foreach ($products as $p) {
            $check = $pdo->prepare("SELECT id FROM products WHERE code = ? AND tenant_id = ?");
            $check->execute([$p['code'], $tenant_id]);
            if (!$check->fetch()) {
                $pid = $mkuuid();
                $insert = $pdo->prepare("INSERT INTO products (id, tenant_id, code, name, description) VALUES (?, ?, ?, ?, ?)");
                $insert->execute([$pid, $tenant_id, $p['code'], $p['name'], $p['desc']]);
            }
        }

        $existingPlans = $pdo->query("SELECT id, name FROM plans WHERE product_id IS NULL");
        foreach ($existingPlans as $plan) {
            $nameLower = strtolower($plan['name']);
            $code = null;
            if (strpos($nameLower, 'mandiri') !== false) $code = 'MANDIRI';
            elseif (strpos($nameLower, 'utama') !== false) $code = 'UTAMA';
            elseif (strpos($nameLower, 'vip') !== false) $code = 'VIP';

            if ($code) {
                $stmt = $pdo->prepare("SELECT id FROM products WHERE code = ? AND tenant_id = ?");
                $stmt->execute([$code, $tenant_id]);
                $prod = $stmt->fetch();
                if ($prod) {
                    $stmt_dur = $pdo->prepare("SELECT duration FROM plans WHERE id = ?");
                    $stmt_dur->execute([$plan['id']]);
                    $dur_row = $stmt_dur->fetch();
                    $billing_cycle = ($dur_row && (int)$dur_row['duration'] >= 365) ? 'yearly' : 'monthly';

                    $update = $pdo->prepare("UPDATE plans SET product_id = ?, billing_cycle = ? WHERE id = ?");
                    $update->execute([$prod['id'], $billing_cycle, $plan['id']]);
                }
            }
        }
    }

    $pdo->commit();
    echo "Migration completed successfully.\n";
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Migration failed: " . $e->getMessage() . "\n";
}
