<?php
// Temporary Production Verification Bridge
// MUST BE REMOVED AFTER PHASE 4E DEPLOYMENT
// Usage: Send HTTP GET or POST with header: X-Verify-Key

require_once __DIR__ . '/config.php';

header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('Expires: 0');

// 1. Authenticate using a derivative of an existing environment secret (so no new secret is committed)
$expected_key = substr(hash_hmac('sha256', 'phase4e', JWT_SECRET), 0, 16);
$provided_key = $_SERVER['HTTP_X_VERIFY_KEY'] ?? '';

if (empty($provided_key) || !hash_equals($expected_key, $provided_key)) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized access to verification bridge."]);
    exit;
}

$diagnostic = [];
$diagnostic['environment'] = APP_ENV;
$diagnostic['php_version'] = phpversion();

try {
    $diagnostic['db_server_version'] = $pdo->getAttribute(PDO::ATTR_SERVER_VERSION);
    // Redact database name
    $db_name = env('DB_NAME', '');
    $diagnostic['db_name'] = strlen($db_name) > 3 ? substr($db_name, 0, 3) . str_repeat('*', strlen($db_name)-3) : '***';
} catch (Exception $e) {
    $diagnostic['db_server_version'] = 'ERROR';
    $diagnostic['db_name'] = 'ERROR';
}

// 2. PK Schema Precheck
$pk_tables = ['pk_leaves', 'pk_constructs', 'pk_indicators', 'pk_questions', 'pk_question_analytics', 'pk_diagnostic_evidence'];
$pk_status = 'PRESENT';
$pk_counts = [];

foreach ($pk_tables as $table) {
    try {
        $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
        $pk_counts[$table] = $stmt->fetchColumn();
    } catch (Exception $e) {
        $pk_status = 'NOT_DEPLOYED';
        $pk_counts[$table] = 'MISSING';
    }
}
$diagnostic['pk_schema'] = $pk_status;
$diagnostic['pk_counts'] = $pk_counts;

// 3. Legacy Safety Check (Read Only)
$legacy_counts = [];
try {
    $stmt = $pdo->query("SELECT COUNT(*) FROM questions");
    $legacy_counts['questions'] = $stmt->fetchColumn();
} catch (Exception $e) {
    $legacy_counts['questions'] = 'MISSING';
}
$diagnostic['legacy_safety_check'] = $legacy_counts;

// 4. Backup Capability Check
$backup_capability = 'NOT_AVAILABLE';
if (function_exists('exec')) {
    $out = [];
    @exec('mysqldump --version 2>&1', $out, $code);
    if ($code === 0) {
        $backup_capability = 'AVAILABLE_VIA_MYSQLDUMP';
    }
}
$diagnostic['backup_method_available'] = $backup_capability;

// Output diagnostic info
echo json_encode(["status" => "SUCCESS", "diagnostic" => $diagnostic], JSON_PRETTY_PRINT);
