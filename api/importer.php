<?php
require_once 'config.php';
require_once 'jwt.php';

header('Content-Type: application/json');

$payload = authenticate($pdo);
if (!$payload || $payload->role !== 'superadmin') {
    http_response_code(403);
    echo json_encode(["error" => "System admin only"]);
    exit;
}

$action = $_GET['action'] ?? '';

if ($action === 'template') {
    // Return CSV Template
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="template_impor_soal.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, ['sub_materi', 'classification', 'cognitive_demand', 'difficulty', 'question', 'option_a', 'option_b', 'option_c', 'option_d', 'option_e', 'correct', 'explanation', 'source_type', 'rights_status', 'source_name', 'source_year', 'source_reference']);
    fputcsv($out, ['Penalaran Umum', 'LATIHAN', 'C3', 'medium', 'Berapa 1+1?', '1', '2', '3', '4', '5', 'B', 'Karena 1+1=2', 'author_created', 'unknown', '', '', '']);
    fclose($out);
    exit;
}

if ($action === 'upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    
    if (!is_array($data) || empty($data)) {
        http_response_code(400);
        echo json_encode(["error" => "Data tidak valid atau kosong"]);
        exit;
    }

    $batch_id = bin2hex(random_bytes(8));
    $tenant_id = $payload->tenant_id ?? null;

    $success_count = 0;
    $error_count = 0;
    
    $stmt = $pdo->prepare("INSERT INTO question_imports_staging (id, batch_id, tenant_id, sub_materi, classification, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, cognitive_demand, source_type, rights_status, source_name, source_year, source_reference, status, error_message) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    foreach ($data as $row) {
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $sub_materi = $row['sub_materi'] ?? '';
        $classification = strtoupper($row['classification'] ?? 'LATIHAN');
        $cognitive_demand = $row['cognitive_demand'] ?? null;
        $difficulty = $row['difficulty'] ?? 'medium';
        $question = $row['question'] ?? '';
        $option_a = $row['option_a'] ?? '';
        $option_b = $row['option_b'] ?? '';
        $option_c = $row['option_c'] ?? '';
        $option_d = $row['option_d'] ?? '';
        $option_e = $row['option_e'] ?? null;
        $correct = strtoupper($row['correct'] ?? '');
        $explanation = $row['explanation'] ?? null;
        $source_type = $row['source_type'] ?? 'author_created';
        $rights_status = $row['rights_status'] ?? 'unknown';
        $source_name = $row['source_name'] ?? null;
        $source_year = !empty($row['source_year']) ? (int)$row['source_year'] : null;
        $source_reference = $row['source_reference'] ?? null;

        $status = 'pending';
        $error_message = '';

        if (empty($question) || empty($option_a) || empty($option_b) || empty($correct) || empty($sub_materi)) {
            $status = 'error';
            $error_message = 'Data wajib (sub_materi, soal, opsi A/B, kunci) kosong';
        } elseif (!in_array($correct, ['A', 'B', 'C', 'D', 'E'])) {
            $status = 'error';
            $error_message = 'Kunci jawaban harus A/B/C/D/E';
        } elseif (!in_array($classification, ['LATIHAN', 'TRYOUT', 'ASESMEN'])) {
            $status = 'error';
            $error_message = 'Classification harus LATIHAN, TRYOUT, atau ASESMEN';
        }

        $stmt->execute([
            $id, $batch_id, $tenant_id, $sub_materi, $classification, $difficulty, $question, $option_a, $option_b, $option_c, $option_d, $option_e, $correct, $explanation, $cognitive_demand, $source_type, $rights_status, $source_name, $source_year, $source_reference, $status, $error_message
        ]);

        if ($status === 'error') {
            $error_count++;
        } else {
            $success_count++;
        }
    }

    echo json_encode(["success" => true, "batch_id" => $batch_id, "success_count" => $success_count, "error_count" => $error_count]);
}
elseif ($action === 'preview' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $batch_id = $_GET['batch_id'] ?? '';
    $stmt = $pdo->prepare("SELECT * FROM question_imports_staging WHERE batch_id = ?");
    $stmt->execute([$batch_id]);
    echo json_encode($stmt->fetchAll());
}
elseif ($action === 'commit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $batch_id = json_decode(file_get_contents('php://input'), true)['batch_id'] ?? '';
    if (!$batch_id) {
        http_response_code(400);
        echo json_encode(["error" => "Batch ID required"]);
        exit;
    }

    // GAP 3: Backend Commit Rejection Security Boundary
    $errStmt = $pdo->prepare("SELECT COUNT(*) FROM question_imports_staging WHERE batch_id = ? AND status = 'error'");
    $errStmt->execute([$batch_id]);
    $errorCount = (int)$errStmt->fetchColumn();

    if ($errorCount > 0) {
        http_response_code(400);
        echo json_encode(["error" => "Batch ini memiliki data invalid ($errorCount baris). Commit ditolak oleh backend. Harap perbaiki seluruh error di file Excel/CSV lalu upload ulang."]);
        exit;
    }

    $stmt = $pdo->prepare("SELECT * FROM question_imports_staging WHERE batch_id = ? AND status = 'pending'");
    $stmt->execute([$batch_id]);
    $rows = $stmt->fetchAll();

    if (count($rows) === 0) {
        http_response_code(400);
        echo json_encode(["error" => "Tidak ada data valid yang bisa di-commit"]);
        exit;
    }

    try {
        $pdo->beginTransaction();
        
        $insertStmt = $pdo->prepare("INSERT INTO questions (id, sub_materi, classification, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, cognitive_demand, source_type, rights_status, source_name, source_year, source_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($rows as $row) {
            $insertStmt->execute([
                $row['id'], $row['sub_materi'], $row['classification'], $row['difficulty'], $row['question'], $row['option_a'], $row['option_b'], $row['option_c'], $row['option_d'], $row['option_e'], $row['correct'], $row['explanation'], $row['cognitive_demand'], $row['source_type'], $row['rights_status'], $row['source_name'], $row['source_year'], $row['source_reference']
            ]);
        }
        
        $pdo->prepare("DELETE FROM question_imports_staging WHERE batch_id = ?")->execute([$batch_id]);
        $pdo->commit();
        
        echo json_encode(["success" => true, "inserted" => count($rows)]);
    } catch (Exception $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Commit failed: Transaction Rolled Back. Reason: " . $e->getMessage()]);
    }
}
