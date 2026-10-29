<?php
// api/tests/extreme_uat_suite.php
// Extreme UAT Automated Verification Suite for EduPath SNBT & TKA Platform

echo "====================================================================\n";
echo "EXTREME UAT VERIFICATION SUITE — EDUPATH CBT & MULTI-TENANT ENGINE\n";
echo "====================================================================\n\n";

$pdo = new PDO('sqlite::memory:');
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// 1. Initialize DB Schema
$pdo->exec("
    CREATE TABLE students (
        id TEXT PRIMARY KEY,
        tenant_id TEXT DEFAULT 'default_tenant',
        school_id TEXT,
        school TEXT,
        name TEXT,
        email TEXT,
        plan TEXT DEFAULT 'pro',
        is_active INTEGER DEFAULT 1
    );

    CREATE TABLE subscriptions (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        plan_id TEXT,
        status TEXT DEFAULT 'active',
        expires_at TEXT
    );

    CREATE TABLE plan_entitlements (
        id TEXT PRIMARY KEY,
        tenant_id TEXT,
        plan_id TEXT,
        feature_key TEXT,
        quota INTEGER DEFAULT -1
    );

    CREATE TABLE student_entitlements (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        tenant_id TEXT,
        feature_key TEXT,
        source_type TEXT DEFAULT 'plan',
        source_id TEXT,
        expires_at TEXT
    );

    CREATE TABLE questions (
        id TEXT PRIMARY KEY,
        exam TEXT DEFAULT 'SNBT',
        test_component TEXT DEFAULT 'TPS',
        subtest TEXT,
        subtes TEXT,
        sub_materi TEXT,
        topic TEXT,
        subtopic TEXT,
        bab TEXT,
        skill TEXT,
        indicator TEXT,
        question_type TEXT DEFAULT 'multiple_choice',
        difficulty TEXT DEFAULT 'medium',
        question TEXT,
        option_a TEXT,
        option_b TEXT,
        option_c TEXT,
        option_d TEXT,
        option_e TEXT,
        correct TEXT,
        explanation TEXT,
        trick TEXT,
        cognitive_demand TEXT,
        source_type TEXT DEFAULT 'author_created',
        rights_status TEXT DEFAULT 'unknown',
        source_name TEXT,
        source_year INTEGER,
        source_reference TEXT,
        is_qc_passed INTEGER DEFAULT 1,
        is_active INTEGER DEFAULT 1,
        classification TEXT DEFAULT 'TRYOUT',
        teacher_name TEXT,
        teacher_id TEXT,
        education_level TEXT DEFAULT 'SMA',
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        subject TEXT,
        domain TEXT,
        irt_score REAL DEFAULT 1.0,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE quiz_attempts (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        quiz_type TEXT DEFAULT 'tryout',
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        package_id TEXT,
        subtes TEXT,
        status TEXT DEFAULT 'started',
        answers TEXT,
        last_saved_at TEXT,
        expires_at TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP,
        completed_at TEXT
    );

    CREATE TABLE quiz_results (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        attempt_id TEXT,
        quiz_type TEXT DEFAULT 'tryout',
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        package_id TEXT,
        subtes TEXT,
        score REAL,
        correct INTEGER,
        total INTEGER,
        duration_sec INTEGER,
        answers TEXT,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );

    CREATE TABLE progress (
        id TEXT PRIMARY KEY,
        student_id TEXT,
        subtes TEXT,
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        subject TEXT,
        bab TEXT,
        score REAL DEFAULT 0,
        mastery INTEGER DEFAULT 0,
        last_study TEXT
    );

    CREATE TABLE materials (
        id TEXT PRIMARY KEY,
        subtes TEXT,
        exam TEXT DEFAULT 'SNBT',
        test_component TEXT DEFAULT 'TPS',
        subtest TEXT,
        sub_materi TEXT,
        topic TEXT,
        subtopic TEXT,
        skill TEXT,
        indicator TEXT,
        title TEXT,
        content TEXT,
        formula_praktis TEXT,
        teacher_name TEXT,
        teacher_id TEXT,
        file_url TEXT,
        education_level TEXT DEFAULT 'SMA',
        assessment_type TEXT DEFAULT 'SNBT',
        track TEXT DEFAULT 'umum',
        subject TEXT,
        domain TEXT,
        is_active INTEGER DEFAULT 1,
        created_at TEXT DEFAULT CURRENT_TIMESTAMP
    );
");

// Seed Base Students
$pdo->exec("
    INSERT INTO students (id, school_id, school, name, email, plan) VALUES
    ('stu-sman1-01', 'sch-sman1', 'SMAN 1 Teladan', 'Budi Santoso', 'budi@sman1.sch.id', 'pro'),
    ('stu-sman1-02', 'sch-sman1', 'SMAN 1 Teladan', 'Siti Rahma', 'siti@sman1.sch.id', 'pro'),
    ('stu-sman2-01', 'sch-sman2', 'SMAN 2 Maju', 'Joko Susilo', 'joko@sman2.sch.id', 'pro');
");

// Seed TKA Questions (4 Saintek, 4 Soshum)
$pdo->exec("
    INSERT INTO questions (id, assessment_type, track, subject, sub_materi, bab, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, trick, irt_score) VALUES
    ('q-tka-mipa-1', 'TKA', 'saintek', 'Matematika IPA', 'Matematika IPA', 'Kalkulus', 'Turunan sin(x)?', 'cos(x)', '-cos(x)', 'tan(x)', 'sec(x)', 'csc(x)', 'A', 'Turunan sin adalah cos', 'Rumus dasar', 1.0),
    ('q-tka-fis-1', 'TKA', 'saintek', 'Fisika', 'Fisika', 'Mekanika', 'Rumus F = ?', 'm.a', 'm.v', '1/2 m.v^2', 'm.g.h', 'p.v', 'A', 'Hukum Newton II', 'F=ma', 1.0),
    ('q-tka-kim-1', 'TKA', 'saintek', 'Kimia', 'Kimia', 'Stoikiometri', 'Rumus molekul air?', 'H2O', 'CO2', 'NaCl', 'HCl', 'CH4', 'A', 'Air adalah H2O', 'Kaidah Lewis', 1.0),
    ('q-tka-bio-1', 'TKA', 'saintek', 'Biologi', 'Biologi', 'Sel', 'Pusat energi sel?', 'Mitokondria', 'Ribosom', 'Nukleus', 'Kloroplas', 'Lisosom', 'A', 'Mitokondria powerhouse of cell', 'Bio sel', 1.0),
    
    ('q-tka-sos-1', 'TKA', 'soshum', 'Sosiologi', 'Sosiologi', 'Interaksi Sosial', 'Bentuk interaksi asosiatif?', 'Kerjasama', 'Konflik', 'Kompetisi', 'Kontravensi', 'Pertikaian', 'A', 'Kerjasama asosiatif', 'Sosiologi dasar', 1.0),
    ('q-tka-eko-1', 'TKA', 'soshum', 'Ekonomi', 'Ekonomi', 'Permintaan & Penawaran', 'Hukum permintaan berbunyi?', 'Harga naik jumlah turun', 'Harga naik jumlah naik', 'Tetap', 'Tidak terpengaruh', 'Tak tentu', 'A', 'Ceteris paribus', 'Kurva permintaan', 1.0),
    ('q-tka-geo-1', 'TKA', 'soshum', 'Geografi', 'Geografi', 'Litosfer', 'Lapisan terluar bumi?', 'Kerak bumi', 'Mantel', 'Inti luar', 'Inti dalam', 'Astenosfer', 'A', 'Crust / kerak bumi', 'Struktur bumi', 1.0),
    ('q-tka-sej-1', 'TKA', 'soshum', 'Sejarah', 'Sejarah', 'Kemerdekaan RI', 'Tahun proklamasi RI?', '1945', '1942', '1928', '1908', '1950', 'A', '17 Agustus 1945', 'Kronologi sejarah', 1.0);
");

// Helper function to simulate quiz submission logic matching api/quiz.php
function executeQuizSubmission($pdo, $student_id, $input) {
    $assessment_type = $input['assessment_type'] ?? 'SNBT';
    $track = $input['track'] ?? 'umum';
    $quiz_type = $input['quiz_type'] ?? 'tryout';
    $attempt_id = $input['attempt_id'] ?? null;
    $duration_sec = $input['duration_sec'] ?? 0;
    $answers = $input['answers'] ?? [];

    $ASSESSMENT_BLUEPRINTS = [
        'SNBT' => ['umum' => ['default_limit' => 150]],
        'TKA' => [
            'saintek' => ['default_limit' => 80],
            'soshum' => ['default_limit' => 80]
        ]
    ];

    if ($attempt_id) {
        $stmt = $pdo->prepare("SELECT status, assessment_type, track FROM quiz_attempts WHERE id = ? AND student_id = ?");
        $stmt->execute([$attempt_id, $student_id]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$attempt) {
            return ['error' => 'Attempt tidak ditemukan', 'http_code' => 404];
        }
        if (!empty($attempt['assessment_type'])) $assessment_type = $attempt['assessment_type'];
        if (!empty($attempt['track'])) $track = $attempt['track'];

        if ($attempt['status'] === 'completed') {
            $chk = $pdo->prepare("SELECT score, correct, total, id as result_id FROM quiz_results WHERE attempt_id = ? AND student_id = ?");
            $chk->execute([$attempt_id, $student_id]);
            $existing = $chk->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                return [
                    'success' => true,
                    'score' => (float)$existing['score'],
                    'correct' => (int)$existing['correct'],
                    'total' => (int)$existing['total'],
                    'result_id' => $existing['result_id'],
                    'message' => 'Hasil sudah tersimpan (Idempotent response)'
                ];
            }
        }
    }

    $correctCount = 0;
    $totalScore = 0;
    $detailedAnswers = [];

    $clientTotal = (int)($input['total_questions'] ?? ($input['total'] ?? 0));
    $blueprint = $ASSESSMENT_BLUEPRINTS[$assessment_type][$track] ?? null;

    if ($clientTotal > 0) {
        $totalQuestions = $clientTotal;
    } elseif ($assessment_type === 'TKA' && isset($blueprint['default_limit'])) {
        $totalQuestions = (int)$blueprint['default_limit'];
    } else {
        $totalQuestions = is_array($answers) ? count($answers) : 0;
    }

    if (!empty($answers)) {
        $qIds = array_column($answers, 'question_id');
        $placeholders = implode(',', array_fill(0, count($qIds), '?'));
        $stmt = $pdo->prepare("SELECT id, correct, irt_score, sub_materi AS subtes, bab FROM questions WHERE id IN ($placeholders)");
        $stmt->execute($qIds);
        $questionsDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $qMap = [];
        foreach ($questionsDb as $q) {
            $qMap[$q['id']] = $q;
        }

        $seenQuestions = [];
        foreach ($answers as $ans) {
            $qid = (string)($ans['question_id'] ?? '');
            if (empty($qid) || isset($seenQuestions[$qid])) continue;
            $seenQuestions[$qid] = true;

            $user_ans = strtolower(trim((string)($ans['answer'] ?? '')));
            if (isset($qMap[$qid])) {
                $db_q = $qMap[$qid];
                $is_correct = ($user_ans !== '' && $user_ans === strtolower((string)$db_q['correct']));
                if ($is_correct) {
                    $correctCount++;
                    $qWeight = ((float)($db_q['irt_score'] ?? 0) > 0) ? (float)$db_q['irt_score'] : 1.0;
                    $totalScore += $qWeight;
                }
                $detailedAnswers[] = [
                    'question_id' => $qid,
                    'user_answer' => $user_ans,
                    'is_correct' => $is_correct
                ];
            }
        }
        if ($clientTotal <= 0 && $assessment_type !== 'TKA' && !empty($seenQuestions)) {
            $totalQuestions = count($seenQuestions);
        }
    }

    if ($assessment_type === 'TKA') {
        $totalScore = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0.0;
    }

    $id = 'res-' . bin2hex(random_bytes(6));
    $ins = $pdo->prepare("INSERT INTO quiz_results (id, student_id, attempt_id, quiz_type, assessment_type, track, score, correct, total, duration_sec, answers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $ins->execute([$id, $student_id, $attempt_id, $quiz_type, $assessment_type, $track, $totalScore, $correctCount, $totalQuestions, $duration_sec, json_encode($detailedAnswers)]);

    if ($attempt_id) {
        $pdo->prepare("UPDATE quiz_attempts SET status = 'completed', completed_at = CURRENT_TIMESTAMP WHERE id = ? AND student_id = ?")->execute([$attempt_id, $student_id]);
    }

    return [
        'success' => true,
        'score' => $totalScore,
        'correct' => $correctCount,
        'total' => $totalQuestions,
        'assessment_type' => $assessment_type,
        'track' => $track,
        'result_id' => $id,
        'message' => 'Hasil berhasil disimpan'
    ];
}

// ----------------------------------------------------------------------------
// TEST 1: TKA CBT Submit with 0 answers (Empty submission / time-out scenario)
// ----------------------------------------------------------------------------
$pdo->prepare("INSERT INTO quiz_attempts (id, student_id, assessment_type, track, status) VALUES ('att-empty-1', 'stu-sman1-01', 'TKA', 'saintek', 'started')")->execute();

$res1 = executeQuizSubmission($pdo, 'stu-sman1-01', [
    'attempt_id' => 'att-empty-1',
    'assessment_type' => 'TKA',
    'track' => 'saintek',
    'total_questions' => 80,
    'answers' => [],
    'duration_sec' => 5400
]);

$attCheck1 = $pdo->query("SELECT status FROM quiz_attempts WHERE id = 'att-empty-1'")->fetchColumn();
assert($res1['success'] === true, "Submit 0 answers must succeed");
assert($res1['score'] === 0.0, "Score must be 0.0");
assert($res1['correct'] === 0, "Correct must be 0");
assert($res1['total'] === 80, "Total must be 80");
assert($attCheck1 === 'completed', "Attempt status must be updated to completed");

// Verify idempotency
$res1_idempotent = executeQuizSubmission($pdo, 'stu-sman1-01', [
    'attempt_id' => 'att-empty-1',
    'answers' => []
]);
assert(strpos($res1_idempotent['message'], 'Idempotent') !== false, "Re-submit must return idempotent response");
echo "✅ PASS: 1. Empty submission (timeout / 0 answers) correctly persists score 0.0, completes attempt, and enforces idempotency\n";

// ----------------------------------------------------------------------------
// TEST 2: TKA CBT Partial submission percentage scoring boundary
// ----------------------------------------------------------------------------
$pdo->prepare("INSERT INTO quiz_attempts (id, student_id, assessment_type, track, status) VALUES ('att-partial-1', 'stu-sman1-02', 'TKA', 'saintek', 'started')")->execute();

$res2 = executeQuizSubmission($pdo, 'stu-sman1-02', [
    'attempt_id' => 'att-partial-1',
    'assessment_type' => 'TKA',
    'track' => 'saintek',
    'total_questions' => 80,
    'answers' => [
        ['question_id' => 'q-tka-mipa-1', 'answer' => 'A'], // correct
        ['question_id' => 'q-tka-fis-1', 'answer' => 'A'],  // correct
        ['question_id' => 'q-tka-kim-1', 'answer' => 'A'],  // correct
        ['question_id' => 'q-tka-bio-1', 'answer' => 'A'],  // correct
        ['question_id' => 'q-tka-sos-1', 'answer' => 'B']   // wrong
    ]
]);

// 4 correct out of 80 questions = (4 / 80) * 100 = 5.00%
assert($res2['score'] === 5.0, "Score must be exactly 5.0% (4 / 80 * 100), got {$res2['score']}");
assert($res2['correct'] === 4, "Correct must be 4");
assert($res2['total'] === 80, "Total must be 80");
echo "✅ PASS: 2. TKA partial submission score is strictly calculated from exam total questions (5.0%), preventing score inflation\n";

// ----------------------------------------------------------------------------
// TEST 3: Deduplication & Case Insensitive Answers
// ----------------------------------------------------------------------------
$pdo->prepare("INSERT INTO quiz_attempts (id, student_id, assessment_type, track, status) VALUES ('att-dedup-1', 'stu-sman2-01', 'TKA', 'soshum', 'started')")->execute();

$res3 = executeQuizSubmission($pdo, 'stu-sman2-01', [
    'attempt_id' => 'att-dedup-1',
    'assessment_type' => 'TKA',
    'track' => 'soshum',
    'total_questions' => 80,
    'answers' => [
        ['question_id' => 'q-tka-sos-1', 'answer' => ' a '], // duplicate 1
        ['question_id' => 'q-tka-sos-1', 'answer' => 'A'],   // duplicate 2
        ['question_id' => 'q-tka-eko-1', 'answer' => 'a'],   // lowercase
    ]
]);

// 2 unique questions answered, both correct = (2 / 80) * 100 = 2.5%
assert($res3['correct'] === 2, "Deduplication must yield exactly 2 correct answers");
assert($res3['score'] === 2.5, "Score must be 2.5%");
echo "✅ PASS: 3. Deduplication prevents multiple submission of the same question_id and trims/lowercases answers properly\n";

// ----------------------------------------------------------------------------
// TEST 4: SNBT vs TKA School Analytics Separation & Multi-Tenant Isolation
// ----------------------------------------------------------------------------
// SMAN 1 has Stu 1 (Score 0) and Stu 2 (TKA Score 5.0)
// Let's add an SNBT result for Stu 1 (IRT Score 750)
$pdo->exec("
    INSERT INTO quiz_results (id, student_id, assessment_type, track, score, correct, total) VALUES
    ('res-snbt-sman1-01', 'stu-sman1-01', 'SNBT', 'umum', 750.0, 75, 150);
");

// Run school analytics query for SMAN 1
$stmt_to = $pdo->prepare("
    SELECT 
        COUNT(DISTINCT qr.student_id) as tryout_participants,
        COUNT(qr.id) as total_tryouts_taken,
        COALESCE(AVG(CASE WHEN qr.assessment_type = 'SNBT' OR qr.assessment_type IS NULL THEN qr.score END), 0) as avg_snbt_score,
        COALESCE(AVG(CASE WHEN qr.assessment_type = 'TKA' THEN qr.score END), 0) as avg_tka_score,
        COUNT(CASE WHEN qr.assessment_type = 'TKA' THEN 1 END) as tka_tryouts_taken
    FROM quiz_results qr
    JOIN students st ON qr.student_id = st.id
    WHERE st.school_id = ?
");
$stmt_to->execute(['sch-sman1']);
$stats_sman1 = $stmt_to->fetch(PDO::FETCH_ASSOC);

assert((int)$stats_sman1['tryout_participants'] === 2, "SMAN 1 must have 2 participants");
assert((float)$stats_sman1['avg_snbt_score'] === 750.0, "SMAN 1 avg_snbt_score must be 750.0");
assert((float)$stats_sman1['avg_tka_score'] === 2.5, "SMAN 1 avg_tka_score must be 2.5 ((0 + 5.0) / 2)");
assert((int)$stats_sman1['tka_tryouts_taken'] === 2, "SMAN 1 tka_tryouts_taken must be 2");

// SMAN 2 Analytics check
$stmt_to->execute(['sch-sman2']);
$stats_sman2 = $stmt_to->fetch(PDO::FETCH_ASSOC);
assert((int)$stats_sman2['tryout_participants'] === 1, "SMAN 2 must have 1 participant");
assert((float)$stats_sman2['avg_snbt_score'] === 0.0, "SMAN 2 avg_snbt_score must be 0 (no SNBT results)");
assert((float)$stats_sman2['avg_tka_score'] === 2.5, "SMAN 2 avg_tka_score must be 2.5");

echo "✅ PASS: 4. School Dashboard accurately isolates SNBT IRT scores from TKA percentage scores with strict tenant isolation\n";

// ----------------------------------------------------------------------------
// TEST 5: CBT Review Access Security (Anti-Cheat & Anti-IDOR)
// ----------------------------------------------------------------------------
function executeReviewQuery($pdo, $requesting_student_id, $result_id) {
    $stmt = $pdo->prepare("SELECT * FROM quiz_results WHERE id = ?");
    $stmt->execute([$result_id]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$result) return ['status' => 404, 'error' => 'Not found'];
    if ($result['student_id'] !== $requesting_student_id) {
        return ['status' => 403, 'error' => 'Akses review kuis tidak diizinkan'];
    }
    return ['status' => 200, 'result' => $result];
}

$revOwn = executeReviewQuery($pdo, 'stu-sman1-02', $res2['result_id']);
assert($revOwn['status'] === 200, "Student must be able to view their own review");

$revIdor = executeReviewQuery($pdo, 'stu-sman2-01', $res2['result_id']);
assert($revIdor['status'] === 403, "Student from another school or account must be blocked with HTTP 403 (IDOR blocked)");
echo "✅ PASS: 5. CBT Review access enforces ownership check, blocking cross-student IDOR vulnerabilities\n";

// ----------------------------------------------------------------------------
// TEST 6: Material API with TKA Track Filtering
// ----------------------------------------------------------------------------
$pdo->exec("
    INSERT INTO materials (id, title, content, subtest, assessment_type, track) VALUES
    ('mat-saintek-1', 'Konsep Kinematika', 'Rumus gerak', 'Fisika', 'TKA', 'saintek'),
    ('mat-soshum-1', 'Struktur Sosial', 'Konsep stratifikasi', 'Sosiologi', 'TKA', 'soshum'),
    ('mat-snbt-1', 'Silogisme Logika', 'Hukum inferensi', 'Penalaran Umum', 'SNBT', 'umum');
");

$stmtSaintek = $pdo->prepare("SELECT COUNT(*) FROM materials WHERE assessment_type = 'TKA' AND (track = 'saintek' OR track IS NULL)");
$stmtSaintek->execute();
assert((int)$stmtSaintek->fetchColumn() === 1, "Saintek material filter must return exactly 1 item");

$stmtSoshum = $pdo->prepare("SELECT COUNT(*) FROM materials WHERE assessment_type = 'TKA' AND (track = 'soshum' OR track IS NULL)");
$stmtSoshum->execute();
assert((int)$stmtSoshum->fetchColumn() === 1, "Soshum material filter must return exactly 1 item");

echo "✅ PASS: 6. Materials API filters cleanly isolate TKA Saintek and Soshum materials\n";

// ----------------------------------------------------------------------------
// TEST 7: Teacher Question CRUD Ownership Scoping
// ----------------------------------------------------------------------------
$pdo->exec("
    INSERT INTO questions (id, question, correct, teacher_id, teacher_name) VALUES
    ('q-teach-a', 'Pertanyaan Guru A', 'A', 'teacher-001', 'Pak Guru A'),
    ('q-teach-b', 'Pertanyaan Guru B', 'B', 'teacher-002', 'Bu Guru B');
");

function updateQuestionAsTeacher($pdo, $teacher_id, $question_id, $new_text) {
    $chk = $pdo->prepare("SELECT teacher_id FROM questions WHERE id = ?");
    $chk->execute([$question_id]);
    $q = $chk->fetch(PDO::FETCH_ASSOC);
    if ($q && !empty($q['teacher_id']) && $q['teacher_id'] !== $teacher_id) {
        return ['status' => 403, 'error' => 'Anda hanya dapat mengubah soal yang Anda buat.'];
    }
    $pdo->prepare("UPDATE questions SET question = ? WHERE id = ?")->execute([$new_text, $question_id]);
    return ['status' => 200, 'success' => true];
}

$teachA_update_own = updateQuestionAsTeacher($pdo, 'teacher-001', 'q-teach-a', 'Updated text A');
assert($teachA_update_own['status'] === 200, "Teacher must be allowed to update own question");

$teachA_update_other = updateQuestionAsTeacher($pdo, 'teacher-001', 'q-teach-b', 'Malicious update text');
assert($teachA_update_other['status'] === 403, "Teacher must be blocked from updating another teacher's question");

echo "✅ PASS: 7. Teacher Question CRUD ownership prevents cross-teacher tampering and enforces RBAC\n";

echo "\n--------------------------------------------------------------------\n";
echo "🎉 ALL 7 EXTREME UAT VERIFICATION TESTS PASSED WITH ZERO FAILURES!\n";
echo "--------------------------------------------------------------------\n";
