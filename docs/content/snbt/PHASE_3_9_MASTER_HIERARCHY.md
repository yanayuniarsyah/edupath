# PHASE 3.9 MASTER HIERARCHY — CANONICAL CONTENT TAXONOMY

Dokumen ini mendefinisikan standar kanonikal hirarki konten pembelajaran dan bank soal SNBT EduPath sesuai spesifikasi **Phase 3.9 Master Hierarchy**.

---

## 1. STRUKTUR HIRARKI KANONIKAL UTBK SNBT

```text
PROGRAM: SNBT
│
├── 3.9.1 PENALARAN UMUM (PU) — [TPS]
│   ├── IND — Penalaran Induktif
│   │   ├── PU-IND-01 — Pola Deret
│   │   ├── PU-IND-02 — Pola Figural/Symbolik
│   │   ├── PU-IND-03 — Analogi dan Relasi
│   │   └── PU-IND-04 — Generalisasi dan Inferensi
│   │
│   ├── DED — Penalaran Deduktif
│   │   ├── PU-DED-01 — Silogisme dan Kuantor
│   │   ├── PU-DED-02 — Logika Kondisional
│   │   └── PU-DED-03 — Penalaran Analitis
│   │
│   └── KUA — Penalaran Kuantitatif
│       ├── PU-KUA-01 — Perbandingan Kuantitas
│       ├── PU-KUA-02 — Rasio dan Proporsi
│       └── PU-KUA-03 — Estimasi dan Interpretasi Kuantitatif
│
├── 3.9.2 PENGETAHUAN DAN PEMAHAMAN UMUM (PPU) — [TPS]
│   ├── PPU-LEX-01 — Makna Kata
│   ├── PPU-LEX-02 — Relasi Makna Kata
│   ├── PPU-LEX-03 — Penggunaan Kata
│   ├── PPU-SEN-01 — Makna Frasa
│   ├── PPU-SEN-02 — Makna Kalimat
│   ├── PPU-INF-01 — Identifikasi Informasi
│   ├── PPU-INF-02 — Hubungan Informasi
│   ├── PPU-INF-03 — Inferensi Kontekstual
│   └── PPU-CTX-01 — Informasi Sosial dan Kontekstual
│
├── 3.9.3 PEMAHAMAN BACAAN DAN MENULIS (PBM) — [TPS]
│   ├── PBM-REA-01 — Informasi Eksplisit
│   ├── PBM-REA-02 — Inferensi
│   ├── PBM-REA-03 — Gagasan Utama dan Struktur
│   ├── PBM-REA-04 — Hubungan Antar Informasi
│   ├── PBM-REA-05 — Evaluasi Isi
│   ├── PBM-STR-01 — Organisasi Teks
│   ├── PBM-STR-02 — Koherensi dan Kohesi
│   ├── PBM-STR-03 — Fungsi Kalimat/Bagian
│   ├── PBM-DEV-01 — Kelengkapan Gagasan
│   ├── PBM-DEV-02 — Pengembangan Paragraf
│   ├── PBM-DEV-03 — Ringkasan dan Sintesis
│   ├── PBM-LNG-01 — Diksi
│   ├── PBM-LNG-02 — Kalimat Efektif
│   ├── PBM-LNG-03 — Ejaan dan Tanda Baca
│   ├── PBM-EDT-01 — Perbaikan Kata
│   ├── PBM-EDT-02 — Perbaikan Kalimat
│   ├── PBM-EDT-03 — Perbaikan Paragraf
│   └── PBM-EDT-04 — Revisi Teks
│
├── 3.9.4 PENGETAHUAN KUANTITATIF (PK) — [TPS]
│   ├── PK-NUM-01 — Operasi Bilangan
│   ├── PK-NUM-02 — Pecahan dan Desimal
│   ├── PK-NUM-03 — Persentase
│   ├── PK-NUM-04 — Rasio dan Proporsi
│   ├── PK-NUM-05 — Pangkat dan Akar
│   ├── PK-ALG-01 — Bentuk Aljabar
│   ├── PK-ALG-02 — Persamaan
│   ├── PK-ALG-03 — Pertidaksamaan
│   ├── PK-ALG-04 — Sistem Persamaan
│   ├── PK-ALG-05 — Fungsi
│   ├── PK-ALG-06 — Pola dan Barisan
│   ├── PK-GEO-01 — Bangun Datar
│   ├── PK-GEO-02 — Bangun Ruang
│   ├── PK-GEO-03 — Sudut
│   ├── PK-GEO-04 — Kesebangunan dan Kekongruenan
│   ├── PK-GEO-05 — Transformasi
│   ├── PK-GEO-06 — Pengukuran
│   ├── PK-DAT-01 — Representasi Data
│   ├── PK-DAT-02 — Ukuran Pemusatan
│   ├── PK-DAT-03 — Ukuran Penyebaran
│   ├── PK-DAT-04 — Analisis dan Perbandingan Data
│   ├── PK-DAT-05 — Variabilitas dan Interpretasi Statistik
│   ├── PK-PRB-01 — Ruang Sampel
│   ├── PK-PRB-02 — Pencacahan
│   ├── PK-PRB-03 — Peluang Kejadian
│   └── PK-PRB-04 — Peluang Bersyarat
│
├── 3.9.5 PENALARAN MATEMATIKA (PM) — [TES LITERASI]
│   ├── PM-REA-01 — Identifikasi Informasi Matematis
│   ├── PM-REA-02 — Representasi Situasi
│   ├── PM-REA-03 — Pemodelan Matematis
│   ├── PM-REA-04 — Formulasi Masalah
│   ├── PM-FOR-01 — Kuantifikasi Situasi
│   ├── PM-FOR-02 — Pemilihan Representasi
│   ├── PM-FOR-03 — Asumsi dan Penyederhanaan
│   ├── PM-FOR-04 — Pembentukan Model
│   ├── PM-FOR-05 — Validasi Awal Model
│   ├── PM-EMP-01 — Operasi dan Perhitungan Kontekstual
│   ├── PM-EMP-02 — Penerapan Geometri
│   ├── PM-EMP-03 — Penerapan Data dan Statistik
│   ├── PM-EMP-04 — Penerapan Peluang
│   ├── PM-EMP-05 — Integrasi Konsep
│   ├── PM-EMP-06 — Pemilihan dan Penerapan Strategi Matematis
│   ├── PM-INT-01 — Interpretasi Hasil
│   ├── PM-INT-02 — Evaluasi Kewajaran
│   ├── PM-INT-03 — Evaluasi Model dan Solusi
│   ├── PM-INT-04 — Evaluasi Klaim Kuantitatif
│   ├── PM-INT-05 — Pengambilan Keputusan
│   └── PM-INT-06 — Komunikasi Kesimpulan Matematis
│
├── 3.9.6 LITERASI DALAM BAHASA INDONESIA (LBI) — [TES LITERASI]
│   ├── LBI-FND-01 — Menemukan Informasi Eksplisit
│   ├── LBI-FND-02 — Menemukan Informasi Tersirat Lokal
│   ├── LBI-FND-03 — Menentukan Informasi yang Relevan
│   ├── LBI-UND-01 — Memahami Gagasan Utama
│   ├── LBI-UND-02 — Memahami Informasi dan Hubungan Antargagasan
│   ├── LBI-UND-03 — Menafsirkan Makna dalam Konteks
│   ├── LBI-UND-04 — Menarik Inferensi dari Teks
│   ├── LBI-UND-05 — Mensintesis Makna Teks
│   ├── LBI-EVA-01 — Mengevaluasi Klaim
│   ├── LBI-EVA-02 — Mengevaluasi Bukti
│   ├── LBI-EVA-03 — Mengevaluasi Konsistensi Informasi
│   ├── LBI-EVA-04 — Mengevaluasi Perspektif dan Argumen
│   ├── LBI-EVA-05 — Mengevaluasi Kelayakan Interpretasi
│   ├── LBI-REF-01 — Merefleksikan Isi Teks
│   ├── LBI-REF-02 — Membandingkan dengan Perspektif Lain
│   ├── LBI-REF-03 — Menghubungkan Teks dengan Konteks
│   └── LBI-REF-04 — Mengevaluasi Objektivitas dan Perspektif
│
└── 3.9.7 LITERASI DALAM BAHASA INGGRIS (LBE) — [TES LITERASI]
    ├── LBE-FND-01 — Finding Explicit Information
    ├── LBE-FND-02 — Finding Locally Implied Information
    ├── LBE-FND-03 — Selecting Relevant Information
    ├── LBE-UND-01 — Understanding Main Ideas
    ├── LBE-UND-02 — Understanding Relationships
    ├── LBE-UND-03 — Interpreting Meaning in Context
    ├── LBE-UND-04 — Making Text-Based Inferences
    ├── LBE-UND-05 — Synthesizing Meaning
    ├── LBE-EVA-01 — Evaluating Claims
    ├── LBE-EVA-02 — Evaluating Evidence
    ├── LBE-EVA-03 — Evaluating Consistency
    ├── LBE-EVA-04 — Evaluating Perspectives and Arguments
    ├── LBE-EVA-05 — Evaluating Interpretations
    ├── LBE-REF-01 — Reflecting on Text Content
    ├── LBE-REF-02 — Comparing Perspectives
    ├── LBE-REF-03 — Applying Text to Context
    ├── LBE-REF-04 — Evaluating Perspective and Objectivity
    ├── LBE-LNG-01 — Vocabulary in Context
    ├── LBE-LNG-02 — Reference and Cohesion
    ├── LBE-LNG-03 — Linguistic Structure for Meaning
    └── LBE-LNG-04 — Linguistic Cues for Meaning
```

