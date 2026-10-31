<?php
/**
 * import_kitab_snbt.php
 * Script pengimpor materi dan bank soal dari berkas Kitab SNBT 2024 ke dalam database EduPath.
 */

if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    die("Akses ditolak. Script hanya dapat dijalankan melalui CLI terminal.");
}

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);

require_once __DIR__ . '/api/config.php';

$materialsJsonPath = file_exists(__DIR__ . '/api/database/seeds/kitab_materials_ready.json') 
    ? __DIR__ . '/api/database/seeds/kitab_materials_ready.json' 
    : __DIR__ . '/scratch/materials_ready.json';

$questionsJsonPath = file_exists(__DIR__ . '/api/database/seeds/kitab_questions_ready.json') 
    ? __DIR__ . '/api/database/seeds/kitab_questions_ready.json' 
    : __DIR__ . '/scratch/questions_ready.json';

if (!file_exists($materialsJsonPath) || !file_exists($questionsJsonPath)) {
    die("File JSON materi atau soal belum ditemukan di api/database/seeds/ atau scratch/.\n");
}

$materials = json_decode(file_get_contents($materialsJsonPath), true);
$questions = json_decode(file_get_contents($questionsJsonPath), true);

function trunc($str, $len = 100) {
    if ($str === null) return null;
    return mb_substr($str, 0, $len, 'UTF-8');
}

echo "========================================================\n";
echo " EDUPath Importer - Kitab SNBT 2024\n";
echo "========================================================\n";
echo "Total materi yang akan diproses: " . count($materials) . "\n";
echo "Total soal yang akan diproses   : " . count($questions) . "\n\n";

// Pastikan kolom dan struktur tabel up-to-date
try {
    $pdo->exec("ALTER TABLE materials ADD COLUMN IF NOT EXISTS formula_praktis TEXT NULL");
} catch (Exception $e) {
    try { $pdo->exec("ALTER TABLE materials ADD COLUMN formula_praktis TEXT NULL"); } catch (Exception $e2) {}
}

$materialsInserted = 0;
$materialsUpdated = 0;
$questionsInserted = 0;
$questionsUpdated = 0;

