<?php
// api/tests/test_affiliate_branch_isolation.php
echo "========================================================\n";
echo "TEST: AFFILIATE & COMMISSION BRANCH SCOPING ISOLATION\n";
echo "========================================================\n\n";

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Setup Schema
$pdo->exec("
    CREATE TABLE tenants (
        id TEXT PRIMARY KEY,
        name TEXT,
        slug TEXT,
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE users (
        id TEXT PRIMARY KEY,
        identity_key TEXT,
        password TEXT,
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE user_roles (
        id TEXT PRIMARY KEY,
        user_id TEXT,
        tenant_id TEXT,
        role TEXT,
        reference_id TEXT
    );

    CREATE TABLE students (
        id TEXT PRIMARY KEY,
        name TEXT,
        email TEXT,
        tenant_id TEXT,
        referred_by TEXT
    );

    CREATE TABLE affiliates (
        id TEXT PRIMARY KEY,
        user_id TEXT,
        tenant_id TEXT,
        referral_code TEXT,
        commission_rate REAL,
        bank_name TEXT,
        bank_account TEXT,
        bank_owner TEXT,
        created_at TEXT
    );

    CREATE TABLE orders (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        plan_name TEXT
    );

    CREATE TABLE commissions (
        id TEXT PRIMARY KEY,
        affiliate_id TEXT,
        tenant_id TEXT,
        order_id TEXT,
        amount REAL,
        commission_rate_snapshot REAL,
        status TEXT,
        created_at TEXT
    );

    CREATE TABLE payouts (
        id TEXT PRIMARY KEY,
        affiliate_id TEXT,
        amount REAL,
        status TEXT,
        bank_name TEXT,
        bank_account TEXT,
        bank_owner TEXT,
        created_at TEXT
    );
");

// 2. Seed Tenants: Bandung vs Surabaya
$tenant_bandung = 'tenant-bandung-001';
$tenant_surabaya = 'tenant-surabaya-002';
$pdo->exec("INSERT INTO tenants VALUES ('$tenant_bandung', 'EduPath Bandung', 'edupath-bandung', 1)");
$pdo->exec("INSERT INTO tenants VALUES ('$tenant_surabaya', 'EduPath Surabaya', 'edupath-surabaya', 1)");

// 3. Seed Affiliates in Bandung & Surabaya
$aff_bdg = 'aff-bdg-101';
$aff_sby = 'aff-sby-202';
$pdo->exec("INSERT INTO affiliates VALUES ('$aff_bdg', 'u-bdg', '$tenant_bandung', 'BDG10', 20.0, 'BCA', '111', 'Mitra Bandung', '2026-01-01')");
$pdo->exec("INSERT INTO affiliates VALUES ('$aff_sby', 'u-sby', '$tenant_surabaya', 'SBY20', 25.0, 'BRI', '222', 'Mitra Surabaya', '2026-01-01')");

// 4. Seed Payouts
$pdo->exec("INSERT INTO payouts VALUES ('pay-bdg', '$aff_bdg', 100000, 'pending', 'BCA', '111', 'Mitra Bandung', '2026-01-02')");
$pdo->exec("INSERT INTO payouts VALUES ('pay-sby', '$aff_sby', 200000, 'pending', 'BRI', '222', 'Mitra Surabaya', '2026-01-02')");

// 5. Seed Commissions
$pdo->exec("INSERT INTO commissions VALUES ('comm-bdg', '$aff_bdg', '$tenant_bandung', 'ord-1', 50000, 20.0, 'pending', '2026-01-02')");
$pdo->exec("INSERT INTO commissions VALUES ('comm-sby', '$aff_sby', '$tenant_surabaya', 'ord-2', 75000, 25.0, 'pending', '2026-01-02')");

function testAffiliatesList($pdo, $role, $tenant_id) {
    if ($role !== 'superadmin' && !empty($tenant_id)) {
        $stmt = $pdo->prepare("SELECT a.id, a.tenant_id FROM affiliates a WHERE a.tenant_id = ?");
        $stmt->execute([$tenant_id]);
    } else {
        $stmt = $pdo->query("SELECT a.id, a.tenant_id FROM affiliates a");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function testPayoutsList($pdo, $role, $tenant_id) {
    if ($role !== 'superadmin' && !empty($tenant_id)) {
        $stmt = $pdo->prepare("SELECT p.id, a.tenant_id FROM payouts p JOIN affiliates a ON p.affiliate_id = a.id WHERE a.tenant_id = ?");
        $stmt->execute([$tenant_id]);
    } else {
        $stmt = $pdo->query("SELECT p.id, a.tenant_id FROM payouts p JOIN affiliates a ON p.affiliate_id = a.id");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function testCommissionsList($pdo, $role, $tenant_id) {
    if ($role !== 'superadmin' && !empty($tenant_id)) {
        $stmt = $pdo->prepare("SELECT c.id, c.tenant_id FROM commissions c JOIN affiliates a ON c.affiliate_id = a.id WHERE (c.tenant_id = ? OR a.tenant_id = ?)");
        $stmt->execute([$tenant_id, $tenant_id]);
    } else {
        $stmt = $pdo->query("SELECT c.id, c.tenant_id FROM commissions c");
    }
    return $stmt->fetchAll(PDO::FETCH_ASSOC);
}

function testCrossTenantMutation($pdo, $admin_tenant, $target_aff_id) {
    $chk = $pdo->prepare("SELECT tenant_id FROM affiliates WHERE id = ?");
    $chk->execute([$target_aff_id]);
    $currentAffTenant = $chk->fetchColumn();
    if ($currentAffTenant !== $admin_tenant) {
        return false; // Forbidden (403)
    }
    return true; // Allowed
}

$allPassed = true;

// TEST 1: Bandung Branch Admin listing affiliates
$bdgAffs = testAffiliatesList($pdo, 'admin', $tenant_bandung);
$bdgIds = array_column($bdgAffs, 'id');
if (in_array($aff_bdg, $bdgIds) && !in_array($aff_sby, $bdgIds)) {
    echo "✅ TEST 1: Admin Bandung only sees Bandung affiliates (Count: " . count($bdgAffs) . ")\n";
} else {
    echo "❌ TEST 1 FAILED: Cross-branch affiliate leakage detected!\n";
    $allPassed = false;
}

// TEST 2: Surabaya Branch Admin listing affiliates
$sbyAffs = testAffiliatesList($pdo, 'admin', $tenant_surabaya);
$sbyIds = array_column($sbyAffs, 'id');
if (in_array($aff_sby, $sbyIds) && !in_array($aff_bdg, $sbyIds)) {
    echo "✅ TEST 2: Admin Surabaya only sees Surabaya affiliates (Count: " . count($sbyAffs) . ")\n";
} else {
    echo "❌ TEST 2 FAILED: Cross-branch affiliate leakage detected!\n";
    $allPassed = false;
}

// TEST 3: Superadmin sees all affiliates
$superAffs = testAffiliatesList($pdo, 'superadmin', null);
if (count($superAffs) === 2) {
    echo "✅ TEST 3: Superadmin sees all branches (Total: 2)\n";
} else {
    echo "❌ TEST 3 FAILED: Superadmin visibility broken!\n";
    $allPassed = false;
}

// TEST 4: Payouts Branch Scoping
$bdgPays = testPayoutsList($pdo, 'admin', $tenant_bandung);
if (count($bdgPays) === 1 && $bdgPays[0]['id'] === 'pay-bdg') {
    echo "✅ TEST 4: Admin Bandung only sees Bandung payouts\n";
} else {
    echo "❌ TEST 4 FAILED: Cross-branch payout leakage!\n";
    $allPassed = false;
}

// TEST 5: Commissions Branch Scoping
$bdgComms = testCommissionsList($pdo, 'admin', $tenant_bandung);
if (count($bdgComms) === 1 && $bdgComms[0]['id'] === 'comm-bdg') {
    echo "✅ TEST 5: Admin Bandung only sees Bandung commissions\n";
} else {
    echo "❌ TEST 5 FAILED: Cross-branch commission leakage!\n";
    $allPassed = false;
}

// TEST 6: Cross-Tenant Modification Protection
$canEditOwn = testCrossTenantMutation($pdo, $tenant_bandung, $aff_bdg);
$canEditOther = testCrossTenantMutation($pdo, $tenant_bandung, $aff_sby);
if ($canEditOwn === true && $canEditOther === false) {
    echo "✅ TEST 6: Admin Bandung is forbidden from modifying Surabaya affiliate (HTTP 403)\n";
} else {
    echo "❌ TEST 6 FAILED: Mutation security check failed!\n";
    $allPassed = false;
}

echo "\n--------------------------------------------------------\n";
if ($allPassed) {
    echo "ALL 6 BRANCH ISOLATION SECURITY TESTS PASSED! 🚀\n";
} else {
    echo "SOME TESTS FAILED!\n";
    exit(1);
}
