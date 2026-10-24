<?php
// api/database/migrations/phase8_universal_taxonomy_v2.php
require_once __DIR__ . '/../../config.php';

echo "Running Phase 8: Universal Assessment Taxonomy V2 Migration...\n";

try {
    $qCols = [
        'education_level' => "VARCHAR(50) DEFAULT 'SMA'",
        'assessment_type' => "VARCHAR(50) DEFAULT 'SNBT'",
        'subject'         => "VARCHAR(100) DEFAULT NULL",
        'domain'          => "VARCHAR(100) DEFAULT NULL",
        'cognitive_demand'=> "VARCHAR(50) DEFAULT 'C3'"
    ];

    foreach ($qCols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE `questions` ADD COLUMN `$col` $def");
            echo "✅ Column questions.$col added.\n";
        } catch (PDOException $e) {
            echo "ℹ️ Column questions.$col already present or error ignored.\n";
        }
    }

    $mCols = [
        'education_level' => "VARCHAR(50) DEFAULT 'SMA'",
        'assessment_type' => "VARCHAR(50) DEFAULT 'SNBT'",
        'subject'         => "VARCHAR(100) DEFAULT NULL",
        'domain'          => "VARCHAR(100) DEFAULT NULL"
    ];

    foreach ($mCols as $col => $def) {
        try {
            $pdo->exec("ALTER TABLE `materials` ADD COLUMN `$col` $def");
            echo "✅ Column materials.$col added.\n";
        } catch (PDOException $e) {
            echo "ℹ️ Column materials.$col already present or error ignored.\n";
        }
    }

    // Backfill defaults
    $pdo->exec("UPDATE questions SET education_level = 'SMA' WHERE education_level IS NULL OR education_level = ''");
    $pdo->exec("UPDATE questions SET assessment_type = 'SNBT' WHERE assessment_type IS NULL OR assessment_type = ''");
    $pdo->exec("UPDATE materials SET education_level = 'SMA' WHERE education_level IS NULL OR education_level = ''");
    $pdo->exec("UPDATE materials SET assessment_type = 'SNBT' WHERE assessment_type IS NULL OR assessment_type = ''");

    echo "✅ Universal Assessment Taxonomy V2 Migration Complete!\n";

} catch (PDOException $e) {
    echo "❌ Migration failed: " . $e->getMessage() . "\n";
}
