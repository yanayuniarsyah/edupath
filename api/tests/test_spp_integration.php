<?php
// api/tests/test_spp_integration.php

require_once __DIR__ . '/../spp_service.php';

$tests_run = 0;
$tests_passed = 0;
$tests_failed = 0;
$failed_details = [];

function assert_test($condition, $test_name) {
    global $tests_run, $tests_passed, $tests_failed, $failed_details;
    $tests_run++;
    if ($condition) {
        $tests_passed++;
        echo "PASS: $test_name\n";
    } else {
        $tests_failed++;
        $failed_details[] = $test_name;
        echo "FAIL: $test_name\n";
    }
}

// 1. Setup Mock DB (SQLite in memory)
$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create required schemas
$pdo->exec("
    CREATE TABLE students (
        id VARCHAR(36) PRIMARY KEY,
        school VARCHAR(150)
    );
    CREATE TABLE spp_attempts (
        id VARCHAR(36) PRIMARY KEY,
        student_id VARCHAR(36),
        attempt_status VARCHAR(50) DEFAULT 'DRAFT',
        scoring_status VARCHAR(50) DEFAULT 'PENDING',
        quality_status VARCHAR(50) DEFAULT 'ACCEPTABLE',
        duration_seconds INT DEFAULT 0,
        started_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        submitted_at DATETIME DEFAULT NULL,
        item_bank_version VARCHAR(50) DEFAULT 'SPP-ITEM-1.0.0',
        scoring_version VARCHAR(50) DEFAULT 'SPP-SCORE-1.0.0',
        interpretation_version VARCHAR(50) DEFAULT 'SPP-INTERPRET-1.0.0',
        narrative_bank_version VARCHAR(50) DEFAULT 'SPP-NARRATIVE-1.0.0'
    );
    CREATE TABLE spp_responses (
        id VARCHAR(36) PRIMARY KEY,
        attempt_id VARCHAR(36),
        responses TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE spp_scores (
        id VARCHAR(36) PRIMARY KEY,
        attempt_id VARCHAR(36),
        facets TEXT,
        dimensions TEXT,
        riasec TEXT,
        riasec_top3 TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
    CREATE TABLE spp_profiles (
        id VARCHAR(36) PRIMARY KEY,
        attempt_id VARCHAR(36),
        bands TEXT,
        relative_strength VARCHAR(50),
        development_area VARCHAR(50),
        narratives TEXT,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    );
");

// Mock Data
$student_1 = "stu-1";
$student_2 = "stu-2";
$pdo->exec("INSERT INTO students (id, school) VALUES ('$student_1', 'SMA 1 Jakarta')");
$pdo->exec("INSERT INTO students (id, school) VALUES ('$student_2', 'SMA 1 Jakarta')");

$service = new SPPService($pdo);

// Helper for valid payload
function get_valid_payload() {
    $facets = [
        'EFC_MAST_01', 'EFC_MAST_02',
        'EFC_CHAL_01', 'EFC_CHAL_02',
        'SRL_PLAN_01', 'SRL_PLAN_02', 'SRL_PLAN_03',
        'SRL_CTRL_01', 'SRL_CTRL_02', 'SRL_CTRL_03',
        'SRL_REFL_01', 'SRL_REFL_02', 'SRL_REFL_03',
        'GRT_PERS_01', 'GRT_PERS_02', 'GRT_PERS_03', 'GRT_PERS_04', 'GRT_PERS_05'
    ];
    $riasecs = [
        'INT_REAL_01', 'INT_REAL_02',
        'INT_INVS_01', 'INT_INVS_02',
        'INT_ARTS_01', 'INT_ARTS_02',
        'INT_SOCL_01', 'INT_SOCL_02',
        'INT_ENTR_01', 'INT_ENTR_02',
        'INT_CONV_01', 'INT_CONV_02'
    ];
    $payload = [];
    foreach ($facets as $k) $payload[$k] = 3;
    foreach ($riasecs as $k) $payload[$k] = 3;
    return $payload;
}

// 1. Create attempt
$create_res = $service->createAttempt($student_1);
assert_test(isset($create_res['id']) && $create_res['status'] === 'DRAFT', "Create attempt works");
$attempt_id_1 = $create_res['id'];

// 2. Draft to In Progress and Submit (Valid submit)
$submit_res_1 = $service->submitAttempt($student_1, $attempt_id_1, get_valid_payload(), 120);
if (isset($submit_res_1['status']) && $submit_res_1['status'] === 'ERROR') {
    echo "ERROR: " . $submit_res_1['message'] . "\n";
}
assert_test(isset($submit_res_1['attempt']) && $submit_res_1['attempt']['attempt_status'] === 'SUBMITTED', "Valid submit moves to SUBMITTED");
assert_test(isset($submit_res_1['attempt']) && $submit_res_1['attempt']['scoring_status'] === 'SCORED', "Valid submit moves to SCORED");

// 3. Duplicate Submit (Idempotency)
$submit_res_dup = $service->submitAttempt($student_1, $attempt_id_1, get_valid_payload(), 120);
assert_test($submit_res_dup['attempt']['id'] === $attempt_id_1, "Duplicate submit returns existing result");

// 4. Invalid Submit (Insufficient data)
$create_res_invalid = $service->createAttempt($student_1);
$attempt_id_invalid = $create_res_invalid['id'];
$invalid_payload = get_valid_payload();
unset($invalid_payload['EFC_MAST_01']);
$submit_res_invalid = $service->submitAttempt($student_1, $attempt_id_invalid, $invalid_payload, 120);
assert_test($submit_res_invalid['attempt']['scoring_status'] === 'INVALID_PAYLOAD', "Invalid payload is rejected in state");

// 5. Quality Review Inclusion
$create_res_speed = $service->createAttempt($student_2);
$attempt_id_speed = $create_res_speed['id'];
$submit_res_speed = $service->submitAttempt($student_2, $attempt_id_speed, get_valid_payload(), 30); // 30 seconds
assert_test($submit_res_speed['attempt']['scoring_status'] === 'SCORED', "Speed flag still scores");
assert_test($submit_res_speed['attempt']['quality_status'] === 'QUALITY_REVIEW_REQUIRED', "Speed flag marks quality review");

// 6. Retake (Old Canonical -> New Canonical)
// student_1 takes another test
sleep(1);
$create_res_retake = $service->createAttempt($student_1);
$attempt_id_retake = $create_res_retake['id'];
$submit_res_retake = $service->submitAttempt($student_1, $attempt_id_retake, get_valid_payload(), 120);
assert_test($submit_res_retake['attempt']['id'] === $attempt_id_retake, "Retake creates new attempt and scores successfully");

// 7. School Aggregate tests inclusion rules
$agg = $service->getSchoolAggregate('SMA 1 Jakarta');
if ($agg['total_canonical_students'] !== 2) {
    echo "ERROR: canonical students count = " . $agg['total_canonical_students'] . "\n";
}
assert_test($agg['total_canonical_students'] === 2, "School aggregate gets exact 2 canonical students (stu-1, stu-2)");
assert_test($agg['quality_review_flags'] === 1, "School aggregate includes the quality review required test from stu-2");

// 8. Historical immutability
$res_historical = $service->getResult($attempt_id_1, $student_1);
assert_test($res_historical['attempt']['id'] === $attempt_id_1, "Historical attempt remains completely intact and accessible");

echo "\n--- Summary ---\n";
echo "Integration Tests Run: $tests_run\n";
echo "Integration Tests Passed: $tests_passed\n";
echo "Integration Tests Failed: $tests_failed\n";
if ($tests_failed > 0) {
    echo "Failed Details:\n" . implode("\n", $failed_details) . "\n";
    exit(1);
} else {
    echo "ALL TESTS PASSED.\n";
    exit(0);
}
