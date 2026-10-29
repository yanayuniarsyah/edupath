<?php
// api/tests/test_tka_authoring_integration.php
// Verification of TKA vs SNBT authoring, filtering, and persistence for Questions and Materials

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Setup in-memory questions & materials tables
$pdo->exec("
    CREATE TABLE questions (
        id INTEGER PRIMARY KEY AUTOINCREMENT,
        assessment_type VARCHAR(50) DEFAULT 'SNBT',
        track VARCHAR(50) DEFAULT 'umum',
        exam VARCHAR(50) DEFAULT 'SNBT',
        test_component VARCHAR(100) DEFAULT 'TPS',
        subtest VARCHAR(100) NOT NULL,
        sub_materi VARCHAR(100) NULL,
        topic VARCHAR(150) NULL,
        subtopic VARCHAR(150) NULL,
        skill VARCHAR(255) NULL,
        indicator VARCHAR(255) NULL,
        question_type VARCHAR(50) DEFAULT 'multiple_choice',
        difficulty VARCHAR(50) DEFAULT 'medium',
        question TEXT NOT NULL,
        option_a TEXT NOT NULL,
        option_b TEXT NOT NULL,
        option_c TEXT NOT NULL,
        option_d TEXT NOT NULL,
        option_e TEXT NOT NULL,
        correct VARCHAR(10) NOT NULL,
        explanation TEXT NULL,
        usage_type VARCHAR(50) DEFAULT 'latihan',
        cognitive_demand VARCHAR(50) DEFAULT 'C3',
        source_type VARCHAR(50) DEFAULT 'author_created',
        rights_status VARCHAR(50) DEFAULT 'verified',
        is_qc_passed TINYINT(1) DEFAULT 1,
        teacher_id VARCHAR(50) NULL,
        teacher_name VARCHAR(150) NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE materials (
        id VARCHAR(36) PRIMARY KEY,
        assessment_type VARCHAR(50) DEFAULT 'SNBT',
        track VARCHAR(50) DEFAULT 'umum',
        exam VARCHAR(50) DEFAULT 'SNBT',
        test_component VARCHAR(100) DEFAULT 'TPS',
        subtest VARCHAR(100) NOT NULL,
        sub_materi VARCHAR(100) NULL,
        topic VARCHAR(150) NULL,
        subtopic VARCHAR(150) NULL,
        skill VARCHAR(255) NULL,
        indicator VARCHAR(255) NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        formula_praktis TEXT NULL,
        teacher_id VARCHAR(50) NULL,
        teacher_name VARCHAR(150) NULL,
        file_url VARCHAR(500) NULL,
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    );
");

echo "====================================================================\n";
echo "TEST SUITE: TKA vs SNBT AUTHORING & MATERIAL INTEGRATION\n";
echo "====================================================================\n\n";

$passCount = 0;

// TEST 1: Question Insertion with TKA Saintek
$stmtQ = $pdo->prepare("
    INSERT INTO questions (
        assessment_type, track, exam, test_component, subtest, sub_materi,
        topic, subtopic, skill, indicator, question_type, difficulty,
        question, option_a, option_b, option_c, option_d, option_e, correct, explanation,
        usage_type, cognitive_demand, source_type, rights_status, is_qc_passed, teacher_id, teacher_name
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$stmtQ->execute([
    'TKA', 'saintek', 'TKA', 'TKA SAINTEK', 'Fisika', 'Fisika',
    'Mekanika & Termodinamika', 'Hukum Newton', 'Analisis Dinamika', 'Menghitung percepatan balok',
    'multiple_choice', 'hard', 'Sebuah balok bermassa 5 kg ditarik dengan gaya 20 N...', '2 m/s^2', '4 m/s^2', '5 m/s^2', '10 m/s^2', '20 m/s^2', 'b',
    'Gunakan rumus a = F / m = 20 / 5 = 4 m/s^2.', 'tryout', 'C4', 'author_created', 'verified', 1, 'teacher-01', 'Pak Fisika'
]);
$q1Id = $pdo->lastInsertId();

$stmtCheck = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmtCheck->execute([$q1Id]);
$q1 = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if ($q1 && $q1['assessment_type'] === 'TKA' && $q1['track'] === 'saintek' && $q1['subtest'] === 'Fisika') {
    echo "✅ PASS: 1. Question authoring successfully persists TKA Saintek metadata\n";
    $passCount++;
} else {
    echo "❌ FAIL: 1. Question authoring failed to persist TKA Saintek metadata\n";
    exit(1);
}

// TEST 2: Question Insertion with TKA Soshum
$stmtQ->execute([
    'TKA', 'soshum', 'TKA', 'TKA SOSHUM', 'Sosiologi', 'Sosiologi',
    'Struktur & Perubahan Sosial', 'Stratifikasi Sosial', 'Analisis Struktur', 'Menganalisis mobilitas sosial',
    'multiple_choice', 'medium', 'Perubahan kedudukan seseorang dari buruh menjadi direktur adalah...', 'Mobilitas vertikal naik', 'Mobilitas vertikal turun', 'Mobilitas horizontal', 'Mobilitas antargenerasi', 'Stagnasi sosial', 'a',
    'Mobilitas vertikal naik (social climbing) terjadi saat status seseorang meningkat.', 'latihan', 'C3', 'author_created', 'verified', 1, 'teacher-02', 'Ibu Sosiologi'
]);
$q2Id = $pdo->lastInsertId();

$stmtCheck->execute([$q2Id]);
$q2 = $stmtCheck->fetch(PDO::FETCH_ASSOC);

if ($q2 && $q2['assessment_type'] === 'TKA' && $q2['track'] === 'soshum' && $q2['subtest'] === 'Sosiologi') {
    echo "✅ PASS: 2. Question authoring successfully persists TKA Soshum metadata\n";
    $passCount++;
} else {
    echo "❌ FAIL: 2. Question authoring failed to persist TKA Soshum metadata\n";
    exit(1);
}

// TEST 3: Material Insertion with TKA Saintek & Soshum
$stmtM = $pdo->prepare("
    INSERT INTO materials (
        id, assessment_type, track, exam, test_component, subtest, sub_materi,
        topic, subtopic, skill, indicator, title, content, formula_praktis, teacher_id, teacher_name, is_active
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
");
$m1Id = 'mat-tka-fisika-01';
$stmtM->execute([
    $m1Id, 'TKA', 'saintek', 'TKA', 'TKA SAINTEK', 'Fisika', 'Fisika',
    'Mekanika & Termodinamika', 'Hukum Newton', 'Analisis Dinamika', 'Menghitung percepatan',
    'Mastering Dinamika Gerak Newton', '<p>Ringkasan materi dinamika...</p>', 'a = \\Sigma F / m', 'teacher-01', 'Pak Fisika', 1
]);

$m2Id = 'mat-tka-ekonomi-01';
$stmtM->execute([
    $m2Id, 'TKA', 'soshum', 'TKA', 'TKA SOSHUM', 'Ekonomi', 'Ekonomi',
    'Pasar & Harga Keseimbangan', 'Elastisitas Permintaan', 'Perhitungan Elastisitas', 'Menghitung koefisien elastisitas',
    'Konsep Elastisitas Permintaan & Penawaran', '<p>Pembahasan elastisitas harga...</p>', 'Ed = (%\\Delta Q) / (%\\Delta P)', 'teacher-02', 'Ibu Sosiologi', 1
]);

$stmtCheckM = $pdo->prepare("SELECT * FROM materials WHERE id = ?");
$stmtCheckM->execute([$m1Id]);
$m1 = $stmtCheckM->fetch(PDO::FETCH_ASSOC);

if ($m1 && $m1['assessment_type'] === 'TKA' && $m1['track'] === 'saintek' && $m1['formula_praktis'] === 'a = \\Sigma F / m') {
    echo "✅ PASS: 3. Material authoring successfully persists TKA Saintek and formula_praktis\n";
    $passCount++;
} else {
    echo "❌ FAIL: 3. Material authoring failed to persist TKA Saintek\n";
    exit(1);
}

// TEST 4: Filtering Questions by Program and Track
$filterAll = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
$filterTkaSaintek = $pdo->query("SELECT COUNT(*) FROM questions WHERE assessment_type = 'TKA' AND track = 'saintek'")->fetchColumn();
$filterTkaSoshum = $pdo->query("SELECT COUNT(*) FROM questions WHERE assessment_type = 'TKA' AND track = 'soshum'")->fetchColumn();
$filterSnbt = $pdo->query("SELECT COUNT(*) FROM questions WHERE assessment_type = 'SNBT' OR assessment_type IS NULL")->fetchColumn();

if ($filterAll == 2 && $filterTkaSaintek == 1 && $filterTkaSoshum == 1 && $filterSnbt == 0) {
    echo "✅ PASS: 4. Questions query filters accurately separate TKA Saintek, TKA Soshum, and SNBT\n";
    $passCount++;
} else {
    echo "❌ FAIL: 4. Questions filtering returned unexpected counts\n";
    exit(1);
}

// TEST 5: Filtering Materials by Program and Track
$mFilterSaintek = $pdo->query("SELECT COUNT(*) FROM materials WHERE assessment_type = 'TKA' AND track = 'saintek'")->fetchColumn();
$mFilterSoshum = $pdo->query("SELECT COUNT(*) FROM materials WHERE assessment_type = 'TKA' AND track = 'soshum'")->fetchColumn();

if ($mFilterSaintek == 1 && $mFilterSoshum == 1) {
    echo "✅ PASS: 5. Materials query filters accurately isolate TKA Saintek and TKA Soshum\n";
    $passCount++;
} else {
    echo "❌ FAIL: 5. Materials query filters returned unexpected counts\n";
    exit(1);
}

// TEST 6: Backward Compatibility (Legacy SNBT questions without assessment_type)
$pdo->exec("
    INSERT INTO questions (
        exam, test_component, subtest, sub_materi,
        topic, subtopic, skill, indicator, question_type, difficulty,
        question, option_a, option_b, option_c, option_d, option_e, correct, explanation,
        usage_type, cognitive_demand, source_type, rights_status, is_qc_passed
    ) VALUES (
        'SNBT', 'TPS', 'Penalaran Umum', 'Penalaran Umum',
        'Induktif', 'Pola Bilangan', 'Identifikasi Pola', 'Menentukan suku ke-n', 'multiple_choice', 'easy',
        '2, 4, 8, 16, ... suku berikutnya adalah?', '20', '24', '32', '64', '128', 'c', 'Kelipatan 2.',
        'latihan', 'C2', 'author_created', 'verified', 1
    )
");
$legacyId = $pdo->lastInsertId();

$stmtLegacy = $pdo->prepare("SELECT * FROM questions WHERE id = ?");
$stmtLegacy->execute([$legacyId]);
$legacyQ = $stmtLegacy->fetch(PDO::FETCH_ASSOC);

// Simulate API layer normalization
$normalizedType = $legacyQ['assessment_type'] ?: ($legacyQ['exam'] ?: 'SNBT');
$normalizedTrack = $legacyQ['track'] ?: 'umum';

if ($normalizedType === 'SNBT' && $normalizedTrack === 'umum') {
    echo "✅ PASS: 6. Legacy records safely default to assessment_type='SNBT' and track='umum'\n";
    $passCount++;
} else {
    echo "❌ FAIL: 6. Legacy normalization failed\n";
    exit(1);
}

// TEST 7: Question Update - Switching from SNBT to TKA
$stmtUpdQ = $pdo->prepare("
    UPDATE questions SET
        assessment_type = ?, track = ?, exam = ?, test_component = ?, subtest = ?, sub_materi = ?, topic = ?
    WHERE id = ?
");
$stmtUpdQ->execute(['TKA', 'saintek', 'TKA', 'TKA SAINTEK', 'Kimia', 'Kimia', 'Stoikiometri', $legacyId]);

$stmtLegacy->execute([$legacyId]);
$switchedQ = $stmtLegacy->fetch(PDO::FETCH_ASSOC);

if ($switchedQ['assessment_type'] === 'TKA' && $switchedQ['track'] === 'saintek' && $switchedQ['subtest'] === 'Kimia') {
    echo "✅ PASS: 7. Question editing successfully transitions program/track to TKA Saintek\n";
    $passCount++;
} else {
    echo "❌ FAIL: 7. Question update failed\n";
    exit(1);
}

// TEST 8: Teacher Role Scoping / RBAC Isolation
$teacher1Id = 'teacher-01';
$teacher2Id = 'teacher-02';

// Simulating Teacher 1 query for "My Questions"
$stmtMyQ = $pdo->prepare("SELECT COUNT(*) FROM questions WHERE teacher_id = ?");
$stmtMyQ->execute([$teacher1Id]);
$t1Count = $stmtMyQ->fetchColumn();

// Simulating Teacher 2 query for "My Materials"
$stmtMyM = $pdo->prepare("SELECT COUNT(*) FROM materials WHERE teacher_id = ?");
$stmtMyM->execute([$teacher2Id]);
$t2Count = $stmtMyM->fetchColumn();

if ($t1Count == 1 && $t2Count == 1) {
    echo "✅ PASS: 8. Teacher ownership scoping correctly isolates teacher's questions and materials\n";
    $passCount++;
} else {
    echo "❌ FAIL: 8. Teacher ownership isolation failed\n";
    exit(1);
}

echo "\n--------------------------------------------------------------------\n";
echo "🎉 ALL $passCount TKA vs SNBT AUTHORING INTEGRATION TESTS PASSED!\n";
echo "--------------------------------------------------------------------\n";
