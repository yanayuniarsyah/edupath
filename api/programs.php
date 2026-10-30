<?php
// api/programs.php
// Master Academic Programs Endpoint (PRD v1.1 - Phase 1)
// Scope: TKA SD, TKA SMP, TKA SMA, SNPMB/UTBK, SNPMB Alumni

require_once 'config.php';

header('Content-Type: application/json');
header('Cache-Control: public, max-age=600'); // Cache for 10 minutes

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(["success" => false, "error" => ["code" => "METHOD_NOT_ALLOWED", "message" => "Method not allowed"]]);
    exit;
}

try {
    $stmt = $pdo->prepare("
        SELECT id, code, name, description, target_level, subject_config, status
        FROM academic_programs
        WHERE status = 'active'
        ORDER BY CASE 
            WHEN code = 'TKA_SD' THEN 1
            WHEN code = 'TKA_SMP' THEN 2
            WHEN code = 'TKA_SMA' THEN 3
            WHEN code = 'SNPMB_UTBK' THEN 4
            WHEN code = 'SNPMB_ALUMNI' THEN 5
            ELSE 6
        END ASC
    ");
    $stmt->execute();
    $programs = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fallback if academic_programs table is not yet seeded
    if (empty($programs)) {
        $programs = [
            [
                'id' => 'prog-tka-sd',
                'code' => 'TKA_SD',
                'name' => 'TKA SD',
                'description' => 'Program Tes Kemampuan Akademik tingkat Sekolah Dasar (Literasi & Numerasi Dasar)',
                'target_level' => 'SD',
                'status' => 'active'
            ],
            [
                'id' => 'prog-tka-smp',
                'code' => 'TKA_SMP',
                'name' => 'TKA SMP',
                'description' => 'Program Tes Kemampuan Akademik tingkat SMP (Penalaran Matematika & Literasi)',
                'target_level' => 'SMP',
                'status' => 'active'
            ],
            [
                'id' => 'prog-tka-sma',
                'code' => 'TKA_SMA',
                'name' => 'TKA SMA',
                'description' => 'Program Tes Kemampuan Akademik tingkat SMA dengan konfigurasi mata pelajaran adaptif',
                'target_level' => 'SMA',
                'status' => 'active'
            ],
            [
                'id' => 'prog-snpmb-utbk',
                'code' => 'SNPMB_UTBK',
                'name' => 'SNPMB/UTBK',
                'description' => 'Persiapan Seleksi Nasional Berdasarkan Tes PTN (TPS, Literasi B. Indonesia & Inggris, Penalaran Matematika)',
                'target_level' => 'SMA',
                'status' => 'active'
            ],
            [
                'id' => 'prog-snpmb-alumni',
                'code' => 'SNPMB_ALUMNI',
                'name' => 'SNPMB Alumni',
                'description' => 'Program intensif alumni/gap year dengan kurikulum pendalaman materi & mentoring khusus',
                'target_level' => 'ALUMNI',
                'status' => 'active'
            ]
        ];
    } else {
        foreach ($programs as &$prog) {
            if (!empty($prog['subject_config']) && is_string($prog['subject_config'])) {
                $prog['subject_config'] = json_decode($prog['subject_config'], true);
            }
        }
    }

    echo json_encode([
        "success" => true,
        "data" => $programs
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        "success" => false,
        "error" => [
            "code" => "DATABASE_ERROR",
            "message" => "Gagal mengambil data master program: " . $e->getMessage()
        ]
    ]);
}
