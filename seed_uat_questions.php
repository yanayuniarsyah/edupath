<?php
require 'api/config.php';

try {
    $pdo->beginTransaction();

    $questions = [
        [
            'sub_materi' => 'Matematika Dasar',
            'classification' => 'LATIHAN',
            'difficulty' => 'easy',
            'question' => 'Berapakah 5 + 5?',
            'option_a' => '8',
            'option_b' => '9',
            'option_c' => '10',
            'option_d' => '11',
            'option_e' => '12',
            'correct' => 'C',
            'explanation' => 'Penjumlahan dasar: 5 ditambah 5 adalah 10.'
        ],
        [
            'sub_materi' => 'Fisika Kuantum',
            'classification' => 'ASESMEN',
            'difficulty' => 'hard',
            'question' => 'Siapa bapak fisika modern?',
            'option_a' => 'Albert Einstein',
            'option_b' => 'Isaac Newton',
            'option_c' => 'Niels Bohr',
            'option_d' => 'Max Planck',
            'option_e' => 'Galileo Galilei',
            'correct' => 'A',
            'explanation' => 'Albert Einstein sering disebut sebagai salah satu bapak fisika modern berkat teori relativitasnya.'
        ],
        [
            'sub_materi' => 'Bahasa Indonesia',
            'classification' => 'TRYOUT',
            'difficulty' => 'medium',
            'question' => 'Manakah kalimat yang baku?',
            'option_a' => 'Saya antri tiket.',
            'option_b' => 'Saya antre tiket.',
            'option_c' => 'Saya mengantri tiket.',
            'option_d' => 'Saya ngantri tiket.',
            'option_e' => 'Saya lagi antri tiket.',
            'correct' => 'B',
            'explanation' => 'Bentuk baku dari antri adalah antre.'
        ]
    ];

    $stmt = $pdo->prepare("INSERT INTO questions (id, sub_materi, classification, difficulty, question, option_a, option_b, option_c, option_d, option_e, correct, explanation) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");

    $count = 0;
    foreach ($questions as $q) {
        $id = bin2hex(random_bytes(16));
        $id = substr($id,0,8).'-'.substr($id,8,4).'-'.substr($id,12,4).'-'.substr($id,16,4).'-'.substr($id,20,12);
        
        $stmt->execute([
            $id, 
            $q['sub_materi'], 
            $q['classification'], 
            $q['difficulty'], 
            $q['question'], 
            $q['option_a'], 
            $q['option_b'], 
            $q['option_c'], 
            $q['option_d'], 
            $q['option_e'], 
            $q['correct'], 
            $q['explanation']
        ]);
        $count++;
    }

    $pdo->commit();
    echo "Berhasil menginject $count soal UAT permanen ke dalam database!\n";

} catch (Exception $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    echo "Gagal: " . $e->getMessage() . "\n";
}
