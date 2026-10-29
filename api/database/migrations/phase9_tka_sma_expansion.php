<?php
// api/database/migrations/phase9_tka_sma_expansion.php
require_once __DIR__ . '/../../config.php';

echo "Running Phase 9: EduPath TKA SMA Assessment Expansion Migration...\n";

function safe_add_column($pdo, $table, $column, $definition) {
    try {
        $stmt = $pdo->query("SHOW COLUMNS FROM `$table` LIKE '$column'");
        if ($stmt->fetch()) {
            echo "ℹ️ Column $table.$column already exists.\n";
            return true;
        }
        $pdo->exec("ALTER TABLE `$table` ADD COLUMN `$column` $definition");
        echo "✅ Column $table.$column added successfully.\n";
        return true;
    } catch (\Throwable $e) {
        echo "⚠️ Notice on $table.$column: " . $e->getMessage() . "\n";
        return false;
    }
}

try {
    // 1. Table questions & materials
    safe_add_column($pdo, 'questions', 'track', "VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`");
    safe_add_column($pdo, 'materials', 'track', "VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`");

    // 2. Table quiz_attempts
    safe_add_column($pdo, 'quiz_attempts', 'assessment_type', "VARCHAR(50) DEFAULT 'SNBT' AFTER `quiz_type`");
    safe_add_column($pdo, 'quiz_attempts', 'track', "VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`");
    safe_add_column($pdo, 'quiz_attempts', 'package_id', "VARCHAR(100) DEFAULT NULL AFTER `track`");

    // 3. Table quiz_results
    safe_add_column($pdo, 'quiz_results', 'assessment_type', "VARCHAR(50) DEFAULT 'SNBT' AFTER `quiz_type`");
    safe_add_column($pdo, 'quiz_results', 'track', "VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`");
    safe_add_column($pdo, 'quiz_results', 'package_id', "VARCHAR(100) DEFAULT NULL AFTER `track`");

    // 4. Table progress
    safe_add_column($pdo, 'progress', 'assessment_type', "VARCHAR(50) DEFAULT 'SNBT' AFTER `subtes`");
    safe_add_column($pdo, 'progress', 'track', "VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`");
    safe_add_column($pdo, 'progress', 'subject', "VARCHAR(100) DEFAULT NULL AFTER `track`");

    // 5. Table question_imports_staging
    safe_add_column($pdo, 'question_imports_staging', 'education_level', "VARCHAR(50) DEFAULT 'SMA' AFTER `sub_materi`");
    safe_add_column($pdo, 'question_imports_staging', 'assessment_type', "VARCHAR(50) DEFAULT 'SNBT' AFTER `education_level`");
    safe_add_column($pdo, 'question_imports_staging', 'track', "VARCHAR(50) DEFAULT 'umum' AFTER `assessment_type`");
    safe_add_column($pdo, 'question_imports_staging', 'subject', "VARCHAR(100) DEFAULT NULL AFTER `track`");
    safe_add_column($pdo, 'question_imports_staging', 'domain', "VARCHAR(100) DEFAULT NULL AFTER `subject`");

    // 6. Backfill existing records
    try {
        $pdo->exec("UPDATE `questions` SET `track` = 'umum' WHERE `track` IS NULL OR `track` = ''");
        $pdo->exec("UPDATE `materials` SET `track` = 'umum' WHERE `track` IS NULL OR `track` = ''");
        $pdo->exec("UPDATE `quiz_attempts` SET `assessment_type` = 'SNBT', `track` = 'umum' WHERE `assessment_type` IS NULL OR `assessment_type` = ''");
        $pdo->exec("UPDATE `quiz_results` SET `assessment_type` = 'SNBT', `track` = 'umum' WHERE `assessment_type` IS NULL OR `assessment_type` = ''");
        $pdo->exec("UPDATE `progress` SET `assessment_type` = 'SNBT', `track` = 'umum' WHERE `assessment_type` IS NULL OR `assessment_type` = ''");
        echo "✅ Default values backfilled for legacy records.\n";
    } catch (\Throwable $e) {
        echo "ℹ️ Backfill notice: " . $e->getMessage() . "\n";
    }

    // 7. Add index
    try {
        $pdo->exec("CREATE INDEX `idx_questions_taxonomy_lookup` ON `questions` (`assessment_type`, `track`, `subject`, `is_active`, `is_qc_passed`)");
        echo "✅ Index idx_questions_taxonomy_lookup created.\n";
    } catch (\Throwable $e) {
        echo "ℹ️ Index notice: " . $e->getMessage() . "\n";
    }

    echo "🎉 Phase 9: TKA SMA Assessment Expansion Migration Completed Successfully!\n";

} catch (\Throwable $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
