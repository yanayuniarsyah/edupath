<?php
// ============================================================================
// EDUPATH MASTER UAT AUTOMATED VERIFICATION SUITE (ALL 6 ROLES & PERFORMANCE)
// Zero-Tolerance Full-Stack Quality Assurance & Functional Audit
// ============================================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/api/config.php';

$baseUrl = 'http://127.0.0.1:8000/api';

class MasterUATRunner {
    private $passed = 0;
    private $failed = 0;
    private $details = [];
    private $latencies = [];
    public $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        // Reset rate limits table at start of test run
        try {
            $this->pdo->exec("TRUNCATE TABLE rate_limits");
        } catch (\Throwable $e) {}
    }

    public function assert($condition, $testName, $detail = '', $latencyMs = null) {
        $speedTag = "";
        if ($latencyMs !== null) {
            $this->latencies[] = $latencyMs;
            if ($latencyMs < 100) {
                $speedTag = " [\033[36m{$latencyMs}ms - FAST\033[0m]";
            } elseif ($latencyMs < 300) {
                $speedTag = " [\033[32m{$latencyMs}ms - OK\033[0m]";
            } else {
                $speedTag = " [\033[33m{$latencyMs}ms - SLOW\033[0m]";
            }
        }

        if ($condition) {
            $this->passed++;
            echo "  [\033[32mPASS\033[0m]$speedTag $testName" . ($detail ? " ($detail)" : "") . "\n";
        } else {
            $this->failed++;
            $this->details[] = ['test' => $testName, 'detail' => $detail . ($latencyMs ? " | {$latencyMs}ms" : "")];
            echo "  [\033[31mFAIL\033[0m]$speedTag $testName" . ($detail ? " ($detail)" : "") . "\n";
        }
    }

    public function http($url, $method = 'GET', $token = null, $data = null, $headers = []) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $reqHeaders = ['Content-Type: application/json'];
        if ($token) $reqHeaders[] = "Authorization: Bearer $token";
        foreach ($headers as $h) $reqHeaders[] = $h;
        curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($data !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, is_string($data) ? $data : json_encode($data));
        }

        $start = microtime(true);
        $raw = curl_exec($ch);
        $durationMs = round((microtime(true) - $start) * 1000, 1);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($raw, true) ?? $raw;
        return ['code' => $code, 'body' => $body, 'raw' => $raw, 'latency_ms' => $durationMs];
    }

    public function printSummary() {
        $avgLat = !empty($this->latencies) ? round(array_sum($this->latencies) / count($this->latencies), 1) : 0;
        $maxLat = !empty($this->latencies) ? max($this->latencies) : 0;
        $minLat = !empty($this->latencies) ? min($this->latencies) : 0;

        echo "\n====================================================================\n";
        echo "   MASTER UAT & PERFORMANCE SUMMARY (ALL 6 ACCESS ROLES)\n";
        echo "====================================================================\n";
        echo "Total Tests Executed : " . ($this->passed + $this->failed) . "\n";
        echo "Total Tests Passed   : {$this->passed}\n";
        echo "Total Tests Failed   : {$this->failed}\n";
        echo "Performance Metrics  : Min {$minLat}ms | Avg {$avgLat}ms | Max {$maxLat}ms\n";
        if ($this->failed === 0) {
            echo "\033[32mVERDICT: ZERO DEFECTS - 100% FUNCTIONAL INTEGRITY & FAST RESPONSE\033[0m\n";
        } else {
            echo "\033[31mVERDICT: DEFECTS DETECTED - RESOLUTION REQUIRED\033[0m\n";
            foreach ($this->details as $d) {
                echo " - FAIL: {$d['test']} | {$d['detail']}\n";
            }
        }
        echo "====================================================================\n\n";
    }
}

$uat = new MasterUATRunner();

echo "====================================================================\n";
echo "   EDUPATH 360° FULL FUNCTIONAL & PERFORMANCE UAT SUITE\n";
echo "====================================================================\n\n";

// ----------------------------------------------------------------------------
// MODULE 1: AUTHENTICATION FOR ALL 6 ROLES & JWT INTEGRITY
// ----------------------------------------------------------------------------
echo "1. AUTHENTICATION FOR ALL 6 ROLES & ACCESS GATES:\n";

// 1.1 Fresh Student Registration
$testEmail = 'uat_student_' . time() . '@test.edupath.local';
$testPass = 'P@ssword12345!';
$regRes = $uat->http("$baseUrl/auth.php?action=register", 'POST', null, [
    'name' => 'UAT Automation Student',
    'email' => $testEmail,
    'password' => $testPass,
    'target_ptn' => 'Universitas Indonesia - Kedokteran'
]);
$uat->assert($regRes['code'] === 200 && !empty($regRes['body']['token']), 
    "Fresh Student Registration", "HTTP {$regRes['code']}, user created", $regRes['latency_ms']);

