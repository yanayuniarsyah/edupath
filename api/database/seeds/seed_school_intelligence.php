<?php
// api/database/seeds/seed_school_intelligence.php
// PRD: EDUPATH SCHOOL INTELLIGENCE (v1.2, Baseline)
// Complete Data Dummy Seeder for School Intelligence (SD, SMP, SMA, BK, Siswa, Rapor, CBT, Konseling)

require_once dirname(__DIR__, 2) . '/config.php';

function seed_school_intelligence_data(PDO $pdo): array {
    $summary = [];

    // 1. Ensure Phase 15 Tables Exist
    require_once dirname(__DIR__) . '/migrations/phase15_school_intelligence.php';
    run_phase15_migration($pdo);

    $tenant_id = 'default_tenant';
    $password_hash = password_hash('password123', PASSWORD_BCRYPT);
    $ciamis_hash = password_hash('EduPathSchool01!2026', PASSWORD_BCRYPT);

    // -------------------------------------------------------------
    // 1. SEKOLAH & AKUN GURU BK (SD, SMP, SMA)
    // -------------------------------------------------------------
    $schools = [
        [
            'id' => 'sch-sma-001',
            'name' => 'SMAN 1 EduPath Unggulan Jakarta',
            'slug' => 'sman-1-edupath-jakarta',
            'npsn' => '20101234',
            'city' => 'Jakarta Selatan',
            'province' => 'DKI Jakarta',
            'bk_name' => 'Dra. Ratna Kusuma, M.Pd',
            'bk_phone' => '081234567801',
            'bk_email' => 'bk.sma@edupath.id',
            'user_id' => 'usr-bk-sma-001',
            'pass_hash' => $password_hash,
            'level' => 'SMA'
        ],
        [
            'id' => 'sch-smp-001',
            'name' => 'SMP EduPath Cendekia Bandung',
            'slug' => 'smp-edupath-cendekia-bdg',
            'npsn' => '20205678',
            'city' => 'Bandung',
            'province' => 'Jawa Barat',
            'bk_name' => 'Budi Santoso, S.Pd',
            'bk_phone' => '081234567802',
            'bk_email' => 'bk.smp@edupath.id',
            'user_id' => 'usr-bk-smp-001',
            'pass_hash' => $password_hash,
            'level' => 'SMP'
        ],
        [
            'id' => 'sch-sd-001',
            'name' => 'SD EduPath Prestasi Mandiri Surabaya',
            'slug' => 'sd-edupath-prestasi-sby',
            'npsn' => '20309876',
            'city' => 'Surabaya',
            'province' => 'Jawa Timur',
            'bk_name' => 'Siti Rahma, S.Pd',
            'bk_phone' => '081234567803',
            'bk_email' => 'bk.sd@edupath.id',
            'user_id' => 'usr-bk-sd-001',
            'pass_hash' => $password_hash,
            'level' => 'SD'
        ],
        [
            'id' => 'sch-ciamis-001',
            'name' => 'SMAN 1 Ciamis',
            'slug' => 'smansa-ciamis',
            'npsn' => '20211543',
            'city' => 'Kabupaten Ciamis',
            'province' => 'Jawa Barat',
            'bk_name' => 'Hj. Nenden Nurhayati, M.Pd',
            'bk_phone' => '081394556677',
            'bk_email' => 'bk.smansaciamis@edupath.co.id',
            'user_id' => 'usr-bk-ciamis-001',
            'pass_hash' => $ciamis_hash,
            'level' => 'SMA'
        ]
    ];

    // 1. Insert/Update Schools & Guru BK Accounts
    $inSchool = $pdo->prepare("
        INSERT INTO schools (id, tenant_id, name, slug, npsn, city, province, bk_name, bk_phone, bk_email, estimated_students, is_active)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 300, 1)
        ON DUPLICATE KEY UPDATE 
            name = VALUES(name), bk_name = VALUES(bk_name), bk_phone = VALUES(bk_phone), 
            city = VALUES(city), province = VALUES(province), is_active = 1
    ");

    foreach ($schools as $s) {
        $email = strtolower(trim($s['bk_email']));
        $inSchool->execute([
            $s['id'], $tenant_id, $s['name'], $s['slug'], $s['npsn'],
            $s['city'], $s['province'], $s['bk_name'], $s['bk_phone'], $email
        ]);

        // Check if user already exists in users table to prevent FK 1452 error
        $stmtU = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(identity_key)) = ? LIMIT 1");
        $stmtU->execute([$email]);
        $existingUid = $stmtU->fetchColumn();

        if ($existingUid) {
            $uid = $existingUid;
            $pdo->prepare("UPDATE users SET password = ?, is_active = 1 WHERE id = ?")
                ->execute([$s['pass_hash'], $uid]);
        } else {
            $uid = $s['user_id'];
            $pdo->prepare("INSERT INTO users (id, identity_key, password, display_name, is_active) VALUES (?, ?, ?, ?, 1)")
                ->execute([$uid, $email, $s['pass_hash'], $s['bk_name']]);
        }

        // Clean existing role and bind with verified uid
        try {
            $pdo->prepare("DELETE FROM user_roles WHERE user_id = ? OR (reference_id = ? AND role = 'school_bk')")
                ->execute([$uid, $s['id']]);
            $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'school_bk', ?)")
                ->execute(['rol-' . bin2hex(random_bytes(8)), $uid, $tenant_id, $s['id']]);
        } catch (\Throwable $e) {}
    }
    $summary['schools_seeded'] = count($schools);

    $students = [
        // SMA (SMAN 1 EduPath Unggulan Jakarta)
        [
            'id' => 'std-sma-001',
            'school_id' => 'sch-sma-001',
            'school_name' => 'SMAN 1 EduPath Unggulan Jakarta',
            'name' => 'Muhammad Rizky Pratama',
            'email' => 'rizky.sma@edupath.id',
            'phone' => '081299887701',
            'target_ptn' => 'Pendidikan Dokter - Universitas Indonesia (UI)',
            'plan' => 'pejuang_ptn',
            'academic_program' => 'SNPMB_UTBK',
            'target_level' => 'SMA',
            'grade_level' => 'Kelas 12',
            'class_name' => '12 MIPA 1',
            'gpa_category' => 'high'
        ],
        [
            'id' => 'std-sma-002',
            'school_id' => 'sch-sma-001',
            'school_name' => 'SMAN 1 EduPath Unggulan Jakarta',
            'name' => 'Aulia Zahra Putri',
            'email' => 'aulia.sma@edupath.id',
            'phone' => '081299887702',
            'target_ptn' => 'Teknik Informatika - Institut Teknologi Bandung (ITB)',
            'plan' => 'mandiri',
            'academic_program' => 'SNPMB_UTBK',
            'target_level' => 'SMA',
            'grade_level' => 'Kelas 12',
            'class_name' => '12 MIPA 1',
            'gpa_category' => 'high'
        ],
        [
            'id' => 'std-sma-003',
            'school_id' => 'sch-sma-001',
            'school_name' => 'SMAN 1 EduPath Unggulan Jakarta',
            'name' => 'Dimas Surya Nugraha',
            'email' => 'dimas.sma@edupath.id',
            'phone' => '081299887703',
            'target_ptn' => 'Manajemen - Universitas Gadjah Mada (UGM)',
            'plan' => 'pejuang_ptn',
            'academic_program' => 'SNPMB_UTBK',
            'target_level' => 'SMA',
            'grade_level' => 'Kelas 12',
            'class_name' => '12 IPS 1',
            'gpa_category' => 'med'
        ],
        [
            'id' => 'std-sma-004',
            'school_id' => 'sch-sma-001',
            'school_name' => 'SMAN 1 EduPath Unggulan Jakarta',
            'name' => 'Nabila Anindya',
            'email' => 'nabila.sma@edupath.id',
            'phone' => '081299887704',
            'target_ptn' => 'Psikologi - Universitas Padjadjaran (UNPAD)',
            'plan' => 'elite',
            'academic_program' => 'SNPMB_UTBK',
            'target_level' => 'SMA',
            'grade_level' => 'Kelas 12',
            'class_name' => '12 IPS 2',
            'gpa_category' => 'med'
        ],
        [
            'id' => 'std-sma-005',
            'school_id' => 'sch-sma-001',
            'school_name' => 'SMAN 1 EduPath Unggulan Jakarta',
            'name' => 'Fajar Hidayat',
            'email' => 'fajar.sma@edupath.id',
            'phone' => '081299887705',
            'target_ptn' => 'Farmasi - Universitas Airlangga (UNAIR)',
            'plan' => 'pejuang_ptn',
            'academic_program' => 'SNPMB_UTBK',
            'target_level' => 'SMA',
            'grade_level' => 'Kelas 12',
            'class_name' => '12 MIPA 2',
            'gpa_category' => 'gap'
        ],
        // SMAN 1 Ciamis
        [
            'id' => 'std-ciamis-001',
            'school_id' => 'sch-ciamis-001',
            'school_name' => 'SMAN 1 Ciamis',
            'name' => 'Rangga Wijaya Kusuma',
            'email' => 'rangga.ciamis@edupath.id',
            'phone' => '081322114455',
            'target_ptn' => 'Teknik Elektro - ITB',
            'plan' => 'pejuang_ptn',
            'academic_program' => 'SNPMB_UTBK',
            'target_level' => 'SMA',
            'grade_level' => 'Kelas 12',
            'class_name' => '12 MIPA 1',
            'gpa_category' => 'high'
        ],
        [
            'id' => 'std-ciamis-002',
            'school_id' => 'sch-ciamis-001',
            'school_name' => 'SMAN 1 Ciamis',
            'name' => 'Tiara Salma Dewi',
            'email' => 'tiara.ciamis@edupath.id',
            'phone' => '081322114456',
            'target_ptn' => 'Akuntansi - Universitas Padjadjaran (UNPAD)',
            'plan' => 'mandiri',
            'academic_program' => 'SNPMB_UTBK',
            'target_level' => 'SMA',
            'grade_level' => 'Kelas 12',
            'class_name' => '12 IPS 1',
            'gpa_category' => 'med'
        ],
        // SMP (SMP EduPath Cendekia Bandung)
        [
            'id' => 'std-smp-001',
            'school_id' => 'sch-smp-001',
            'school_name' => 'SMP EduPath Cendekia Bandung',
            'name' => 'Alif Pratama Putra',
            'email' => 'alif.smp@edupath.id',
            'phone' => '082155667701',
            'target_ptn' => 'Target SMA: SMAN 3 Bandung (Unggulan)',
            'plan' => 'tka_smp',
            'academic_program' => 'TKA_SMP',
            'target_level' => 'SMP',
            'grade_level' => 'Kelas 9',
            'class_name' => '9A',
            'gpa_category' => 'high'
        ],
        [
            'id' => 'std-smp-002',
            'school_id' => 'sch-smp-001',
            'school_name' => 'SMP EduPath Cendekia Bandung',
            'name' => 'Farah Diba Ayu',
            'email' => 'farah.smp@edupath.id',
            'phone' => '082155667702',
            'target_ptn' => 'Target SMA: SMAN 5 Bandung',
            'plan' => 'tka_smp',
            'academic_program' => 'TKA_SMP',
            'target_level' => 'SMP',
            'grade_level' => 'Kelas 9',
            'class_name' => '9B',
            'gpa_category' => 'med'
        ],
        [
            'id' => 'std-smp-003',
            'school_id' => 'sch-smp-001',
            'school_name' => 'SMP EduPath Cendekia Bandung',
            'name' => 'Kevin Julio Pangestu',
            'email' => 'kevin.smp@edupath.id',
            'phone' => '082155667703',
            'target_ptn' => 'Target SMK: SMKN 1 Bandung (Teknologi Informasi)',
            'plan' => 'tka_smp',
            'academic_program' => 'TKA_SMP',
            'target_level' => 'SMP',
            'grade_level' => 'Kelas 9',
            'class_name' => '9C',
            'gpa_category' => 'gap'
        ],
        // SD (SD EduPath Prestasi Mandiri Surabaya)
        [
            'id' => 'std-sd-001',
            'school_id' => 'sch-sd-001',
            'school_name' => 'SD EduPath Prestasi Mandiri Surabaya',
            'name' => 'Kenzo Ardiansyah',
            'email' => 'kenzo.sd@edupath.id',
            'phone' => '083144556601',
            'target_ptn' => 'Target SMP: SMP Negeri 1 Surabaya',
            'plan' => 'tka_sd',
            'academic_program' => 'TKA_SD',
            'target_level' => 'SD',
            'grade_level' => 'Kelas 6',
            'class_name' => '6A',
            'gpa_category' => 'high'
        ],
        [
            'id' => 'std-sd-002',
            'school_id' => 'sch-sd-001',
            'school_name' => 'SD EduPath Prestasi Mandiri Surabaya',
            'name' => 'Clarissa Aurelia',
            'email' => 'clarissa.sd@edupath.id',
            'phone' => '083144556602',
            'target_ptn' => 'Target SMP: SMP Labschool Surabaya',
            'plan' => 'tka_sd',
            'academic_program' => 'TKA_SD',
            'target_level' => 'SD',
            'grade_level' => 'Kelas 6',
            'class_name' => '6B',
            'gpa_category' => 'med'
        ]
    ];

    // 2. Insert/Update Students (SD, SMP, SMA)
    foreach ($students as $st) {
        $email = strtolower(trim($st['email']));

        // Check if user exists in users table to respect FK constraint ur_user_fk
        $stmtU = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(identity_key)) = ? LIMIT 1");
        $stmtU->execute([$email]);
        $existingUid = $stmtU->fetchColumn();

        if ($existingUid) {
            $uid = $existingUid;
            $pdo->prepare("UPDATE users SET password = ?, display_name = ?, is_active = 1 WHERE id = ?")
                ->execute([$password_hash, $st['name'], $uid]);
        } else {
            $uid = 'usr-' . $st['id'];
            $pdo->prepare("INSERT INTO users (id, identity_key, password, display_name, is_active) VALUES (?, ?, ?, ?, 1)")
                ->execute([$uid, $email, $password_hash, $st['name']]);
        }

        // Upsert into students table
        $inStudent = $pdo->prepare("
            INSERT INTO students (id, tenant_id, school_id, school, name, email, password, phone, target_ptn, plan, academic_program, target_level, grade_level, is_active)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)
            ON DUPLICATE KEY UPDATE 
                school_id = VALUES(school_id), school = VALUES(school), name = VALUES(name),
                password = VALUES(password), phone = VALUES(phone),
                target_ptn = VALUES(target_ptn), plan = VALUES(plan), 
                academic_program = VALUES(academic_program), target_level = VALUES(target_level),
                grade_level = VALUES(grade_level), is_active = 1
        ");
        $inStudent->execute([
            $st['id'], $tenant_id, $st['school_id'], $st['school_name'],
            $st['name'], $email, $password_hash, $st['phone'],
            $st['target_ptn'], $st['plan'], $st['academic_program'],
            $st['target_level'], $st['grade_level']
        ]);

        $stmtS = $pdo->prepare("SELECT id FROM students WHERE LOWER(TRIM(email)) = ? LIMIT 1");
        $stmtS->execute([$email]);
        $actualStdId = $stmtS->fetchColumn() ?: $st['id'];

        // Clean and bind user_role with verified uid
        try {
            $pdo->prepare("DELETE FROM user_roles WHERE user_id = ? OR (reference_id = ? AND role = 'student')")
                ->execute([$uid, $actualStdId]);
            $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'student', ?)")
                ->execute(['rol-' . bin2hex(random_bytes(8)), $uid, $tenant_id, $actualStdId]);
        } catch (\Throwable $e) {}
    }

    // 2b. GURU / TUTOR BIMBEL DUMMY
    $guruEmail = 'guru.matematika@edupath.id';
    $stmtG = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(identity_key)) = ? LIMIT 1");
    $stmtG->execute([$guruEmail]);
    $gUid = $stmtG->fetchColumn();

    if ($gUid) {
        $pdo->prepare("UPDATE users SET password = ?, display_name = 'Hendra Wijaya, S.Si (Guru Matematika)', is_active = 1 WHERE id = ?")
            ->execute([$password_hash, $gUid]);
    } else {
        $gUid = 'usr-guru-001';
        $pdo->prepare("INSERT INTO users (id, identity_key, password, display_name, is_active) VALUES (?, ?, ?, 'Hendra Wijaya, S.Si (Guru Matematika)', 1)")
            ->execute([$gUid, $guruEmail, $password_hash]);
    }

    try {
        $pdo->prepare("DELETE FROM user_roles WHERE user_id = ? OR role = 'teacher'")->execute([$gUid]);
        $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'teacher', ?)")
            ->execute(['rol-guru-001', $gUid, $tenant_id, $gUid]);
    } catch (\Throwable $e) {}

    $summary['students_seeded'] = count($students);

    // 2b. GURU / TUTOR BIMBEL DUMMY
    $guruEmail = 'guru.matematika@edupath.id';
    $stmtG = $pdo->prepare("SELECT id FROM users WHERE LOWER(TRIM(identity_key)) = ? LIMIT 1");
    $stmtG->execute([$guruEmail]);
    $gUid = $stmtG->fetchColumn() ?: 'usr-guru-001';
    
    $pdo->prepare("
        INSERT INTO users (id, identity_key, password, display_name, is_active)
        VALUES (?, ?, ?, 'Hendra Wijaya, S.Si (Guru Matematika)', 1)
        ON DUPLICATE KEY UPDATE password = VALUES(password), display_name = VALUES(display_name), is_active = 1
    ")->execute([$gUid, $guruEmail, $password_hash]);

    $stmtG->execute([$guruEmail]);
    $gUidActual = $stmtG->fetchColumn() ?: $gUid;

    try {
        $pdo->prepare("DELETE FROM user_roles WHERE user_id = ?")->execute([$gUidActual]);
        $pdo->prepare("INSERT INTO user_roles (id, user_id, tenant_id, role, reference_id) VALUES (?, ?, ?, 'teacher', ?)")
            ->execute(['rol-guru-001', $gUidActual, $tenant_id, $gUidActual]);
    } catch (\Throwable $ge) {}

    // -------------------------------------------------------------
    // 3. NILAI RAPOR MULTI-SEMESTER & DETAILS
    // -------------------------------------------------------------
    $semesters = [
        ['year' => '2025/2026', 'sem' => 'ganjil', 'status' => 'verified'],
        ['year' => '2025/2026', 'sem' => 'genap', 'status' => 'verified'],
        ['year' => '2026/2027', 'sem' => 'ganjil', 'status' => 'verified']
    ];

    $subjectsSMA = [
        ['name' => 'Matematika Wajib', 'kkm' => 75],
        ['name' => 'Bahasa Indonesia', 'kkm' => 75],
        ['name' => 'Bahasa Inggris', 'kkm' => 75],
        ['name' => 'Pendidikan Pancasila', 'kkm' => 75],
        ['name' => 'Sejarah', 'kkm' => 75],
        ['name' => 'Fisika', 'kkm' => 75],
        ['name' => 'Kimia', 'kkm' => 75],
        ['name' => 'Biologi', 'kkm' => 75]
    ];

    $subjectsSMP = [
        ['name' => 'Matematika', 'kkm' => 72],
        ['name' => 'Bahasa Indonesia', 'kkm' => 75],
        ['name' => 'Bahasa Inggris', 'kkm' => 72],
        ['name' => 'IPA Terpadu', 'kkm' => 72],
        ['name' => 'IPS Terpadu', 'kkm' => 75],
        ['name' => 'Pendidikan Pancasila', 'kkm' => 75],
        ['name' => 'Informatika', 'kkm' => 75]
    ];

    $subjectsSD = [
        ['name' => 'Matematika', 'kkm' => 70],
        ['name' => 'Bahasa Indonesia', 'kkm' => 75],
        ['name' => 'Ilmu Pengetahuan Alam dan Sosial (IPAS)', 'kkm' => 72],
        ['name' => 'Pendidikan Pancasila', 'kkm' => 75],
        ['name' => 'Bahasa Inggris', 'kkm' => 70],
        ['name' => 'Seni Budaya & Prakarya', 'kkm' => 75]
    ];

    $inRc = $pdo->prepare("
        INSERT INTO report_cards (id, tenant_id, school_id, student_id, education_level, academic_year, semester, class_level, class_name, curriculum, status, verified_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'Kurikulum Merdeka', ?, NOW())
        ON DUPLICATE KEY UPDATE status = VALUES(status), updated_at = NOW()
    ");

    $delRcd = $pdo->prepare("DELETE FROM report_card_details WHERE report_card_id = ?");
    $inRcd = $pdo->prepare("
        INSERT INTO report_card_details (id, report_card_id, subject_name, score, letter_grade, kkm, competency_achievement)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");

    $totalRc = 0;
    foreach ($students as $st) {
        $level = $st['target_level'];
        $subjects = ($level === 'SD') ? $subjectsSD : (($level === 'SMP') ? $subjectsSMP : $subjectsSMA);

        foreach ($semesters as $sIdx => $sem) {
            $rcId = 'rc-' . $st['id'] . '-s' . ($sIdx + 1);
            $inRc->execute([
                $rcId, $tenant_id, $st['school_id'], $st['id'],
                $level, $sem['year'], $sem['sem'], $st['grade_level'],
                $st['class_name'], $sem['status']
            ]);

            $delRcd->execute([$rcId]);

            foreach ($subjects as $sub) {
                // Determine score according to student category
                if ($st['gpa_category'] === 'high') {
                    $score = rand(88, 97);
                } elseif ($st['gpa_category'] === 'med') {
                    $score = rand(76, 88);
                } else { // gap category (e.g. weak in Math/Science)
                    if (str_contains(strtolower($sub['name']), 'matematika') || str_contains(strtolower($sub['name']), 'fisika')) {
                        $score = rand(65, 72); // Below KKM!
                    } else {
                        $score = rand(78, 86);
                    }
                }

                $letter = ($score >= 90) ? 'A' : (($score >= 80) ? 'B' : (($score >= 70) ? 'C' : 'D'));
                $comp = "Menunjukkan penguasaan " . ($score >= 85 ? "sangat baik" : "cukup") . " pada capaian pembelajaran " . $sub['name'];

                $inRcd->execute([
                    'rcd-' . bin2hex(random_bytes(8)),
                    $rcId, $sub['name'], $score, $letter, $sub['kkm'], $comp
                ]);
            }
            $totalRc++;
        }
    }
    $summary['report_cards_seeded'] = $totalRc;

    // -------------------------------------------------------------
    // 4. CBT QUIZ RESULTS & TRYOUT UTBK
    // -------------------------------------------------------------
    $delQuiz = $pdo->prepare("DELETE FROM quiz_results WHERE student_id = ?");
    $inQuiz = $pdo->prepare("
        INSERT INTO quiz_results (id, student_id, quiz_type, subtes, score, correct, total, duration_sec, created_at)
        VALUES (?, ?, 'tryout', ?, ?, ?, 30, ?, NOW())
    ");

    $subtestsSNBT = ['Penalaran Umum', 'Pengetahuan Kuantitatif', 'Pemahaman Bacaan & Menulis', 'Literasi Bahasa Indonesia', 'Literasi Bahasa Inggris', 'Penalaran Matematika'];

    foreach ($students as $st) {
        $delQuiz->execute([$st['id']]);
        if ($st['target_level'] === 'SMA') {
            foreach ($subtestsSNBT as $subtes) {
                $base = ($st['gpa_category'] === 'high') ? rand(650, 750) : (($st['gpa_category'] === 'med') ? rand(560, 640) : rand(480, 560));
                $correct = round($base / 25);
                $inQuiz->execute([
                    'qr-' . bin2hex(random_bytes(8)),
                    $st['id'], $subtes, $base, $correct, rand(1200, 1800)
                ]);
            }
        } else {
            // TKA SMP / SD
            $inQuiz->execute([
                'qr-' . bin2hex(random_bytes(8)),
                $st['id'], 'Tes Kemampuan Akademik (TKA)', rand(75, 95), rand(22, 28), 1500
            ]);
        }
    }
    $summary['cbt_results_seeded'] = count($students);

    // -------------------------------------------------------------
    // 5. DIAGNOSTIC RESULTS & LEARNING PATH
    // -------------------------------------------------------------
    $inDiag = $pdo->prepare("
        INSERT INTO diagnostic_results (id, student_id, answers, raw_score, recommended_track, created_at)
        VALUES (?, ?, '{}', ?, ?, NOW())
        ON DUPLICATE KEY UPDATE recommended_track = VALUES(recommended_track)
    ");

    foreach ($students as $st) {
        $track = ($st['gpa_category'] === 'high') ? 'Investigatif & Realistis (Sains & Teknologi)' : 'Enterprising & Sosial (Bisnis & Humaniora)';
        $inDiag->execute([
            'diag-' . $st['id'], $st['id'], rand(75, 95), $track
        ]);
    }

    // -------------------------------------------------------------
    // 6. CATATAN BIMBINGAN KONSELING (GUIDANCE RECORDS)
    // -------------------------------------------------------------
    $pdo->exec("DELETE FROM guidance_records WHERE id LIKE 'gr-seed-%'");
    $inGr = $pdo->prepare("
        INSERT INTO guidance_records (id, tenant_id, school_id, student_id, counselor_name, category, priority, issue_summary, action_plan, status, follow_up_date)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, DATE_ADD(CURDATE(), INTERVAL 7 DAY))
    ");

    $guidanceData = [
        [
            'id' => 'gr-seed-001',
            'school_id' => 'sch-sma-001',
            'student_id' => 'std-sma-005', // Fajar Hidayat (gap)
            'counselor' => 'Dra. Ratna Kusuma, M.Pd',
            'category' => 'akademik',
            'priority' => 'tinggi',
            'issue' => 'Nilai Matematika dan Fisika semester 1-3 berada di bawah KKM (68). Kesulitan dalam topik aljabar fungsi dan mekanika gerak.',
            'plan' => 'Didaftarkan ke kelas pendampingan intensif & bedah soal mandiri EduPath. Evaluasi mingguan dengan Guru Pengampu.',
            'status' => 'in_progress'
        ],
        [
            'id' => 'gr-seed-002',
            'school_id' => 'sch-sma-001',
            'student_id' => 'std-sma-001', // Rizky (Kedokteran UI)
            'counselor' => 'Dra. Ratna Kusuma, M.Pd',
            'category' => 'karir_studi',
            'priority' => 'sedang',
            'issue' => 'Konsultasi pemetaan peluang SNBP vs SNBT untuk FK UI. Membutuhkan strategi cadangan di pilihan ke-2 (FK Unpad / FK Undip).',
            'plan' => 'Analisis konsistensi nilai Biologi & Kimia semester 1-5. Latihan simulasi CBT SNBT terjadwal tiap pekan.',
            'status' => 'monitoring'
        ],
        [
            'id' => 'gr-seed-003',
            'school_id' => 'sch-smp-001',
            'student_id' => 'std-smp-003', // Kevin Julio
            'counselor' => 'Budi Santoso, S.Pd',
            'category' => 'karir_studi',
            'priority' => 'sedang',
            'issue' => 'Minat dominan pada bidang teknis praktis & coding. Membutuhkan arahan antara memilih SMA atau SMK Unggulan.',
            'plan' => 'Konseling penelusuran bakat dengan orang tua & observasi program keahlian Rekayasa Perangkat Lunak SMKN 1 Bandung.',
            'status' => 'resolved'
        ]
    ];

    foreach ($guidanceData as $g) {
        $inGr->execute([
            $g['id'], $tenant_id, $g['school_id'], $g['student_id'],
            $g['counselor'], $g['category'], $g['priority'],
            $g['issue'], $g['plan'], $g['status']
        ]);
    }
    $summary['guidance_records_seeded'] = count($guidanceData);

    // -------------------------------------------------------------
    // 7. PELACAKAN PILIHAN KARIR & PTN (ADMISSION TRACKING)
    // -------------------------------------------------------------
    $pdo->exec("DELETE FROM admission_trackings WHERE id LIKE 'at-seed-%'");
    $inAt = $pdo->prepare("
        INSERT INTO admission_trackings (id, tenant_id, school_id, student_id, target_university, target_faculty, target_major, selection_path, priority_order, status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");

    $admissionTrackings = [
        ['id' => 'at-seed-001', 'school_id' => 'sch-sma-001', 'student_id' => 'std-sma-001', 'univ' => 'Universitas Indonesia', 'faculty' => 'Fakultas Kedokteran', 'major' => 'Pendidikan Dokter', 'path' => 'SNBP', 'prio' => 1, 'status' => 'rencana', 'notes' => 'Pilihan Utama Kuota Sekolah'],
        ['id' => 'at-seed-002', 'school_id' => 'sch-sma-001', 'student_id' => 'std-sma-001', 'univ' => 'Universitas Airlangga', 'faculty' => 'Fakultas Kedokteran', 'major' => 'Pendidikan Dokter', 'path' => 'SNBT', 'prio' => 2, 'status' => 'rencana', 'notes' => 'Target Cadangan UTBK'],
        ['id' => 'at-seed-003', 'school_id' => 'sch-sma-001', 'student_id' => 'std-sma-002', 'univ' => 'Institut Teknologi Bandung', 'faculty' => 'STEI', 'major' => 'Teknik Informatika', 'path' => 'SNBP', 'prio' => 1, 'status' => 'rencana', 'notes' => 'Rata-rata Matematika & Fisika 95+'],
        ['id' => 'at-seed-004', 'school_id' => 'sch-sma-001', 'student_id' => 'std-sma-003', 'univ' => 'Universitas Gadjah Mada', 'faculty' => 'FEB', 'major' => 'Manajemen', 'path' => 'SNBT', 'prio' => 1, 'status' => 'rencana', 'notes' => 'Fokus Tryout Kuantitatif & Literasi']
    ];

    foreach ($admissionTrackings as $at) {
        $inAt->execute([
            $at['id'], $tenant_id, $at['school_id'], $at['student_id'],
            $at['univ'], $at['faculty'], $at['major'], $at['path'],
            $at['prio'], $at['status'], $at['notes']
        ]);
    }
    $summary['admission_trackings_seeded'] = count($admissionTrackings);

    return $summary;
}

if (php_sapi_name() === 'cli' || defined('STDIN')) {
    echo "=== SEEDING EDUPATH SCHOOL INTELLIGENCE DUMMY DATA ===\n";
    try {
        $pdo = get_db_connection();
        $res = seed_school_intelligence_data($pdo);
        print_r($res);
        echo "SUCCESS! Data dummy berhasil disuntikkan.\n";
    } catch (\Throwable $e) {
        echo "ERROR: " . $e->getMessage() . "\n";
    }
}

