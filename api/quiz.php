<?php
// api/quiz.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

$payload = authenticate();
if (!in_array($payload->role, ['student', 'admin', 'superadmin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(["error" => "Akses tidak diizinkan"]);
    exit;
}

require_once 'EntitlementService.php';

function verify_quiz_entitlement($pdo, $payload, $quiz_type) {
    if ($payload->role !== 'student' || strtolower($quiz_type) !== 'tryout') {
        return true;
    }
    
    $entitlementService = new EntitlementService($pdo);
    $tenant_id = $payload->tenant_id ?? 'default_tenant';
    
    $hasTryout = $entitlementService->hasEntitlement($payload->id, $tenant_id, 'tryout_unlimited')
              || $entitlementService->hasEntitlement($payload->id, $tenant_id, 'premium_assessments');
              
    if (!$hasTryout) {
        // Auto-heal check if student has active subscription or paid plan record
        try {
            $stmt = $pdo->prepare("
                SELECT s.plan, sub.id as sub_id, sub.expires_at 
                FROM students s 
                LEFT JOIN subscriptions sub ON s.id = sub.student_id AND sub.status = 'active' AND (sub.expires_at IS NULL OR sub.expires_at > NOW())
                WHERE s.id = ?
            ");
            $stmt->execute([$payload->id]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            $plan_name = strtolower($row['plan'] ?? '');
            
            $is_paid = !empty($row['sub_id']);
            if (!$is_paid && $plan_name !== 'free' && $plan_name !== 'trial' && $plan_name !== '') {
                foreach (['pejuang', 'mandiri', 'elite', 'utbk', 'premium', 'starter', 'utama', 'pro'] as $k) {
                    if (strpos($plan_name, $k) !== false) {
                        $is_paid = true;
                        break;
                    }
                }
            }
            
            if ($is_paid) {
                $entitlementService->grantEntitlement($payload->id, 'tryout_unlimited', $row['sub_id'] ?? null, $row['expires_at'] ?? null);
                $hasTryout = true;
            }
        } catch (\Throwable $e) {}
    }
    
    if (!$hasTryout) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "error" => "Paket Berlangganan Diperlukan. Fitur CBT Tryout Nasional berbobot IRT hanya tersedia untuk pengguna Pro Member.",
            "code" => "ENTITLEMENT_REQUIRED"
        ]);
        exit;
    }
    return true;
}

if (($action === 'save' || $action === 'save_answers') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $attempt_id = trim($input['attempt_id'] ?? '');
    $answers = $input['answers'] ?? [];
    if ($attempt_id === '' || !is_array($answers)) {
        http_response_code(400);
        echo json_encode(["error" => "Attempt dan jawaban wajib dikirim."]);
        exit;
    }
    try {
        $stmt = $pdo->prepare("UPDATE quiz_attempts SET answers = ?, last_saved_at = NOW() WHERE id = ? AND student_id = ? AND status = 'started' AND (expires_at IS NULL OR expires_at >= NOW())");
        $stmt->execute([json_encode($answers), $attempt_id, $payload->id]);
        if ($stmt->rowCount() !== 1) {
            http_response_code(409);
            echo json_encode(["error" => "Attempt tidak aktif atau waktu telah habis."]);
            exit;
        }
        echo json_encode(["success" => true, "saved_at" => date('c')]);
    } catch (PDOException $e) {
        http_response_code(503);
        echo json_encode(["error" => "Autosave belum tersedia. Jalankan migrasi reliability terlebih dahulu."]);
    }
    exit;
}

if (($action === 'start' || $action === 'start_attempt') && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit($pdo, 'quiz_start', 10, 5)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak permintaan mulai kuis."]);
        exit;
    }

    $subtes = $input['subtes'] ?? null;
    $quiz_type = $input['quiz_type'] ?? 'latihan';
    verify_quiz_entitlement($pdo, $payload, $quiz_type);
    $limit = $quiz_type === 'tryout' ? 150 : max(1, min(50, (int)($input['limit'] ?? 10)));

    $duration_sec = max(60, min(7200, (int)($input['duration_sec'] ?? 900)));
    $expires_at = date('Y-m-d H:i:s', time() + $duration_sec);
    $attempt_id = trim($input['attempt_id'] ?? '');
    if ($attempt_id !== '') {
        try {
            $resume = $pdo->prepare("SELECT id, expires_at FROM quiz_attempts WHERE id = ? AND student_id = ? AND status = 'started'");
            $resume->execute([$attempt_id, $payload->id]);
            $existing = $resume->fetch();
            if ($existing) $expires_at = $existing['expires_at'];
            else $attempt_id = '';
        } catch (PDOException $e) {
            $attempt_id = '';
        }
    }
    if ($attempt_id === '') {
        $attempt_id = bin2hex(random_bytes(16));
        $attempt_id = substr($attempt_id,0,8).'-'.substr($attempt_id,8,4).'-'.substr($attempt_id,12,4).'-'.substr($attempt_id,16,4).'-'.substr($attempt_id,20,12);
        try {
            $stmt = $pdo->prepare("INSERT INTO quiz_attempts (id, student_id, quiz_type, subtes, status, expires_at) VALUES (?, ?, ?, ?, 'started', ?)");
            $stmt->execute([$attempt_id, $payload->id, $quiz_type, $subtes, $expires_at]);
        } catch (PDOException $e) {
            $stmt = $pdo->prepare("INSERT INTO quiz_attempts (id, student_id, quiz_type, subtes, status) VALUES (?, ?, ?, ?, 'started')");
            $stmt->execute([$attempt_id, $payload->id, $quiz_type, $subtes]);
        }
    }

    // Real Question Injection Taxonomy
    $allowed_classification = strtoupper($quiz_type);
    if ($allowed_classification === 'LATIHAN' || $allowed_classification === 'TRYOUT') {
        $classification_filter = "classification IN ('LATIHAN', 'TRYOUT')";
    } else {
        $classification_filter = "classification = 'ASESMEN'";
    }

    $education_level = trim($input['education_level'] ?? ($_GET['education_level'] ?? ''));
    $assessment_type = trim($input['assessment_type'] ?? ($_GET['assessment_type'] ?? ''));
    if (!empty($education_level)) {
        $classification_filter .= " AND (education_level = " . $pdo->quote($education_level) . " OR education_level IS NULL)";
    }
    if (!empty($assessment_type)) {
        $classification_filter .= " AND (assessment_type = " . $pdo->quote($assessment_type) . " OR assessment_type IS NULL)";
    }

    if ($subtes) {
        $stmt = $pdo->prepare("SELECT id, COALESCE(NULLIF(sub_materi, ''), subtes, subtest, 'Penalaran Umum') AS subtes, COALESCE(NULLIF(sub_materi, ''), subtes, subtest, 'Penalaran Umum') AS sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE (sub_materi = ? OR subtes = ?) AND is_active = 1 AND is_qc_passed = 1 AND $classification_filter");
        $stmt->execute([$subtes, $subtes]);
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($questions) {
            shuffle($questions);
            if (count($questions) > (int)$limit) {
                $questions = array_slice($questions, 0, (int)$limit);
            }
        }
    } else {
        if (strtolower($quiz_type) === 'tryout') {
            $has_pbm = 0;
            try {
                $has_pbm = (int)$pdo->query("SELECT COUNT(*) FROM questions WHERE (sub_materi LIKE '%Bacaan%' OR subtes LIKE '%Bacaan%') AND is_active = 1 AND is_qc_passed = 1 AND $classification_filter")->fetchColumn();
            } catch (\Throwable $e) {}

            if ($has_pbm >= 20) {
                $tryout_distribution = [
                    'Penalaran Umum' => 30,
                    'Pengetahuan Dan Pemahaman Umum' => 20,
                    'Pemahaman Bacaan Dan Menulis' => 20,
                    'Pengetahuan Kuantitatif' => 20,
                    'Literasi Dalam Bahasa Indonesia' => 20,
                    'Literasi Dalam Bahasa Inggris' => 20,
                    'Penalaran Matematika' => 20
                ];
            } else {
                $tryout_distribution = [
                    'Penalaran Umum' => 30,
                    'Pengetahuan Dan Pemahaman Umum' => 20,
                    'Pengetahuan Kuantitatif' => 20,
                    'Literasi Dalam Bahasa Indonesia' => 30,
                    'Literasi Dalam Bahasa Inggris' => 20,
                    'Penalaran Matematika' => 30
                ];
            }
            $questions = [];
            foreach ($tryout_distribution as $sub_name => $sub_limit) {
                $stmt = $pdo->prepare("SELECT id, COALESCE(NULLIF(sub_materi, ''), subtes, subtest, 'Penalaran Umum') AS subtes, COALESCE(NULLIF(sub_materi, ''), subtes, subtest, 'Penalaran Umum') AS sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE (sub_materi LIKE ? OR subtes LIKE ?) AND is_active = 1 AND is_qc_passed = 1 AND $classification_filter");
                $stmt->execute(["%" . $sub_name . "%", "%" . $sub_name . "%"]);
                $fetched = $stmt->fetchAll(PDO::FETCH_ASSOC);
                if ($fetched) {
                    shuffle($fetched);
                    if (count($fetched) > $sub_limit) {
                        $fetched = array_slice($fetched, 0, $sub_limit);
                    }
                    $questions = array_merge($questions, $fetched);
                }
            }
        } else {
            $stmt = $pdo->prepare("SELECT id, COALESCE(NULLIF(sub_materi, ''), subtes, subtest, 'Penalaran Umum') AS subtes, COALESCE(NULLIF(sub_materi, ''), subtes, subtest, 'Penalaran Umum') AS sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE is_active = 1 AND is_qc_passed = 1 AND $classification_filter");
            $stmt->execute();
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            if ($questions) {
                shuffle($questions);
                if (count($questions) > (int)$limit) {
                    $questions = array_slice($questions, 0, (int)$limit);
                }
            }
        }
    }

    // Resilient fallback: If no questions found matching exact filters, get any active questions without ORDER BY RAND
    if (empty($questions)) {
        $stmt = $pdo->query("SELECT id, COALESCE(NULLIF(sub_materi, ''), subtes, subtest, 'Penalaran Umum') AS subtes, COALESCE(NULLIF(sub_materi, ''), subtes, subtest, 'Penalaran Umum') AS sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE is_active = 1");
        $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($questions) {
            shuffle($questions);
            if (count($questions) > (int)$limit) {
                $questions = array_slice($questions, 0, (int)$limit);
            }
        }
    }

    echo json_encode([
        "attempt_id" => $attempt_id,
        "expires_at" => $expires_at,
        "questions" => $questions
    ]);
}
elseif ($action === 'questions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Backward compatibility for existing Web App
    $sub_materi_input = $_GET['sub_materi'] ?? $_GET['subtes'] ?? '';
    $quiz_type = $_GET['quiz_type'] ?? 'latihan';
    verify_quiz_entitlement($pdo, $payload, $quiz_type);
    $limit = $quiz_type === 'tryout' ? 150 : max(1, min(50, (int)($_GET['limit'] ?? 10)));
    
    $allowed_classification = strtoupper($quiz_type);
    if ($allowed_classification === 'LATIHAN' || $allowed_classification === 'TRYOUT') {
        $classification_filter = "classification IN ('LATIHAN', 'TRYOUT')";
    } else {
        $classification_filter = "classification = 'ASESMEN'";
    }

    $education_level = trim($_GET['education_level'] ?? '');
    $assessment_type = trim($_GET['assessment_type'] ?? '');
    if (!empty($education_level)) {
        $classification_filter .= " AND (education_level = " . $pdo->quote($education_level) . " OR education_level IS NULL)";
    }
    if (!empty($assessment_type)) {
        $classification_filter .= " AND (assessment_type = " . $pdo->quote($assessment_type) . " OR assessment_type IS NULL)";
    }

    if ($sub_materi_input) {
        $stmt = $pdo->prepare("SELECT id, sub_materi AS subtes, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE sub_materi = ? AND is_active = 1 AND is_qc_passed = 1 AND $classification_filter");
        $stmt->execute([$sub_materi_input]);
        $questions = $stmt->fetchAll();
        if ($questions) {
            shuffle($questions);
            if (count($questions) > (int)$limit) {
                $questions = array_slice($questions, 0, (int)$limit);
            }
        }
    } else {
        if (strtolower($quiz_type) === 'tryout') {
            $has_pbm = 0;
            try {
                $has_pbm = (int)$pdo->query("SELECT COUNT(*) FROM questions WHERE (sub_materi LIKE '%Bacaan%' OR subtes LIKE '%Bacaan%') AND is_active = 1 AND is_qc_passed = 1 AND $classification_filter")->fetchColumn();
            } catch (\Throwable $e) {}

            if ($has_pbm >= 20) {
                $tryout_distribution = [
                    'Penalaran Umum' => 30,
                    'Pengetahuan Dan Pemahaman Umum' => 20,
                    'Pemahaman Bacaan Dan Menulis' => 20,
                    'Pengetahuan Kuantitatif' => 20,
                    'Literasi Dalam Bahasa Indonesia' => 20,
                    'Literasi Dalam Bahasa Inggris' => 20,
                    'Penalaran Matematika' => 20
                ];
            } else {
                $tryout_distribution = [
                    'Penalaran Umum' => 30,
                    'Pengetahuan Dan Pemahaman Umum' => 20,
                    'Pengetahuan Kuantitatif' => 20,
                    'Literasi Dalam Bahasa Indonesia' => 30,
                    'Literasi Dalam Bahasa Inggris' => 20,
                    'Penalaran Matematika' => 30
                ];
            }
            $questions = [];
            foreach ($tryout_distribution as $sub_name => $sub_limit) {
                $stmt = $pdo->prepare("SELECT id, sub_materi AS subtes, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE sub_materi LIKE ? AND is_active = 1 AND is_qc_passed = 1 AND $classification_filter");
                $stmt->execute(["%" . $sub_name . "%"]);
                $fetched = $stmt->fetchAll();
                if ($fetched) {
                    shuffle($fetched);
                    if (count($fetched) > $sub_limit) {
                        $fetched = array_slice($fetched, 0, $sub_limit);
                    }
                    $questions = array_merge($questions, $fetched);
                }
            }
        } else {
            $stmt = $pdo->prepare("SELECT id, sub_materi AS subtes, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE is_active = 1 AND is_qc_passed = 1 AND $classification_filter");
            $stmt->execute();
            $questions = $stmt->fetchAll();
            if ($questions) {
                shuffle($questions);
                if (count($questions) > (int)$limit) {
                    $questions = array_slice($questions, 0, (int)$limit);
                }
            }
        }
    }
    
    echo json_encode($questions);
}
elseif ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit($pdo, 'quiz_submit', 10, 2)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu sering mengirim jawaban."]);
        exit;
    }

    $raw_answers = $input['answers'] ?? []; 
    $answers = [];
    foreach ($raw_answers as $k => $v) {
        if (is_array($v) && isset($v['question_id'])) {
            $answers[] = $v;
        } else {
            $answers[] = ['question_id' => (string)$k, 'answer' => (string)$v];
        }
    }
    $quiz_type = $input['quiz_type'] ?? 'latihan';
    $subtes = $input['subtes'] ?? null;
    $duration_sec = $input['duration_sec'] ?? 0;
    $attempt_id = trim($input['attempt_id'] ?? '');

    if ($attempt_id) {
        try {
            $lock = $pdo->prepare("SELECT status, expires_at FROM quiz_attempts WHERE id = ? AND student_id = ? FOR UPDATE");
            $lock->execute([$attempt_id, $payload->id]);
            $attempt = $lock->fetch();
            if (!$attempt) {
                http_response_code(404);
                echo json_encode(["error" => "Attempt tidak ditemukan."]);
                exit;
            }
            if ($attempt['status'] === 'completed') {
                $check = $pdo->prepare("SELECT score, correct, total, id as result_id FROM quiz_results WHERE attempt_id = ? AND student_id = ?");
                $check->execute([$attempt_id, $payload->id]);
                $existingResult = $check->fetch();
                if ($existingResult) {
                    echo json_encode(["success" => true, "score" => (float)$existingResult['score'], "correct" => (int)$existingResult['correct'], "total" => (int)$existingResult['total'], "result_id" => $existingResult['result_id']]);
                    exit;
                }
            }
            if (!empty($attempt['expires_at']) && strtotime($attempt['expires_at']) < time()) {
                http_response_code(409);
                echo json_encode(["error" => "Waktu pengerjaan telah habis."]);
                exit;
            }
        } catch (PDOException $e) {
            // Older installations may not have the timer column; migration is additive.
        }
    }

    // Check idempotency if attempt_id is provided
    if ($attempt_id) {
        try {
            // Check if already completed
            $check = $pdo->prepare("SELECT score, correct, total, id as result_id FROM quiz_results WHERE attempt_id = ? AND student_id = ?");
            $check->execute([$attempt_id, $payload->id]);
            $existingResult = $check->fetch();
            if ($existingResult) {
                // Idempotent return
                echo json_encode([
                    "success" => true,
                    "score" => (float)$existingResult['score'],
                    "correct" => (int)$existingResult['correct'],
                    "total" => (int)$existingResult['total'],
                    "result_id" => $existingResult['result_id'],
                    "message" => "Hasil sudah tersimpan (Idempotent response)"
                ]);
                exit;
            }
        } catch (PDOException $e) { /* Migration not run yet, column might not exist */ }
    }

    $correctCount = 0;
    $totalScore = 0;
    $totalQuestions = count($answers);
    $detailedAnswers = [];

    if (empty($answers)) {
        echo json_encode(["success" => true, "score" => 0, "correct" => 0, "total" => 0]);
        exit;
    }

    $qIds = array_column($answers, 'question_id');
    $idList = $qIds;
    $inQuery = implode(',', array_fill(0, count($qIds), '?'));
    // Calculate final scores based on classical properties if available (we keep legacy irt_score field fallback for now if it exists, or just use 1)
    $stmt = $pdo->prepare("SELECT id, correct, irt_score, sub_materi AS subtes, bab FROM questions WHERE id IN ($inQuery)");
    $stmt->execute($idList);
    $questionsDb = $stmt->fetchAll();

    $qMap = [];
    foreach ($questionsDb as $q) {
        $qMap[$q['id']] = $q;
    }

    foreach ($answers as $ans) {
        $qid = $ans['question_id'];
        $user_ans = strtolower($ans['answer']);
        if (isset($qMap[$qid])) {
            $db_q = $qMap[$qid];
            $is_correct = ($user_ans === strtolower($db_q['correct']));
            
            if ($is_correct) {
                $correctCount++;
                $qWeight = ((float)($db_q['irt_score'] ?? 0) > 0) ? (float)$db_q['irt_score'] : 1.0;
                $totalScore += $qWeight;
                
                // Update Progress
                if ($db_q['bab']) {
                    $upd = $pdo->prepare("INSERT INTO progress (id, student_id, subtes, bab, score, mastery) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE score = score + ?, mastery = mastery + 1, last_study = NOW()");
                    $uuid = bin2hex(random_bytes(16));
                    $uuid = substr($uuid,0,8).'-'.substr($uuid,8,4).'-'.substr($uuid,12,4).'-'.substr($uuid,16,4).'-'.substr($uuid,20,12);
                    $upd->execute([$uuid, $payload->id, $db_q['subtes'], $db_q['bab'], $qWeight, $qWeight]);
                }
            }

            $detailedAnswers[] = [
                "question_id" => $qid,
                "user_answer" => $user_ans,
                "is_correct" => $is_correct
            ];
        }
    }

    $id = bin2hex(random_bytes(16));
    $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
    
    try {
        $ins = $pdo->prepare("INSERT INTO quiz_results (id, student_id, attempt_id, quiz_type, subtes, score, correct, total, duration_sec, answers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$id, $payload->id, $attempt_id ? $attempt_id : null, $quiz_type, $subtes, $totalScore, $correctCount, $totalQuestions, $duration_sec, json_encode($detailedAnswers)]);
        
        if ($attempt_id) {
            $upd = $pdo->prepare("UPDATE quiz_attempts SET status = 'completed', completed_at = NOW() WHERE id = ?");
            $upd->execute([$attempt_id]);
        }
    } catch (PDOException $e) {
        // Fallback for Web App backward compatibility if attempt_id column doesn't exist
        $ins = $pdo->prepare("INSERT INTO quiz_results (id, student_id, quiz_type, subtes, score, correct, total, duration_sec, answers) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$id, $payload->id, $quiz_type, $subtes, $totalScore, $correctCount, $totalQuestions, $duration_sec, json_encode($detailedAnswers)]);
    }

    echo json_encode([
        "success" => true,
        "score" => $totalScore,
        "correct" => $correctCount,
        "total" => $totalQuestions,
        "result_id" => $id,
        "message" => "Hasil berhasil disimpan"
    ]);
}
elseif ($action === 'review' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $attempt_id = trim($_GET['attempt_id'] ?? '');
    $result_id = trim($_GET['result_id'] ?? '');

    if (empty($attempt_id) && empty($result_id)) {
        http_response_code(400);
        echo json_encode(["error" => "attempt_id atau result_id wajib disertakan"]);
        exit;
    }

    if ($result_id) {
        $stmt = $pdo->prepare("SELECT * FROM quiz_results WHERE id = ? LIMIT 1");
        $stmt->execute([$result_id]);
    } else {
        $stmt = $pdo->prepare("SELECT * FROM quiz_results WHERE attempt_id = ? LIMIT 1");
        $stmt->execute([$attempt_id]);
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$result) {
        http_response_code(404);
        echo json_encode(["error" => "Hasil kuis tidak ditemukan"]);
        exit;
    }

    if ($payload->role === 'student' && $result['student_id'] !== $payload->id) {
        http_response_code(403);
        echo json_encode(["error" => "Akses review kuis tidak diizinkan"]);
        exit;
    }

    $detailedAnswers = json_decode($result['answers'] ?? '[]', true) ?: [];
    $qIds = array_column($detailedAnswers, 'question_id');

    $questionsData = [];
    if (!empty($qIds)) {
        $inQuery = implode(',', array_fill(0, count($qIds), '?'));
        $stmt_q = $pdo->prepare("
            SELECT id, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, trick, sub_materi as subtes, difficulty, irt_score 
            FROM questions 
            WHERE id IN ($inQuery)
        ");
        $stmt_q->execute($qIds);
        $questionsList = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

        $qMap = [];
        foreach ($questionsList as $q) {
            $qMap[$q['id']] = $q;
        }

        foreach ($detailedAnswers as $ans) {
            $qid = $ans['question_id'];
            if (isset($qMap[$qid])) {
                $qItem = $qMap[$qid];
                $qItem['user_answer'] = $ans['user_answer'] ?? '';
                $qItem['is_correct'] = (bool)($ans['is_correct'] ?? false);
                $questionsData[] = $qItem;
            }
        }
    }

    echo json_encode([
        "success" => true,
        "score" => (float)$result['score'],
        "correct" => (int)$result['correct'],
        "total" => (int)$result['total'],
        "quiz_type" => $result['quiz_type'],
        "subtes" => $result['subtes'],
        "questions" => $questionsData
    ]);
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint quiz tidak ditemukan"]);
}
