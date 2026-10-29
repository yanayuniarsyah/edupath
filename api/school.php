<?php
// api/school.php — EduPath School & Guru BK Management API
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

header('Content-Type: application/json; charset=utf-8');

function ensure_school_schema($pdo) {
    static $done = false;
    if ($done) return;

    $lockFile = sys_get_temp_dir() . '/edupath_school_schema_' . md5(__DIR__) . '.lock';
    if (!isset($_GET['sync_schema']) && file_exists($lockFile) && (time() - @filemtime($lockFile) < 86400)) {
        $done = true;
        return;
    }

    $done = true;
    @touch($lockFile);

    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS schools (
                id VARCHAR(36) PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                slug VARCHAR(255) NOT NULL UNIQUE,
                npsn VARCHAR(20) NULL,
                city VARCHAR(100) NULL,
                province VARCHAR(100) NULL,
                bk_name VARCHAR(150) NOT NULL,
                bk_phone VARCHAR(50) NOT NULL,
                bk_email VARCHAR(150) NOT NULL,
                estimated_students INT DEFAULT 200,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                INDEX idx_school_name (name),
                INDEX idx_school_slug (slug)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
        ");
        // Convert to default charset to eliminate any collation mismatch
        try { $pdo->exec("ALTER TABLE schools CONVERT TO CHARACTER SET utf8mb4"); } catch (\Throwable $e) {}
    } catch (\Throwable $e) {
        error_log("School schema creation failed: " . $e->getMessage());
    }

    try {
        // Add is_active to schools if not present
        $cols = $pdo->query("SHOW COLUMNS FROM schools LIKE 'is_active'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE schools ADD COLUMN is_active TINYINT DEFAULT 1");
        }
        $tCols = $pdo->query("SHOW COLUMNS FROM schools LIKE 'tenant_id'")->fetchAll();
        if (empty($tCols)) {
            $pdo->exec("ALTER TABLE schools ADD COLUMN tenant_id VARCHAR(36) DEFAULT 'default_tenant' AFTER id");
        }
    } catch (\Throwable $e) {}

    try {
        // Add school_id to students if not present
        $cols = $pdo->query("SHOW COLUMNS FROM students LIKE 'school_id'")->fetchAll();
        if (empty($cols)) {
            $pdo->exec("ALTER TABLE students ADD COLUMN school_id VARCHAR(36) NULL AFTER school");
        }
    } catch (\Throwable $e) {}

    try {
        // Add index on students(school) for high-performance aggregate queries
        $pdo->exec("CREATE INDEX idx_student_school_name ON students(school)");
    } catch (\Throwable $e) {}

    try { $pdo->exec("ALTER TABLE quiz_attempts ADD COLUMN quiz_type VARCHAR(50) DEFAULT 'tryout'"); } catch (\Throwable $e) {}
    try { $pdo->exec("ALTER TABLE quiz_results ADD COLUMN quiz_type VARCHAR(50) DEFAULT 'tryout'"); } catch (\Throwable $e) {}
}

ensure_school_schema($pdo);

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?? [];

// Helper to generate UUID
function generate_school_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// -------------------------------------------------------------
// PUBLIC LIST (Untuk dropdown asal sekolah saat registrasi/profil siswa)
// -------------------------------------------------------------
if ($action === 'public_list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $stmt = $pdo->query("SELECT id, name, city, province FROM schools WHERE is_active IS NULL OR is_active != 0 ORDER BY name ASC");
        $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["success" => true, "schools" => $schools]);
    } catch (\Throwable $e) {
        try {
            $stmt = $pdo->query("SELECT id, name, city, province FROM schools ORDER BY name ASC");
            $schools = $stmt->fetchAll(PDO::FETCH_ASSOC);
            echo json_encode(["success" => true, "schools" => $schools]);
        } catch (\Throwable $e2) {
            echo json_encode(["success" => true, "schools" => []]);
        }
    }
    exit;
}

