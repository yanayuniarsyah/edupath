<?php
// api/migrate_mobile.php
// Runner for EduPath Mobile Readiness Migration
// HARUS dijalankan via CLI saja. Tidak boleh diakses melalui browser/HTTP.

// 1. CLI-only enforcement
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden. Migration harus dijalankan via CLI."]);
    exit;
}

require_once 'config.php';

// Verify the environment matches expectations to prevent accidental execution
if (!isset($db_name) || empty($db_name)) {
    echo "ERROR: Database name not defined in config.\n";
    exit(1);
}
echo "Environment check: Target database is '$db_name'.\n";

try {
    // Ensure PDO uses exceptions
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // 2. Migration Tracking Mechanism
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS schema_migrations (
            id INT AUTO_INCREMENT PRIMARY KEY,
            migration_name VARCHAR(255) NOT NULL UNIQUE,
            applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ");

    $migrationName = 'mobile_readiness_v1';
    
    $stmt = $pdo->prepare("SELECT id FROM schema_migrations WHERE migration_name = :name");
    $stmt->execute([':name' => $migrationName]);
    if ($stmt->fetch()) {
        echo "Migration '$migrationName' has already been applied. Skipping.\n";
        exit(0);
    }

    echo "Applying migration '$migrationName'...\n";

    // 3. Read and Validate Migration File
    $sqlFile = __DIR__ . '/database/migrations/mobile_readiness.sql';
    if (!file_exists($sqlFile) || !is_readable($sqlFile)) {
        echo "ERROR: Migration file not found or not readable: mobile_readiness.sql\n";
        exit(1);
    }

    $sql = file_get_contents($sqlFile);
    if (empty($sql)) {
        echo "ERROR: Migration file is empty.\n";
        exit(1);
    }

    // 4. Execute the main SQL batch (CREATE TABLE IF NOT EXISTS is idempotent)
    // Note: DDL statements in MySQL implicitly commit transactions, so wrapping in a transaction 
    // does not provide full rollback if a middle statement fails. 
    // We rely on IF NOT EXISTS for idempotency.
    $pdo->exec($sql);
    echo "Base tables created successfully.\n";

    // 5. Handle schema changes (ALTER TABLE) safely
    // Check if attempt_id column exists in quiz_results
    $colCheck = $pdo->query("SHOW COLUMNS FROM `quiz_results` LIKE 'attempt_id'");
    if ($colCheck->rowCount() == 0) {
        echo "Adding attempt_id column to quiz_results...\n";
        $pdo->exec("ALTER TABLE `quiz_results` ADD COLUMN `attempt_id` VARCHAR(36) DEFAULT NULL AFTER `student_id`");
        // Add unique index safely by checking if it exists
        $idxCheck = $pdo->query("SHOW INDEX FROM `quiz_results` WHERE Key_name = 'idx_attempt_id'");
        if ($idxCheck->rowCount() == 0) {
            $pdo->exec("ALTER TABLE `quiz_results` ADD UNIQUE INDEX `idx_attempt_id` (`attempt_id`)");
        }
    } else {
        echo "Column attempt_id already exists in quiz_results.\n";
    }

    // 6. Verification
    $requiredTables = [
        'refresh_tokens', 'device_tokens', 'spp_diagnostic_questions', 
        'target_universities', 'target_programs', 'quiz_attempts', 'rate_limits'
    ];
    foreach ($requiredTables as $table) {
        $check = $pdo->query("SHOW TABLES LIKE '$table'");
        if ($check->rowCount() == 0) {
            throw new Exception("Verification failed: Table '$table' was not created.");
        }
    }

    // Record migration success
    $stmt = $pdo->prepare("INSERT INTO schema_migrations (migration_name) VALUES (:name)");
    $stmt->execute([':name' => $migrationName]);

    echo "Migration '$migrationName' completed and verified successfully.\n";

} catch (PDOException $e) {
    // 7. Do not expose raw PDO errors
    echo "DATABASE ERROR: The migration failed due to a database exception.\n";
    // Log detailed error internally (simulated here via CLI output, in prod write to log file)
    echo "Log: " . $e->getMessage() . "\n";
    exit(1);
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
    exit(1);
}