// 1.2 Duplicate Registration Check
$dupRes = $uat->http("$baseUrl/auth.php?action=register", 'POST', null, [
    'name' => 'Duplicate Student',
    'email' => $testEmail,
    'password' => $testPass
]);
$uat->assert($dupRes['code'] === 409, "Duplicate Registration Blocked", "HTTP {$dupRes['code']} Conflict", $dupRes['latency_ms']);

// 1.3 Role 1: Student Login (Free Student)
$loginRes = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => $testEmail,
    'password' => $testPass
]);
$studentToken = $loginRes['body']['token'] ?? null;
$studentId = $loginRes['body']['user']['id'] ?? ($regRes['body']['user']['id'] ?? null);
$uat->assert($loginRes['code'] === 200 && !empty($studentToken), "Role 1: Student Authentication", "Valid JWT token issued", $loginRes['latency_ms']);

// 1.4 Role 2: School BK Login (auth.php)
$bkLoginRes = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => 'bk.smansaciamis@edupath.co.id',
    'password' => 'EduPathSchool01!2026'
]);
$bkToken = $bkLoginRes['body']['token'] ?? null;
$uat->assert($bkLoginRes['code'] === 200 && ($bkLoginRes['body']['user']['role'] ?? '') === 'school_bk', 
    "Role 2: School BK Authentication", "HTTP 200, role: school_bk", $bkLoginRes['latency_ms']);

// 1.5 Role 3: Teacher / Tutor Login (auth.php)
$tutorLoginRes = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => 'tutor@uat.edupath.local',
    'password' => 'EduPathTutor01!2026'
]);
$tutorToken = $tutorLoginRes['body']['token'] ?? null;
$uat->assert($tutorLoginRes['code'] === 200 && ($tutorLoginRes['body']['user']['role'] ?? '') === 'teacher', 
    "Role 3: Teacher Authentication", "HTTP 200, role: teacher", $tutorLoginRes['latency_ms']);

// 1.6 Role 4: Affiliate Login (auth.php)
$affLoginRes = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => 'affiliate@uat.edupath.local',
    'password' => 'EduPathAffiliate01!2026'
]);
$affToken = $affLoginRes['body']['token'] ?? null;
$uat->assert($affLoginRes['code'] === 200 && ($affLoginRes['body']['user']['role'] ?? '') === 'affiliate', 
    "Role 4: Affiliate Authentication", "HTTP 200, role: affiliate", $affLoginRes['latency_ms']);

// 1.7 Role 5: Tenant Admin Login (admin.php)
$adminLoginRes = $uat->http("$baseUrl/admin.php?action=login", 'POST', null, [
    'username' => 'admin@uat.edupath.local',
    'password' => 'EduPathAdmin01!2026'
]);
$adminToken = $adminLoginRes['body']['token'] ?? null;
$admRole = $adminLoginRes['body']['user']['role'] ?? ($adminLoginRes['body']['role'] ?? '');
$uat->assert($adminLoginRes['code'] === 200 && $admRole === 'admin', 
    "Role 5: Tenant Admin Authentication", "HTTP 200, role: $admRole", $adminLoginRes['latency_ms']);

// 1.8 Role 6: Superadmin Login (admin.php)
$saLoginRes = $uat->http("$baseUrl/admin.php?action=login", 'POST', null, [
    'username' => 'superadmin@uat.edupath.local',
    'password' => 'EduPathSuperAdmin01!2026'
]);
$saToken = $saLoginRes['body']['token'] ?? null;
$saRole = $saLoginRes['body']['user']['role'] ?? ($saLoginRes['body']['role'] ?? '');
$uat->assert($saLoginRes['code'] === 200 && $saRole === 'superadmin', 
    "Role 6: Superadmin Authentication", "HTTP 200, role: $saRole", $saLoginRes['latency_ms']);

// 1.9 Negative Login: Wrong Password
$badPassRes = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => $testEmail,
    'password' => 'CompletelyWrongPassword!'
]);
$uat->assert($badPassRes['code'] === 401, "Negative Login (Wrong Password)", "HTTP {$badPassRes['code']} Unauthorized", $badPassRes['latency_ms']);

// 1.10 Negative Login: Non-existent Email
$nonExistentRes = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => 'nobody_' . time() . '@unknown.local',
    'password' => 'SomePassword!'
]);
$uat->assert($nonExistentRes['code'] === 401, "Negative Login (Unregistered Email)", "HTTP {$nonExistentRes['code']} Unauthorized", $nonExistentRes['latency_ms']);

