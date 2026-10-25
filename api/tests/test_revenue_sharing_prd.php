<?php
// api/tests/test_revenue_sharing_prd.php
// Comprehensive Quality Gates Verification Suite for PRD v1.2

echo "==================================================================\n";
echo "TEST SUITE: EDUPATH PRD v1.2 REVENUE SHARING & WATERFALL ENGINE\n";
echo "==================================================================\n\n";

require_once __DIR__ . '/../RevenueSplitEngine.php';
require_once __DIR__ . '/../FinancialLedger.php';
require_once __DIR__ . '/../RoyaltyRegistry.php';

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Setup in-memory tables matching production schema
$pdo->exec("
    CREATE TABLE orders (
        id TEXT PRIMARY KEY,
        tenant_id TEXT,
        student_id TEXT,
        plan_id TEXT,
        plan_name TEXT,
        amount REAL,
        affiliate_id TEXT,
        channel TEXT,
        branch_tenant_id TEXT,
        school_id TEXT,
        community_id TEXT,
        is_renewal INTEGER DEFAULT 0,
        gateway_fee REAL DEFAULT 0,
        royalty_fee REAL DEFAULT 0,
        distributable_pool REAL DEFAULT 0,
        rule_snapshot TEXT,
        status TEXT,
        created_at TEXT
    );

    CREATE TABLE financial_ledger (
        id TEXT PRIMARY KEY,
        order_id TEXT,
        tenant_id TEXT,
        channel TEXT,
        entry_type TEXT,
        party_type TEXT,
        party_id TEXT,
        amount REAL,
        is_credit INTEGER,
        rule_snapshot TEXT,
        status TEXT,
        reversal_ref_id TEXT,
        notes TEXT,
        created_at TEXT
    );

    CREATE TABLE content_owners (
        id TEXT PRIMARY KEY,
        name TEXT,
        email TEXT,
        status TEXT
    );

    CREATE TABLE royalty_contracts (
        id TEXT PRIMARY KEY,
        owner_id TEXT,
        title TEXT,
        plan_id TEXT,
        package_key TEXT,
        percentage_rate REAL DEFAULT 0,
        per_use_rate REAL DEFAULT 0,
        valid_from TEXT,
        valid_to TEXT,
        is_active INTEGER DEFAULT 1,
        terms_notes TEXT
    );

    CREATE TABLE royalty_accruals (
        id TEXT PRIMARY KEY,
        contract_id TEXT,
        owner_id TEXT,
        order_id TEXT,
        percentage_amount REAL,
        usage_amount REAL,
        total_amount REAL,
        status TEXT,
        created_at TEXT
    );

    CREATE TABLE commissions (
        id TEXT PRIMARY KEY,
        affiliate_id TEXT,
        tenant_id TEXT,
        order_id TEXT,
        amount REAL,
        commission_rate_snapshot REAL,
        status TEXT
    );
");

$passed = 0;
$total = 0;

function assertCondition(bool $cond, string $label, string $detail = '') {
    global $passed, $total;
    $total++;
    if ($cond) {
        $passed++;
        echo "✅ GATE $total PASS: $label\n";
    } else {
        echo "❌ GATE $total FAIL: $label ($detail)\n";
    }
}

// ----------------------------------------------------------------------
// QUALITY GATE 1: First Subscription via Affiliator with Branch Partner
// PRD Section 8.1 Example:
// G = 200,000, Gateway = 5,000 (2.5%), Royalty = 12,000 (5% = 10,000 + 2,000 use)
// Pool = 200,000 - 5,000 - 12,000 = 183,000
// HQ = 50% * 183,000 = 91,500
// Affiliate = 20% * 183,000 = 36,600
// Branch Net = (50% - 20%) * 183,000 = 30% * 183,000 = 54,900
// ----------------------------------------------------------------------
$res1 = RevenueSplitEngine::calculate([
    'gross_amount'            => '200000.00',
    'channel'                 => 'AFFILIATE',
    'is_renewal'              => false, // First subscription
    'gateway_fee'             => '5000.00',
    'royalty_fee'             => '12000.00',
    'affiliate_id'            => 'aff-cilacap-01',
    'branch_tenant_id'        => 'branch-cilacap'
]);

assertCondition(
    $res1['distributable_pool'] === '183000.00' &&
    $res1['hq_amount'] === '91500.00' &&
    $res1['affiliate_amount'] === '36600.00' &&
    $res1['branch_amount'] === '54900.00',
    "First Subscription Waterfall Math (HQ: 91.5k, Aff: 36.6k, Branch: 54.9k, Pool: 183k)",
    json_encode($res1)
);

// ----------------------------------------------------------------------
// QUALITY GATE 2: Monthly / Annual Renewal via Affiliator with Branch Partner
// PRD Section 8.2 Example:
// Pool = 183,000
// HQ = 50% * 183,000 = 91,500
// Affiliate = 10% * 183,000 = 18,300 (cut from Branch 50%)
// Branch Net = (50% - 10%) * 183,000 = 40% * 183,000 = 73,200
// ----------------------------------------------------------------------
$res2 = RevenueSplitEngine::calculate([
    'gross_amount'            => '200000.00',
    'channel'                 => 'AFFILIATE',
    'is_renewal'              => true, // Renewal
    'gateway_fee'             => '5000.00',
    'royalty_fee'             => '12000.00',
    'affiliate_id'            => 'aff-cilacap-01',
    'branch_tenant_id'        => 'branch-cilacap'
]);

assertCondition(
    $res2['distributable_pool'] === '183000.00' &&
    $res2['hq_amount'] === '91500.00' &&
    $res2['affiliate_amount'] === '18300.00' &&
    $res2['branch_amount'] === '73200.00',
    "Renewal Subscription Waterfall Math (HQ: 91.5k, Aff: 18.3k, Branch: 73.2k, Pool: 183k)",
    json_encode($res2)
);

// ----------------------------------------------------------------------
// QUALITY GATE 3: Invariance & Zero Float Drift
// Gross Amount = HQ + Branch + Affiliate + Gateway + Royalty
// ----------------------------------------------------------------------
$sum1 = bcadd(bcadd(bcadd(bcadd($res1['hq_amount'], $res1['branch_amount'], 2), $res1['affiliate_amount'], 2), $res1['gateway_fee'], 2), $res1['royalty_fee'], 2);
$sum2 = bcadd(bcadd(bcadd(bcadd($res2['hq_amount'], $res2['branch_amount'], 2), $res2['affiliate_amount'], 2), $res2['gateway_fee'], 2), $res2['royalty_fee'], 2);

assertCondition(
    $sum1 === '200000.00' && $sum2 === '200000.00',
    "Financial Invariance: Exact zero-drift sum equals Gross Amount ($200,000.00)"
);

// ----------------------------------------------------------------------
// QUALITY GATE 4: Independent Affiliator without Branch Partner
// PRD Section 4.2: If affiliator does not have a branch, do NOT pay branch.
// ----------------------------------------------------------------------
$resIndep = RevenueSplitEngine::calculate([
    'gross_amount'            => '200000.00',
    'channel'                 => 'AFFILIATE',
    'is_renewal'              => false,
    'gateway_fee'             => '5000.00',
    'royalty_fee'             => '12000.00',
    'affiliate_id'            => 'aff-independent-99',
    'branch_tenant_id'        => null // Independent
]);

assertCondition(
    $resIndep['branch_amount'] === '0.00' &&
    $resIndep['affiliate_amount'] === '36600.00' &&
    $resIndep['hq_amount'] === '146400.00' && // 91,500 + 54,900
    $resIndep['unassigned_branch'] === '54900.00',
    "Independent Affiliate: Branch payout is 0.00, Affiliate receives 36.6k, no orphan branch payout"
);

// ----------------------------------------------------------------------
// QUALITY GATE 5: School & Community Channel with 10% Combined Cap
// PRD Section 4.3 & 8.3:
// S = max 10% of G. (e.g. 7% school, 3% community = 10% = 20,000)
// Pool = 200,000 - 5,000 - 12,000 - 20,000 = 163,000 to HQ
// ----------------------------------------------------------------------
$resSchool = RevenueSplitEngine::calculate([
    'gross_amount'               => '200000.00',
    'channel'                    => 'SCHOOL',
    'gateway_fee'                => '5000.00',
    'royalty_fee'                => '12000.00',
    'school_id'                  => 'school-sman1',
    'school_commission_rate'     => '7.00',
    'community_id'               => 'comm-sman1-alumni',
    'community_commission_rate'  => '3.00'
]);

assertCondition(
    $resSchool['school_amount'] === '14000.00' && // 7% of 200,000
    $resSchool['community_amount'] === '6000.00' && // 3% of 200,000
    $resSchool['hq_amount'] === '163000.00' &&
    $resSchool['affiliate_amount'] === '0.00' &&
    $resSchool['branch_amount'] === '0.00',
    "School & Community Channel: School 7% (14k), Comm 3% (6k), HQ remainder (163k)"
);

// ----------------------------------------------------------------------
// QUALITY GATE 6: School/Community Cap Enforcement
// If school asks for 10% and community asks for 5% (total 15%),
// system must automatically scale/cap total combined to max 10% (20,000).
// ----------------------------------------------------------------------
$resCap = RevenueSplitEngine::calculate([
    'gross_amount'               => '200000.00',
    'channel'                    => 'SCHOOL',
    'gateway_fee'                => '5000.00',
    'royalty_fee'                => '0.00',
    'school_commission_rate'     => '10.00',
    'community_commission_rate'  => '5.00' // Total requested 15%
]);

$combinedS = bcadd($resCap['school_amount'], $resCap['community_amount'], 2);
assertCondition(
    $combinedS === '200000.00' * 0.10 . '.00' || bccomp($combinedS, '20000.00', 2) === 0,
    "School/Community Cap: Combined allocation is strictly capped at max 10% of G ($20,000.00)"
);

// ----------------------------------------------------------------------
// QUALITY GATE 7: Direct Sales Channel
// PRD Section 4.4 & 8.4:
// G = 200,000, F = 5,000, R = 12,000 => HQ = 183,000. No affiliate/branch.
// ----------------------------------------------------------------------
$resDirect = RevenueSplitEngine::calculate([
    'gross_amount'            => '200000.00',
    'channel'                 => 'DIRECT',
    'gateway_fee'             => '5000.00',
    'royalty_fee'             => '12000.00'
]);

assertCondition(
    $resDirect['hq_amount'] === '183000.00' &&
    $resDirect['affiliate_amount'] === '0.00' &&
    $resDirect['branch_amount'] === '0.00',
    "Direct Sales Channel: 183,000 to HQ, 0 to affiliate and branch"
);

// ----------------------------------------------------------------------
// QUALITY GATE 8: Royalty Registry Contract Matching (% and Per-Use)
// PRD Section 6.1: R = Percentage + Usage
// ----------------------------------------------------------------------
$pdo->exec("
    INSERT INTO content_owners (id, name, email, status) VALUES ('owner-pak-budi', 'Pak Budi Guru Matematika', 'budi@gmail.com', 'active');
    INSERT INTO royalty_contracts 
        (id, owner_id, title, plan_id, percentage_rate, per_use_rate, valid_from, is_active)
    VALUES 
        ('contract-snbt-01', 'owner-pak-budi', 'Soal UTBK SNBT Matematika', 'plan-pro-01', 5.00, 2000.00, '2026-01-01', 1);
");

$royaltyCalc = RoyaltyRegistry::calculateRoyalties($pdo, 'plan-pro-01', '200000.00');
assertCondition(
    $royaltyCalc['total_royalty'] === '12000.00' &&
    $royaltyCalc['percentage_total'] === '10000.00' &&
    $royaltyCalc['usage_total'] === '2000.00' &&
    count($royaltyCalc['contracts']) === 1,
    "Royalty Registry Contract Matching: 5% (10k) + per-use (2k) = 12k total royalty"
);

// ----------------------------------------------------------------------
// QUALITY GATE 9: Financial Ledger Posting & Double-Entry Balance
// PRD Section 10.5
// ----------------------------------------------------------------------
$orderId = 'ord-test-001';
$entries = FinancialLedger::postOrderSplit($pdo, $orderId, 'tenant-cilacap', 'AFFILIATE', $res1);
assertCondition(
    count($entries) === 6, // GROSS_SALES, GATEWAY_FEE, ROYALTY_EXPENSE, HQ_REVENUE, AFFILIATE_COMMISSION, BRANCH_NET_REVENUE
    "Financial Ledger Posting: Exactly 6 traceable immutable line items posted for order $orderId"
);

// Verify Party Balances
$affBal = FinancialLedger::getPartyBalance($pdo, 'AFFILIATE', 'aff-cilacap-01');
$branchBal = FinancialLedger::getPartyBalance($pdo, 'BRANCH', 'branch-cilacap');
$hqBal = FinancialLedger::getPartyBalance($pdo, 'HQ', 'HQ_CENTRAL');

assertCondition(
    $affBal === '36600.00' && $branchBal === '54900.00' && $hqBal === '91500.00',
    "Financial Ledger Balances: Aff: 36.6k, Branch: 54.9k, HQ: 91.5k exactly matches waterfall"
);

// ----------------------------------------------------------------------
// QUALITY GATE 10: Webhook Idempotency Guard (No Double Commissions)
// PRD Section 12 Quality Gate
// ----------------------------------------------------------------------
$dupEntries = FinancialLedger::postOrderSplit($pdo, $orderId, 'tenant-cilacap', 'AFFILIATE', $res1);
assertCondition(
    count($dupEntries) === 0,
    "Idempotency Guard: Reposting same order produces 0 duplicate entries"
);

// ----------------------------------------------------------------------
// QUALITY GATE 11: Refund & Chargeback Reversal Protocol
// PRD Section 10.5 & 12: Reverse ledger without deleting historical entries
// ----------------------------------------------------------------------
$reversalsCount = FinancialLedger::reverseOrderLedger($pdo, $orderId, 'Student Requested Refund');
$affBalAfterRefund = FinancialLedger::getPartyBalance($pdo, 'AFFILIATE', 'aff-cilacap-01');
$branchBalAfterRefund = FinancialLedger::getPartyBalance($pdo, 'BRANCH', 'branch-cilacap');
$hqBalAfterRefund = FinancialLedger::getPartyBalance($pdo, 'HQ', 'HQ_CENTRAL');

assertCondition(
    $reversalsCount === 6 &&
    $affBalAfterRefund === '0.00' &&
    $branchBalAfterRefund === '0.00' &&
    $hqBalAfterRefund === '0.00',
    "Refund Reversals: 6 counter-entries created, all balances returned to 0.00 without deleting history"
);

// ----------------------------------------------------------------------
// QUALITY GATE 12: Historical Immutability (Rule Snapshot Preservation)
// PRD Section 12: Future rule changes do not retroactively alter past orders
// ----------------------------------------------------------------------
$stmt = $pdo->prepare("SELECT rule_snapshot FROM financial_ledger WHERE order_id = ? LIMIT 1");
$stmt->execute([$orderId]);
$savedSnapshot = json_decode($stmt->fetchColumn(), true);

assertCondition(
    $savedSnapshot['prd_version'] === '1.2' &&
    $savedSnapshot['affiliate_rate_applied'] === '20%' &&
    $savedSnapshot['branch_rate_applied'] === '30%',
    "Rule Snapshot Preservation: Exact parameters at execution time are permanently preserved in JSON snapshot"
);

echo "\n------------------------------------------------------------------\n";
echo "SUMMARY: $passed / $total QUALITY GATES PASSED PERFECTLY!\n";
echo "------------------------------------------------------------------\n";

if ($passed === $total) {
    echo "🎉 ALL PRD v1.2 QUALITY GATES VERIFIED SUCCESSFULLY!\n";
    exit(0);
} else {
    echo "❌ SOME QUALITY GATES FAILED.\n";
    exit(1);
}