// -------------------------------------------------------------
// 1. REGISTER SEKOLAH / GURU BK
// -------------------------------------------------------------
if ($action === 'register' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!check_rate_limit($pdo, 'school_register', 5, 30)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak percobaan pendaftaran. Coba lagi nanti."]);
        exit;
    }

    $name = trim($input['name'] ?? '');
    $bk_name = trim($input['bk_name'] ?? '');
    $email = strtolower(trim($input['email'] ?? ''));
    $phone = trim($input['phone'] ?? '');
    $password = $input['password'] ?? '';
    $npsn = trim($input['npsn'] ?? '');
    $city = trim($input['city'] ?? '');
    $estimated_students = (int)($input['estimated_students'] ?? 200);

    if (empty($name) || empty($bk_name) || empty($email) || empty($phone) || empty($password)) {
        http_response_code(400);
        echo json_encode(["error" => "Nama Sekolah, Koordinator BK, Email, No. WhatsApp, dan Password wajib diisi."]);
        exit;
    }

    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        http_response_code(400);
        echo json_encode(["error" => "Format email tidak valid."]);
        exit;
    }

    if (strlen($password) < 6) {
        http_response_code(400);
        echo json_encode(["error" => "Password minimal 6 karakter."]);
        exit;
    }

    // Check if email already used in users
    $stmt = $pdo->prepare("SELECT id FROM users WHERE identity_key = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        http_response_code(409);
        echo json_encode(["error" => "Email sudah terdaftar. Silakan login."]);
        exit;
    }

    // Generate unique slug for school
    $baseSlug = preg_replace('/[^a-z0-9]+/i', '-', strtolower($name));
    $baseSlug = trim($baseSlug, '-');
    if (empty($baseSlug)) $baseSlug = 'sekolah';
    $slug = $baseSlug;
    $suffix = 1;
    while (true) {
        $stmt_s = $pdo->prepare("SELECT id FROM schools WHERE slug = ?");
        $stmt_s->execute([$slug]);
        if (!$stmt_s->fetch()) break;
        $slug = $baseSlug . '-' . (++$suffix);
    }

    $school_id = generate_school_uuid();
    $user_id = generate_school_uuid();
    $ur_id = generate_school_uuid();
    $hashed_pass = password_hash($password, PASSWORD_BCRYPT);

    $pdo->beginTransaction();
    try {
        // Insert into schools
        $stmt = $pdo->prepare("INSERT INTO schools (id, name, slug, npsn, city, bk_name, bk_phone, bk_email, estimated_students) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$school_id, $name, $slug, $npsn, $city, $bk_name, $phone, $email, $estimated_students]);

        // Insert into users
        $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)");
        $stmt->execute([$user_id, $email, $hashed_pass]);

        // Insert into user_roles (using default_tenant to satisfy foreign key & NOT NULL)
        $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, 'default_tenant', 'school_bk', ?)");
        $stmt->execute([$ur_id, $user_id, $school_id]);

        $pdo->commit();
        reset_rate_limit($pdo, 'school_register');

        $token = generate_jwt([
            'user_id' => $user_id,
            'id' => $school_id,
            'role' => 'school_bk',
            'school_id' => $school_id,
            'school_name' => $name,
            'email' => $email
        ]);

        $share_link = "https://edupath.co.id/?school=" . urlencode($name) . "#/spp";

        echo json_encode([
            "success" => true,
            "message" => "Pendaftaran sekolah berhasil!",
            "token" => $token,
            "school" => [
                "id" => $school_id,
                "name" => $name,
                "slug" => $slug,
                "bk_name" => $bk_name,
                "bk_email" => $email,
                "bk_phone" => $phone,
                "share_link" => $share_link
            ]
        ]);
        exit;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        error_log("School registration failed: " . $e->getMessage());
        echo json_encode(["error" => "Gagal mendaftarkan sekolah: " . $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------
// 2. LOGIN GURU BK / SEKOLAH
// -------------------------------------------------------------
if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $email = strtolower(trim($input['email'] ?? ''));
        $password = $input['password'] ?? '';

        if (!check_rate_limit($pdo, 'school_login', 30, 15)) {
            http_response_code(429);
            echo json_encode(["error" => "Terlalu banyak percobaan login. Coba lagi dalam beberapa saat."]);
            exit;
        }

        if (empty($email) || empty($password)) {
            http_response_code(400);
            echo json_encode(["error" => "Email dan password wajib diisi."]);
            exit;
        }

        $stmt = $pdo->prepare("
            SELECT u.id as user_id, u.password as user_password, ur.role, ur.reference_id,
                   s.id as school_id, s.name as school_name, s.slug, s.bk_name, s.bk_email, s.bk_phone, s.city
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            LEFT JOIN schools s ON (ur.reference_id = s.id OR u.identity_key = s.bk_email)
            WHERE (LOWER(u.identity_key) = ? OR LOWER(s.bk_email) = ?) 
              AND u.is_active = 1 
              AND ur.role IN ('school_bk', 'teacher', 'admin', 'superadmin')
            LIMIT 1
        ");
        $stmt->execute([$email, $email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        $isPassValid = false;
        if ($user && !empty($user['user_password'])) {
            $isPassValid = password_verify($password, $user['user_password']);
        }

        if (!$user || !$isPassValid) {
            http_response_code(401);
            echo json_encode(["error" => "Email atau password salah."]);
            exit;
        }

        reset_rate_limit($pdo, 'school_login');

        $school_id = $user['school_id'] ?? $user['reference_id'];
        $school_name = $user['school_name'] ?? 'Sekolah Mitra';

        $token = generate_jwt([
            'user_id' => $user['user_id'],
            'id' => $school_id,
            'role' => $user['role'],
            'school_id' => $school_id,
            'school_name' => $school_name,
            'email' => $email
        ]);

        $share_link = "https://edupath.co.id/?school=" . urlencode($school_name) . "#/spp";

        echo json_encode([
            "success" => true,
            "token" => $token,
            "school" => [
                "id" => $school_id,
                "name" => $school_name,
                "slug" => $user['slug'] ?? '',
                "bk_name" => $user['bk_name'] ?? 'Koordinator BK',
                "bk_email" => $email,
                "bk_phone" => $user['bk_phone'] ?? '',
                "city" => $user['city'] ?? '',
                "share_link" => $share_link
            ]
        ]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        error_log("School login error: " . $e->getMessage());
        echo json_encode(["error" => "Terjadi kendala saat login sekolah: " . $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------
// GUARD AUTHENTICATION UNTUK ENDPOINT DI BAWAH
// -------------------------------------------------------------
// Security Hardening: Only allow GET parameter token on standalone export download
if (!empty($_GET['token']) && empty($_SERVER['HTTP_AUTHORIZATION']) && ($action === 'export')) {
    $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer ' . safe_str($_GET['token']);
}

$payload = authenticate();
if (!in_array($payload->role, ['school_bk', 'teacher', 'admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Akses khusus Guru BK / Sekolah"]);
    exit;
}

// Resolve current school identity
$current_school_name = $payload->school_name ?? '';
$current_school_id = $payload->school_id ?? ($payload->id ?? '');

// If superadmin or admin, allow passing school_name in query param
if (in_array($payload->role, ['superadmin', 'admin']) && !empty($_GET['school_name'])) {
    $current_school_name = safe_str($_GET['school_name']);
    try {
        $stmt_find = $pdo->prepare("SELECT id, tenant_id FROM schools WHERE LOWER(TRIM(name)) = LOWER(TRIM(?)) LIMIT 1");
        $stmt_find->execute([$current_school_name]);
        $found_sch = $stmt_find->fetch(PDO::FETCH_ASSOC);
        if ($found_sch) {
            if ($payload->role === 'admin' && !empty($payload->tenant_id) && !empty($found_sch['tenant_id']) && $found_sch['tenant_id'] !== $payload->tenant_id && $found_sch['tenant_id'] !== 'default_tenant') {
                http_response_code(403);
                echo json_encode(["error" => "Anda tidak memiliki izin mengakses data sekolah cabang lain."]);
                exit;
            }
            $current_school_id = $found_sch['id'];
        }
    } catch (\Throwable $e) {}
}

// If school name still empty, fetch from schools table
if (empty($current_school_name) && !empty($current_school_id)) {
    $stmt = $pdo->prepare("SELECT name, bk_name, bk_phone, bk_email, city FROM schools WHERE id = ?");
    $stmt->execute([$current_school_id]);
    $sData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($sData) {
        $current_school_name = $sData['name'];
    }
}

// -------------------------------------------------------------
// 3. DASHBOARD SEKOLAH AGREGAT (KPIs, Paket Belajar, RIASEC, PTN)
// -------------------------------------------------------------
if ($action === 'dashboard' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($current_school_name)) {
        http_response_code(400);
        echo json_encode(["error" => "Profil sekolah belum terhubung."]);
        exit;
    }

    // Auto-link any unlinked students who registered with exact school name
    if (!empty($current_school_id) && !empty($current_school_name)) {
        try {
            $pdo->prepare("UPDATE students SET school_id = ? WHERE (school_id IS NULL OR school_id = '') AND LOWER(TRIM(school)) = LOWER(TRIM(?))")
                ->execute([$current_school_id, $current_school_name]);
        } catch (\Throwable $e) {}
    }

    // 1. Total Siswa Terdata (Strict school_id or exact name fallback)
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE (school_id = ? OR (school_id IS NULL AND LOWER(TRIM(school)) = LOWER(TRIM(?))))");
    $stmt->execute([$current_school_id, $current_school_name]);
    $total_students = (int)$stmt->fetchColumn();

    // 2. Paket EduPath Breakdown & Subscriptions
    // Menghitung siswa yang terdaftar di program/paket bimbingan berbayar vs free
    $package_breakdown = [
        'pejuang_ptn' => 0,
        'mandiri' => 0,
        'elite' => 0,
        'free_spp' => 0
    ];
    $paid_package_total = 0;

    $stmt_pkg = $pdo->prepare("
        SELECT 
            COALESCE(p.name, 'Free SPP Diagnostic') as plan_name,
            sub.status as sub_status,
            COUNT(DISTINCT st.id) as total_count
        FROM students st
        LEFT JOIN subscriptions sub ON st.id = sub.student_id AND sub.status = 'active'
        LEFT JOIN plans p ON sub.plan_id = p.id
        WHERE (st.school_id = ? OR (st.school_id IS NULL AND LOWER(TRIM(st.school)) = LOWER(TRIM(?))))
        GROUP BY p.name, sub.status
    ");
    $stmt_pkg->execute([$current_school_id, $current_school_name]);
    $pkg_rows = $stmt_pkg->fetchAll(PDO::FETCH_ASSOC);

    foreach ($pkg_rows as $row) {
        $pName = strtoupper($row['plan_name'] ?? 'FREE');
        $cnt = (int)$row['total_count'];
        if (strpos($pName, 'UTBK') !== false || strpos($pName, 'PEJUANG') !== false) {
            $package_breakdown['pejuang_ptn'] += $cnt;
            $paid_package_total += $cnt;
        } elseif (strpos($pName, 'STARTER') !== false || strpos($pName, 'MANDIRI') !== false) {
            $package_breakdown['mandiri'] += $cnt;
            $paid_package_total += $cnt;
        } elseif (strpos($pName, 'ELITE') !== false || strpos($pName, 'MENTORING') !== false || strpos($pName, 'VIP') !== false) {
            $package_breakdown['elite'] += $cnt;
            $paid_package_total += $cnt;
        }
    }
    // Sisa siswa adalah Free SPP Diagnostic
    $package_breakdown['free_spp'] = max(0, $total_students - $paid_package_total);

    // 3. SPP Assessment Aggregates & RIASEC
    $sppAggregate = [
        'total_canonical_students' => 0,
        'riasec_distribution' => [],
        'strength_distribution' => [],
        'development_distribution' => []
    ];
    try {
        if (file_exists(__DIR__ . '/spp_service.php')) {
            require_once __DIR__ . '/spp_service.php';
            $sppService = new SPPService($pdo);
            $res = $sppService->getSchoolAggregate($current_school_name, null);
            if (is_array($res)) {
                $sppAggregate = $res;
            }
        }
    } catch (\Throwable $e) {
        error_log("SPP aggregate error: " . $e->getMessage());
    }

    // 4. Performa CBT Tryout Nasional Sekolah (SNBT & TKA terisolasi)
    $tryout_stats = [
        'tryout_participants' => 0,
        'total_tryouts_taken' => 0,
        'avg_tryout_score' => 0,
        'avg_snbt_score' => 0,
        'avg_tka_score' => 0,
        'tka_tryouts_taken' => 0
    ];
    try {
        $stmt_to = $pdo->prepare("
            SELECT 
                COUNT(DISTINCT qr.student_id) as tryout_participants,
                COUNT(qr.id) as total_tryouts_taken,
                COALESCE(AVG(CASE WHEN qr.assessment_type = 'SNBT' OR qr.assessment_type IS NULL THEN qr.score END), 0) as avg_snbt_score,
                COALESCE(AVG(CASE WHEN qr.assessment_type = 'TKA' THEN qr.score END), 0) as avg_tka_score,
                COUNT(CASE WHEN qr.assessment_type = 'TKA' THEN 1 END) as tka_tryouts_taken
            FROM quiz_results qr
            JOIN students st ON qr.student_id = st.id
            WHERE (st.school_id = ? OR (st.school_id IS NULL AND LOWER(TRIM(st.school)) = LOWER(TRIM(?))))
        ");
        $stmt_to->execute([$current_school_id, $current_school_name]);
        $row_to = $stmt_to->fetch(PDO::FETCH_ASSOC);
        if ($row_to) {
            $tryout_stats['tryout_participants'] = (int)$row_to['tryout_participants'];
            $tryout_stats['total_tryouts_taken'] = (int)$row_to['total_tryouts_taken'];
            $tryout_stats['avg_snbt_score'] = round((float)$row_to['avg_snbt_score'], 1);
            $tryout_stats['avg_tryout_score'] = $tryout_stats['avg_snbt_score']; // backward-compatible alias
            $tryout_stats['avg_tka_score'] = round((float)$row_to['avg_tka_score'], 1);
            $tryout_stats['tka_tryouts_taken'] = (int)$row_to['tka_tryouts_taken'];
        }
    } catch (\Throwable $e) {
        // Fallback query if assessment_type column not present yet
        try {
            $stmt_fallback = $pdo->prepare("SELECT COUNT(DISTINCT qr.student_id) as tryout_participants, COUNT(qr.id) as total_tryouts_taken, COALESCE(AVG(qr.score), 0) as avg_tryout_score FROM quiz_results qr JOIN students st ON qr.student_id = st.id WHERE (st.school_id = ? OR (st.school_id IS NULL AND LOWER(TRIM(st.school)) = LOWER(TRIM(?))))");
            $stmt_fallback->execute([$current_school_id, $current_school_name]);
            $row_fb = $stmt_fallback->fetch(PDO::FETCH_ASSOC);
            if ($row_fb) {
                $tryout_stats['tryout_participants'] = (int)$row_fb['tryout_participants'];
                $tryout_stats['total_tryouts_taken'] = (int)$row_fb['total_tryouts_taken'];
                $tryout_stats['avg_tryout_score'] = round((float)$row_fb['avg_tryout_score'], 1);
                $tryout_stats['avg_snbt_score'] = $tryout_stats['avg_tryout_score'];
            }
        } catch (\Throwable $e2) {}
    }

    // 5. Top 5 Target Universitas & Jurusan
    $stmt_univ = $pdo->prepare("
        SELECT target_ptn, COUNT(*) as total
        FROM students
        WHERE (school_id = ? OR (school_id IS NULL AND LOWER(TRIM(school)) = LOWER(TRIM(?)))) AND target_ptn IS NOT NULL AND target_ptn != ''
        GROUP BY target_ptn
        ORDER BY total DESC
        LIMIT 5
    ");
    $stmt_univ->execute([$current_school_id, $current_school_name]);
    $top_universities = $stmt_univ->fetchAll(PDO::FETCH_ASSOC);

    // 6. Growth Areas (Tantangan Belajar Bersama)
    $growth_areas = [
        [
            "title" => "Penalaran Matematika & Logika Kuantitatif",
            "desc" => "Mayoritas siswa butuh penguatan pemahaman konsep aljabar dan aritmatika terapan.",
            "pct" => 64
        ],
        [
            "title" => "Manajemen Waktu & Konsistensi Belajar",
            "desc" => "Siswa masih terkendala disiplin latihan soal berkala di luar jam sekolah.",
            "pct" => 52
        ],
        [
            "title" => "Literasi Bahasa Inggris Akademik",
            "desc" => "Pemahaman teks komparatif panjang dan inferensi ide pokok masih perlu pembiasaan.",
            "pct" => 45
        ]
    ];

    echo json_encode([
        "success" => true,
        "school_name" => $current_school_name,
        "kpis" => [
            "total_students" => $total_students,
            "paid_package_students" => $paid_package_total,
            "free_diagnostic_students" => $package_breakdown['free_spp'],
            "tryout_participants" => (int)$tryout_stats['tryout_participants'],
            "average_tryout_score" => round((float)($tryout_stats['avg_tryout_score'] ?? 0), 1),
            "avg_snbt_score" => round((float)($tryout_stats['avg_snbt_score'] ?? 0), 1),
            "avg_tka_score" => round((float)($tryout_stats['avg_tka_score'] ?? 0), 1),
            "tka_tryouts_taken" => (int)($tryout_stats['tka_tryouts_taken'] ?? 0),
            "spp_completed_students" => $sppAggregate['total_canonical_students'] ?? 0
        ],
        "package_breakdown" => $package_breakdown,
        "riasec_distribution" => $sppAggregate['riasec_distribution'] ?? [],
        "strength_distribution" => $sppAggregate['strength_distribution'] ?? [],
        "development_distribution" => $sppAggregate['development_distribution'] ?? [],
        "top_universities" => $top_universities,
        "growth_areas" => $growth_areas,
        "share_link" => "https://edupath.co.id/?school=" . urlencode($current_school_name) . "#/spp"
    ]);
    exit;
}

// -------------------------------------------------------------
// 4. DAFTAR SISWA & PAKET BELAJAR (ROSTER)
// -------------------------------------------------------------
if ($action === 'students' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($current_school_name)) {
        http_response_code(400);
        echo json_encode(["error" => "Profil sekolah belum terhubung."]);
        exit;
    }

    $search = safe_str($_GET['search'] ?? '');
    $filter_package = safe_str($_GET['package'] ?? 'all');
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 25)));
    $offset = ($page - 1) * $limit;

    $where = ["(st.school_id = ? OR (st.school_id IS NULL AND LOWER(TRIM(st.school)) = LOWER(TRIM(?))))"];
    $params = [$current_school_id, $current_school_name];

    if (!empty($search)) {
        $where[] = "(st.name LIKE ? OR st.email LIKE ? OR st.target_ptn LIKE ?)";
        $params[] = "%$search%";
        $params[] = "%$search%";
        $params[] = "%$search%";
    }

    if ($filter_package === 'paid') {
        $where[] = "sub.status = 'active'";
    } elseif ($filter_package === 'free') {
        $where[] = "(sub.id IS NULL OR sub.status != 'active')";
    } elseif ($filter_package === 'pejuang_ptn') {
        $where[] = "(p.name LIKE '%UTBK%' OR p.name LIKE '%Pejuang%') AND sub.status = 'active'";
    } elseif ($filter_package === 'mandiri') {
        $where[] = "(p.name LIKE '%Starter%' OR p.name LIKE '%Mandiri%') AND sub.status = 'active'";
    } elseif ($filter_package === 'elite') {
        $where[] = "(p.name LIKE '%Elite%' OR p.name LIKE '%VIP%') AND sub.status = 'active'";
    }

    $where_sql = implode(" AND ", $where);

    try {
        // Total Count
        $stmt_c = $pdo->prepare("
            SELECT COUNT(DISTINCT st.id)
            FROM students st
            LEFT JOIN subscriptions sub ON st.id = sub.student_id AND sub.status = 'active'
            LEFT JOIN plans p ON sub.plan_id = p.id
            WHERE $where_sql
        ");
        $stmt_c->execute($params);
        $total_records = (int)$stmt_c->fetchColumn();

        // Query Data Siswa Roster
        $sql = "
            SELECT 
                st.id,
                st.name,
                st.email,
                COALESCE(st.phone, '-') as phone,
                st.target_ptn,
                st.created_at,
                COALESCE(p.name, 'Free SPP Diagnostic') as package_name,
                COALESCE(p.name, 'FREE') as package_code,
                COALESCE(sub.status, 'free') as package_status,
                sub.expires_at as package_expires_at,
                (
                    SELECT s.riasec_top3 
                    FROM spp_scores s 
                    JOIN spp_attempts att ON s.attempt_id = att.id 
                    WHERE att.student_id = st.id AND att.scoring_status = 'SCORED'
                    ORDER BY att.submitted_at DESC 
                    LIMIT 1
                ) as riasec_top3,
                (
                    SELECT COUNT(*) 
                    FROM quiz_attempts qa 
                    WHERE qa.student_id = st.id
                ) as tryout_count,
                (
                    SELECT qr.score 
                    FROM quiz_results qr 
                    WHERE qr.student_id = st.id
                    ORDER BY qr.created_at DESC 
                    LIMIT 1
                ) as latest_tryout_score
            FROM students st
            LEFT JOIN subscriptions sub ON st.id = sub.student_id AND sub.status = 'active'
            LEFT JOIN plans p ON sub.plan_id = p.id
            WHERE $where_sql
            ORDER BY st.created_at DESC
            LIMIT ? OFFSET ?
        ";

    $params[] = $limit;
    $params[] = $offset;

    $stmt = $pdo->prepare($sql);
    foreach ($params as $idx => $val) {
        $stmt->bindValue($idx + 1, $val, is_int($val) ? PDO::PARAM_INT : PDO::PARAM_STR);
    }
    $stmt->execute();
    $students = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format output
    foreach ($students as &$stu) {
        $top3 = json_decode($stu['riasec_top3'] ?? '', true);
        $stu['riasec_badge'] = (is_array($top3) && count($top3) > 0) ? implode('-', $top3) : 'Belum Tes';
        $stu['latest_tryout_score'] = $stu['latest_tryout_score'] !== null ? round((float)$stu['latest_tryout_score'], 1) : null;
    }

    echo json_encode([
        "success" => true,
        "total" => $total_records,
        "page" => $page,
        "limit" => $limit,
        "data" => $students,
        "students" => $students
    ]);
    exit;
    } catch (\Throwable $e) {
        error_log("School students roster error: " . $e->getMessage());
        echo json_encode([
            "success" => true,
            "total" => 0,
            "page" => $page,
            "limit" => $limit,
            "data" => [],
            "students" => []
        ]);
        exit;
    }
}

// -------------------------------------------------------------
// 4b. REKAP ASESMEN SPP RIASEC ANGKATAN
// -------------------------------------------------------------
if ($action === 'spp_recap' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($current_school_name)) {
        http_response_code(400);
        echo json_encode(["error" => "Profil sekolah belum terhubung."]);
        exit;
    }

    $sppAggregate = [
        'total_canonical_students' => 0,
        'riasec_distribution' => [],
        'strength_distribution' => [],
        'development_distribution' => []
    ];
    try {
        if (file_exists(__DIR__ . '/spp_service.php')) {
            require_once __DIR__ . '/spp_service.php';
            $sppService = new SPPService($pdo);
            $res = $sppService->getSchoolAggregate($current_school_name, null);
            if (is_array($res)) {
                $sppAggregate = $res;
            }
        }
    } catch (\Throwable $e) {}

    echo json_encode([
        "success" => true,
        "school_name" => $current_school_name,
        "aggregate" => $sppAggregate,
        "riasec_distribution" => $sppAggregate['riasec_distribution'] ?? [],
        "strength_distribution" => $sppAggregate['strength_distribution'] ?? [],
        "development_distribution" => $sppAggregate['development_distribution'] ?? []
    ]);
    exit;
}

// -------------------------------------------------------------
// 5. EKSPOR DATA KE CSV/EXCEL
// -------------------------------------------------------------
if ($action === 'export' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (empty($current_school_name)) {
        http_response_code(400);
        echo "Profil sekolah belum terhubung.";
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            st.name,
            st.email,
            COALESCE(st.phone, '-') as phone,
            st.target_ptn,
            COALESCE(p.name, 'Free SPP Diagnostic') as package_name,
            COALESCE(sub.status, 'free') as package_status,
            sub.expires_at as package_expires_at,
            st.created_at
        FROM students st
        LEFT JOIN subscriptions sub ON st.id = sub.student_id AND sub.status = 'active'
        LEFT JOIN plans p ON sub.plan_id = p.id
        WHERE st.school_id = ? OR (st.school_id IS NULL AND LOWER(TRIM(st.school)) = LOWER(TRIM(?)))
        ORDER BY st.created_at DESC
    ");
    $stmt->execute([$current_school_id, $current_school_name]);
    $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=rekap_siswa_' . preg_replace('/[^a-z0-9]/i', '_', strtolower($current_school_name)) . '.csv');

    $output = fopen('php://output', 'w');
    // UTF-8 BOM for Excel compatibility
    fputs($output, "\xEF\xBB\xBF");

    // Anti-CSV Formula Injection Sanitizer
    $sanitize_csv = function($val) {
        if ($val === null) return '';
        $str = (string)$val;
        if (in_array(substr($str, 0, 1), ['=', '+', '-', '@', "\t", "\r"], true)) {
            return "'" . $str;
        }
        return $str;
    };

    fputcsv($output, ['Nama Siswa', 'Email', 'No. WhatsApp', 'Target PTN / Jurusan', 'Program / Paket EduPath', 'Status Paket', 'Masa Aktif Paket', 'Tanggal Bergabung']);

    foreach ($rows as $r) {
        fputcsv($output, [
            $sanitize_csv($r['name']),
            $sanitize_csv($r['email']),
            $sanitize_csv($r['phone']),
            $sanitize_csv($r['target_ptn']),
            $sanitize_csv($r['package_name']),
            $sanitize_csv(strtoupper($r['package_status'])),
            $r['package_expires_at'] ? date('d-m-Y', strtotime($r['package_expires_at'])) : '-',
            date('d-m-Y H:i', strtotime($r['created_at']))
        ]);
    }
    fclose($output);
    exit;
}

// -------------------------------------------------------------
// 6. UPDATE PROFIL SEKOLAH / GURU BK
// -------------------------------------------------------------
if ($action === 'update_profile' && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'PUT'])) {
    $name = trim($input['name'] ?? ($input['school_name'] ?? ''));
    $bk_name = trim($input['bk_name'] ?? ($input['bk_coordinator_name'] ?? ''));
    $email = strtolower(trim($input['email'] ?? ($input['bk_email'] ?? '')));
    $phone = trim($input['phone'] ?? ($input['bk_phone'] ?? ''));
    $city = trim($input['city'] ?? '');
    $province = trim($input['province'] ?? '');
    $npsn = trim($input['npsn'] ?? '');
    $estimated_students = (int)($input['estimated_students'] ?? 200);
    $new_password = $input['new_password'] ?? ($input['password'] ?? '');

    if (empty($name) || empty($bk_name) || empty($email)) {
        http_response_code(400);
        echo json_encode(["error" => "Nama Sekolah, Koordinator BK, dan Email wajib diisi"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Update schools table
        $stmt = $pdo->prepare("
            UPDATE schools 
            SET name = ?, bk_name = ?, bk_email = ?, bk_phone = ?, city = ?, province = ?, npsn = ?, estimated_students = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $bk_name, $email, $phone, $city, $province, $npsn, $estimated_students, $current_school_id]);

        // 2. Sync linked users table
        $stmtUser = $pdo->prepare("SELECT user_id FROM user_roles WHERE reference_id = ? AND role = 'school_bk' LIMIT 1");
        $stmtUser->execute([$current_school_id]);
        $uId = $stmtUser->fetchColumn();

        if ($uId) {
            $pdo->prepare("UPDATE users SET identity_key = ?, display_name = ? WHERE id = ?")->execute([$email, $bk_name, $uId]);
            if (!empty($new_password)) {
                if (strlen($new_password) < 6) {
                    $pdo->rollBack();
                    http_response_code(400);
                    echo json_encode(["error" => "Password minimal 6 karakter"]);
                    exit;
                }
                $hashed = password_hash($new_password, PASSWORD_BCRYPT);
                $pdo->prepare("UPDATE users SET password = ? WHERE id = ?")->execute([$hashed, $uId]);
            }
        }

        $pdo->commit();

        $share_link = "https://edupath.co.id/?school=" . urlencode($name) . "#/spp";

        echo json_encode([
            "success" => true,
            "message" => "Pengaturan profil sekolah berhasil diperbarui",
            "school" => [
                "id" => $current_school_id,
                "name" => $name,
                "bk_name" => $bk_name,
                "bk_email" => $email,
                "bk_phone" => $phone,
                "city" => $city,
                "province" => $province,
                "npsn" => $npsn,
                "estimated_students" => $estimated_students,
                "share_link" => $share_link
            ]
        ]);
        exit;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Gagal memperbarui profil sekolah: " . $e->getMessage()]);
        exit;
    }
}

http_response_code(404);
echo json_encode(["error" => "Action tidak valid."]);
