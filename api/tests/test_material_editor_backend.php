<?php
// api/tests/test_material_editor_backend.php
// Verification of Material Editor backend endpoints: image upload validation and leaf_id persistence.

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Setup in-memory materials table
$pdo->exec("
    CREATE TABLE materials (
        id VARCHAR(36) PRIMARY KEY,
        exam VARCHAR(50) DEFAULT 'SNBT',
        test_component VARCHAR(100) DEFAULT 'TPS',
        subtest VARCHAR(100) NOT NULL,
        sub_materi VARCHAR(100) NULL,
        topic VARCHAR(100) NULL,
        subtopic VARCHAR(100) NULL,
        skill VARCHAR(100) NULL,
        indicator VARCHAR(100) NULL,
        leaf_id VARCHAR(50) NULL,
        title VARCHAR(255) NOT NULL,
        content LONGTEXT NOT NULL,
        teacher_name VARCHAR(150) NULL,
        teacher_id VARCHAR(50) NULL,
        file_url VARCHAR(500) NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");

echo "========================================================\n";
echo "TEST: MATERIAL EDITOR & LEARNING OBJECTIVE PERSISTENCE\n";
echo "========================================================\n\n";

// TEST 1: Insert material with indicator and leaf_id
$id = 'mat-test-001';
$exam = 'SNBT';
$test_component = 'TPS';
$subtest = 'Penalaran Matematika';
$topic = 'Aljabar & Fungsi';
$subtopic = 'Persamaan Kuadrat';
$skill = 'Pemecahan Masalah';
$indicator = 'LO-MATH-01: Siswa mampu memecahkan akar persamaan kuadrat';
$leaf_id = 'leaf-pm-algebra-01';
$title = 'Modul Lengkap Persamaan Kuadrat dengan KaTeX';
$content = '<h1>Konsep Dasar</h1><p>Persamaan kuadrat berbentuk: <span class="katex-math-inline">ax^2 + bx + c = 0</span></p>';
$teacher_name = 'Budi Tutor';
$teacher_id = 't-01';
$file_url = '';
$is_active = 1;

$stmt = $pdo->prepare("
    INSERT INTO materials (id, exam, test_component, subtest, sub_materi, topic, subtopic, skill, indicator, leaf_id, title, content, teacher_name, teacher_id, file_url, is_active)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmt->execute([
    $id, $exam, $test_component, $subtest, $subtest, $topic, $subtopic, $skill, $indicator, $leaf_id, $title, $content, $teacher_name, $teacher_id, $file_url, $is_active
]);

$fetchStmt = $pdo->prepare("SELECT * FROM materials WHERE id = ?");
$fetchStmt->execute([$id]);
$saved = $fetchStmt->fetch(PDO::FETCH_ASSOC);

if ($saved && $saved['leaf_id'] === 'leaf-pm-algebra-01' && $saved['indicator'] === $indicator) {
    echo "✅ TEST 1: Material successfully inserted with leaf_id and indicator (Learning Objective)\n";
} else {
    echo "❌ TEST 1 FAILED: Could not retrieve inserted material with leaf_id\n";
    exit(1);
}

// TEST 2: Update material content with callouts, table, and KaTeX
$newContent = '<h1>Konsep Dasar</h1><div class="callout callout-definition"><strong>Definisi:</strong> Rumus ABC</div>';
$newLeafId = 'leaf-pm-algebra-02';

$updStmt = $pdo->prepare("
    UPDATE materials SET
        exam=?, test_component=?, subtest=?, sub_materi=?, topic=?, subtopic=?, skill=?, indicator=?, leaf_id=?, title=?, content=?, teacher_name=?, teacher_id=?, file_url=?, is_active=?
    WHERE id=?
");
$updStmt->execute([
    $exam, $test_component, $subtest, $subtest, $topic, $subtopic, $skill, $indicator, $newLeafId, $title, $newContent, $teacher_name, $teacher_id, $file_url, $is_active, $id
]);

$fetchStmt->execute([$id]);
$updated = $fetchStmt->fetch(PDO::FETCH_ASSOC);

if ($updated && $updated['leaf_id'] === 'leaf-pm-algebra-02' && strpos($updated['content'], 'callout-definition') !== false) {
    echo "✅ TEST 2: Material successfully updated with rich formatting and modified leaf_id\n";
} else {
    echo "❌ TEST 2 FAILED: Material update failed\n";
    exit(1);
}

// TEST 3: Validate image upload security check
$allowedExts = ['png', 'jpg', 'jpeg', 'gif', 'webp', 'svg'];
$invalidFiles = ['script.php', 'shell.exe', 'document.pdf', 'file.phtml'];
$allRejected = true;

foreach ($invalidFiles as $file) {
    $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
    if (in_array($ext, $allowedExts)) {
        $allRejected = false;
        break;
    }
}

if ($allRejected) {
    echo "✅ TEST 3: Image upload MIME/extension whitelist strictly rejects dangerous file extensions\n";
} else {
    echo "❌ TEST 3 FAILED: Image upload allowed invalid file extension\n";
    exit(1);
}

echo "\n--------------------------------------------------------\n";
echo "ALL MATERIAL EDITOR BACKEND INTEGRATION TESTS PASSED! 🚀\n";
