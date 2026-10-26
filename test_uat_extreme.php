<?php
// ============================================================================
// EDUPATH EXTREME UAT & ADVERSARIAL STRESS TEST SUITE
// Zero-Tolerance Quality Assurance, Security Fuzzing & Bug Hunting
// ============================================================================

ini_set('display_errors', 1);
error_reporting(E_ALL);

require_once __DIR__ . '/api/config.php';

$baseUrl = 'http://127.0.0.1:8000/api';

class ExtremeUATRunner {
    private $passed = 0;
    private $failed = 0;
    private $bugsFound = [];
    private $latencies = [];
    public $pdo;

    public function __construct() {
        global $pdo;
        $this->pdo = $pdo;
        try {
            $this->pdo->exec("TRUNCATE TABLE rate_limits");
        } catch (\Throwable $e) {}
    }

    public function assert($condition, $testName, $detail = '', $severity = 'CRITICAL') {
        if ($condition) {
            $this->passed++;
            echo "  [\033[32mPASS\033[0m] $testName" . ($detail ? " ($detail)" : "") . "\n";
        } else {
            $this->failed++;
            $this->bugsFound[] = [
                'severity' => $severity,
                'test' => $testName,
                'detail' => $detail
            ];
            echo "  [\033[31mBUG DETECTED\033[0m] $testName" . ($detail ? " ($detail)" : "") . "\n";
        }
    }

    public function http($url, $method = 'GET', $token = null, $data = null, $headers = [], $rawInput = null) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 15);
        $reqHeaders = [];
        if ($token) $reqHeaders[] = "Authorization: Bearer $token";
        foreach ($headers as $h) $reqHeaders[] = $h;

        // Default content type json if not explicitly overridden
        $hasContentType = false;
        foreach ($reqHeaders as $h) {
            if (stripos($h, 'Content-Type:') !== false) {
                $hasContentType = true;
                break;
            }
        }
        if (!$hasContentType && ($method === 'POST' || $method === 'PUT')) {
            $reqHeaders[] = 'Content-Type: application/json';
        }

        curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);

        $payload = $rawInput;
        if ($payload === null && $data !== null) {
            $payload = is_string($data) ? $data : json_encode($data);
        }

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        } elseif ($method !== 'GET') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
            if ($payload !== null) curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
        }

        $start = microtime(true);
        $raw = curl_exec($ch);
        $durationMs = round((microtime(true) - $start) * 1000, 1);
        $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $body = json_decode($raw, true) ?? $raw;
        return ['code' => $code, 'body' => $body, 'raw' => $raw, 'latency_ms' => $durationMs];
    }

    public function getBugs() {
        return $this->bugsFound;
    }

    public function printSummary() {
        echo "\n====================================================================\n";
        echo "   EXTREME UAT & ADVERSARIAL STRESS TEST SUMMARY\n";
        echo "====================================================================\n";
        echo "Total Test Vectors  : " . ($this->passed + $this->failed) . "\n";
        echo "Total Passed        : {$this->passed}\n";
        echo "Total Bugs Detected : {$this->failed}\n";
        if ($this->failed === 0) {
            echo "\033[32mVERDICT: ZERO DEFECTS UNDER EXTREME FUZZING & ADVERSARIAL TESTING\033[0m\n";
        } else {
            echo "\033[31mVERDICT: BUGS DISCOVERED - ROOT CAUSE FIX REQUIRED\033[0m\n\n";
            echo "--- LIST OF DETECTED DEFECTS ---\n";
            foreach ($this->bugsFound as $idx => $b) {
                echo ($idx + 1) . ". [{$b['severity']}] {$b['test']}: {$b['detail']}\n";
            }
        }
        echo "====================================================================\n\n";
    }
}

$uat = new ExtremeUATRunner();

echo "====================================================================\n";
echo "   STARTING EXTREME UAT & HARDENING VERIFICATION\n";
echo "====================================================================\n\n";

// ----------------------------------------------------------------------------
// SETUP CREDENTIALS
// ----------------------------------------------------------------------------
// 1. Login Super Admin
$saLogin = $uat->http("$baseUrl/admin.php?action=login", 'POST', null, [
    'username' => 'superadmin@uat.edupath.local',
    'password' => 'EduPathSuperAdmin01!2026'
]);
$saToken = $saLogin['body']['token'] ?? null;

