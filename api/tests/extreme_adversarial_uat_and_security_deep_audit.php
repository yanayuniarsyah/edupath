<?php
// api/tests/extreme_adversarial_uat_and_security_deep_audit.php
// EXTREME ADVERSARIAL UAT, COMPREHENSIVE SECURITY AUDIT & ACCESS LATENCY VERIFIER

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../jwt.php';

$baseUrl = 'http://127.0.0.1:8000';
$passed = 0;
$failed = 0;
$total = 0;

function report($name, $status, $detail = '', $latency = null) {
    global $passed, $failed, $total;
    $total++;
    $timeStr = $latency !== null ? sprintf(" (%.2f ms)", $latency) : "";
    if ($status === 'PASS') {
        $passed++;
        echo "  [PASS] $name$timeStr\n";
    } else {
        $failed++;
        echo "  [FAIL] $name$timeStr: $detail\n";
    }
}

function req($method, $path, $headers = [], $body = null) {
    global $baseUrl;
    $url = $baseUrl . $path;
    $ch = curl_init($url);
    $reqHeaders = [];
    foreach ($headers as $k => $v) {
        $reqHeaders[] = "$k: $v";
    }
    curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
    curl_setopt($ch, CURLOPT_HEADER, true);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);
    
    if ($body !== null) {
        $data = is_array($body) ? json_encode($body) : $body;
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        if (!isset($headers['Content-Type'])) {
            $reqHeaders[] = "Content-Type: application/json";
            curl_setopt($ch, CURLOPT_HTTPHEADER, $reqHeaders);
        }
    }
    
    $start = microtime(true);
    $response = curl_exec($ch);
    $latencyMs = (microtime(true) - $start) * 1000;
    
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $headerSize = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
    $rawHeader = substr($response, 0, $headerSize);
    $rawBody = substr($response, $headerSize);
    curl_close($ch);
    
    $parsedHeaders = [];
    foreach (explode("\r\n", $rawHeader) as $line) {
        if (strpos($line, ':') !== false) {
            list($hk, $hv) = explode(':', $line, 2);
            $parsedHeaders[strtolower(trim($hk))] = trim($hv);
        }
    }
    
    $json = json_decode($rawBody, true);
    return [
        'code' => $httpCode,
        'headers' => $parsedHeaders,
        'body' => $rawBody,
        'json' => $json,
        'latency_ms' => $latencyMs
    ];
}

echo "====================================================================\n";
echo "EXTREME ADVERSARIAL UAT & COMPREHENSIVE SECURITY AUDIT SUITE\n";
echo "====================================================================\n\n";

// ── 1. AUDIT SECURITY: Web Access Blocking for Sensitive Files ──
echo "GATE 1: Direct Web Access Blocking for Administrative & Internal Files\n";
$sensitiveFiles = [
    '/import_kitab_snbt.php',
    '/import_tryout_questions.php',
    '/install_db.php',
    '/deploy-ftp.js',
    '/deploy.bat',
    '/.env',
    '/api/.env'
];

foreach ($sensitiveFiles as $sFile) {
    $res = req('GET', $sFile);
    // In local PHP built-in server, PHP CLI files will exit with 403 from the CLI check
    // On Apache, .htaccess blocks with 403
    $isBlocked = ($res['code'] === 403 || $res['code'] === 404 || strpos($res['body'], 'Akses ditolak') !== false);
    report("Block Direct Access to $sFile", $isBlocked ? 'PASS' : 'FAIL', "Got HTTP {$res['code']}: " . substr($res['body'], 0, 60));
}

// ── 2. AUDIT SECURITY: Adversarial Input & Fuzzing (SQLi / XSS / Type Juggling) ──
echo "\nGATE 2: Adversarial Input Fuzzing (SQLi, XSS, Null Bytes)\n";
$fuzzPayloads = [
    "' OR '1'='1",
    "'; DROP TABLE students; --",
    "<script>alert('xss')</script>",
    "admin'/*",
    "../../../../etc/passwd",
    "null\0byte"
];

$sqliProtected = true;
foreach ($fuzzPayloads as $p) {
    $res = req('POST', '/api/auth.php?action=login', [], [
        'email' => $p,
        'password' => 'BadPassword123!'
    ]);
    if ($res['code'] === 200 && ($res['json']['success'] ?? false) === true) {
        $sqliProtected = false;
        report("Fuzzing Bypass with payload", 'FAIL', "Bypassed with payload: $p");
        break;
    }
    if (stripos($res['body'], 'SQL syntax') !== false || stripos($res['body'], 'PDOException') !== false) {
        $sqliProtected = false;
        report("SQL Error Leaked with payload", 'FAIL', "Leaked SQL error: " . substr($res['body'], 0, 100));
        break;
    }
}
if ($sqliProtected) {
    report("All Fuzzing Payloads Neutralized Without Leakage", 'PASS');
}

