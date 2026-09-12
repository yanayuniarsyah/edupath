<?php
// api/v1/assessments.php
require_once '../config.php';
require_once '../jwt.php';
require_once '../EntitlementService.php';

// Enable error reporting for debug (disable in prod)
error_reporting(E_ALL);
ini_set('display_errors', 1);

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$path_parts = explode('/', trim($path, '/'));

// Expected paths:
// GET  /api/v1/assessments/{slug}
// POST /api/v1/assessments/{id}/attempts
// GET  /api/v1/attempts/{id}
// POST /api/v1/attempts/{id}/responses
// POST /api/v1/attempts/{id}/complete
// GET  /api/v1/attempts/{id}/result

$payload = authenticate();
if ($payload->role !== 'student') {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden. Hanya student yang bisa mengakses endpoint ini."]);
    exit;
}

$student_id = $payload->id;

// Router
if ($method === 'GET' && count($path_parts) >= 4 && $path_parts[2] === 'assessments' && !empty($path_parts[3]) && count($path_parts) == 4) {
    // GET /api/v1/assessments/{slug}
    $slug = $path_parts[3];
    getAssessment($pdo, $slug, $student_id);
} 
elseif ($method === 'POST' && count($path_parts) == 5 && $path_parts[2] === 'assessments' && $path_parts[4] === 'attempts') {
    // POST /api/v1/assessments/{id}/attempts
    $assessment_id = $path_parts[3];
    startAttempt($pdo, $assessment_id, $student_id, $payload->tenant_id);
}
elseif ($method === 'GET' && count($path_parts) == 4 && $path_parts[2] === 'attempts') {
    // GET /api/v1/attempts/{id}
    $attempt_id = $path_parts[3];
    getAttemptStatus($pdo, $attempt_id, $student_id);
}
elseif ($method === 'POST' && count($path_parts) == 5 && $path_parts[2] === 'attempts' && $path_parts[4] === 'responses') {
    // POST /api/v1/attempts/{id}/responses
    $attempt_id = $path_parts[3];
    saveResponse($pdo, $attempt_id, $student_id);
}
elseif ($method === 'POST' && count($path_parts) == 5 && $path_parts[2] === 'attempts' && $path_parts[4] === 'complete') {
    // POST /api/v1/attempts/{id}/complete
    $attempt_id = $path_parts[3];
    completeAttempt($pdo, $attempt_id, $student_id);
}
elseif ($method === 'GET' && count($path_parts) == 5 && $path_parts[2] === 'attempts' && $path_parts[4] === 'result') {
    // GET /api/v1/attempts/{id}/result
    $attempt_id = $path_parts[3];
    getAttemptResult($pdo, $attempt_id, $student_id);
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint tidak ditemukan atau method tidak didukung."]);
}

// ---------------------------------------------------------
// CONTROLLERS
// ---------------------------------------------------------

function getAssessment($pdo, $slug, $student_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, title, type, is_premium, status FROM assessments WHERE slug = ?");
        $stmt->execute([$slug]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assessment) {
            http_response_code(404);
            echo json_encode(["error" => "Assessment tidak ditemukan."]);
            return;
        }
        
        if ($assessment['status'] !== 'active') {
            http_response_code(400);
            echo json_encode(["error" => "Assessment tidak aktif."]);
            return;
        }

        // Get Published Version
        $stmt_v = $pdo->prepare("SELECT id, version, duration_sec, config, published_at FROM assessment_versions WHERE assessment_id = ? AND status = 'published' ORDER BY version DESC LIMIT 1");
        $stmt_v->execute([$assessment['id']]);
        $version = $stmt_v->fetch(PDO::FETCH_ASSOC);

        if (!$version) {
            http_response_code(404);
            echo json_encode(["error" => "Versi published tidak ditemukan untuk assessment ini."]);
            return;
        }

        // Get Questions for this version (Without Answer Key)
        $stmt_q = $pdo->prepare("
            SELECT aq.id as assessment_question_id, aq.seq_order, aq.weight_override, qr.content, qr.options, qr.default_weight, qc.type as question_type
            FROM assessment_questions aq
            JOIN question_revisions qr ON aq.question_revision_id = qr.id
            JOIN questions_canonical qc ON qr.question_id = qc.id
            WHERE aq.assessment_version_id = ?
            ORDER BY aq.seq_order ASC
        ");
        $stmt_q->execute([$version['id']]);
        $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);
        
        // Decode JSON safely
        foreach ($questions as &$q) {
            $q['options'] = json_decode($q['options'], true);
            // Explicitly DO NOT return answer_key
        }

        echo json_encode([
            "success" => true,
            "assessment" => $assessment,
            "version" => [
                "id" => $version['id'],
                "version" => $version['version'],
                "duration_sec" => $version['duration_sec'],
                // config might contain display settings, but no scoring keys
                "config" => json_decode($version['config'], true) 
            ],
            "questions" => $questions
        ]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error.", "details" => $e->getMessage()]);
    }
}

