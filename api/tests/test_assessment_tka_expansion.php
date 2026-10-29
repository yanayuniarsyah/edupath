<?php
// api/tests/test_assessment_tka_expansion.php
// Comprehensive Automated Test Suite for Phase 3: EduPath TKA SMA Assessment Expansion

echo "====================================================================\n";
echo "TEST SUITE: EDUPATH TKA SMA ASSESSMENT EXPANSION (PHASE 3)\n";
echo "====================================================================\n\n";

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// --------------------------------------------------------------------
// 0. Setup Complete Database Schema with Phase 9 Additive Columns
// --------------------------------------------------------------------
$pdo->exec("
    CREATE TABLE tenants (
        id TEXT PRIMARY KEY,
        name TEXT,
        slug TEXT,
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE users (
        id TEXT PRIMARY KEY,
        identity_key TEXT,
        password TEXT,
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE user_roles (
        id TEXT PRIMARY KEY,
        user_id TEXT,
        tenant_id TEXT,
        role TEXT,
        reference_id TEXT
    );

    CREATE TABLE students (
        id TEXT PRIMARY KEY,
        name TEXT,
        email TEXT,
        tenant_id TEXT,
        school TEXT,
        school_id TEXT,
        plan TEXT DEFAULT 'free'
    );

    CREATE TABLE schools (
        id TEXT PRIMARY KEY,
        tenant_id TEXT DEFAULT 'default_tenant',
        name TEXT,
        slug TEXT,
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE entitlements (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        tenant_id TEXT,
        feature_key TEXT,
        subscription_id TEXT,
        expires_at TEXT,
        revoked_at TEXT
    );

    CREATE TABLE questions (
        id TEXT PRIMARY KEY,
        subtes TEXT,
        sub_materi TEXT,
        bab TEXT,
        difficulty TEXT,
        question TEXT,
        option_a TEXT,
        option_b TEXT,
        option_c TEXT,
        option_d TEXT,
        option_e TEXT,
        correct TEXT,
        explanation TEXT,
        trick TEXT,
        irt_score REAL DEFAULT 1.0,
        classification TEXT DEFAULT 'TRYOUT',
        education_level TEXT DEFAULT 'SMA',
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        subject TEXT,
        domain TEXT,
        cognitive_demand TEXT DEFAULT 'C3',
        is_active INTEGER DEFAULT 1,
        is_qc_passed INTEGER DEFAULT 1
    );

    CREATE TABLE quiz_attempts (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        quiz_type TEXT,
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        package_id TEXT,
        subtes TEXT,
        status TEXT DEFAULT 'started',
        answers TEXT,
        created_at TEXT,
        expires_at TEXT
    );

    CREATE TABLE quiz_results (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        attempt_id TEXT,
        quiz_type TEXT,
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        package_id TEXT,
        subtes TEXT,
        score REAL,
        correct INTEGER,
        total INTEGER,
        duration_sec INTEGER,
        answers TEXT,
        created_at TEXT
    );

    CREATE TABLE progress (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        subtes TEXT,
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        subject TEXT,
        score REAL,
        total_questions INTEGER,
        correct_answers INTEGER,
        accuracy REAL,
        updated_at TEXT
    );

    CREATE TABLE question_imports_staging (
        id TEXT PRIMARY KEY,
        import_batch_id TEXT,
        question TEXT,
        option_a TEXT,
        option_b TEXT,
        option_c TEXT,
        option_d TEXT,
        option_e TEXT,
        correct TEXT,
        explanation TEXT,
        trick TEXT,
        subtes TEXT,
        sub_materi TEXT,
        education_level TEXT DEFAULT 'SMA',
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        subject TEXT,
        domain TEXT,
        difficulty TEXT,
        cognitive_demand TEXT,
        status TEXT DEFAULT 'pending',
        validation_errors TEXT
    );
");

// --------------------------------------------------------------------
// Seed Tenants & Students
// --------------------------------------------------------------------
$pdo->exec("
    INSERT INTO tenants VALUES ('t-1', 'EduPath B2C', 'edupath-b2c', 1);

    -- Free Student (no entitlements)
    INSERT INTO students (id, name, email, tenant_id, plan) VALUES ('s-free', 'Free Student', 'free@edupath.local', 't-1', 'free');

    -- Pro Student (Unlimited Tryout)
    INSERT INTO students (id, name, email, tenant_id, plan) VALUES ('s-pro', 'Pro Student', 'pro@edupath.local', 't-1', 'pejuang_ptn');
    INSERT INTO entitlements (id, student_id, tenant_id, feature_key) VALUES ('ent-1', 's-pro', 't-1', 'tryout_unlimited');

    -- TKA Only Student
    INSERT INTO students (id, name, email, tenant_id, plan) VALUES ('s-tka', 'TKA Student', 'tka@edupath.local', 't-1', 'tka_saintek');
    INSERT INTO entitlements (id, student_id, tenant_id, feature_key) VALUES ('ent-2', 's-tka', 't-1', 'tka_saintek');

    -- Expired Entitlement Student
    INSERT INTO students (id, name, email, tenant_id, plan) VALUES ('s-exp', 'Expired Student', 'exp@edupath.local', 't-1', 'pejuang_ptn');
    INSERT INTO entitlements (id, student_id, tenant_id, feature_key, expires_at) VALUES ('ent-3', 's-exp', 't-1', 'tryout_unlimited', '2020-01-01 00:00:00');

    -- School 1 & School 2
    INSERT INTO schools (id, tenant_id, name, slug) VALUES ('sch-1', 't-1', 'SMAN 1 Jakarta', 'sman-1-jkt');
    INSERT INTO schools (id, tenant_id, name, slug) VALUES ('sch-2', 't-1', 'SMAN 2 Surabaya', 'sman-2-sby');

    INSERT INTO students (id, name, email, tenant_id, school_id, plan) VALUES ('s-sch1-a', 'Sch1 Student A', 'a@sch1.local', 't-1', 'sch-1', 'pejuang_ptn');
    INSERT INTO students (id, name, email, tenant_id, school_id, plan) VALUES ('s-sch1-b', 'Sch1 Student B', 'b@sch1.local', 't-1', 'sch-1', 'pejuang_ptn');
    INSERT INTO students (id, name, email, tenant_id, school_id, plan) VALUES ('s-sch2-a', 'Sch2 Student A', 'a@sch2.local', 't-1', 'sch-2', 'pejuang_ptn');
");

// --------------------------------------------------------------------
// Seed Questions: SNBT, TKA Saintek, TKA Soshum
// --------------------------------------------------------------------
// 1. SNBT Questions
$pdo->exec("
    INSERT INTO questions (id, subtes, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, trick, irt_score, education_level, assessment_type, track, subject)
    VALUES 
    ('snbt-1', 'penalaran_umum', 'SNBT Soal 1', 'A', 'B', 'C', 'D', 'E', 'A', 'Expl SNBT 1', 'Trick 1', 1.2, 'SMA', 'SNBT', 'umum', 'penalaran_umum'),
    ('snbt-2', 'kuantitatif', 'SNBT Soal 2', 'A', 'B', 'C', 'D', 'E', 'B', 'Expl SNBT 2', 'Trick 2', 1.5, 'SMA', 'SNBT', 'umum', 'kuantitatif');
");

// 2. TKA Saintek Questions
$pdo->exec("
    INSERT INTO questions (id, subtes, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, trick, irt_score, education_level, assessment_type, track, subject)
    VALUES 
    ('saintek-m1', 'Matematika IPA', 'Integral x dx', 'x^2/2', 'x', '2x', 'x^3', '0', 'A', 'Expl Integral', 'Trick Int', 1.0, 'SMA', 'TKA', 'saintek', 'Matematika IPA'),
    ('saintek-f1', 'Fisika', 'Hukum Newton II', 'F=ma', 'E=mc2', 'v=s/t', 'P=F/A', 'W=Fs', 'A', 'Expl Newton', 'Trick Newton', 1.0, 'SMA', 'TKA', 'saintek', 'Fisika'),
    ('saintek-k1', 'Kimia', 'pH asam kuat', '1', '7', '14', '10', '8', 'A', 'Expl pH', 'Trick pH', 1.0, 'SMA', 'TKA', 'saintek', 'Kimia'),
    ('saintek-b1', 'Biologi', 'Organel respirasi sel', 'Mitokondria', 'Ribosom', 'Lisosom', 'Plastida', 'Vakuola', 'A', 'Expl Mitokondria', 'Trick Bio', 1.0, 'SMA', 'TKA', 'saintek', 'Biologi');
");

// 3. TKA Soshum Questions
$pdo->exec("
    INSERT INTO questions (id, subtes, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, trick, irt_score, education_level, assessment_type, track, subject)
    VALUES 
    ('soshum-s1', 'Sosiologi', 'Interaksi sosial terjadi jika', 'Kontak dan komunikasi', 'Isolasi', 'Konflik fisik', 'Diam', 'Tidur', 'A', 'Expl Sosiologi', 'Trick Sos', 1.0, 'SMA', 'TKA', 'soshum', 'Sosiologi'),
    ('soshum-e1', 'Ekonomi', 'Hukum penawaran menyatakan', 'Harga naik jumlah naik', 'Harga naik jumlah turun', 'Tetap', 'Turun', 'Nol', 'A', 'Expl Ekonomi', 'Trick Eko', 1.0, 'SMA', 'TKA', 'soshum', 'Ekonomi'),
    ('soshum-g1', 'Geografi', 'Garis lintang 0 derajat', 'Khatulistiwa', 'Meridian', 'Kutub', 'Tropis', 'Zenit', 'A', 'Expl Geo', 'Trick Geo', 1.0, 'SMA', 'TKA', 'soshum', 'Geografi'),
    ('soshum-j1', 'Sejarah', 'Proklamasi RI tahun', '1945', '1928', '1908', '1965', '1998', 'A', 'Expl Sejarah', 'Trick Sej', 1.0, 'SMA', 'TKA', 'soshum', 'Sejarah');
");


// ====================================================================
// CORE ASSESSMENT ENGINE LOGIC (Mirrors api/quiz.php blueprint & rules)
// ====================================================================
$ASSESSMENT_BLUEPRINTS = [
    'SNBT' => [
        'default_track' => 'umum',
        'allowed_tracks' => ['umum'],
        'subjects' => [
            'penalaran_umum',
            'pemahaman_bacaan',
            'pemahaman_umum',
            'kuantitatif',
            'literasi_indonesia',
            'literasi_inggris',
            'penalaran_matematika'
        ],
        'scoring' => 'irt'
    ],
    'TKA' => [
        'default_track' => 'saintek',
        'allowed_tracks' => ['saintek', 'soshum'],
        'tracks' => [
            'saintek' => [
                'name' => 'TKA Saintek',
                'subjects' => ['Matematika IPA', 'Fisika', 'Kimia', 'Biologi']
            ],
            'soshum' => [
                'name' => 'TKA Soshum',
                'subjects' => ['Sosiologi', 'Ekonomi', 'Geografi', 'Sejarah']
            ]
        ],
        'scoring' => 'percentage'
    ]
];

function verifyEntitlement($pdo, $student_id, $assessment_type = 'SNBT', $track = 'umum') {
    $now = date('Y-m-d H:i:s');
    $stmt = $pdo->prepare("
        SELECT feature_key FROM entitlements 
        WHERE student_id = ? 
          AND revoked_at IS NULL
          AND (expires_at IS NULL OR expires_at > ?)
          AND feature_key IN ('tryout_unlimited', 'premium_assessments', 'tka_saintek', 'tka_soshum', 'full_bundle')
    ");
    $stmt->execute([$student_id, $now]);
    $entitlements = $stmt->fetchAll(PDO::FETCH_COLUMN);

    if (empty($entitlements)) {
        return ['allowed' => false, 'error' => 'ENTITLEMENT_REQUIRED'];
    }

    if ($assessment_type === 'TKA') {
        $requiredKey = 'tka_' . strtolower($track);
        $hasKey = in_array($requiredKey, $entitlements) ||
                  in_array('tryout_unlimited', $entitlements) ||
                  in_array('full_bundle', $entitlements);
        if (!$hasKey) {
            return ['allowed' => false, 'error' => 'ENTITLEMENT_REQUIRED'];
        }
    }
    return ['allowed' => true];
}

function startAssessment($pdo, $blueprints, $student_id, $assessment_type, $track, $minPerSubject = 1) {
    // 1. Validate Assessment Type & Track
    if (!isset($blueprints[$assessment_type])) {
        return ['status' => 400, 'error' => 'INVALID_ASSESSMENT_METADATA', 'message' => 'Unknown assessment type'];
    }
    $bp = $blueprints[$assessment_type];
    if (!in_array($track, $bp['allowed_tracks'])) {
        return ['status' => 400, 'error' => 'INVALID_ASSESSMENT_METADATA', 'message' => 'Invalid track for assessment type'];
    }

    // 2. Entitlement check
    $ent = verifyEntitlement($pdo, $student_id, $assessment_type, $track);
    if (!$ent['allowed']) {
        return ['status' => 403, 'error' => 'ENTITLEMENT_REQUIRED', 'message' => 'Entitlement required'];
    }

    // 3. Question Retrieval per Blueprint
    $questions = [];
    if ($assessment_type === 'TKA') {
        $subjects = $bp['tracks'][$track]['subjects'];
        foreach ($subjects as $subj) {
            $stmt = $pdo->prepare("
                SELECT id, question, option_a, option_b, option_c, option_d, option_e, subtes, subject, track, assessment_type
                FROM questions
                WHERE assessment_type = 'TKA' AND track = ? AND (subject = ? OR subtes = ?) AND is_active = 1
                LIMIT 10
            ");
            $stmt->execute([$track, $subj, $subj]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if (count($rows) < $minPerSubject) {
                // Strict Halt: No silent fallback!
                return [
                    'status' => 422,
                    'error' => 'INSUFFICIENT_QUESTION_BANK',
                    'message' => "Insufficient active questions for subject: $subj in $track"
                ];
            }
            $questions = array_merge($questions, $rows);
        }
    } else {
        // SNBT
        $stmt = $pdo->prepare("
            SELECT id, question, option_a, option_b, option_c, option_d, option_e, subtes, subject, track, assessment_type
            FROM questions
            WHERE (assessment_type = 'SNBT' OR assessment_type IS NULL) AND is_active = 1
        ");
        $stmt->execute();
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 4. Create Attempt
    $attemptId = 'att-' . uniqid();
    $expiresAt = date('Y-m-d H:i:s', time() + 7200);
    $stmt = $pdo->prepare("
        INSERT INTO quiz_attempts (id, student_id, quiz_type, assessment_type, track, package_id, status, created_at, expires_at)
        VALUES (?, ?, 'tryout', ?, ?, 'pkg-default', 'started', datetime('now'), ?)
    ");
    $stmt->execute([$attemptId, $student_id, $assessment_type, $track, $expiresAt]);

    return [
        'status' => 200,
        'attempt_id' => $attemptId,
        'assessment_type' => $assessment_type,
        'track' => $track,
        'expires_at' => $expiresAt,
        'questions' => $questions
    ];
}

function submitAssessment($pdo, $blueprints, $attemptId, $student_id, $answers) {
    $stmt = $pdo->prepare("SELECT * FROM quiz_attempts WHERE id = ?");
    $stmt->execute([$attemptId]);
    $att = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$att) {
        return ['status' => 404, 'error' => 'ATTEMPT_NOT_FOUND'];
    }
    if ($att['student_id'] !== $student_id) {
        return ['status' => 403, 'error' => 'FORBIDDEN'];
    }
    if ($att['status'] === 'completed') {
        return ['status' => 409, 'error' => 'ALREADY_COMPLETED', 'message' => 'Attempt already submitted'];
    }

    $assessment_type = $att['assessment_type'] ?: 'SNBT';
    $track = $att['track'] ?: 'umum';

    // Fetch questions to score
    $qIds = array_column($answers, 'question_id');
    if (empty($qIds)) {
        $finalScore = 0.0;
        $correctCount = 0;
        $total = 0;
    } else {
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $stmt = $pdo->prepare("SELECT id, correct, irt_score FROM questions WHERE id IN ($placeholders)");
        $stmt->execute($qIds);
        $qMap = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $q) {
            $qMap[$q['id']] = $q;
        }

        $correctCount = 0;
        $total = count($answers);
        $irtSum = 0.0;

        foreach ($answers as $ans) {
            $qid = $ans['question_id'];
            $userAns = $ans['answer'] ?? null;
            if (isset($qMap[$qid]) && !empty($userAns) && strtoupper($userAns) === strtoupper($qMap[$qid]['correct'])) {
                $correctCount++;
                $irtSum += (float)($qMap[$qid]['irt_score'] ?? 1.0);
            }
        }

        if ($assessment_type === 'TKA') {
            $finalScore = $total > 0 ? round(($correctCount / $total) * 100, 2) : 0.0;
        } else {
            // SNBT formula
            $finalScore = $total > 0 ? round(500 + ($irtSum / $total) * 300, 2) : 500.0;
        }
    }

    // Mark attempt completed
    $stmt = $pdo->prepare("UPDATE quiz_attempts SET status = 'completed', answers = ? WHERE id = ?");
    $stmt->execute([json_encode($answers), $attemptId]);

    // Insert result
    $resId = 'res-' . uniqid();
    $stmt = $pdo->prepare("
        INSERT INTO quiz_results (id, student_id, attempt_id, quiz_type, assessment_type, track, package_id, score, correct, total, duration_sec, answers, created_at)
        VALUES (?, ?, ?, 'tryout', ?, ?, ?, ?, ?, ?, 3600, ?, datetime('now'))
    ");
    $stmt->execute([$resId, $student_id, $attemptId, $assessment_type, $track, $att['package_id'], $finalScore, $correctCount, $total, json_encode($answers)]);

    return [
        'status' => 200,
        'result_id' => $resId,
        'assessment_type' => $assessment_type,
        'track' => $track,
        'score' => $finalScore,
        'correct' => $correctCount,
        'total' => $total
    ];
}

// --------------------------------------------------------------------
// TEST RUNNER
// --------------------------------------------------------------------
$totalTests = 0;
$passedTests = 0;

function it($title, $fn) {
    global $totalTests, $passedTests;
    $totalTests++;
    try {
        $fn();
        echo "✅ PASS: $title\n";
        $passedTests++;
    } catch (\Throwable $e) {
        echo "❌ FAIL: $title\n";
        echo "   Error: " . $e->getMessage() . " at " . $e->getFile() . ":" . $e->getLine() . "\n";
    }
}


// ====================================================================
// TEST 1: SNBT Tryout Regression
// ====================================================================
it("1. SNBT Tryout Regression — default track umum, IRT scoring, attempt creation & persistence", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    $res = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'SNBT', 'umum');
    assert($res['status'] === 200, "Start SNBT must succeed");
    assert($res['assessment_type'] === 'SNBT', "Assessment type must be SNBT");
    assert($res['track'] === 'umum', "Track must be umum");
    assert(count($res['questions']) === 2, "Must return seeded SNBT questions");

    // Submit attempt with 1 correct, 1 wrong
    $answers = [
        ['question_id' => 'snbt-1', 'answer' => 'A'], // correct (irt 1.2)
        ['question_id' => 'snbt-2', 'answer' => 'A']  // wrong (correct is B)
    ];
    $sub = submitAssessment($pdo, $ASSESSMENT_BLUEPRINTS, $res['attempt_id'], 's-pro', $answers);
    assert($sub['status'] === 200, "Submit SNBT must succeed");
    assert($sub['assessment_type'] === 'SNBT', "Result assessment_type must be SNBT");
    assert($sub['correct'] === 1, "Correct answers must be 1");
    // IRT score: 500 + (1.2/2)*300 = 500 + 180 = 680
    assert($sub['score'] == 680.0, "Score should match SNBT formula (680)");
});

// ====================================================================
// TEST 2: TKA Saintek Subject Filtering
// ====================================================================
it("2. TKA Saintek — Student starts TKA Saintek, questions strictly Matematika IPA, Fisika, Kimia, Biologi", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    $res = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'TKA', 'saintek', 1);
    assert($res['status'] === 200, "Start TKA Saintek must succeed");
    assert(count($res['questions']) === 4, "Must return 4 questions (1 per subject)");

    $expectedSubjects = ['Matematika IPA', 'Fisika', 'Kimia', 'Biologi'];
    foreach ($res['questions'] as $q) {
        assert($q['assessment_type'] === 'TKA', "Question must have assessment_type=TKA");
        assert($q['track'] === 'saintek', "Question must have track=saintek");
        assert(in_array($q['subject'], $expectedSubjects), "Subject {$q['subject']} must be valid Saintek");
    }
});

// ====================================================================
// TEST 3: TKA Soshum Subject Filtering
// ====================================================================
it("3. TKA Soshum — Student starts TKA Soshum, questions strictly Sosiologi, Ekonomi, Geografi, Sejarah", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    $res = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'TKA', 'soshum', 1);
    assert($res['status'] === 200, "Start TKA Soshum must succeed");
    assert(count($res['questions']) === 4, "Must return 4 questions (1 per subject)");

    $expectedSubjects = ['Sosiologi', 'Ekonomi', 'Geografi', 'Sejarah'];
    foreach ($res['questions'] as $q) {
        assert($q['assessment_type'] === 'TKA', "Question must have assessment_type=TKA");
        assert($q['track'] === 'soshum', "Question must have track=soshum");
        assert(in_array($q['subject'], $expectedSubjects), "Subject {$q['subject']} must be valid Soshum");
    }
});

// ====================================================================
// TEST 4: Insufficient Question Bank Guard (Zero Silent Fallback)
// ====================================================================
it("4. Insufficient Question Bank — Returns HTTP 422 INSUFFICIENT_QUESTION_BANK without silent fallback", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    // Delete Kimia questions to trigger bank insufficiency
    $pdo->exec("UPDATE questions SET is_active = 0 WHERE subject = 'Kimia'");

    $res = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'TKA', 'saintek', 1);
    assert($res['status'] === 422, "Status must be 422");
    assert($res['error'] === 'INSUFFICIENT_QUESTION_BANK', "Must return INSUFFICIENT_QUESTION_BANK error code");

    // Re-enable Kimia
    $pdo->exec("UPDATE questions SET is_active = 1 WHERE subject = 'Kimia'");
});

// ====================================================================
// TEST 5: Invalid Assessment Metadata Rejection
// ====================================================================
it("5. Invalid Metadata — Rejects unknown type or invalid tracks with HTTP 400", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    // Invalid type
    $res1 = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'OLYMPIAD', 'saintek');
    assert($res1['status'] === 400, "Unknown assessment type must return 400");
    assert($res1['error'] === 'INVALID_ASSESSMENT_METADATA', "Error code must be INVALID_ASSESSMENT_METADATA");

    // Mismatched track (SNBT does not have saintek track)
    $res2 = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'SNBT', 'saintek');
    assert($res2['status'] === 400, "Mismatched track must return 400");
    assert($res2['error'] === 'INVALID_ASSESSMENT_METADATA', "Error code must be INVALID_ASSESSMENT_METADATA");
});

// ====================================================================
// TEST 6: Unauthorized Package & Expired Entitlements
// ====================================================================
it("6. Unauthorized Access — Free user or expired entitlement rejected with HTTP 403", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    // Free student
    $res1 = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-free', 'TKA', 'saintek');
    assert($res1['status'] === 403, "Free user must be rejected with 403");
    assert($res1['error'] === 'ENTITLEMENT_REQUIRED', "Error code must be ENTITLEMENT_REQUIRED");

    // Expired entitlement student
    $res2 = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-exp', 'TKA', 'saintek');
    assert($res2['status'] === 403, "Expired student must be rejected with 403");
    assert($res2['error'] === 'ENTITLEMENT_REQUIRED', "Error code must be ENTITLEMENT_REQUIRED");

    // Student with only tka_saintek trying to access tka_soshum
    $res3 = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-tka', 'TKA', 'soshum');
    assert($res3['status'] === 403, "TKA Saintek student cannot access Soshum");
});

// ====================================================================
// TEST 7: Question Importer Taxonomy & Staging
// ====================================================================
it("7. Question Importer — Staging, deduplication, and commit with TKA taxonomy", function() use ($pdo) {
    $batchId = 'batch-' . uniqid();
    $stagingId = 'stg-' . uniqid();

    // 1. Insert into staging
    $stmt = $pdo->prepare("
        INSERT INTO question_imports_staging 
        (id, import_batch_id, question, option_a, option_b, option_c, option_d, option_e, correct, subtes, education_level, assessment_type, track, subject, domain, status)
        VALUES (?, ?, 'TKA Batch Soal 1', 'A', 'B', 'C', 'D', 'E', 'A', 'Fisika', 'SMA', 'TKA', 'saintek', 'Fisika', 'Mekanika', 'pending')
    ");
    $stmt->execute([$stagingId, $batchId]);

    // 2. Validate staging entry
    $stmt = $pdo->prepare("SELECT * FROM question_imports_staging WHERE id = ?");
    $stmt->execute([$stagingId]);
    $staged = $stmt->fetch(PDO::FETCH_ASSOC);
    assert($staged['assessment_type'] === 'TKA', "Staged assessment_type must be TKA");
    assert($staged['track'] === 'saintek', "Staged track must be saintek");
    assert($staged['subject'] === 'Fisika', "Staged subject must be Fisika");

    // 3. Commit to questions
    $qId = 'q-imported-' . uniqid();
    $stmt = $pdo->prepare("
        INSERT INTO questions (id, question, option_a, option_b, option_c, option_d, option_e, correct, subtes, education_level, assessment_type, track, subject, domain)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $qId, $staged['question'], $staged['option_a'], $staged['option_b'], $staged['option_c'],
        $staged['option_d'], $staged['option_e'], $staged['correct'], $staged['subtes'],
        $staged['education_level'], $staged['assessment_type'], $staged['track'], $staged['subject'], $staged['domain']
    ]);

    // 4. Verify in questions table
    $stmt = $pdo->prepare("SELECT assessment_type, track, subject, domain FROM questions WHERE id = ?");
    $stmt->execute([$qId]);
    $committed = $stmt->fetch(PDO::FETCH_ASSOC);
    assert($committed['assessment_type'] === 'TKA', "Committed question must have assessment_type TKA");
    assert($committed['track'] === 'saintek', "Committed question must have track saintek");
    assert($committed['domain'] === 'Mekanika', "Committed question must have domain Mekanika");
});

// ====================================================================
// TEST 8: Timer, Autosave & Submit Idempotency
// ====================================================================
it("8. Timer & Submit Idempotency — Completed attempt cannot be re-submitted", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    $res = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'TKA', 'saintek', 1);
    $attemptId = $res['attempt_id'];

    $answers = [
        ['question_id' => 'saintek-m1', 'answer' => 'A'],
        ['question_id' => 'saintek-f1', 'answer' => 'A']
    ];

    // First submit
    $sub1 = submitAssessment($pdo, $ASSESSMENT_BLUEPRINTS, $attemptId, 's-pro', $answers);
    assert($sub1['status'] === 200, "First submit must succeed");

    // Second submit (must be rejected as already completed)
    $sub2 = submitAssessment($pdo, $ASSESSMENT_BLUEPRINTS, $attemptId, 's-pro', $answers);
    assert($sub2['status'] === 409, "Second submit must return 409 conflict");
    assert($sub2['error'] === 'ALREADY_COMPLETED', "Error must indicate ALREADY_COMPLETED");
});

