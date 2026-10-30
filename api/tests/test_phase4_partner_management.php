<?php
// api/tests/test_phase4_partner_management.php
// Automated QA Test for Dual Partner Operating Models (PRD v1.1 Phase 4: AC-08, AC-09, AC-10)

echo "=== RUNNING QA TEST: PHASE 4 PARTNER MANAGEMENT ===\n";

$db = new PDO('sqlite::memory:');
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Setup Mock Schema
$db->exec("
    CREATE TABLE tenants (
        id VARCHAR(36) PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        slug VARCHAR(255) NOT NULL,
        is_active INT DEFAULT 1
    );

    CREATE TABLE users (
        id VARCHAR(36) PRIMARY KEY,
        identity_key VARCHAR(255) NOT NULL,
        is_active INT DEFAULT 1
    );

    CREATE TABLE partner_profiles (
        id VARCHAR(36) NOT NULL PRIMARY KEY,
        partner_type TEXT CHECK(partner_type IN ('tenant', 'scoped_operator')) NOT NULL,
        name VARCHAR(255) NOT NULL,
        code VARCHAR(50) NOT NULL UNIQUE,
        tenant_id VARCHAR(36) NULL,
        user_id VARCHAR(36) NULL,
        contact_email VARCHAR(150) NULL,
        contact_phone VARCHAR(50) NULL,
        status TEXT CHECK(status IN ('active', 'suspended', 'terminated')) NOT NULL DEFAULT 'active',
        revenue_share_pct DECIMAL(5,2) NOT NULL DEFAULT 0.00,
        notes TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE partner_assignments (
        id VARCHAR(36) NOT NULL PRIMARY KEY,
        partner_id VARCHAR(36) NOT NULL,
        operator_user_id VARCHAR(36) NULL,
        tenant_id VARCHAR(36) NULL,
        location VARCHAR(255) NULL,
        offering_id VARCHAR(36) NULL,
        class_id VARCHAR(36) NULL,
        session_id VARCHAR(36) NULL,
        start_date DATE NOT NULL,
        end_date DATE NULL,
        status TEXT CHECK(status IN ('active', 'revoked', 'expired')) NOT NULL DEFAULT 'active',
        revoked_at DATETIME NULL,
        revoked_by VARCHAR(36) NULL,
        revocation_reason TEXT NULL,
        created_by VARCHAR(36) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE partner_assignment_logs (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        assignment_id VARCHAR(36) NOT NULL,
        action VARCHAR(50) NOT NULL,
        actor_id VARCHAR(36) NOT NULL,
        metadata TEXT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");

// 2. Seed Tenants & Operators
$db->exec("INSERT INTO tenants (id, name, slug) VALUES ('tenant-hq', 'EduPath HQ', 'hq')");
$db->exec("INSERT INTO tenants (id, name, slug) VALUES ('tenant-bdg', 'EduPath Bandung', 'bdg')");
$db->exec("INSERT INTO tenants (id, name, slug) VALUES ('tenant-sby', 'EduPath Surabaya', 'sby')");

$db->exec("INSERT INTO users (id, identity_key) VALUES ('user-op-1', 'operator1@mitra.com')");
$db->exec("INSERT INTO users (id, identity_key) VALUES ('user-op-2', 'operator2@mitra.com')");

// Test 1: Create Partner Tenant (Model 1 - AC-08)
$db->exec("
    INSERT INTO partner_profiles (id, partner_type, name, code, tenant_id, status)
    VALUES ('pp-tenant-sby', 'tenant', 'Mitra Surabaya Mandiri', 'PTN-SBY01', 'tenant-sby', 'active')
");

$pTenant = $db->query("SELECT * FROM partner_profiles WHERE id = 'pp-tenant-sby'")->fetch(PDO::FETCH_ASSOC);
assert($pTenant['partner_type'] === 'tenant', 'Model 1 Partner Tenant type assertion');
assert($pTenant['tenant_id'] === 'tenant-sby', 'Model 1 Tenant binding assertion');
echo "  [PASS] Test 1: Partner Tenant (Model 1) registered with strict tenant binding.\n";

// Test 2: Create Partner Scoped Operator (Model 2 - AC-09)
$db->exec("
    INSERT INTO partner_profiles (id, partner_type, name, code, user_id, status)
    VALUES ('pp-op-bdg', 'scoped_operator', 'Mitra Pengajar Bandung', 'POP-BDG01', 'user-op-1', 'active')
");

$db->exec("
    INSERT INTO partner_assignments (
        id, partner_id, operator_user_id, tenant_id, class_id, session_id,
        start_date, end_date, status
    ) VALUES (
        'asg-01', 'pp-op-bdg', 'user-op-1', 'tenant-bdg', 'class-utbk-01', 'ses-001',
        date('now', '-1 day'), date('now', '+30 day'), 'active'
    )
");

require_once __DIR__ . '/../PartnerService.php';

$canAccessAssignedSession = PartnerService::hasActiveAssignment($db, 'user-op-1', 'session', 'ses-001', 'tenant-bdg');
assert($canAccessAssignedSession === true, 'AC-09: Operator can access assigned session');

$canAccessUnassignedSession = PartnerService::hasActiveAssignment($db, 'user-op-1', 'session', 'ses-999-unassigned', 'tenant-bdg');
assert($canAccessUnassignedSession === false, 'AC-09: Operator CANNOT access unassigned session');

$canAccessOtherTenantSession = PartnerService::hasActiveAssignment($db, 'user-op-1', 'session', 'ses-001', 'tenant-sby');
assert($canAccessOtherTenantSession === false, 'AC-08/AC-09: Operator cannot cross-tenant without assignment');

echo "  [PASS] Test 2: Scoped Operator (Model 2) access validated against active assignments.\n";

// Test 3: Revocation Flow (AC-10)
// Revoking assignment must change status without deleting the row or altering history
$revokedTime = date('Y-m-d H:i:s');
$db->exec("
    UPDATE partner_assignments
    SET status = 'revoked',
        revoked_at = '$revokedTime',
        revoked_by = 'admin-hq',
        revocation_reason = 'Pergantian jadwal pengajar mitra'
    WHERE id = 'asg-01'
");

$db->exec("
    INSERT INTO partner_assignment_logs (assignment_id, action, actor_id, metadata)
    VALUES ('asg-01', 'revoked', 'admin-hq', '{\"reason\":\"Pergantian jadwal\"}')
");

// Verify row still exists in DB (AC-10 Invariant)
$asgRow = $db->query("SELECT * FROM partner_assignments WHERE id = 'asg-01'")->fetch(PDO::FETCH_ASSOC);
assert($asgRow !== false, 'AC-10: Assignment row must NOT be deleted');
assert($asgRow['status'] === 'revoked', 'AC-10: Status must be revoked');
assert(!empty($asgRow['revoked_at']), 'AC-10: Revoked timestamp recorded');
assert(!empty($asgRow['revocation_reason']), 'AC-10: Revocation reason recorded');

// Verify access is immediately terminated (AC-10)
$accessAfterRevocation = PartnerService::hasActiveAssignment($db, 'user-op-1', 'session', 'ses-001', 'tenant-bdg');
assert($accessAfterRevocation === false, 'AC-10: Revocation immediately halts operational access');

// Verify audit trail
$logCount = $db->query("SELECT COUNT(*) FROM partner_assignment_logs WHERE assignment_id = 'asg-01' AND action = 'revoked'")->fetchColumn();
assert((int)$logCount === 1, 'AC-10: Audit log recorded');

echo "  [PASS] Test 3: Revocation terminates access immediately while preserving full history (AC-10).\n";

echo "=== ALL PHASE 4 QA TESTS PASSED SUCCESSFULLY! ===\n";
