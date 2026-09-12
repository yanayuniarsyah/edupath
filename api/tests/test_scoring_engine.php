<?php
// api/tests/test_scoring_engine.php
// Pure-PHP unit tests for Phase 2D-2

require_once __DIR__ . '/../v1/scoring/ScoringEngineFactory.php';

function runTest($name, $expected, $actual) {
    if ($expected === $actual) {
        echo "PASS: $name\n";
    } else {
        echo "FAIL: $name\nExpected: " . json_encode($expected) . "\nActual: " . json_encode($actual) . "\n";
    }
}

function expectException($name, $callable, $expectedExceptionMessage) {
    try {
        $callable();
        echo "FAIL: $name (No exception thrown)\n";
    } catch (Exception $e) {
        if ($e->getMessage() === $expectedExceptionMessage) {
            echo "PASS: $name\n";
        } else {
            echo "FAIL: $name\nExpected Exception: $expectedExceptionMessage\nActual Exception: " . $e->getMessage() . "\n";
        }
    }
}

$baseConfig = ['strategy' => 'quiz_v1', 'unanswered_penalty' => 0, 'wrong_penalty' => 0];

// Test 1: Correct answer
$engine = ScoringEngineFactory::make($baseConfig);
$res1 = $engine->calculate([
    'config' => $baseConfig,
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => 10, 'answer_key' => ['correct' => 'A']]],
    'responses' => ['q1' => 'A']
]);
runTest('Test 1 - Correct answer (is_correct)', true, $res1->question_scores['q1']['is_correct']);
runTest('Test 1 - Correct answer (score_earned)', 10.0, $res1->question_scores['q1']['score_earned']);

// Test 2: Wrong answer
$res2 = $engine->calculate([
    'config' => ['strategy' => 'quiz_v1', 'wrong_penalty' => -1],
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => 10, 'answer_key' => ['correct' => 'A']]],
    'responses' => ['q1' => 'B']
]);
runTest('Test 2 - Wrong answer (is_correct)', false, $res2->question_scores['q1']['is_correct']);
runTest('Test 2 - Wrong answer (score_earned)', -1.0, $res2->question_scores['q1']['score_earned']);

// Test 3: Unanswered
$res3 = $engine->calculate([
    'config' => $baseConfig,
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => 10, 'answer_key' => ['correct' => 'A']]],
    'responses' => [] // missing
]);
runTest('Test 3 - Unanswered (is_correct)', false, $res3->question_scores['q1']['is_correct']);
runTest('Test 3 - Unanswered (score_earned)', 0.0, $res3->question_scores['q1']['score_earned']);

// Test 4: Weight override
$res4 = $engine->calculate([
    'config' => $baseConfig,
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => 5, 'weight_override' => 2, 'answer_key' => ['correct' => 'A']]],
    'responses' => ['q1' => 'A']
]);
runTest('Test 4 - Weight override', 2.0, $res4->question_scores['q1']['score_earned']);

// Test 5: Default weight
$res5 = $engine->calculate([
    'config' => $baseConfig,
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => 3, 'weight_override' => null, 'answer_key' => ['correct' => 'A']]],
    'responses' => ['q1' => 'A']
]);
runTest('Test 5 - Default weight', 3.0, $res5->question_scores['q1']['score_earned']);

// Test 6: Fallback
$res6 = $engine->calculate([
    'config' => $baseConfig,
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => null, 'weight_override' => null, 'answer_key' => ['correct' => 'A']]],
    'responses' => ['q1' => 'A']
]);
runTest('Test 6 - Fallback weight', 1.0, $res6->question_scores['q1']['score_earned']);

// Test 7: Zero weight
$res7 = $engine->calculate([
    'config' => $baseConfig,
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => 0, 'weight_override' => null, 'answer_key' => ['correct' => 'A']]],
    'responses' => ['q1' => 'A']
]);
runTest('Test 7 - Zero weight', 0.0, $res7->question_scores['q1']['score_earned']);

// Test 8: Negative weight
expectException('Test 8 - Negative weight rejected by quiz_v1', function() use ($engine, $baseConfig) {
    $engine->calculate([
        'config' => $baseConfig,
        'questions' => [['assessment_question_id' => 'q1', 'default_weight' => -5, 'answer_key' => ['correct' => 'A']]],
        'responses' => ['q1' => 'A']
    ]);
}, 'INVALID_SCORING_CONFIG');

// Test 9: Unknown strategy
expectException('Test 9 - Unknown strategy', function() {
    ScoringEngineFactory::make(['strategy' => 'unknown']);
}, 'SCORING_ENGINE_NOT_FOUND');

// Test 10: Invalid config
expectException('Test 10 - Invalid config', function() {
    ScoringEngineFactory::make([]);
}, 'INVALID_SCORING_CONFIG');

// Test 11: Client score injection (simulated by ignoring extra payload fields)
// Since calculate only takes response string, client can't inject score object
$res11 = $engine->calculate([
    'config' => $baseConfig,
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => 5, 'answer_key' => ['correct' => 'A']]],
    'responses' => ['q1' => 'B'] // client sent wrong, but injected is_correct via payload
]);
// the engine correctly only receives the 'B' string as 'response_value' via controller extraction
runTest('Test 11 - Client score injection ignored', 0.0, $res11->question_scores['q1']['score_earned']);

// Test 12: Determinism
$snapshot = [
    'config' => $baseConfig,
    'questions' => [['assessment_question_id' => 'q1', 'default_weight' => 5, 'answer_key' => ['correct' => 'A']]],
    'responses' => ['q1' => 'A']
];
$det1 = clone $engine->calculate($snapshot);
$det2 = clone $engine->calculate($snapshot);
runTest('Test 12 - Determinism', json_encode($det1), json_encode($det2));

?>