// 2. Login Tenant Admin
$admLogin = $uat->http("$baseUrl/admin.php?action=login", 'POST', null, [
    'username' => 'admin@uat.edupath.local',
    'password' => 'EduPathAdmin01!2026'
]);
$admToken = $admLogin['body']['token'] ?? null;

// 3. Register and Login Student A (Pro Subscribed)
$emailStudentA = 'pro_student_' . time() . '@test.edupath.local';
$passStudentA = 'SecureStudent01!2026';
$uat->http("$baseUrl/auth.php?action=register", 'POST', null, [
    'name' => 'Pro Student Alpha',
    'email' => $emailStudentA,
    'password' => $passStudentA
]);
$loginA = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => $emailStudentA,
    'password' => $passStudentA
]);
$tokenA = $loginA['body']['token'] ?? null;
$studentAId = $loginA['body']['user']['id'] ?? null;

// Grant Entitlement to Student A
$stmt = $uat->pdo->prepare("UPDATE students SET plan = 'pejuang' WHERE id = ?");
$stmt->execute([$studentAId]);
require_once __DIR__ . '/api/EntitlementService.php';
$entService = new EntitlementService($uat->pdo);
$entService->grantEntitlement($studentAId, 'tryout_unlimited');
$entService->grantEntitlement($studentAId, 'premium_materials');

// 4. Register Student B (Free / Competitor Student)
$emailStudentB = 'free_student_' . time() . '@test.edupath.local';
$passStudentB = 'SecureStudent02!2026';
$uat->http("$baseUrl/auth.php?action=register", 'POST', null, [
    'name' => 'Free Student Beta',
    'email' => $emailStudentB,
    'password' => $passStudentB
]);
$loginB = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => $emailStudentB,
    'password' => $passStudentB
]);
$tokenB = $loginB['body']['token'] ?? null;
$studentBId = $loginB['body']['user']['id'] ?? null;


// ============================================================================
// SUITE 1: MALFORMED / FUZZED / TYPE INJECTION PAYLOADS
// ============================================================================
echo "1. INPUT FUZZING & TYPE INJECTION RESILIENCE:\n";

// 1.1 Non-JSON body sent to POST endpoints that expect JSON
$res = $uat->http("$baseUrl/quiz.php?action=start", 'POST', $tokenA, null, [], "MALFORMED_NON_JSON_DATA{{{[[[");
$uat->assert($res['code'] !== 500 && $res['code'] !== 0, 
    "Fuzzing: Non-JSON Body to Quiz Start handled safely", "Got HTTP {$res['code']}");

// 1.2 Empty body to auth login
$res = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, null, [], "");
$uat->assert($res['code'] === 400, 
    "Fuzzing: Empty body to auth login rejected with 400", "Got HTTP {$res['code']}");

// 1.3 Array parameter injection in query string: ?education_level[]=sma
$res = $uat->http("$baseUrl/quiz.php?action=questions&education_level[]=sma", 'GET', $tokenA);
$uat->assert($res['code'] !== 500 && $res['code'] !== 0 && !str_contains($res['raw'], 'TypeError'), 
    "Fuzzing: Array query param ?education_level[]=sma handled without TypeError crash", "Got HTTP {$res['code']}");

// 1.4 Array parameter injection in query string: ?subtes[]=1
$res = $uat->http("$baseUrl/materials.php?action=list&subtes[]=1", 'GET', $tokenA);
$uat->assert($res['code'] !== 500 && !str_contains($res['raw'], 'TypeError'), 
    "Fuzzing: Array query param ?subtes[]=1 to materials.php without TypeError crash", "Got HTTP {$res['code']}");

// 1.5 Array parameter injection in query string: ?search[]=test to admin.php
$res = $uat->http("$baseUrl/admin.php?action=students&search[]=test", 'GET', $admToken);
$uat->assert($res['code'] !== 500 && !str_contains($res['raw'], 'TypeError'), 
    "Fuzzing: Array query param ?search[]=test to admin.php without TypeError crash", "Got HTTP {$res['code']}");

// 1.6 Huge Payload (1MB string payload to prevent DoS/memory exhaustion)
$hugeString = str_repeat('A', 500000);
$res = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => $hugeString,
    'password' => 'test'
]);
$uat->assert($res['code'] === 400 || $res['code'] === 401 || $res['code'] === 429, 
    "Fuzzing: Huge 500KB payload in login handled safely", "Got HTTP {$res['code']}");

