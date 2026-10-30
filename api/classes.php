<?php
// api/classes.php
// Modul Manajemen Kelas Offline, Ruangan, Penjadwalan, Reschedule/Inval & Rekap Kehadiran
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';
require_once __DIR__ . '/PartnerService.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// 1. Otorisasi Token
$payload = authenticate();
$allowedStudentActions = [
    'my_classes', 'my_sessions', 'my_attendance', 'makeup_requests',
    'renewal_options', 'renew_enrollment', 'renewal_history'
];
if ($payload->role === 'student' && !in_array($action, $allowedStudentActions)) {
    http_response_code(403);
    echo json_encode(["error" => "Akses khusus staf internal (Admin Cabang, Superadmin, dan Guru)."]);
    exit;
} elseif (!in_array($payload->role, ['superadmin', 'admin', 'teacher', 'guru', 'tutor', 'operator', 'student'])) {
    http_response_code(403);
    echo json_encode(["error" => "Akses tidak diizinkan."]);
    exit;
}

$tenant_id = $payload->tenant_id;
$isSuper = ($payload->role === 'superadmin');

// Helper UUID
function gen_uuid() {
    return sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

// ========================================================
// 1. MANAJEMEN RUANG FISIK KELAS (class_rooms)
// ========================================================
if ($action === 'rooms') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "SELECT r.*, t.name as tenant_name FROM class_rooms r LEFT JOIN tenants t ON r.tenant_id = t.id WHERE 1=1";
        $params = [];
        if (!$isSuper) {
            $sql .= " AND r.tenant_id = ?";
            $params[] = $tenant_id;
        }
        $sql .= " ORDER BY r.name ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["rooms" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // Hanya Admin Cabang & Superadmin yang boleh membuat/mengubah ruang
    if (!in_array($payload->role, ['superadmin', 'admin'])) {
        http_response_code(403);
        echo json_encode(["error" => "Hanya Administrator yang dapat mengelola inventaris ruangan"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = safe_str($input['name'] ?? '');
        $capacity = (int)($input['capacity'] ?? 15);
        $tId = $isSuper ? ($input['tenant_id'] ?? $tenant_id) : $tenant_id;
        if (!$tId) {
            $firstTenant = $pdo->query("SELECT id FROM tenants ORDER BY created_at ASC LIMIT 1")->fetchColumn();
            $tId = $firstTenant ?: 'default';
        }
        if (!$name) {
            http_response_code(400); echo json_encode(["error" => "Nama ruangan harus diisi"]); exit;
        }
        $id = gen_uuid();
        $stmt = $pdo->prepare("INSERT INTO class_rooms (id, tenant_id, name, capacity, is_active) VALUES (?, ?, ?, ?, 1)");
        $stmt->execute([$id, $tId, $name, $capacity]);
        echo json_encode(["success" => true, "id" => $id]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = safe_str($input['id'] ?? ($_GET['id'] ?? ''));
        $name = safe_str($input['name'] ?? '');
        $capacity = (int)($input['capacity'] ?? 15);
        $isActive = isset($input['is_active']) ? (int)$input['is_active'] : 1;
        $sql = "UPDATE class_rooms SET name = ?, capacity = ?, is_active = ? WHERE id = ?";
        $params = [$name, $capacity, $isActive, $id];
        if (!$isSuper) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenant_id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["success" => true]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = safe_str($_GET['id'] ?? '');
        $sql = "DELETE FROM class_rooms WHERE id = ?";
        $params = [$id];
        if (!$isSuper) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenant_id;
        }
        try {
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            echo json_encode(["success" => true]);
        } catch (\PDOException $e) {
            http_response_code(409);
            echo json_encode(["error" => "Ruangan tidak dapat dihapus karena masih digunakan dalam jadwal sesi aktif"]);
        }
        exit;
    }
}

// ========================================================
// 2. MANAJEMEN ROMBEL / KELAS (classes)
// ========================================================
elseif ($action === 'classes') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "
            SELECT c.*, c.default_room_id as room_id, r.name as room_name, t.name as tenant_name,
                   (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.id AND cs.status = 'active') as student_count
            FROM classes c
            LEFT JOIN class_rooms r ON c.default_room_id = r.id
            LEFT JOIN tenants t ON c.tenant_id = t.id
            WHERE c.status != 'archived'
        ";
        $params = [];
        if (!$isSuper) {
            $sql .= " AND c.tenant_id = ?";
            $params[] = $tenant_id;
        }
        $sql .= " ORDER BY c.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $classList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["classes" => $classList, "data" => $classList]);
        exit;
    }

    if (!in_array($payload->role, ['superadmin', 'admin'])) {
        http_response_code(403);
        echo json_encode(["error" => "Hanya Administrator Cabang yang dapat mengelola kelas rombel"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = safe_str($input['name'] ?? '');
        $academic_year = safe_str($input['academic_year'] ?? (date('Y') . '/' . (date('Y') + 1)));
        $default_room_id = !empty($input['default_room_id']) ? $input['default_room_id'] : (!empty($input['room_id']) ? $input['room_id'] : null);
        $max_capacity = (int)($input['max_capacity'] ?? 15);
        $tId = $isSuper ? ($input['tenant_id'] ?? $tenant_id) : $tenant_id;
        if (!$tId) {
            $firstTenant = $pdo->query("SELECT id FROM tenants ORDER BY created_at ASC LIMIT 1")->fetchColumn();
            $tId = $firstTenant ?: 'default';
        }

        if (!$name) {
            http_response_code(400); echo json_encode(["error" => "Nama rombel kelas wajib diisi"]); exit;
        }
        $id = gen_uuid();
        $stmt = $pdo->prepare("
            INSERT INTO classes (id, tenant_id, name, academic_year, default_room_id, max_capacity, status)
            VALUES (?, ?, ?, ?, ?, ?, 'active')
        ");
        $stmt->execute([$id, $tId, $name, $academic_year, $default_room_id, $max_capacity]);
        echo json_encode(["success" => true, "id" => $id]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = safe_str($input['id'] ?? ($_GET['id'] ?? ''));
        $name = safe_str($input['name'] ?? '');
        $academic_year = safe_str($input['academic_year'] ?? '');
        $default_room_id = !empty($input['default_room_id']) ? $input['default_room_id'] : (!empty($input['room_id']) ? $input['room_id'] : null);
        $max_capacity = (int)($input['max_capacity'] ?? 15);
        $status = $input['status'] ?? 'active';

        $sql = "UPDATE classes SET name = ?, academic_year = ?, default_room_id = ?, max_capacity = ?, status = ? WHERE id = ?";
        $params = [$name, $academic_year, $default_room_id, $max_capacity, $status, $id];
        if (!$isSuper) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenant_id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["success" => true]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        // Soft delete: arsipkan kelas demi integritas histori audit dan payroll
        $id = $_GET['id'] ?? '';
        $sql = "UPDATE classes SET status = 'archived' WHERE id = ?";
        $params = [$id];
        if (!$isSuper) {
            $sql .= " AND tenant_id = ?";
            $params[] = $tenant_id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["success" => true, "message" => "Kelas berhasil diarsipkan"]);
        exit;
    }
}

// ========================================================
// 3. PENUGASAN SISWA KE KELAS (class_students)
// ========================================================
elseif ($action === 'class_students') {
    $class_id = $_GET['class_id'] ?? ($input['class_id'] ?? '');
    if (!$class_id) {
        http_response_code(400); echo json_encode(["error" => "Class ID wajib ditentukan"]); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("
            SELECT cs.id, cs.id as enrollment_id, cs.joined_at, cs.status as enrollment_status,
                   s.id as student_id, s.name, s.name as student_name, s.email, s.email as student_email, s.phone, s.school, s.target_ptn, s.plan,
                   s.telegram_chat_id, s.parent_telegram_id
            FROM class_students cs
            JOIN students s ON cs.student_id = s.id
            WHERE cs.class_id = ? AND cs.status = 'active'
            ORDER BY s.name ASC
        ");
        $stmt->execute([$class_id]);
        $studentsList = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(["students" => $studentsList, "class_students" => $studentsList]);
        exit;
    }

    if (!in_array($payload->role, ['superadmin', 'admin'])) {
        http_response_code(403);
        echo json_encode(["error" => "Hanya Administrator Cabang yang dapat menugaskan siswa ke kelas"]);
        exit;
    }

    // AC-08/AC-18: Enforce tenant boundary for branch admin
    if (!$isSuper) {
        $stmtTenantChk = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND tenant_id = ? LIMIT 1");
        $stmtTenantChk->execute([$class_id, $tenant_id]);
        if (!$stmtTenantChk->fetch()) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden: Anda tidak berwenang mengelola siswa di rombel cabang lain"]);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $student_id = $input['student_id'] ?? '';
        if (!$student_id) {
            http_response_code(400); echo json_encode(["error" => "Student ID wajib disertakan"]); exit;
        }

        try {
            $pdo->beginTransaction();

            // AC-14: Atomic validation of capacity across cohort AND all scheduled sessions
            $qChk = $pdo->prepare("
                SELECT c.max_capacity, (SELECT COUNT(*) FROM class_students WHERE class_id = c.id AND status = 'active') as current_count
                FROM classes c WHERE c.id = ? FOR UPDATE
            ");
            $qChk->execute([$class_id]);
            $cls = $qChk->fetch(PDO::FETCH_ASSOC);
            if ($cls && $cls['current_count'] >= $cls['max_capacity']) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(["error" => "Kapasitas kelas telah penuh (Maksimal {$cls['max_capacity']} siswa)"]);
                exit;
            }

            // Cek Keberadaan Siswa
            $stdChk = $pdo->prepare("SELECT id FROM students WHERE id = ?");
            $stdChk->execute([$student_id]);
            if (!$stdChk->fetch()) {
                $pdo->rollBack();
                http_response_code(404);
                echo json_encode(["error" => "Data siswa tidak ditemukan atau belum terdaftar"]);
                exit;
            }

            // AC-14: Validate capacity of ALL scheduled sessions in this class atomically
            $qSessions = $pdo->prepare("
                SELECT cs.id, cs.session_date, cs.start_time, cr.capacity as room_capacity,
                       (SELECT COUNT(*) FROM session_attendances sa WHERE sa.session_id = cs.id) as current_occupancy
                FROM class_sessions cs
                JOIN class_rooms cr ON cs.room_id = cr.id
                WHERE cs.class_id = ? AND cs.status = 'scheduled'
                FOR UPDATE
            ");
            $qSessions->execute([$class_id]);
            $sessions = $qSessions->fetchAll(PDO::FETCH_ASSOC);

            foreach ($sessions as $ses) {
                if ($ses['current_occupancy'] >= $ses['room_capacity']) {
                    $pdo->rollBack();
                    http_response_code(409);
                    echo json_encode([
                        "error" => "Kapasitas ruangan penuh pada sesi {$ses['session_date']} ({$ses['start_time']}). Penugasan dibatalkan untuk mencegah overbooking (AC-14 all_sessions atomic check)."
                    ]);
                    exit;
                }
            }

            $id = gen_uuid();
            $stmt = $pdo->prepare("
                INSERT INTO class_students (id, class_id, student_id, joined_at, confirmed_at, status)
                VALUES (?, ?, ?, NOW(), NOW(), 'active')
                ON DUPLICATE KEY UPDATE status = 'active', confirmed_at = NOW()
            ");
            $stmt->execute([$id, $class_id, $student_id]);

            // Auto-provision attendance records for scheduled sessions so roster is immediately visible
            foreach ($sessions as $ses) {
                $att_id = gen_uuid();
                $pdo->prepare("
                    INSERT IGNORE INTO session_attendances (id, session_id, student_id, attendance_status)
                    VALUES (?, ?, ?, 'absent')
                ")->execute([$att_id, $ses['id'], $student_id]);
            }

            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Siswa berhasil ditugaskan ke kelas dan seluruh jadwal sesi tervalidasi aman"]);
            exit;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["error" => "Gagal menugaskan siswa ke kelas: " . $e->getMessage()]);
            exit;
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $student_id = $_GET['student_id'] ?? '';
        $stmt = $pdo->prepare("UPDATE class_students SET status = 'dropped' WHERE class_id = ? AND student_id = ?");
        $stmt->execute([$class_id, $student_id]);
        echo json_encode(["success" => true, "message" => "Siswa dikeluarkan dari rombel"]);
        exit;
    }
}

// ========================================================
// 4. SESI KALENDER RIIL & PENCEGAHAN BENTURAN (class_sessions)
// ========================================================
elseif ($action === 'sessions') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $class_id = $_GET['class_id'] ?? '';
        $date = $_GET['date'] ?? '';
        $teacher_id = $_GET['teacher_id'] ?? '';

        $sql = "
            SELECT ses.*, c.name as class_name, r.name as room_name,
                   COALESCE(a.name, 'Guru EduPath') as teacher_name,
                   COALESCE(orig.name, NULL) as original_teacher_name,
                   (SELECT COUNT(*) FROM session_attendances sa WHERE sa.session_id = ses.id AND sa.attendance_status = 'present') as present_count,
                   (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = ses.class_id AND cs.status = 'active') as total_students
            FROM class_sessions ses
            JOIN classes c ON ses.class_id = c.id
            JOIN class_rooms r ON ses.room_id = r.id
            LEFT JOIN admins a ON ses.teacher_id = a.id
            LEFT JOIN admins orig ON ses.original_teacher_id = orig.id
            WHERE 1=1
        ";
        $params = [];
        if (!$isSuper) {
            $sql .= " AND c.tenant_id = ?";
            $params[] = $tenant_id;
        }
        if ($class_id) {
            $sql .= " AND ses.class_id = ?";
            $params[] = $class_id;
        }
        if ($date) {
            $sql .= " AND ses.session_date = ?";
            $params[] = $date;
        }
        if ($teacher_id) {
            $sql .= " AND ses.teacher_id = ?";
            $params[] = $teacher_id;
        }
        $sql .= " ORDER BY ses.session_date ASC, ses.start_time ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["sessions" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // Pembuatan sesi baru atau reschedule sesi
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $isReschedule = isset($input['is_reschedule']) && $input['is_reschedule'];
        $class_id = $input['class_id'] ?? '';
        $session_date = $input['session_date'] ?? '';
        $start_time = $input['start_time'] ?? '';
        $end_time = $input['end_time'] ?? '';
        $room_id = $input['room_id'] ?? '';
        $teacher_id = $input['teacher_id'] ?? '';
        $tutor_fee = !empty($input['tutor_fee_snapshot']) ? (float)$input['tutor_fee_snapshot'] : 75000.00;
        $session_type = $input['session_type'] ?? 'regular';

        if (!$class_id || !$session_date || !$start_time || !$end_time || !$room_id || !$teacher_id) {
            http_response_code(400); echo json_encode(["error" => "Seluruh kolom sesi kelas wajib diisi"]); exit;
        }

        // AC-08/AC-18: Enforce tenant boundary on session creation/rescheduling
        if (!$isSuper) {
            $stmtTenantChk = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND tenant_id = ? LIMIT 1");
            $stmtTenantChk->execute([$class_id, $tenant_id]);
            if (!$stmtTenantChk->fetch()) {
                http_response_code(403);
                echo json_encode(["error" => "Forbidden: Anda tidak berwenang mengelola sesi pada rombel cabang lain"]);
                exit;
            }
        }

        // --- CONFLICT DETECTION ENGINE (TOCTOU PROOF) ---
        // Kunci atomik: pastikan ruangan dan guru tidak bentrok
        $conflictQuery = "
            SELECT id, room_id, teacher_id FROM class_sessions
            WHERE session_date = ?
              AND status NOT IN ('cancelled', 'rescheduled')
              AND ((room_id = ?) OR (teacher_id = ?))
              AND (start_time < ? AND end_time > ?)
            LIMIT 1
        ";
        $chkStmt = $pdo->prepare($conflictQuery);
        $chkStmt->execute([$session_date, $room_id, $teacher_id, $end_time, $start_time]);
        $conflict = $chkStmt->fetch(PDO::FETCH_ASSOC);

        if ($conflict) {
            http_response_code(409);
            $reason = ($conflict['room_id'] === $room_id) 
                ? "Ruangan yang dipilih sedang digunakan oleh sesi lain pada jam tersebut."
                : "Guru pengampu sedang memiliki jadwal mengajar di kelas lain pada jam tersebut.";
            echo json_encode(["error" => "Benturan Jadwal Terdeteksi (Conflict): " . $reason]);
            exit;
        }

        $newSessionId = gen_uuid();
        if ($isReschedule) {
            $originalSessionId = $input['original_session_id'] ?? '';
            $reason = $input['reschedule_reason'] ?? 'Penjadwalan Ulang oleh Cabang';
            if ($originalSessionId) {
                $upOrig = $pdo->prepare("
                    UPDATE class_sessions 
                    SET status = 'rescheduled', reschedule_reason = ?, rescheduled_to_session_id = ? 
                    WHERE id = ?
                ");
                $upOrig->execute([$reason, $newSessionId, $originalSessionId]);
            }
            $session_type = 'rescheduled';
        }

        $insStmt = $pdo->prepare("
            INSERT INTO class_sessions (id, class_id, session_date, start_time, end_time, room_id, teacher_id, session_type, tutor_fee_snapshot, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'scheduled')
        ");
        $insStmt->execute([$newSessionId, $class_id, $session_date, $start_time, $end_time, $room_id, $teacher_id, $session_type, $tutor_fee]);

        echo json_encode(["success" => true, "session_id" => $newSessionId, "message" => "Sesi kelas berhasil dijadwalkan"]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $sessionId = $_GET['id'] ?? ($input['id'] ?? '');
        if (!$sessionId) {
            http_response_code(400); echo json_encode(["error" => "Session ID wajib disertakan"]); exit;
        }
        if (!in_array($payload->role, ['superadmin', 'admin'])) {
            http_response_code(403); echo json_encode(["error" => "Hanya Administrator yang berwenang membatalkan atau menghapus sesi"]); exit;
        }
        if (!$isSuper) {
            $stmtTenantChk = $pdo->prepare("SELECT c.id FROM class_sessions cs JOIN classes c ON cs.class_id = c.id WHERE cs.id = ? AND c.tenant_id = ?");
            $stmtTenantChk->execute([$sessionId, $tenant_id]);
            if (!$stmtTenantChk->fetch()) {
                http_response_code(403); echo json_encode(["error" => "Forbidden: Anda tidak berwenang menghapus sesi rombel cabang lain"]); exit;
            }
        }
        $pdo->prepare("DELETE FROM session_attendances WHERE session_id = ?")->execute([$sessionId]);
        $pdo->prepare("DELETE FROM class_sessions WHERE id = ?")->execute([$sessionId]);
        echo json_encode(["success" => true, "message" => "Sesi kelas berhasil dihapus"]);
        exit;
    }
}

// ========================================================
// 4b. MANAJEMEN POLA JADWAL RUTIN (class_schedules)
// ========================================================
elseif ($action === 'schedules') {
    $class_id = $_GET['class_id'] ?? ($input['class_id'] ?? '');
    if (!$class_id && $_SERVER['REQUEST_METHOD'] !== 'DELETE') {
        http_response_code(400); echo json_encode(["error" => "Class ID wajib disertakan"]); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "
            SELECT cs.*, cr.name as room_name, cr.capacity as room_capacity,
                   COALESCE(a.name, a.username, 'Guru EduPath') as teacher_name
            FROM class_schedules cs
            JOIN class_rooms cr ON cs.room_id = cr.id
            LEFT JOIN admins a ON cs.primary_teacher_id = a.id
            JOIN classes c ON cs.class_id = c.id
            WHERE cs.class_id = ?
        ";
        $params = [$class_id];
        if (!$isSuper) {
            $sql .= " AND c.tenant_id = ?";
            $params[] = $tenant_id;
        }
        $sql .= " ORDER BY cs.day_of_week ASC, cs.start_time ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["schedules" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if (!in_array($payload->role, ['superadmin', 'admin'])) {
        http_response_code(403); echo json_encode(["error" => "Hanya Administrator Cabang yang dapat mengelola jadwal rutin"]); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $day_of_week = (int)($input['day_of_week'] ?? 1);
        $start_time = safe_str($input['start_time'] ?? '');
        $end_time = safe_str($input['end_time'] ?? '');
        $subject = safe_str($input['subject'] ?? 'Pelajaran Reguler');
        $room_id = safe_str($input['room_id'] ?? '');
        $primary_teacher_id = safe_str($input['primary_teacher_id'] ?? '');

        if (!$start_time || !$end_time || !$room_id || !$primary_teacher_id) {
            http_response_code(400); echo json_encode(["error" => "Semua kolom jadwal rutin wajib diisi (hari, jam mulai/selesai, ruangan, guru)"]); exit;
        }

        $id = gen_uuid();
        $ins = $pdo->prepare("
            INSERT INTO class_schedules (id, class_id, day_of_week, start_time, end_time, subject, room_id, primary_teacher_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $ins->execute([$id, $class_id, $day_of_week, $start_time, $end_time, $subject, $room_id, $primary_teacher_id]);
        echo json_encode(["success" => true, "id" => $id, "message" => "Pola jadwal rutin berhasil disimpan"]);
        exit;
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = $_GET['id'] ?? ($input['id'] ?? '');
        if (!$id) {
            http_response_code(400); echo json_encode(["error" => "Schedule ID wajib disertakan"]); exit;
        }
        $stmt = $pdo->prepare("DELETE FROM class_schedules WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(["success" => true, "message" => "Pola jadwal berhasil dihapus"]);
        exit;
    }
}

// ========================================================
// 4c. GENERATOR SESI KALENDER RUTIN (generate_sessions)
// Mendukung penjadwalan massal (misal: 2x seminggu Senin & Kamis pk 13:30 Jan 2026 - Nov 2026)
// ========================================================
elseif ($action === 'generate_sessions') {
    if (!in_array($payload->role, ['superadmin', 'admin'])) {
        http_response_code(403); echo json_encode(["error" => "Hanya Administrator Cabang yang dapat men-generate jadwal sesi"]); exit;
    }

    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        http_response_code(405); echo json_encode(["error" => "Metode request harus POST"]); exit;
    }

    $class_id = safe_str($input['class_id'] ?? '');
    $days_of_week = is_array($input['days_of_week'] ?? null) ? array_map('intval', $input['days_of_week']) : [];
    $start_time = safe_str($input['start_time'] ?? '13:30');
    $end_time = safe_str($input['end_time'] ?? '15:00');
    $start_date = safe_str($input['start_date'] ?? '');
    $end_date = safe_str($input['end_date'] ?? '');
    $room_id = safe_str($input['room_id'] ?? '');
    $teacher_id = safe_str($input['teacher_id'] ?? '');
    $subject = safe_str($input['subject'] ?? '');
    $tutor_fee = !empty($input['tutor_fee_snapshot']) ? (float)$input['tutor_fee_snapshot'] : 75000.00;
    $skip_conflicts = isset($input['skip_conflicts']) ? (bool)$input['skip_conflicts'] : true;
    $clear_existing = !empty($input['clear_existing_scheduled']);

    // Dukungan kustom jam per hari (slots array, misal: Selasa pk 10:30 & Kamis pk 13:30)
    $slotsInput = is_array($input['slots'] ?? null) ? $input['slots'] : [];
    $slotsByDay = [];
    if (!empty($slotsInput)) {
        foreach ($slotsInput as $sl) {
            $dow = (int)($sl['day_of_week'] ?? 0);
            if ($dow >= 1 && $dow <= 7) {
                $st = safe_str($sl['start_time'] ?? $start_time);
                $et = safe_str($sl['end_time'] ?? $end_time);
                if (strlen($st) === 5) $st .= ':00';
                if (strlen($et) === 5) $et .= ':00';
                $slotsByDay[$dow] = [
                    'day_of_week' => $dow,
                    'start_time' => $st,
                    'end_time' => $et,
                    'room_id' => safe_str($sl['room_id'] ?? ''),
                    'teacher_id' => safe_str($sl['teacher_id'] ?? '')
                ];
            }
        }
    }

    if (!empty($slotsByDay)) {
        $days_of_week = array_keys($slotsByDay);
    } else {
        if (strlen($start_time) === 5) $start_time .= ':00';
        if (strlen($end_time) === 5) $end_time .= ':00';
        foreach ($days_of_week as $dow) {
            $slotsByDay[$dow] = [
                'day_of_week' => $dow,
                'start_time' => $start_time,
                'end_time' => $end_time,
                'room_id' => '',
                'teacher_id' => ''
            ];
        }
    }

    if (!$class_id || empty($slotsByDay) || !$start_date || !$end_date) {
        http_response_code(400);
        echo json_encode([
            "error" => "Parameter tidak lengkap: class_id, jadwal hari (days_of_week atau slots), start_date, dan end_date wajib diisi."
        ]);
        exit;
    }

    // Validasi kelas & batas tenant
    $clsStmt = $pdo->prepare("SELECT * FROM classes WHERE id = ?");
    $clsStmt->execute([$class_id]);
    $cls = $clsStmt->fetch(PDO::FETCH_ASSOC);
    if (!$cls) {
        http_response_code(404); echo json_encode(["error" => "Rombel kelas tidak ditemukan"]); exit;
    }
    if (!$isSuper && $cls['tenant_id'] !== $tenant_id) {
        http_response_code(403); echo json_encode(["error" => "Forbidden: Anda tidak berwenang mengelola kelas cabang lain"]); exit;
    }

    // Fallback ruangan & guru dari default kelas bila kosong
    if (!$room_id) {
        $room_id = $cls['default_room_id'] ?? '';
    }
    if (!$room_id) {
        $firstRoom = $pdo->prepare("SELECT id FROM class_rooms WHERE tenant_id = ? AND is_active = 1 LIMIT 1");
        $firstRoom->execute([$cls['tenant_id']]);
        $room_id = $firstRoom->fetchColumn();
    }
    if (!$room_id) {
        http_response_code(400);
        echo json_encode(["error" => "Ruang belajar belum ditentukan. Silakan tambahkan atau pilih ruangan kelas terlebih dahulu."]);
        exit;
    }

    if (!$teacher_id) {
        $tStmt = $pdo->prepare("
            SELECT ur.user_id 
            FROM user_roles ur 
            WHERE ur.role IN ('teacher', 'guru', 'tutor') 
              AND (ur.tenant_id = ? OR ur.tenant_id IS NULL) 
            LIMIT 1
        ");
        $tStmt->execute([$cls['tenant_id']]);
        $teacher_id = $tStmt->fetchColumn();
        if (!$teacher_id) {
            $firstAdmin = $pdo->prepare("SELECT id FROM admins WHERE tenant_id = ? OR tenant_id IS NULL LIMIT 1");
            $firstAdmin->execute([$cls['tenant_id']]);
            $teacher_id = $firstAdmin->fetchColumn() ?: $payload->id;
        }
    }

    if (!$subject) {
        $subject = $cls['name'];
    }

    try {
        $startDt = new DateTime($start_date);
        $endDt = new DateTime($end_date);
        if ($startDt > $endDt) {
            http_response_code(400); echo json_encode(["error" => "Tanggal mulai tidak boleh lebih besar dari tanggal selesai"]); exit;
        }
    } catch (\Throwable $e) {
        http_response_code(400); echo json_encode(["error" => "Format tanggal tidak valid (gunakan YYYY-MM-DD)"]); exit;
    }

    try {
        $pdo->beginTransaction();

        // 1. Simpan pola ke class_schedules untuk setiap hari yang dipilih
        $scheduleMap = [];
        $checkSched = $pdo->prepare("SELECT id FROM class_schedules WHERE class_id = ? AND day_of_week = ?");
        $insSched = $pdo->prepare("
            INSERT INTO class_schedules (id, class_id, day_of_week, start_time, end_time, subject, room_id, primary_teacher_id)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $upSched = $pdo->prepare("
            UPDATE class_schedules SET start_time = ?, end_time = ?, subject = ?, room_id = ?, primary_teacher_id = ?
            WHERE id = ?
        ");

        foreach ($slotsByDay as $dow => $slot) {
            $slotStart = $slot['start_time'];
            $slotEnd = $slot['end_time'];
            $slotRoom = !empty($slot['room_id']) ? $slot['room_id'] : $room_id;
            $slotTeacher = !empty($slot['teacher_id']) ? $slot['teacher_id'] : $teacher_id;

            $checkSched->execute([$class_id, $dow]);
            $existingSchedId = $checkSched->fetchColumn();
            if ($existingSchedId) {
                $upSched->execute([$slotStart, $slotEnd, $subject, $slotRoom, $slotTeacher, $existingSchedId]);
                $scheduleMap[$dow] = $existingSchedId;
            } else {
                $newSchedId = gen_uuid();
                $insSched->execute([$newSchedId, $class_id, $dow, $slotStart, $slotEnd, $subject, $slotRoom, $slotTeacher]);
                $scheduleMap[$dow] = $newSchedId;
            }
        }

        // 2. Opsi: Bersihkan sesi scheduled masa depan jika diminta (regenerasi bersih)
        if ($clear_existing) {
            $delStmt = $pdo->prepare("
                SELECT id FROM class_sessions 
                WHERE class_id = ? AND session_date >= ? AND status = 'scheduled'
            ");
            $delStmt->execute([$class_id, $start_date]);
            $oldIds = $delStmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($oldIds)) {
                $inClause = implode(',', array_fill(0, count($oldIds), '?'));
                $pdo->prepare("DELETE FROM session_attendances WHERE session_id IN ($inClause)")->execute($oldIds);
                $pdo->prepare("DELETE FROM class_sessions WHERE id IN ($inClause)")->execute($oldIds);
            }
        }

        // 3. Ambil siswa aktif rombel untuk auto-provision roster kehadiran
        $stStudents = $pdo->prepare("SELECT student_id FROM class_students WHERE class_id = ? AND status = 'active'");
        $stStudents->execute([$class_id]);
        $activeStudents = $stStudents->fetchAll(PDO::FETCH_COLUMN);

        // 4. Query pendeteksi benturan jadwal ruangan & guru
        $conflictStmt = $pdo->prepare("
            SELECT cs.id, cs.room_id, cs.teacher_id, c.name as conflict_class, cr.name as room_name, COALESCE(a.name, a.username, 'Guru Lain') as teacher_name
            FROM class_sessions cs
            JOIN classes c ON cs.class_id = c.id
            JOIN class_rooms cr ON cs.room_id = cr.id
            LEFT JOIN admins a ON cs.teacher_id = a.id
            WHERE cs.session_date = ?
              AND cs.status NOT IN ('cancelled', 'rescheduled')
              AND ((cs.room_id = ?) OR (cs.teacher_id = ?))
              AND (cs.start_time < ? AND cs.end_time > ?)
            LIMIT 1
        ");

        $dupStmt = $pdo->prepare("
            SELECT id FROM class_sessions 
            WHERE class_id = ? AND session_date = ? AND start_time = ? AND status NOT IN ('cancelled', 'rescheduled')
            LIMIT 1
        ");

        $insSession = $pdo->prepare("
            INSERT INTO class_sessions (id, class_id, schedule_id, session_date, start_time, end_time, room_id, teacher_id, session_type, tutor_fee_snapshot, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'regular', ?, 'scheduled')
        ");

        $insAtt = $pdo->prepare("
            INSERT IGNORE INTO session_attendances (id, session_id, student_id, attendance_status)
            VALUES (?, ?, ?, 'absent')
        ");

        $dayNames = [
            1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis',
            5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'
        ];

        $createdSessions = 0;
        $conflicts = [];
        $cur = clone $startDt;

        while ($cur <= $endDt) {
            $dow = (int)$cur->format('N');
            if (isset($slotsByDay[$dow])) {
                $slot = $slotsByDay[$dow];
                $slotStart = $slot['start_time'];
                $slotEnd = $slot['end_time'];
                $slotRoom = !empty($slot['room_id']) ? $slot['room_id'] : $room_id;
                $slotTeacher = !empty($slot['teacher_id']) ? $slot['teacher_id'] : $teacher_id;

                $dateStr = $cur->format('Y-m-d');

                // Cek duplikat sesi pada rombel ini di jam tersebut
                $dupStmt->execute([$class_id, $dateStr, $slotStart]);
                if ($dupStmt->fetch()) {
                    $cur->modify('+1 day');
                    continue;
                }

                // Cek benturan jadwal ruangan dan guru
                $conflictStmt->execute([$dateStr, $slotRoom, $slotTeacher, $slotEnd, $slotStart]);
                $cHit = $conflictStmt->fetch(PDO::FETCH_ASSOC);
                if ($cHit) {
                    $reason = ($cHit['room_id'] === $slotRoom)
                        ? "Ruangan {$cHit['room_name']} sedang digunakan kelas '{$cHit['conflict_class']}'"
                        : "Tutor {$cHit['teacher_name']} sedang mengajar di kelas '{$cHit['conflict_class']}'";
                    $conflicts[] = [
                        "date" => $dateStr,
                        "day" => $dayNames[$dow] ?? '',
                        "time" => substr($slotStart, 0, 5) . '–' . substr($slotEnd, 0, 5),
                        "reason" => $reason
                    ];
                    if (!$skip_conflicts) {
                        $pdo->rollBack();
                        http_response_code(409);
                        echo json_encode([
                            "error" => "Benturan jadwal terdeteksi pada {$dateStr} ({$dayNames[$dow]} pk " . substr($slotStart, 0, 5) . "): {$reason}",
                            "conflicts" => $conflicts
                        ]);
                        exit;
                    }
                    $cur->modify('+1 day');
                    continue;
                }

                $sesId = gen_uuid();
                $schedId = $scheduleMap[$dow] ?? null;
                $insSession->execute([$sesId, $class_id, $schedId, $dateStr, $slotStart, $slotEnd, $slotRoom, $slotTeacher, $tutor_fee]);

                // Auto roster presensi untuk seluruh siswa terdaftar
                foreach ($activeStudents as $stuId) {
                    $attId = gen_uuid();
                    $insAtt->execute([$attId, $sesId, $stuId]);
                }

                $createdSessions++;
            }
            $cur->modify('+1 day');
        }

        $pdo->commit();

        $slotSummaries = [];
        foreach ($slotsByDay as $dow => $slot) {
            $dayName = $dayNames[$dow] ?? "Hari-$dow";
            $slotSummaries[] = "$dayName pk " . substr($slot['start_time'], 0, 5) . '–' . substr($slot['end_time'], 0, 5);
        }

        echo json_encode([
            "success" => true,
            "created_count" => $createdSessions,
            "skipped_conflicts" => count($conflicts),
            "conflicts_detail" => $conflicts,
            "days_summary" => $slotSummaries,
            "start_date" => $start_date,
            "end_date" => $end_date,
            "message" => "Berhasil menghasilkan {$createdSessions} sesi belajar tatap muka (" . implode(' & ', $slotSummaries) . ") dari {$start_date} s/d {$end_date}." . (count($conflicts) > 0 ? " Catatan: " . count($conflicts) . " sesi dilewati karena benturan jadwal." : "")
        ]);
        exit;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Gagal men-generate sesi jadwal: " . $e->getMessage()]);
        exit;
    }
}

// ========================================================
// 5. INPUT PRESENSI SISWA & JURNAL BAP (session_attendance)
// ========================================================
elseif ($action === 'attendance') {
    $session_id = $_GET['session_id'] ?? ($input['session_id'] ?? '');
    if (!$session_id) {
        http_response_code(400); echo json_encode(["error" => "Session ID wajib disertakan"]); exit;
    }

    // AC-08/AC-18: Strict Tenant Isolation Check for Branch Staff / Admin
    if (!$isSuper && in_array($payload->role, ['admin', 'teacher', 'guru', 'tutor'], true)) {
        $stmtSesTenant = $pdo->prepare("
            SELECT c.tenant_id 
            FROM class_sessions ses 
            JOIN classes c ON ses.class_id = c.id 
            WHERE ses.id = ? LIMIT 1
        ");
        $stmtSesTenant->execute([$session_id]);
        $sesTenantId = $stmtSesTenant->fetchColumn();
        if ($sesTenantId && !empty($tenant_id) && $sesTenantId !== $tenant_id) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden: Anda tidak berwenang mengakses atau mengubah presensi sesi cabang lain."]);
            exit;
        }
    }

    // AC-09: Scoped Operator hanya dapat mengakses/mengisi presensi sesi yang ditugaskan secara aktif
    if ($payload->role === 'operator') {
        $operatorUid = $payload->user_id ?? ($payload->id ?? null);
        if (!PartnerService::hasActiveAssignment($pdo, $operatorUid, 'session', $session_id, $tenant_id)) {
            http_response_code(403);
            echo json_encode(["error" => "AC09_FORBIDDEN: Anda tidak memiliki penugasan aktif untuk sesi ini"]);
            exit;
        }
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        // Ambil daftar kehadiran sesi beserta seluruh siswa aktif rombel
        $stmt = $pdo->prepare("
            SELECT s.id as student_id, s.name, s.name as student_name, s.email, s.email as student_email, s.phone,
                   s.telegram_chat_id, s.parent_telegram_id,
                   COALESCE(sa.attendance_status, 'present') as attendance_status,
                   COALESCE(sa.minutes_late, 0) as minutes_late,
                   COALESCE(sa.notes, '') as notes,
                   sa.check_in_time
            FROM class_students cs
            JOIN students s ON cs.student_id = s.id
            JOIN class_sessions ses ON cs.class_id = ses.class_id
            LEFT JOIN session_attendances sa ON sa.session_id = ses.id AND sa.student_id = s.id
            WHERE ses.id = ? AND cs.status = 'active'
            ORDER BY s.name ASC
        ");
        $stmt->execute([$session_id]);
        $attendances = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Ambil BAP sesi
        $sesStmt = $pdo->prepare("SELECT id, status, bap_notes, completed_at FROM class_sessions WHERE id = ?");
        $sesStmt->execute([$session_id]);
        $sesData = $sesStmt->fetch(PDO::FETCH_ASSOC);

        echo json_encode([
            "attendances" => $attendances,
            "students" => $attendances,
            "session" => $sesData
        ]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $attendances = $input['attendances'] ?? [];
        $teachingTopic = trim($input['teaching_topic'] ?? '');
        $topicNotes = trim($input['notes'] ?? '');
        $bap_notes = trim($input['bap_notes'] ?? '');
        if (!$bap_notes && ($teachingTopic || $topicNotes)) {
            $bap_notes = ($teachingTopic ? "[Materi/Topik: {$teachingTopic}]\n" : '') . $topicNotes;
        }
        $markCompleted = isset($input['mark_completed']) ? (bool)$input['mark_completed'] : true;

        if (empty($attendances) && !$bap_notes) {
            http_response_code(400); echo json_encode(["error" => "Data presensi atau catatan BAP tidak boleh kosong"]); exit;
        }

        $pdo->beginTransaction();
        try {
            if (!empty($attendances)) {
                $stmtAtt = $pdo->prepare("
                    INSERT INTO session_attendances (id, session_id, student_id, attendance_status, minutes_late, notes, check_in_time)
                    VALUES (?, ?, ?, ?, ?, ?, NOW())
                    ON DUPLICATE KEY UPDATE 
                        attendance_status = VALUES(attendance_status),
                        minutes_late = VALUES(minutes_late),
                        notes = VALUES(notes)
                ");

                foreach ($attendances as $att) {
                    $stdId = $att['student_id'] ?? '';
                    $status = $att['attendance_status'] ?? 'present';
                    $late = (int)($att['minutes_late'] ?? 0);
                    $notes = $att['notes'] ?? '';
                    if ($stdId) {
                        $attId = gen_uuid();
                        $stmtAtt->execute([$attId, $session_id, $stdId, $status, $late, $notes]);
                    }
                }
            }

            // Update Sesi BAP
            $upSql = "UPDATE class_sessions SET bap_notes = ?";
            $upParams = [$bap_notes];
            if ($markCompleted) {
                $upSql .= ", status = 'completed', completed_at = NOW()";
            }
            $upSql .= " WHERE id = ?";
            $upParams[] = $session_id;

            $pdo->prepare($upSql)->execute($upParams);
            $pdo->commit();

            echo json_encode(["success" => true, "message" => "Presensi dan Jurnal BAP berhasil disimpan"]);
            exit;
        } catch (\Exception $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal menyimpan presensi: " . $e->getMessage()]);
            exit;
        }
    }
}

// ========================================================
// 6. PORTAL GURU DATA (teacher_hub)
// ========================================================
elseif ($action === 'teacher_hub') {
    $myTeacherId = in_array($payload->role, ['teacher', 'guru', 'tutor']) ? ($payload->user_id ?? $payload->id) : ($input['teacher_id'] ?? $payload->id);

    // Ambil sesi mengajar guru ini
    $sesStmt = $pdo->prepare("
        SELECT ses.*, c.name as class_name, r.name as room_name,
               (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.id AND cs.status = 'active') as student_count
        FROM class_sessions ses
        JOIN classes c ON ses.class_id = c.id
        JOIN class_rooms r ON ses.room_id = r.id
        WHERE ses.teacher_id = ?
        ORDER BY ses.session_date ASC, ses.start_time ASC
        LIMIT 30
    ");
    $sesStmt->execute([$myTeacherId]);
    $mySessions = $sesStmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil daftar rombel/kelas guru ini
    $clsStmt = $pdo->prepare("
        SELECT DISTINCT c.*, r.name as room_name,
               (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = c.id AND cs.status = 'active') as student_count
        FROM classes c
        JOIN class_sessions ses ON ses.class_id = c.id
        LEFT JOIN class_rooms r ON c.default_room_id = r.id
        WHERE ses.teacher_id = ? AND c.status = 'active'
    ");
    $clsStmt->execute([$myTeacherId]);
    $myClasses = $clsStmt->fetchAll(PDO::FETCH_ASSOC);

    // Ambil daftar siswa unik yang diajar oleh guru ini
    $stdStmt = $pdo->prepare("
        SELECT DISTINCT s.id, s.name, s.email, s.phone, c.name as class_name
        FROM class_sessions ses
        JOIN class_students cs ON ses.class_id = cs.class_id AND cs.status = 'active'
        JOIN students s ON cs.student_id = s.id
        JOIN classes c ON cs.class_id = c.id
        WHERE ses.teacher_id = ?
        ORDER BY s.name ASC
        LIMIT 50
    ");
    $stdStmt->execute([$myTeacherId]);
    $myStudents = $stdStmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "status" => "SUCCESS",
        "sessions" => $mySessions,
        "upcoming_sessions" => $mySessions,
        "classes" => $myClasses,
        "students" => $myStudents,
        "stats" => [
            "total_sessions" => count($mySessions),
            "total_students" => count($myStudents),
            "total_classes" => count($myClasses)
        ]
    ]);
    exit;
}

// ========================================================
// 7. EARLY WARNING SYSTEM KEHADIRAN SISWA (< 75%)
// ========================================================
elseif ($action === 'ews_report') {
    // Formula Akurat: Sesi Dihitung Hanya Sejak Tanggal Siswa Bergabung (joined_at)
    $sql = "
        SELECT s.id as student_id, s.name, s.email, s.phone, s.telegram_chat_id, s.parent_telegram_id,
               c.id as class_id, c.name as class_name, cs.joined_at,
               COUNT(ses.id) as total_sessions,
               COUNT(CASE WHEN sa.attendance_status IN ('present', 'late') THEN 1 END) as attended_sessions,
               COUNT(CASE WHEN sa.attendance_status = 'absent' THEN 1 END) as absent_count,
               ROUND(
                 COALESCE(
                   (COUNT(CASE WHEN sa.attendance_status IN ('present', 'late') THEN 1 END) / NULLIF(COUNT(ses.id), 0)) * 100,
                   100
                 ), 1
               ) as attendance_percentage
        FROM class_students cs
        JOIN students s ON cs.student_id = s.id
        JOIN classes c ON cs.class_id = c.id
        JOIN class_sessions ses ON ses.class_id = c.id 
             AND ses.session_date >= DATE(cs.joined_at) 
             AND ses.status = 'completed'
        LEFT JOIN session_attendances sa ON sa.session_id = ses.id AND sa.student_id = s.id
        WHERE cs.status = 'active'
    ";
    $params = [];
    if (!$isSuper) {
        $sql .= " AND c.tenant_id = ?";
        $params[] = $tenant_id;
    }
    $sql .= " GROUP BY s.id, c.id HAVING (attendance_percentage < 75.0 OR absent_count >= 3) ORDER BY attendance_percentage ASC";

}

// ========================================================
// 8. CLASS OFFERINGS MANAGEMENT (PRD v1.1 E2, AC-11)
// ========================================================
elseif ($action === 'offerings') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $prog_id = $_GET['program_id'] ?? '';
        $r_mode  = $_GET['renewal_mode'] ?? '';
        $sql = "
            SELECT co.*, c.name as class_name, ap.name as program_name, p.name as plan_name, t.name as tenant_name,
                   (SELECT COUNT(*) FROM class_students cs WHERE cs.class_id = co.class_id AND cs.status = 'active') as enrolled_count
            FROM class_offerings co
            LEFT JOIN classes c ON co.class_id = c.id
            LEFT JOIN academic_programs ap ON co.academic_program = ap.code OR co.program_id = ap.id
            LEFT JOIN plans p ON co.plan_id = p.id
            LEFT JOIN tenants t ON co.tenant_id = t.id
            WHERE 1=1
        ";
        $params = [];
        if (!$isSuper && $payload->role !== 'student') {
            $sql .= " AND co.tenant_id = ?";
            $params[] = $tenant_id;
        }
        if ($prog_id) {
            $sql .= " AND (co.program_id = ? OR co.academic_program = ?)";
            $params[] = $prog_id;
            $params[] = $prog_id;
        }
        if ($r_mode) {
            $sql .= " AND co.renewal_mode = ?";
            $params[] = $r_mode;
        }
        $sql .= " ORDER BY co.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["success" => true, "offerings" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if (!in_array($payload->role, ['superadmin', 'admin'])) {
        http_response_code(403);
        echo json_encode(["error" => "Hanya Administrator yang dapat mengelola penawaran kelas offline."]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $name = safe_str($input['name'] ?? '');
        $program_id = safe_str($input['program_id'] ?? '');
        $academic_program = safe_str($input['academic_program'] ?? 'SNPMB/UTBK');
        $plan_id = safe_str($input['plan_id'] ?? '');
        $class_id = safe_str($input['class_id'] ?? '');
        $renewal_mode = in_array($input['renewal_mode'] ?? '', ['new_enrollment', 'extend_existing']) ? $input['renewal_mode'] : 'new_enrollment';
        $max_capacity = (int)($input['max_capacity'] ?? 15);
        $reg_open = !empty($input['registration_open_at']) ? $input['registration_open_at'] : null;
        $reg_close = !empty($input['registration_close_at']) ? $input['registration_close_at'] : null;
        $tId = $isSuper ? ($input['tenant_id'] ?? $tenant_id) : $tenant_id;

        if (!$name) {
            http_response_code(400); echo json_encode(["error" => "Nama penawaran wajib diisi"]); exit;
        }

        // AC-08/AC-18: If class_id specified, verify it belongs to administrator's tenant
        if (!$isSuper && !empty($class_id)) {
            $stmtClassTenant = $pdo->prepare("SELECT id FROM classes WHERE id = ? AND tenant_id = ? LIMIT 1");
            $stmtClassTenant->execute([$class_id, $tenant_id]);
            if (!$stmtClassTenant->fetch()) {
                http_response_code(403);
                echo json_encode(["error" => "Forbidden: Kelas rombel yang dipilih tidak berada di bawah cabang/tenant Anda"]);
                exit;
            }
        }

        $id = gen_uuid();
        $stmt = $pdo->prepare("
            INSERT INTO class_offerings 
              (id, tenant_id, program_id, academic_program, plan_id, class_id, name, renewal_mode, max_capacity, registration_open_at, registration_close_at, status)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'open')
        ");
        $stmt->execute([$id, $tId, $program_id ?: null, $academic_program, $plan_id ?: null, $class_id ?: null, $name, $renewal_mode, $max_capacity, $reg_open, $reg_close]);

        if ($class_id) {
            $pdo->prepare("UPDATE classes SET offering_id = ? WHERE id = ?")->execute([$id, $class_id]);
        }

        echo json_encode(["success" => true, "id" => $id, "message" => "Offering kelas berhasil dibuat"]);
        exit;
    }
}

// ========================================================
// 9. MAKEUP SESSIONS & QUOTA MANAGEMENT (PRD v1.1 E7, AC-15)
// ========================================================
elseif ($action === 'makeup_requests') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $sql = "
            SELECT mr.*, s.name as student_name, s.email as student_email,
                   os.session_date as origin_date, os.start_time as origin_time, os.subject as origin_subject,
                   ts.session_date as target_date, ts.start_time as target_time, ts.subject as target_subject,
                   tr.name as target_room_name,
                   c.name as class_name
            FROM makeup_requests mr
            JOIN students s ON mr.student_id = s.id
            JOIN class_sessions os ON mr.origin_session_id = os.id
            JOIN class_sessions ts ON mr.target_session_id = ts.id
            JOIN class_rooms tr ON ts.room_id = tr.id
            JOIN classes c ON os.class_id = c.id
            WHERE 1=1
        ";
        $params = [];
        if ($payload->role === 'student') {
            $sql .= " AND mr.student_id = ?";
            $params[] = $payload->id;
        } elseif (!$isSuper) {
            $sql .= " AND c.tenant_id = ?";
            $params[] = $tenant_id;
        }
        $sql .= " ORDER BY mr.created_at DESC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["success" => true, "makeup_requests" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    // Submit Makeup Request (Siswa atau Admin)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $student_id = ($payload->role === 'student') ? $payload->id : safe_str($input['student_id'] ?? '');
        $origin_session_id = safe_str($input['origin_session_id'] ?? '');
        $target_session_id = safe_str($input['target_session_id'] ?? '');
        $reason = safe_str($input['reason'] ?? '');

        if (!$student_id || !$origin_session_id || !$target_session_id || !$reason) {
            http_response_code(400);
            echo json_encode(["error" => "student_id, origin_session_id, target_session_id, dan reason wajib disertakan."]);
            exit;
        }

        // AC-15: Validasi Siswa benar-benar terdaftar di rombel sesi asal
        $stmt_enr_check = $pdo->prepare("
            SELECT cs.id 
            FROM class_students cs
            JOIN class_sessions ses ON cs.class_id = ses.class_id
            WHERE cs.student_id = ? AND ses.id = ? AND cs.status = 'active'
            LIMIT 1
        ");
        $stmt_enr_check->execute([$student_id, $origin_session_id]);
        if (!$stmt_enr_check->fetch()) {
            http_response_code(403);
            echo json_encode(["error" => "Anda tidak terdaftar aktif di rombel kelas untuk sesi asal tersebut."]);
            exit;
        }

        // AC-15: Quota Check (Maksimal 2 pengajuan makeup per periode 90 hari)
        $stmt_quota = $pdo->prepare("
            SELECT COUNT(*) FROM makeup_requests 
            WHERE student_id = ? AND status IN ('pending', 'approved', 'attended') 
              AND created_at > DATE_SUB(NOW(), INTERVAL 90 DAY)
        ");
        $stmt_quota->execute([$student_id]);
        $used_quota = (int)$stmt_quota->fetchColumn();
        if ($used_quota >= 2) {
            http_response_code(409);
            echo json_encode(["error" => "Batas kuota pengajuan makeup telah tercapai (Maksimal 2 pengajuan per periode - AC-15)."]);
            exit;
        }

        // AC-15: Target Session Capacity Check
        $stmt_target_cap = $pdo->prepare("
            SELECT cs.id, cs.session_date, cs.start_time, cr.capacity as room_capacity,
                   (SELECT COUNT(*) FROM session_attendances WHERE session_id = cs.id) as current_occupancy
            FROM class_sessions cs
            JOIN class_rooms cr ON cs.room_id = cr.id
            WHERE cs.id = ?
        ");
        $stmt_target_cap->execute([$target_session_id]);
        $target_ses = $stmt_target_cap->fetch(PDO::FETCH_ASSOC);

        if (!$target_ses) {
            http_response_code(404);
            echo json_encode(["error" => "Sesi pengganti tidak ditemukan."]);
            exit;
        }

        if ($target_ses['current_occupancy'] >= $target_ses['room_capacity']) {
            http_response_code(409);
            echo json_encode(["error" => "Kapasitas ruangan pada sesi pengganti telah penuh (AC-15)."]);
            exit;
        }

        $id = gen_uuid();
        $pdo->prepare("
            INSERT INTO makeup_requests (id, student_id, origin_session_id, target_session_id, reason, status)
            VALUES (?, ?, ?, ?, ?, 'pending')
        ")->execute([$id, $student_id, $origin_session_id, $target_session_id, $reason]);

        echo json_encode([
            "success" => true,
            "id" => $id,
            "message" => "Pengajuan makeup sesi berhasil diajukan dan menunggu persetujuan (Sisa kuota: " . (1 - $used_quota) . ")."
        ]);
        exit;
    }
}

elseif ($action === 'approve_makeup_request') {
    if (!in_array($payload->role, ['superadmin', 'admin', 'teacher'])) {
        http_response_code(403);
        echo json_encode(["error" => "Hanya staf internal yang berwenang menyetujui jadwal makeup."]);
        exit;
    }

    $request_id = safe_str($input['request_id'] ?? ($input['id'] ?? ''));
    $decision = safe_str($input['decision'] ?? 'approved'); // 'approved' or 'rejected'
    $notes = safe_str($input['notes'] ?? '');

    $stmt_req = $pdo->prepare("
        SELECT mr.*, c.tenant_id as class_tenant_id 
        FROM makeup_requests mr
        JOIN class_sessions cs ON mr.target_session_id = cs.id
        JOIN classes c ON cs.class_id = c.id
        WHERE mr.id = ?
    ");
    $stmt_req->execute([$request_id]);
    $req = $stmt_req->fetch(PDO::FETCH_ASSOC);

    if (!$req || $req['status'] !== 'pending') {
        http_response_code(400);
        echo json_encode(["error" => "Pengajuan makeup tidak ditemukan atau sudah diproses."]);
        exit;
    }

    // AC-08/AC-18: Strict tenant boundary check for branch admin
    if (!$isSuper && !empty($tenant_id) && $req['class_tenant_id'] !== $tenant_id) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Anda tidak berwenang memproses permohonan makeup untuk cabang/tenant lain."]);
        exit;
    }

    if ($decision === 'approved') {
        try {
            $pdo->beginTransaction();

            // Pessimistic lock on target session capacity
            $stmt_lock = $pdo->prepare("
                SELECT cs.id, cr.capacity, (SELECT COUNT(*) FROM session_attendances WHERE session_id = cs.id) as current_occupancy
                FROM class_sessions cs
                JOIN class_rooms cr ON cs.room_id = cr.id
                WHERE cs.id = ? FOR UPDATE
            ");
            $stmt_lock->execute([$req['target_session_id']]);
            $cap_row = $stmt_lock->fetch(PDO::FETCH_ASSOC);

            if ($cap_row && $cap_row['current_occupancy'] >= $cap_row['capacity']) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(["error" => "Kapasitas sesi pengganti telah penuh saat persetujuan diproses."]);
                exit;
            }

            $pdo->prepare("
                UPDATE makeup_requests 
                SET status = 'approved', approved_by = ?, approved_at = NOW(), notes = ?
                WHERE id = ?
            ")->execute([$payload->id, $notes, $request_id]);

            // Add student as makeup guest in target session
            $att_id = gen_uuid();
            $pdo->prepare("
                INSERT INTO session_attendances (id, session_id, student_id, attendance_status, is_makeup_guest)
                VALUES (?, ?, ?, 'absent', 1)
                ON DUPLICATE KEY UPDATE is_makeup_guest = 1
            ")->execute([$att_id, $req['target_session_id'], $req['student_id']]);

            $pdo->commit();
            echo json_encode(["success" => true, "message" => "Pengajuan makeup berhasil disetujui."]);
            exit;
        } catch (\PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Gagal memproses persetujuan makeup: " . $e->getMessage()]);
            exit;
        }
    } else {
        $pdo->prepare("
            UPDATE makeup_requests 
            SET status = 'rejected', approved_by = ?, approved_at = NOW(), notes = ?
            WHERE id = ?
        ")->execute([$payload->id, $notes, $request_id]);
        echo json_encode(["success" => true, "message" => "Pengajuan makeup ditolak."]);
        exit;
    }
}

// ========================================================
// 10. STUDENT OFFLINE PORTAL VIEWS
// ========================================================
elseif ($action === 'my_classes') {
    if ($payload->role !== 'student') { http_response_code(403); exit; }
    $stmt = $pdo->prepare("
        SELECT c.*, cr.name as room_name, t.name as tenant_name, cs.joined_at, cs.confirmed_at
        FROM class_students cs
        JOIN classes c ON cs.class_id = c.id
        LEFT JOIN class_rooms cr ON c.default_room_id = cr.id
        LEFT JOIN tenants t ON c.tenant_id = t.id
        WHERE cs.student_id = ? AND cs.status = 'active'
    ");
    $stmt->execute([$payload->id]);
    echo json_encode(["success" => true, "classes" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

elseif ($action === 'my_sessions') {
    if ($payload->role !== 'student') { http_response_code(403); exit; }
    $stmt = $pdo->prepare("
        SELECT ses.*, cr.name as room_name, c.name as class_name, sa.attendance_status, sa.check_in_time, sa.is_makeup_guest
        FROM class_students cs
        JOIN class_sessions ses ON cs.class_id = ses.class_id
        LEFT JOIN class_rooms cr ON ses.room_id = cr.id
        LEFT JOIN classes c ON ses.class_id = c.id
        LEFT JOIN session_attendances sa ON sa.session_id = ses.id AND sa.student_id = ?
        WHERE cs.student_id = ? AND cs.status = 'active'
        ORDER BY ses.session_date ASC, ses.start_time ASC
    ");
    $stmt->execute([$payload->id, $payload->id]);
    echo json_encode(["success" => true, "sessions" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

elseif ($action === 'my_attendance') {
    if ($payload->role !== 'student') { http_response_code(403); exit; }
    $stmt = $pdo->prepare("
        SELECT sa.*, ses.session_date, ses.start_time, ses.end_time, ses.subject, c.name as class_name
        FROM session_attendances sa
        JOIN class_sessions ses ON sa.session_id = ses.id
        JOIN classes c ON ses.class_id = c.id
        WHERE sa.student_id = ?
        ORDER BY ses.session_date DESC
    ");
    $stmt->execute([$payload->id]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total = count($records);
    $present = count(array_filter($records, fn($r) => in_array($r['attendance_status'], ['present', 'late'])));
    $rate = $total > 0 ? round(($present / $total) * 100, 1) : 100.0;

    echo json_encode([
        "success" => true,
        "summary" => [
            "total_sessions" => $total,
            "attended" => $present,
            "attendance_rate" => $rate
        ],
        "records" => $records
    ]);
    exit;
}

// ==============================================================================
// 10. CONFIGURABLE COHORT RENEWAL (PRD v1.1 Phase 5: AC-11, AC-12, AC-13)
// ==============================================================================
elseif ($action === 'renewal_policies') {
    if (!in_array($payload->role, ['superadmin', 'admin'])) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Admin only"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $offering_id = safe_str($_GET['offering_id'] ?? '');
        $sql = "SELECT rp.*, co.name as offering_name, co.renewal_mode as offering_renewal_mode
                FROM renewal_policies rp
                JOIN class_offerings co ON rp.offering_id = co.id
                WHERE 1=1";
        $params = [];
        if (!empty($offering_id)) {
            $sql .= " AND rp.offering_id = ?";
            $params[] = $offering_id;
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["success" => true, "policies" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $offering_id = safe_str($input['offering_id'] ?? '');
        $renewal_mode = safe_str($input['renewal_mode'] ?? 'new_enrollment');
        $renewal_price = floatval($input['renewal_price'] ?? 0.0);
        $extension_days = intval($input['extension_days'] ?? 30);
        $allowed_cohort_change = isset($input['allowed_cohort_change']) ? (int)$input['allowed_cohort_change'] : 1;
        $eligibility_rule = safe_str($input['eligibility_rule'] ?? 'active_or_grace');
        $open_at = !empty($input['open_at']) ? safe_str($input['open_at']) : null;
        $close_at = !empty($input['close_at']) ? safe_str($input['close_at']) : null;

        if (empty($offering_id)) {
            http_response_code(400);
            echo json_encode(["error" => "offering_id diperlukan"]);
            exit;
        }

        if (!in_array($renewal_mode, ['new_enrollment', 'extend_existing'], true)) {
            http_response_code(400);
            echo json_encode(["error" => "renewal_mode harus 'new_enrollment' atau 'extend_existing'"]);
            exit;
        }

        $id = gen_uuid();
        $stmt = $pdo->prepare("
            INSERT INTO renewal_policies (
                id, offering_id, renewal_mode, renewal_price, extension_days,
                allowed_cohort_change, eligibility_rule, open_at, close_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ON DUPLICATE KEY UPDATE
                renewal_mode = VALUES(renewal_mode),
                renewal_price = VALUES(renewal_price),
                extension_days = VALUES(extension_days),
                allowed_cohort_change = VALUES(allowed_cohort_change),
                eligibility_rule = VALUES(eligibility_rule),
                open_at = VALUES(open_at),
                close_at = VALUES(close_at)
        ");
        $stmt->execute([
            $id, $offering_id, $renewal_mode, $renewal_price, $extension_days,
            $allowed_cohort_change, $eligibility_rule, $open_at, $close_at
        ]);

        // Sinkronkan ke class_offerings.renewal_mode (AC-11)
        $pdo->prepare("UPDATE class_offerings SET renewal_mode = ? WHERE id = ?")->execute([$renewal_mode, $offering_id]);

        echo json_encode(["success" => true, "message" => "Kebijakan renewal berhasil disimpan"]);
        exit;
    }
}

elseif ($action === 'renewal_options') {
    $enrollment_id = safe_str($_GET['enrollment_id'] ?? ($input['enrollment_id'] ?? ''));
    if (empty($enrollment_id)) {
        http_response_code(400);
        echo json_encode(["error" => "enrollment_id diperlukan"]);
        exit;
    }

    $stmt = $pdo->prepare("
        SELECT cs.*, c.name as class_name, c.tenant_id, c.max_capacity,
               co.id as offering_id, co.name as offering_name, co.renewal_mode as offering_renewal_mode
        FROM class_students cs
        JOIN classes c ON cs.class_id = c.id
        LEFT JOIN class_offerings co ON cs.offering_id = co.id OR c.id = co.class_id
        WHERE cs.id = ?
        LIMIT 1
    ");
    $stmt->execute([$enrollment_id]);
    $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$enrollment) {
        http_response_code(404);
        echo json_encode(["error" => "Data enrollment tidak ditemukan"]);
        exit;
    }

    // Student authorization
    if ($payload->role === 'student' && $enrollment['student_id'] !== $payload->id) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Bukan enrollment Anda"]);
        exit;
    }

    // Ambil renewal policy jika ada
    $stmtPol = $pdo->prepare("SELECT * FROM renewal_policies WHERE offering_id = ? LIMIT 1");
    $stmtPol->execute([$enrollment['offering_id'] ?? '']);
    $policy = $stmtPol->fetch(PDO::FETCH_ASSOC);

    $renewalMode = $policy['renewal_mode'] ?? ($enrollment['offering_renewal_mode'] ?? 'new_enrollment');
    $renewalPrice = floatval($policy['renewal_price'] ?? 0.0);
    $extensionDays = intval($policy['extension_days'] ?? 30);
    $allowChangeCohort = (bool)($policy['allowed_cohort_change'] ?? 1);

    // Ambil cohort alternatif jika allowed
    $availableCohorts = [];
    if ($allowChangeCohort && !empty($enrollment['offering_id'])) {
        $stmtC = $pdo->prepare("
            SELECT c.id, c.name, c.max_capacity,
                   (SELECT COUNT(*) FROM class_students WHERE class_id = c.id AND status = 'active') as active_students
            FROM classes c
            JOIN class_offerings co ON co.class_id = c.id
            WHERE co.id = ? AND c.status = 'active'
        ");
        $stmtC->execute([$enrollment['offering_id']]);
        $availableCohorts = $stmtC->fetchAll(PDO::FETCH_ASSOC);
    }

    echo json_encode([
        "success" => true,
        "enrollment" => $enrollment,
        "policy" => [
            "renewal_mode" => $renewalMode,
            "renewal_price" => $renewalPrice,
            "extension_days" => $extensionDays,
            "allowed_cohort_change" => $allowChangeCohort
        ],
        "available_cohorts" => $availableCohorts
    ]);
    exit;
}

elseif ($action === 'renew_enrollment') {
    $enrollment_id = safe_str($input['enrollment_id'] ?? '');
    $target_class_id = safe_str($input['target_class_id'] ?? '');
    $order_id = safe_str($input['order_id'] ?? '');

    if (empty($enrollment_id)) {
        http_response_code(400);
        echo json_encode(["error" => "enrollment_id diperlukan"]);
        exit;
    }

    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            SELECT cs.*, c.name as class_name, c.tenant_id,
                   co.id as offering_id, co.renewal_mode as offering_renewal_mode
            FROM class_students cs
            JOIN classes c ON cs.class_id = c.id
            LEFT JOIN class_offerings co ON cs.offering_id = co.id OR c.id = co.class_id
            WHERE cs.id = ?
            FOR UPDATE
        ");
        $stmt->execute([$enrollment_id]);
        $enrollment = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$enrollment) {
            $pdo->rollBack();
            http_response_code(404);
            echo json_encode(["error" => "Data enrollment tidak ditemukan"]);
            exit;
        }

        if ($payload->role === 'student' && $enrollment['student_id'] !== $payload->id) {
            $pdo->rollBack();
            http_response_code(403);
            echo json_encode(["error" => "Forbidden: Anda hanya berwenang memperpanjang enrollment Anda sendiri"]);
            exit;
        }

        if ($enrollment['status'] !== 'active') {
            $pdo->rollBack();
            http_response_code(400);
            echo json_encode(["error" => "Hanya enrollment berstatus aktif yang dapat diperpanjang"]);
            exit;
        }

        // Ambil renewal policy
        $stmtPol = $pdo->prepare("SELECT * FROM renewal_policies WHERE offering_id = ? LIMIT 1");
        $stmtPol->execute([$enrollment['offering_id'] ?? '']);
        $policy = $stmtPol->fetch(PDO::FETCH_ASSOC);

        $renewalMode = $policy['renewal_mode'] ?? ($enrollment['offering_renewal_mode'] ?? 'new_enrollment');
        $extensionDays = intval($policy['extension_days'] ?? 30);
        $renewalPrice = floatval($policy['renewal_price'] ?? 0.0);
        $studentId = $enrollment['student_id'];
        $offeringId = $enrollment['offering_id'] ?? $enrollment['class_id'];
        $prevClassId = $enrollment['class_id'];
        $newClassId = !empty($target_class_id) ? $target_class_id : $prevClassId;
        $prevEndDate = $enrollment['valid_until'];

        // AC-11/AC-12: Validasi perpindahan cohort & batas tenant
        if (!empty($target_class_id) && $target_class_id !== $prevClassId) {
            if ($policy && isset($policy['allowed_cohort_change']) && !(int)$policy['allowed_cohort_change']) {
                $pdo->rollBack();
                http_response_code(403);
                echo json_encode(["error" => "Kebijakan offering ini tidak mengizinkan perpindahan rombel (cohort change not allowed)."]);
                exit;
            }

            // Validasi target class berada di tenant yang sama dan aktif
            $stmtTargetChk = $pdo->prepare("SELECT id, tenant_id, status FROM classes WHERE id = ?");
            $stmtTargetChk->execute([$target_class_id]);
            $targetClassRow = $stmtTargetChk->fetch(PDO::FETCH_ASSOC);

            if (!$targetClassRow || $targetClassRow['status'] !== 'active') {
                $pdo->rollBack();
                http_response_code(400);
                echo json_encode(["error" => "Rombel tujuan tidak aktif atau tidak ditemukan."]);
                exit;
            }

            if ($targetClassRow['tenant_id'] !== $enrollment['tenant_id']) {
                $pdo->rollBack();
                http_response_code(403);
                echo json_encode(["error" => "Forbidden: Rombel tujuan berada di cabang/tenant yang berbeda."]);
                exit;
            }
        }

        $renewalRecordId = gen_uuid();

        if ($renewalMode === 'new_enrollment') {
            // AC-12: Mode 1 (new_enrollment) -> membuat enrollment baru di class_students
            // Validasi kapasitas all_sessions rombel tujuan
            $capStmt = $pdo->prepare("SELECT max_capacity FROM classes WHERE id = ? FOR UPDATE");
            $capStmt->execute([$newClassId]);
            $maxCap = intval($capStmt->fetchColumn() ?: 15);

            $curCountStmt = $pdo->prepare("SELECT COUNT(*) FROM class_students WHERE class_id = ? AND status = 'active'");
            $curCountStmt->execute([$newClassId]);
            $curCount = intval($curCountStmt->fetchColumn());

            if ($curCount >= $maxCap) {
                $pdo->rollBack();
                http_response_code(409);
                echo json_encode(["error" => "Kapasitas rombel tujuan sudah penuh"]);
                exit;
            }

            $newEnrollmentId = gen_uuid();
            $newEndDate = date('Y-m-d', strtotime("+$extensionDays days"));

            $insertNew = $pdo->prepare("
                INSERT INTO class_students (
                    id, class_id, student_id, joined_at, valid_until,
                    offering_id, renewal_reference_id, status
                ) VALUES (?, ?, ?, NOW(), ?, ?, ?, 'active')
            ");
            $insertNew->execute([
                $newEnrollmentId,
                $newClassId,
                $studentId,
                $newEndDate,
                $offeringId,
                $enrollment_id
            ]);

            // Enrollment lama tetap ada sebagai histori, diubah statusnya menjadi 'completed'
            $pdo->prepare("UPDATE class_students SET status = 'completed' WHERE id = ?")->execute([$enrollment_id]);

            // Catat ke renewal_records (AC-12)
            $stmtRec = $pdo->prepare("
                INSERT INTO renewal_records (
                    id, original_enrollment_id, new_enrollment_id, student_id, offering_id,
                    renewal_mode, previous_class_id, new_class_id, previous_end_date, new_end_date,
                    price_paid, order_id, status
                ) VALUES (?, ?, ?, ?, ?, 'new_enrollment', ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmtRec->execute([
                $renewalRecordId,
                $enrollment_id,
                $newEnrollmentId,
                $studentId,
                $offeringId,
                $prevClassId,
                $newClassId,
                $prevEndDate,
                $newEndDate,
                $renewalPrice,
                $order_id ?: null
            ]);

            $pdo->commit();
            log_audit($pdo, $studentId, $enrollment['tenant_id'], 'cohort_renewal_new_enrollment', $newEnrollmentId, [
                'original_enrollment_id' => $enrollment_id,
                'target_class_id' => $newClassId
            ]);

            echo json_encode([
                "success" => true,
                "message" => "Enrollment baru berhasil dibuat melalui alur renewal (AC-12)",
                "data" => [
                    "renewal_mode" => "new_enrollment",
                    "original_enrollment_id" => $enrollment_id,
                    "new_enrollment_id" => $newEnrollmentId,
                    "valid_until" => $newEndDate,
                    "class_id" => $newClassId
                ]
            ]);
            exit;

        } else {
            // AC-13: Mode 2 (extend_existing) -> memperpanjang enrollment yang ada & menyimpan histori
            $baseDate = !empty($prevEndDate) && (strtotime($prevEndDate) > time()) ? $prevEndDate : date('Y-m-d');
            $newEndDate = date('Y-m-d', strtotime("$baseDate +$extensionDays days"));

            $updateExisting = $pdo->prepare("
                UPDATE class_students
                SET valid_until = ?, status = 'active'
                WHERE id = ?
            ");
            $updateExisting->execute([$newEndDate, $enrollment_id]);

            // Catat record histori perpanjangan tanpa menimpa histori sesi/presensi lama (AC-13)
            $stmtRec = $pdo->prepare("
                INSERT INTO renewal_records (
                    id, original_enrollment_id, new_enrollment_id, student_id, offering_id,
                    renewal_mode, previous_class_id, new_class_id, previous_end_date, new_end_date,
                    price_paid, order_id, status
                ) VALUES (?, ?, NULL, ?, ?, 'extend_existing', ?, ?, ?, ?, ?, ?, 'active')
            ");
            $stmtRec->execute([
                $renewalRecordId,
                $enrollment_id,
                $studentId,
                $offeringId,
                $prevClassId,
                $prevClassId,
                $prevEndDate,
                $newEndDate,
                $renewalPrice,
                $order_id ?: null
            ]);

            $pdo->commit();
            log_audit($pdo, $studentId, $enrollment['tenant_id'], 'cohort_renewal_extend_existing', $enrollment_id, [
                'previous_end_date' => $prevEndDate,
                'new_end_date' => $newEndDate
            ]);

            echo json_encode([
                "success" => true,
                "message" => "Enrollment berhasil diperpanjang dengan pencatatan histori renewal (AC-13)",
                "data" => [
                    "renewal_mode" => "extend_existing",
                    "enrollment_id" => $enrollment_id,
                    "previous_end_date" => $prevEndDate,
                    "new_end_date" => $newEndDate
                ]
            ]);
            exit;
        }

    } catch (\Throwable $e) {
        $pdo->rollBack();
        http_response_code(500);
        echo json_encode(["error" => "Gagal memproses perpanjangan: " . $e->getMessage()]);
        exit;
    }
}

elseif ($action === 'renewal_history') {
    $student_id = safe_str($_GET['student_id'] ?? '');
    $enrollment_id = safe_str($_GET['enrollment_id'] ?? '');

    if ($payload->role === 'student') {
        $student_id = $payload->id;
    }

    $sql = "
        SELECT rr.*,
               c1.name as previous_class_name,
               c2.name as new_class_name,
               co.name as offering_name,
               s.name as student_name
        FROM renewal_records rr
        LEFT JOIN classes c1 ON rr.previous_class_id = c1.id
        LEFT JOIN classes c2 ON rr.new_class_id = c2.id
        LEFT JOIN class_offerings co ON rr.offering_id = co.id
        LEFT JOIN students s ON rr.student_id = s.id
        WHERE 1=1
    ";
    $params = [];

    if (!empty($student_id)) {
        $sql .= " AND rr.student_id = ?";
        $params[] = $student_id;
    }

    if (!empty($enrollment_id)) {
        $sql .= " AND (rr.original_enrollment_id = ? OR rr.new_enrollment_id = ?)";
        $params[] = $enrollment_id;
        $params[] = $enrollment_id;
    }

    $sql .= " ORDER BY rr.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $history = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "history" => $history]);
    exit;
}

else {
    http_response_code(404);
    echo json_encode(["error" => "Action tidak valid pada modul kelas"]);
    exit;
}
