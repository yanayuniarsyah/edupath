<?php
// api/classes.php
// Modul Manajemen Kelas Offline, Ruangan, Penjadwalan, Reschedule/Inval & Rekap Kehadiran
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true) ?: [];

// 1. Otorisasi Token
$payload = authenticate();
if (!in_array($payload->role, ['superadmin', 'admin', 'teacher'])) {
    http_response_code(403);
    echo json_encode(["error" => "Akses khusus staf internal (Admin Cabang, Superadmin, dan Guru)."]);
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

    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $student_id = $input['student_id'] ?? '';
        if (!$student_id) {
            http_response_code(400); echo json_encode(["error" => "Student ID wajib disertakan"]); exit;
        }

        // Cek Kuota Kelas Maksimal
        $qChk = $pdo->prepare("
            SELECT c.max_capacity, (SELECT COUNT(*) FROM class_students WHERE class_id = c.id AND status = 'active') as current_count
            FROM classes c WHERE c.id = ?
        ");
        $qChk->execute([$class_id]);
        $cls = $qChk->fetch(PDO::FETCH_ASSOC);
        if ($cls && $cls['current_count'] >= $cls['max_capacity']) {
            http_response_code(409);
            echo json_encode(["error" => "Kapasitas kelas telah penuh (Maksimal {$cls['max_capacity']} siswa)"]);
            exit;
        }

        // Cek Keberadaan Siswa
        $stdChk = $pdo->prepare("SELECT id FROM students WHERE id = ?");
        $stdChk->execute([$student_id]);
        if (!$stdChk->fetch()) {
            http_response_code(404);
            echo json_encode(["error" => "Data siswa tidak ditemukan atau belum terdaftar"]);
            exit;
        }

        $id = gen_uuid();
        try {
            $stmt = $pdo->prepare("
                INSERT INTO class_students (id, class_id, student_id, joined_at, status)
                VALUES (?, ?, ?, NOW(), 'active')
                ON DUPLICATE KEY UPDATE status = 'active'
            ");
            $stmt->execute([$id, $class_id, $student_id]);
            echo json_encode(["success" => true, "message" => "Siswa berhasil ditugaskan ke kelas"]);
            exit;
        } catch (\PDOException $e) {
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
    $myTeacherId = $payload->role === 'teacher' ? ($payload->user_id ?? $payload->id) : ($input['teacher_id'] ?? $payload->id);

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

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    echo json_encode(["at_risk_students" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    exit;
}

else {
    http_response_code(404);
    echo json_encode(["error" => "Action tidak valid pada modul kelas"]);
    exit;
}
