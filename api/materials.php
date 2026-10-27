<?php
// api/materials.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'EntitlementService.php';

function ensure_and_seed_materials($pdo, $force = false) {
    static $executed = false;
    if ($executed && !$force) return;
    $executed = true;

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS materials (
                id VARCHAR(36) PRIMARY KEY,
                subtes VARCHAR(100) NULL,
                exam VARCHAR(50) DEFAULT 'SNBT',
                test_component VARCHAR(100) DEFAULT 'TPS',
                subtest VARCHAR(100) NOT NULL,
                sub_materi VARCHAR(100) NULL,
                topic VARCHAR(150) NULL,
                subtopic VARCHAR(150) NULL,
                skill VARCHAR(255) NULL,
                indicator VARCHAR(255) NULL,
                title VARCHAR(255) NOT NULL,
                content LONGTEXT NOT NULL,
                formula_praktis TEXT NULL,
                teacher_name VARCHAR(150) NULL,
                teacher_id VARCHAR(50) NULL,
                file_url VARCHAR(500) NULL,
                education_level VARCHAR(50) DEFAULT 'SMA',
                assessment_type VARCHAR(50) DEFAULT 'SNBT',
                subject VARCHAR(100) NULL,
                domain VARCHAR(100) NULL,
                is_active TINYINT(1) DEFAULT 1,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
    } catch (\Throwable $e) {}

    try {
        $pdo->exec("ALTER TABLE materials ADD COLUMN IF NOT EXISTS formula_praktis TEXT NULL");
    } catch (\Throwable $e) {
        try { $pdo->exec("ALTER TABLE materials ADD COLUMN formula_praktis TEXT NULL"); } catch (\Throwable $e2) {}
    }

    try {
        $count = (int)$pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
        if ($count === 0 || $force) {
            $seedFile = __DIR__ . '/database/seeds/kitab_materials_ready.json';
            if (file_exists($seedFile)) {
                $raw = file_get_contents($seedFile);
                $items = json_decode($raw, true);
                if (is_array($items) && count($items) > 0) {
                    $insertStmt = $pdo->prepare("
                        INSERT INTO materials (
                            id, subtes, title, content, formula_praktis, exam, test_component,
                            subtest, sub_materi, topic, subtopic, skill, indicator,
                            teacher_name, file_url, is_active, education_level,
                            assessment_type, subject, domain
                        ) VALUES (
                            ?, ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?, ?, ?,
                            ?, ?, ?, ?,
                            ?, ?, ?
                        )
                        ON DUPLICATE KEY UPDATE 
                            content = VALUES(content),
                            formula_praktis = VALUES(formula_praktis),
                            subtest = VALUES(subtest),
                            sub_materi = VALUES(sub_materi),
                            topic = VALUES(topic),
                            subtopic = VALUES(subtopic),
                            skill = VALUES(skill),
                            indicator = VALUES(indicator)
                    ");
                    foreach ($items as $m) {
                        $id = $m['id'] ?? bin2hex(random_bytes(16));
                        $subtes = mb_substr($m['subtes'] ?? '', 0, 100, 'UTF-8');
                        $title = mb_substr($m['title'] ?? '', 0, 250, 'UTF-8');
                        $content = $m['content'] ?? '';
                        $formula = $m['formula_praktis'] ?? 'Kuasai konsep kunci dan metode analisis cepat untuk subtes ini.';
                        $exam = mb_substr($m['exam'] ?? 'SNBT', 0, 50, 'UTF-8');
                        $comp = mb_substr($m['test_component'] ?? 'TPS', 0, 50, 'UTF-8');
                        $subtest = mb_substr($m['subtest'] ?? '', 0, 100, 'UTF-8');
                        $sub_materi = mb_substr($m['sub_materi'] ?? '', 0, 150, 'UTF-8');
                        $topic = mb_substr($m['topic'] ?? '', 0, 150, 'UTF-8');
                        $subtopic = mb_substr($m['subtopic'] ?? '', 0, 150, 'UTF-8');
                        $skill = mb_substr($m['skill'] ?? '', 0, 250, 'UTF-8');
                        $indicator = mb_substr($m['indicator'] ?? '', 0, 250, 'UTF-8');
                        $teacher = mb_substr($m['teacher_name'] ?? 'Tim EduPath', 0, 150, 'UTF-8');
                        $file_url = mb_substr($m['file_url'] ?? '', 0, 500, 'UTF-8');
                        $active = isset($m['is_active']) ? (int)$m['is_active'] : 1;
                        $ed_level = mb_substr($m['education_level'] ?? 'SMA', 0, 50, 'UTF-8');
                        $ass_type = mb_substr($m['assessment_type'] ?? 'SNBT', 0, 50, 'UTF-8');
                        $subject = mb_substr($m['subject'] ?? '', 0, 100, 'UTF-8');
                        $domain = mb_substr($m['domain'] ?? '', 0, 100, 'UTF-8');

                        $insertStmt->execute([
                            $id, $subtes, $title, $content, $formula, $exam, $comp,
                            $subtest, $sub_materi, $topic, $subtopic, $skill, $indicator,
                            $teacher, $file_url, $active, $ed_level,
                            $ass_type, $subject, $domain
                        ]);
                    }
                }
            }
        }
    } catch (\Throwable $e) {}
}

$action = $_GET['action'] ?? '';

if ($action === 'seed' || $action === 'sync') {
    ensure_and_seed_materials($pdo, true);
    $total = (int)$pdo->query("SELECT COUNT(*) FROM materials")->fetchColumn();
    echo json_encode(["success" => true, "message" => "Seed materials executed", "total_materials" => $total]);
    exit;
}

if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // 0. Ensure table exists and auto-seed if empty
    ensure_and_seed_materials($pdo);

    // 1. AUTHENTICATE (Optional for general material reading)
    $token = get_token();
    $payload = $token ? verify_jwt($token) : null;
    
    // 2. INITIALIZE ENTITLEMENT SERVICE
    $entitlementService = new EntitlementService($pdo);
    
    // 3. CHECK ENTITLEMENT (Server-side Role & Subscription Enforcement)
    $subtes = safe_str($_GET['subtes'] ?? '');
    $isAdvanced = isset($_GET['advanced']) && $_GET['advanced'] == 1;

    $isStaff = $payload && in_array($payload->role, ['superadmin', 'admin', 'teacher', 'school_bk'], true);
    $hasPremiumAccess = $isStaff || ($payload && $entitlementService->hasEntitlement($payload->id, $payload->tenant_id ?? '', 'premium_materials'));

    if ($isAdvanced && !$hasPremiumAccess) {
        http_response_code(403);
        echo json_encode([
            "success" => false,
            "error" => [
                "code" => "FORBIDDEN_ENTITLEMENT",
                "message" => "Anda tidak memiliki akses ke materi premium ini. Silakan upgrade paket Anda."
            ]
        ]);
        exit;
    }
    
    // 4. FETCH RESOURCE
    $test_component = safe_str($_GET['test_component'] ?? '');
    $topic = safe_str($_GET['topic'] ?? '');

    $education_level = safe_str($_GET['education_level'] ?? '');
    $assessment_type = safe_str($_GET['assessment_type'] ?? '');

    $sql = "SELECT id, exam, test_component, subtest, topic, subtopic, skill, indicator, title, content, formula_praktis, file_url, teacher_name, education_level, assessment_type, subject, domain, created_at FROM materials WHERE (is_active = 1 OR is_active IS NULL)";
    $params = [];
    if ($subtes) {
        $sql .= " AND (subtest = ? OR subtest LIKE ?)";
        $params[] = $subtes;
        $params[] = "%$subtes%";
    }
    if ($test_component && $test_component !== 'all') {
        $sql .= " AND test_component = ?";
        $params[] = $test_component;
    }
    if ($topic && $topic !== 'all') {
        $sql .= " AND topic = ?";
        $params[] = $topic;
    }
    if ($education_level && $education_level !== 'all') {
        $sql .= " AND (education_level = ? OR education_level IS NULL)";
        $params[] = $education_level;
    }
    if ($assessment_type && $assessment_type !== 'all') {
        $sql .= " AND (assessment_type = ? OR assessment_type IS NULL)";
        $params[] = $assessment_type;
    }
    $sql .= " ORDER BY created_at DESC";

    try {
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode($materials);
    } catch (PDOException $e) {
        // If column formula_praktis or other new column is missing, attempt auto-repair and retry once
        if (strpos($e->getMessage(), 'Unknown column') !== false || strpos($e->getMessage(), 'formula_praktis') !== false) {
            try {
                $pdo->exec("ALTER TABLE materials ADD COLUMN formula_praktis TEXT NULL");
                $stmt = $pdo->prepare($sql);
                $stmt->execute($params);
                $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
                echo json_encode($materials);
                exit;
            } catch (Exception $subEx) {}
        }
        
        // Fallback: query without formula_praktis if still failing
        try {
            $fallbackSql = str_replace(", formula_praktis", "", $sql);
            $stmt = $pdo->prepare($fallbackSql);
            $stmt->execute($params);
            $materials = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode($materials);
        } catch (Exception $finalEx) {
            echo json_encode([]);
        }
    }
} else {
    http_response_code(404);
    echo json_encode([
        "success" => false, 
        "error" => [
            "code" => "NOT_FOUND",
            "message" => "Endpoint materi tidak ditemukan"
        ]
    ]);
}