// 1.7 SQL Injection in login email
$res = $uat->http("$baseUrl/auth.php?action=login", 'POST', null, [
    'email' => "' OR '1'='1' --",
    'password' => 'randompass'
]);
$uat->assert($res['code'] === 400 || $res['code'] === 401, 
    "Security: SQL Injection in auth login blocked", "Got HTTP {$res['code']}");

// 1.8 SQL Injection in subtes query parameter
$res = $uat->http("$baseUrl/quiz.php?action=questions&subtes=" . urlencode("' OR 1=1 --"), 'GET', $tokenA);
$uat->assert($res['code'] !== 500 && !str_contains($res['raw'], 'SQLSTATE'), 
    "Security: SQL Injection in quiz subtes parameter protected by PDO", "Got HTTP {$res['code']}");


// ============================================================================
// SUITE 2: QUIZ & CBT ENGINE ADVERSARIAL STRESS TESTING
// ============================================================================
echo "\n2. QUIZ & CBT ENGINE ADVERSARIAL STRESS TESTING:\n";

// 2.1 Start attempt
$startAttempt = $uat->http("$baseUrl/quiz.php?action=start", 'POST', $tokenA, [
    'quiz_type' => 'tryout',
    'duration_sec' => 3600
]);
$attemptId = $startAttempt['body']['attempt_id'] ?? null;
$questions = $startAttempt['body']['questions'] ?? [];
$uat->assert(!empty($attemptId) && !empty($questions), 
    "CBT: Successfully started Tryout Attempt", "Attempt ID: $attemptId, Questions: " . count($questions));

$sampleQ1 = $questions[0]['id'] ?? '1';

// 2.2 Question Secrecy Verification: ensure 'correct', 'explanation', 'trick' NEVER appear in exam feed
$leakedKeys = [];
foreach ($questions as $q) {
    if (isset($q['correct']) || isset($q['explanation']) || isset($q['trick'])) {
        $leakedKeys[] = $q['id'];
    }
}
$uat->assert(empty($leakedKeys), 
    "CBT Secrecy: No answer keys or explanations leaked in start attempt payload", "Leaked: " . count($leakedKeys));

// 2.3 Autosave with invalid answer data type (array instead of string or null)
$fuzzSave = $uat->http("$baseUrl/quiz.php?action=save", 'POST', $tokenA, [
    'attempt_id' => $attemptId,
    'answers' => [
        $sampleQ1 => ['unexpected' => 'array']
    ]
]);
$uat->assert($fuzzSave['code'] === 200 || $fuzzSave['code'] === 400, 
    "CBT Fuzz: Autosave with unexpected nested array handles safely", "Got HTTP {$fuzzSave['code']}");

// 2.4 Autosave on another student's attempt (Student B trying to save answers on Student A's attempt)
$crossSave = $uat->http("$baseUrl/quiz.php?action=save", 'POST', $tokenB, [
    'attempt_id' => $attemptId,
    'answers' => [
        $sampleQ1 => 'A'
    ]
]);
$uat->assert($crossSave['code'] === 409 || $crossSave['code'] === 403 || $crossSave['code'] === 404, 
    "CBT Security: Cross-student autosave tampering blocked", "Got HTTP {$crossSave['code']}");

// 2.5 Submit Attempt with DUPLICATE Question IDs (Exploit Attempt: answering same question 20 times to inflate score)
$duplicateAnswers = [];
for ($i = 0; $i < 20; $i++) {
    $duplicateAnswers[] = [
        'question_id' => $sampleQ1,
        'answer' => 'A'
    ];
}
$dupSubmit = $uat->http("$baseUrl/quiz.php?action=submit", 'POST', $tokenA, [
    'attempt_id' => $attemptId,
    'quiz_type' => 'tryout',
    'answers' => $duplicateAnswers
]);
// If student submits 20 duplicates of 1 question, total scored should NOT be 20!
$score = (float)($dupSubmit['body']['score'] ?? 0);
$correct = (int)($dupSubmit['body']['correct'] ?? 0);
$uat->assert($dupSubmit['code'] === 200 && $correct <= 1 && $score <= 1.0, 
    "CBT Anti-Exploit: Duplicate question submissions cannot inflate correct count or score", 
    "Correct: $correct, Score: $score (Should be <= 1)");

// 2.6 Reviewing an Attempt: Student B trying to review Student A's result
$resultId = $dupSubmit['body']['result_id'] ?? null;
$crossReview = $uat->http("$baseUrl/quiz.php?action=review&result_id=$resultId", 'GET', $tokenB);
$uat->assert($crossReview['code'] === 403, 
    "CBT RBAC: Student B forbidden from reviewing Student A exam results", "Got HTTP {$crossReview['code']}");