function startAttempt($pdo, $assessment_id, $student_id, $tenant_id) {
    try {
        $pdo->beginTransaction();

        // 1. Check Assessment
        $stmt = $pdo->prepare("SELECT type, is_premium, status FROM assessments WHERE id = ?");
        $stmt->execute([$assessment_id]);
        $assessment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$assessment || $assessment['status'] !== 'active') {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(["error" => "Assessment tidak ditemukan atau tidak aktif."]);
            return;
        }

        // 2. Check Premium Entitlement
        if ((int)$assessment['is_premium'] === 1) {
            $entitlementService = new EntitlementService($pdo);
            if (!$entitlementService->hasEntitlement($student_id, $tenant_id, 'premium_assessments')) {
                $pdo->rollBack();
                http_response_code(403);
                echo json_encode(["error" => "Anda tidak memiliki akses ke assessment premium ini."]);
                return;
            }
        }

        // 3. Get Published Version
        $stmt_v = $pdo->prepare("SELECT id, duration_sec FROM assessment_versions WHERE assessment_id = ? AND status = 'published' ORDER BY version DESC LIMIT 1");
        $stmt_v->execute([$assessment_id]);
        $version = $stmt_v->fetch(PDO::FETCH_ASSOC);

        if (!$version) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(["error" => "Tidak ada versi published untuk assessment ini."]);
            return;
        }
        
        $version_id = $version['id'];
        $duration_sec = (int)$version['duration_sec'];

        // 4. Pessimistic Lock on existing attempt
        $stmt_att = $pdo->prepare("SELECT id, expires_at FROM attempts WHERE student_id = ? AND assessment_version_id = ? AND status = 'started' FOR UPDATE");
        $stmt_att->execute([$student_id, $version_id]);
        $existing_attempt = $stmt_att->fetch(PDO::FETCH_ASSOC);

        if ($existing_attempt) {
            // Check if expired
            if (strtotime($existing_attempt['expires_at']) < time()) {
                // Auto-complete or auto-abandon? Just return error for now to let completeEndpoint handle it
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(["error" => "Sesi sebelumnya telah habis waktu. Harap submit sesi tersebut terlebih dahulu.", "attempt_id" => $existing_attempt['id']]);
                return;
            }
            
            // Idempotent: return existing attempt
            $pdo->commit();
            echo json_encode([
                "success" => true,
                "message" => "Melanjutkan attempt yang ada.",
                "attempt_id" => $existing_attempt['id'],
                "expires_at" => $existing_attempt['expires_at']
            ]);
            return;
        }

        // 5. Create new attempt
        $new_attempt_id = bin2hex(random_bytes(16));
        $new_attempt_id = substr($new_attempt_id,0,8).'-'.substr($new_attempt_id,8,4).'-'.substr($new_attempt_id,12,4).'-'.substr($new_attempt_id,16,4).'-'.substr($new_attempt_id,20,12);
        
        $expires_at = date('Y-m-d H:i:s', time() + $duration_sec);

        $ins = $pdo->prepare("INSERT INTO attempts (id, student_id, assessment_version_id, status, started_at, expires_at) VALUES (?, ?, ?, 'started', NOW(), ?)");
        $ins->execute([$new_attempt_id, $student_id, $version_id, $expires_at]);

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Attempt baru berhasil dibuat.",
            "attempt_id" => $new_attempt_id,
            "expires_at" => $expires_at
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Database error.", "details" => $e->getMessage()]);
    }
}

