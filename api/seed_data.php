<?php
// api/seed_data.php
require_once 'config.php';

try {
    // Drop tabel lama agar skema baru bisa dibuat
    $pdo->exec("DROP TABLE IF EXISTS questions");
    
    // Buat tabel questions
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS questions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subtes VARCHAR(100) NOT NULL,
            bab VARCHAR(100) NOT NULL,
            difficulty ENUM('Mudah', 'Sedang', 'HOTS') DEFAULT 'Sedang',
            question TEXT NOT NULL,
            option_a TEXT NOT NULL,
            option_b TEXT NOT NULL,
            option_c TEXT NOT NULL,
            option_d TEXT NOT NULL,
            option_e TEXT NOT NULL,
            correct CHAR(1) NOT NULL,
            irt_score DECIMAL(5,2) DEFAULT 10.00,
            explanation TEXT,
            concept TEXT,
            is_active TINYINT(1) DEFAULT 1,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    // Pastikan tabel kosong dulu untuk reset
    $pdo->exec("TRUNCATE TABLE questions");

    $seed_data = [
        // Penalaran Umum
        [
            'subtes' => 'Penalaran Umum', 'bab' => 'Penalaran Deduktif', 'difficulty' => 'HOTS',
            'question' => '<p>Tingkat inflasi yang tinggi sering kali diikuti oleh kenaikan suku bunga bank sentral. Kenaikan suku bunga ini dimaksudkan untuk mengurangi jumlah uang yang beredar sehingga dapat menekan inflasi. Namun, kebijakan ini berdampak pada melambatnya pertumbuhan sektor riil karena biaya pinjaman menjadi lebih mahal bagi pengusaha.</p><p class="mt-4">Berdasarkan paragraf di atas, manakah pernyataan di bawah ini yang PALING MUNGKIN BENAR?</p>',
            'option_a' => 'Penurunan suku bunga bank sentral akan menyebabkan inflasi menurun.',
            'option_b' => 'Jika sektor riil tumbuh pesat, dapat dipastikan bahwa suku bunga bank sentral sedang tinggi.',
            'option_c' => 'Jika jumlah uang beredar berkurang, ada kemungkinan pertumbuhan sektor riil mengalami perlambatan.',
            'option_d' => 'Inflasi yang tinggi tidak selalu membutuhkan kebijakan dari bank sentral.',
            'option_e' => 'Pengusaha selalu merugi jika inflasi meningkat tajam.',
            'correct' => 'C', 'irt_score' => 25.50,
            'explanation' => 'Suku bunga naik -> Uang beredar berkurang -> Pertumbuhan sektor riil melambat.',
            'concept' => 'Silogisme Kausalitas'
        ],
        [
            'subtes' => 'Penalaran Umum', 'bab' => 'Pola Bilangan', 'difficulty' => 'Sedang',
            'question' => '<p>Sebuah barisan bilangan didefinisikan sebagai berikut:</p><p class="text-center font-bold text-xl my-4">2, 5, 11, 23, 47, ...</p><p>Berapakah nilai bilangan selanjutnya pada barisan tersebut?</p>',
            'option_a' => '85', 'option_b' => '90', 'option_c' => '95', 'option_d' => '100', 'option_e' => '105',
            'correct' => 'C', 'irt_score' => 15.00,
            'explanation' => 'Pola: (n * 2) + 1. 47 * 2 + 1 = 95.',
            'concept' => 'Barisan Aritmatika Bertingkat'
        ],
        [
            'subtes' => 'Penalaran Umum', 'bab' => 'Analisis Grafis', 'difficulty' => 'Mudah',
            'question' => '<p>Jika semua kambing makan rumput dan beberapa hewan yang makan rumput adalah sapi. Kesimpulan yang tepat adalah...</p>',
            'option_a' => 'Sapi adalah kambing', 'option_b' => 'Beberapa kambing bukan pemakan rumput', 'option_c' => 'Semua sapi makan rumput', 'option_d' => 'Beberapa sapi makan rumput seperti kambing', 'option_e' => 'Tidak dapat disimpulkan',
            'correct' => 'E', 'irt_score' => 8.50,
            'explanation' => 'Premis tidak menghubungkan sapi dan kambing secara langsung.',
            'concept' => 'Penarikan Kesimpulan'
        ],
        // Pengetahuan Kuantitatif
        [
            'subtes' => 'Pengetahuan Kuantitatif', 'bab' => 'Geometri', 'difficulty' => 'Sedang',
            'question' => '<p>Diketahui sebuah segitiga siku-siku memiliki panjang sisi miring 13 cm dan salah satu sisi tegaknya 5 cm. Berapakah luas segitiga tersebut?</p>',
            'option_a' => '15 cm&sup2;', 'option_b' => '30 cm&sup2;', 'option_c' => '60 cm&sup2;', 'option_d' => '65 cm&sup2;', 'option_e' => '120 cm&sup2;',
            'correct' => 'B', 'irt_score' => 12.00,
            'explanation' => 'Tripel pythagoras 5, 12, 13. Luas = 1/2 * 5 * 12 = 30.',
            'concept' => 'Teorema Pythagoras'
        ],
        [
            'subtes' => 'Pengetahuan Kuantitatif', 'bab' => 'Aljabar', 'difficulty' => 'HOTS',
            'question' => '<p>Berapa banyak bilangan bulat x yang memenuhi pertidaksamaan x^2 - 5x + 6 < 0 ?</p>',
            'option_a' => '0', 'option_b' => '1', 'option_c' => '2', 'option_d' => '3', 'option_e' => 'Tak hingga',
            'correct' => 'A', 'irt_score' => 28.50,
            'explanation' => '(x-2)(x-3) < 0 -> 2 < x < 3. Tidak ada bilangan bulat di antara 2 dan 3.',
            'concept' => 'Pertidaksamaan Kuadrat'
        ],
        // Penalaran Matematika
        [
            'subtes' => 'Penalaran Matematika', 'bab' => 'Sistem Persamaan', 'difficulty' => 'Sedang',
            'question' => '<p>Di sebuah toko buku, harga 3 buku tulis dan 2 pulpen adalah Rp21.000,00. Sedangkan harga 2 buku tulis dan 3 pulpen adalah Rp19.000,00.</p><p class="mt-4">Jika Budi membeli 5 buku tulis dan 5 pulpen dengan membayar selembar uang Rp50.000,00, berapakah uang kembalian yang diterima Budi?</p>',
            'option_a' => 'Rp5.000,00', 'option_b' => 'Rp10.000,00', 'option_c' => 'Rp15.000,00', 'option_d' => 'Rp20.000,00', 'option_e' => 'Rp40.000,00',
            'correct' => 'B', 'irt_score' => 18.50,
            'explanation' => 'Tambahkan pers 1 dan 2: 5 buku + 5 pulpen = 40.000. Kembalian 50.000 - 40.000 = 10.000.',
            'concept' => 'SPLDV'
        ],
        // Literasi Bahasa Indonesia
        [
            'subtes' => 'Literasi Bahasa Indonesia', 'bab' => 'Makna Kata', 'difficulty' => 'Mudah',
            'question' => '<p>Perhatikan kalimat berikut!</p><p class="mt-2 italic bg-gray-100 p-3 rounded">Pemerintah daerah mengimbau seluruh warga untuk mengurangi penggunaan sampah plastik demi melestarikan lingkungan yang berkelanjutan.</p><p class="mt-4">Kata yang bercetak miring <i>"mengimbau"</i> pada kalimat di atas memiliki makna yang sama dengan kata...</p>',
            'option_a' => 'Menyuruh', 'option_b' => 'Memaksa', 'option_c' => 'Mengingatkan', 'option_d' => 'Menyarankan', 'option_e' => 'Mewajibkan',
            'correct' => 'D', 'irt_score' => 7.00,
            'explanation' => 'Mengimbau berarti menyerukan atau menyarankan dengan sungguh-sungguh.',
            'concept' => 'Sinonim Kontekstual'
        ],
        [
            'subtes' => 'Literasi Bahasa Indonesia', 'bab' => 'Ide Pokok', 'difficulty' => 'Sedang',
            'question' => '<p>Kecerdasan buatan (AI) kini telah merambah banyak sektor, mulai dari kesehatan hingga pendidikan. Dalam dunia medis, AI membantu dokter mendiagnosis penyakit secara lebih cepat dan akurat. Sementara di bidang pendidikan, platform adaptif mampu menyesuaikan materi sesuai kemampuan tiap siswa.</p><p class="mt-4">Ide pokok paragraf di atas adalah...</p>',
            'option_a' => 'AI sangat berguna untuk mendiagnosis penyakit.', 'option_b' => 'Pendidikan membutuhkan platform adaptif.', 'option_c' => 'Pemanfaatan kecerdasan buatan di berbagai sektor.', 'option_d' => 'Kemajuan teknologi mempercepat diagnosis dokter.', 'option_e' => 'AI mengubah cara siswa belajar di sekolah.',
            'correct' => 'C', 'irt_score' => 14.50,
            'explanation' => 'Kalimat pertama adalah kalimat utama yang mencakup sektor kesehatan dan pendidikan.',
            'concept' => 'Gagasan Utama Paragraf'
        ],
        // Literasi Bahasa Inggris
        [
            'subtes' => 'Literasi Bahasa Inggris', 'bab' => 'Reading Comprehension', 'difficulty' => 'Sedang',
            'question' => '<p><i>The rapid development of technology has changed the way people communicate. Decades ago, letters were the primary means of long-distance communication, which took days or even weeks. Today, instant messaging allows people to connect in real time regardless of distance.</i></p><p class="mt-4">What is the main topic of the text?</p>',
            'option_a' => 'The history of letter writing.', 'option_b' => 'The evolution of communication technology.', 'option_c' => 'How to use instant messaging.', 'option_d' => 'The negative impact of technology.', 'option_e' => 'Why people prefer writing letters.',
            'correct' => 'B', 'irt_score' => 16.00,
            'explanation' => 'The text contrasts past and present communication methods driven by technology.',
            'concept' => 'Main Idea'
        ],
        [
            'subtes' => 'Literasi Bahasa Inggris', 'bab' => 'Vocabulary in Context', 'difficulty' => 'HOTS',
            'question' => '<p><i>Many animal species are facing extinction due to habitat loss. Environmentalists urge the government to implement stringent laws to protect these endangered species before it is too late.</i></p><p class="mt-4">The word <b>"stringent"</b> in the passage is closest in meaning to...</p>',
            'option_a' => 'Flexible', 'option_b' => 'Lenient', 'option_c' => 'Strict', 'option_d' => 'Obsolete', 'option_e' => 'Vague',
            'correct' => 'C', 'irt_score' => 26.50,
            'explanation' => 'Stringent means strict, precise, and exacting.',
            'concept' => 'Synonym Matching'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO questions (subtes, bab, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, irt_score, explanation, concept) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    
    foreach ($seed_data as $q) {
        $stmt->execute([
            $q['subtes'], $q['bab'], $q['difficulty'], $q['question'],
            $q['option_a'], $q['option_b'], $q['option_c'], $q['option_d'], $q['option_e'],
            $q['correct'], $q['irt_score'], $q['explanation'], $q['concept']
        ]);
    }

    // --- MATERIALS SEED ---
    $pdo->exec("DROP TABLE IF EXISTS materials");
    $pdo->exec("
        CREATE TABLE materials (
            id INT AUTO_INCREMENT PRIMARY KEY,
            subtes VARCHAR(100) NOT NULL,
            title VARCHAR(255) NOT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $materials_data = [
        [
            'subtes' => 'Penalaran Umum',
            'title' => 'Silogisme Kausalitas',
            'content' => 'Silogisme kausalitas adalah penarikan kesimpulan berdasarkan hubungan sebab-akibat. Jika A -> B dan B -> C, maka A -> C.'
        ],
        [
            'subtes' => 'Pengetahuan Kuantitatif',
            'title' => 'Tripel Pythagoras',
            'content' => 'Tripel Pythagoras adalah tiga bilangan asli a, b, c yang memenuhi a^2 + b^2 = c^2. Contoh yang sering keluar: 3, 4, 5; 5, 12, 13; 8, 15, 17; 7, 24, 25.'
        ],
        [
            'subtes' => 'Literasi Bahasa Inggris',
            'title' => 'Reading Comprehension Strategies',
            'content' => 'Skimming: reading rapidly to get the general overview. Scanning: reading rapidly to find specific facts.'
        ]
    ];

    $stmtMat = $pdo->prepare("INSERT INTO materials (subtes, title, content) VALUES (?, ?, ?)");
    foreach ($materials_data as $m) {
        $stmtMat->execute([$m['subtes'], $m['title'], $m['content']]);
    }

    echo json_encode(["success" => true, "message" => "Berhasil memuat 100% soal dan materi real ke dalam database."]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(["error" => "Database error: " . $e->getMessage()]);
}
?>
