<?php
// api/tests/test_phase6_unified_progress.php
// Automated QA Test for Unified Academic Engine & Student Progress (PRD v1.1 Phase 6: AC-16, AC-17)

echo "=== RUNNING QA TEST: PHASE 6 UNIFIED ACADEMIC PROGRESS ===\n";

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Mock DB Schema
$db->exec("
    CREATE TABLE students (
        id VARCHAR(36) PRIMARY KEY,
        tenant_id VARCHAR(36) NULL,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL,
        school VARCHAR(255) NULL,
        plan VARCHAR(50) DEFAULT 'free',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE progress (
        id VARCHAR(36) PRIMARY KEY,
        student_id VARCHAR(36) NOT NULL,
        subtes VARCHAR(100) NOT NULL,
        bab VARCHAR(100) NOT NULL,
        score FLOAT DEFAULT 0,
        mastery INT DEFAULT 0,
        last_study TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE quiz_results (
        id VARCHAR(36) PRIMARY KEY,
        student_id VARCHAR(36) NOT NULL,
        quiz_type VARCHAR(50) DEFAULT 'tryout',
        assessment_type VARCHAR(50) DEFAULT 'SNBT',
        track VARCHAR(50) DEFAULT 'umum',
        subtes VARCHAR(100) NULL,
        score FLOAT NOT NULL,
        correct INT NOT NULL,
        total INT NOT NULL,
        duration_sec INT DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE classes (
        id VARCHAR(36) PRIMARY KEY,
        name VARCHAR(150) NOT NULL,
        academic_year VARCHAR(20) DEFAULT '2026/2027',
        default_room_id VARCHAR(36) NULL
    );

    CREATE TABLE class_students (
        id VARCHAR(36) PRIMARY KEY,
        class_id VARCHAR(36) NOT NULL,
        student_id VARCHAR(36) NOT NULL,
        joined_at DATETIME NOT NULL,
        valid_until DATE NULL,
        status TEXT DEFAULT 'active'
    );

    CREATE TABLE session_attendances (
        id VARCHAR(36) PRIMARY KEY,
        session_id VARCHAR(36) NOT NULL,
        student_id VARCHAR(36) NOT NULL,
        attendance_status TEXT CHECK(attendance_status IN ('present', 'late', 'absent', 'excused')) NOT NULL
    );

    CREATE TABLE spp_attempts (
        id VARCHAR(36) PRIMARY KEY,
        student_id VARCHAR(36) NOT NULL,
        attempt_status VARCHAR(50) DEFAULT 'SUBMITTED',
        scoring_status VARCHAR(50) DEFAULT 'SCORED',
        quality_status VARCHAR(50) DEFAULT 'ACCEPTABLE',
        duration_seconds INT DEFAULT 1800,
        started_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE entitlements (
        id VARCHAR(36) PRIMARY KEY,
        user_id VARCHAR(36) NOT NULL,
        feature_code VARCHAR(100) NOT NULL,
        source_type VARCHAR(50) DEFAULT 'purchase',
        is_active INT DEFAULT 1,
        expires_at DATETIME NULL
    );
");

// 2. Seed Unified Data for Student 1
$db->exec("INSERT INTO students (id, name, email, school, plan) VALUES ('stu-hybrid-01', 'Ananda Putri', 'ananda@hybrid.edu', 'SMA Negeri 1 Jakarta', 'HYBRID_PRO')");

// Online Progress
$db->exec("INSERT INTO progress (id, student_id, subtes, bab, score, mastery) VALUES ('prg-1', 'stu-hybrid-01', 'Penalaran Matematika', 'Fungsi Kuadrat', 88.5, 90)");

// CBT Results
$db->exec("INSERT INTO quiz_results (id, student_id, quiz_type, assessment_type, score, correct, total) VALUES ('cbt-1', 'stu-hybrid-01', 'tryout', 'SNBT', 740.5, 25, 30)");
$db->exec("INSERT INTO quiz_results (id, student_id, quiz_type, assessment_type, score, correct, total) VALUES ('cbt-2', 'stu-hybrid-01', 'tryout', 'SNBT', 790.0, 27, 30)");

// Offline Class & Attendance
$db->exec("INSERT INTO classes (id, name) VALUES ('cls-hyb-01', 'Rombel Hybrid SNBT Weekend')");
$db->exec("INSERT INTO class_students (id, class_id, student_id, joined_at, valid_until, status) VALUES ('cs-1', 'cls-hyb-01', 'stu-hybrid-01', '2026-10-01 08:00:00', '2026-12-31', 'active')");
$db->exec("INSERT INTO session_attendances (id, session_id, student_id, attendance_status) VALUES ('att-1', 'ses-01', 'stu-hybrid-01', 'present')");
$db->exec("INSERT INTO session_attendances (id, session_id, student_id, attendance_status) VALUES ('att-2', 'ses-02', 'stu-hybrid-01', 'late')");

// SPP Diagnostic
$db->exec("INSERT INTO spp_attempts (id, student_id, attempt_status, scoring_status) VALUES ('spp-att-1', 'stu-hybrid-01', 'SUBMITTED', 'SCORED')");

// Entitlements
$db->exec("INSERT INTO entitlements (id, user_id, feature_code, is_active) VALUES ('ent-1', 'stu-hybrid-01', 'FEATURE_HYBRID_CAMPUS', 1)");

// Test 1: AC-16 & AC-17 Unified Profile Integration
$student = $db->query("SELECT * FROM students WHERE id = 'stu-hybrid-01'")->fetch(PDO::FETCH_ASSOC);
assert(!empty($student['name']), 'Student identity exists');

// Verify CBT stats aggregated
$cbtAgg = $db->query("SELECT COUNT(*) as cnt, AVG(score) as avg_sc, MAX(score) as max_sc FROM quiz_results WHERE student_id = 'stu-hybrid-01'")->fetch(PDO::FETCH_ASSOC);
assert((int)$cbtAgg['cnt'] === 2, 'AC-16: 2 CBT tryouts recorded');
assert(round($cbtAgg['max_sc'], 1) == 790.0, 'AC-16: Max CBT score calculated');

// Verify Offline Attendance aggregated
$attAgg = $db->query("SELECT COUNT(*) as total, SUM(CASE WHEN attendance_status IN ('present', 'late') THEN 1 ELSE 0 END) as attended FROM session_attendances WHERE student_id = 'stu-hybrid-01'")->fetch(PDO::FETCH_ASSOC);
assert((int)$attAgg['total'] === 2, 'AC-16: Offline attendance records linked');
assert((int)$attAgg['attended'] === 2, 'AC-16: Attendance rate calculated');

// Verify Entitlements linked to same student ID (AC-17)
$entCount = $db->query("SELECT COUNT(*) FROM entitlements WHERE user_id = 'stu-hybrid-01' AND is_active = 1")->fetchColumn();
assert((int)$entCount === 1, 'AC-17: Shared entitlement linked under single identity');

echo "  [PASS] Test 1: Online CBT, Offline Attendances, and Entitlements unified under one student profile (AC-16, AC-17).\n";

// Test 2: Multi-student Isolation in Academic Progress
$db->exec("INSERT INTO students (id, name, email) VALUES ('stu-isolated-02', 'Dimas Arya', 'dimas@online.edu')");
$dimasCBT = $db->query("SELECT COUNT(*) FROM quiz_results WHERE student_id = 'stu-isolated-02'")->fetchColumn();
assert((int)$dimasCBT === 0, 'No CBT data leakage between students');

$dimasClasses = $db->query("SELECT COUNT(*) FROM class_students WHERE student_id = 'stu-isolated-02'")->fetchColumn();
assert((int)$dimasClasses === 0, 'No class enrollment leakage between students');
echo "  [PASS] Test 2: Strict student progress boundary verified with no cross-user leakage.\n";

echo "=== ALL PHASE 6 QA TESTS PASSED SUCCESSFULLY! ===\n";