function getAttemptStatus($pdo, $attempt_id, $student_id) {
    try {
        $stmt = $pdo->prepare("SELECT id, assessment_version_id, status, started_at, expires_at, completed_at FROM attempts WHERE id = ? AND student_id = ?");
        $stmt->execute([$attempt_id, $student_id]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            http_response_code(404);
            echo json_encode(["error" => "Attempt tidak ditemukan atau bukan milik Anda."]);
            return;
        }

        echo json_encode(["success" => true, "attempt" => $attempt]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error."]);
    }
}

function saveResponse($pdo, $attempt_id, $student_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $aq_id = $input['assessment_question_id'] ?? null;
    $response_val = $input['response_value'] ?? null;

    if (!$aq_id || !$response_val) {
        http_response_code(400);
        echo json_encode(["error" => "assessment_question_id dan response_value wajib dikirim."]);
        return;
    }

    try {
        // Validate attempt ownership and status
        $stmt = $pdo->prepare("SELECT status, expires_at, assessment_version_id FROM attempts WHERE id = ? AND student_id = ?");
        $stmt->execute([$attempt_id, $student_id]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            http_response_code(404);
            echo json_encode(["error" => "Attempt tidak ditemukan."]);
            return;
        }

        if ($attempt['status'] !== 'started') {
            http_response_code(400);
            echo json_encode(["error" => "Attempt sudah tidak aktif (status: {$attempt['status']})."]);
            return;
        }

        if (strtotime($attempt['expires_at']) < time()) {
            http_response_code(400);
            echo json_encode(["error" => "Waktu pengerjaan telah habis."]);
            return;
        }

        // Validate that assessment_question belongs to this version
        $stmt_aq = $pdo->prepare("SELECT id FROM assessment_questions WHERE id = ? AND assessment_version_id = ?");
        $stmt_aq->execute([$aq_id, $attempt['assessment_version_id']]);
        if (!$stmt_aq->fetch()) {
            http_response_code(400);
            echo json_encode(["error" => "assessment_question_id tidak valid untuk versi assessment ini."]);
            return;
        }

        // Upsert response (IDEMPOTENCY)
        $resp_id = bin2hex(random_bytes(16));
        $resp_id = substr($resp_id,0,8).'-'.substr($resp_id,8,4).'-'.substr($resp_id,12,4).'-'.substr($resp_id,16,4).'-'.substr($resp_id,20,12);

        $upsert = $pdo->prepare("
            INSERT INTO attempt_responses (id, attempt_id, assessment_question_id, response_value, answered_at) 
            VALUES (?, ?, ?, ?, NOW())
            ON DUPLICATE KEY UPDATE response_value = VALUES(response_value), answered_at = NOW()
        ");
        $upsert->execute([$resp_id, $attempt_id, $aq_id, $response_val]);

        echo json_encode(["success" => true, "message" => "Jawaban berhasil disimpan."]);

    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error.", "details" => $e->getMessage()]);
    }
}

function completeAttempt($pdo, $attempt_id, $student_id) {
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("SELECT status, assessment_version_id FROM attempts WHERE id = ? AND student_id = ? FOR UPDATE");
        $stmt->execute([$attempt_id, $student_id]);
        $attempt = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$attempt) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(["error" => "Attempt tidak ditemukan."]);
            return;
        }

        // Idempotency check
        if ($attempt['status'] === 'completed') {
            $stmt_res = $pdo->prepare("SELECT total_score, profile_metadata FROM attempt_results WHERE attempt_id = ?");
            $stmt_res->execute([$attempt_id]);
            $result = $stmt_res->fetch(PDO::FETCH_ASSOC);
            $pdo->commit();
            
            echo json_encode([
                "success" => true,
                "message" => "Attempt sudah dikomplitasi (Idempotent response).",
                "result" => [
                    "total_score" => (float)$result['total_score'],
                    "profile_metadata" => json_decode($result['profile_metadata'], true)
                ]
            ]);
            return;
        }

        // Load frozen snapshot
        $stmt_v = $pdo->prepare("SELECT config FROM assessment_versions WHERE id = ?");
        $stmt_v->execute([$attempt['assessment_version_id']]);
        $version = $stmt_v->fetch(PDO::FETCH_ASSOC);
        $config = json_decode($version['config'], true);

        // Load Questions and Answer Keys
        $stmt_q = $pdo->prepare("
            SELECT aq.id as assessment_question_id, aq.weight_override, qr.default_weight, qr.answer_key
            FROM assessment_questions aq
            JOIN question_revisions qr ON aq.question_revision_id = qr.id
            WHERE aq.assessment_version_id = ?
        ");
        $stmt_q->execute([$attempt['assessment_version_id']]);
        $questions = $stmt_q->fetchAll(PDO::FETCH_ASSOC);

        // Load Responses
        $stmt_r = $pdo->prepare("SELECT assessment_question_id, response_value FROM attempt_responses WHERE attempt_id = ?");
        $stmt_r->execute([$attempt_id]);
        $rawResponses = $stmt_r->fetchAll(PDO::FETCH_ASSOC);
        $responses = [];
        foreach ($rawResponses as $r) {
            $responses[$r['assessment_question_id']] = $r['response_value'];
        }

        // Run Scoring Engine Factory
        require_once 'scoring/ScoringEngineFactory.php';
        
        $attemptData = [
            'config' => $config,
            'questions' => $questions,
            'responses' => $responses
        ];

        try {
            $scoringEngine = ScoringEngineFactory::make($config);
            $scoringResult = $scoringEngine->calculate($attemptData);
        } catch (Exception $engineEx) {
            $pdo->rollBack();
            $msg = $engineEx->getMessage();
            http_response_code(500);
            echo json_encode(["error" => "Engine error: $msg"]);
            return;
        }
        
        // 1. Mark completed
        $upd = $pdo->prepare("UPDATE attempts SET status = 'completed', completed_at = NOW() WHERE id = ?");
        $upd->execute([$attempt_id]);

        // 2. Update Responses with is_correct and score_earned
        $upd_resp = $pdo->prepare("UPDATE attempt_responses SET is_correct = ?, score_earned = ? WHERE attempt_id = ? AND assessment_question_id = ?");
        foreach ($scoringResult->question_scores as $aqId => $scores) {
            // is_correct could be boolean or null, PDO handles boolean correctly but we can explicitly pass int
            $isCorrectInt = $scores['is_correct'] === null ? null : (int)$scores['is_correct'];
            $upd_resp->execute([$isCorrectInt, $scores['score_earned'], $attempt_id, $aqId]);
        }

        // 3. Insert Result
        $res_id = bin2hex(random_bytes(16));
        $res_id = substr($res_id,0,8).'-'.substr($res_id,8,4).'-'.substr($res_id,12,4).'-'.substr($res_id,16,4).'-'.substr($res_id,20,12);
        
        $profileMetaJson = $scoringResult->profile_metadata !== null ? json_encode($scoringResult->profile_metadata) : null;
        
        $ins = $pdo->prepare("INSERT INTO attempt_results (id, attempt_id, total_score, profile_metadata) VALUES (?, ?, ?, ?)");
        $ins->execute([$res_id, $attempt_id, $scoringResult->total_score, $profileMetaJson]);

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Attempt berhasil dikomplitasi.",
            "result" => [
                "total_score" => $scoringResult->total_score,
                "profile_metadata" => $scoringResult->profile_metadata
            ]
        ]);

    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Database error.", "details" => $e->getMessage()]);
    }
}

function getAttemptResult($pdo, $attempt_id, $student_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT ar.total_score, ar.profile_metadata, a.status 
            FROM attempts a 
            LEFT JOIN attempt_results ar ON a.id = ar.attempt_id 
            WHERE a.id = ? AND a.student_id = ?
        ");
        $stmt->execute([$attempt_id, $student_id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            http_response_code(404);
            echo json_encode(["error" => "Attempt tidak ditemukan."]);
            return;
        }

        if ($row['status'] !== 'completed') {
            http_response_code(400);
            echo json_encode(["error" => "Attempt belum dikomplitasi."]);
            return;
        }

        echo json_encode([
            "success" => true,
            "result" => [
                "total_score" => (float)$row['total_score'],
                "profile_metadata" => json_decode($row['profile_metadata'], true)
            ]
        ]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Database error.", "details" => $e->getMessage()]);
    }
}
?>
