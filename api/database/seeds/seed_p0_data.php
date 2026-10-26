<?php
require_once "config.php";
require_once "jwt.php";

$mkuuid = function() {
    return sprintf( '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ),
        mt_rand( 0, 0xffff ),
        mt_rand( 0, 0x0fff ) | 0x4000,
        mt_rand( 0, 0x3fff ) | 0x8000,
        mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff ), mt_rand( 0, 0xffff )
    );
};

echo "Seeding P0 Diagnostic Data...\n";

try {
    $pdo->exec("TRUNCATE TABLE questions");
} catch (PDOException $e) {
    echo "Warning (Truncate): " . $e->getMessage() . "\n";
    // fallback to delete
    $pdo->exec("DELETE FROM questions");
}

$mock_questions = [
    // Penalaran Umum (PU)
    [
        "domain" => "Penalaran Umum", "sub_materi" => "Penalaran Deduktif",
        "question" => "Semua ilmuwan tekun. Sebagian ilmuwan suka membaca komik. Kesimpulan yang tepat adalah...",
        "options" => [
            "Sebagian orang yang tekun suka membaca komik",
            "Semua orang yang tekun adalah ilmuwan",
            "Tidak ada ilmuwan yang tidak suka membaca komik",
            "Semua pembaca komik adalah ilmuwan tekun",
            "Sebagian orang yang tekun bukan ilmuwan"
        ],
        "correct" => "A", "difficulty" => "medium"
    ],
    [
        "domain" => "Penalaran Umum", "sub_materi" => "Pola Bilangan",
        "question" => "Berapakah angka berikutnya dari deret: 2, 3, 6, 15, 42, ...?",
        "options" => ["123", "84", "108", "135", "96"],
        "correct" => "A", "difficulty" => "hard"
    ],
    [
        "domain" => "Penalaran Umum", "sub_materi" => "Penalaran Analitik",
        "question" => "Andi lebih tinggi dari Budi. Cici lebih tinggi dari Andi. Dedi lebih pendek dari Budi. Siapa yang paling tinggi?",
        "options" => ["Andi", "Budi", "Cici", "Dedi", "Tidak dapat ditentukan"],
        "correct" => "C", "difficulty" => "easy"
    ],
    // Pengetahuan Kuantitatif (PK)
    [
        "domain" => "Pengetahuan Kuantitatif", "sub_materi" => "Aljabar Dasar",
        "question" => "Jika x + y = 10 dan x - y = 4, berapakah nilai x * y?",
        "options" => ["21", "24", "16", "20", "25"],
        "correct" => "A", "difficulty" => "medium"
    ],
    [
        "domain" => "Pengetahuan Kuantitatif", "sub_materi" => "Statistika",
        "question" => "Rata-rata dari 4 bilangan adalah 15. Jika bilangan kelima ditambahkan, rata-rata menjadi 16. Berapakah bilangan kelima?",
        "options" => ["20", "16", "18", "24", "22"],
        "correct" => "A", "difficulty" => "hard"
    ],
    [
        "domain" => "Pengetahuan Kuantitatif", "sub_materi" => "Geometri",
        "question" => "Sebuah segitiga siku-siku memiliki alas 6 cm dan tinggi 8 cm. Berapakah kelilingnya?",
        "options" => ["24 cm", "14 cm", "20 cm", "48 cm", "28 cm"],
        "correct" => "A", "difficulty" => "easy"
    ],
    // Pemahaman Bacaan & Menulis (PBM)
    [
        "domain" => "Pemahaman Bacaan & Menulis", "sub_materi" => "Kalimat Efektif",
        "question" => "Manakah kalimat berikut yang paling efektif?",
        "options" => [
            "Bagi semua siswa harus mengumpulkan tugas hari ini.",
            "Semua siswa harus mengumpulkan tugas hari ini.",
            "Untuk semua siswa diharapkan mengumpulkan tugas hari ini.",
            "Semua siswa-siswa harus mengumpulkan tugas hari ini.",
            "Bagi para siswa-siswi sekalian wajib mengumpulkan tugas."
        ],
        "correct" => "B", "difficulty" => "medium"
    ],
    [
        "domain" => "Pemahaman Bacaan & Menulis", "sub_materi" => "Ide Pokok",
        "question" => "Hutan hujan tropis memiliki peran penting dalam menjaga keseimbangan iklim global. Tanpanya, suhu bumi akan meningkat drastis. Ide pokok paragraf tersebut adalah...",
        "options" => [
            "Suhu bumi yang meningkat",
            "Pentingnya hutan hujan tropis",
            "Keseimbangan iklim global",
            "Hutan hujan sebagai paru-paru dunia",
            "Penyebab pemanasan global"
        ],
        "correct" => "B", "difficulty" => "easy"
    ]
];

try {
    $stmt = $pdo->prepare("INSERT INTO questions (id, subtes, domain, sub_materi, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, source_name, source_year, is_qc_passed, irt_score) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 10)");

    $count = 0;
    foreach ($mock_questions as $q) {
        $uuid = $mkuuid();
        $stmt->execute([
            $uuid,
            $q["domain"], // fallback to subtes
            $q["domain"],
            $q["sub_materi"],
            $q["difficulty"],
            $q["question"],
            $q["options"][0] ?? '',
            $q["options"][1] ?? '',
            $q["options"][2] ?? '',
            $q["options"][3] ?? '',
            $q["options"][4] ?? null,
            $q["correct"],
            "UTBK Kemdikbud Mock",
            2024
        ]);
        $count++;
    }

    echo "Successfully seeded " . $count . " questions with provenance and domains.\n";
} catch (PDOException $e) {
    echo "Error inserting data: " . $e->getMessage() . "\n";
}

