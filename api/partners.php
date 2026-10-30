<?php
// api/partners.php
// Dual Partner Operating Models (PRD v1.1 Phase 4: AC-08, AC-09, AC-10)

require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';
require_once 'PartnerService.php';

header('Content-Type: application/json; charset=utf-8');

$action = safe_str($_GET['action'] ?? 'list');
$input = json_decode(file_get_contents('php://input'), true) ?: [];

$payload = authenticate();
if (!$payload) {
    http_response_code(401);
    echo json_encode(["error" => "Unauthorized"]);
    exit;
}

$isSuperadmin = ($payload->role === 'superadmin');
$isAdmin = in_array($payload->role, ['admin', 'superadmin'], true);
$userTenantId = $payload->tenant_id ?? null;
$userId = $payload->user_id ?? ($payload->id ?? null);

// ─────────────────────────────────────────────────────────────────────────────
// 1. LIST PARTNER PROFILES
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'list' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Administrator only"]);
        exit;
    }

    $typeFilter = safe_str($_GET['partner_type'] ?? '');
    $statusFilter = safe_str($_GET['status'] ?? '');
    $search = safe_str($_GET['search'] ?? '');

    $sql = "
        SELECT p.*, t.name as tenant_name, u.identity_key as user_email,
               (SELECT COUNT(*) FROM partner_assignments WHERE partner_id = p.id AND status = 'active') as active_assignments_count
        FROM partner_profiles p
        LEFT JOIN tenants t ON p.tenant_id = t.id
        LEFT JOIN users u ON p.user_id = u.id
        WHERE 1=1
    ";
    $params = [];

    // AC-08: Tenant Admin hanya mengakses data profil dalam konteks tenant-nya sendiri
    if (!$isSuperadmin) {
        $sql .= " AND (p.tenant_id = ? OR p.tenant_id IS NULL)";
        $params[] = $userTenantId;
    }

    if (!empty($typeFilter)) {
        $sql .= " AND p.partner_type = ?";
        $params[] = $typeFilter;
    }

    if (!empty($statusFilter)) {
        $sql .= " AND p.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($search)) {
        $sql .= " AND (p.name LIKE ? OR p.code LIKE ? OR p.contact_email LIKE ?)";
        $sTerm = '%' . $search . '%';
        $params[] = $sTerm;
        $params[] = $sTerm;
        $params[] = $sTerm;
    }

    $sql .= " ORDER BY p.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $partners = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $partners]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 2. CREATE PARTNER PROFILE
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'create' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Administrator only"]);
        exit;
    }

    $partnerType = safe_str($input['partner_type'] ?? 'scoped_operator');
    $name = trim(safe_str($input['name'] ?? ''));
    $code = strtoupper(trim(safe_str($input['code'] ?? '')));
    $contactEmail = trim(safe_str($input['contact_email'] ?? ''));
    $contactPhone = trim(safe_str($input['contact_phone'] ?? ''));
    $revenueShare = floatval($input['revenue_share_pct'] ?? 0.0);
    $notes = safe_str($input['notes'] ?? '');
    $tenantId = safe_str($input['tenant_id'] ?? '');
    $operatorUserId = safe_str($input['user_id'] ?? '');

    if (empty($name)) {
        http_response_code(400);
        echo json_encode(["error" => "Nama mitra wajib diisi"]);
        exit;
    }

    if (!in_array($partnerType, ['tenant', 'scoped_operator'], true)) {
        http_response_code(400);
        echo json_encode(["error" => "partner_type harus 'tenant' atau 'scoped_operator'"]);
        exit;
    }

    // AC-08: Model 1 (Partner Tenant) hanya boleh dibuat oleh Superadmin
    if ($partnerType === 'tenant' && !$isSuperadmin) {
        http_response_code(403);
        echo json_encode(["error" => "Hanya Superadmin yang berwenang membuat Partner Tenant"]);
        exit;
    }

    // Auto-generate code if empty
    if (empty($code)) {
        $prefix = ($partnerType === 'tenant') ? 'PTN-' : 'POP-';
        $code = $prefix . strtoupper(substr(bin2hex(random_bytes(4)), 0, 6));
    }

    // Periksa keunikan code
    $stmtCheck = $pdo->prepare("SELECT id FROM partner_profiles WHERE code = ? LIMIT 1");
    $stmtCheck->execute([$code]);
    if ($stmtCheck->fetch()) {
        http_response_code(409);
        echo json_encode(["error" => "Kode mitra sudah digunakan. Gunakan kode lain."]);
        exit;
    }

    // Scope tenant isolation
    if (!$isSuperadmin) {
        $tenantId = $userTenantId;
    } else if (empty($tenantId) && $partnerType === 'tenant') {
        http_response_code(400);
        echo json_encode(["error" => "Partner Tenant wajib dihubungkan ke tenant_id"]);
        exit;
    }

    $id = bin2hex(random_bytes(16));
    $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);

    $stmt = $pdo->prepare("
        INSERT INTO partner_profiles (
            id, partner_type, name, code, tenant_id, user_id,
            contact_email, contact_phone, status, revenue_share_pct, notes
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', ?, ?)
    ");
    $stmt->execute([
        $id,
        $partnerType,
        $name,
        $code,
        $tenantId ?: null,
        $operatorUserId ?: null,
        $contactEmail ?: null,
        $contactPhone ?: null,
        $revenueShare,
        $notes ?: null
    ]);

    log_audit($pdo, $userId, $tenantId ?: null, 'partner_created', $id, [
        'partner_type' => $partnerType,
        'code' => $code,
        'name' => $name
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Mitra berhasil didaftarkan",
        "data" => ["id" => $id, "code" => $code, "partner_type" => $partnerType]
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 3. UPDATE PARTNER PROFILE
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'update' && in_array($_SERVER['REQUEST_METHOD'], ['PUT', 'POST'], true)) {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Administrator only"]);
        exit;
    }

    $id = safe_str($input['id'] ?? ($_GET['id'] ?? ''));
    if (empty($id)) {
        http_response_code(400);
        echo json_encode(["error" => "ID mitra diperlukan"]);
        exit;
    }

    $stmtFind = $pdo->prepare("SELECT * FROM partner_profiles WHERE id = ? LIMIT 1");
    $stmtFind->execute([$id]);
    $partner = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if (!$partner) {
        http_response_code(404);
        echo json_encode(["error" => "Mitra tidak ditemukan"]);
        exit;
    }

    // AC-08: Tenant Admin tidak boleh mengubah profil tenant lain
    if (!$isSuperadmin && $partner['tenant_id'] !== $userTenantId) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Anda tidak berwenang mengelola mitra cabang lain"]);
        exit;
    }

    $name = isset($input['name']) ? trim(safe_str($input['name'])) : $partner['name'];
    $contactEmail = isset($input['contact_email']) ? trim(safe_str($input['contact_email'])) : $partner['contact_email'];
    $contactPhone = isset($input['contact_phone']) ? trim(safe_str($input['contact_phone'])) : $partner['contact_phone'];
    $status = isset($input['status']) && in_array($input['status'], ['active', 'suspended', 'terminated'], true) ? $input['status'] : $partner['status'];
    $revenueShare = isset($input['revenue_share_pct']) ? floatval($input['revenue_share_pct']) : floatval($partner['revenue_share_pct']);
    $notes = isset($input['notes']) ? safe_str($input['notes']) : $partner['notes'];

    $stmtUpdate = $pdo->prepare("
        UPDATE partner_profiles
        SET name = ?, contact_email = ?, contact_phone = ?, status = ?, revenue_share_pct = ?, notes = ?
        WHERE id = ?
    ");
    $stmtUpdate->execute([$name, $contactEmail, $contactPhone, $status, $revenueShare, $notes, $id]);

    log_audit($pdo, $userId, $partner['tenant_id'], 'partner_updated', $id, [
        'status' => $status,
        'revenue_share_pct' => $revenueShare
    ]);

    echo json_encode(["success" => true, "message" => "Profil mitra berhasil diperbarui"]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 4. LIST ASSIGNMENTS (AC-09)
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'assignments' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $partnerId = safe_str($_GET['partner_id'] ?? '');
    $statusFilter = safe_str($_GET['status'] ?? '');
    $offeringId = safe_str($_GET['offering_id'] ?? '');
    $classId = safe_str($_GET['class_id'] ?? '');

    $sql = "
        SELECT pa.*, p.name as partner_name, p.partner_type, p.code as partner_code,
               u.identity_key as operator_email, co.name as offering_name, c.name as class_name,
               t.name as tenant_name
        FROM partner_assignments pa
        JOIN partner_profiles p ON pa.partner_id = p.id
        LEFT JOIN users u ON pa.operator_user_id = u.id
        LEFT JOIN class_offerings co ON pa.offering_id = co.id
        LEFT JOIN classes c ON pa.class_id = c.id
        LEFT JOIN tenants t ON pa.tenant_id = t.id
        WHERE 1=1
    ";
    $params = [];

    if (!$isSuperadmin) {
        if ($isAdmin) {
            // Tenant admin: filter by tenant
            $sql .= " AND (pa.tenant_id = ? OR pa.tenant_id IS NULL)";
            $params[] = $userTenantId;
        } else {
            // Operator / Teacher: AC-09: hanya melihat tugas aktif milik sendiri
            $sql .= " AND (pa.operator_user_id = ? OR p.user_id = ?) AND pa.status = 'active'";
            $params[] = $userId;
            $params[] = $userId;
        }
    }

    if (!empty($partnerId)) {
        $sql .= " AND pa.partner_id = ?";
        $params[] = $partnerId;
    }

    if (!empty($statusFilter)) {
        $sql .= " AND pa.status = ?";
        $params[] = $statusFilter;
    }

    if (!empty($offeringId)) {
        $sql .= " AND pa.offering_id = ?";
        $params[] = $offeringId;
    }

    if (!empty($classId)) {
        $sql .= " AND pa.class_id = ?";
        $params[] = $classId;
    }

    $sql .= " ORDER BY pa.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(["success" => true, "data" => $assignments]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 5. CREATE ASSIGNMENT (Model 2: Scoped Operator)
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'create_assignment' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Administrator only"]);
        exit;
    }

    $partnerId = safe_str($input['partner_id'] ?? '');
    $operatorUserId = safe_str($input['operator_user_id'] ?? '');
    $tenantId = safe_str($input['tenant_id'] ?? '');
    $location = safe_str($input['location'] ?? '');
    $offeringId = safe_str($input['offering_id'] ?? '');
    $classId = safe_str($input['class_id'] ?? '');
    $sessionId = safe_str($input['session_id'] ?? '');
    $startDate = safe_str($input['start_date'] ?? date('Y-m-d'));
    $endDate = !empty($input['end_date']) ? safe_str($input['end_date']) : null;

    if (empty($partnerId)) {
        http_response_code(400);
        echo json_encode(["error" => "partner_id diperlukan"]);
        exit;
    }

    // Verify partner existence
    $stmtP = $pdo->prepare("SELECT * FROM partner_profiles WHERE id = ? LIMIT 1");
    $stmtP->execute([$partnerId]);
    $partner = $stmtP->fetch(PDO::FETCH_ASSOC);
    if (!$partner) {
        http_response_code(404);
        echo json_encode(["error" => "Profil mitra tidak ditemukan"]);
        exit;
    }

    // Scoped operator validation: cannot mix models ambiguously
    if ($partner['partner_type'] === 'tenant' && empty($tenantId)) {
        $tenantId = $partner['tenant_id'];
    }

    if (!$isSuperadmin) {
        $tenantId = $userTenantId;
    }

    $assignmentId = bin2hex(random_bytes(16));
    $assignmentId = substr($assignmentId,0,8).'-'.substr($assignmentId,8,4).'-'.substr($assignmentId,12,4).'-'.substr($assignmentId,16,4).'-'.substr($assignmentId,20,12);

    $stmtInsert = $pdo->prepare("
        INSERT INTO partner_assignments (
            id, partner_id, operator_user_id, tenant_id, location,
            offering_id, class_id, session_id, start_date, end_date,
            status, created_by
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'active', ?)
    ");
    $stmtInsert->execute([
        $assignmentId,
        $partnerId,
        $operatorUserId ?: null,
        $tenantId ?: null,
        $location ?: null,
        $offeringId ?: null,
        $classId ?: null,
        $sessionId ?: null,
        $startDate,
        $endDate,
        $userId
    ]);

    // Log assignment creation
    $stmtLog = $pdo->prepare("
        INSERT INTO partner_assignment_logs (assignment_id, action, actor_id, metadata)
        VALUES (?, 'created', ?, ?)
    ");
    $stmtLog->execute([
        $assignmentId,
        $userId,
        json_encode([
            "partner_id" => $partnerId,
            "offering_id" => $offeringId,
            "class_id" => $classId,
            "start_date" => $startDate,
            "end_date" => $endDate
        ])
    ]);

    log_audit($pdo, $userId, $tenantId ?: null, 'partner_assignment_created', $assignmentId, [
        'partner_id' => $partnerId,
        'class_id' => $classId
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Assignment mitra berhasil dibuat",
        "data" => ["id" => $assignmentId, "status" => "active"]
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 6. REVOKE ASSIGNMENT (AC-10: Revocation menghentikan akses tanpa menghapus histori)
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'revoke_assignment' && in_array($_SERVER['REQUEST_METHOD'], ['POST', 'DELETE'], true)) {
    if (!$isAdmin) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Administrator only"]);
        exit;
    }

    $assignmentId = safe_str($input['id'] ?? ($_GET['id'] ?? ''));
    $reason = trim(safe_str($input['reason'] ?? 'Pencabutan tugas operasional'));

    if (empty($assignmentId)) {
        http_response_code(400);
        echo json_encode(["error" => "ID assignment diperlukan"]);
        exit;
    }

    $stmtFind = $pdo->prepare("SELECT * FROM partner_assignments WHERE id = ? LIMIT 1");
    $stmtFind->execute([$assignmentId]);
    $assign = $stmtFind->fetch(PDO::FETCH_ASSOC);

    if (!$assign) {
        http_response_code(404);
        echo json_encode(["error" => "Assignment tidak ditemukan"]);
        exit;
    }

    if (!$isSuperadmin && $assign['tenant_id'] !== $userTenantId) {
        http_response_code(403);
        echo json_encode(["error" => "Forbidden: Anda tidak berwenang mencabut penugasan cabang lain"]);
        exit;
    }

    if ($assign['status'] === 'revoked') {
        echo json_encode(["success" => true, "message" => "Assignment sudah berstatus dicabut sebelumnya"]);
        exit;
    }

    // AC-10 Invariant: TIDAK MENGHAPUS ROW DARI DATABASE (TIDAK ADA DELETE STATEMENT).
    // HANYA MENGUBAH STATUS KE 'revoked', MENCATAT TIMESTAMP, ACTOR, DAN REASON.
    $stmtRevoke = $pdo->prepare("
        UPDATE partner_assignments
        SET status = 'revoked',
            revoked_at = NOW(),
            revoked_by = ?,
            revocation_reason = ?
        WHERE id = ?
    ");
    $stmtRevoke->execute([$userId, $reason, $assignmentId]);

    // Audit trail log
    $stmtLog = $pdo->prepare("
        INSERT INTO partner_assignment_logs (assignment_id, action, actor_id, metadata)
        VALUES (?, 'revoked', ?, ?)
    ");
    $stmtLog->execute([
        $assignmentId,
        $userId,
        json_encode([
            "revoked_at" => date('Y-m-d H:i:s'),
            "reason" => $reason,
            "previous_status" => $assign['status']
        ])
    ]);

    log_audit($pdo, $userId, $assign['tenant_id'], 'partner_assignment_revoked', $assignmentId, [
        'reason' => $reason
    ]);

    echo json_encode([
        "success" => true,
        "message" => "Assignment berhasil dicabut. Seluruh histori akademik, kelas, dan transaksi siswa tetap aman tersimpan.",
        "data" => [
            "id" => $assignmentId,
            "status" => "revoked",
            "revoked_at" => date('Y-m-d H:i:s')
        ]
    ]);
    exit;
}

// ─────────────────────────────────────────────────────────────────────────────
// 7. MY ACTIVE ASSIGNMENTS (AC-09: Scoped Operator Active Tasks)
// ─────────────────────────────────────────────────────────────────────────────
if ($action === 'my_assignments' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT pa.*, p.name as partner_name, p.code as partner_code,
               co.name as offering_name, c.name as class_name, t.name as tenant_name
        FROM partner_assignments pa
        JOIN partner_profiles p ON pa.partner_id = p.id
        LEFT JOIN class_offerings co ON pa.offering_id = co.id
        LEFT JOIN classes c ON pa.class_id = c.id
        LEFT JOIN tenants t ON pa.tenant_id = t.id
        WHERE (pa.operator_user_id = ? OR p.user_id = ?)
          AND pa.status = 'active'
          AND (CURRENT_DATE >= pa.start_date)
          AND (pa.end_date IS NULL OR CURRENT_DATE <= pa.end_date)
        ORDER BY pa.start_date ASC
    ");
    $stmt->execute([$userId, $userId]);
    $activeAssignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "data" => $activeAssignments,
        "count" => count($activeAssignments)
    ]);
    exit;
}

http_response_code(400);
echo json_encode(["error" => "Aksi tidak dikenali"]);
