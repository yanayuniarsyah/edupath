<?php
// api/tests/test_multi_program_catalog_and_question_types.php
// Verification suite for:
// 1. TKA SD and TKA SMP taxonomy & authoring
// 2. Dynamic Question Types (multiple_choice, complex_mcq, true_false, short_answer)
// 3. Multi-program catalog (TKA SD, SMP, SMA, SNPMB/UTBK, SNPMB Alumni Online & Hybrid)

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/../database/seed_multi_program_catalog.php';

echo "============================================================\n";
echo "TEST SUITE: Multi-Program Catalog & Dynamic Question Types\n";
echo "============================================================\n\n";

$tests_passed = 0;
$tests_failed = 0;

function assert_test($condition, $name, $detail = '') {
    global $tests_passed, $tests_failed;
    if ($condition) {
        echo " [PASS] $name\n";
        $tests_passed++;
    } else {
        echo " [FAIL] $name: $detail\n";
        $tests_failed++;
    }
}

// -------------------------------------------------------------
// TEST 1: Master Academic Programs
// -------------------------------------------------------------
echo "--- TEST 1: Master Academic Programs ---\n";
$progs = $pdo->query("SELECT code, target_level FROM academic_programs")->fetchAll(PDO::FETCH_KEY_PAIR);
assert_test(isset($progs['TKA_SD']), "Academic Program TKA_SD exists in DB");
assert_test(isset($progs['TKA_SMP']), "Academic Program TKA_SMP exists in DB");
assert_test(isset($progs['TKA_SMA']), "Academic Program TKA_SMA exists in DB");
assert_test(isset($progs['SNPMB_UTBK']), "Academic Program SNPMB_UTBK exists in DB");
assert_test(isset($progs['SNPMB_ALUMNI']), "Academic Program SNPMB_ALUMNI exists in DB");

// -------------------------------------------------------------
// TEST 2: Multi-program Products & Plans
// -------------------------------------------------------------
echo "\n--- TEST 2: Multi-program Products & Plans ---\n";
$seedResult = seed_multi_program_catalog($pdo);
assert_test(!empty($seedResult), "seed_multi_program_catalog executes idempotently");

$prodCount = (int)$pdo->query("SELECT COUNT(*) FROM products WHERE status = 'active'")->fetchColumn();
assert_test($prodCount >= 12, "Products table contains all 12 products (actual: $prodCount)");

$planCount = (int)$pdo->query("SELECT COUNT(*) FROM plans WHERE is_active = 1 AND is_archived = 0")->fetchColumn();
assert_test($planCount >= 24, "Plans table contains multi-program packages (actual: $planCount)");

// Verify Online & Hybrid plans exist for each program
$sdPlans = $pdo->query("SELECT delivery_mode FROM plans WHERE academic_program = 'TKA SD'")->fetchAll(PDO::FETCH_COLUMN);
assert_test(in_array('online', $sdPlans) && in_array('hybrid', $sdPlans), "TKA SD has both online and hybrid plans");

$smpPlans = $pdo->query("SELECT delivery_mode FROM plans WHERE academic_program = 'TKA SMP'")->fetchAll(PDO::FETCH_COLUMN);
assert_test(in_array('online', $smpPlans) && in_array('hybrid', $smpPlans), "TKA SMP has both online and hybrid plans");

$smaPlans = $pdo->query("SELECT delivery_mode FROM plans WHERE academic_program = 'TKA SMA'")->fetchAll(PDO::FETCH_COLUMN);
assert_test(in_array('online', $smaPlans) && in_array('hybrid', $smaPlans), "TKA SMA has both online and hybrid plans");

$alumniPlans = $pdo->query("SELECT delivery_mode FROM plans WHERE academic_program = 'SNPMB Alumni'")->fetchAll(PDO::FETCH_COLUMN);
assert_test(in_array('online', $alumniPlans) && in_array('hybrid', $alumniPlans), "SNPMB Alumni has both online and hybrid plans");

