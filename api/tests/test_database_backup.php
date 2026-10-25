<?php
// api/tests/test_database_backup.php
// Unit & Integration Test Suite for EduPath Database Backup & Restore Service

require_once __DIR__ . '/../DatabaseBackupService.php';

echo "====================================================================\n";
echo "TEST SUITE: EDUPATH DATABASE BACKUP & RESTORE SERVICE\n";
echo "====================================================================\n\n";

$tempDir = sys_get_temp_dir() . '/edupath_backup_test_' . uniqid();
@mkdir($tempDir, 0755, true);

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Setup sample schema
$pdo->exec("
    CREATE TABLE users (
        id TEXT PRIMARY KEY,
        name TEXT,
        email TEXT,
        notes TEXT
    );
    CREATE TABLE orders (
        id TEXT PRIMARY KEY,
        user_id TEXT,
        amount REAL,
        status TEXT
    );
    INSERT INTO users VALUES ('u1', 'Super Admin', 'admin@edupath.id', 'Special ; note with semicolon; and quotes \"here\"');
    INSERT INTO users VALUES ('u2', 'Student One', 'stu1@edupath.id', 'Normal note');
    INSERT INTO orders VALUES ('ord1', 'u1', 500000.0, 'paid');
    INSERT INTO orders VALUES ('ord2', 'u2', 250000.0, 'pending');
");

$service = new DatabaseBackupService($pdo, $tempDir);

$passed = 0;
$failed = 0;

function runTest(string $name, bool $condition, string $detail = '') {
    global $passed, $failed;
    if ($condition) {
        $passed++;
        echo "✅ PASS: $name\n";
    } else {
        $failed++;
        echo "❌ FAIL: $name => $detail\n";
    }
}

// TEST 1: Directory security & .htaccess
runTest("1. Directory Protection: .htaccess exists and contains Deny from all",
    file_exists($tempDir . '/.htaccess') && str_contains(file_get_contents($tempDir . '/.htaccess'), 'Deny from all'),
    "Htaccess tidak terbuat dengan proteksi yang benar"
);

// TEST 2: Create Backup
$backupResult = $service->createBackup(true, 'test_snapshot.sql');
runTest("2. Create Backup: Snapshot created successfully with valid metadata",
    $backupResult['success'] === true && file_exists($backupResult['filepath']) && $backupResult['size_bytes'] > 0,
    "File backup gagal dibuat"
);

// TEST 3: SQL Content Verification
$sqlContent = file_get_contents($backupResult['filepath']);
runTest("3. SQL Content: Contains table structures and data inserts",
    str_contains($sqlContent, 'CREATE TABLE users') && str_contains($sqlContent, 'INSERT INTO `users`') && str_contains($sqlContent, 'Super Admin'),
    "Isi SQL tidak lengkap atau hilang"
);

// TEST 4: SQL Parser with complex semicolons inside quotes
$testSql = "
    INSERT INTO users VALUES ('u3', 'Test; Name', 'email@test.id', 'Note with ; semicolon');
    UPDATE orders SET status = 'completed; verified' WHERE id = 'ord1';
";
$statements = $service->splitSqlStatements($testSql);
runTest("4. SQL Statement Parser: Properly splits into exactly 2 statements without breaking quoted semicolons",
    count($statements) === 2 && str_contains($statements[0], 'Test; Name') && str_contains($statements[1], 'completed; verified'),
    "Parser salah memecah statement: Count = " . count($statements)
);

// TEST 5: Restore Into Clean Database
$pdoRestore = new PDO('sqlite::memory:');
$pdoRestore->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
$restoreService = new DatabaseBackupService($pdoRestore, $tempDir);

$restoreResult = $restoreService->restoreFromSql($sqlContent, 'sa-test-user', false);
runTest("5. Restore Database: Executed statements successfully without fatal errors",
    $restoreResult['success'] === true && $restoreResult['statements_executed'] > 0,
    "Restore gagal: " . json_encode($restoreResult)
);

$userCount = (int)$pdoRestore->query("SELECT COUNT(*) FROM users")->fetchColumn();
$orderCount = (int)$pdoRestore->query("SELECT COUNT(*) FROM orders")->fetchColumn();
$adminUser = $pdoRestore->query("SELECT * FROM users WHERE id = 'u1'")->fetch(PDO::FETCH_ASSOC);

runTest("6. Restored Data Integrity: Row count matches original (2 users, 2 orders)",
    $userCount === 2 && $orderCount === 2,
    "Data hasil restore tidak sesuai: users=$userCount, orders=$orderCount"
);

runTest("7. Restored Value Integrity: Preserved complex quoted string with semicolons",
    $adminUser['notes'] === 'Special ; note with semicolon; and quotes "here"',
    "Nilai teks rusak saat direstore: " . ($adminUser['notes'] ?? 'null')
);

// TEST 8: Anti-Path Traversal Protection
$isBlocked = false;
try {
    $service->getBackupFile('../../etc/passwd');
} catch (InvalidArgumentException $e) {
    $isBlocked = true;
}
runTest("8. Security Path Traversal: Blocked ../../ traversal attempts",
    $isBlocked === true,
    "Path traversal tidak diblokir"
);

// TEST 9: List Backups
$list = $service->listBackups();
runTest("9. List Backups: Found created snapshots in directory",
    count($list) >= 1 && $list[0]['filename'] === 'test_snapshot.sql',
    "List backup tidak menampilkan file"
);

// TEST 10: Delete Backup
$deleteSuccess = $service->deleteBackup('test_snapshot.sql');
runTest("10. Delete Backup: Snapshot deleted cleanly",
    $deleteSuccess === true && !file_exists($tempDir . '/test_snapshot.sql'),
    "Gagal menghapus file snapshot"
);

// Cleanup temp dir
@array_map('unlink', glob("$tempDir/*.*"));
@rmdir($tempDir);

echo "\n====================================================================\n";
echo "RESULT: $passed PASSED, $failed FAILED\n";
echo "====================================================================\n";

if ($failed > 0) exit(1);