---

## 2. STRUKTUR TERDALAM UNIVERSAL PER INDICATOR (CONTENT PACKAGE)

Setiap node `[INDICATOR]` wajib mengunci paket konten lengkap berikut:

```text
[INDICATOR: {indicator_id}]
│
├── 01. LEARNING OBJECTIVE (ID: {indicator_id}-LO01)
│
├── 02. MATERIAL UNIT (ID: {indicator_id}-M01)
│   ├── 02.1 Konsep Inti
│   ├── 02.2 Definisi
│   ├── 02.3 Prinsip / Aturan
│   ├── 02.4 Komponen
│   ├── 02.5 Karakteristik
│   ├── 02.6 Strategi
│   ├── 02.7 Langkah Penyelesaian
│   ├── 02.8 Variasi Kasus
│   ├── 02.9 Kesalahan Umum
│   ├── 02.10 Boundary / Exclusion
│   └── 02.11 Ringkasan
│
├── 03. WORKED EXAMPLE (ID: {indicator_id}-E01)
│   ├── 03.1 Stimulus
│   ├── 03.2 Pertanyaan
│   ├── 03.3 Pilihan Jawaban
│   ├── 03.4 Jawaban Benar
│   ├── 03.5 Langkah Pembahasan
│   └── 03.6 Insight / Strategi Trik Cepat
│
├── 04. PRACTICE (ID: {indicator_id}-P01)
│   ├── 04.1 Basic
│   ├── 04.2 Intermediate
│   └── 04.3 Advanced
│
├── 05. DIAGNOSTIC (ID: {indicator_id}-D01)
│   ├── 05.1 Diagnostic Item
│   ├── 05.2 Answer Key
│   ├── 05.3 Explanation
│   └── 05.4 Diagnostic Mapping
│
└── 06. TRYOUT (ID: {indicator_id}-T01)
    ├── 06.1 Tryout Item
    ├── 06.2 Answer Key
    ├── 06.3 Explanation
    ├── 06.4 Difficulty
    └── 06.5 Cognitive Demand
```

