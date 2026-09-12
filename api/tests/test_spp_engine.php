<?php
// api/tests/test_spp_engine.php

require_once __DIR__ . '/../spp_engine.php';

$tests_run = 0;
$tests_passed = 0;
$tests_failed = 0;
$failed_details = [];

function assert_test($condition, $test_name) {
    global $tests_run, $tests_passed, $tests_failed, $failed_details;
    $tests_run++;
    if ($condition) {
        $tests_passed++;
        echo "PASS: $test_name\n";
    } else {
        $tests_failed++;
        $failed_details[] = $test_name;
        echo "FAIL: $test_name\n";
    }
}

function get_base_payload($value = 3) {
    $facets = [
        'EFC_MAST_01', 'EFC_MAST_02',
        'EFC_CHAL_01', 'EFC_CHAL_02',
        'SRL_PLAN_01', 'SRL_PLAN_02', 'SRL_PLAN_03',
        'SRL_CTRL_01', 'SRL_CTRL_02', 'SRL_CTRL_03',
        'SRL_REFL_01', 'SRL_REFL_02', 'SRL_REFL_03',
        'GRT_PERS_01', 'GRT_PERS_02', 'GRT_PERS_03', 'GRT_PERS_04', 'GRT_PERS_05'
    ];
    $riasecs = [
        'INT_REAL_01', 'INT_REAL_02',
        'INT_INVS_01', 'INT_INVS_02',
        'INT_ARTS_01', 'INT_ARTS_02',
        'INT_SOCL_01', 'INT_SOCL_02',
        'INT_ENTR_01', 'INT_ENTR_02',
        'INT_CONV_01', 'INT_CONV_02'
    ];
    $payload = [];
    foreach ($facets as $k) $payload[$k] = $value;
    foreach ($riasecs as $k) $payload[$k] = $value;
    return $payload;
}

// 1. Invalid inputs
$payload_29 = get_base_payload();
unset($payload_29['EFC_MAST_01']);
$res = SPPScoringEngine::score($payload_29);
assert_test($res['status'] === 'INVALID_PAYLOAD', "Missing 1 item returns INVALID_PAYLOAD");

$payload_31 = get_base_payload();
$payload_31['UNKNOWN_ITEM'] = 3;
$res = SPPScoringEngine::score($payload_31);
assert_test($res['status'] === 'INVALID_PAYLOAD', "Extra 31st item returns INVALID_PAYLOAD");

$payload_0 = get_base_payload();
$payload_0['EFC_MAST_01'] = 0;
$res = SPPScoringEngine::score($payload_0);
assert_test($res['status'] === 'INVALID_PAYLOAD', "Value 0 returns INVALID_PAYLOAD");

$payload_6 = get_base_payload();
$payload_6['EFC_MAST_01'] = 6;
$res = SPPScoringEngine::score($payload_6);
assert_test($res['status'] === 'INVALID_PAYLOAD', "Value 6 returns INVALID_PAYLOAD");

$payload_float = get_base_payload();
$payload_float['EFC_MAST_01'] = 2.5; // Will fail is_int validation
$res = SPPScoringEngine::score($payload_float);
assert_test($res['status'] === 'INVALID_PAYLOAD', "Float value returns INVALID_PAYLOAD");

$payload_string = get_base_payload();
$payload_string['EFC_MAST_01'] = "3"; // string
$res = SPPScoringEngine::score($payload_string);
assert_test($res['status'] === 'INVALID_PAYLOAD', "String value returns INVALID_PAYLOAD");

// 2. All 1
$payload_all_1 = get_base_payload(1);
$res_1 = SPPScoringEngine::score($payload_all_1);
assert_test($res_1['status'] === 'SCORED', "All 1 status SCORED");
assert_test(abs($res_1['dimensions']['potential']['transformed_score'] - 0) < 0.0001, "All 1 Potential == 0");

// 3. All 3
$payload_all_3 = get_base_payload(3);
$res_3 = SPPScoringEngine::score($payload_all_3);
assert_test(abs($res_3['dimensions']['potential']['transformed_score'] - 50) < 0.0001, "All 3 Potential == 50");