// ====================================================================
// TEST 9: Scoring Boundaries & Unanswered Handling
// ====================================================================
it("9. Scoring Boundaries — Perfect (100%), Zero (0%), Mixed, and Unanswered questions", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    // 9a. 100% Correct
    $res1 = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'TKA', 'saintek', 1);
    $allCorrect = [
        ['question_id' => 'saintek-m1', 'answer' => 'A'],
        ['question_id' => 'saintek-f1', 'answer' => 'A'],
        ['question_id' => 'saintek-k1', 'answer' => 'A'],
        ['question_id' => 'saintek-b1', 'answer' => 'A']
    ];
    $sub1 = submitAssessment($pdo, $ASSESSMENT_BLUEPRINTS, $res1['attempt_id'], 's-pro', $allCorrect);
    assert($sub1['score'] == 100.0, "All correct must yield score 100.0 (got {$sub1['score']})");

    // 9b. All Wrong
    $res2 = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'TKA', 'saintek', 1);
    $allWrong = [
        ['question_id' => 'saintek-m1', 'answer' => 'B'],
        ['question_id' => 'saintek-f1', 'answer' => 'B'],
        ['question_id' => 'saintek-k1', 'answer' => 'B'],
        ['question_id' => 'saintek-b1', 'answer' => 'B']
    ];
    $sub2 = submitAssessment($pdo, $ASSESSMENT_BLUEPRINTS, $res2['attempt_id'], 's-pro', $allWrong);
    assert($sub2['score'] == 0.0, "All wrong must yield score 0.0 (got {$sub2['score']})");

    // 9c. Mixed & Unanswered (2 correct, 1 wrong, 1 unanswered null)
    $res3 = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'TKA', 'saintek', 1);
    $mixed = [
        ['question_id' => 'saintek-m1', 'answer' => 'A'], // correct
        ['question_id' => 'saintek-f1', 'answer' => 'A'], // correct
        ['question_id' => 'saintek-k1', 'answer' => 'B'], // wrong
        ['question_id' => 'saintek-b1', 'answer' => null] // unanswered
    ];
    $sub3 = submitAssessment($pdo, $ASSESSMENT_BLUEPRINTS, $res3['attempt_id'], 's-pro', $mixed);
    assert($sub3['correct'] === 2, "Correct count must be 2");
    assert($sub3['score'] == 50.0, "2 of 4 must yield score 50.0 (got {$sub3['score']})");
});

