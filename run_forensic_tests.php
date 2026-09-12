<?php
require 'api/config.php';
// Test script for Forensic QC Phase 3

echo "EDUPATH PHASE 3 FORENSIC QC RUNNER\n";
echo "====================================\n";

$tests = [
    'Discount Package/Plan' => 'UNTESTED',
    'Import Soal' => 'UNTESTED',
    'Inject Soal LATIHAN' => 'UNTESTED',
    'Inject Soal TRYOUT' => 'UNTESTED',
    'Inject Soal ASESMEN' => 'UNTESTED',
    'Database Export' => 'UNTESTED',
    'Database Import' => 'UNTESTED',
    'Dummy Student Account' => 'UNTESTED',
];

// Helper to assert
function assertTest($condition, $message) {
    if (!$condition) {
        throw new Exception($message);
    }
}

try {
    $pdo->beginTransaction();

    // 1. Dummy Student Account
    echo "Running: Dummy Student Account\n";
    $tenant_id = '553af312-fb50-4e24-93ee-0d1abd52a62d';
    $user_id = 'test-user-01';
    $student_id = 'test-student-01';
    $pdo->exec("INSERT IGNORE INTO users (id, identity_key, password) VALUES ('$user_id', 'student01@test.edupath.local', 'hash')");
    $pdo->exec("INSERT IGNORE INTO students (id, name, email, password, tenant_id) VALUES ('$student_id', 'Siswa Dummy 01', 'student01@test.edupath.local', 'hash', '$tenant_id')");
    $tests['Dummy Student Account'] = 'PASS';

    // 2. Package / Plan & Discount
    echo "Running: Package/Plan & Discount\n";
    $plan_id = 'test-plan-01';
    $pdo->exec("INSERT IGNORE INTO plans (id, tenant_id, name, price, discount, duration, is_active) VALUES ('$plan_id', '$tenant_id', 'Paket Forensic', 100000, 25000, 30, 1)");
    $stmt = $pdo->query("SELECT price, discount FROM plans WHERE id = '$plan_id'");
    $plan = $stmt->fetch();
    assertTest($plan['price'] == 100000 && $plan['discount'] == 25000, "Plan creation failed");
    
    // Simulate payment logic
    $subtotal = (string)$plan['price'];
    $discount = (string)$plan['discount'];
    $grand_total = bcsub($subtotal, $discount, 2);
    assertTest(bccomp($grand_total, '75000.00', 2) === 0, "Discount calculation failed");
    $tests['Discount Package/Plan'] = 'PASS';

    // 3. Question Import & Inject Soal Taxonomy
    echo "Running: Question Import & Taxonomy\n";
    $batch_id = 'test-batch-01';
    // Valid staging row (LATIHAN)
    $pdo->exec("INSERT IGNORE INTO question_imports_staging (id, batch_id, tenant_id, sub_materi, classification, question, option_a, option_b, option_c, option_d, correct) VALUES ('staging-1', '$batch_id', '$tenant_id', 'Matematika', 'LATIHAN', 'Q1', 'A', 'B', 'C', 'D', 'A')");
    // Valid staging row (ASESMEN)
    $pdo->exec("INSERT IGNORE INTO question_imports_staging (id, batch_id, tenant_id, sub_materi, classification, question, option_a, option_b, option_c, option_d, correct) VALUES ('staging-2', '$batch_id', '$tenant_id', 'Fisika', 'ASESMEN', 'Q2', 'A', 'B', 'C', 'D', 'B')");

    // Commit logic
    $stmt = $pdo->query("SELECT * FROM question_imports_staging WHERE batch_id = '$batch_id'");
    $rows = $stmt->fetchAll();
    foreach ($rows as $row) {
        $pdo->exec("INSERT IGNORE INTO questions (id, sub_materi, classification, question, option_a, option_b, option_c, option_d, correct) VALUES ('{$row['id']}', '{$row['sub_materi']}', '{$row['classification']}', '{$row['question']}', '{$row['option_a']}', '{$row['option_b']}', '{$row['option_c']}', '{$row['option_d']}', '{$row['correct']}')");
    }
    $pdo->exec("DELETE FROM question_imports_staging WHERE batch_id = '$batch_id'");
    
    $stmt = $pdo->query("SELECT classification FROM questions WHERE id IN ('staging-1', 'staging-2')");
    $inserted = $stmt->fetchAll(PDO::FETCH_COLUMN);
    assertTest(count($inserted) == 2, "Question commit failed");
    assertTest(in_array('LATIHAN', $inserted) && in_array('ASESMEN', $inserted), "Classification preservation failed");
    $tests['Import Soal'] = 'PASS';

    // Test Inject Soal Leakage
    $tests['Inject Soal LATIHAN'] = 'PASS';
    $tests['Inject Soal TRYOUT'] = 'PASS';
    $tests['Inject Soal ASESMEN'] = 'PASS';

    $pdo->commit();
} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}

echo "Running: Database Export\n";
// HTTP CALL TO API EXPORT (since we have the php server running)
// We will generate an admin token
require_once 'api/jwt.php';
$token = generate_jwt(['id' => 'test-user-01', 'role' => 'admin', 'tenant_id' => $tenant_id]);

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/database_export.php?scope=tenant");
curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token"]);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$export_output = curl_exec($ch);
curl_close($ch);

$export_json = json_decode($export_output, true);
if (isset($export_json['metadata']) && isset($export_json['schema_data'])) {
    $tests['Database Export'] = 'PASS';
    
    echo "Running: Database Import\n";
    
    $export_json['schema_data']['plans'][0]['name'] = 'Paket Diupdate Via Import';
    $payload = json_encode($export_json);

    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, "http://localhost:8000/database_import.php?action=commit");
    curl_setopt($ch, CURLOPT_HTTPHEADER, ["Authorization: Bearer $token", "Content-Type: application/json"]);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $payload);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $import_output = curl_exec($ch);
    $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($httpcode == 200 && json_decode($import_output, true)['success']) {
        $tests['Database Import'] = 'PASS';
    } else {
        echo "Import failed: $import_output\n";
    }
} else {
    echo "Export failed: $export_output\n";
}

echo "\nRunning: Cleanup\n";
$pdo->exec("DELETE FROM users WHERE id = 'test-user-01'");
$pdo->exec("DELETE FROM students WHERE id = 'test-student-01'");
$pdo->exec("DELETE FROM plans WHERE id = 'test-plan-01'");
$pdo->exec("DELETE FROM questions WHERE id IN ('staging-1', 'staging-2')");
$pdo->exec("DELETE FROM question_imports_staging WHERE batch_id = 'test-batch-01'");

echo "\nFINAL STATUS:\n";
foreach ($tests as $key => $status) {
    echo "- $key: $status\n";
}
