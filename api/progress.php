<?php
// api/progress.php
// Unified Academic Engine & Student Progress (PRD v1.1 Phase 6: AC-16, AC-17)

require_once 'config.php';
require_once 'jwt.php';

header('Content-Type: application/json; charset=utf-8');

$action = safe_str($_GET['action'] ?? 'unified');
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$payload = authenticate();
if (!$payload) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$studentId = $payload->id;
if ($payload->role !== 'student') {
    // If admin or teacher views a student's progress
    $targetStudentId = safe_str($_GET['student_id'] ?? '');
    if (!empty($targetStudentId) && in_array($payload->role, ['superadmin', 'admin', 'teacher', 'guru', 'tutor'], true)) {
        $studentId = $targetStudentId;
    } else {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Khusus siswa atau staf akademik"]);
        exit;
    }
}

// ─────────────────────────────────────────────────────────────────────────────
// 1. UNIFIED STUDENT ACADEMIC PROGRESS (AC-16, AC-17)
// ─────────────────────────────────────────────────────────────────────────────
if (in_array($action, ['unified', 'get'], true) && $_SERVER['REQUEST_METHOD'] === 'GET') {
    // 1. Student Profile
    $stmtStudent = $pdo->prepare("
        SELECT s.id, s.name, s.email, s.school, s.plan, s.tenant_id, s.created_at,
               t.name as tenant_name
        FROM students s
        LEFT JOIN tenants t ON s.tenant_id = t.id
        WHERE s.id = ?
        LIMIT 1
    ");
    $stmtStudent->execute([$studentId]);
    $student = $stmtStudent->fetch(PDO::FETCH_ASSOC) ?: [];

    if (empty($student)) {
        http_response_code(404);
        echo json_encode(["error" => "Student tidak ditemukan"]);
        exit;
    }

    // AC-08/AC-18: Tenant boundary enforcement for branch admin & teachers
    if ($payload->role !== 'student' && $payload->role !== 'superadmin' && !empty($payload->tenant_id)) {
        if (!empty($student['tenant_id']) && $student['tenant_id'] !== $payload->tenant_id) {
            http_response_code(403);
            echo json_encode(["error" => "Forbidden: Anda tidak berwenang melihat progress siswa cabang lain"]);
            exit;
        }
    }

    // 2. Online Topic & Quiz Progress
    $stmtProgress = $pdo->prepare("SELECT * FROM progress WHERE student_id = ? ORDER BY last_study DESC");
    $stmtProgress->execute([$studentId]);
    $topicProgress = $stmtProgress->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 3. CBT & Tryout Stats
    $stmtCBT = $pdo->prepare("
        SELECT 
            COUNT(id) as total_tests,
            COALESCE(AVG(score), 0) as avg_score,
            COALESCE(MAX(score), 0) as highest_score,
            COALESCE(SUM(correct), 0) as total_correct,
            COALESCE(SUM(total), 0) as total_questions
        FROM quiz_results
        WHERE student_id = ?
    ");
    $stmtCBT->execute([$studentId]);
    $cbtSummary = $stmtCBT->fetch(PDO::FETCH_ASSOC);

    $stmtRecentCBT = $pdo->prepare("
        SELECT id, quiz_type, assessment_type, track, subtes, score, correct, total, duration_sec, created_at
        FROM quiz_results
        WHERE student_id = ?
        ORDER BY created_at DESC
        LIMIT 5
    ");
    $stmtRecentCBT->execute([$studentId]);
    $recentCBT = $stmtRecentCBT->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 4. Offline Class & Cohort Enrollments
    $stmtClasses = $pdo->prepare("
        SELECT cs.id as enrollment_id, cs.class_id, cs.joined_at, cs.valid_until, cs.renewal_reference_id, cs.status,
               c.name as class_name, c.academic_year,
               co.name as offering_name, co.renewal_mode,
               cr.name as default_room_name
        FROM class_students cs
        JOIN classes c ON cs.class_id = c.id
        LEFT JOIN class_offerings co ON cs.offering_id = co.id OR c.id = co.class_id
        LEFT JOIN class_rooms cr ON c.default_room_id = cr.id
        WHERE cs.student_id = ? AND cs.status = 'active'
    ");
    $stmtClasses->execute([$studentId]);
    $enrolledClasses = $stmtClasses->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 5. Attendance Summary
    $stmtAtt = $pdo->prepare("
        SELECT 
            COUNT(sa.id) as total_attended_records,
            SUM(CASE WHEN sa.attendance_status = 'present' THEN 1 ELSE 0 END) as present_count,
            SUM(CASE WHEN sa.attendance_status = 'late' THEN 1 ELSE 0 END) as late_count,
            SUM(CASE WHEN sa.attendance_status = 'absent' THEN 1 ELSE 0 END) as absent_count,
            SUM(CASE WHEN sa.attendance_status = 'excused' THEN 1 ELSE 0 END) as excused_count
        FROM session_attendances sa
        WHERE sa.student_id = ?
    ");
    $stmtAtt->execute([$studentId]);
    $attendanceStats = $stmtAtt->fetch(PDO::FETCH_ASSOC);
    $totalAttended = intval($attendanceStats['total_attended_records'] ?? 0);
    $presentLike = intval($attendanceStats['present_count'] ?? 0) + intval($attendanceStats['late_count'] ?? 0);
    $attendanceRate = $totalAttended > 0 ? round(($presentLike / $totalAttended) * 100, 1) : 100.0;

    // 6. Upcoming Sessions for Enrolled Classes
    $stmtUpcoming = $pdo->prepare("
        SELECT ses.id, ses.class_id, ses.session_date, ses.start_time, ses.end_time, ses.subject,
               c.name as class_name, cr.name as room_name
        FROM class_students cs
        JOIN class_sessions ses ON cs.class_id = ses.class_id
        JOIN classes c ON ses.class_id = c.id
        LEFT JOIN class_rooms cr ON ses.room_id = cr.id
        WHERE cs.student_id = ? AND cs.status = 'active'
          AND (ses.session_date > CURRENT_DATE OR (ses.session_date = CURRENT_DATE AND ses.end_time >= CURRENT_TIME))
          AND ses.status = 'scheduled'
        ORDER BY ses.session_date ASC, ses.start_time ASC
        LIMIT 3
    ");
    $stmtUpcoming->execute([$studentId]);
    $upcomingSessions = $stmtUpcoming->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // 7. Diagnostic Potential (SPP)
    $stmtSPP = $pdo->prepare("
        SELECT id, attempt_status, scoring_status, quality_status, duration_seconds, submitted_at
        FROM spp_attempts
        WHERE student_id = ?
        ORDER BY started_at DESC
        LIMIT 1
    ");
    $stmtSPP->execute([$studentId]);
    $latestSPP = $stmtSPP->fetch(PDO::FETCH_ASSOC);

    // 8. Active Entitlements
    $stmtEnt = $pdo->prepare("
        SELECT feature_code, source_type, is_active, expires_at
        FROM entitlements
        WHERE user_id = ? AND is_active = 1 AND (expires_at IS NULL OR expires_at >= NOW())
    ");
    $stmtEnt->execute([$studentId]);
    $activeEntitlements = $stmtEnt->fetchAll(PDO::FETCH_ASSOC) ?: [];

    // Response structure
    $unified = [
        "student" => $student,
        "online_learning" => [
            "topic_progress" => $topicProgress,
            "cbt_summary" => [
                "total_tests" => intval($cbtSummary['total_tests'] ?? 0),
                "avg_score" => round(floatval($cbtSummary['avg_score'] ?? 0), 1),
                "highest_score" => round(floatval($cbtSummary['highest_score'] ?? 0), 1),
                "total_correct" => intval($cbtSummary['total_correct'] ?? 0),
                "total_questions" => intval($cbtSummary['total_questions'] ?? 0)
            ],
            "recent_cbt_tests" => $recentCBT
        ],
        "offline_learning" => [
            "enrolled_cohorts" => $enrolledClasses,
            "attendance_summary" => [
                "total_sessions_recorded" => $totalAttended,
                "present" => intval($attendanceStats['present_count'] ?? 0),
                "late" => intval($attendanceStats['late_count'] ?? 0),
                "absent" => intval($attendanceStats['absent_count'] ?? 0),
                "excused" => intval($attendanceStats['excused_count'] ?? 0),
                "attendance_rate_pct" => $attendanceRate
            ],
            "upcoming_sessions" => $upcomingSessions
        ],
        "diagnostic_spp" => $latestSPP ?: null,
        "entitlements" => $activeEntitlements
    ];

    if ($action === 'get' && empty($_GET['unified'])) {
        // Backward compatible raw array if caller expected flat rows
        echo json_encode($topicProgress);
    } else {
        echo json_encode([
            "success" => true,
            "data" => $unified
        ]);
    }
    exit;
}

http_response_code(400);
echo json_encode(["error" => "Aksi tidak dikenali pada progress module"]);
