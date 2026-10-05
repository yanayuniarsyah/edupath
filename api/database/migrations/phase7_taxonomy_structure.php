<?php
// api/database/migrations/phase7_taxonomy_structure.php
require_once __DIR__ . '/../../config.php';

echo "Running Phase 7: SNBT Content Architecture & Taxonomy Migration...\n";

try {
    // 1. Ensure materials table exists
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `materials` (
          `id` VARCHAR(36) NOT NULL PRIMARY KEY,
          `exam` VARCHAR(50) DEFAULT 'SNBT',
          `test_component` VARCHAR(50) DEFAULT 'TPS',
          `subtest` VARCHAR(100) NOT NULL,
          `topic` VARCHAR(100) DEFAULT NULL,
          `subtopic` VARCHAR(100) DEFAULT NULL,
          `skill` VARCHAR(150) DEFAULT NULL,
          `indicator` VARCHAR(255) DEFAULT NULL,
          `title` VARCHAR(255) NOT NULL,
          `content` LONGTEXT NOT NULL,
          `teacher_name` VARCHAR(100) DEFAULT NULL,
          `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
          `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
          KEY `mat_subtest_idx` (`subtest`),
          KEY `mat_topic_idx` (`topic`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
    ");
    echo "✅ Table materials verified/created.\n";

    // 2. Add columns to questions table if not exists
    $columns_to_add = [
        'exam' => "VARCHAR(50) DEFAULT 'SNBT' AFTER `id`",
        'test_component' => "VARCHAR(50) DEFAULT 'TPS' AFTER `exam`",
        'subtest' => "VARCHAR(100) DEFAULT NULL AFTER `test_component`",
        'topic' => "VARCHAR(100) DEFAULT NULL AFTER `subtest`",
        'subtopic' => "VARCHAR(100) DEFAULT NULL AFTER `topic`",
        'skill' => "VARCHAR(150) DEFAULT NULL AFTER `subtopic`",
        'indicator' => "VARCHAR(255) DEFAULT NULL AFTER `skill`",
        'question_type' => "VARCHAR(50) DEFAULT 'multiple_choice' AFTER `indicator`"
    ];

    foreach ($columns_to_add as $col => $definition) {
        try {
            $pdo->exec("ALTER TABLE `questions` ADD COLUMN `$col` $definition");
            echo "✅ Column questions.$col added.\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
                echo "ℹ️ Column questions.$col already exists.\n";
            } else {
                echo "⚠️ Warning on questions.$col: " . $e->getMessage() . "\n";
            }
        }
    }

    // 3. Backfill questions: if subtest is NULL, use sub_materi or subtes
    $pdo->exec("
        UPDATE questions 
        SET subtest = COALESCE(subtest, sub_materi, subtes, 'Penalaran Umum')
        WHERE subtest IS NULL OR subtest = ''
    ");

    // 4. Map test_component based on subtest
    $pdo->exec("
        UPDATE questions 
        SET test_component = CASE 
            WHEN subtest LIKE '%Literasi%' OR subtest LIKE '%Matematika%' THEN 'TES LITERASI'
            ELSE 'TPS'
        END
        WHERE test_component IS NULL OR test_component = ''
    ");

    // 5. Add columns to materials table if it already existed with older schema
    $material_cols = [
        'exam' => "VARCHAR(50) DEFAULT 'SNBT'",
        'test_component' => "VARCHAR(50) DEFAULT 'TPS'",
        'topic' => "VARCHAR(100) DEFAULT NULL",
        'subtopic' => "VARCHAR(100) DEFAULT NULL",
        'skill' => "VARCHAR(150) DEFAULT NULL",
        'indicator' => "VARCHAR(255) DEFAULT NULL",
        'teacher_name' => "VARCHAR(100) DEFAULT NULL"
    ];
    foreach ($material_cols as $col => $definition) {
        try {
            $pdo->exec("ALTER TABLE `materials` ADD COLUMN `$col` $definition");
            echo "✅ Column materials.$col added.\n";
        } catch (PDOException $e) {
            // ignore duplicate column
        }
    }

    echo "🎉 Phase 7 Migration completed successfully!\n";

} catch (Exception $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
