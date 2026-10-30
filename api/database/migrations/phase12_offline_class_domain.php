<?php
// api/database/migrations/phase12_offline_class_domain.php
// Migration runner for Phase 12 — Offline Class Domain, Atomic Capacity & Makeup Quota

require_once dirname(__DIR__, 2) . '/config.php';

function run_phase12_migration(PDO $pdo): array {
    $results = [];

    // 1. class_offerings
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS class_offerings (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                tenant_id VARCHAR(36) NOT NULL,
                program_id VARCHAR(36) NULL,
                academic_program VARCHAR(50) NOT NULL DEFAULT 'SNPMB/UTBK',
                plan_id VARCHAR(36) NULL,
                class_id VARCHAR(36) NULL COMMENT 'Cohort / Rombel terhubung',
                name VARCHAR(150) NOT NULL,
                renewal_mode ENUM('new_enrollment', 'extend_existing') NOT NULL DEFAULT 'new_enrollment',
                max_capacity INT NOT NULL DEFAULT 15,
                registration_open_at DATETIME NULL,
                registration_close_at DATETIME NULL,
                status ENUM('draft', 'open', 'closed', 'in_progress', 'completed') NOT NULL DEFAULT 'open',
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY co_tenant_fk (tenant_id),
                KEY co_class_fk (class_id),
                KEY co_plan_fk (plan_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $results[] = "Table 'class_offerings' verified/created.";
    } catch (\Throwable $e) {
        $results[] = "Error on class_offerings: " . $e->getMessage();
    }

    // 2. Extend classes & class_students
    try {
        $classCols = $pdo->query("DESCRIBE classes")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('offering_id', $classCols)) {
            $pdo->exec("ALTER TABLE classes ADD COLUMN offering_id VARCHAR(36) NULL AFTER academic_year");
            $results[] = "Added column 'offering_id' to 'classes'.";
        }

        $csCols = $pdo->query("DESCRIBE class_students")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('renewal_reference_id', $csCols)) {
            $pdo->exec("ALTER TABLE class_students ADD COLUMN renewal_reference_id VARCHAR(36) NULL AFTER status");
            $results[] = "Added column 'renewal_reference_id' to 'class_students'.";
        }
        if (!in_array('confirmed_at', $csCols)) {
            $pdo->exec("ALTER TABLE class_students ADD COLUMN confirmed_at DATETIME NULL AFTER joined_at");
            $results[] = "Added column 'confirmed_at' to 'class_students'.";
        }
    } catch (\Throwable $e) {
        $results[] = "Error extending classes/class_students: " . $e->getMessage();
    }

    // 3. makeup_requests
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS makeup_requests (
                id VARCHAR(36) NOT NULL PRIMARY KEY,
                student_id VARCHAR(36) NOT NULL,
                origin_session_id VARCHAR(36) NOT NULL,
                target_session_id VARCHAR(36) NOT NULL,
                reason TEXT NOT NULL,
                status ENUM('pending', 'approved', 'rejected', 'attended') NOT NULL DEFAULT 'pending',
                approved_by VARCHAR(36) NULL,
                approved_at DATETIME NULL,
                notes TEXT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                KEY mr_student_fk (student_id),
                KEY mr_orig_ses_fk (origin_session_id),
                KEY mr_targ_ses_fk (target_session_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        $results[] = "Table 'makeup_requests' verified/created.";
    } catch (\Throwable $e) {
        $results[] = "Error on makeup_requests: " . $e->getMessage();
    }

    return $results;
}

if (php_sapi_name() === 'cli' || isset($_GET['run'])) {
    $res = run_phase12_migration($pdo);
    if (php_sapi_name() === 'cli') {
        echo implode("\n", $res) . "\nMigration Phase 12 Done.\n";
    } else {
        echo json_encode(["success" => true, "results" => $res]);
    }
}