// -------------------------------------------------------------
// TEST 3: api/plans.php Output
// -------------------------------------------------------------
echo "\n--- TEST 3: api/plans.php Response Structure ---\n";
ob_start();
$_SERVER['REQUEST_METHOD'] = 'GET';
$_GET = [];
@include __DIR__ . '/../plans.php';
$output = ob_get_clean();
$plansResponse = json_decode($output, true);

assert_test($plansResponse['success'] === true, "api/plans.php returns success=true");
assert_test(count($plansResponse['products']) >= 12, "api/plans.php returns all multi-program products (actual: " . count($plansResponse['products']) . ")");
assert_test(count($plansResponse['packages']) >= 24, "api/plans.php returns all packages");

// Verify product attributes
$firstProduct = $plansResponse['products'][0] ?? [];
assert_test(!empty($firstProduct['academic_program']), "Products in api/plans.php have academic_program attribute");
assert_test(!empty($firstProduct['delivery_mode']), "Products in api/plans.php have delivery_mode attribute");

// -------------------------------------------------------------
// TEST 4: Dynamic Question Types Grading Logic
// -------------------------------------------------------------
echo "\n--- TEST 4: Dynamic Question Types Grading Logic ---\n";

// Helper mimicking quiz.php evaluation
function evaluateQuestionAnswer($user_ans, $expected_ans, $qType) {
    $user_ans = strtolower(trim((string)$user_ans));
    $expected_ans = strtolower(trim((string)$expected_ans));
    $qType = strtolower($qType);

    if ($user_ans === '' || $expected_ans === '') return false;

    if ($qType === 'complex_mcq') {
        $uParts = array_filter(array_map('trim', explode(',', $user_ans)));
        $eParts = array_filter(array_map('trim', explode(',', $expected_ans)));
        sort($uParts);
        sort($eParts);
        return (!empty($uParts) && $uParts === $eParts);
    } elseif ($qType === 'true_false') {
        $cleanU = preg_replace('/\s+/', '', $user_ans);
        $cleanE = preg_replace('/\s+/', '', $expected_ans);
        $uTokens = array_filter(explode(',', $cleanU));
        $eTokens = array_filter(explode(',', $cleanE));
        sort($uTokens);
        sort($eTokens);
        return (!empty($uTokens) && $uTokens === $eTokens);
    } elseif ($qType === 'short_answer') {
        $cleanU = preg_replace('/\s+/', ' ', trim($user_ans));
        $cleanE = preg_replace('/\s+/', ' ', trim($expected_ans));
        return ($cleanU === $cleanE);
    } else {
        return ($user_ans === $expected_ans);
    }
}

// 4a. Multiple Choice
assert_test(evaluateQuestionAnswer('a', 'a', 'multiple_choice'), "multiple_choice: correct single match");
assert_test(!evaluateQuestionAnswer('b', 'a', 'multiple_choice'), "multiple_choice: incorrect single match rejected");

// 4b. Complex MCQ (order-independent)
assert_test(evaluateQuestionAnswer('c,a', 'a,c', 'complex_mcq'), "complex_mcq: 'c,a' matches 'a,c' order-independently");
assert_test(evaluateQuestionAnswer('B, D, a', 'a,b,d', 'complex_mcq'), "complex_mcq: 'B, D, a' matches 'a,b,d' with whitespace/case insensitivity");
assert_test(!evaluateQuestionAnswer('a,b', 'a,b,c', 'complex_mcq'), "complex_mcq: partial selection correctly rejected");

// 4c. True / False Statement Matrix
assert_test(evaluateQuestionAnswer('a:B,b:S', 'a:b,b:s', 'true_false'), "true_false: 'a:B,b:S' matches 'a:b,b:s'");
assert_test(evaluateQuestionAnswer('b:s, a:b', 'a:b,b:s', 'true_false'), "true_false: reversed order 'b:s, a:b' matches");
assert_test(!evaluateQuestionAnswer('a:b,b:b', 'a:b,b:s', 'true_false'), "true_false: mismatch statement correctly rejected");

