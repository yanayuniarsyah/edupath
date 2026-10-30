<?php
// api/database/seed_multi_program_catalog.php
// PRD v1.1 Unified Product Architecture Catalog Seeder
// Seeds products & plans for TKA SD, TKA SMP, TKA SMA, SNPMB/UTBK, and SNPMB Alumni across Online & Hybrid

require_once __DIR__ . '/../config.php';

function seed_multi_program_catalog(PDO $pdo): array {
    $results = [];

    // 1. Ensure master academic programs exist
    $programs = [
        ['prog-tka-sd', 'TKA_SD', 'TKA SD', 'Program Tes Kemampuan Akademik tingkat SD (Literasi & Numerasi Dasar)', 'SD'],
        ['prog-tka-smp', 'TKA_SMP', 'TKA SMP', 'Program Tes Kemampuan Akademik tingkat SMP (Penalaran Matematika & Literasi)', 'SMP'],
        ['prog-tka-sma', 'TKA_SMA', 'TKA SMA', 'Program Tes Kemampuan Akademik tingkat SMA (Saintek & Soshum Adaptif)', 'SMA'],
        ['prog-snpmb-utbk', 'SNPMB_UTBK', 'SNPMB/UTBK', 'Persiapan Seleksi Nasional Berdasarkan Tes PTN (TPS & Literasi)', 'SMA'],
        ['prog-snpmb-alumni', 'SNPMB_ALUMNI', 'SNPMB Alumni', 'Program intensif alumni/gap year dengan pendalaman materi & mentoring khusus', 'ALUMNI']
    ];

    $stmtProg = $pdo->prepare("
        INSERT INTO academic_programs (id, code, name, description, target_level, status)
        VALUES (?, ?, ?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE name=VALUES(name), description=VALUES(description), target_level=VALUES(target_level)
    ");
    foreach ($programs as $p) {
        $stmtProg->execute($p);
    }
    $results[] = "Academic programs verified (" . count($programs) . " programs).";

    // 2. Multi-program Products
    $products = [
        [
            'id' => 'prod-mandiri',
            'code' => 'MANDIRI',
            'name' => 'Paket Mandiri SNPMB',
            'description' => 'Akses belajar mandiri adaptif dengan IRT assessment, 50.000+ bank soal, dan simulasi SNBT terukur.'
        ],
        [
            'id' => 'prod-pejuang',
            'code' => 'UTBK',
            'name' => 'Paket Pejuang PTN',
            'description' => 'AI Tutor Companion 24/7, simulasi IRT adaptif tanpa batas, dan rasionalisasi prodi real-time.'
        ],
        [
            'id' => 'prod-elite',
            'code' => 'VIP',
            'name' => 'Paket Elite Mentoring',
            'description' => '1-on-1 Zoom mentoring mingguan dengan Senior Mentor alumni UI/ITB + audit portofolio belajar.'
        ],
        [
            'id' => 'prod-snpmb-hybrid',
            'code' => 'SNBT_HYBRID',
            'name' => 'Paket SNPMB Hybrid Intensif',
            'description' => 'Kombinasi bimbingan tatap muka offline di cabang mitra + platform online adaptif & konsultasi prodi.'
        ],
        [
            'id' => 'prod-tka-sma-online',
            'code' => 'TKA_SMA_ONLINE',
            'name' => 'Paket TKA SMA Mandiri',
            'description' => 'Penguasaan materi Tes Kemampuan Akademik SMA Saintek/Soshum dengan bank soal HOTS & analisis kelemahan.'
        ],
        [
            'id' => 'prod-tka-sma-hybrid',
            'code' => 'TKA_SMA_HYBRID',
            'name' => 'Paket TKA SMA Terpadu',
            'description' => 'Kelas tatap muka interaktif di cabang plus akses modul digital TKA SMA & drill soal terarah.'
        ],
        [
            'id' => 'prod-tka-smp-online',
            'code' => 'TKA_SMP_ONLINE',
            'name' => 'Paket TKA SMP Mandiri',
            'description' => 'Pendalaman fondasi logika matematika, literasi bahasa, dan IPA/IPS SMP untuk persiapan jenjang SMA favorit.'
        ],
        [
            'id' => 'prod-tka-smp-hybrid',
            'code' => 'TKA_SMP_HYBRID',
            'name' => 'Paket TKA SMP Terpadu',
            'description' => 'Bimbingan belajar offline di kelas kecil bersama tutor ahli + latihan soal adaptif digital berkala.'
        ],
        [
            'id' => 'prod-tka-sd-online',
            'code' => 'TKA_SD_ONLINE',
            'name' => 'Paket TKA SD Mandiri',
            'description' => 'Penguatan literasi membaca, numerasi ceria, dan latihan soal bertahap untuk siswa SD tingkat akhir.'
        ],
        [
            'id' => 'prod-tka-sd-hybrid',
            'code' => 'TKA_SD_HYBRID',
            'name' => 'Paket TKA SD Terpadu',
            'description' => 'Pendampingan belajar tatap muka yang menyenangkan dan interaktif di sentra bimbel mitra.'
        ],
        [
            'id' => 'prod-alumni-intensif',
            'code' => 'ALUMNI_ONLINE',
            'name' => 'Paket Alumni Gap Year Intensif',
            'description' => 'Kurikulum khusus alumni yang fokus pada perbaikan strategi, pendalaman subtes tersulit, & habit belajar harian.'
        ],
        [
            'id' => 'prod-alumni-karantina',
            'code' => 'ALUMNI_HYBRID',
            'name' => 'Paket Alumni Supercamp Karantina',
            'description' => 'Program karantina intensif hybrid tatap muka harian, bedah soal 12 jam/hari, & mentoring mental juara PTN.'
        ]
    ];

    $stmtProd = $pdo->prepare("
        INSERT INTO products (id, tenant_id, code, name, description, status)
        VALUES (?, 'default_tenant', ?, ?, ?, 'active')
        ON DUPLICATE KEY UPDATE code=VALUES(code), name=VALUES(name), description=VALUES(description), status='active'
    ");
    foreach ($products as $pr) {
        $stmtProd->execute([$pr['id'], $pr['code'], $pr['name'], $pr['description']]);
    }
    $results[] = "Products catalog seeded (" . count($products) . " products).";

    // 3. Multi-program Plans
    $defaultPlans = [
        // SNPMB/UTBK Online
        [
            'id' => 'plan-p1-monthly',
            'product_id' => 'prod-mandiri',
            'program_id' => 'prog-snpmb-utbk',
            'academic_program' => 'SNPMB/UTBK',
            'delivery_mode' => 'online',
            'name' => 'Paket Mandiri (Bulanan)',
            'price' => 180000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                '500+ Micro-Lessons Adaptif (TPS & Literasi)',
                '50.000+ Bank Soal HOTS dengan Standar Skor IRT',
                '5x Tryout Nasional / Bulan + Pembahasan Lengkap',
                'Radar Deteksi Blind-Spot Belajar Instan',
                'Habit Tracker: Weekly Learning Check-in (WLC)'
            ])
        ],
        [
            'id' => 'plan-p1-yearly',
            'product_id' => 'prod-mandiri',
            'program_id' => 'prog-snpmb-utbk',
            'academic_program' => 'SNPMB/UTBK',
            'delivery_mode' => 'online',
            'name' => 'Paket Mandiri (Tahunan)',
            'price' => 1296000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                '500+ Micro-Lessons Adaptif (TPS & Literasi)',
                '50.000+ Bank Soal HOTS dengan Standar Skor IRT',
                '5x Tryout Nasional / Bulan + Pembahasan Lengkap',
                'Radar Deteksi Blind-Spot Belajar Instan',
                'Hemat 40% Akses Penuh Selama 1 Tahun Penuh'
            ])
        ],
        [
            'id' => 'plan-p2-monthly',
            'product_id' => 'prod-pejuang',
            'program_id' => 'prog-snpmb-utbk',
            'academic_program' => 'SNPMB/UTBK',
            'delivery_mode' => 'online',
            'name' => 'Paket Pejuang PTN (Bulanan)',
            'price' => 480000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Semua Fitur di Paket Mandiri',
                'AI Tutor Companion 24/7 (Bimbingan logika tanpa batas)',
                'Unlimited Simulasi IRT Adaptif (Bebas TO tanpa kuota)',
                'Estimasi Kesiapan & Rasionalisasi Prodi Edukatif',
                'Laporan Progres Belajar Otomatis via WhatsApp Orang Tua'
            ])
        ],
        [
            'id' => 'plan-p2-yearly',
            'product_id' => 'prod-pejuang',
            'program_id' => 'prog-snpmb-utbk',
            'academic_program' => 'SNPMB/UTBK',
            'delivery_mode' => 'online',
            'name' => 'Paket Pejuang PTN (Tahunan)',
            'price' => 2304000,
            'discount' => 60,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Semua Fitur di Paket Mandiri',
                'AI Tutor Companion 24/7 (Bimbingan logika tanpa batas)',
                'Unlimited Simulasi IRT Adaptif (Bebas TO tanpa kuota)',
                'Estimasi Kesiapan & Rasionalisasi Prodi Edukatif',
                'Paling Populer: Hemat 60% Bayar Tahunan'
            ])
        ],
        [
            'id' => 'plan-p3-monthly',
            'product_id' => 'prod-elite',
            'program_id' => 'prog-snpmb-utbk',
            'academic_program' => 'SNPMB/UTBK',
            'delivery_mode' => 'online',
            'name' => 'Paket Elite Mentoring (Bulanan)',
            'price' => 980000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Semua Fitur Lengkap di Paket Pejuang PTN',
                '1-on-1 Zoom Mentoring Mingguan via Zoom (60 Menit)',
                'Grup WhatsApp VIP Langsung bareng Senior Mentor UI/ITB',
                'Audit Portofolio Belajar & Siasat Strategi Pilihan Prodi'
            ])
        ],
        [
            'id' => 'plan-p3-yearly',
            'product_id' => 'prod-elite',
            'program_id' => 'prog-snpmb-utbk',
            'academic_program' => 'SNPMB/UTBK',
            'delivery_mode' => 'online',
            'name' => 'Paket Elite Mentoring (Tahunan)',
            'price' => 8232000,
            'discount' => 30,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Semua Fitur Lengkap di Paket Pejuang PTN',
                '1-on-1 Zoom Mentoring Mingguan via Zoom (60 Menit)',
                'Grup WhatsApp VIP Langsung bareng Senior Mentor UI/ITB',
                'Audit Portofolio Belajar & Siasat Strategi Pilihan Prodi',
                'Garansi Score Improvement atau Refund'
            ])
        ],

        // SNPMB Hybrid
        [
            'id' => 'plan-snbt-hybrid-monthly',
            'product_id' => 'prod-snpmb-hybrid',
            'program_id' => 'prog-snpmb-utbk',
            'academic_program' => 'SNPMB/UTBK',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket SNBT Hybrid Intensif (Bulanan)',
            'price' => 850000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Kelas Tatap Muka Offline 2x Seminggu di Sentra Bimbel',
                'Akses Penuh Platform Online CBT Adaptif & Bank Soal',
                'Klinik Konsultasi Soal Sulit & PR Sekolah',
                'Simulasi Tryout Offline & Online Terjadwal'
            ])
        ],
        [
            'id' => 'plan-snbt-hybrid-yearly',
            'product_id' => 'prod-snpmb-hybrid',
            'program_id' => 'prog-snpmb-utbk',
            'academic_program' => 'SNPMB/UTBK',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket SNBT Hybrid Intensif (Tahunan)',
            'price' => 6120000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Kelas Tatap Muka Offline 2x Seminggu di Sentra Bimbel',
                'Akses Penuh Platform Online CBT Adaptif & Bank Soal',
                'Klinik Konsultasi Soal Sulit & PR Sekolah',
                'Simulasi Tryout Offline & Online Terjadwal',
                'Hemat 40% Paket Tahunan Lengkap'
            ])
        ],

        // TKA SMA Online & Hybrid
        [
            'id' => 'plan-tka-sma-online-monthly',
            'product_id' => 'prod-tka-sma-online',
            'program_id' => 'prog-tka-sma',
            'academic_program' => 'TKA SMA',
            'delivery_mode' => 'online',
            'name' => 'Paket TKA SMA Mandiri (Bulanan)',
            'price' => 180000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Materi Lengkap TKA SMA Saintek & Soshum',
                'Bank Soal HOTS Kurikulum Nasional & Kurikulum Merdeka',
                'CBT Tryout TKA Adaptif dengan Analisis Skor',
                'Pembahasan Soal Langkah demi Langkah'
            ])
        ],
        [
            'id' => 'plan-tka-sma-online-yearly',
            'product_id' => 'prod-tka-sma-online',
            'program_id' => 'prog-tka-sma',
            'academic_program' => 'TKA SMA',
            'delivery_mode' => 'online',
            'name' => 'Paket TKA SMA Mandiri (Tahunan)',
            'price' => 1296000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Materi Lengkap TKA SMA Saintek & Soshum',
                'Bank Soal HOTS Kurikulum Nasional & Kurikulum Merdeka',
                'CBT Tryout TKA Adaptif dengan Analisis Skor',
                'Hemat 40% Akses Belajar Sepanjang Tahun'
            ])
        ],
        [
            'id' => 'plan-tka-sma-hybrid-monthly',
            'product_id' => 'prod-tka-sma-hybrid',
            'program_id' => 'prog-tka-sma',
            'academic_program' => 'TKA SMA',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket TKA SMA Terpadu (Bulanan)',
            'price' => 650000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Pertemuan Tatap Muka Offline 2x Seminggu di Bimbel',
                'Bedah Materi Eksakta (Matematika, Fisika, Kimia, Biologi) / Sosial',
                'Akses Aplikasi CBT TKA Online 24 Jam',
                'Buku Modul Cetak & Lembar Kerja Siswa'
            ])
        ],
        [
            'id' => 'plan-tka-sma-hybrid-yearly',
            'product_id' => 'prod-tka-sma-hybrid',
            'program_id' => 'prog-tka-sma',
            'academic_program' => 'TKA SMA',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket TKA SMA Terpadu (Tahunan)',
            'price' => 4680000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Pertemuan Tatap Muka Offline 2x Seminggu di Bimbel',
                'Bedah Materi Eksakta (Matematika, Fisika, Kimia, Biologi) / Sosial',
                'Akses Aplikasi CBT TKA Online 24 Jam',
                'Buku Modul Cetak & Lembar Kerja Siswa',
                'Hemat 40% Program 1 Tahun Ajaran'
            ])
        ],

        // TKA SMP Online & Hybrid
        [
            'id' => 'plan-tka-smp-online-monthly',
            'product_id' => 'prod-tka-smp-online',
            'program_id' => 'prog-tka-smp',
            'academic_program' => 'TKA SMP',
            'delivery_mode' => 'online',
            'name' => 'Paket TKA SMP Mandiri (Bulanan)',
            'price' => 150000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Materi Penalaran Matematika, Bahasa & IPA/IPS SMP',
                'Latihan Soal Interaktif Berbasis Konsep',
                'CBT Tryout Simulasi Asesmen Akademik SMP',
                'Laporan Progres Belajar Mingguan'
            ])
        ],
        [
            'id' => 'plan-tka-smp-online-yearly',
            'product_id' => 'prod-tka-smp-online',
            'program_id' => 'prog-tka-smp',
            'academic_program' => 'TKA SMP',
            'delivery_mode' => 'online',
            'name' => 'Paket TKA SMP Mandiri (Tahunan)',
            'price' => 1080000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Materi Penalaran Matematika, Bahasa & IPA/IPS SMP',
                'Latihan Soal Interaktif Berbasis Konsep',
                'CBT Tryout Simulasi Asesmen Akademik SMP',
                'Hemat 40% Akses Penuh Selama 1 Tahun'
            ])
        ],
        [
            'id' => 'plan-tka-smp-hybrid-monthly',
            'product_id' => 'prod-tka-smp-hybrid',
            'program_id' => 'prog-tka-smp',
            'academic_program' => 'TKA SMP',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket TKA SMP Terpadu (Bulanan)',
            'price' => 450000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Kelas Tatap Muka 2x Seminggu di Ruang Kelas Nyaman',
                'Bimbingan PR Sekolah & Persiapan Ujian Sekolah',
                'Akses Penuh Aplikasi Belajar & CBT Online',
                'Pendampingan Tutor Sabar & Berpengalaman'
            ])
        ],
        [
            'id' => 'plan-tka-smp-hybrid-yearly',
            'product_id' => 'prod-tka-smp-hybrid',
            'program_id' => 'prog-tka-smp',
            'academic_program' => 'TKA SMP',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket TKA SMP Terpadu (Tahunan)',
            'price' => 3240000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Kelas Tatap Muka 2x Seminggu di Ruang Kelas Nyaman',
                'Bimbingan PR Sekolah & Persiapan Ujian Sekolah',
                'Akses Penuh Aplikasi Belajar & CBT Online',
                'Hemat 40% Paket 1 Tahun Ajaran SMP'
            ])
        ],

        // TKA SD Online & Hybrid
        [
            'id' => 'plan-tka-sd-online-monthly',
            'product_id' => 'prod-tka-sd-online',
            'program_id' => 'prog-tka-sd',
            'academic_program' => 'TKA SD',
            'delivery_mode' => 'online',
            'name' => 'Paket TKA SD Mandiri (Bulanan)',
            'price' => 120000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Numerasi Dasar & Literasi Membaca Ceria',
                'Animasi Konsep Matematika & Sains Mudah Dipahami',
                'Quiz Interaktif dengan Poin & Gamifikasi',
                'Persiapan Masuk SMP Unggulan'
            ])
        ],
        [
            'id' => 'plan-tka-sd-online-yearly',
            'product_id' => 'prod-tka-sd-online',
            'program_id' => 'prog-tka-sd',
            'academic_program' => 'TKA SD',
            'delivery_mode' => 'online',
            'name' => 'Paket TKA SD Mandiri (Tahunan)',
            'price' => 864000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Numerasi Dasar & Literasi Membaca Ceria',
                'Animasi Konsep Matematika & Sains Mudah Dipahami',
                'Quiz Interaktif dengan Poin & Gamifikasi',
                'Hemat 40% Akses Belajar 1 Tahun Penuh'
            ])
        ],
        [
            'id' => 'plan-tka-sd-hybrid-monthly',
            'product_id' => 'prod-tka-sd-hybrid',
            'program_id' => 'prog-tka-sd',
            'academic_program' => 'TKA SD',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket TKA SD Terpadu (Bulanan)',
            'price' => 350000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Kelas Tatap Muka Hangat & Interaktif di Cabang',
                'Bimbingan Membaca, Berhitung Cepat, & PR Sekolah',
                'Akses EduPath Kids CBT & Materi Interaktif',
                'Laporan Perkembangan Karakter & Nilai Belajar'
            ])
        ],
        [
            'id' => 'plan-tka-sd-hybrid-yearly',
            'product_id' => 'prod-tka-sd-hybrid',
            'program_id' => 'prog-tka-sd',
            'academic_program' => 'TKA SD',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket TKA SD Terpadu (Tahunan)',
            'price' => 2520000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Kelas Tatap Muka Hangat & Interaktif di Cabang',
                'Bimbingan Membaca, Berhitung Cepat, & PR Sekolah',
                'Akses EduPath Kids CBT & Materi Interaktif',
                'Hemat 40% Pilihan Terbaik untuk Siswa SD'
            ])
        ],

        // SNPMB Alumni Online & Hybrid
        [
            'id' => 'plan-alumni-intensif-monthly',
            'product_id' => 'prod-alumni-intensif',
            'program_id' => 'prog-snpmb-alumni',
            'academic_program' => 'SNPMB Alumni',
            'delivery_mode' => 'online',
            'name' => 'Paket Alumni Gap Year (Bulanan)',
            'price' => 750000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Kurikulum Khusus Gap Year: Bedah Subtes dari Dasar',
                'Analisis Kesalahan UTBK Tahun Sebelumnya',
                'Daily Study Checklist & Habit Tracker Intensif',
                'Tryout Mingguan dengan Perankingan Nasional Alumni'
            ])
        ],
        [
            'id' => 'plan-alumni-intensif-yearly',
            'product_id' => 'prod-alumni-intensif',
            'program_id' => 'prog-snpmb-alumni',
            'academic_program' => 'SNPMB Alumni',
            'delivery_mode' => 'online',
            'name' => 'Paket Alumni Gap Year (Tahunan)',
            'price' => 5400000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Kurikulum Khusus Gap Year: Bedah Subtes dari Dasar',
                'Analisis Kesalahan UTBK Tahun Sebelumnya',
                'Daily Study Checklist & Habit Tracker Intensif',
                'Hemat 40% Pendampingan sampai Hari H Ujian PTN'
            ])
        ],
        [
            'id' => 'plan-alumni-karantina-monthly',
            'product_id' => 'prod-alumni-karantina',
            'program_id' => 'prog-snpmb-alumni',
            'academic_program' => 'SNPMB Alumni',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket Alumni Karantina (Bulanan)',
            'price' => 2500000,
            'discount' => 0,
            'duration' => 30,
            'billing_cycle' => 'monthly',
            'features' => json_encode([
                'Supercamp Tatap Muka Harian Full Day di Sentra EduPath',
                'Bedah Soal dan Diskusi 10 Jam per Hari bersama Master Tutor',
                'Akomodasi & Konsumsi Belajar Terpadu (Opsional Cabang)',
                'Simulasi CBT Real-time Setiap Hari Sabtu'
            ])
        ],
        [
            'id' => 'plan-alumni-karantina-yearly',
            'product_id' => 'prod-alumni-karantina',
            'program_id' => 'prog-snpmb-alumni',
            'academic_program' => 'SNPMB Alumni',
            'delivery_mode' => 'hybrid',
            'name' => 'Paket Alumni Karantina (Tahunan)',
            'price' => 18000000,
            'discount' => 40,
            'duration' => 365,
            'billing_cycle' => 'yearly',
            'features' => json_encode([
                'Supercamp Tatap Muka Harian Full Day di Sentra EduPath',
                'Bedah Soal dan Diskusi 10 Jam per Hari bersama Master Tutor',
                'Akomodasi & Konsumsi Belajar Terpadu (Opsional Cabang)',
                'Hemat 40% Garansi Pendampingan Intensif sampai Lolos PTN'
            ])
        ]
    ];

    try {
        $cols = $pdo->query("DESCRIBE plans")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('program_id', $cols)) {
            $pdo->exec("ALTER TABLE plans ADD COLUMN program_id VARCHAR(36) NULL AFTER tenant_id");
        }
    } catch (\Throwable $e) {}

    try {
        $pdo->exec("ALTER TABLE questions MODIFY COLUMN correct VARCHAR(255) NOT NULL DEFAULT ''");
    } catch (\Throwable $e) {}

    try {
        $qcols = $pdo->query("DESCRIBE questions")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('track', $qcols)) {
            $pdo->exec("ALTER TABLE questions ADD COLUMN track VARCHAR(50) NULL DEFAULT 'umum' AFTER assessment_type");
        }
    } catch (\Throwable $e) {}

    try {
        $mcols = $pdo->query("DESCRIBE materials")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('track', $mcols)) {
            $pdo->exec("ALTER TABLE materials ADD COLUMN track VARCHAR(50) NULL DEFAULT 'umum' AFTER assessment_type");
        }
    } catch (\Throwable $e) {}

    $stmtPlan = $pdo->prepare("
        INSERT INTO plans (
            id, tenant_id, product_id, program_id, academic_program, delivery_mode,
            name, price, base_price, discount, duration,
            features, billing_cycle, is_active, is_archived
        ) VALUES (
            ?, 'default_tenant', ?, ?, ?, ?,
            ?, ?, ?, ?, ?,
            ?, ?, 1, 0
        )
        ON DUPLICATE KEY UPDATE
            product_id=VALUES(product_id),
            program_id=VALUES(program_id),
            academic_program=VALUES(academic_program),
            delivery_mode=VALUES(delivery_mode),
            name=VALUES(name),
            price=VALUES(price),
            base_price=VALUES(base_price),
            discount=VALUES(discount),
            duration=VALUES(duration),
            features=VALUES(features),
            billing_cycle=VALUES(billing_cycle),
            is_active=1,
            is_archived=0
    ");

    $entStmt = $pdo->prepare("INSERT IGNORE INTO plan_entitlements (id, plan_id, feature_key) VALUES (UUID(), ?, ?)");
    $VALID_ENTITLEMENTS = ['tryout_unlimited', 'ai_adaptive_path', 'premium_materials', 'feature_quiz'];

    foreach ($defaultPlans as $dp) {
        $stmtPlan->execute([
            $dp['id'],
            $dp['product_id'],
            $dp['program_id'],
            $dp['academic_program'],
            $dp['delivery_mode'],
            $dp['name'],
            $dp['price'],
            $dp['price'],
            $dp['discount'],
            $dp['duration'],
            $dp['features'],
            $dp['billing_cycle']
        ]);

        foreach ($VALID_ENTITLEMENTS as $ent) {
            $entStmt->execute([$dp['id'], $ent]);
        }
    }
    $results[] = "Multi-program plans seeded (" . count($defaultPlans) . " packages).";

    return $results;
}

if (php_sapi_name() === 'cli' || defined('STDIN')) {
    echo "Running multi-program catalog seeder...\n";
    $res = seed_multi_program_catalog($pdo);
    foreach ($res as $r) {
        echo " - " . $r . "\n";
    }
    echo "Done.\n";
}
