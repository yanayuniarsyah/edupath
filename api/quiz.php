<?php
// api/quiz.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

$payload = authenticate();
if ($payload->role !== 'student') {
    http_response_code(403);
    echo json_encode(["error" => "Hanya siswa yang dapat mengakses kuis"]);
    exit;
}

if ($action === 'start' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit($pdo, 'quiz_start', 10, 5)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak permintaan mulai kuis."]);
        exit;
    }

    $subtes = $input['subtes'] ?? null;
    $limit = $input['limit'] ?? 10;
    $quiz_type = $input['quiz_type'] ?? 'latihan';

    $attempt_id = bin2hex(random_bytes(16));
    $attempt_id = substr($attempt_id,0,8).'-'.substr($attempt_id,8,4).'-'.substr($attempt_id,12,4).'-'.substr($attempt_id,16,4).'-'.substr($attempt_id,20,12);

    try {
        $stmt = $pdo->prepare("INSERT INTO quiz_attempts (id, student_id, quiz_type, subtes, status) VALUES (?, ?, ?, ?, 'started')");
        $stmt->execute([$attempt_id, $payload->id, $quiz_type, $subtes]);
    } catch (PDOException $e) { /* Ignore if migration not run */ }

    // Real Question Injection Taxonomy
    $allowed_classification = strtoupper($quiz_type);
    if ($allowed_classification === 'LATIHAN' || $allowed_classification === 'TRYOUT') {
        $classification_filter = "classification IN ('LATIHAN', 'TRYOUT')";
    } else {
        $classification_filter = "classification = 'ASESMEN'";
    }

    if ($subtes) {
        $stmt = $pdo->prepare("SELECT id, sub_materi AS subtes, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE sub_materi = ? AND is_active = 1 AND $classification_filter ORDER BY RAND() LIMIT ?");
        $stmt->bindValue(1, $subtes);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    } else {
        $stmt = $pdo->prepare("SELECT id, sub_materi AS subtes, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE is_active = 1 AND $classification_filter ORDER BY RAND() LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    $questions = $stmt->fetchAll();

    echo json_encode([
        "attempt_id" => $attempt_id,
        "questions" => $questions
    ]);
}
elseif ($action === 'questions' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Backward compatibility for existing Web App
    $sub_materi_input = $_GET['sub_materi'] ?? $_GET['subtes'] ?? '';
    $limit = $_GET['limit'] ?? 10;
    $quiz_type = $_GET['quiz_type'] ?? 'latihan';
    
    $allowed_classification = strtoupper($quiz_type);
    if ($allowed_classification === 'LATIHAN' || $allowed_classification === 'TRYOUT') {
        $classification_filter = "classification IN ('LATIHAN', 'TRYOUT')";
    } else {
        $classification_filter = "classification = 'ASESMEN'";
    }

    if ($sub_materi_input) {
        $stmt = $pdo->prepare("SELECT id, sub_materi AS subtes, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE sub_materi = ? AND is_active = 1 AND $classification_filter ORDER BY RAND() LIMIT ?");
        $stmt->bindValue(1, $sub_materi_input);
        $stmt->bindValue(2, (int)$limit, PDO::PARAM_INT);
    } else {
        $stmt = $pdo->prepare("SELECT id, sub_materi AS subtes, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE is_active = 1 AND $classification_filter ORDER BY RAND() LIMIT ?");
        $stmt->bindValue(1, (int)$limit, PDO::PARAM_INT);
    }
    $stmt->execute();
    
    echo json_encode($stmt->fetchAll());
}
elseif ($action === 'submit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit($pdo, 'quiz_submit', 10, 2)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu sering mengirim jawaban."]);
        exit;
    }

    $answers = $input['answers'] ?? []; 
    $quiz_type = $input['quiz_type'] ?? 'latihan';
    $subtes = $input['subtes'] ?? null;
    $duration_sec = $input['duration_sec'] ?? 0;
    $attempt_id = trim($input['attempt_id'] ?? '');

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
    $stmt = $pdo->prepare("SELECT id, correct, irt_score, p_value, sub_materi AS subtes, bab FROM questions WHERE id IN ($inQuery)");
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
                $totalScore += (float)$db_q['irt_score'];
                
                // Update Progress
                if ($db_q['bab']) {
                    $upd = $pdo->prepare("INSERT INTO progress (id, student_id, subtes, bab, score, mastery) VALUES (?, ?, ?, ?, ?, 1) ON DUPLICATE KEY UPDATE score = score + ?, mastery = mastery + 1, last_study = NOW()");
                    $uuid = bin2hex(random_bytes(16));
                    $uuid = substr($uuid,0,8).'-'.substr($uuid,8,4).'-'.substr($uuid,12,4).'-'.substr($uuid,16,4).'-'.substr($uuid,20,12);
                    $upd->execute([$uuid, $payload->id, $db_q['subtes'], $db_q['bab'], $db_q['irt_score'], $db_q['irt_score']]);
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
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint quiz tidak ditemukan"]);
}
?>
