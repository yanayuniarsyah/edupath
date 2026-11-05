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

    // Phase 15: School Intelligence Tables
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS report_cards (
              id VARCHAR(36) NOT NULL PRIMARY KEY,
              tenant_id VARCHAR(36) NOT NULL DEFAULT 'default_tenant',
              school_id VARCHAR(36) NOT NULL,
              student_id VARCHAR(36) NOT NULL,
              education_level ENUM('SD', 'SMP', 'SMA') NOT NULL DEFAULT 'SMA',
              academic_year VARCHAR(20) NOT NULL,
              semester ENUM('ganjil', 'genap') NOT NULL DEFAULT 'ganjil',
              class_level VARCHAR(20) NOT NULL,
              class_name VARCHAR(50) NULL,
              curriculum VARCHAR(50) NOT NULL DEFAULT 'Kurikulum Merdeka',
              status ENUM('draft', 'submitted', 'verified', 'rejected', 'locked') NOT NULL DEFAULT 'draft',
              notes TEXT NULL,
              verified_by VARCHAR(36) NULL,
              verified_at DATETIME NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              UNIQUE KEY uq_student_report_period (student_id, academic_year, semester),
              KEY idx_rc_school (school_id),
              KEY idx_rc_level (education_level),
              KEY idx_rc_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS report_card_details (
              id VARCHAR(36) NOT NULL PRIMARY KEY,
              report_card_id VARCHAR(36) NOT NULL,
              subject_name VARCHAR(100) NOT NULL,
              subject_code VARCHAR(50) NULL,
              score DECIMAL(5, 2) NOT NULL DEFAULT 0.00,
              letter_grade VARCHAR(5) NULL,
              kkm DECIMAL(5, 2) NOT NULL DEFAULT 75.00,
              competency_achievement TEXT NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_rcd_card (report_card_id),
              KEY idx_rcd_subject (subject_name)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS guidance_records (
              id VARCHAR(36) NOT NULL PRIMARY KEY,
              tenant_id VARCHAR(36) NOT NULL DEFAULT 'default_tenant',
              school_id VARCHAR(36) NOT NULL,
              student_id VARCHAR(36) NOT NULL,
              counselor_name VARCHAR(150) NOT NULL,
              category ENUM('akademik', 'karir_studi', 'kedisiplinan', 'pribadi') NOT NULL DEFAULT 'akademik',
              priority ENUM('rendah', 'sedang', 'tinggi', 'kritis') NOT NULL DEFAULT 'sedang',
              issue_summary TEXT NOT NULL,
              action_plan TEXT NOT NULL,
              status ENUM('open', 'in_progress', 'resolved', 'monitoring') NOT NULL DEFAULT 'open',
              follow_up_date DATE NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_gr_school (school_id),
              KEY idx_gr_student (student_id),
              KEY idx_gr_priority (priority),
              KEY idx_gr_status (status)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS admission_trackings (
              id VARCHAR(36) NOT NULL PRIMARY KEY,
              tenant_id VARCHAR(36) NOT NULL DEFAULT 'default_tenant',
              school_id VARCHAR(36) NOT NULL,
              student_id VARCHAR(36) NOT NULL,
              target_university VARCHAR(150) NOT NULL,
              target_faculty VARCHAR(150) NULL,
              target_major VARCHAR(150) NOT NULL,
              selection_path ENUM('SNBP', 'SNBT', 'MANDIRI', 'KEDINASAN', 'LAINNYA') NOT NULL DEFAULT 'SNBT',
              priority_order INT NOT NULL DEFAULT 1,
              status ENUM('rencana', 'terdaftar', 'lolos', 'tidak_lolos', 'cadangan') NOT NULL DEFAULT 'rencana',
              notes TEXT NULL,
              created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
              updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
              KEY idx_at_school (school_id),
              KEY idx_at_student (student_id),
              KEY idx_at_path (selection_path)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
        ");
    } catch (\Throwable $e) {
        error_log("Phase 15 schema auto-creation error: " . $e->getMessage());
    }
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

// Fallback for Admin/Superadmin if no specific school selected: choose first active school
if (empty($current_school_name)) {
    try {
        $stmt_first = $pdo->query("SELECT id, name FROM schools WHERE is_active IS NULL OR is_active != 0 ORDER BY name ASC LIMIT 1");
        $first_sch = $stmt_first->fetch(PDO::FETCH_ASSOC);
        if ($first_sch) {
            $current_school_id = $first_sch['id'];
            $current_school_name = $first_sch['name'];
        }
    } catch (\Throwable $e) {}
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

// =============================================================
// PHASE 15: EDUPATH SCHOOL INTELLIGENCE API ENDPOINTS
// PRD: EDUPATH SCHOOL INTELLIGENCE (v1.2, Baseline)
// =============================================================

// Helper: Standard subjects configuration per level & curriculum
function get_standard_subjects_config($level = 'SMA', $curriculum = 'Kurikulum Merdeka') {
    $lvl = strtoupper(trim($level));
    if ($lvl === 'SD') {
        return [
            ["code" => "MAT_SD", "name" => "Matematika", "kkm" => 70, "group" => "Wajib"],
            ["code" => "BIN_SD", "name" => "Bahasa Indonesia", "kkm" => 75, "group" => "Wajib"],
            ["code" => "IPAS_SD", "name" => "Ilmu Pengetahuan Alam dan Sosial (IPAS)", "kkm" => 72, "group" => "Wajib"],
            ["code" => "PPKN_SD", "name" => "Pendidikan Pancasila", "kkm" => 75, "group" => "Wajib"],
            ["code" => "ENG_SD", "name" => "Bahasa Inggris", "kkm" => 70, "group" => "Wajib"],
            ["code" => "SENI_SD", "name" => "Seni Budaya & Prakarya", "kkm" => 75, "group" => "Wajib"],
            ["code" => "PJOK_SD", "name" => "PJOK", "kkm" => 75, "group" => "Wajib"]
        ];
    } elseif ($lvl === 'SMP') {
        return [
            ["code" => "MAT_SMP", "name" => "Matematika", "kkm" => 72, "group" => "Wajib"],
            ["code" => "BIN_SMP", "name" => "Bahasa Indonesia", "kkm" => 75, "group" => "Wajib"],
            ["code" => "ENG_SMP", "name" => "Bahasa Inggris", "kkm" => 72, "group" => "Wajib"],
            ["code" => "IPA_SMP", "name" => "IPA Terpadu", "kkm" => 72, "group" => "Wajib"],
            ["code" => "IPS_SMP", "name" => "IPS Terpadu", "kkm" => 75, "group" => "Wajib"],
            ["code" => "PPKN_SMP", "name" => "Pendidikan Pancasila", "kkm" => 75, "group" => "Wajib"],
            ["code" => "INF_SMP", "name" => "Informatika", "kkm" => 75, "group" => "Wajib"],
            ["code" => "PJOK_SMP", "name" => "PJOK", "kkm" => 75, "group" => "Wajib"],
            ["code" => "SENI_SMP", "name" => "Seni Budaya", "kkm" => 75, "group" => "Wajib"]
        ];
    } else { // SMA
        return [
            ["code" => "MAT_SMA", "name" => "Matematika Wajib", "kkm" => 75, "group" => "Wajib"],
            ["code" => "BIN_SMA", "name" => "Bahasa Indonesia", "kkm" => 75, "group" => "Wajib"],
            ["code" => "ENG_SMA", "name" => "Bahasa Inggris", "kkm" => 75, "group" => "Wajib"],
            ["code" => "PPKN_SMA", "name" => "Pendidikan Pancasila", "kkm" => 75, "group" => "Wajib"],
            ["code" => "SEJ_SMA", "name" => "Sejarah", "kkm" => 75, "group" => "Wajib"],
            // Mata Pelajaran Pilihan / Peminatan
            ["code" => "FIS_SMA", "name" => "Fisika", "kkm" => 75, "group" => "Peminatan Saintek"],
            ["code" => "KIM_SMA", "name" => "Kimia", "kkm" => 75, "group" => "Peminatan Saintek"],
            ["code" => "BIO_SMA", "name" => "Biologi", "kkm" => 75, "group" => "Peminatan Saintek"],
            ["code" => "MAT_TL_SMA", "name" => "Matematika Tingkat Lanjut", "kkm" => 75, "group" => "Peminatan Saintek"],
            ["code" => "EKO_SMA", "name" => "Ekonomi", "kkm" => 75, "group" => "Peminatan Soshum"],
            ["code" => "SOS_SMA", "name" => "Sosiologi", "kkm" => 75, "group" => "Peminatan Soshum"],
            ["code" => "GEO_SMA", "name" => "Geografi", "kkm" => 75, "group" => "Peminatan Soshum"]
        ];
    }
}

// -------------------------------------------------------------
// 1. REPORT TEMPLATE METADATA (Untuk konfigurasi & template Excel)
// -------------------------------------------------------------
// -------------------------------------------------------------
// 0. SEED DUMMY DATA ACTION (Untuk UAT & Demo PRD v1.2)
// -------------------------------------------------------------
if ($action === 'seed_dummy') {
    try {
        require_once dirname(__DIR__) . '/api/database/seeds/seed_school_intelligence.php';
        $res = seed_school_intelligence_data($pdo);
        echo json_encode(["success" => true, "message" => "Data dummy School Intelligence berhasil disuntikkan.", "details" => $res]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(["error" => "Gagal seeding dummy: " . $e->getMessage()]);
        exit;
    }
}

if ($action === 'report_template' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $level = $_GET['level'] ?? 'SMA';
    $curriculum = $_GET['curriculum'] ?? 'Kurikulum Merdeka';
    $subjects = get_standard_subjects_config($level, $curriculum);

    echo json_encode([
        "success" => true,
        "level" => strtoupper($level),
        "curriculum" => $curriculum,
        "subjects" => $subjects,
        "sample_headers" => array_merge(
            ["NISN / ID", "Nama Siswa", "Email Siswa", "Jenjang", "Kelas", "Tahun Ajaran", "Semester"],
            array_column($subjects, 'name')
        )
    ]);
    exit;
}

// -------------------------------------------------------------
// 2. REPORT CARDS ROSTER (List Nilai Rapor dengan Filter & Rata-rata)
// -------------------------------------------------------------
if ($action === 'report_cards' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $page = max(1, (int)($_GET['page'] ?? 1));
    $limit = max(1, min(100, (int)($_GET['limit'] ?? 20)));
    $offset = ($page - 1) * $limit;

    $academic_year = $_GET['academic_year'] ?? '';
    $semester = $_GET['semester'] ?? '';
    $class_level = $_GET['class_level'] ?? '';
    $education_level = $_GET['education_level'] ?? '';
    $status = $_GET['status'] ?? '';
    $search = trim($_GET['search'] ?? '');

    $where = ["(rc.school_id = :school_id OR LOWER(TRIM(st.school)) = LOWER(TRIM(:school_name)))"];
    $params = [
        ":school_id" => $current_school_id,
        ":school_name" => $current_school_name
    ];

    if (!empty($academic_year)) {
        $where[] = "rc.academic_year = :academic_year";
        $params[":academic_year"] = $academic_year;
    }
    if (!empty($semester)) {
        $where[] = "rc.semester = :semester";
        $params[":semester"] = $semester;
    }
    if (!empty($class_level)) {
        $where[] = "rc.class_level = :class_level";
        $params[":class_level"] = $class_level;
    }
    if (!empty($education_level)) {
        $where[] = "rc.education_level = :education_level";
        $params[":education_level"] = strtoupper($education_level);
    }
    if (!empty($status)) {
        $where[] = "rc.status = :status";
        $params[":status"] = $status;
    }
    if (!empty($search)) {
        $where[] = "(st.name LIKE :search OR st.email LIKE :search OR rc.class_name LIKE :search)";
        $params[":search"] = "%$search%";
    }

    $whereSql = implode(" AND ", $where);

    // Count query
    $countStmt = $pdo->prepare("
        SELECT COUNT(DISTINCT rc.id)
        FROM report_cards rc
        JOIN students st ON rc.student_id = st.id
        WHERE $whereSql
    ");
    $countStmt->execute($params);
    $total = (int)$countStmt->fetchColumn();

    // Data query with aggregate scores (Strict ANSI SQL & ONLY_FULL_GROUP_BY compliant)
    $queryStmt = $pdo->prepare("
        SELECT 
            rc.id, rc.student_id, rc.education_level, rc.academic_year, rc.semester,
            rc.class_level, rc.class_name, rc.curriculum, rc.status, rc.notes,
            rc.verified_at, rc.created_at, rc.updated_at,
            st.name as student_name, st.email as student_email, st.phone as student_phone,
            st.school as student_school, st.target_ptn, st.plan as student_plan,
            COALESCE(agg.total_subjects, 0) as total_subjects,
            COALESCE(agg.average_score, 0) as average_score,
            COALESCE(agg.max_score, 0) as max_score,
            COALESCE(agg.min_score, 0) as min_score
        FROM report_cards rc
        JOIN students st ON rc.student_id = st.id
        LEFT JOIN (
            SELECT 
                report_card_id,
                COUNT(id) as total_subjects,
                ROUND(AVG(score), 2) as average_score,
                MAX(score) as max_score,
                MIN(score) as min_score
            FROM report_card_details
            GROUP BY report_card_id
        ) agg ON rc.id = agg.report_card_id
        WHERE $whereSql
        ORDER BY rc.academic_year DESC, rc.semester DESC, st.name ASC
        LIMIT :limit OFFSET :offset
    ");

    foreach ($params as $k => $v) {
        $queryStmt->bindValue($k, $v);
    }
    $queryStmt->bindValue(':limit', $limit, PDO::PARAM_INT);
    $queryStmt->bindValue(':offset', $offset, PDO::PARAM_INT);
    $queryStmt->execute();
    $reportCards = $queryStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "total" => $total,
        "page" => $page,
        "limit" => $limit,
        "report_cards" => $reportCards
    ]);
    exit;
}

// -------------------------------------------------------------
// 3. REPORT CARD DETAIL (Single Rapor dengan Rincian per Mapel)
// -------------------------------------------------------------
if ($action === 'report_card_detail' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $id = safe_str($_GET['id'] ?? '');
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(["error" => "Parameter ID rapor wajib diisi."]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT 
            rc.*,
            st.name as student_name, st.email as student_email, st.phone as student_phone,
            st.school as student_school, st.target_ptn, st.plan as student_plan
        FROM report_cards rc
        JOIN students st ON rc.student_id = st.id
        WHERE rc.id = ? AND (rc.school_id = ? OR LOWER(TRIM(st.school)) = LOWER(TRIM(?)))
    ");
    $stmt->execute([$id, $current_school_id, $current_school_name]);
    $reportCard = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reportCard) {
        http_response_code(404);
        echo json_encode(["error" => "Data rapor tidak ditemukan atau tidak berwenang."]);
        exit;
    }

    $detailStmt = $pdo->prepare("
        SELECT id, subject_name, subject_code, score, letter_grade, kkm, competency_achievement
        FROM report_card_details
        WHERE report_card_id = ?
        ORDER BY subject_name ASC
    ");
    $detailStmt->execute([$id]);
    $reportCard['details'] = $detailStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "report_card" => $reportCard]);
    exit;
}

// -------------------------------------------------------------
// 4. SAVE REPORT CARD (Manual Input / Edit Draft Rapor)
// -------------------------------------------------------------
if ($action === 'save_report_card' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $student_id = safe_str($input['student_id'] ?? '');
    $education_level = strtoupper(safe_str($input['education_level'] ?? 'SMA'));
    $academic_year = safe_str($input['academic_year'] ?? '2026/2027');
    $semester = strtolower(safe_str($input['semester'] ?? 'ganjil'));
    $class_level = safe_str($input['class_level'] ?? 'Kelas 12');
    $class_name = safe_str($input['class_name'] ?? '');
    $curriculum = safe_str($input['curriculum'] ?? 'Kurikulum Merdeka');
    $status = safe_str($input['status'] ?? 'draft');
    $notes = safe_str($input['notes'] ?? '');
    $details = $input['details'] ?? [];

    if (empty($student_id) || empty($academic_year)) {
        http_response_code(400);
        echo json_encode(["error" => "ID Siswa dan Tahun Ajaran wajib diisi."]);
        exit;
    }

    // Verify student belongs to this school
    $stCheck = $pdo->prepare("
        SELECT id, name, school, school_id 
        FROM students 
        WHERE id = ? AND (school_id = ? OR LOWER(TRIM(school)) = LOWER(TRIM(?)))
    ");
    $stCheck->execute([$student_id, $current_school_id, $current_school_name]);
    $student = $stCheck->fetch(PDO::FETCH_ASSOC);
    if (!$student) {
        http_response_code(403);
        echo json_encode(["error" => "Siswa tidak terdaftar pada sekolah Anda."]);
        exit;
    }

    $pdo->beginTransaction();
    try {
        // Check if report card already exists for this period
        $checkRc = $pdo->prepare("
            SELECT id, status FROM report_cards 
            WHERE student_id = ? AND academic_year = ? AND semester = ?
        ");
        $checkRc->execute([$student_id, $academic_year, $semester]);
        $existingRc = $checkRc->fetch(PDO::FETCH_ASSOC);

        if ($existingRc && in_array($existingRc['status'], ['verified', 'locked']) && !in_array($payload->role, ['superadmin', 'admin'])) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["error" => "Rapor periode ini telah diverifikasi/dikunci. Hubungi Kepala Sekolah/Admin untuk koreksi."]);
            exit;
        }

        $report_card_id = $existingRc['id'] ?? generate_school_uuid();

        if ($existingRc) {
            $upRc = $pdo->prepare("
                UPDATE report_cards SET 
                    education_level = ?, class_level = ?, class_name = ?, 
                    curriculum = ?, status = ?, notes = ?, updated_at = NOW()
                WHERE id = ?
            ");
            $upRc->execute([$education_level, $class_level, $class_name, $curriculum, $status, $notes, $report_card_id]);
        } else {
            $inRc = $pdo->prepare("
                INSERT INTO report_cards (
                    id, tenant_id, school_id, student_id, education_level, 
                    academic_year, semester, class_level, class_name, curriculum, status, notes
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $inRc->execute([
                $report_card_id, $payload->tenant_id ?? 'default_tenant', $current_school_id,
                $student_id, $education_level, $academic_year, $semester,
                $class_level, $class_name, $curriculum, $status, $notes
            ]);
        }

        // Replace details
        $pdo->prepare("DELETE FROM report_card_details WHERE report_card_id = ?")->execute([$report_card_id]);

        $inDetail = $pdo->prepare("
            INSERT INTO report_card_details (
                id, report_card_id, subject_name, subject_code, score, letter_grade, kkm, competency_achievement
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");

        foreach ($details as $d) {
            $sName = safe_str($d['subject_name'] ?? '');
            if (empty($sName)) continue;
            $sCode = safe_str($d['subject_code'] ?? '');
            $score = max(0, min(100, (float)($d['score'] ?? 0)));
            $kkm = max(0, min(100, (float)($d['kkm'] ?? 75)));
            
            // Auto compute letter grade if not supplied
            $letter = safe_str($d['letter_grade'] ?? '');
            if (empty($letter)) {
                if ($score >= 90) $letter = 'A';
                elseif ($score >= 80) $letter = 'B';
                elseif ($score >= 70) $letter = 'C';
                else $letter = 'D';
            }

            $comp = safe_str($d['competency_achievement'] ?? '');
            $inDetail->execute([
                generate_school_uuid(), $report_card_id, $sName, $sCode,
                $score, $letter, $kkm, $comp
            ]);
        }

        $pdo->commit();

        echo json_encode([
            "success" => true,
            "message" => "Nilai rapor berhasil disimpan.",
            "report_card_id" => $report_card_id,
            "status" => $status
        ]);
        exit;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Gagal menyimpan rapor: " . $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------
// 5. VERIFY REPORT CARD (Verifikasi / Approval Status Rapor)
// -------------------------------------------------------------
if ($action === 'verify_report_card' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = safe_str($input['id'] ?? '');
    $newStatus = safe_str($input['status'] ?? 'verified'); // verified, rejected, locked
    $verifyNotes = safe_str($input['notes'] ?? '');

    if (empty($id) || !in_array($newStatus, ['verified', 'rejected', 'locked', 'draft'])) {
        http_response_code(400);
        echo json_encode(["error" => "Parameter ID dan Status valid ('verified', 'rejected', 'locked', 'draft') wajib diisi."]);
        exit;
    }

    try {
        $stmt = $pdo->prepare("
            UPDATE report_cards SET 
                status = ?, notes = CASE WHEN ? != '' THEN ? ELSE notes END,
                verified_by = ?, verified_at = NOW(), updated_at = NOW()
            WHERE id = ? AND school_id = ?
        ");
        $stmt->execute([$newStatus, $verifyNotes, $verifyNotes, $payload->id ?? 'counselor', $id, $current_school_id]);

        echo json_encode([
            "success" => true,
            "message" => "Status rapor berhasil diperbarui menjadi " . strtoupper($newStatus),
            "status" => $newStatus
        ]);
        exit;
    } catch (\Throwable $e) {
        http_response_code(500);
        echo json_encode(["error" => "Gagal verifikasi rapor: " . $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------
// 6. IMPORT PREVIEW (Validasi Baris Excel Sebelum Commit)
// -------------------------------------------------------------
if ($action === 'report_import_preview' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rows = $input['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) {
        http_response_code(400);
        echo json_encode(["error" => "Data baris Excel tidak ditemukan."]);
        exit;
    }

    // Fetch all students for this school into memory for fast lookup
    $stStmt = $pdo->prepare("
        SELECT id, name, email, phone 
        FROM students 
        WHERE school_id = ? OR LOWER(TRIM(school)) = LOWER(TRIM(?))
    ");
    $stStmt->execute([$current_school_id, $current_school_name]);
    $schoolStudents = $stStmt->fetchAll(PDO::FETCH_ASSOC);

    $previewList = [];
    $validCount = 0;
    $warningCount = 0;
    $errorCount = 0;

    foreach ($rows as $idx => $r) {
        $rowNum = $idx + 1;
        $studentIdentifier = trim($r['nisn'] ?? ($r['email'] ?? ($r['name'] ?? '')));
        $studentName = trim($r['name'] ?? ($r['nama'] ?? ''));
        $studentEmail = trim($r['email'] ?? '');
        $academicYear = trim($r['academic_year'] ?? ($r['tahun_ajaran'] ?? '2026/2027'));
        $semester = strtolower(trim($r['semester'] ?? 'ganjil'));
        $classLevel = trim($r['class_level'] ?? ($r['kelas'] ?? 'Kelas 12'));
        $educationLevel = strtoupper(trim($r['education_level'] ?? ($r['jenjang'] ?? 'SMA')));
        $curriculum = trim($r['curriculum'] ?? 'Kurikulum Merdeka');
        $subjectsData = $r['subjects'] ?? [];

        // Match student
        $matchedStudent = null;
        foreach ($schoolStudents as $st) {
            if (!empty($studentEmail) && strtolower($st['email']) === strtolower($studentEmail)) {
                $matchedStudent = $st;
                break;
            }
            if (!empty($studentName) && strtolower(trim($st['name'])) === strtolower($studentName)) {
                $matchedStudent = $st;
                break;
            }
        }

        $rowStatus = 'VALID';
        $issues = [];

        if (!$matchedStudent) {
            $rowStatus = 'ERROR';
            $issues[] = "Siswa '$studentName' ($studentEmail) tidak ditemukan di daftar siswa sekolah ini.";
        }

        if (empty($subjectsData) || !is_array($subjectsData)) {
            $rowStatus = 'ERROR';
            $issues[] = "Tidak ada nilai mata pelajaran yang terbaca pada baris ini.";
        } else {
            foreach ($subjectsData as $sName => $sVal) {
                if (!is_numeric($sVal) || $sVal < 0 || $sVal > 100) {
                    $rowStatus = ($rowStatus === 'ERROR') ? 'ERROR' : 'WARNING';
                    $issues[] = "Nilai $sName ($sVal) tidak valid (harus 0-100).";
                }
            }
        }

        // Check duplicate period
        $isDuplicate = false;
        if ($matchedStudent) {
            $chkDup = $pdo->prepare("
                SELECT id, status FROM report_cards 
                WHERE student_id = ? AND academic_year = ? AND semester = ?
            ");
            $chkDup->execute([$matchedStudent['id'], $academicYear, $semester]);
            $existing = $chkDup->fetch(PDO::FETCH_ASSOC);
            if ($existing) {
                $isDuplicate = true;
                if ($rowStatus !== 'ERROR') {
                    $rowStatus = 'WARNING';
                    $issues[] = "Data rapor periode ini sudah ada (akan menimpa data yang ada jika dilanjutkan).";
                }
            }
        }

        if ($rowStatus === 'VALID') $validCount++;
        elseif ($rowStatus === 'WARNING') $warningCount++;
        else $errorCount++;

        $previewList[] = [
            "row_num" => $rowNum,
            "student_id" => $matchedStudent['id'] ?? null,
            "student_name" => $matchedStudent['name'] ?? $studentName,
            "student_email" => $matchedStudent['email'] ?? $studentEmail,
            "academic_year" => $academicYear,
            "semester" => $semester,
            "class_level" => $classLevel,
            "education_level" => $educationLevel,
            "curriculum" => $curriculum,
            "subjects" => $subjectsData,
            "is_duplicate" => $isDuplicate,
            "status" => $rowStatus,
            "issues" => $issues
        ];
    }

    echo json_encode([
        "success" => true,
        "summary" => [
            "total_rows" => count($rows),
            "valid_rows" => $validCount,
            "warning_rows" => $warningCount,
            "error_rows" => $errorCount
        ],
        "preview" => $previewList
    ]);
    exit;
}

// -------------------------------------------------------------
// 7. IMPORT COMMIT (Eksekusi Simpan Batch Rapor yang Lolos Validasi)
// -------------------------------------------------------------
if ($action === 'report_import_commit' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    $rows = $input['rows'] ?? [];
    if (empty($rows) || !is_array($rows)) {
        http_response_code(400);
        echo json_encode(["error" => "Daftar baris untuk di-commit kosong."]);
        exit;
    }

    $pdo->beginTransaction();
    $committedCount = 0;
    try {
        $inRc = $pdo->prepare("
            INSERT INTO report_cards (
                id, tenant_id, school_id, student_id, education_level, 
                academic_year, semester, class_level, class_name, curriculum, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')
            ON DUPLICATE KEY UPDATE 
                education_level = VALUES(education_level),
                class_level = VALUES(class_level),
                class_name = VALUES(class_name),
                curriculum = VALUES(curriculum),
                status = 'submitted',
                updated_at = NOW()
        ");

        $delDetail = $pdo->prepare("DELETE FROM report_card_details WHERE report_card_id = ?");
        $inDetail = $pdo->prepare("
            INSERT INTO report_card_details (
                id, report_card_id, subject_name, score, letter_grade, kkm
            ) VALUES (?, ?, ?, ?, ?, 75.00)
        ");

        foreach ($rows as $r) {
            $studentId = safe_str($r['student_id'] ?? '');
            if (empty($studentId)) continue;

            $academicYear = safe_str($r['academic_year'] ?? '2026/2027');
            $semester = strtolower(safe_str($r['semester'] ?? 'ganjil'));
            $classLevel = safe_str($r['class_level'] ?? 'Kelas 12');
            $className = safe_str($r['class_name'] ?? '');
            $eduLevel = strtoupper(safe_str($r['education_level'] ?? 'SMA'));
            $curriculum = safe_str($r['curriculum'] ?? 'Kurikulum Merdeka');
            $subjects = $r['subjects'] ?? [];

            // Get existing ID or generate
            $chk = $pdo->prepare("SELECT id FROM report_cards WHERE student_id = ? AND academic_year = ? AND semester = ?");
            $chk->execute([$studentId, $academicYear, $semester]);
            $existingId = $chk->fetchColumn();
            $rcId = $existingId ?: generate_school_uuid();

            $inRc->execute([
                $rcId, $payload->tenant_id ?? 'default_tenant', $current_school_id,
                $studentId, $eduLevel, $academicYear, $semester, $classLevel,
                $className, $curriculum
            ]);

            // Re-fetch ID in case of ON DUPLICATE KEY
            $chk->execute([$studentId, $academicYear, $semester]);
            $actualRcId = $chk->fetchColumn() ?: $rcId;

            // Details
            $delDetail->execute([$actualRcId]);
            foreach ($subjects as $sName => $sScore) {
                if (!is_numeric($sScore)) continue;
                $scoreNum = max(0, min(100, (float)$sScore));
                $letter = ($scoreNum >= 90) ? 'A' : (($scoreNum >= 80) ? 'B' : (($scoreNum >= 70) ? 'C' : 'D'));
                $inDetail->execute([generate_school_uuid(), $actualRcId, safe_str($sName), $scoreNum, $letter]);
            }

            $committedCount++;
        }

        $pdo->commit();
        echo json_encode([
            "success" => true,
            "message" => "Berhasil mengimpor $committedCount nilai rapor siswa.",
            "committed_count" => $committedCount
        ]);
        exit;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Gagal commit import rapor: " . $e->getMessage()]);
        exit;
    }
}

// -------------------------------------------------------------
// 8. STUDENT 360 INTELLIGENCE (Profil Komprehensif Terpadu Siswa)
// -------------------------------------------------------------
if ($action === 'student_360' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $student_id = safe_str($_GET['student_id'] ?? '');
    if (empty($student_id)) {
        http_response_code(400);
        echo json_encode(["error" => "Parameter student_id wajib diisi."]);
        exit;
    }

    // Student identity
    $stStmt = $pdo->prepare("
        SELECT id, name, email, phone, school, target_ptn, plan, streak, total_score, created_at
        FROM students 
        WHERE id = ? AND (school_id = ? OR LOWER(TRIM(school)) = LOWER(TRIM(?)))
    ");
    $stStmt->execute([$student_id, $current_school_id, $current_school_name]);
    $student = $stStmt->fetch(PDO::FETCH_ASSOC);

    if (!$student) {
        http_response_code(404);
        echo json_encode(["error" => "Siswa tidak ditemukan atau tidak berada dalam kewenangan sekolah ini."]);
        exit;
    }

    // 1. Report Cards History across semesters
    $rcStmt = $pdo->prepare("
        SELECT rc.id, rc.education_level, rc.academic_year, rc.semester, rc.class_level, rc.class_name, rc.curriculum, rc.status, rc.verified_at
        FROM report_cards rc
        WHERE rc.student_id = ?
        ORDER BY rc.academic_year ASC, rc.semester ASC
    ");
    $rcStmt->execute([$student_id]);
    $reportCards = $rcStmt->fetchAll(PDO::FETCH_ASSOC);

    $detStmt = $pdo->prepare("
        SELECT subject_name, score, letter_grade, kkm, competency_achievement 
        FROM report_card_details 
        WHERE report_card_id = ?
        ORDER BY subject_name ASC
    ");

    $semesterProgression = [];
    $allSubjectScores = [];

    foreach ($reportCards as &$rc) {
        $detStmt->execute([$rc['id']]);
        $rc['details'] = $detStmt->fetchAll(PDO::FETCH_ASSOC);
        $avg = count($rc['details']) > 0 ? round(array_sum(array_column($rc['details'], 'score')) / count($rc['details']), 2) : 0;
        $rc['average_score'] = $avg;
        $label = $rc['academic_year'] . ' (' . ucfirst($rc['semester']) . ')';
        $semesterProgression[] = [
            "period" => $label,
            "average" => $avg,
            "class_level" => $rc['class_level']
        ];
        foreach ($rc['details'] as $dt) {
            $allSubjectScores[$dt['subject_name']][] = (float)$dt['score'];
        }
    }

    // 2. CBT / Assessment Tryout History
    $cbtStmt = $pdo->prepare("
        SELECT id, quiz_type, subtes, score, correct, total, duration_sec, created_at
        FROM quiz_results
        WHERE student_id = ?
        ORDER BY created_at DESC
        LIMIT 15
    ");
    $cbtStmt->execute([$student_id]);
    $assessmentHistory = $cbtStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Diagnostic & Learning Path
    $diagStmt = $pdo->prepare("
        SELECT id, answers, raw_score, recommended_track, created_at
        FROM diagnostic_results
        WHERE student_id = ?
        ORDER BY created_at DESC
        LIMIT 1
    ");
    $diagStmt->execute([$student_id]);
    $diagnosticResult = $diagStmt->fetch(PDO::FETCH_ASSOC);

    // 4. Learning Gap Identification (Mata pelajaran dengan nilai < KKM atau rata-rata terendah)
    $learningGaps = [];
    foreach ($allSubjectScores as $subj => $scores) {
        $avgScore = round(array_sum($scores) / count($scores), 2);
        if ($avgScore < 75) {
            $learningGaps[] = [
                "subject" => $subj,
                "average_score" => $avgScore,
                "gap_severity" => ($avgScore < 65 ? "TINGGI" : "SEDANG"),
                "recommendation" => "Perlu bimbingan intensif materi dasar & latihan mandiri pada topik $subj."
            ];
        }
    }

    // 5. Guidance Counseling Records
    $grStmt = $pdo->prepare("
        SELECT id, counselor_name, category, priority, issue_summary, action_plan, status, follow_up_date, created_at
        FROM guidance_records
        WHERE student_id = ?
        ORDER BY created_at DESC
    ");
    $grStmt->execute([$student_id]);
    $guidanceRecords = $grStmt->fetchAll(PDO::FETCH_ASSOC);

    // 6. Admission Tracking
    $atStmt = $pdo->prepare("
        SELECT id, target_university, target_faculty, target_major, selection_path, priority_order, status, notes
        FROM admission_trackings
        WHERE student_id = ?
        ORDER BY priority_order ASC
    ");
    $atStmt->execute([$student_id]);
    $admissionTracking = $atStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "student" => $student,
        "report_cards" => $reportCards,
        "semester_progression" => $semesterProgression,
        "assessment_history" => $assessmentHistory,
        "diagnostic" => $diagnosticResult,
        "learning_gaps" => $learningGaps,
        "guidance_records" => $guidanceRecords,
        "admission_tracking" => $admissionTracking
    ]);
    exit;
}

// -------------------------------------------------------------
// 9. GUIDANCE INTELLIGENCE RECORDS (Catatan Konseling BK)
// -------------------------------------------------------------
if ($action === 'guidance_records') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $student_id = safe_str($_GET['student_id'] ?? '');
        $status = safe_str($_GET['status'] ?? '');
        $category = safe_str($_GET['category'] ?? '');

        $where = ["gr.school_id = ?"];
        $params = [$current_school_id];

        if (!empty($student_id)) {
            $where[] = "gr.student_id = ?";
            $params[] = $student_id;
        }
        if (!empty($status)) {
            $where[] = "gr.status = ?";
            $params[] = $status;
        }
        if (!empty($category)) {
            $where[] = "gr.category = ?";
            $params[] = $category;
        }

        $stmt = $pdo->prepare("
            SELECT gr.*, st.name as student_name, st.email as student_email, st.phone as student_phone, st.target_ptn
            FROM guidance_records gr
            JOIN students st ON gr.student_id = st.id
            WHERE " . implode(" AND ", $where) . "
            ORDER BY gr.created_at DESC
        ");
        $stmt->execute($params);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(["success" => true, "guidance_records" => $records]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $student_id = safe_str($input['student_id'] ?? '');
        $counselor_name = safe_str($input['counselor_name'] ?? ($payload->name ?? 'Guru BK'));
        $category = safe_str($input['category'] ?? 'akademik');
        $priority = safe_str($input['priority'] ?? 'sedang');
        $issue_summary = safe_str($input['issue_summary'] ?? '');
        $action_plan = safe_str($input['action_plan'] ?? '');
        $status = safe_str($input['status'] ?? 'open');
        $follow_up_date = !empty($input['follow_up_date']) ? safe_str($input['follow_up_date']) : null;

        if (empty($student_id) || empty($issue_summary)) {
            http_response_code(400);
            echo json_encode(["error" => "ID Siswa dan Ringkasan Permasalahan wajib diisi."]);
            exit;
        }

        $id = generate_school_uuid();
        $stmt = $pdo->prepare("
            INSERT INTO guidance_records (
                id, tenant_id, school_id, student_id, counselor_name, category, priority, 
                issue_summary, action_plan, status, follow_up_date
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([
            $id, $payload->tenant_id ?? 'default_tenant', $current_school_id, $student_id,
            $counselor_name, $category, $priority, $issue_summary, $action_plan, $status, $follow_up_date
        ]);

        echo json_encode(["success" => true, "message" => "Catatan pendampingan berhasil disimpan.", "id" => $id]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = safe_str($input['id'] ?? '');
        $status = safe_str($input['status'] ?? 'in_progress');
        $action_plan = safe_str($input['action_plan'] ?? '');
        $follow_up_date = !empty($input['follow_up_date']) ? safe_str($input['follow_up_date']) : null;

        if (empty($id)) {
            http_response_code(400);
            echo json_encode(["error" => "ID catatan pendampingan wajib diisi."]);
            exit;
        }

        $stmt = $pdo->prepare("
            UPDATE guidance_records SET 
                status = ?, 
                action_plan = CASE WHEN ? != '' THEN ? ELSE action_plan END,
                follow_up_date = ?,
                updated_at = NOW()
            WHERE id = ? AND school_id = ?
        ");
        $stmt->execute([$status, $action_plan, $action_plan, $follow_up_date, $id, $current_school_id]);

        echo json_encode(["success" => true, "message" => "Status catatan pendampingan diperbarui."]);
        exit;
    }
}

// -------------------------------------------------------------
// 10. ADMISSION READINESS INTELLIGENCE (Analisis SNBP & SNBT)
// -------------------------------------------------------------
if ($action === 'admission_readiness' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. SNBP Readiness: Students ranking with consistent high GPA across semesters
    $snbpStmt = $pdo->prepare("
        SELECT 
            st.id as student_id, st.name as student_name, st.email, st.phone, st.target_ptn,
            COUNT(rc.id) as total_semesters_recorded,
            ROUND(AVG(rcd.score), 2) as cumulative_gpa,
            MAX(rcd.score) as highest_subject_score,
            MIN(rcd.score) as lowest_subject_score
        FROM students st
        JOIN report_cards rc ON st.id = rc.student_id
        JOIN report_card_details rcd ON rc.id = rcd.report_card_id
        WHERE (st.school_id = :school_id OR LOWER(TRIM(st.school)) = LOWER(TRIM(:school_name)))
        GROUP BY st.id, st.name, st.email, st.phone, st.target_ptn
        HAVING total_semesters_recorded >= 1
        ORDER BY cumulative_gpa DESC
        LIMIT 50
    ");
    $snbpStmt->execute([
        ":school_id" => $current_school_id,
        ":school_name" => $current_school_name
    ]);
    $snbpRanking = $snbpStmt->fetchAll(PDO::FETCH_ASSOC);

    // 2. SNBT CBT Performance Distribution
    $snbtStmt = $pdo->prepare("
        SELECT 
            st.id as student_id, st.name as student_name, st.target_ptn,
            ROUND(AVG(qr.score), 1) as avg_cbt_score,
            COUNT(qr.id) as tryouts_attempted,
            MAX(qr.score) as top_score
        FROM students st
        JOIN quiz_results qr ON st.id = qr.student_id
        WHERE (st.school_id = :school_id OR LOWER(TRIM(st.school)) = LOWER(TRIM(:school_name)))
        GROUP BY st.id, st.name, st.target_ptn
        ORDER BY avg_cbt_score DESC
        LIMIT 50
    ");
    $snbtStmt->execute([
        ":school_id" => $current_school_id,
        ":school_name" => $current_school_name
    ]);
    $snbtPerformance = $snbtStmt->fetchAll(PDO::FETCH_ASSOC);

    // 3. Admission Trackings target count
    $targetStmt = $pdo->prepare("
        SELECT target_university, target_major, selection_path, COUNT(*) as applicant_count
        FROM admission_trackings
        WHERE school_id = ?
        GROUP BY target_university, target_major, selection_path
        ORDER BY applicant_count DESC
        LIMIT 10
    ");
    $targetStmt->execute([$current_school_id]);
    $popularTargets = $targetStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "snbp_ranking" => $snbpRanking,
        "snbt_performance" => $snbtPerformance,
        "popular_targets" => $popularTargets
    ]);
    exit;
}


http_response_code(404);
echo json_encode(["error" => "Action tidak valid."]);
