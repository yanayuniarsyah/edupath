<?php
// api/admin.php
require_once 'config.php';
require_once 'jwt.php';
require_once 'rate_limit.php';

$action = $_GET['action'] ?? '';
$input = json_decode(file_get_contents('php://input'), true);

if ($action === 'login' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    // Rate limiting untuk admin login — 5 percobaan per 30 menit
    if (!check_rate_limit($pdo, 'admin_login', 5, 30)) {
        http_response_code(429);
        echo json_encode(["error" => "Terlalu banyak percobaan login. Coba lagi nanti."]);
        exit;
    }

    $username = $input['username'] ?? '';
    $password = $input['password'] ?? '';

    // Join with tenants to check if tenant is active (only for non-superadmin)
    $stmt = $pdo->prepare("
        SELECT u.id as user_id, u.password, ur.role, ur.tenant_id, ur.reference_id, a.id as admin_id, t.is_active as tenant_active
        FROM users u 
        JOIN user_roles ur ON u.id = ur.user_id 
        LEFT JOIN admins a ON ur.reference_id = a.id
        LEFT JOIN tenants t ON ur.tenant_id = t.id
        WHERE u.identity_key = ? AND u.is_active = 1 AND ur.role IN ('admin', 'superadmin')
    ");
    $stmt->execute([$username]);
    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($admin && password_verify($password, $admin['password'])) {
        if ($admin['role'] === 'admin' && (int)$admin['tenant_active'] === 0) {
            http_response_code(403);
            echo json_encode(["error" => "Tenant anda telah dinonaktifkan."]);
            exit;
        }

        reset_rate_limit($pdo, 'admin_login');
        $token = generate_jwt([
            'user_id' => $admin['user_id'], 
            'id' => $admin['reference_id'], 
            'role' => $admin['role'], 
            'tenant_id' => $admin['tenant_id'] // superadmin will naturally have NULL here
        ]);
        unset($admin['password']);
        echo json_encode(["token" => $token, "user" => $admin]);
    } else {
        log_audit($pdo, null, null, 'admin_login_failed', null, ['username' => $username, 'ip' => $_SERVER['REMOTE_ADDR'] ?? '']);
        http_response_code(401);
        echo json_encode(["error" => "Username atau password salah"]);
    }
    exit;
}

// Untuk rute di bawah ini, wajib login sebagai admin atau superadmin
$payload = authenticate();
if (!in_array($payload->role, ['admin', 'superadmin'])) {
    http_response_code(403);
    echo json_encode(["error" => "Forbidden"]);
    exit;
}

if ($action === 'stats' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    if ($payload->role === 'superadmin') {
        // Superadmin stats (Global)
        $stmt = $pdo->query("SELECT COUNT(*) FROM tenants");
        $totalTenants = $stmt->fetchColumn();
        echo json_encode(["totalTenants" => (int)$totalTenants]);
        exit;
    }

    $tenant_id = $payload->tenant_id;
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $totalStudents = $stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) FROM students WHERE is_active = 1 AND tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $activeStudents = $stmt->fetchColumn();

    $totalQuizzes = $pdo->query("SELECT COUNT(*) FROM questions")->fetchColumn();
    
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM orders o JOIN students s ON o.student_id = s.id WHERE o.status IN ('paid', 'settlement') AND s.tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $totalRevenue = $stmt->fetchColumn() ?: 0;
    
    // Total subscriptions (B2C + B2B within tenant)
    $stmt = $pdo->prepare("
        SELECT COUNT(*) FROM subscriptions s
        LEFT JOIN students st ON s.student_id = st.id
        WHERE (s.tenant_id = ? OR st.tenant_id = ?) AND s.status = 'active'
    ");
    $stmt->execute([$tenant_id, $tenant_id]);
    $activeSubscriptions = $stmt->fetchColumn() ?: 0;

    // Affiliate summary
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM affiliates WHERE tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $totalAffiliates = $stmt->fetchColumn() ?: 0;

    // Commission summary (only paid)
    $stmt = $pdo->prepare("SELECT SUM(amount) FROM commissions WHERE status = 'paid' AND tenant_id = ?");
    $stmt->execute([$tenant_id]);
    $totalCommissions = $stmt->fetchColumn() ?: 0;

    echo json_encode([
        "totalStudents" => (int)$totalStudents,
        "activeStudents" => (int)$activeStudents,
        "totalQuizzes" => (int)$totalQuizzes,
        "totalRevenue" => (float)$totalRevenue,
        "activeSubscriptions" => (int)$activeSubscriptions,
        "totalAffiliates" => (int)$totalAffiliates,
        "totalCommissions" => (float)$totalCommissions
    ]);
}
elseif ($action === 'students') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $stmt = $pdo->prepare("SELECT id, name, email, plan, is_active FROM students WHERE tenant_id = ? ORDER BY created_at DESC");
        $stmt->execute([$payload->tenant_id]);
        echo json_encode($stmt->fetchAll());
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        $name = trim($input['name'] ?? '');
        $email = trim($input['email'] ?? '');
        $password = $input['password'] ?? '';
        $plan = $input['plan'] ?? 'free';
        
        if (empty($name) || empty($email) || empty($password)) {
            http_response_code(400); echo json_encode(["error" => "Name, email, and password required"]); exit;
        }
        
        $user_id = bin2hex(random_bytes(16));
        $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
        
        $student_id = bin2hex(random_bytes(16));
        $student_id = substr($student_id,0,8).'-'.substr($student_id,8,4).'-'.substr($student_id,12,4).'-'.substr($student_id,16,4).'-'.substr($student_id,20,12);
        
        $tenant_id = $payload->tenant_id;
        if ($payload->role === 'superadmin' && !$tenant_id) {
            $tenant = $pdo->query("SELECT id FROM tenants LIMIT 1")->fetch();
            $tenant_id = $tenant ? $tenant['id'] : null;
        }

        try {
            $pdo->beginTransaction();
            $hashed = password_hash($password, PASSWORD_BCRYPT);
            
            $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password) VALUES (?, ?, ?)");
            $stmt->execute([$user_id, $email, $hashed]);
            
            $stmt = $pdo->prepare("INSERT INTO students (id, user_id, tenant_id, name, email, plan) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$student_id, $user_id, $tenant_id, $name, $email, $plan]);
            
            $ur_id = bin2hex(random_bytes(16));
            $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
            $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)");
            $stmt->execute([$ur_id, $user_id, $tenant_id, $student_id]);
            
            $pdo->commit();
            echo json_encode(["success" => true, "id" => $student_id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal membuat siswa", "details" => $e->getMessage()]);
        }
    }
}
elseif ($action === 'orders' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT o.*, s.name as student_name, s.email as student_email 
        FROM orders o 
        JOIN students s ON o.student_id = s.id 
        WHERE s.tenant_id = ?
        ORDER BY o.created_at DESC
    ");
    $stmt->execute([$payload->tenant_id]);
    echo json_encode($stmt->fetchAll());
}
elseif ($action === 'affiliates' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $stmt = $pdo->prepare("
        SELECT a.*, u.identity_key 
        FROM affiliates a 
        JOIN users u ON a.user_id = u.id 
        WHERE a.tenant_id = ? 
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$payload->tenant_id]);
    echo json_encode($stmt->fetchAll());
}
elseif ($action === 'tenants') {
    if ($payload->role !== 'superadmin') {
        http_response_code(403);
        echo json_encode(["error" => "System admin only"]);
        exit;
    }

    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode($pdo->query("SELECT * FROM tenants ORDER BY created_at DESC")->fetchAll());
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $admin_email = trim($input['admin_email'] ?? '');
        $admin_name = trim($input['admin_name'] ?? '');
        $admin_password = $input['admin_password'] ?? '';
        
        try {
            $pdo->beginTransaction();
            
            // 1. Create Tenant
            $stmt = $pdo->prepare("INSERT INTO tenants (id, name, slug, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$id, $input['name'], $input['slug']]);
            
            // 2. Provision First Admin (if provided)
            if (!empty($admin_email) && !empty($admin_password)) {
                $hashed_password = password_hash($admin_password, PASSWORD_BCRYPT);
                
                // Create user identity
                $user_id = bin2hex(random_bytes(16));
                $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
                
                $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)");
                $stmt->execute([$user_id, $admin_email, $hashed_password]);
                
                // Create admin profile
                $admin_id = bin2hex(random_bytes(16));
                $admin_id = substr($admin_id,0,8).'-'.substr($admin_id,8,4).'-'.substr($admin_id,12,4).'-'.substr($admin_id,16,4).'-'.substr($admin_id,20,12);
                
                $stmt = $pdo->prepare("INSERT INTO admins (id, username, password, name) VALUES (?, ?, ?, ?)");
                // Admin profile legacy password field is populated for compatibility
                $stmt->execute([$admin_id, $admin_email, $hashed_password, $admin_name ?: 'Admin']);
                
                // Link Role
                $ur_id = bin2hex(random_bytes(16));
                $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
                
                $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'admin', ?)");
                $stmt->execute([$ur_id, $user_id, $id, $admin_id]);
            }
            
            $pdo->commit();
            log_audit($pdo, $payload->user_id, null, 'tenant_created', $id, ['name' => $input['name'], 'slug' => $input['slug']]);
            echo json_encode(["success" => true, "id" => $id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500);
            echo json_encode(["error" => "Gagal membuat tenant", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = $input['id'] ?? '';
        $is_active = isset($input['is_active']) ? (int)$input['is_active'] : 1;
        $stmt = $pdo->prepare("UPDATE tenants SET name=?, slug=?, is_active=? WHERE id=?");
        $stmt->execute([$input['name'], $input['slug'], $is_active, $id]);
        
        log_audit($pdo, $payload->user_id, null, 'tenant_updated', $id, ['is_active' => $is_active]);
        
        echo json_encode(["success" => true]);
    }
}
elseif ($action === 'entitlements_dictionary') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode([
            ['key' => 'tryout_unlimited', 'label' => 'Tryout Tanpa Batas'],
            ['key' => 'ai_adaptive_path', 'label' => 'Jalur Belajar AI Adaptif'],
            ['key' => 'premium_materials', 'label' => 'Materi Premium'],
            ['key' => 'feature_quiz', 'label' => 'Akses Kuis Reguler']
        ]);
    }
}
elseif ($action === 'plans') {
    $VALID_ENTITLEMENTS = ['tryout_unlimited', 'ai_adaptive_path', 'premium_materials', 'feature_quiz'];
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $plans = $pdo->query("SELECT * FROM plans ORDER BY price ASC")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($plans as &$plan) {
            $stmt = $pdo->prepare("SELECT feature_key FROM plan_entitlements WHERE plan_id = ?");
            $stmt->execute([$plan['id']]);
            $features = $stmt->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($features)) {
                $plan['features'] = json_encode($features);
            }
        }
        echo json_encode($plans);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403); echo json_encode(["error" => "System admin only"]); exit;
        }
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $features = is_array($input['features']) ? $input['features'] : [];
        $valid_features = array_intersect($features, $VALID_ENTITLEMENTS);
        $discount = isset($input['discount']) ? (float)$input['discount'] : 0.00;
        
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("INSERT INTO plans (id, tenant_id, name, price, discount, duration, features) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$id, null, $input['name'], $input['price'], $discount, $input['duration'], json_encode(array_values($valid_features))]);
            
            $stmt_ent = $pdo->prepare("INSERT IGNORE INTO plan_entitlements (id, plan_id, feature_key) VALUES (UUID(), ?, ?)");
            foreach ($valid_features as $fk) {
                $stmt_ent->execute([$id, $fk]);
            }
            $pdo->commit();
            echo json_encode(["success" => true, "id" => $id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal membuat plan", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403); echo json_encode(["error" => "System admin only"]); exit;
        }
        $id = $input['id'] ?? '';
        $features = is_array($input['features']) ? $input['features'] : [];
        $valid_features = array_intersect($features, $VALID_ENTITLEMENTS);
        
        $discount = isset($input['discount']) ? (float)$input['discount'] : 0.00;
        
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE plans SET name=?, price=?, discount=?, duration=?, features=? WHERE id=?");
            $stmt->execute([$input['name'], $input['price'], $discount, $input['duration'], json_encode(array_values($valid_features)), $id]);
            
            $pdo->prepare("DELETE FROM plan_entitlements WHERE plan_id = ?")->execute([$id]);
            
            $stmt_ent = $pdo->prepare("INSERT IGNORE INTO plan_entitlements (id, plan_id, feature_key) VALUES (UUID(), ?, ?)");
            foreach ($valid_features as $fk) {
                $stmt_ent->execute([$id, $fk]);
            }
            $pdo->commit();
            echo json_encode(["success" => true]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal update plan", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403); echo json_encode(["error" => "System admin only"]); exit;
        }
        $id = $_GET['id'] ?? '';
        $pdo->prepare("DELETE FROM plans WHERE id = ?")->execute([$id]);
        echo json_encode(["success" => true]);
    }
}
elseif ($action === 'questions') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        echo json_encode($pdo->query("SELECT * FROM questions ORDER BY created_at DESC")->fetchAll());
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "System admin only"]);
            exit;
        }
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $stmt = $pdo->prepare("INSERT INTO questions (id, sub_materi, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, explanation, cognitive_demand, source_type, rights_status, source_name, source_year, source_reference) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        $sub_materi = $input['sub_materi'] ?? $input['subtes'] ?? '';
        $stmt->execute([
            $id, $sub_materi, $input['bab']??null, $input['difficulty']??'medium', 
            $input['question'], $input['option_a'], $input['option_b'], $input['option_c'], 
            $input['option_d'], $input['option_e']??null, $input['correct'], $input['explanation']??null,
            $input['cognitive_demand']??null, $input['source_type']??'author_created', $input['rights_status']??'unknown',
            $input['source_name']??null, $input['source_year']??null, $input['source_reference']??null
        ]);
        echo json_encode(["success" => true, "id" => $id]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "System admin only"]);
            exit;
        }
        $id = $input['id'] ?? '';
        $stmt = $pdo->prepare("UPDATE questions SET sub_materi=?, bab=?, difficulty=?, question=?, option_a=?, option_b=?, option_c=?, option_d=?, option_e=?, correct=?, explanation=?, cognitive_demand=?, source_type=?, rights_status=?, source_name=?, source_year=?, source_reference=? WHERE id=?");
        $sub_materi = $input['sub_materi'] ?? $input['subtes'] ?? '';
        $stmt->execute([
            $sub_materi, $input['bab']??null, $input['difficulty']??'medium', 
            $input['question'], $input['option_a'], $input['option_b'], $input['option_c'], 
            $input['option_d'], $input['option_e']??null, $input['correct'], $input['explanation']??null,
            $input['cognitive_demand']??null, $input['source_type']??'author_created', $input['rights_status']??'unknown',
            $input['source_name']??null, $input['source_year']??null, $input['source_reference']??null,
            $id
        ]);
        echo json_encode(["success" => true]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        if ($payload->role !== 'superadmin') {
            http_response_code(403);
            echo json_encode(["error" => "System admin only"]);
            exit;
        }
        $id = $_GET['id'] ?? '';
        $pdo->prepare("DELETE FROM questions WHERE id = ?")->execute([$id]);
        echo json_encode(["success" => true]);
    }
}
elseif ($action === 'staff') {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        $tenant_id = $payload->role === 'superadmin' ? null : $payload->tenant_id;
        
        $sql = "
            SELECT u.identity_key as username, a.name, a.id, ur.role
            FROM users u
            JOIN user_roles ur ON u.id = ur.user_id
            JOIN admins a ON ur.reference_id = a.id
            WHERE ur.role != 'superadmin'
        ";
        $params = [];
        if ($tenant_id) {
            $sql .= " AND ur.tenant_id = ?";
            $params[] = $tenant_id;
        } else {
            $sql .= " AND ur.tenant_id IS NULL";
        }
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        echo json_encode(["staff" => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $username = trim($input['username'] ?? '');
        $name = trim($input['name'] ?? '');
        $role = $input['role'] ?? 'teacher';
        $password = $input['password'] ?? '';
        
        if (empty($username) || empty($password)) {
            http_response_code(400); echo json_encode(["error" => "Username dan password diperlukan"]); exit;
        }

        $tenant_id = $payload->role === 'superadmin' ? null : $payload->tenant_id;
        if (!in_array($role, ['admin', 'teacher'])) $role = 'teacher';

        try {
            $pdo->beginTransaction();
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);
            
            $user_id = bin2hex(random_bytes(16));
            $user_id = substr($user_id,0,8).'-'.substr($user_id,8,4).'-'.substr($user_id,12,4).'-'.substr($user_id,16,4).'-'.substr($user_id,20,12);
            
            $stmt = $pdo->prepare("INSERT INTO users (id, identity_key, password, is_active) VALUES (?, ?, ?, 1)");
            $stmt->execute([$user_id, $username, $hashed_password]);
            
            $admin_id = bin2hex(random_bytes(16));
            $admin_id = substr($admin_id,0,8).'-'.substr($admin_id,8,4).'-'.substr($admin_id,12,4).'-'.substr($admin_id,16,4).'-'.substr($admin_id,20,12);
            
            $stmt = $pdo->prepare("INSERT INTO admins (id, username, password, name) VALUES (?, ?, ?, ?)");
            $stmt->execute([$admin_id, $username, $hashed_password, $name]);
            
            $ur_id = bin2hex(random_bytes(16));
            $ur_id = substr($ur_id,0,8).'-'.substr($ur_id,8,4).'-'.substr($ur_id,12,4).'-'.substr($ur_id,16,4).'-'.substr($ur_id,20,12);
            
            $stmt = $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, ?, ?)");
            $stmt->execute([$ur_id, $user_id, $tenant_id, $role, $admin_id]);
            
            $pdo->commit();
            log_audit($pdo, $payload->user_id, $tenant_id, 'staff_created', $admin_id, ['username' => $username, 'role' => $role]);
            echo json_encode(["success" => true, "id" => $admin_id]);
        } catch (PDOException $e) {
            $pdo->rollBack();
            http_response_code(500); echo json_encode(["error" => "Gagal membuat staff", "details" => $e->getMessage()]);
        }
    } elseif ($_SERVER['REQUEST_METHOD'] === 'PUT') {
        $id = $input['id'] ?? '';
        $name = trim($input['name'] ?? '');
        $role = $input['role'] ?? 'teacher';
        
        $stmt = $pdo->prepare("UPDATE admins SET name = ? WHERE id = ?");
        $stmt->execute([$name, $id]);
        
        $stmt = $pdo->prepare("UPDATE user_roles SET role = ? WHERE reference_id = ? AND role != 'superadmin'");
        $stmt->execute([$role, $id]);
        
        if (!empty($input['password'])) {
            $hashed = password_hash($input['password'], PASSWORD_BCRYPT);
            $stmt = $pdo->prepare("UPDATE users u JOIN user_roles ur ON u.id = ur.user_id SET u.password = ? WHERE ur.reference_id = ?");
            $stmt->execute([$hashed, $id]);
            $stmt = $pdo->prepare("UPDATE admins SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $id]);
        }
        echo json_encode(["success" => true]);
    } elseif ($_SERVER['REQUEST_METHOD'] === 'DELETE') {
        $id = $_GET['id'] ?? '';
        $stmt = $pdo->prepare("DELETE FROM users WHERE id IN (SELECT user_id FROM user_roles WHERE reference_id = ? AND role != 'superadmin')");
        $stmt->execute([$id]);
        echo json_encode(["success" => true]);
    }
}
else {
    http_response_code(404);
    echo json_encode(["error" => "Endpoint admin tidak ditemukan"]);
}
?>