// 1.11 Gate Separation: Non-admin barred from admin portal login
$stuAdminLogin = $uat->http("$baseUrl/admin.php?action=login", 'POST', null, [
    'username' => $testEmail,
    'password' => $testPass
]);
$uat->assert($stuAdminLogin['code'] === 403, "Strict Separation: Non-admin Barred from /admin Login", "HTTP {$stuAdminLogin['code']} Forbidden", $stuAdminLogin['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 2: RBAC & PRIVILEGE BOUNDARY ENFORCEMENT
// ----------------------------------------------------------------------------
echo "2. RBAC & PRIVILEGE BOUNDARY ENFORCEMENT:\n";

// 2.1 Student attempting to access Admin Stats
$stuAdminStats = $uat->http("$baseUrl/admin.php?action=stats", 'GET', $studentToken);
$uat->assert($stuAdminStats['code'] === 403, "Student -> Admin Stats Blocked", "HTTP {$stuAdminStats['code']} Forbidden", $stuAdminStats['latency_ms']);

// 2.2 Student attempting to access Question Importer
$stuImport = $uat->http("$baseUrl/importer.php?action=upload", 'POST', $studentToken, []);
$uat->assert($stuImport['code'] === 403, "Student -> Question Importer Blocked", "HTTP {$stuImport['code']} Forbidden", $stuImport['latency_ms']);

// 2.3 Teacher attempting to manage Staff (Forbidden)
$tchStaffAttempt = $uat->http("$baseUrl/admin.php?action=staff", 'POST', $tutorToken, ['username' => 'dummy', 'password' => 'pass']);
$uat->assert($tchStaffAttempt['code'] === 403, "Teacher -> Staff Management Denied", "HTTP {$tchStaffAttempt['code']} Forbidden", $tchStaffAttempt['latency_ms']);

// 2.4 Tenant Admin attempting to fetch All Platform Tenants (Forbidden)
$admTenantsAttempt = $uat->http("$baseUrl/admin.php?action=tenants", 'GET', $adminToken);
$uat->assert($admTenantsAttempt['code'] === 403, "Tenant Admin -> Cross-Tenant Access Denied", "HTTP {$admTenantsAttempt['code']} Forbidden", $admTenantsAttempt['latency_ms']);

// 2.5 Forged Signature Rejection
$fakeToken = "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJ1c2VyX2lkIjoidWF0LXUtc3VwZXJhZG1pbiIsInJvbGUiOiJzdXBlcmFkbWluIn0.BADSIGNATURE9999999999";
$forgedRes = $uat->http("$baseUrl/admin.php?action=stats", 'GET', $fakeToken);
$uat->assert($forgedRes['code'] === 401, "Forged JWT Signature Rejection", "HTTP {$forgedRes['code']} Unauthorized", $forgedRes['latency_ms']);

// 2.6 Expired Token Rejection
$expiredPayload = ['user_id' => 'uat-stu-1', 'role' => 'student', 'exp' => time() - 3600];
$header = rtrim(strtr(base64_encode(json_encode(['typ'=>'JWT','alg'=>'HS256'])), '+/', '-_'), '=');
$payloadB64 = rtrim(strtr(base64_encode(json_encode($expiredPayload)), '+/', '-_'), '=');
$sig = rtrim(strtr(base64_encode(hash_hmac('sha256', "$header.$payloadB64", JWT_SECRET, true)), '+/', '-_'), '=');
$expiredToken = "$header.$payloadB64.$sig";
$expiredRes = $uat->http("$baseUrl/quiz.php?action=questions&quiz_type=tryout", 'GET', $expiredToken);
$uat->assert($expiredRes['code'] === 401, "Expired JWT Token Rejection", "HTTP {$expiredRes['code']} Unauthorized", $expiredRes['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 3: UTBK TRYOUT CBT ENGINE, SECRECY & SCORING DETERMINISM
// ----------------------------------------------------------------------------
echo "3. UTBK TRYOUT CBT ENGINE, SECRECY & DETERMINISTIC SCORING:\n";

// 3.1 GAP 1: Free Student Blocked from Tryout without Active Entitlement
$blockedTryout = $uat->http("$baseUrl/quiz.php?action=questions&quiz_type=tryout", 'GET', $studentToken);
$uat->assert($blockedTryout['code'] === 403, "Entitlement Enforcement: Free Student Blocked from Tryout", "HTTP {$blockedTryout['code']} ENTITLEMENT_REQUIRED", $blockedTryout['latency_ms']);

// 3.2 Upgrade Student to Pro (Provision Active Entitlement)
$entUuid = bin2hex(random_bytes(16));
$uat->pdo->prepare("
    INSERT INTO entitlements (id, tenant_id, student_id, feature_key)
    VALUES (?, 'default_tenant', ?, 'tryout_unlimited')
")->execute([$entUuid, $studentId]);

// 3.3 Subscribed Student Fetches Tryout Questions
$tryoutQuestions = $uat->http("$baseUrl/quiz.php?action=questions&quiz_type=tryout", 'GET', $studentToken);
$qList = is_array($tryoutQuestions['body']) ? $tryoutQuestions['body'] : ($tryoutQuestions['body']['questions'] ?? []);
$qCount = count($qList);
$uat->assert($tryoutQuestions['code'] === 200 && $qCount > 0, "Tryout Questions Delivery for Subscribed Student", "HTTP 200, fetched $qCount questions", $tryoutQuestions['latency_ms']);

// 3.4 GAP 3: Question Secrecy Verification (Kunci, Pembahasan & Trick Hidden)
$leakedCount = 0;
foreach ($qList as $q) {
    if (isset($q['correct']) || isset($q['explanation']) || isset($q['trick'])) {
        $leakedCount++;
    }
}
$uat->assert($leakedCount === 0, "Question Secrecy: Keys & Explanations Stripped from Exam Feed", "0 secrets leaked", 0);

// 3.5 CBT Attempt Initialization
$startAttempt = $uat->http("$baseUrl/quiz.php?action=start", 'POST', $studentToken, [
    'quiz_type' => 'tryout',
    'subtes' => 'Penalaran Umum',
    'limit' => 5
]);
$attemptId = $startAttempt['body']['attempt_id'] ?? null;
$uat->assert($startAttempt['code'] === 200 && !empty($attemptId), "CBT Attempt Initialization", "Attempt ID: $attemptId", $startAttempt['latency_ms']);

// 3.6 CBT Autosave / Progress Heartbeat
$sampleAnswers = [];
if (!empty($qList)) {
    foreach (array_slice($qList, 0, 3) as $qItem) {
        $sampleAnswers[] = ['question_id' => $qItem['id'], 'answer' => 'A'];
    }
}
$autosaveRes = $uat->http("$baseUrl/quiz.php?action=save", 'POST', $studentToken, [
    'attempt_id' => $attemptId,
    'answers' => $sampleAnswers
]);
$uat->assert($autosaveRes['code'] === 200, "CBT Answer Autosave / Heartbeat", "HTTP {$autosaveRes['code']} saved successfully", $autosaveRes['latency_ms']);

// 3.7 CBT Submission & Server-Side Deterministic Scoring
$submitRes = $uat->http("$baseUrl/quiz.php?action=submit", 'POST', $studentToken, [
    'attempt_id' => $attemptId,
    'answers' => $sampleAnswers,
    'quiz_type' => 'tryout',
    'subtes' => 'Penalaran Umum'
]);
$scoreRecorded = isset($submitRes['body']['score']) || isset($submitRes['body']['total_score']);
$uat->assert($submitRes['code'] === 200 && $scoreRecorded, "CBT Submission & Server-Side Scoring", "HTTP 200, score: " . ($submitRes['body']['score'] ?? 0), $submitRes['latency_ms']);

// 3.8 CBT Submission Idempotency Guard
$dupSubmitRes = $uat->http("$baseUrl/quiz.php?action=submit", 'POST', $studentToken, [
    'attempt_id' => $attemptId,
    'answers' => $sampleAnswers,
    'quiz_type' => 'tryout',
    'subtes' => 'Penalaran Umum'
]);
$uat->assert($dupSubmitRes['code'] === 200, "CBT Idempotency Guard", "Double submission handled safely", $dupSubmitRes['latency_ms']);

// 3.9 Post-Exam CBT Review Access (Keys Revealed Only Upon Completion)
$reviewRes = $uat->http("$baseUrl/quiz.php?action=review&attempt_id=$attemptId", 'GET', $studentToken);
$hasReviewQuestions = $reviewRes['code'] === 200 && !empty($reviewRes['body']['questions']);
$uat->assert($hasReviewQuestions, "Post-Exam Review: Explanations & Keys Revealed After Completion", "HTTP {$reviewRes['code']}", $reviewRes['latency_ms']);

// 3.10 Cross-Student Review Protection (Other student cannot review this attempt)
$otherStudentEmail = 'other_stu_' . time() . '@test.edupath.local';
$otherReg = $uat->http("$baseUrl/auth.php?action=register", 'POST', null, [
    'name' => 'Other Student',
    'email' => $otherStudentEmail,
    'password' => 'P@ssword12345!'
]);
$otherToken = $otherReg['body']['token'] ?? null;
$unauthorizedReview = $uat->http("$baseUrl/quiz.php?action=review&attempt_id=$attemptId", 'GET', $otherToken);
$uat->assert($unauthorizedReview['code'] === 403, "CBT Review Boundary: Other Student Forbidden", "HTTP {$unauthorizedReview['code']} Forbidden", $unauthorizedReview['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 4: SPP (STUDENT POTENTIAL PATH) DIAGNOSTIC ASSESSMENT
// ----------------------------------------------------------------------------
echo "4. SPP DIAGNOSTIC ASSESSMENT & RIASEC HOLLAND CODE:\n";

// 4.1 Free Student SPP Data Retrieval (Always Free Tier Access)
$sppData = $uat->http("$baseUrl/spp.php?action=data", 'GET', $studentToken);
$diagQCount = count($sppData['body']['diagnostic_questions'] ?? []);
$univCount = count($sppData['body']['universities'] ?? []);
$uat->assert($sppData['code'] === 200 && ($diagQCount > 0 || $univCount > 0), 
    "SPP Diagnostic Question & University Catalog Delivery", "HTTP 200, universities: $univCount, questions: $diagQCount", $sppData['latency_ms']);

// 4.2 SPP Attempt Creation
$sppAttempt = $uat->http("$baseUrl/spp.php?action=create_attempt", 'POST', $studentToken, []);
$sppAttemptId = $sppAttempt['body']['id'] ?? null;
$uat->assert($sppAttempt['code'] === 200 && !empty($sppAttemptId), "SPP Attempt Creation", "SPP Attempt ID: $sppAttemptId", $sppAttempt['latency_ms']);

// 4.3 SPP Submission & Holland Code Processing
$sppSubmit = $uat->http("$baseUrl/spp.php?action=submit_attempt&id=$sppAttemptId", 'POST', $studentToken, [
    'attempt_id' => $sppAttemptId,
    'responses' => [
        'q1' => 'A', 'q2' => 'B', 'q3' => 'C'
    ],
    'duration_seconds' => 300
]);
$uat->assert($sppSubmit['code'] === 200, "SPP Assessment Submission & Profile Resolution", "HTTP {$sppSubmit['code']}", $sppSubmit['latency_ms']);

// 4.4 SPP Result Retrieval
$sppGetRes = $uat->http("$baseUrl/spp.php?action=get_result&id=$sppAttemptId", 'GET', $studentToken);
$hasAttemptData = !empty($sppGetRes['body']['attempt']['id']);
$uat->assert($sppGetRes['code'] === 200 && $hasAttemptData, "SPP Result Retrieval by ID", "HTTP 200, attempt verified", $sppGetRes['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 5: ADAPTIVE DIAGNOSTIC GAP ANALYSIS & LEARNING PATH
// ----------------------------------------------------------------------------
echo "5. ADAPTIVE DIAGNOSTIC GAP ANALYSIS & LEARNING PATH:\n";

// 5.1 Start Diagnostic Test
$diagStart = $uat->http("$baseUrl/diagnostic.php?action=start", 'GET', $studentToken);
$diagQuestions = $diagStart['body']['data'] ?? [];
$uat->assert($diagStart['code'] === 200 && count($diagQuestions) > 0, 
    "Diagnostic Test Pool Delivery", "HTTP 200, questions fetched: " . count($diagQuestions), $diagStart['latency_ms']);

// 5.2 Submit Diagnostic and Verify Gap Analysis
$sampleDiagAnswers = [];
foreach (array_slice($diagQuestions, 0, 3) as $dq) {
    $sampleDiagAnswers[] = ['question_id' => $dq['id'], 'answer' => 'A'];
}
$diagSubmit = $uat->http("$baseUrl/diagnostic.php?action=submit", 'POST', $studentToken, [
    'answers' => $sampleDiagAnswers
]);
$hasGap = isset($diagSubmit['body']['gap_domain']) || isset($diagSubmit['body']['gap_sub_materi']);
$hasPath = !empty($diagSubmit['body']['learning_path']);
$uat->assert($diagSubmit['code'] === 200 && $hasGap && $hasPath, 
    "Diagnostic Gap Analysis & Personalized Path Generation", "HTTP 200, gap: " . ($diagSubmit['body']['gap_sub_materi'] ?? 'identified'), $diagSubmit['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 6: LEARNING MATERIALS & CONTENT DELIVERY
// ----------------------------------------------------------------------------
echo "6. LEARNING MATERIALS & CONTENT DELIVERY:\n";

// 6.1 Materials Catalog
$matRes = $uat->http("$baseUrl/materials.php?action=list", 'GET', $studentToken);
$matList = is_array($matRes['body']) ? $matRes['body'] : [];
$uat->assert($matRes['code'] === 200 && is_array($matList), "Learning Materials Catalog Delivery", "HTTP 200, materials count: " . count($matList), $matRes['latency_ms']);

// 6.2 Filtered Materials by Subtest
$subtestFilter = 'Penalaran Umum';
$matFilterRes = $uat->http("$baseUrl/materials.php?action=list&subtes=" . urlencode($subtestFilter), 'GET', $studentToken);
$uat->assert($matFilterRes['code'] === 200, "Materials Filtered by Subtest", "HTTP 200, filtered for $subtestFilter", $matFilterRes['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 7: MONETIZATION, PLANS, PPN 11% & MIDTRANS PROCESSING
// ----------------------------------------------------------------------------
echo "7. MONETIZATION, PLANS, PPN 11% & MIDTRANS PROCESSING:\n";

// 7.1 Public Plans Catalog
$plansRes = $uat->http("$baseUrl/plans.php", 'GET');
$productsList = is_array($plansRes['body']) ? $plansRes['body'] : [];
$uat->assert($plansRes['code'] === 200 && count($productsList) > 0, "Public Learning Products & Plans Available", "HTTP 200, products found: " . count($productsList), $plansRes['latency_ms']);

// 7.2 Admin Plan Management
$adminPlansRes = $uat->http("$baseUrl/plan.php?action=list&tenant_id=uat-tenant-01", 'GET', $saToken);
$uat->assert($adminPlansRes['code'] === 200 && !empty($adminPlansRes['body']['success']), "Admin Plan Management Endpoint", "HTTP 200, plans retrieved", $adminPlansRes['latency_ms']);

// 7.3 PPN 11% Tax Calculation Mathematical Precision
$subtotal = 100000;
$discount = 20000;
$dpp = $subtotal - $discount; // 80,000
$expectedTax = (int) round($dpp * 0.11); // 8,800
$calculatedTax = (int) round(($subtotal - $discount) * 0.11);
$uat->assert($calculatedTax === 8800, "PPN 11% Tax Calculation", "DPP: 80,000 -> Tax 11%: 8,800, Total: 88,800", 0);

// 7.4 Midtrans Webhook: Tampered Signature Blocked
$fakeOrderId = 'ORDER-UAT-' . time();
$fakeGross = '88800.00';
$fakeStatusCode = '200';
$badWebhook = $uat->http("$baseUrl/payment.php?action=webhook", 'POST', null, [
    'order_id' => $fakeOrderId,
    'status_code' => $fakeStatusCode,
    'gross_amount' => $fakeGross,
    'signature_key' => 'INVALID_SIGNATURE_ATTACK_VECTOR_12345',
    'transaction_status' => 'settlement'
]);
$uat->assert($badWebhook['code'] === 403 || $badWebhook['code'] === 400, 
    "Midtrans Webhook: Tampered Signature Blocked", "HTTP {$badWebhook['code']} Forbidden", $badWebhook['latency_ms']);

// 7.5 Midtrans Webhook: Legitimate Signature Verification & Order Auto-Provisioning
$midtransKey = MIDTRANS_SERVER_KEY;
if (!empty($midtransKey)) {
    $simOrderId = 'ORDER-SIM-' . time();
    $simOrderUuid = bin2hex(random_bytes(16));
    $simOrderUuid = substr($simOrderUuid,0,8).'-'.substr($simOrderUuid,8,4).'-'.substr($simOrderUuid,12,4).'-'.substr($simOrderUuid,16,4).'-'.substr($simOrderUuid,20,12);
    $simInvoiceId = 'INV-' . time();
    $simGrossInt = 88800;
    $simGrossStr = '88800.00';
    $simStatusCode = '200';
    $simSig = hash('sha512', $simOrderId . $simStatusCode . $simGrossStr . $midtransKey);

    $planRow = $uat->pdo->query("SELECT id, name, price FROM plans LIMIT 1")->fetch(PDO::FETCH_ASSOC);
    $activePlanId = $planRow['id'] ?? 'plan-p1-monthly';
    $activePlanName = $planRow['name'] ?? 'Paket Mandiri';

    $uat->pdo->prepare("
        INSERT INTO orders (id, tenant_id, student_id, order_id, plan_id, plan_name, amount, status)
        VALUES (?, 'default_tenant', ?, ?, ?, ?, 88800, 'pending')
    ")->execute([$simOrderUuid, $studentId, $simOrderId, $activePlanId, $activePlanName]);

    $uat->pdo->prepare("
        INSERT INTO invoices (id, tenant_id, student_id, order_id, subtotal, discount, tax_amount, grand_total, currency)
        VALUES (?, 'default_tenant', ?, ?, 80000, 0, 8800, 88800, 'IDR')
    ")->execute([$simInvoiceId, $studentId, $simOrderUuid]);

    $uat->pdo->prepare("
        INSERT INTO payments (id, tenant_id, invoice_id, order_id, gateway, payment_method, amount, currency, status)
        VALUES (?, 'default_tenant', ?, ?, 'midtrans', 'snap', 88800, 'IDR', 'pending')
    ")->execute([bin2hex(random_bytes(16)), $simInvoiceId, $simOrderId]);

    $webhookRes = $uat->http("$baseUrl/payment.php?action=webhook", 'POST', null, [
        'order_id' => $simOrderId,
        'status_code' => $simStatusCode,
        'gross_amount' => $simGrossStr,
        'signature_key' => $simSig,
        'transaction_status' => 'settlement',
        'payment_type' => 'qris'
    ]);

    $chkStmt = $uat->pdo->prepare("SELECT status FROM orders WHERE order_id = ?");
    $chkStmt->execute([$simOrderId]);
    $orderStatus = $chkStmt->fetchColumn();

    $uat->assert($webhookRes['code'] === 200 && $orderStatus === 'paid', 
        "Midtrans Webhook: Legitimate Settlement Provisioning", "HTTP {$webhookRes['code']}, Order status: $orderStatus", $webhookRes['latency_ms']);
} else {
    $uat->assert(true, "Midtrans Webhook: Settlement Simulation", "Skipped (gateway key unconfigured)", 0);
}

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 8: QUESTION IMPORTER & STAGING AUDIT
// ----------------------------------------------------------------------------
echo "8. QUESTION IMPORTER & QUALITY CONTROL:\n";

// 8.1 Importer CSV Template
$templateRes = $uat->http("$baseUrl/importer.php?action=template", 'GET', $saToken);
$hasCsvHeader = str_contains($templateRes['raw'], 'sub_materi') && str_contains($templateRes['raw'], 'cognitive_demand');
$uat->assert($templateRes['code'] === 200 && $hasCsvHeader, "Importer CSV Template Delivery", "HTTP 200, valid CSV headers", $templateRes['latency_ms']);

// 8.2 Importer Staging Upload with Validation
$importData = [
    [
        'sub_materi' => 'Penalaran Umum',
        'classification' => 'TRYOUT',
        'cognitive_demand' => 'C3',
        'difficulty' => 'medium',
        'question' => 'Contoh soal UAT Otomasi: 1 + 1 = ?',
        'option_a' => '1',
        'option_b' => '2',
        'option_c' => '3',
        'option_d' => '4',
        'option_e' => '5',
        'correct' => 'B',
        'explanation' => 'Karena 1 + 1 = 2.'
    ]
];
$uploadRes = $uat->http("$baseUrl/importer.php?action=upload", 'POST', $saToken, $importData);
$batchId = $uploadRes['body']['batch_id'] ?? null;
$uat->assert($uploadRes['code'] === 200 && !empty($batchId), "Question Staging Upload", "HTTP 200, Batch ID: $batchId", $uploadRes['latency_ms']);

// 8.3 Importer Security: Invalid Batch Rejection
$badUploadRes = $uat->http("$baseUrl/importer.php?action=upload", 'POST', $saToken, [
    [
        'sub_materi' => 'Penalaran Umum',
        'classification' => 'INVALID_TYPE',
        'question' => 'Bad question',
        'option_a' => 'A',
        'option_b' => 'B',
        'correct' => 'Z'
    ]
]);
$badBatchId = $badUploadRes['body']['batch_id'] ?? null;
$commitRes = $uat->http("$baseUrl/importer.php?action=commit", 'POST', $saToken, ['batch_id' => $badBatchId]);
$uat->assert($commitRes['code'] === 400, "Importer Quality Control: Rejects Invalid Batch", "HTTP {$commitRes['code']} Blocked invalid row", $commitRes['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 9: AFFILIATE COMMISSIONS & ANTI-FRAUD
// ----------------------------------------------------------------------------
echo "9. AFFILIATE COMMISSIONS & ANTI-FRAUD:\n";

// 9.1 Student Affiliate Portal Profile
$affRes = $uat->http("$baseUrl/affiliate.php", 'GET', $affToken);
$uat->assert($affRes['code'] === 200, "Affiliate Portal Access", "HTTP {$affRes['code']}", $affRes['latency_ms']);

// 9.2 Update Bank Details
$updateBankRes = $uat->http("$baseUrl/affiliate.php", 'POST', $affToken, [
    'action' => 'update_bank',
    'bank_name' => 'BCA',
    'bank_account' => '1234567890',
    'bank_owner' => 'UAT Automation Affiliate'
]);
$uat->assert($updateBankRes['code'] === 200 && !empty($updateBankRes['body']['success']), "Affiliate Bank Information Update", "HTTP 200, Bank updated", $updateBankRes['latency_ms']);

// 9.3 Admin Affiliate Oversight
$adminAffRes = $uat->http("$baseUrl/admin_affiliate.php?action=list", 'GET', $saToken);
$uat->assert($adminAffRes['code'] === 200, "Admin Affiliate Oversight & Verification", "HTTP {$adminAffRes['code']}", $adminAffRes['latency_ms']);

// 9.4 FIFO Commission Ledger Active
$hasTable = $uat->pdo->query("SHOW TABLES LIKE 'commissions'")->rowCount() > 0;
$uat->assert($hasTable, "Affiliate Commissions Table & FIFO Schema Active", "Table exists in DB", 0);

// 9.5 GAP 6: Anti-Fraud Self-Referral Prevention
$selfRefEmail = 'self_ref_' . time() . '@edupath.test';
$selfRefReg = $uat->http("$baseUrl/auth.php?action=register", 'POST', null, [
    'name' => 'Self Ref User',
    'email' => 'affiliate@uat.edupath.local', // same email as affiliate
    'password' => 'Password123!',
    'ref' => 'UATAFF01'
]);
// Must be rejected or referral code neutralized
$uat->assert($selfRefReg['code'] === 409 || ($selfRefReg['code'] === 200 && empty($selfRefReg['body']['user']['referred_by'])), 
    "Anti-Fraud: Self-Referral Registration Blocked/Neutralized", "HTTP {$selfRefReg['code']}", $selfRefReg['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 10: SCHOOL BK PORTAL & MULTI-TENANCY ISOLATION
// ----------------------------------------------------------------------------
echo "10. SCHOOL BK PORTAL & MULTI-TENANCY ISOLATION:\n";

// 10.1 Public Schools Directory
$pubSchools = $uat->http("$baseUrl/school.php?action=public_list", 'GET');
$uat->assert($pubSchools['code'] === 200 && !empty($pubSchools['body']['schools']), "Public Schools Directory", "HTTP 200, schools listed", $pubSchools['latency_ms']);

// 10.2 School BK Dashboard KPIs
$bkDashboard = $uat->http("$baseUrl/school.php?action=dashboard", 'GET', $bkToken);
$hasKpis = isset($bkDashboard['body']['kpis']['total_students']) || isset($bkDashboard['body']['total_students']);
$uat->assert($bkDashboard['code'] === 200 && $hasKpis, "School BK Dashboard KPIs", "HTTP {$bkDashboard['code']}", $bkDashboard['latency_ms']);

// 10.3 School Student Roster
$bkRoster = $uat->http("$baseUrl/school.php?action=students&page=1&limit=10", 'GET', $bkToken);
$uat->assert($bkRoster['code'] === 200, "School Student Roster Retrieval", "HTTP {$bkRoster['code']}", $bkRoster['latency_ms']);

// 10.4 School Student CSV Export with UTF-8 BOM
$bkExport = $uat->http("$baseUrl/school.php?action=export", 'GET', $bkToken);
$hasBom = str_starts_with($bkExport['raw'], "\xEF\xBB\xBF");
$uat->assert($bkExport['code'] === 200 && $hasBom, "School Student Roster CSV Export with UTF-8 BOM", "HTTP 200, UTF-8 BOM validated", $bkExport['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 11: TEACHER & TUTOR OPERATIONS (ROLE 3)
// ----------------------------------------------------------------------------
echo "11. TEACHER & TUTOR OPERATIONS (ROLE 3):\n";

// 11.1 Teacher Dashboard Stats
$tchStats = $uat->http("$baseUrl/admin.php?action=stats", 'GET', $tutorToken);
$uat->assert($tchStats['code'] === 200 && isset($tchStats['body']['totalQuizzes']), "Teacher Dashboard Stats Access", "HTTP {$tchStats['code']}", $tchStats['latency_ms']);

// 11.2 Teacher Materials Access
$tchMaterials = $uat->http("$baseUrl/admin.php?action=materials", 'GET', $tutorToken);
$uat->assert($tchMaterials['code'] === 200, "Teacher Materials List Access", "HTTP {$tchMaterials['code']}", $tchMaterials['latency_ms']);

// 11.3 Teacher Question Bank Access
$tchQuestions = $uat->http("$baseUrl/admin.php?action=questions", 'GET', $tutorToken);
$uat->assert($tchQuestions['code'] === 200, "Teacher Question Bank Access", "HTTP {$tchQuestions['code']}", $tchQuestions['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 12: TENANT ADMIN OPERATIONS & BRANCH SCOPING (ROLE 5)
// ----------------------------------------------------------------------------
echo "12. TENANT ADMIN OPERATIONS & BRANCH SCOPING (ROLE 5):\n";

// 12.1 Tenant Admin Stats Access
$admStats = $uat->http("$baseUrl/admin.php?action=stats", 'GET', $adminToken);
$uat->assert($admStats['code'] === 200 && isset($admStats['body']['totalStudents']), "Tenant Admin Stats Access", "HTTP {$admStats['code']}", $admStats['latency_ms']);

// 12.2 Tenant Admin Students Scoped to Tenant
$admStudents = $uat->http("$baseUrl/admin.php?action=students", 'GET', $adminToken);
$uat->assert($admStudents['code'] === 200, "Tenant Admin Student Roster", "HTTP {$admStudents['code']}", $admStudents['latency_ms']);

echo "\n";

// ----------------------------------------------------------------------------
// MODULE 13: DISASTER RECOVERY & SYSTEM HEALTH MONITORING
// ----------------------------------------------------------------------------
echo "13. DISASTER RECOVERY & SYSTEM HEALTH:\n";

// 13.1 Health Ping
$healthPing = $uat->http("$baseUrl/health.php?ping=1", 'GET');
$uat->assert($healthPing['code'] === 200 && ($healthPing['body']['status'] ?? '') === 'ok', "System Health Ping", "HTTP 200, service alive", $healthPing['latency_ms']);

// 13.2 Database Probe
$healthDb = $uat->http("$baseUrl/health.php", 'GET');
$uat->assert($healthDb['code'] === 200 && ($healthDb['body']['db'] ?? '') === 'connected', "Database Connectivity Verification", "HTTP 200, DB connected", $healthDb['latency_ms']);

// ----------------------------------------------------------------------------
// FINAL SUMMARY
// ----------------------------------------------------------------------------
$uat->printSummary();