// 2.7 Reviewing non-existent attempt/result
$fakeReview = $uat->http("$baseUrl/quiz.php?action=review&result_id=non-existent-uuid", 'GET', $tokenA);
$uat->assert($fakeReview['code'] === 404, 
    "CBT Edge Case: Reviewing non-existent result returns 404 cleanly", "Got HTTP {$fakeReview['code']}");

// 2.8 Fuzzing answer parameter with array value in submit: {"answers": [{"question_id": "1", "answer": ["A", "B"]}]}
$fuzzAttempt = $uat->http("$baseUrl/quiz.php?action=start", 'POST', $tokenA, ['quiz_type' => 'latihan']);
$fuzzAttId = $fuzzAttempt['body']['attempt_id'] ?? null;
$typeErrorSubmit = $uat->http("$baseUrl/quiz.php?action=submit", 'POST', $tokenA, [
    'attempt_id' => $fuzzAttId,
    'quiz_type' => 'latihan',
    'answers' => [
        ['question_id' => $sampleQ1, 'answer' => ['A', 'B']]
    ]
]);
$uat->assert($typeErrorSubmit['code'] !== 500 && !str_contains($typeErrorSubmit['raw'], 'TypeError'), 
    "CBT Fuzz: Submitting array as answer string handled without TypeError crash", "Got HTTP {$typeErrorSubmit['code']}");


// ============================================================================
// SUITE 3: DIAGNOSTIC & SPP ASSESSMENT EDGE CASES
// ============================================================================
echo "\n3. DIAGNOSTIC & SPP ASSESSMENT EDGE CASES:\n";

// 3.1 Submitting diagnostic with empty answers
$emptyDiag = $uat->http("$baseUrl/diagnostic.php?action=submit", 'POST', $tokenA, [
    'answers' => []
]);
$uat->assert($emptyDiag['code'] === 400, 
    "Diagnostic: Empty answers returns 400 Bad Request", "Got HTTP {$emptyDiag['code']}");

// 3.2 Submitting diagnostic with non-existent question IDs
$fakeDiag = $uat->http("$baseUrl/diagnostic.php?action=submit", 'POST', $tokenA, [
    'answers' => [
        ['question_id' => 'fake-q-1', 'answer' => 'A'],
        ['question_id' => 'fake-q-2', 'answer' => 'B']
    ]
]);
$uat->assert($fakeDiag['code'] !== 500 && !str_contains($fakeDiag['raw'], 'DivisionByZeroError'), 
    "Diagnostic: Submitting questions not in DB handles gracefully without DivisionByZeroError", "Got HTTP {$fakeDiag['code']}");

// 3.3 Verifying certificate with non-existent certificate number
$fakeCert = $uat->http("$baseUrl/spp.php?action=verify_certificate&no=INVALID-CERT-9999", 'GET');
$uat->assert($fakeCert['code'] === 404, 
    "SPP Certificate: Verification of non-existent certificate returns 404", "Got HTTP {$fakeCert['code']}");

// 3.4 Student B trying to fetch Student A's certificate directly
$crossCert = $uat->http("$baseUrl/spp.php?action=get_certificate&attempt_id=$attemptId&student_id=$studentAId", 'GET', $tokenB);
$uat->assert($crossCert['code'] === 400 || $crossCert['code'] === 403 || $crossCert['code'] === 404, 
    "SPP Security: Student B cannot steal Student A certificate", "Got HTTP {$crossCert['code']}");


// ============================================================================
// SUITE 4: PAYMENT, MONETIZATION & MIDTRANS WEBHOOK SECURITY
// ============================================================================
echo "\n4. PAYMENT & MIDTRANS WEBHOOK SECURITY:\n";

// 4.1 Create order with non-existent plan
$badPlan = $uat->http("$baseUrl/payment.php?action=create", 'POST', $tokenA, [
    'plan_id' => 'non-existent-plan-999'
]);
$uat->assert($badPlan['code'] === 404, 
    "Payment: Non-existent plan returns 404", "Got HTTP {$badPlan['code']}");