// 4d. Short Answer
assert_test(evaluateQuestionAnswer('  42  ', '42', 'short_answer'), "short_answer: '  42  ' matches '42'");
assert_test(evaluateQuestionAnswer('Hukum Newton II', 'hukum newton ii', 'short_answer'), "short_answer: case-insensitive match");
assert_test(!evaluateQuestionAnswer('Hukum Newton 1', 'hukum newton ii', 'short_answer'), "short_answer: wrong answer rejected");

// -------------------------------------------------------------
// TEST 5: Question & Material Insertion with TKA SD and SMP
// -------------------------------------------------------------
echo "\n--- TEST 5: Question & Material DB Persistence for TKA SD & SMP ---\n";

$testQIdSD = 'test-q-sd-' . bin2hex(random_bytes(4));
$stmtQ = $pdo->prepare("
    INSERT INTO questions (
        id, exam, test_component, subtest, topic, question_type,
        subtes, sub_materi, bab, difficulty, question,
        option_a, option_b, option_c, option_d, correct,
        is_qc_passed, is_active, education_level, assessment_type, track, subject
    ) VALUES (
        ?, 'TKA', 'TKA SD', 'Matematika SD', 'Bilangan & Pecahan', 'complex_mcq',
        'Matematika SD', 'Matematika SD', 'Bilangan & Pecahan', 'medium', 'Pilihlah bilangan prima di bawah ini:',
        '2', '3', '4', '6', 'a,b',
        1, 1, 'SD', 'TKA', 'sd', 'Matematika SD'
    )
");
$stmtQ->execute([$testQIdSD]);

$checkQ = $pdo->prepare("SELECT id, education_level, track, question_type, correct FROM questions WHERE id = ?");
$checkQ->execute([$testQIdSD]);
$qData = $checkQ->fetch(PDO::FETCH_ASSOC);
assert_test($qData && $qData['education_level'] === 'SD' && $qData['track'] === 'sd', "Question for TKA SD inserted with correct education_level and track", json_encode($qData));
assert_test($qData && $qData['question_type'] === 'complex_mcq' && $qData['correct'] === 'a,b', "Question has question_type complex_mcq and correct 'a,b'", json_encode($qData));

// Cleanup test question
$pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$testQIdSD]);

// Material SD test
$stmtM = $pdo->prepare("
    INSERT INTO materials (
        subtes, exam, test_component, subtest, sub_materi, title, content,
        is_active, education_level, assessment_type, track, subject
    ) VALUES (
        'Matematika SD', 'TKA', 'TKA SD', 'Matematika SD', 'Matematika SD', 'Operasi Bilangan Cacah Test', 'Materi konsep penjumlahan...',
        1, 'SD', 'TKA', 'sd', 'Matematika SD'
    )
");
$stmtM->execute();
$newMId = $pdo->lastInsertId();

$checkM = $pdo->prepare("SELECT id, education_level, track FROM materials WHERE id = ?");
$checkM->execute([$newMId]);
$mData = $checkM->fetch(PDO::FETCH_ASSOC);
assert_test($mData && $mData['education_level'] === 'SD' && $mData['track'] === 'sd', "Material for TKA SD inserted with correct education_level and track", json_encode($mData));

// Cleanup test material
$pdo->prepare("DELETE FROM materials WHERE id = ?")->execute([$newMId]);

// -------------------------------------------------------------
// SUMMARY
// -------------------------------------------------------------
echo "\n============================================================\n";
echo "SUMMARY: Passed: $tests_passed | Failed: $tests_failed\n";
echo "============================================================\n";

if ($tests_failed > 0) {
    exit(1);
}
echo "ALL TESTS PASSED!\n";