try {
    $pdo->beginTransaction();

    // 1. IMPORT MATERI
    echo "--- [1/2] Mengimpor Materi Pembelajaran ---\n";
    
    $checkMatStmt = $pdo->prepare("SELECT id FROM materials WHERE title = ? AND subtes = ?");
    $insertMatStmt = $pdo->prepare("
        INSERT INTO materials (
            subtes, title, content, formula_praktis, exam, test_component, 
            subtest, sub_materi, topic, subtopic, skill, indicator, 
            teacher_name, file_url, is_active, education_level, 
            assessment_type, subject, domain
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?
        )
    ");
    $updateMatStmt = $pdo->prepare("
        UPDATE materials SET 
            content = ?, formula_praktis = ?, exam = ?, test_component = ?, subtest = ?, 
            sub_materi = ?, topic = ?, subtopic = ?, skill = ?, indicator = ?, 
            teacher_name = ?, file_url = ?, is_active = ?, education_level = ?, 
            assessment_type = ?, subject = ?, domain = ?
        WHERE id = ?
    ");

    foreach ($materials as $m) {
        $checkMatStmt->execute([trunc($m['title'], 250), trunc($m['subtes'], 100)]);
        $existing = $checkMatStmt->fetch(PDO::FETCH_ASSOC);

        $subtes = trunc($m['subtes'], 100);
        $title = trunc($m['title'], 250);
        $content = $m['content'];
        $formula = $m['formula_praktis'] ?? 'Kuasai konsep kunci dan metode analisis cepat untuk subtes ini.';
        $exam = trunc($m['exam'], 50);
        $comp = trunc($m['test_component'], 50);
        $subtest = trunc($m['subtest'], 100);
        $sub_materi = trunc($m['sub_materi'], 150);
        $topic = trunc($m['topic'], 150);
        $subtopic = trunc($m['subtopic'], 150);
        $skill = trunc($m['skill'], 250);
        $indicator = trunc($m['indicator'], 250);
        $teacher = trunc($m['teacher_name'], 150);
        $file_url = trunc($m['file_url'], 500);
        $active = (int)$m['is_active'];
        $ed_level = trunc($m['education_level'], 50);
        $ass_type = trunc($m['assessment_type'], 50);
        $subject = trunc($m['subject'], 100);
        $domain = trunc($m['domain'], 100);

        if ($existing) {
            $updateMatStmt->execute([
                $content, $formula, $exam, $comp, $subtest,
                $sub_materi, $topic, $subtopic, $skill, $indicator,
                $teacher, $file_url, $active, $ed_level,
                $ass_type, $subject, $domain,
                $existing['id']
            ]);
            $materialsUpdated++;
        } else {
            $insertMatStmt->execute([
                $subtes, $title, $content, $formula, $exam, $comp,
                $subtest, $sub_materi, $topic, $subtopic, $skill, $indicator,
                $teacher, $file_url, $active, $ed_level,
                $ass_type, $subject, $domain
            ]);
            $materialsInserted++;
        }
    }
    echo "✅ Materi selesai diproses: $materialsInserted baru, $materialsUpdated diperbarui.\n\n";

    // 2. IMPORT SOAL
    echo "--- [2/2] Mengimpor Bank Soal & Pembahasan ---\n";
    $checkQStmt = $pdo->prepare("SELECT id FROM questions WHERE source_reference = ? AND sub_materi = ?");
    $insertQStmt = $pdo->prepare("
        INSERT INTO questions (
            id, subtes, sub_materi, bab, difficulty, question, 
            option_a, option_b, option_c, option_d, option_e, correct, 
            irt_score, explanation, trick, concept, is_active, 
            exam, test_component, subtest, topic, subtopic, skill, indicator, 
            question_type, is_qc_passed, classification, teacher_name, 
            cognitive_demand, source_type, rights_status, source_name, 
            source_year, source_reference, education_level, assessment_type, 
            subject, domain
        ) VALUES (
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?, ?, ?, 
            ?, ?
        )
    ");
    $updateQStmt = $pdo->prepare("
        UPDATE questions SET 
            subtes = ?, sub_materi = ?, bab = ?, difficulty = ?, question = ?, 
            option_a = ?, option_b = ?, option_c = ?, option_d = ?, option_e = ?, correct = ?, 
            irt_score = ?, explanation = ?, trick = ?, concept = ?, is_active = ?, 
            exam = ?, test_component = ?, subtest = ?, topic = ?, subtopic = ?, skill = ?, indicator = ?, 
            question_type = ?, is_qc_passed = ?, classification = ?, teacher_name = ?, 
            cognitive_demand = ?, source_type = ?, rights_status = ?, source_name = ?, 
            source_year = ?, education_level = ?, assessment_type = ?, 
            subject = ?, domain = ?
        WHERE id = ?
    ");

    foreach ($questions as $q) {
        $subtes = trunc($q['subtes'], 100);
        $sub_materi = trunc($q['sub_materi'], 150);
        $bab = trunc($q['bab'], 100);
        $diff = in_array($q['difficulty'], ['Mudah', 'Sedang', 'HOTS']) ? $q['difficulty'] : 'Sedang';
        $question = $q['question'];
        $opt_a = $q['option_a'];
        $opt_b = $q['option_b'];
        $opt_c = $q['option_c'];
        $opt_d = $q['option_d'];
        $opt_e = $q['option_e'];
        $correct = strtoupper(trim($q['correct']));
        if (!in_array($correct, ['A', 'B', 'C', 'D', 'E'])) $correct = 'A';
        $irt = (float)($q['irt_score'] ?? 15.00);
        $exp = $q['explanation'];
        $trick = $q['trick'] ?? '';
        $concept = trunc($q['concept'] ?? $sub_materi, 100);
        $active = (int)($q['is_active'] ?? 1);
        $exam = trunc($q['exam'] ?? 'SNBT', 50);
        $comp = trunc($q['test_component'] ?? 'TPS', 50);
        $subtest = trunc($q['subtest'] ?? $subtes, 100);
        $topic = trunc($q['topic'] ?? $sub_materi, 150);
        $subtopic = trunc($q['subtopic'] ?? $sub_materi, 150);
        $skill = trunc($q['skill'] ?? $sub_materi, 250);
        $indicator = trunc($q['indicator'] ?? $sub_materi, 250);
        $q_type = trunc($q['question_type'] ?? 'multiple_choice', 50);
        $qc = (int)($q['is_qc_passed'] ?? 1);
        $class = trunc($q['classification'] ?? 'LATIHAN', 50);
        $t_name = trunc($q['teacher_name'] ?? 'Tim LetStudy & EduPath', 150);
        $cog = trunc($q['cognitive_demand'] ?? 'C4', 50);
        $s_type = trunc($q['source_type'] ?? 'author_created', 50);
        $rights = trunc($q['rights_status'] ?? 'verified', 50);
        $s_name = trunc($q['source_name'] ?? 'Kitab SNBT 2024 (LetStudy)', 250);
        $s_year = (int)($q['source_year'] ?? 2024);
        $s_ref = trunc($q['source_reference'] ?? 'Kitab SNBT 2024', 250);
        $ed_level = trunc($q['education_level'] ?? 'SMA', 50);
        $ass_type = trunc($q['assessment_type'] ?? 'SNBT', 50);
        $subject = trunc($q['subject'] ?? $subtes, 100);
        $domain = trunc($q['domain'] ?? $comp, 100);

        $checkQStmt->execute([$s_ref, $sub_materi]);
        $existing = $checkQStmt->fetch(PDO::FETCH_ASSOC);

        if ($existing) {
            $updateQStmt->execute([
                $subtes, $sub_materi, $bab, $diff, $question,
                $opt_a, $opt_b, $opt_c, $opt_d, $opt_e, $correct,
                $irt, $exp, $trick, $concept, $active,
                $exam, $comp, $subtest, $topic, $subtopic, $skill, $indicator,
                $q_type, $qc, $class, $t_name,
                $cog, $s_type, $rights, $s_name,
                $s_year, $ed_level, $ass_type,
                $subject, $domain,
                $existing['id']
            ]);
            $questionsUpdated++;
        } else {
            $q_id = $q['id'] ?? (sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000,
                mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
            ));

            $insertQStmt->execute([
                $q_id, $subtes, $sub_materi, $bab, $diff, $question,
                $opt_a, $opt_b, $opt_c, $opt_d, $opt_e, $correct,
                $irt, $exp, $trick, $concept, $active,
                $exam, $comp, $subtest, $topic, $subtopic, $skill, $indicator,
                $q_type, $qc, $class, $t_name,
                $cog, $s_type, $rights, $s_name,
                $s_year, $s_ref, $ed_level, $ass_type,
                $subject, $domain
            ]);
            $questionsInserted++;
        }
    }
    echo "✅ Soal selesai diproses: $questionsInserted baru, $questionsUpdated diperbarui.\n\n";

    $pdo->commit();

    echo "========================================================\n";
    echo " REKAP HASIL IMPORT DATABASE EDUPATH\n";
    echo "========================================================\n";
    
    echo "1. Distribusi Materi per Subtes:\n";
    $mDist = $pdo->query("SELECT subtes, COUNT(*) as cnt FROM materials GROUP BY subtes ORDER BY cnt DESC");
    while ($r = $mDist->fetch(PDO::FETCH_ASSOC)) {
        echo "   - " . ($r['subtes'] ?: '(Tanpa Subtes)') . ": {$r['cnt']} materi\n";
    }

    echo "\n2. Distribusi Soal per Subtes:\n";
    $qDist = $pdo->query("SELECT subtes, COUNT(*) as cnt FROM questions GROUP BY subtes ORDER BY cnt DESC");
    while ($r = $qDist->fetch(PDO::FETCH_ASSOC)) {
        echo "   - " . ($r['subtes'] ?: '(Tanpa Subtes)') . ": {$r['cnt']} butir soal\n";
    }

    $totalMat = $pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
    $totalQ = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    echo "\nTotal Materi di Database : $totalMat\n";
    echo "Total Soal di Database   : $totalQ\n";
    echo "Status: SUKSES!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "❌ Terjadi error saat impor data: " . $e->getMessage() . "\n";
    echo "Di baris: " . $e->getLine() . "\n";
}
