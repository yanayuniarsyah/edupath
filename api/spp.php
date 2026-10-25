<?php
// api/spp.php
require_once 'config.php';
require_once 'jwt.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

if (($action === 'data' || $action === 'questions') && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // This endpoint can be accessed without auth to allow pre-login SPP assessment, 
    // or with auth. We don't enforce authentication here.
    
    try {
        $data = [
            "universities" => [],
            "programs" => [],
            "diagnostic_questions" => []
        ];

        // 1. Fetch Target Universities
        $stmt_univ = $pdo->query("SELECT id, name FROM target_universities ORDER BY name ASC");
        if ($stmt_univ) {
            $data["universities"] = $stmt_univ->fetchAll();
        }

        // 2. Fetch Target Programs
        $stmt_prog = $pdo->query("SELECT id, university_id, name, target_score, req_ability FROM target_programs ORDER BY name ASC");
        if ($stmt_prog) {
            $programs = $stmt_prog->fetchAll();
            foreach ($programs as &$prog) {
                // Decode JSON req_ability back to object/array
                $prog['req_ability'] = json_decode($prog['req_ability'], true);
            }
            $data["programs"] = $programs;
        }

        // 3. Fetch SPP Diagnostic Questions (EXCLUDING answer key and explanation)
        // Ensure we NEVER expose 'answer', 'hint', 'concept', or 'explanation'
        $stmt_q = $pdo->query("SELECT id, subject, category, skill, difficulty, question, options FROM spp_diagnostic_questions ORDER BY id LIMIT 20");
        if ($stmt_q) {
            $questions = $stmt_q->fetchAll();
            foreach ($questions as &$q) {
                // Decode JSON options
                $q['options'] = json_decode($q['options'], true);
            }
            $data["diagnostic_questions"] = $questions;
        }

        echo json_encode($data);
    } catch (PDOException $e) {
        http_response_code(503);
        echo json_encode(["error" => "Data adaptive learning belum tersedia."]);
    }
}
else if ($action === 'create_attempt' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'spp_service.php';
    $payload = authenticate();
    if ($payload->role !== 'student') { http_response_code(403); echo json_encode(["error" => "Forbidden"]); exit; }
    
    $service = new SPPService($pdo);
    echo json_encode($service->createAttempt($payload->id));
}
else if ($action === 'submit_attempt' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    require_once 'spp_service.php';
    $payload = authenticate();
    if ($payload->role !== 'student') { http_response_code(403); echo json_encode(["error" => "Forbidden"]); exit; }
    
    $attempt_id = $_GET['id'] ?? ($input['attempt_id'] ?? ($input['id'] ?? ''));
    if (!$attempt_id) { http_response_code(400); echo json_encode(["error" => "Attempt ID required"]); exit; }
    
    $duration = $input['duration_seconds'] ?? ($input['duration'] ?? 0);
    $responses = $input['responses'] ?? ($input['answers'] ?? []);
    
    $service = new SPPService($pdo);
    $result = $service->submitAttempt($payload->id, $attempt_id, $responses, $duration);
    echo json_encode($result);
}
else if ($action === 'get_result' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once 'spp_service.php';
    $payload = authenticate();
    if ($payload->role !== 'student') { http_response_code(403); echo json_encode(["error" => "Forbidden"]); exit; }
    
    $attempt_id = $_GET['id'] ?? ($input['attempt_id'] ?? ($input['id'] ?? ''));
    if (!$attempt_id) { http_response_code(400); echo json_encode(["error" => "Attempt ID required"]); exit; }
    
    $service = new SPPService($pdo);
    $result = $service->getResult($attempt_id, $payload->id);
    if (!$result) { http_response_code(404); echo json_encode(["error" => "Result not found"]); exit; }
    
    echo json_encode($result);
}
else if ($action === 'get_school_aggregate' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once 'spp_service.php';
    $payload = authenticate();
    // Authority check: must be admin or superadmin
    if (!in_array($payload->role, ['admin', 'superadmin'])) {
        http_response_code(403); echo json_encode(["error" => "Forbidden"]); exit;
    }
    
    // If superadmin, tenant_id is NULL. If admin, they are locked to their tenant.
    $tenant_id = $payload->tenant_id;
    
    // School name is now an optional sub-filter, mostly for superadmin or B2C compatibility
    $school_name = $_GET['school_name'] ?? null;
    
    // Prevent empty queries that return everything
    if (!$tenant_id && !$school_name) {
        http_response_code(400); echo json_encode(["error" => "Missing tenant or school context"]); exit;
    }
    
    $service = new SPPService($pdo);
    echo json_encode($service->getSchoolAggregate($school_name, $tenant_id));
}
else if ($action === 'get_certificate' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    require_once 'spp_service.php';
    $payload = authenticate();
    
    $attempt_id = $_GET['attempt_id'] ?? ($_GET['id'] ?? '');
    if (!$attempt_id) {
        http_response_code(400);
        echo json_encode(["status" => "ERROR", "message" => "Parameter attempt_id wajib disertakan"]);
        exit;
    }
    
    $service = new SPPService($pdo);
    
    // Jika student, hanya boleh ambil miliknya sendiri
    $student_id = $payload->id;
    if (in_array($payload->role, ['admin', 'superadmin', 'school_bk'])) {
        // Admin/BK bisa meminta sertifikat siswa tertentu jika student_id disertakan
        $student_id = $_GET['student_id'] ?? $payload->id;
        // Jika tidak diberikan student_id, ambil dari attempt record
        if (!$student_id || $student_id === $payload->id) {
            $stmtAtt = $pdo->prepare("SELECT student_id FROM spp_attempts WHERE id = ?");
            $stmtAtt->execute([$attempt_id]);
            $student_id = $stmtAtt->fetchColumn();
        }
    }
    
    if (!$student_id) {
        http_response_code(400);
        echo json_encode(["status" => "ERROR", "message" => "Identitas siswa tidak ditemukan"]);
        exit;
    }
    
    $result = $service->getOrCreateCertificate($attempt_id, $student_id);
    if (isset($result['status']) && $result['status'] === 'ERROR') {
        http_response_code(404);
    }
    echo json_encode($result);
}
else if ($action === 'verify_certificate' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // Public endpoint: tidak memerlukan autentikasi JWT agar bisa diakses scan QR
    require_once 'spp_service.php';
    
    $identifier = $_GET['no'] ?? ($_GET['number'] ?? ($_GET['hash'] ?? ($_GET['id'] ?? '')));
    if (!$identifier) {
        http_response_code(400);
        echo json_encode(["valid" => false, "message" => "Nomor sertifikat atau verification hash wajib disertakan"]);
        exit;
    }
    
    $service = new SPPService($pdo);
    $result = $service->verifyCertificate($identifier);
    
    if (!$result['valid']) {
        http_response_code(404);
    }
    echo json_encode($result);
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint tidak ditemukan"]);
}