// ── 3. AUDIT SECURITY: JWT Signature Hardening & Alg=None Defense ──
echo "\nGATE 3: JWT Hardening (Alg=None & Signature Tampering)\n";
$studentId = 'test_sec_student_' . uniqid();
$validToken = generate_jwt([
    'id' => $studentId,
    'role' => 'student',
    'tenant_id' => 'default_tenant',
    'email' => 'sec_student@edupath.local'
]);

// Alg=none attack
$tokenParts = explode('.', $validToken);
$fakeHeader = rtrim(strtr(base64_encode(json_encode(['typ' => 'JWT', 'alg' => 'none'])), '+/', '-_'), '=');
$algNoneToken = "$fakeHeader.{$tokenParts[1]}.";
$resNone = req('GET', '/api/progress.php?action=unified', [
    'Authorization' => "Bearer $algNoneToken"
]);
$isNoneRejected = ($resNone['code'] === 401 || $resNone['code'] === 403);
report("JWT Alg=none Attack Neutralized", $isNoneRejected ? 'PASS' : 'FAIL', "Got HTTP {$resNone['code']}");

// Tampered payload attack
$tamperedPayload = rtrim(strtr(base64_encode(json_encode([
    'id' => $studentId,
    'role' => 'superadmin',
    'tenant_id' => 'default_tenant'
])), '+/', '-_'), '=');
$tamperedToken = "{$tokenParts[0]}.$tamperedPayload.{$tokenParts[2]}";
$resTampered = req('GET', '/api/admin.php?action=dashboard_stats', [
    'Authorization' => "Bearer $tamperedToken"
]);
$isTamperedRejected = ($resTampered['code'] === 401 || $resTampered['code'] === 403);
report("JWT Tampered Signature Attack Neutralized", $isTamperedRejected ? 'PASS' : 'FAIL', "Got HTTP {$resTampered['code']}");

// ── 4. AUDIT SECURITY: RBAC Privilege Escalation Defense ──
echo "\nGATE 4: RBAC Student Privilege Escalation Defense\n";
$resEscalate = req('GET', '/api/admin.php?action=students', [
    'Authorization' => "Bearer $validToken"
]);
$isEscalateBlocked = ($resEscalate['code'] === 403);
report("Student Access to /api/admin.php Blocked with 403 Forbidden", $isEscalateBlocked ? 'PASS' : 'FAIL', "Got HTTP {$resEscalate['code']}");

// ── 5. AUDIT SECURITY: IDOR Cross-Student Isolation ──
echo "\nGATE 5: IDOR Cross-Student Quiz Review Isolation\n";
$studentBId = 'victim_student_' . uniqid();
$pdo->prepare("INSERT INTO students (id, tenant_id, name, email, password, plan, is_active) VALUES (?, 'default_tenant', 'Victim Student', ?, 'hash', 'free', 1)")
    ->execute([$studentBId, "victim_$studentBId@test.com"]);

$quizResultId = 'res_' . uniqid();
$pdo->prepare("INSERT INTO quiz_results (id, student_id, quiz_type, subtes, score, correct, total, duration_sec, answers) VALUES (?, ?, 'LATIHAN', 'Penalaran Umum', 80, 8, 10, 300, '[]')")
    ->execute([$quizResultId, $studentBId]);

$resIdor = req('GET', "/api/quiz.php?action=review&result_id=$quizResultId", [
    'Authorization' => "Bearer $validToken"
]);
$isIdorBlocked = ($resIdor['code'] === 403);
report("Cross-Student IDOR Attempt Blocked with 403 Forbidden", $isIdorBlocked ? 'PASS' : 'FAIL', "Got HTTP {$resIdor['code']}");

// ── 6. AUDIT PERFORMANCE: API Response Times & ETag 304 Caching ──
echo "\nGATE 6: Access Time & Network Bandwidth Optimization\n";
$resPlans = req('GET', '/api/plans.php');
report("Catalog /api/plans.php Response Time", $resPlans['latency_ms'] < 60 ? 'PASS' : 'FAIL', "Latency: {$resPlans['latency_ms']} ms", $resPlans['latency_ms']);

