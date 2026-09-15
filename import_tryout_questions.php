<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require 'api/config.php';

$transcriptPath = __DIR__ . '/tryout_data.txt';
if (!file_exists($transcriptPath)) {
    die("File tryout_data.txt tidak ditemukan. Silakan buat file tersebut dan masukkan isi soal tryout ke dalamnya.\n");
}
$lines = file($transcriptPath);

try {
    // 1. Pastikan kolom is_qc_passed ada di database
    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN is_qc_passed TINYINT(1) DEFAULT 0");
        echo "Berhasil menambahkan kolom is_qc_passed ke tabel questions.<br>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Error adding is_qc_passed: " . $e->getMessage() . "<br>\n";
        }
    }

    try {
        $pdo->exec("ALTER TABLE questions ADD COLUMN classification VARCHAR(50) DEFAULT 'LATIHAN'");
        echo "Berhasil menambahkan kolom classification ke tabel questions.<br>\n";
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'Duplicate column name') === false) {
            echo "Error adding classification: " . $e->getMessage() . "<br>\n";
        }
    }

    $count = 0;
    // 2. Siapkan query insert (TANPA source_type, rights_status, cognitive_demand)
    $stmt = $pdo->prepare("INSERT INTO questions (id, subtes, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, is_qc_passed, classification) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'TRYOUT')");

    $allQuestions = [];
    $keys = [];
    $currentBab = '';
    $currentQuestion = null;
    $answersBlock = false;

    foreach ($lines as $tLine) {
        $tLine = trim($tLine);
        if (empty($tLine)) continue;
        
        if ($tLine === 'KUNCI JAWABAN' || strpos($tLine, 'KUNCI JAWABAN') !== false && strpos($tLine, '===') === false && strpos($tLine, '150') === false) {
            $answersBlock = true;
            continue;
        }
        
        if (strpos($tLine, '===') !== false) {
            continue;
        }
        
        if (preg_match('/^[A-F]\.\s+(.+)$/', $tLine, $m)) {
            $currentBab = trim($m[1]);
            continue;
        }
        
        if ($answersBlock) {
            if (preg_match('/^(\d+)\s+([A-E])$/', $tLine, $m)) {
                $keys[(int)$m[1]] = strtolower($m[2]);
            }
            continue;
        }
        
        if (preg_match('/^(\d+)\.\s+(.+)$/', $tLine, $m)) {
            if ($currentQuestion) {
                $allQuestions[] = $currentQuestion;
            }
            $currentQuestion = [
                'num' => (int)$m[1],
                'bab' => $currentBab,
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

    foreach ($allQuestions as $q) {
        if (!isset($keys[$q['num']])) continue;
        
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $correct = $keys[$q['num']];
        $opt_a = $q['options']['a'] ?? '';
        $opt_b = $q['options']['b'] ?? '';
        $opt_c = $q['options']['c'] ?? '';
        $opt_d = $q['options']['d'] ?? '';
        $opt_e = $q['options']['e'] ?? '';
        
        $subMateri = ucwords(strtolower(trim($q['bab'])));
        
        try {
            $stmt->execute([
                $id, $subMateri, $subMateri, $subMateri, 'medium', $q['question'], $opt_a, $opt_b, $opt_c, $opt_d, $opt_e, $correct
            ]);
            $count++;
        } catch (Exception $e) {
            echo "Error saving question " . $q['num'] . ": " . $e->getMessage() . "<br>\n";
        }
    }

    echo "Successfully imported $count questions.\n";

} catch (Throwable $e) {
    echo "<h1>Error 500 Terdeteksi</h1>";
    echo "Pesan Error: " . $e->getMessage() . "<br>";
    echo "File: " . $e->getFile() . " (Baris " . $e->getLine() . ")<br>";
}

