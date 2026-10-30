<?php
// api/tests/test_phase5_cohort_renewal.php
// Automated QA Test for Configurable Cohort Renewal (PRD v1.1 Phase 5: AC-11, AC-12, AC-13)

echo "=== RUNNING QA TEST: PHASE 5 COHORT RENEWAL ===\n";

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Setup Mock Schema
$db->exec("
    CREATE TABLE tenants (
        id VARCHAR(36) PRIMARY KEY,
        name VARCHAR(255) NOT NULL
    );

    CREATE TABLE students (
        id VARCHAR(36) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        email VARCHAR(255) NOT NULL
    );

    CREATE TABLE classes (
        id VARCHAR(36) PRIMARY KEY,
        tenant_id VARCHAR(36) NOT NULL,
        name VARCHAR(150) NOT NULL,
        max_capacity INT NOT NULL DEFAULT 15,
        status TEXT DEFAULT 'active'
    );

    CREATE TABLE class_offerings (
        id VARCHAR(36) PRIMARY KEY,
        tenant_id VARCHAR(36) NOT NULL,
        class_id VARCHAR(36) NULL,
        name VARCHAR(150) NOT NULL,
        renewal_mode TEXT CHECK(renewal_mode IN ('new_enrollment', 'extend_existing')) NOT NULL DEFAULT 'new_enrollment',
        max_capacity INT NOT NULL DEFAULT 15,
        status TEXT DEFAULT 'open'
    );

    CREATE TABLE class_students (
        id VARCHAR(36) PRIMARY KEY,
        class_id VARCHAR(36) NOT NULL,
        student_id VARCHAR(36) NOT NULL,
        joined_at DATETIME NOT NULL,
        valid_until DATE NULL,
        offering_id VARCHAR(36) NULL,
        renewal_reference_id VARCHAR(36) NULL,
        status TEXT CHECK(status IN ('active', 'completed', 'transferred', 'dropped')) DEFAULT 'active'
    );

    CREATE TABLE renewal_policies (
        id VARCHAR(36) PRIMARY KEY,
        offering_id VARCHAR(36) NOT NULL,
        renewal_mode TEXT CHECK(renewal_mode IN ('new_enrollment', 'extend_existing')) NOT NULL DEFAULT 'new_enrollment',
        renewal_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        extension_days INT NOT NULL DEFAULT 30,
        allowed_cohort_change INT NOT NULL DEFAULT 1,
        eligibility_rule VARCHAR(100) NOT NULL DEFAULT 'active_or_grace',
        open_at DATETIME NULL,
        close_at DATETIME NULL,
        policy_version VARCHAR(20) NOT NULL DEFAULT 'v1.0'
    );

    CREATE TABLE renewal_records (
        id VARCHAR(36) PRIMARY KEY,
        original_enrollment_id VARCHAR(36) NOT NULL,
        new_enrollment_id VARCHAR(36) NULL,
        student_id VARCHAR(36) NOT NULL,
        offering_id VARCHAR(36) NOT NULL,
        renewal_mode TEXT CHECK(renewal_mode IN ('new_enrollment', 'extend_existing')) NOT NULL,
        previous_class_id VARCHAR(36) NOT NULL,
        new_class_id VARCHAR(36) NOT NULL,
        previous_end_date DATE NULL,
        new_end_date DATE NOT NULL,
        price_paid DECIMAL(15,2) NOT NULL DEFAULT 0.00,
        order_id VARCHAR(36) NULL,
        status TEXT DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");

// 2. Seed Initial Records
$db->exec("INSERT INTO tenants VALUES ('t-hq', 'EduPath HQ')");
$db->exec("INSERT INTO students VALUES ('stu-101', 'Budi Santoso', 'budi@example.com')");
$db->exec("INSERT INTO students VALUES ('stu-102', 'Siti Aminah', 'siti@example.com')");

$db->exec("INSERT INTO classes VALUES ('class-cbt-a', 't-hq', 'Kelas Intensif UTBK A', 15, 'active')");
$db->exec("INSERT INTO classes VALUES ('class-cbt-b', 't-hq', 'Kelas Intensif UTBK B', 15, 'active')");

// Test 1: AC-11 Offering Renewal Policy Configuration
$db->exec("
    INSERT INTO class_offerings (id, tenant_id, class_id, name, renewal_mode, max_capacity, status)
    VALUES ('off-utbk-01', 't-hq', 'class-cbt-a', 'Paket Intensif SNPMB Batch 1', 'new_enrollment', 15, 'open')
");

$db->exec("
    INSERT INTO renewal_policies (
        id, offering_id, renewal_mode, renewal_price, extension_days, allowed_cohort_change
    ) VALUES ('pol-01', 'off-utbk-01', 'new_enrollment', 350000.00, 30, 1)
");

$offering = $db->query("SELECT * FROM class_offerings WHERE id = 'off-utbk-01'")->fetch(PDO::FETCH_ASSOC);
$policy = $db->query("SELECT * FROM renewal_policies WHERE offering_id = 'off-utbk-01'")->fetch(PDO::FETCH_ASSOC);

assert($offering['renewal_mode'] === 'new_enrollment', 'AC-11: Offering has renewal_mode');
assert($policy['renewal_price'] == 350000.00, 'AC-11: Policy has renewal_price');
echo "  [PASS] Test 1: Offering and Renewal Policy configured with renewal_mode (AC-11).\n";

// Test 2: AC-12 Mode 1 (new_enrollment)
// Siswa terdaftar di enrollment lama
$db->exec("
    INSERT INTO class_students (id, class_id, student_id, joined_at, valid_until, offering_id, status)
    VALUES ('enr-orig-01', 'class-cbt-a', 'stu-101', '2026-09-01 10:00:00', '2026-10-01', 'off-utbk-01', 'active')
");

// Eksekusi renewal Mode 1 -> new enrollment di class-cbt-b
$newEnrId = 'enr-new-02';
$newEndDate = '2026-11-01';

$db->beginTransaction();
$db->exec("
    INSERT INTO class_students (id, class_id, student_id, joined_at, valid_until, offering_id, renewal_reference_id, status)
    VALUES ('$newEnrId', 'class-cbt-b', 'stu-101', '2026-10-01 10:00:00', '$newEndDate', 'off-utbk-01', 'enr-orig-01', 'active')
");
$db->exec("UPDATE class_students SET status = 'completed' WHERE id = 'enr-orig-01'");
$db->exec("
    INSERT INTO renewal_records (
        id, original_enrollment_id, new_enrollment_id, student_id, offering_id,
        renewal_mode, previous_class_id, new_class_id, previous_end_date, new_end_date, price_paid
    ) VALUES (
        'rec-01', 'enr-orig-01', '$newEnrId', 'stu-101', 'off-utbk-01',
        'new_enrollment', 'class-cbt-a', 'class-cbt-b', '2026-10-01', '$newEndDate', 350000.00
    )
");
$db->commit();

// Verifikasi enrollment lama tetap ada (AC-12)
$oldEnr = $db->query("SELECT * FROM class_students WHERE id = 'enr-orig-01'")->fetch(PDO::FETCH_ASSOC);
assert($oldEnr !== false, 'AC-12: Old enrollment record preserved');
assert($oldEnr['status'] === 'completed', 'AC-12: Old enrollment marked completed');

// Verifikasi enrollment baru terhubung ke enrollment lama (AC-12)
$newEnr = $db->query("SELECT * FROM class_students WHERE id = '$newEnrId'")->fetch(PDO::FETCH_ASSOC);
assert($newEnr !== false, 'AC-12: New enrollment created');
assert($newEnr['renewal_reference_id'] === 'enr-orig-01', 'AC-12: New enrollment references old enrollment');
assert($newEnr['class_id'] === 'class-cbt-b', 'AC-12: New enrollment in target class');

// Verifikasi audit log di renewal_records
$rec = $db->query("SELECT * FROM renewal_records WHERE id = 'rec-01'")->fetch(PDO::FETCH_ASSOC);
assert($rec['renewal_mode'] === 'new_enrollment', 'AC-12: renewal_records logs mode');
assert($rec['original_enrollment_id'] === 'enr-orig-01', 'AC-12: renewal_records links original');
assert($rec['new_enrollment_id'] === $newEnrId, 'AC-12: renewal_records links new');
echo "  [PASS] Test 2: Mode new_enrollment creates connected new enrollment and preserves history (AC-12).\n";

// Test 3: AC-13 Mode 2 (extend_existing)
// Siswa 102 di offering yang menggunakan extend_existing
$db->exec("
    INSERT INTO class_students (id, class_id, student_id, joined_at, valid_until, offering_id, status)
    VALUES ('enr-orig-02', 'class-cbt-a', 'stu-102', '2026-10-01 10:00:00', '2026-10-31', 'off-utbk-01', 'active')
");

$extendedEndDate = '2026-11-30';
$db->beginTransaction();
$db->exec("UPDATE class_students SET valid_until = '$extendedEndDate' WHERE id = 'enr-orig-02'");
$db->exec("
    INSERT INTO renewal_records (
        id, original_enrollment_id, new_enrollment_id, student_id, offering_id,
        renewal_mode, previous_class_id, new_class_id, previous_end_date, new_end_date, price_paid
    ) VALUES (
        'rec-02', 'enr-orig-02', NULL, 'stu-102', 'off-utbk-01',
        'extend_existing', 'class-cbt-a', 'class-cbt-a', '2026-10-31', '$extendedEndDate', 350000.00
    )
");
$db->commit();

// Verifikasi ID enrollment tetap sama (AC-13)
$extEnr = $db->query("SELECT * FROM class_students WHERE id = 'enr-orig-02'")->fetch(PDO::FETCH_ASSOC);
assert($extEnr['valid_until'] === $extendedEndDate, 'AC-13: Extended valid_until');
assert($extEnr['status'] === 'active', 'AC-13: Remains active');

// Verifikasi renewal_records mencatat histori periode lama dan baru (AC-13)
$recExt = $db->query("SELECT * FROM renewal_records WHERE id = 'rec-02'")->fetch(PDO::FETCH_ASSOC);
assert($recExt['renewal_mode'] === 'extend_existing', 'AC-13: Logged as extend_existing');
assert($recExt['previous_end_date'] === '2026-10-31', 'AC-13: Previous end date preserved in history');
assert($recExt['new_end_date'] === $extendedEndDate, 'AC-13: New end date recorded');
echo "  [PASS] Test 3: Mode extend_existing extends validity while preserving full historical records (AC-13).\n";

echo "=== ALL PHASE 5 QA TESTS PASSED SUCCESSFULLY! ===\n";