$etag = $resPlans['headers']['etag'] ?? '';
if (!empty($etag)) {
    $resPlans304 = req('GET', '/api/plans.php', ['If-None-Match' => $etag]);
    $is304 = ($resPlans304['code'] === 304);
    report("Catalog ETag HTTP 304 Cache Response", $is304 ? 'PASS' : 'FAIL', "Code {$resPlans304['code']}, {$resPlans304['latency_ms']} ms", $resPlans304['latency_ms']);
} else {
    report("Catalog ETag HTTP 304 Cache Response", 'FAIL', "No ETag header returned");
}

$resProg = req('GET', '/api/programs.php');
report("Programs /api/programs.php Response Time", $resProg['latency_ms'] < 60 ? 'PASS' : 'FAIL', "Latency: {$resProg['latency_ms']} ms", $resProg['latency_ms']);

$etagProg = $resProg['headers']['etag'] ?? '';
if (!empty($etagProg)) {
    $resProg304 = req('GET', '/api/programs.php', ['If-None-Match' => $etagProg]);
    $isProg304 = ($resProg304['code'] === 304);
    report("Programs ETag HTTP 304 Cache Response", $isProg304 ? 'PASS' : 'FAIL', "Code {$resProg304['code']}, {$resProg304['latency_ms']} ms", $resProg304['latency_ms']);
} else {
    report("Programs ETag HTTP 304 Cache Response", 'FAIL', "No ETag header returned");
}

$resMat = req('GET', '/api/materials.php?action=list&education_level=SMA&subtest=Penalaran%20Umum');
report("Materials /api/materials.php Response Time", $resMat['latency_ms'] < 60 ? 'PASS' : 'FAIL', "Latency: {$resMat['latency_ms']} ms", $resMat['latency_ms']);

$etagMat = $resMat['headers']['etag'] ?? '';
if (!empty($etagMat)) {
    $resMat304 = req('GET', '/api/materials.php?action=list&education_level=SMA&subtest=Penalaran%20Umum', ['If-None-Match' => $etagMat]);
    $isMat304 = ($resMat304['code'] === 304);
    report("Materials ETag HTTP 304 Cache Response", $isMat304 ? 'PASS' : 'FAIL', "Code {$resMat304['code']}, {$resMat304['latency_ms']} ms", $resMat304['latency_ms']);
} else {
    report("Materials ETag HTTP 304 Cache Response", 'FAIL', "No ETag header returned");
}

// ── 7. EXTREME UAT: Multi-Program Catalog Completeness ──
echo "\nGATE 7: Multi-Program Academic Catalog Completeness\n";
$catalog = $resPlans['json']['packages'] ?? [];
$programsFound = [];
foreach ($catalog as $pkg) {
    $programsFound[$pkg['academic_program'] ?? 'unknown'] = true;
}
$expectedPrograms = ['TKA SD', 'TKA SMP', 'TKA SMA', 'SNPMB/UTBK', 'SNPMB Alumni'];
$allProgsPresent = true;
foreach (['TKA SD', 'TKA SMP', 'TKA SMA', 'SNPMB/UTBK'] as $ep) {
    if (!isset($programsFound[$ep])) {
        $allProgsPresent = false;
        break;
    }
}
report("Universal Catalog Availability (SD, SMP, SMA, SNPMB)", $allProgsPresent ? 'PASS' : 'FAIL', "Programs: " . implode(', ', array_keys($programsFound)));

// ── 8. EXTREME UAT: Secret Leaking Prevention (Answer Key Security) ──
echo "\nGATE 8: Secret Leaking Prevention (Tryout / Quiz Answer Key Gating)\n";
$resQuestions = req('GET', '/api/quiz.php?action=questions&quiz_type=TRYOUT&assessment_type=SNBT', [
    'Authorization' => "Bearer $validToken"
]);
$qList = $resQuestions['json']['questions'] ?? [];
$secretLeaked = false;
foreach ($qList as $q) {
    if (isset($q['correct']) || isset($q['explanation']) || isset($q['trick'])) {
        $secretLeaked = true;
        break;
    }
}
report("Zero Answer Key Leakage During Active Quiz Generation", !$secretLeaked ? 'PASS' : 'FAIL', $secretLeaked ? "Answer key was exposed in question payload!" : "Verified");

echo "\n====================================================================\n";
echo sprintf("AUDIT SUMMARY: %d / %d TESTS PASSED (Failed: %d)\n", $passed, $total, $failed);
echo "====================================================================\n";

if ($failed > 0) {
    exit(1);
} else {
    echo "🎉 ALL SECURITY AUDIT & ACCESS OPTIMIZATION GATES VERIFIED 100%!\n";
    exit(0);
}
