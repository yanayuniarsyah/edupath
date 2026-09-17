<?php
require_once "config.php";
require_once "jwt.php";

$mkuuid = function() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
};

$action = $_GET["action"] ?? "";

if ($action === "start") {
    try {
        // Fetch diagnostic blueprint (2 questions per domain for quick test)
        $domains = ["Penalaran Umum", "Pengetahuan Kuantitatif", "Pemahaman Bacaan & Menulis", "Penalaran Matematika"];
        $diagnostic_questions = [];

        foreach ($domains as $domain) {
            $stmt = $pdo->prepare("SELECT id, domain, sub_materi, difficulty, question, option_a, option_b, option_c, option_d, option_e FROM questions WHERE domain = ? AND is_active = 1 ORDER BY RAND() LIMIT 2");
            $stmt->execute([$domain]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $diagnostic_questions = array_merge($diagnostic_questions, $questions);
        }

        echo json_encode(["success" => true, "data" => $diagnostic_questions]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to load diagnostic test"]);
    }
} elseif ($action === "submit" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $payload = authenticate();
    $input = json_decode(file_get_contents("php://input"), true);
    $answers = $input["answers"] ?? []; // [{ question_id: "...", answer: "A" }]

    if (empty($answers)) {
        http_response_code(400);
        echo json_encode(["error" => "No answers provided"]);
        exit;
    }

    $qIds = array_column($answers, "question_id");
    if (empty($qIds)) {
        echo json_encode(["success" => true, "message" => "Empty questions"]);
        exit;
    }

    $inQuery = implode(",", array_fill(0, count($qIds), "?"));
    $stmt = $pdo->prepare("SELECT id, correct, domain, sub_materi FROM questions WHERE id IN ($inQuery)");
    $stmt->execute($qIds);
    $questionsDb = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $qMap = [];
    foreach ($questionsDb as $q) {
        $qMap[$q["id"]] = $q;
    }

    $totalScore = 0;
    $domain_stats = []; // domain => [correct, total]
    $submateri_stats = []; // submateri => [correct, total, domain]

    foreach ($answers as $ans) {
        $qid = $ans["question_id"];
        $user_ans = strtoupper($ans["answer"]);

        if (isset($qMap[$qid])) {
            $db_q = $qMap[$qid];
            $is_correct = ($user_ans === strtoupper($db_q["correct"]));
            $domain = $db_q["domain"] ?? "Uncategorized";
            $submateri = $db_q["sub_materi"] ?? "Uncategorized";

            if (!isset($domain_stats[$domain])) $domain_stats[$domain] = ["correct" => 0, "total" => 0];
            if (!isset($submateri_stats[$submateri])) $submateri_stats[$submateri] = ["correct" => 0, "total" => 0, "domain" => $domain];

            $domain_stats[$domain]["total"]++;
            $submateri_stats[$submateri]["total"]++;

            if ($is_correct) {
                $totalScore += 10;
                $domain_stats[$domain]["correct"]++;
                $submateri_stats[$submateri]["correct"]++;
            }
        }
    }

    // GAP Analysis
    $worst_submateri = null;
    $worst_domain = null;
    $lowest_pct = 101;

    foreach ($submateri_stats as $sm => $stat) {
        $pct = ($stat["correct"] / $stat["total"]) * 100;
        if ($pct < $lowest_pct) {
            $lowest_pct = $pct;
            $worst_submateri = $sm;
            $worst_domain = $stat["domain"];
        }
    }

    $diag_id = $mkuuid();
    $details = json_encode([
        "domain_stats" => $domain_stats,
        "submateri_stats" => $submateri_stats
    ]);

    try {
        $pdo->beginTransaction();
        
        $ins = $pdo->prepare("INSERT INTO diagnostic_results (id, student_id, total_score, gap_domain, gap_sub_materi, gap_score, details) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $ins->execute([$diag_id, $payload->id, $totalScore, $worst_domain, $worst_submateri, $lowest_pct, $details]);

        $path_id = $mkuuid();
        $recommended = json_encode([
            "primary_focus" => $worst_submateri,
            "actions" => [
                ["type" => "material", "title" => "Pelajari Modul $worst_submateri"],
                ["type" => "drill", "title" => "Latihan Fokus $worst_submateri"]
            ]
        ]);

        $ins_path = $pdo->prepare("INSERT INTO learning_paths (id, student_id, diagnostic_result_id, gap_focus, recommended_materials) VALUES (?, ?, ?, ?, ?)");
        $ins_path->execute([$path_id, $payload->id, $diag_id, $worst_submateri, $recommended]);

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "diagnostic_id" => $diag_id,
            "gap_domain" => $worst_domain,
            "gap_sub_materi" => $worst_submateri,
            "gap_score" => $lowest_pct,
            "learning_path" => json_decode($recommended)
        ]);
    } catch (PDOException $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Gagal menyimpan hasil diagnostik"]);
    }
} else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found"]);
}

