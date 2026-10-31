<?php
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Akses ditolak. Script hanya dapat dijalankan melalui CLI terminal.");
}

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
require 'api/config.php';

$transcriptPath = __DIR__ . '/tryout_data.txt';
if (!file_exists($transcriptPath)) {
    die("File tryout_data.txt tidak ditemukan.\n");
}
$lines = file($transcriptPath);

try {
    // 1. Pastikan kolom pendukung ada di database
    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN is_qc_passed TINYINT(1) DEFAULT 1");
    } catch (\Throwable $e) {}

    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN classification VARCHAR(50) DEFAULT 'LATIHAN'");
    } catch (\Throwable $e) {}

    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN source_name VARCHAR(255) NULL");
    } catch (\Throwable $e) {}

    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN source_year INT NULL");
    } catch (\Throwable $e) {}

    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN source_reference TEXT NULL");
    } catch (\Throwable $e) {}

    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN cognitive_demand VARCHAR(50) DEFAULT 'C4'");
    } catch (\Throwable $e) {}

    // Bersihkan data tryout parsial/rusak sebelumnya
    $pdo->exec("DELETE FROM questions WHERE classification = 'TRYOUT' OR source_name = 'EduPath UTBK Standar SNBT'");

    $validSections = [
        'PENALARAN UMUM' => 'Penalaran Umum',
        'PENGETAHUAN DAN PEMAHAMAN UMUM' => 'Pengetahuan Dan Pemahaman Umum',
        'PEMAHAMAN BACAAN DAN MENULIS' => 'Pemahaman Bacaan Dan Menulis',
        'PENGETAHUAN KUANTITATIF' => 'Pengetahuan Kuantitatif',
        'LITERASI DALAM BAHASA INDONESIA' => 'Literasi Dalam Bahasa Indonesia',
        'LITERASI DALAM BAHASA INGGRIS' => 'Literasi Dalam Bahasa Inggris',
        'PENALARAN MATEMATIKA' => 'Penalaran Matematika'
    ];

    $allQuestions = [];
    $keys = [];
    $currentBab = '';
    $currentQuestion = null;
    $answersBlock = false;

    foreach ($lines as $line) {
        $tLine = trim($line);
        if (empty($tLine)) continue;

        if (strpos($tLine, 'KUNCI JAWABAN') !== false && strpos($tLine, '===') === false && strpos($tLine, '150') === false) {
            $answersBlock = true;
            continue;
        }

        if (strpos($tLine, '===') !== false) {
            continue;
        }

        // Section header check (HANYA match 7 judul subtes resmi, tidak memakan opsi A-G)
        if (preg_match('/^[A-G]\.\s+(.+)$/', $tLine, $m)) {
            $potentialSection = strtoupper(trim($m[1]));
            if (isset($validSections[$potentialSection])) {
                $currentBab = $validSections[$potentialSection];
                continue;
            }
        }

        if ($answersBlock) {
            if (preg_match('/^(\d+)\s+([A-E])$/', $tLine, $m)) {
                $keys[(int)$m[1]] = strtoupper($m[2]);
            }
            continue;
        }

        if (preg_match('/^(\d+)\.\s+(.+)$/', $tLine, $m)) {
            if ($currentQuestion) {
                $allQuestions[] = $currentQuestion;
            }
            $currentQuestion = [
                'num' => (int)$m[1],
                'sub_materi' => $currentBab,
                'question' => trim($m[2]),
                'options' => []
            ];
            continue;
        }

        if ($currentQuestion && preg_match('/^([A-E])\.\s+(.+)$/', $tLine, $m)) {
            $currentQuestion['options'][strtolower($m[1])] = trim($m[2]);
            continue;
        }

        if ($currentQuestion && !preg_match('/^[A-E]\./', $tLine) && count($currentQuestion['options']) === 0) {
            $currentQuestion['question'] .= "\n" . $tLine;
        }
    }

    if ($currentQuestion) {
        $allQuestions[] = $currentQuestion;
    }

    $stmt = $pdo->prepare("
        INSERT INTO questions (
            id, subtes, sub_materi, bab, difficulty, irt_score, question, 
            option_a, option_b, option_c, option_d, option_e, correct, 
            is_active, is_qc_passed, classification, cognitive_demand, 
            source_name, source_year, source_reference
        ) VALUES (
            ?, ?, ?, ?, 'medium', 5, ?, 
            ?, ?, ?, ?, ?, ?, 
            1, 1, 'TRYOUT', 'C4', 
            'EduPath UTBK Standar SNBT', 2026, 'Paket Tryout Akbar SNBT'
        )
    ");

    $count = 0;
    foreach ($allQuestions as $q) {
        $num = $q['num'];
        if (!isset($keys[$num])) continue;

        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);

        $correct = $keys[$num];
        $opt_a = $q['options']['a'] ?? '';
        $opt_b = $q['options']['b'] ?? '';
        $opt_c = $q['options']['c'] ?? '';
        $opt_d = $q['options']['d'] ?? '';
        $opt_e = $q['options']['e'] ?? '';

        $sub = $q['sub_materi'];

        $stmt->execute([
            $id, $sub, $sub, $sub, $q['question'],
            $opt_a, $opt_b, $opt_c, $opt_d, $opt_e, $correct
        ]);
        $count++;
    }

    header('Content-Type: application/json');
    echo json_encode([
        "success" => true,
        "message" => "Successfully imported $count UTBK questions into database.",
        "total_parsed" => count($allQuestions),
        "total_inserted" => $count
    ]);

} catch (Throwable $e) {
    header('Content-Type: application/json');
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => $e->getMessage(),
        "file" => $e->getFile(),
        "line" => $e->getLine()
    ]);
}
