<?php
require_once "config.php";
require_once "jwt.php";
require_once "EntitlementService.php";

function verify_diagnostic_access($pdo) {
    $token = get_token();
    if (!$token) {
        http_response_code(401);
        echo json_encode(["success" => false, "error" => "Silakan login terlebih dahulu untuk mengakses Diagnostic Assessment."]);
        exit;
    }
    $payload = verify_jwt($token);
    if (!$payload) {
        http_response_code(401);
        echo json_encode(["success" => false, "error" => "Sesi Anda telah kedaluwarsa. Silakan masuk kembali."]);
        exit;
    }
    if (in_array($payload->role, ['superadmin', 'admin', 'teacher', 'guru', 'tutor', 'school_bk', 'affiliate'], true)) {
        return $payload;
    }
    
    $entitlementService = new EntitlementService($pdo);
    $tenant_id = $payload->tenant_id ?? 'default_tenant';
    $hasAccess = $entitlementService->hasEntitlement($payload->id, $tenant_id, 'diagnostic_assessment')
              || $entitlementService->hasEntitlement($payload->id, $tenant_id, 'premium_assessments');
              
    if (!$hasAccess) {
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
                $entitlementService->grantEntitlement($payload->id, 'diagnostic_assessment', $row['sub_id'] ?? null, $row['expires_at'] ?? null);
                $hasAccess = true;
            }
        } catch (\Throwable $e) {}
    }
    
    if (!$hasAccess) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "error" => "Fitur Diagnostic Assessment khusus pengguna Premium. Silakan pilih paket belajar Anda.",
            "code" => "ENTITLEMENT_REQUIRED"
        ]);
        exit;
    }
    return $payload;
}

$mkuuid = function() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
};

function ensure_diagnostic_schema($pdo) {
    static $done = false;
    if ($done) return;

    $lockFile = sys_get_temp_dir() . '/edupath_diagnostic_schema_' . md5(__DIR__) . '.lock';
    if (!isset($_GET['sync_schema']) && file_exists($lockFile) && (time() - @filemtime($lockFile) < 86400)) {
        $done = true;
        return;
    }

    $done = true;
    @touch($lockFile);

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS diagnostic_results (
                id VARCHAR(36) PRIMARY KEY,
                student_id VARCHAR(36) NOT NULL,
                total_score INT DEFAULT 0,
                gap_domain VARCHAR(100),
                gap_sub_materi VARCHAR(100),
                gap_score DECIMAL(5,2) DEFAULT 0,
                details TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS learning_paths (
                id VARCHAR(36) PRIMARY KEY,
                student_id VARCHAR(36) NOT NULL,
                diagnostic_result_id VARCHAR(36),
                gap_focus VARCHAR(100),
                recommended_materials TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            );
        ");
    } catch (\Throwable $e) {}
}
ensure_diagnostic_schema($pdo);

$action = $_GET["action"] ?? "";

if ($action === "start") {
    $payload = verify_diagnostic_access($pdo);
    try {
        // Fetch diagnostic questions covering key domains
        $domains = ["Penalaran Umum", "Pengetahuan Kuantitatif", "Pemahaman Bacaan & Menulis", "Penalaran Matematika", "Literasi Bahasa Indonesia", "Literasi Bahasa Inggris"];
        $diagnostic_questions = [];

        foreach ($domains as $domain) {
            $stmt = $pdo->prepare("
                SELECT id, COALESCE(subtes, ?) as domain, COALESCE(sub_materi, subtes, 'Umum') as sub_materi, 
                       difficulty, question, option_a, option_b, option_c, option_d, option_e 
                FROM questions 
                WHERE (subtes = ? OR sub_materi = ? OR test_component = ?) AND (is_active = 1 OR is_active IS NULL) 
                ORDER BY RAND() LIMIT 2
            ");
            $stmt->execute([$domain, $domain, $domain, $domain]);
            $questions = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $diagnostic_questions = array_merge($diagnostic_questions, $questions);
        }

        // Fallback / fill up from general pool if needed
        if (count($diagnostic_questions) < 4) {
            $existingIds = array_column($diagnostic_questions, 'id');
            $placeholders = !empty($existingIds) ? implode(',', array_fill(0, count($existingIds), '?')) : '0';
            $limit = 8 - count($diagnostic_questions);
            $fallbackStmt = $pdo->prepare("
                SELECT id, COALESCE(subtes, 'Penalaran Umum') as domain, COALESCE(sub_materi, subtes, 'Umum') as sub_materi, 
                       difficulty, question, option_a, option_b, option_c, option_d, option_e 
                FROM questions 
                WHERE id NOT IN ($placeholders) AND (is_active = 1 OR is_active IS NULL) 
                ORDER BY RAND() LIMIT $limit
            ");
            $fallbackStmt->execute($existingIds);
            $extra = $fallbackStmt->fetchAll(PDO::FETCH_ASSOC);
            $diagnostic_questions = array_merge($diagnostic_questions, $extra);
        }

        echo json_encode(["success" => true, "data" => $diagnostic_questions]);
    } catch (PDOException $e) {
        http_response_code(500);
        echo json_encode(["error" => "Failed to load diagnostic test: " . $e->getMessage()]);
    }
} elseif ($action === "submit" && $_SERVER["REQUEST_METHOD"] === "POST") {
    $payload = verify_diagnostic_access($pdo);
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
    $stmt = $pdo->prepare("SELECT id, correct, COALESCE(subtes, 'Penalaran Umum') as domain, COALESCE(sub_materi, subtes, 'Umum') as sub_materi FROM questions WHERE id IN ($inQuery)");
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
        $qid = is_scalar($ans["question_id"] ?? null) ? (string)$ans["question_id"] : '';
        $rawAns = $ans["answer"] ?? '';
        $user_ans = is_string($rawAns) ? strtoupper(trim($rawAns)) : (is_scalar($rawAns) ? strtoupper((string)$rawAns) : '');

        if ($qid !== '' && isset($qMap[$qid])) {
            $db_q = $qMap[$qid];
            $is_correct = ($user_ans !== '' && $user_ans === strtoupper((string)$db_q["correct"]));
            $domain = $db_q["domain"] ?? "Penalaran Umum";
            $submateri = $db_q["sub_materi"] ?? "Penalaran Umum";

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
    $worst_submateri = "Penalaran Umum";
    $worst_domain = "Penalaran Umum";
    $lowest_pct = 0.0;

    if (!empty($submateri_stats)) {
        $lowest_pct = 101.0;
        foreach ($submateri_stats as $sm => $stat) {
            $tot = max(1, (int)$stat["total"]);
            $pct = round(($stat["correct"] / $tot) * 100, 2);
            if ($pct < $lowest_pct) {
                $lowest_pct = $pct;
                $worst_submateri = $sm;
                $worst_domain = $stat["domain"];
            }
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
        echo json_encode(["error" => "Gagal menyimpan hasil diagnostik: " . $e->getMessage()]);
    }
} else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint not found"]);
}

