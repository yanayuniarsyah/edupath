<?php
// seed_school_intelligence.php
// Web & CLI runner for seeding EduPath School Intelligence Dummy Data (PRD v1.2)

header('Content-Type: text/html; charset=utf-8');
require_once __DIR__ . '/api/config.php';
require_once __DIR__ . '/api/database/seeds/seed_school_intelligence.php';

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>Seed EduPath School Intelligence</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif; background: #070b14; color: #fff; padding: 40px; }
        .card { max-width: 680px; margin: 0 auto; background: #0d1224; border: 1px solid #1e293b; border-radius: 16px; padding: 24px; box-shadow: 0 10px 40px rgba(0,0,0,0.5); }
        h1 { color: #c0ff00; font-size: 20px; margin-top: 0; }
        .success { color: #34d399; font-weight: bold; }
        .error { color: #f87171; font-weight: bold; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; font-size: 13px; }
        th, td { padding: 8px 12px; text-align: left; border-bottom: 1px solid #1e293b; }
        th { color: #94a3b8; }
        .btn { display: inline-block; background: #c0ff00; color: #000; font-weight: bold; text-decoration: none; padding: 10px 20px; border-radius: 10px; margin-top: 20px; }
    </style>
</head>
<body>
<div class="card">
    <h1>🌱 EduPath School Intelligence Data Seeder (v1.2)</h1>
    <?php
    try {
        $res = seed_school_intelligence_data($pdo);
        echo "<p class='success'>✅ Berhasil menyuntikkan data dummy ke database!</p>";
        echo "<table>";
        echo "<tr><th>Komponen Data</th><th>Jumlah Tersimpan</th></tr>";
        echo "<tr><td>Sekolah Mitra (SD, SMP, SMA & Ciamis)</td><td>" . ($res['schools_seeded'] ?? 0) . " Sekolah</td></tr>";
        echo "<tr><td>Data Siswa Terdistribusi (SD, SMP, SMA)</td><td>" . ($res['students_seeded'] ?? 0) . " Siswa</td></tr>";
        echo "<tr><td>Nilai Rapor Multi-Semester (Verified)</td><td>" . ($res['report_cards_seeded'] ?? 0) . " Rapor</td></tr>";
        echo "<tr><td>Hasil CBT Tryout UTBK & TKA</td><td>" . ($res['cbt_results_seeded'] ?? 0) . " Siswa</td></tr>";
        echo "<tr><td>Catatan Bimbingan Konseling (BK)</td><td>" . ($res['guidance_records_seeded'] ?? 0) . " Kasus/Intervensi</td></tr>";
        echo "<tr><td>Pelacakan Seleksi Masuk PTN (SNBP & SNBT)</td><td>" . ($res['admission_trackings_seeded'] ?? 0) . " Rekomendasi</td></tr>";
        echo "</table>";

        echo "<h3 style='color:#38bdf8; margin-top:24px;'>Akun Login Guru BK yang Tersedia:</h3>";
        echo "<ul>";
        echo "<li><b>SMA:</b> bk.sma@edupath.id / <code>password123</code> (SMAN 1 EduPath Unggulan)</li>";
        echo "<li><b>SMP:</b> bk.smp@edupath.id / <code>password123</code> (SMP EduPath Cendekia)</li>";
        echo "<li><b>SD:</b> bk.sd@edupath.id / <code>password123</code> (SD EduPath Prestasi Mandiri)</li>";
        echo "<li><b>Ciamis:</b> bk.smansaciamis@edupath.co.id / <code>EduPathSchool01!2026</code> (SMAN 1 Ciamis)</li>";
        echo "</ul>";

        echo "<a href='/#/school' class='btn'>Buka Portal Sekolah &rarr;</a>";
    } catch (\Throwable $e) {
        echo "<p class='error'>❌ Terjadi kendala: " . htmlspecialchars($e->getMessage()) . "</p>";
    }
    ?>
</div>
</body>
</html>