// 4. All 5
$payload_all_5 = get_base_payload(5);
$res_5 = SPPScoringEngine::score($payload_all_5);
assert_test(abs($res_5['dimensions']['potential']['transformed_score'] - 100) < 0.0001, "All 5 Potential == 100");

// 5. Reverse Scoring
$payload_rev = get_base_payload(5);
$payload_rev['GRT_PERS_04'] = 2;
$payload_rev['GRT_PERS_05'] = 4;
$res_rev = SPPScoringEngine::score($payload_rev);
assert_test(abs($res_rev['facets']['perseverance'] - 4.2) < 0.0001, "Reverse scoring perseverance is 4.2");

// 6. RIASEC Tie
$payload_r_tie = get_base_payload(1);
$payload_r_tie['INT_SOCL_01'] = 4;
$payload_r_tie['INT_SOCL_02'] = 4;
$payload_r_tie['INT_ENTR_01'] = 4;
$payload_r_tie['INT_ENTR_02'] = 4;
$res_r_tie = SPPScoringEngine::score($payload_r_tie);
assert_test($res_r_tie['riasec_top3'][0] === 'S' && $res_r_tie['riasec_top3'][1] === 'E', "RIASEC Tie breaks S over E");

// 7. All RIASEC tie
$payload_all_r_tie = get_base_payload(5);
$res_all_r_tie = SPPScoringEngine::score($payload_all_r_tie);
assert_test($res_all_r_tie['riasec_top3'] === ['R', 'I', 'A'], "All RIASEC Tie gives R-I-A");

// 8. Dimension Tie (Learning vs Potential)
// Make L and P 100, G 50
$payload_dim_tie = get_base_payload(5);
// Fix reverse items so L reaches 100
$payload_dim_tie['SRL_CTRL_03'] = 1;
$payload_dim_tie['SRL_REFL_03'] = 1;
// Set G (Perseverance) lower
$payload_dim_tie['GRT_PERS_01'] = 3;
$payload_dim_tie['GRT_PERS_02'] = 3;
$payload_dim_tie['GRT_PERS_03'] = 3;
$payload_dim_tie['GRT_PERS_04'] = 3; // rev = 3
$payload_dim_tie['GRT_PERS_05'] = 3; // rev = 3
$res_dim_tie = SPPScoringEngine::score($payload_dim_tie);
$prof_dim_tie = SPPProfileEngine::generateProfile($res_dim_tie);
assert_test($prof_dim_tie['relative_strength'] === 'LEARNING', "Learning beats Potential in strength tie");
assert_test($prof_dim_tie['development_area'] === 'GROWTH', "Growth is development area");

// 9. Three-way Dimension Tie
$payload_all_3 = get_base_payload(3);
$res_all_3 = SPPScoringEngine::score($payload_all_3);
$prof_all_3 = SPPProfileEngine::generateProfile($res_all_3);
assert_test($prof_all_3['relative_strength'] === null, "Three-way tie gives null strength");
assert_test($prof_all_3['development_area'] === null, "Three-way tie gives null dev area");

// 10. Quality Resolver
$quality1 = SPPStateResolver::resolveQualityStatus(30, false);
assert_test($quality1 === 'QUALITY_REVIEW_REQUIRED', "Speed < 45 gives QUALITY_REVIEW_REQUIRED");
$quality2 = SPPStateResolver::resolveQualityStatus(120, true);
assert_test($quality2 === 'QUALITY_REVIEW_REQUIRED', "Straight-line gives QUALITY_REVIEW_REQUIRED");
$quality3 = SPPStateResolver::resolveQualityStatus(120, false);
assert_test($quality3 === 'ACCEPTABLE', "Normal gives ACCEPTABLE");

echo "\n--- Summary ---\n";
echo "Tests Run: $tests_run\n";
echo "Tests Passed: $tests_passed\n";
echo "Tests Failed: $tests_failed\n";
if ($tests_failed > 0) {
    echo "Failed Details:\n" . implode("\n", $failed_details) . "\n";
    exit(1);
} else {
    echo "ALL TESTS PASSED.\n";
    exit(0);
}