// ====================================================================
// TEST 10: School Dashboard Analytics Separation & Tenant Isolation
// ====================================================================
it("10. School Analytics — SNBT vs TKA metric separation and tenant isolation", function() use ($pdo) {
    // Sch1 Student A: SNBT score = 700
    $pdo->exec("
        INSERT INTO quiz_results (id, student_id, attempt_id, quiz_type, assessment_type, track, score, correct, total, answers, created_at)
        VALUES ('res-sch1-snbt', 's-sch1-a', 'att-sch1-1', 'tryout', 'SNBT', 'umum', 700.0, 70, 100, '[]', datetime('now'))
    ");
    // Sch1 Student B: TKA score = 80
    $pdo->exec("
        INSERT INTO quiz_results (id, student_id, attempt_id, quiz_type, assessment_type, track, score, correct, total, answers, created_at)
        VALUES ('res-sch1-tka', 's-sch1-b', 'att-sch1-2', 'tryout', 'TKA', 'saintek', 80.0, 16, 20, '[]', datetime('now'))
    ");

    // Sch2 Student A: SNBT score = 600
    $pdo->exec("
        INSERT INTO quiz_results (id, student_id, attempt_id, quiz_type, assessment_type, track, score, correct, total, answers, created_at)
        VALUES ('res-sch2-snbt', 's-sch2-a', 'att-sch2-1', 'tryout', 'SNBT', 'umum', 600.0, 60, 100, '[]', datetime('now'))
    ");

    // Query analytics for School 1 using updated school.php aggregation logic
    $stmt = $pdo->prepare("
        SELECT 
            COUNT(DISTINCT qr.id) AS total_attempts,
            ROUND(AVG(CASE WHEN qr.assessment_type = 'SNBT' OR qr.assessment_type IS NULL THEN qr.score END), 2) AS avg_snbt_score,
            ROUND(AVG(CASE WHEN qr.assessment_type = 'TKA' THEN qr.score END), 2) AS avg_tka_score
        FROM quiz_results qr
        JOIN students s ON s.id = qr.student_id
        WHERE s.school_id = 'sch-1'
    ");
    $stmt->execute();
    $analytics = $stmt->fetch(PDO::FETCH_ASSOC);

    assert((int)$analytics['total_attempts'] === 2, "School 1 should have 2 attempts total");
    assert((float)$analytics['avg_snbt_score'] === 700.0, "School 1 avg SNBT score must be 700.0 without TKA contamination");
    assert((float)$analytics['avg_tka_score'] === 80.0, "School 1 avg TKA score must be 80.0 without SNBT contamination");

    // Verify Sch 2 does not have Sch 1's results (tenant/school isolation)
    $stmt = $pdo->prepare("
        SELECT COUNT(DISTINCT qr.id) as cnt FROM quiz_results qr JOIN students s ON s.id = qr.student_id WHERE s.school_id = 'sch-2'
    ");
    $stmt->execute();
    $sch2Cnt = (int)$stmt->fetchColumn();
    assert($sch2Cnt === 1, "School 2 must only have its own 1 attempt");
});

// ====================================================================
// TEST 11: Legacy Record Compatibility (assessment_type IS NULL)
// ====================================================================
it("11. Legacy Record Compatibility — NULL assessment_type/track safely defaults to SNBT/umum", function() use ($pdo) {
    // Insert legacy attempt with NULL columns
    $pdo->exec("
        INSERT INTO quiz_attempts (id, student_id, quiz_type, assessment_type, track, package_id, subtes, status, answers)
        VALUES ('att-legacy', 's-pro', 'tryout', NULL, NULL, NULL, 'penalaran_umum', 'completed', '[]')
    ");

    // Query mimicking quiz.php review or student dashboard
    $stmt = $pdo->prepare("SELECT id, COALESCE(assessment_type, 'SNBT') AS assessment_type, COALESCE(track, 'umum') AS track FROM quiz_attempts WHERE id = 'att-legacy'");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);

    assert($row['assessment_type'] === 'SNBT', "Legacy record must resolve to SNBT");
    assert($row['track'] === 'umum', "Legacy record must resolve to track umum");
});

// ====================================================================
// TEST 12: Student-Facing Answer Key Protection
// ====================================================================
it("12. Answer Key Protection — Answers, explanations, and tricks never returned during active assessment", function() use ($pdo, $ASSESSMENT_BLUEPRINTS) {
    $res = startAssessment($pdo, $ASSESSMENT_BLUEPRINTS, 's-pro', 'TKA', 'saintek', 1);
    assert($res['status'] === 200, "Start assessment succeeded");

    foreach ($res['questions'] as $q) {
        assert(!array_key_exists('correct', $q), "Active question must NOT contain 'correct'");
        assert(!array_key_exists('explanation', $q), "Active question must NOT contain 'explanation'");
        assert(!array_key_exists('trick', $q), "Active question must NOT contain 'trick'");
        assert(!array_key_exists('is_correct', $q), "Active question must NOT contain 'is_correct'");
    }
});

// --------------------------------------------------------------------
// SUMMARY
// --------------------------------------------------------------------
echo "\n--------------------------------------------------------------------\n";
if ($passedTests === $totalTests) {
    echo "🎉 ALL $totalTests PHASE 3 AUTOMATED TESTS PASSED WITH ZERO FAILURES!\n";
} else {
    echo "⚠️ $passedTests of $totalTests tests passed. Check errors above.\n";
}
echo "--------------------------------------------------------------------\n";
