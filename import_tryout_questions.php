<?php
require 'api/config.php';

$transcriptPath = 'C:\Users\yanay\.gemini\antigravity-ide\brain\51ed2b76-3bbc-4ff0-88a1-d812597226c2\.system_generated\logs\transcript_full.jsonl';
$lines = file($transcriptPath);

$count = 0;
$stmt = $pdo->prepare("INSERT INTO questions (id, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, is_qc_passed, usage_type, source_type, rights_status, cognitive_demand) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'tryout', 'author_created', 'owned', 'C3')");

foreach ($lines as $line) {
    $data = json_decode($line, true);
    if (!$data || $data['type'] !== 'USER_INPUT') continue;
    
    $text = $data['content'] ?? '';
    if (strpos($text, 'EDUPATH — TRYOUT UTBK') === false) continue;
    
    $textLines = explode("\n", $text);
    $allQuestions = [];
    $keys = [];
    $currentBab = '';
    $currentQuestion = null;
    $answersBlock = false;
    
    foreach ($textLines as $tLine) {
        $tLine = trim($tLine);
        if (empty($tLine)) continue;
        
        if ($tLine === 'KUNCI JAWABAN' || strpos($tLine, 'KUNCI JAWABAN') !== false && strpos($tLine, '===') === false && strpos($tLine, '150') === false) {
            $answersBlock = true;
            continue;
        }
        
        if (strpos($tLine, '===') !== false) {
            continue;
        }
        
        // Match sections
        if (preg_match('/^[A-F]\.\s+(.+)$/', $tLine, $m)) {
            $currentBab = trim($m[1]);
            continue;
        }
        
        // Parse answers block
        if ($answersBlock) {
            if (preg_match('/^(\d+)\s+([A-E])$/', $tLine, $m)) {
                $keys[(int)$m[1]] = strtolower($m[2]);
            }
            continue;
        }
        
        // Question number
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
        
        // Option
        if ($currentQuestion && preg_match('/^([A-E])\.\s+(.+)$/', $tLine, $m)) {
            $currentQuestion['options'][strtolower($m[1])] = trim($m[2]);
            continue;
        }
        
        // Additional question text
        if ($currentQuestion && !preg_match('/^[A-E]\./', $tLine) && count($currentQuestion['options']) === 0) {
            $currentQuestion['question'] .= "\n" . $tLine;
        }
    }
    
    if ($currentQuestion) {
        $allQuestions[] = $currentQuestion;
    }
    
    // Insert for this prompt block
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
                $id, $subMateri, $subMateri, 'medium', $q['question'], $opt_a, $opt_b, $opt_c, $opt_d, $opt_e, $correct
            ]);
            $count++;
        } catch (Exception $e) {
            echo "Error saving question " . $q['num'] . ": " . $e->getMessage() . "\n";
        }
    }
}

echo "Successfully imported $count questions.\n";

