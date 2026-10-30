<?php
// api/database/migrations/phase10_shared_product_catalog.php
// Migration runner for Phase 10 — Shared Product Catalog

require_once dirname(__DIR__, 2) . '/config.php';

function run_phase10_migration(PDO $pdo): array {
    $results = [];

    // 1. academic_programs table
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS academic_programs (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                code VARCHAR(50) NOT NULL UNIQUE,
                name VARCHAR(100) NOT NULL,
                description TEXT NULL,
                target_level ENUM('SD', 'SMP', 'SMA', 'ALUMNI') NOT NULL DEFAULT 'SMA',
                subject_config JSON NULL COMMENT 'Konfigurasi mata pelajaran pilihan atau peminatan versioned',
                status ENUM('active', 'inactive') NOT NULL DEFAULT 'active',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $results[] = "Table 'academic_programs' verified/created.";

        // Seed 5 academic programs
        $stmt = $pdo->prepare("
            INSERT IGNORE INTO academic_programs (id, code, name, description, target_level, status) VALUES
            ('prog-tka-sd', 'TKA_SD', 'TKA SD', 'Program Tes Kemampuan Akademik tingkat Sekolah Dasar (Literasi & Numerasi Dasar)', 'SD', 'active'),
            ('prog-tka-smp', 'TKA_SMP', 'TKA SMP', 'Program Tes Kemampuan Akademik tingkat SMP (Penalaran Matematika & Literasi)', 'SMP', 'active'),
            ('prog-tka-sma', 'TKA_SMA', 'TKA SMA', 'Program Tes Kemampuan Akademik tingkat SMA dengan konfigurasi mata pelajaran adaptif', 'SMA', 'active'),
            ('prog-snpmb-utbk', 'SNPMB_UTBK', 'SNPMB/UTBK', 'Persiapan Seleksi Nasional Berdasarkan Tes PTN (TPS, Literasi B. Indonesia & Inggris, Penalaran Matematika)', 'SMA', 'active'),
            ('prog-snpmb-alumni', 'SNPMB_ALUMNI', 'SNPMB Alumni', 'Program intensif alumni/gap year dengan kurikulum pendalaman materi & mentoring khusus', 'ALUMNI', 'active');
        ");
        $stmt->execute();
        $results[] = "Academic programs seeded (TKA SD, SMP, SMA, SNPMB/UTBK, Alumni).";
    } catch (\Throwable $e) {
        $results[] = "Error on academic_programs: " . $e->getMessage();
    }

    // 2. Extend plans table with catalog columns
    try {
        $cols = $pdo->query("DESCRIBE plans")->fetchAll(PDO::FETCH_COLUMN);

        $toAdd = [
            'program_id'       => "ALTER TABLE plans ADD COLUMN program_id VARCHAR(36) NULL AFTER tenant_id",
            'academic_program' => "ALTER TABLE plans ADD COLUMN academic_program VARCHAR(50) NOT NULL DEFAULT 'SNPMB/UTBK' AFTER program_id",
            'package_code'     => "ALTER TABLE plans ADD COLUMN package_code VARCHAR(50) NULL AFTER academic_program",
            'delivery_mode'    => "ALTER TABLE plans ADD COLUMN delivery_mode ENUM('online', 'offline', 'hybrid') NOT NULL DEFAULT 'online' AFTER package_code",
            'components'       => "ALTER TABLE plans ADD COLUMN components JSON NULL AFTER features",
            'base_price'       => "ALTER TABLE plans ADD COLUMN base_price DECIMAL(15,2) NULL AFTER price",
            'currency'         => "ALTER TABLE plans ADD COLUMN currency VARCHAR(3) NOT NULL DEFAULT 'IDR' AFTER base_price",
            'duration_value'   => "ALTER TABLE plans ADD COLUMN duration_value INT NULL AFTER duration",
            'duration_unit'    => "ALTER TABLE plans ADD COLUMN duration_unit ENUM('days', 'months') NOT NULL DEFAULT 'days' AFTER duration_value",
            'status'           => "ALTER TABLE plans ADD COLUMN status ENUM('draft', 'published', 'archived') NOT NULL DEFAULT 'published' AFTER is_active",
            'version'          => "ALTER TABLE plans ADD COLUMN version INT NOT NULL DEFAULT 1 AFTER status",
            'effective_from'   => "ALTER TABLE plans ADD COLUMN effective_from DATETIME NULL AFTER version",
            'effective_until'  => "ALTER TABLE plans ADD COLUMN effective_until DATETIME NULL AFTER effective_from"
        ];

        foreach ($toAdd as $col => $sql) {
            if (!in_array($col, $cols)) {
                $pdo->exec($sql);
                $results[] = "Added column '$col' to 'plans'.";
            }
        }

        // Sync baseline
        $pdo->exec("UPDATE plans SET base_price = price WHERE base_price IS NULL");
        $pdo->exec("UPDATE plans SET duration_value = duration WHERE duration_value IS NULL");
        $pdo->exec("UPDATE plans SET program_id = 'prog-snpmb-utbk' WHERE program_id IS NULL AND academic_program = 'SNPMB/UTBK'");
        $results[] = "Baseline data on 'plans' synced successfully.";
    } catch (\Throwable $e) {
        $results[] = "Error on extending plans: " . $e->getMessage();
    }

    // 3. tenant_product_overrides table
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS tenant_product_overrides (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                tenant_id VARCHAR(36) NOT NULL,
                plan_id VARCHAR(36) NOT NULL,
                override_price DECIMAL(15,2) NOT NULL,
                currency VARCHAR(3) NOT NULL DEFAULT 'IDR',
                reason VARCHAR(255) NOT NULL,
                authorized_by VARCHAR(36) NOT NULL,
                status ENUM('active', 'expired', 'revoked') NOT NULL DEFAULT 'active',
                effective_from DATETIME DEFAULT CURRENT_TIMESTAMP,
                effective_until DATETIME NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                UNIQUE KEY uq_tenant_plan_override (tenant_id, plan_id),
                KEY tpo_tenant_fk (tenant_id),
                KEY tpo_plan_fk (plan_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $results[] = "Table 'tenant_product_overrides' verified/created.";
    } catch (\Throwable $e) {
        $results[] = "Error on tenant_product_overrides: " . $e->getMessage();
    }

    return $results;
}

if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $res = run_phase10_migration($pdo);
    if (php_sapi_name() === 'cli') {
        echo implode("\n", $res) . "\nMigration Phase 10 Done.\n";
    } else {
        echo json_encode(["success" => true, "results" => $res]);
    }
}
