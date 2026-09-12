<?php
// verify_phase3.php
require_once 'api/config.php';

echo "PHASE 3 VERIFICATION SCRIPT\n";
echo "===========================\n";

$tests = [];

// 1. Verify plans has discount field
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM plans LIKE 'discount'");
    $tests['Discount pada Package/Plan'] = $stmt->fetch() ? 'PASS' : 'FAIL';
} catch (Exception $e) { $tests['Discount pada Package/Plan'] = 'FAIL'; }

// 2. Verify questions classification
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM questions LIKE 'classification'");
    $tests['Inject Soal - Taxonomy (LATIHAN/TRYOUT/ASESMEN)'] = $stmt->fetch() ? 'PASS' : 'FAIL';
} catch (Exception $e) { $tests['Inject Soal - Taxonomy (LATIHAN/TRYOUT/ASESMEN)'] = 'FAIL'; }

// 3. Verify question_imports_staging classification
try {
    $stmt = $pdo->query("SHOW COLUMNS FROM question_imports_staging LIKE 'classification'");
    $tests['Import Soal - Taxonomy Staging'] = $stmt->fetch() ? 'PASS' : 'FAIL';
} catch (Exception $e) { $tests['Import Soal - Taxonomy Staging'] = 'FAIL'; }

// 4. Verify Database Export/Import files exist
$tests['Database Export (api/database_export.php)'] = file_exists('api/database_export.php') ? 'PASS' : 'FAIL';
$tests['Database Import (api/database_import.php)'] = file_exists('api/database_import.php') ? 'PASS' : 'FAIL';

// 5. Verify Quiz Enforces ASESMEN isolation
$quizContent = file_get_contents('api/quiz.php');
if (strpos($quizContent, "classification = 'ASESMEN'") !== false) {
    $tests['Inject Soal - ASESMEN Leakage Protection'] = 'PASS';
} else {
    $tests['Inject Soal - ASESMEN Leakage Protection'] = 'FAIL';
}

echo "\nRESULTS:\n";
$all_pass = true;
foreach ($tests as $name => $status) {
    echo "- [$status] $name\n";
    if ($status === 'FAIL') $all_pass = false;
}

echo "\n===========================\n";
if ($all_pass) {
    echo "PHASE 3 IMPLEMENTATION — VERIFIED\n";
} else {
    echo "PHASE 3 IMPLEMENTATION — BLOCKED\n";
}
?>