---

## 3. ATURAN PENGKODEAN ID (ID INTEGRATION STANDARD)

Setiap entitas terikat secara deterministik:
- **Topic Code**: `{Subtest}-{Group}-{No}` misal: `PU-IND-01`
- **Indicator**: `{Topic Code}-I{NN}` misal: `PU-IND-01-I01`
- **Learning Objective**: `{Indicator}-LO{NN}` misal: `PU-IND-01-I01-LO01`
- **Material Unit**: `{Indicator}-M{NN}` misal: `PU-IND-01-I01-M01`
- **Worked Example**: `{Indicator}-E{NN}` misal: `PU-IND-01-I01-E01`
- **Practice**: `{Indicator}-P{NN}` misal: `PU-IND-01-I01-P01`
- **Diagnostic**: `{Indicator}-D{NN}` misal: `PU-IND-01-I01-D01`
- **Tryout**: `{Indicator}-T{NN}` misal: `PU-IND-01-I01-T01`

---

## 4. CROSS-DIMENSIONAL METADATA (NON-HIERARCHICAL)

Atribut berikut merupakan metadata pada level butir / modul, bukan level taksonomi:
- `difficulty`: Basic | Intermediate | Advanced
- `cognitive_demand`: Pemahaman Konseptual | Penerapan / Penalaran | Evaluasi & Sintesis
- `question_type`: Pilihan Ganda (A-E) | Pilihan Majemuk Kompleks | Isian Singkat
- `stimulus_type`: Teks Naratif | Tabel Data | Grafik/Diagram | Infografis | Abstrak/None
- `context`: Personal | Saintifik | Sosial-Budaya | Kewarganegaraan
- `source`: Kitab SNBT 2024 / Balai Pengelolaan Pengujian Pendidikan (BP3)
- `year`: 2024