// 4.2 Webhook: Tampered signature rejection
$fakeWebhook = $uat->http("$baseUrl/payment.php?action=webhook", 'POST', null, [
    'order_id' => 'ORG-999999',
    'status_code' => '200',
    'gross_amount' => '100000',
    'signature_key' => 'TAMPERED_FAKE_SIGNATURE',
    'transaction_status' => 'settlement'
]);
$uat->assert($fakeWebhook['code'] === 403, 
    "Payment Security: Webhook with forged signature rejected with 403", "Got HTTP {$fakeWebhook['code']}");

// 4.3 Webhook: Empty / missing required fields
$emptyWebhook = $uat->http("$baseUrl/payment.php?action=webhook", 'POST', null, []);
$uat->assert($emptyWebhook['code'] === 503 || $emptyWebhook['code'] === 400, 
    "Payment Security: Empty webhook payload rejected safely", "Got HTTP {$emptyWebhook['code']}");


// ============================================================================
// SUITE 5: CROSS-TENANT ISOLATION & PRIVILEGE BOUNDARIES (RBAC)
// ============================================================================
echo "\n5. CROSS-TENANT ISOLATION & PRIVILEGE BOUNDARIES:\n";

// 5.1 Student attempting to call Admin actions
$adminEndpoints = [
    'stats' => 'GET',
    'students' => 'GET',
    'orders' => 'GET',
    'questions' => 'GET',
    'tenants' => 'GET',
    'staff' => 'GET',
    'database_backups' => 'GET'
];

foreach ($adminEndpoints as $act => $m) {
    $r = $uat->http("$baseUrl/admin.php?action=$act", $m, $tokenA);
    $uat->assert($r['code'] === 403, 
        "RBAC Boundary: Student blocked from /admin.php?action=$act", "Got HTTP {$r['code']}");
}

// 5.2 Tenant Admin trying to view Superadmin only endpoints (e.g. tenants list)
$tenantCross = $uat->http("$baseUrl/admin.php?action=tenants", 'GET', $admToken);
$uat->assert($tenantCross['code'] === 403, 
    "Tenant Boundary: Tenant Admin blocked from global tenants list", "Got HTTP {$tenantCross['code']}");

// 5.3 Path Traversal attempt on database backup download
$traversal = $uat->http("$baseUrl/admin.php?action=database_download_backup&filename=../../../../../../windows/win.ini", 'GET', $saToken);
$uat->assert($traversal['code'] === 400 || $traversal['code'] === 404 || $traversal['code'] === 500, 
    "Security: Path traversal on backup download blocked safely", "Got HTTP {$traversal['code']}");


// ============================================================================
// SUITE 6: AFFILIATE COMMISSION & ANTI-FRAUD
// ============================================================================
echo "\n6. AFFILIATE COMMISSION & ANTI-FRAUD:\n";

// 6.1 Payout request with negative amount
$negPayout = $uat->http("$baseUrl/affiliate.php", 'POST', $tokenA, [
    'action' => 'request_payout',
    'amount' => -50000
]);
$uat->assert($negPayout['code'] === 400, 
    "Affiliate: Negative payout request blocked with 400", "Got HTTP {$negPayout['code']}");

// 6.2 Payout request exceeding available balance
$exceedPayout = $uat->http("$baseUrl/affiliate.php", 'POST', $tokenA, [
    'action' => 'request_payout',
    'amount' => 999999999
]);
$uat->assert($exceedPayout['code'] === 400, 
    "Affiliate: Payout exceeding balance blocked with 400", "Got HTTP {$exceedPayout['code']}");


// ============================================================================
// SUITE 7: OFFLINE CLASSES & ROOMS STRESS TESTING
// ============================================================================
echo "\n7. OFFLINE CLASSES & ROOMS STRESS TESTING:\n";

// 7.1 Student trying to create offline class room (must be 403)
$stuRoom = $uat->http("$baseUrl/classes.php?action=rooms", 'POST', $tokenA, [
    'name' => 'Hacked Room',
    'capacity' => 20
]);
$uat->assert($stuRoom['code'] === 403, 
    "Classes RBAC: Student forbidden from creating offline class rooms", "Got HTTP {$stuRoom['code']}");

// 7.2 Admin creating room with empty name (must be 400)
$emptyRoom = $uat->http("$baseUrl/classes.php?action=rooms", 'POST', $admToken, [
    'name' => '',
    'capacity' => 20
]);
$uat->assert($emptyRoom['code'] === 400, 
    "Classes Validation: Empty room name rejected with 400", "Got HTTP {$emptyRoom['code']}");


// ============================================================================
// SUMMARY
// ============================================================================
$uat->printSummary();
