<?php
// api/database/migrations/phase11_performance_and_security_indexes.php
// Creates high-performance composite and lookup indexes across all high-frequency tables

require_once __DIR__ . '/../../config.php';

echo "Running Phase 11 Performance & Security Index Optimization...\n";

$indexes = [
    'students' => [
        'idx_students_tenant_id' => 'tenant_id',
        'idx_students_referred_by' => 'referred_by',
        'idx_students_school_id' => 'school_id',
        'idx_students_plan' => 'plan'
    ],
    'orders' => [
        'idx_orders_status' => 'status',
        'idx_orders_tenant_id' => 'tenant_id',
        'idx_orders_student_status' => 'student_id, status'
    ],
    'subscriptions' => [
        'idx_subs_student_status' => 'student_id, status',
        'idx_subs_expires_status' => 'expires_at, status'
    ],
    'materials' => [
        'idx_materials_subtest' => 'subtest',
        'idx_materials_edulevel' => 'education_level',
        'idx_materials_active' => 'is_active',
        'idx_materials_topic' => 'topic'
    ],
    'questions' => [
        'idx_questions_subtest' => 'subtest',
        'idx_questions_edulevel' => 'education_level',
        'idx_questions_qc_active' => 'is_qc_passed, is_active'
    ],
    'plans' => [
        'idx_plans_prog_mode' => 'academic_program, delivery_mode',
        'idx_plans_active_arch' => 'is_active, is_archived'
    ],
    'rate_limits' => [
        'idx_ratelimits_lock' => 'lock_until'
    ],
    'audit_logs' => [
        'idx_audit_created' => 'created_at'
    ],
    'financial_ledger' => [
        'idx_finledger_tenant' => 'tenant_id'
    ],
    'spp_attempts' => [
        'idx_spp_status' => 'attempt_status'
    ],
    'quiz_attempts' => [
        'idx_quizatt_status' => 'status'
    ]
];

$tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
$added = 0;
$skipped = 0;

foreach ($indexes as $table => $tableIndexes) {
    if (!in_array($table, $tables)) {
        continue;
    }

    $existingIndexes = array_column(
        $pdo->query("SHOW INDEX FROM `$table`")->fetchAll(PDO::FETCH_ASSOC),
        'Key_name'
    );

    foreach ($tableIndexes as $indexName => $columns) {
        if (in_array($indexName, $existingIndexes)) {
            $skipped++;
            continue;
        }

        try {
            $pdo->exec("CREATE INDEX `$indexName` ON `$table` ($columns)");
            echo " [ADDED] Index `$indexName` on `$table` ($columns)\n";
            $added++;
        } catch (\Throwable $e) {
            echo " [WARN] Could not create index `$indexName` on `$table`: " . $e->getMessage() . "\n";
        }
    }
}

echo "Done. Added: $added indexes | Existing: $skipped indexes.\n";
