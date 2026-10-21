# DOKUMEN SPESIFIKASI ARSITEKTUR & AUDIT SISTEM AI
## Pengayaan Platform EduPath: Fitur TKS (Tes Kemampuan Skolastik) Jenjang SMP & SMA
**Document Version:** 1.0.0-RELEASE  
**Classification:** Technical Architecture, Psychometric Specification & AI Audit Master Document  
**Target Audience:** AI Audit Systems (LLM Auditor, Code Review Agent, Security & Psychometrics Evaluator), System Architects, Product Engineers  
**Base Repository:** EduPath (Vue 3 + PHP REST API + MariaDB Modular Monolith)  
**Standard Compliance:** EduPath Target Architecture, API V1 Contract, UU No. 27/2022 (UU PDP), Standar Asesmen Kemendikbudristek / BPPP  

---

## DAFTAR ISI
1. [Executive Brief & Strategic Mandate](#1-executive-brief--strategic-mandate)
2. [Profil Pengguna & Karakteristik Kognitif (SMP vs SMA)](#2-profil-pengguna--karakteristik-kognitif-smp-vs-sma)
3. [Taksonomi Pedagogis & Domain Kemampuan TKS](#3-taksonomi-pedagogis--domain-kemampuan-tks)
4. [Model Psikometri & Scoring Engine (CTT vs IRT 2PL)](#4-model-psikometri--scoring-engine-ctt-vs-irt-2pl)
5. [Arsitektur Database & Strategi Zero-Breakage Migration](#5-arsitektur-database--strategi-zero-breakage-migration)
6. [Arsitektur Testing Engine: CAT (Adaptive) & Fixed CBT](#6-arsitektur-testing-engine-cat-adaptive--fixed-cbt)
7. [Integrasi AI: Blind Spot Detector & Diagnostic Narrative Generator](#7-integrasi-ai-blind-spot-detector--diagnostic-narrative-generator)
8. [Spesifikasi Kontrak API V1 (`/api/v1/tks/*`)](#8-spesifikasi-kontrak-api-v1-apiv1tks)
9. [Arsitektur Frontend (Vue 3 Composition API & State Flow)](#9-arsitektur-frontend-vue-3-composition-api--state-flow)
10. [Integritas Asesmen & Anti-Cheating Engine](#10-integritas-asesmen--anti-cheating-engine)
11. [Model Bisnis, Entitlement & Monetisasi Multi-Jenjang](#11-model-bisnis-entitlement--monetisasi-multi-jenjang)
12. [Keamanan, Kepatuhan Regulasi & Privasi Data Anak (UU PDP)](#12-keamanan-kepatuhan-regulasi--privasi-data-anak-uu-pdp)
13. [Risk Register & Edge Case Matrix](#13-risk-register--edge-case-matrix)
14. [Protokol Audit AI: Prompt Prompting & Rubrik Evaluasi](#14-protokol-audit-ai-prompt-prompting--rubrik-evaluasi)

---

## 1. EXECUTIVE BRIEF & STRATEGIC MANDATE

### 1.1 Latar Belakang & Masalah
EduPath pada fase awal dibangun secara monolitik dan terspesialisasi kuat (*tightly coupled*) untuk persiapan **UTBK-SNBT** (Siswa SMA Kelas 12 & Gap Year). Struktur database (`questions`, `progress`), taksonomi (`EduData.js`), dan backend API (`quiz.php`, `spp.php`) mengasumsikan bahwa semua materi berpusat pada subtes SNBT (Penalaran Umum, Pengetahuan Kuantitatif, PBM, PPU, Literasi).

Namun, terdapat *market demand* dan kebutuhan edukasi yang jauh lebih awal:
1. **Siswa SMP (Kelas 7–9):** Membutuhkan pemetaan kemampuan kognitif awal, persiapan seleksi masuk SMA unggulan berasrama/favorit (seperti MAN Insan Cendekia, SMA Pradita Dirgantara, LabSchool, SMA Taruna Nusantara, PPDB Jalur Tes/Prestasi), dan persiapan ANBK (Asesmen Nasional Berbasis Komputer).
2. **Siswa SMA (Kelas 10–11):** Membutuhkan penguatan fondasi skolastik sejak dini sebelum kelas 12, pemetaan minat/bakat Kurikulum Merdeka (penentuan mata pelajaran pilihan), dan persiapan tes kedinasan/skolastik umum.

### 1.2 Definisi Fitur: TKS (Tes Kemampuan Skolastik)
**TKS bukan TKA.**  
- **TKA (Tes Kemampuan Akademik):** Menguji penguasaan materi kurikulum mata pelajaran spesifik (Biologi, Fisika, Sejarah, Geografi).
- **TKS (Tes Kemampuan Skolastik):** Menguji *fluid intelligence*, potensi kognitif murni, kemampuan pemecahan masalah (*problem solving*), penalaran logis, numerasi terapan, dan pemahaman wacana tanpa bergantung pada hafalan rumus/fakta kurikulum kaku.

### 1.3 Mandate Pengembangan
Membangun modul **Universal TKS Engine** di dalam ekosistem EduPath yang melayani jenjang **SMP** dan **SMA** dengan prinsip:
- **Zero-Breakage Guarantee:** Tidak merusak atau mengganggu skema data, endpoint, dan pipeline SNBT yang sedang berjalan di *production*.
- **Data-Driven Taxonomy:** Taksonomi tidak lagi di-hardcode di Vue atau PHP, melainkan dikelola secara dinamis berbasis data (*tree taxonomy*).
- **Stateless & Scalable:** Engine mampu melayani 50.000 peserta tes simultan per tenant (sekolah) dengan *server-side authoritative scoring*.
- **Psychometrically Sound:** Menggabungkan Classical Test Theory (CTT) untuk evaluasi cepat dan Item Response Theory (IRT 2PL) untuk *ability estimation* ($\theta$).

---

## 2. PROFIL PENGGUNA & KARAKTERISTIK KOGNITIF (SMP VS SMA)

| Dimensi Analisis | Jenjang SMP (Usia 12–15 Tahun) | Jenjang SMA (Usia 15–18 Tahun) |
| :--- | :--- | :--- |
| **Tahapan Perkembangan Kognitif (Piaget)** | Transisi dari *Konkret Operasional* ke *Formal Operasional*. Mulai mampu berpikir abstrak namun masih memerlukan konteks realistis/visual. | *Formal Operasional Penuh*. Mampu berpikir proporsional, hipotesis-deduktif, dan manipulasi simbolik abstrak. |
| **Tujuan Asesmen (Objective)** | 1. Diagnosa potensi skolastik dasar.<br>2. Persiapan PPDB Prestasi / Seleksi SMA Unggulan.<br>3. Pemetaan kemampuan literasi & numerasi ANBK.<br>4. Deteksi bakat & gaya berpikir logis. | 1. Fondasi akselerasi UTBK-SNBT.<br>2. Pemilihan peminatan mapel Kurikulum Merdeka.<br>3. Latihan tes potensi akademik kedinasan.<br>4. Pengukuran kesiapan pendidikan tinggi. |
| **Karakteristik Stimulus Soal** | Teks stimulus lebih pendek (100–250 kata), infografis visual, diagram tabel sederhana, kontekstual kehidupan remaja/sekolah. | Teks majemuk sintesis (250–500 kata), data multi-grafik, kontradiksi premis logika formal, konteks sains & sosio-ekonomi nyata. |
| **Cognitive Demand (Bloom Anderson)** | Dominan C2 (Pemahaman), C3 (Aplikasi), dan C4 (Analisis Terpandu). | Dominan C4 (Analisis Kompleks), C5 (Evaluasi Argumen), dan C6 (Sintesis Hipotesis). |
| **Ekspektasi Waktu per Butir** | 60 – 75 detik per butir soal. | 45 – 60 detik per butir soal (standar kecepatan SNBT/CAT). |
| **Dukungan Gamifikasi & UI** | Membutuhkan UI yang *engaging*, visual menarik, *streak badge*, ilustrasi maskot edukatif, bahasa ramah anak. | Tampilan minimalis, *focus-mode*, analitik berbasis radar chart, komparasi persentil, metrik performa presisi tinggi. |

---

## 3. TAKSONOMI PEDAGOGIS & DOMAIN KEMAMPUAN TKS

### 3.1 Domain Taksonomi TKS SMP (Grade 7 – 9)

```
TKS_SMP (Tes Kemampuan Skolastik SMP)
├── 1. PENALARAN VERBAL (VER_SMP)
│   ├── VER_SMP_01: Hubungan Kata & Analogi Dasar
│   ├── VER_SMP_02: Silogisme Konseptual & Pola Ingkar
│   ├── VER_SMP_03: Pemahaman Wacana & Ide Pokok Terarah
│   └── VER_SMP_04: Penarikan Simpulan Kasus Keseharian
├── 2. PENALARAN NUMERIK & ARITMATIKA (NUM_SMP)
│   ├── NUM_SMP_01: Deret Pola Bilangan & Barisan Teratur
│   ├── NUM_SMP_02: Aritmatika Sosial & Estimasi Angka Terapan
│   ├── NUM_SMP_03: Aljabar Bernalar & Perbandingan Kuantitas
│   └── NUM_SMP_04: Interpretasi Diagram & Tabel Informasi
├── 3. PENALARAN SPASIAL & FIGURAL (SPA_SMP)
│   ├── SPA_SMP_01: Rotasi & Pencerminan Bangun 2D
│   ├── SPA_SMP_02: Serial Pola Bentuk Geometris
│   └── SPA_SMP_03: Jaring-jaring & Rekonstruksi Bangun Ruang Dasar
└── 4. PENALARAN LOGIS & ANALITIK (LOG_SMP)
    ├── LOG_SMP_01: Urutan Posisi & Penjadwalan Sederhana
    └── LOG_SMP_02: Klasifikasi Logis & Pengelompokan Kriteria
```

### 3.2 Domain Taksonomi TKS SMA (Grade 10 – 12)

```
TKS_SMA (Tes Kemampuan Skolastik SMA)
├── 1. PENALARAN INDUKTIF & DEDUKTIF FORMAL (LOG_SMA)
│   ├── LOG_SMA_01: Silogisme Kompleks, Modus Ponens/Tollens & Ekuivalensi
│   ├── LOG_SMA_02: Penalaran Analitik Relasional & Matriks Kondisional
│   ├── LOG_SMA_03: Evaluasi Validitas Argumen & Fallacy Detection
│   └── LOG_SMA_04: Generalisasi Induktif & Penalaran Kausalitas
├── 2. PENALARAN KUANTITATIF & KECUKUPAN DATA (NUM_SMA)
│   ├── NUM_SMA_01: Logika Kuantitatif Lanjut & Pemodelan Matematis
│   ├── NUM_SMA_02: Perbandingan Hubungan Kuantitas ($P$ vs $Q$)
│   ├── NUM_SMA_03: Soal Kecukupan Data (Pernyataan 1 dan 2 Cukup?)
│   └── NUM_SMA_04: Analisis Data Majemuk & Probabilitas Kondisional
├── 3. PENALARAN FIGURAL & MATRIKS ABSTRAK (SPA_SMA)
│   ├── SPA_SMA_01: Transformasi Matriks Figural 3x3
│   ├── SPA_SMA_02: Spasial 3D Lanjut (Irisan Bidang & Perspektif)
│   └── SPA_SMA_03: Analogi Bentuk Abstrak Non-Verbal
└── 4. PENALARAN KRITIS & WACANA ANALITIS (VER_SMA)
    ├── VER_SMA_01: Kritik Opini, Implikasi Tersembunyi & Bias Wacana
    ├── VER_SMA_02: Sintesis Multi-Sumber Teks Akademik
    └── VER_SMA_03: Analogi Konseptual Abstrak
```

### 3.3 Validitas Konstruk & Standar Penyusunan Butir Soal
Setiap butir soal TKS diwajibkan melewati *Quality Control* (QC) otomatis dan manual dengan metadata:
- **Tingkat Kesukaran Historis ($p$-value):** Ditargetkan berdistribusi normal (Mudah: $0.70 < p \le 0.90$, Sedang: $0.30 \le p \le 0.70$, Sukar: $0.10 \le p < 0.30$).
- **Daya Beda ($r_{pbis}$):** Koefisien korelasi poin biserial wajib $\ge 0.25$. Butir soal dengan $r_{pbis} < 0.20$ otomatis dikarantina (*auto-quarantine*).
- **Distraktor Efektif:** Seluruh opsi distraktor (A, B, C, D untuk SMP; A, B, C, D, E untuk SMA) harus dipilih minimal oleh 5% peserta dalam kelompok uji kalibrasi.

---

## 4. MODEL PSIKOMETRI & SCORING ENGINE (CTT VS IRT 2PL)

### 4.1 Filosofi Dual-Engine Scoring
EduPath menerapkan dua lapis mesin kalkulasi skor untuk menyeimbangkan performa komputasi real-time dan akurasi psikometri ilmiah:

```
[Student Answers]
       │
       ▼
┌────────────────────────────────────────────────────────┐
│               STAGE 1: Deterministic Engine            │
│  - Instant CTT Raw Score & Subtest Accuracy (%)       │
│  - Strict Execution Time: < 30ms                      │
└──────────────────────────────────┬─────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────┐
│             STAGE 2: IRT 2PL Estimation Engine         │
│  - Latent Trait Theta (θ) Computation (-3.0 to +3.0)   │
│  - Scaled Standard Score (200 - 800)                  │
│  - Percentile Rank relative to Norm Cohort             │
└──────────────────────────────────┬─────────────────────┘
                                   │
                                   ▼
┌────────────────────────────────────────────────────────┐
│             STAGE 3: AI Diagnostic Advisor             │
│  - Interprets Theta & Blind Spots                      │
│  - Generates Natural Language Recommendation          │
└────────────────────────────────────────────────────────┘
```

### 4.2 Formula Item Response Theory (IRT 2-Parameter Logistic / 2PL)
Probabilitas siswa dengan kemampuan $\theta$ menjawab benar butir soal $i$ dirumuskan:

$$P_i(\theta) = \frac{1}{1 + e^{-D \cdot a_i (\theta - b_i)}}$$

Di mana:
- $\theta$: Estimasi tingkat kemampuan skolastik siswa (*latent trait*), berdistribusi normal baku $\mathcal{N}(0, 1)$ dengan rentang teoritis $[-3.0, +3.0]$.
- $b_i$: Indeks tingkat kesukaran (*item difficulty*) butir soal $i$.
- $a_i$: Indeks daya pembeda (*item discrimination*) butir soal $i$ ($0.5 \le a_i \le 2.5$).
- $D$: Faktor penskalaan konstanta psikometri ($D = 1.702$ untuk aproksimasi fungsi normal).

### 4.3 Algoritma Estimasi Kemampuan ($\theta$)
Menggunakan metode **Expected A Posteriori (EAP)** atau **Maximum Likelihood Estimation (MLE)** ter-regularisasi dengan prior distribusi normal baku untuk mencegah estimasi tak berhingga saat siswa benar semua atau salah semua:

$$\theta_{\text{final}} = \arg\max_\theta \left[ \sum_{i=1}^{n} \Big( u_i \ln P_i(\theta) + (1 - u_i) \ln (1 - P_i(\theta)) \Big) - \frac{\theta^2}{2} \right]$$

Di mana $u_i \in \{0, 1\}$ adalah respons siswa pada butir soal $i$.

### 4.4 Standardized Conversion Scale
Skor mentah $\theta$ dikonversikan ke skala standar yang mudah dipahami peserta dan orang tua:
- **TKS SMA (Skala Standar UTBK):**  
  $$\text{Score}_{\text{SMA}} = 500 + (\theta \times 100)$$  
  *(Dibatasi pada rentang min 200 s/d max 800)*
- **TKS SMP (Skala Stanine & Nilai Standar Nasional):**  
  $$\text{Score}_{\text{SMP}} = 70 + (\theta \times 10)$$  
  *(Dibatasi pada rentang min 40 s/d max 100, dilengkapi dengan Kategori: Superior, Di Atas Rata-rata, Rata-rata, Butuh Bimbingan)*.

---

## 5. ARSITEKTUR DATABASE & STRATEGI ZERO-BREAKAGE MIGRATION

### 5.1 Skema Database Eksis vs Masalah Keterikatan
Tabel `questions` saat ini hanya memiliki kolom `subtes` (berisi nilai seperti "Penalaran Umum", "Literasi Bahasa Indonesia") tanpa membedakan apakah soal tersebut untuk SMP, SMA, atau SNBT umum. Jika soal SMP diinput begitu saja, sistem tryout SNBT eksis di `api/quiz.php` berisiko mencampur soal SMP ke dalam tryout pejuang PTN.

### 5.2 Strategi Zero-Breakage (Non-Destructive Migration)
Prinsip utama:
1. Tidak ada perubahan nama kolom lama.
2. Tidak ada penghapusan kolom lama.
3. Penambahan kolom baru wajib memiliki nilai default yang memetakan data eksis ke `'SMA'` dan `'SNBT'`.
4. Index ditambahkan untuk mengoptimasi query multi-tenant dan multi-level.

### 5.3 DDL Migration Script (`migration_v2_tks_multilevel.sql`)

```sql
-- ============================================================================
-- MIGRATION: UNIVERSAL TAXONOMY & MULTI-LEVEL TKS SUPPORT (SMP & SMA)
-- Safe, Idempotent, and Backward-Compatible
-- ============================================================================

-- 1. Modifikasi Tabel `questions`
ALTER TABLE `questions`
ADD COLUMN `education_level` ENUM('SMP', 'SMA', 'UMUM') NOT NULL DEFAULT 'UMUM' AFTER `id`,
ADD COLUMN `grade_tier` VARCHAR(10) NOT NULL DEFAULT 'ALL' AFTER `education_level`,
ADD COLUMN `assessment_family` ENUM('SNBT', 'TKS', 'TKA', 'AKM') NOT NULL DEFAULT 'SNBT' AFTER `grade_tier`,
ADD COLUMN `irt_a` FLOAT DEFAULT 1.0 AFTER `irt_score`,
ADD COLUMN `irt_b` FLOAT DEFAULT 0.0 AFTER `irt_a`,
ADD COLUMN `irt_c` FLOAT DEFAULT 0.0 AFTER `irt_b`;

-- Tambahkan Composite Index untuk pencarian kilat berdasarkan jenjang & keluarga asesmen
CREATE INDEX `idx_questions_taxo_lookup` 
ON `questions` (`assessment_family`, `education_level`, `subtes`, `is_active`);

CREATE INDEX `idx_questions_grade` 
ON `questions` (`education_level`, `grade_tier`);

-- 2. Modifikasi Tabel `students`
ALTER TABLE `students`
ADD COLUMN `education_level` ENUM('SMP', 'SMA', 'GAPYEAR', 'UMUM') NOT NULL DEFAULT 'SMA' AFTER `school`,
ADD COLUMN `grade_class` TINYINT(2) DEFAULT NULL AFTER `education_level`,
ADD COLUMN `target_school_type` VARCHAR(100) DEFAULT NULL AFTER `target_ptn`;

-- 3. Modifikasi Tabel `quiz_results`
ALTER TABLE `quiz_results`
ADD COLUMN `education_level` ENUM('SMP', 'SMA', 'UMUM') NOT NULL DEFAULT 'SMA' AFTER `student_id`,
ADD COLUMN `assessment_family` ENUM('SNBT', 'TKS', 'TKA', 'AKM') NOT NULL DEFAULT 'SNBT' AFTER `education_level`,
ADD COLUMN `theta_score` FLOAT DEFAULT NULL AFTER `score`,
ADD COLUMN `sem` FLOAT DEFAULT NULL AFTER `theta_score`,
ADD COLUMN `domain_breakdown` JSON DEFAULT NULL AFTER `answers`;

CREATE INDEX `idx_quiz_results_lookup` 
ON `quiz_results` (`student_id`, `assessment_family`, `education_level`, `created_at`);

-- 4. Modifikasi Tabel `progress`
ALTER TABLE `progress`
ADD COLUMN `education_level` ENUM('SMP', 'SMA', 'UMUM') NOT NULL DEFAULT 'SMA' AFTER `student_id`,
ADD COLUMN `assessment_family` ENUM('SNBT', 'TKS', 'TKA', 'AKM') NOT NULL DEFAULT 'SNBT' AFTER `education_level`;

-- Drop old unique key safely and recreate composite unique key
ALTER TABLE `progress` 
DROP INDEX `student_subtes_bab`,
ADD UNIQUE KEY `std_taxo_progress_uq` (`student_id`, `assessment_family`, `education_level`, `subtes`, `bab`);

-- 5. Tabel Baru: `tks_diagnostic_sessions`
CREATE TABLE IF NOT EXISTS `tks_diagnostic_sessions` (
  `id` VARCHAR(36) NOT NULL,
  `student_id` VARCHAR(36) NOT NULL,
  `tenant_id` VARCHAR(36) NOT NULL,
  `education_level` ENUM('SMP', 'SMA') NOT NULL,
  `status` ENUM('in_progress', 'completed', 'abandoned') NOT NULL DEFAULT 'in_progress',
  `current_step` INT(11) NOT NULL DEFAULT 1,
  `total_steps` INT(11) NOT NULL DEFAULT 20,
  `question_ids` JSON NOT NULL,
  `responses` JSON DEFAULT NULL,
  `theta_estimate` FLOAT DEFAULT 0.0,
  `radar_metrics` JSON DEFAULT NULL,
  `ai_feedback` TEXT DEFAULT NULL,
  `started_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `completed_at` TIMESTAMP NULL DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `tks_session_std_idx` (`student_id`),
  KEY `tks_session_tenant_idx` (`tenant_id`),
  CONSTRAINT `tks_session_std_fk` FOREIGN KEY (`student_id`) REFERENCES `students` (`id`) ON DELETE CASCADE,
  CONSTRAINT `tks_session_tenant_fk` FOREIGN KEY (`tenant_id`) REFERENCES `tenants` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci;
```

---

## 6. ARSITEKTUR TESTING ENGINE: CAT (ADAPTIVE) & FIXED CBT

### 6.1 Mode Pengujian TKS
Platform mendukung 2 modus pelaksanaan asesmen TKS:

1. **Mode A: Fixed-Length Balanced CBT (Tryout / Simulasi Masuk Sekolah)**
   - Jumlah butir dan komposisi waktu fix per subtes.
   - Semua siswa mendapatkan paket soal dengan distribusi bobot kesulitan seimbang.
   - Digunakan untuk simulasi ujian serentak, seleksi sekolah, dan evaluasi periodik.
2. **Mode B: Computerized Adaptive Testing / Multi-Stage Testing (CAT-MST)**
   - Digunakan khusus pada modul **Diagnostik Cepat & Student Potential Path (SPP)**.
   - Menggunakan alur 3-Stage Routing (Router -> Intermediate -> Specialist) untuk menentukan kemampuan siswa secara akurat hanya dalam 15–20 butir soal tanpa membuat siswa frustrasi.

```
                  ┌──────────────────────────────┐
                  │   STAGE 1: ROUTER STAGE      │
                  │   (5 Soal Tingkat Sedang)    │
                  └──────────────┬───────────────┘
                                 │
                 ┌───────────────┴───────────────┐
                 │                               │
           Skor Rendah                      Skor Tinggi
                 │                               │
                 ▼                               ▼
  ┌──────────────────────────────┐ ┌──────────────────────────────┐
  │  STAGE 2A: TINGKAT DASAR     │ │  STAGE 2B: TINGKAT LANJUT    │
  │  (5 Soal Kesukaran Rendah)   │ │  (5 Soal Kesukaran Tinggi)   │
  └──────────────┬───────────────┘ └──────────────┬───────────────┘
                 │                                │
                 ▼                                ▼
  ┌──────────────────────────────┐ ┌──────────────────────────────┐
  │  STAGE 3A: REMEDIAL DETECTOR │ │  STAGE 3B: MASTERY VALIDATOR │
  │  (5 Soal Diagnosa Blind Spot)│ │  (5 Soal HOTS / Daya Pembeda)│
  └──────────────────────────────┘ └──────────────────────────────┘
```

### 6.2 Algoritma Item Selection (Fisher Information)
Pada mode CAT penuh, butir soal berikutnya dipilih berdasarkan nilai **Item Information Function (IIF)** maksimum pada estimasi kemampuan siswa saat ini ($\hat{\theta}_k$):

$$I_i(\hat{\theta}) = a_i^2 \cdot P_i(\hat{\theta}) \cdot (1 - P_i(\hat{\theta}))$$

Soal dengan $I_i(\hat{\theta})$ terbesar memberikan informasi psikometri paling presisi pada titik kemampuan siswa, meminimalkan *Standard Error of Measurement* (SEM).

---

## 7. INTEGRASI AI: BLIND SPOT DETECTOR & DIAGNOSTIC NARRATIVE GENERATOR

### 7.1 Pemisahan Peran Deterministik vs AI Generatif
> **PRINSIP KRITIS ARSITEKTUR (NON-NEGOTIABLE):**  
> Model AI LLM **DILARANG MENGHITUNG ATAU MENGUBAH SKOR**.  
> Skor angka, persentil, dan trait $\theta$ dihitung 100% oleh deterministic backend engine berbasis rumus IRT teruji.  
> Peran AI dibatasi secara murni sebagai **Pedagogical Diagnostic Interpreter** yang mengubah vektor angka psikometri menjadi wawasan reflektif dan rencana aksi belajar terstruktur.

### 7.2 Struktur Payload Input ke AI Diagnostic Pipeline
Backend memformat data hasil tes menjadi *structured prompt payload*:

```json
{
  "student_meta": {
    "education_level": "SMP",
    "grade_class": 8,
    "target_destination": "SMA Pradita Dirgantara"
  },
  "psychometric_result": {
    "overall_score": 76.5,
    "theta": 0.65,
    "percentile": 74.2,
    "category": "Di Atas Rata-Rata"
  },
  "domain_performance": [
    {
      "domain_code": "VER_SMP",
      "name": "Penalaran Verbal",
      "accuracy_pct": 85.0,
      "avg_response_time_sec": 38.2,
      "status": "STRONG"
    },
    {
      "domain_code": "NUM_SMP",
      "name": "Penalaran Numerik",
      "accuracy_pct": 50.0,
      "avg_response_time_sec": 82.5,
      "status": "WEAK",
      "failed_topics": ["Aritmatika Sosial", "Aljabar Bernalar"]
    },
    {
      "domain_code": "SPA_SMP",
      "name": "Penalaran Spasial",
      "accuracy_pct": 90.0,
      "avg_response_time_sec": 29.1,
      "status": "MASTERY"
    },
    {
      "domain_code": "LOG_SMP",
      "name": "Penalaran Logis",
      "accuracy_pct": 65.0,
      "avg_response_time_sec": 61.0,
      "status": "MODERATE",
      "failed_topics": ["Urutan Posisi & Penjadwalan"]
    }
  ]
}
```

### 7.3 System Prompt Guardrail untuk AI Engine
```text
Anda adalah EduPath Cognitive Diagnostic Advisor berstandar psikometri pendidikan.
Tugas Anda: Memberikan narasi diagnostik konstruktif untuk siswa berdasarkan data psikometri terlampir.
Aturan Keras:
1. JANGAN pernah memodifikasi, mengoreksi, atau memperdebatkan angka skor psikometri yang disediakan.
2. Gunakan tone yang suportif, memotivasi, objektif, dan berorientasi pada pertumbuhan (Growth Mindset).
3. Jika education_level == 'SMP', gunakan bahasa yang mudah dicerna remaja usia 13-15 tahun dan sediakan ringkasan terpisah untuk orang tua.
4. Identifikasi tepat 1 'Superpower' (aspek terkuat) dan tepat 1 'Blind Spot' (kelemahan tersembunyi yang perlu diprioritaskan).
5. Sediakan actionable 7-day micro-learning recommendation berdasarkan failed_topics.
```

---

## 8. SPESIFIKASI KONTRAK API V1 (`/api/v1/tks/*`)

Selaras dengan standard [API_V1_CONTRACT.md](file:///c:/Users/yanay/OneDrive/Documents/YANA/ELYANA.BIZ.ID/7%20BIMBEL/BIMBEL%20V1/API_V1_CONTRACT.md).

### 8.1 Ringkasan Endpoint

| Method | Endpoint | Auth | Entitlement Key | Deskripsi |
| :--- | :--- | :--- | :--- | :--- |
| `GET` | `/api/v1/tks/taxonomy` | Public/Guest | None | Mengambil hierarki domain materi TKS per jenjang (`SMP`/`SMA`). |
| `POST` | `/api/v1/tks/assessment/start` | JWT User | `tks_assessment_access` | Menginisialisasi sesi pengerjaan TKS baru (CBT atau CAT). |
| `POST` | `/api/v1/tks/assessment/submit-item`| JWT User | `tks_assessment_access` | Mengirim jawaban butir soal (khusus mode adaptif CAT/MST). |
| `POST` | `/api/v1/tks/assessment/finish` | JWT User | `tks_assessment_access` | Finalisasi sesi, kalkulasi $\theta$, dan pencatatan riwayat ujian. |
| `GET` | `/api/v1/tks/assessment/{session_id}/report` | JWT User | `tks_report_basic` | Mengambil detail laporan psikometri & narasi AI. |
| `GET` | `/api/v1/tks/radar/summary` | JWT User | `tks_report_basic` | Mengambil data agregat radar bakat/kognitif lintas subtes. |

### 8.2 Contoh Payload: `POST /api/v1/tks/assessment/start`

**Request Headers:**
```http
Authorization: Bearer <jwt_token>
X-CSRF-Token: <csrf_token>
Content-Type: application/json
```

**Request Body:**
```json
{
  "education_level": "SMP",
  "mode": "DIAGNOSTIC_CAT",
  "grade_class": 8,
  "client_instance_id": "c1f7b82e-9d82-4f33-8a02-55c3c0de8219"
}
```

**Success Response (HTTP 201 Created):**
```json
{
  "success": true,
  "data": {
    "session_id": "sess_98e1a7b4-3c1a-4d22-b519-7d0c897f110a",
    "education_level": "SMP",
    "total_duration_sec": 1800,
    "current_stage": 1,
    "first_question": {
      "id": "q_smp_num_0042",
      "domain": "NUM_SMP",
      "sub_materi": "Aritmatika Sosial",
      "cognitive_demand": "C3",
      "question": "Sebuah koperasi sekolah membeli 2 lusin buku tulis dengan diskon 15%...",
      "options": [
        {"key": "A", "text": "Rp 68.000,00"},
        {"key": "B", "text": "Rp 72.000,00"},
        {"key": "C", "text": "Rp 81.600,00"},
        {"key": "D", "text": "Rp 96.000,00"}
      ]
    }
  }
}
```

### 8.3 Contoh Payload: `POST /api/v1/tks/assessment/finish`

**Request Body:**
```json
{
  "session_id": "sess_98e1a7b4-3c1a-4d22-b519-7d0c897f110a",
  "answers": [
    {"question_id": "q_smp_num_0042", "answer": "C", "duration_sec": 42},
    {"question_id": "q_smp_ver_0019", "answer": "A", "duration_sec": 28}
  ],
  "client_telemetry": {
    "tab_switch_count": 0,
    "full_screen_exits": 0
  }
}
```

**Success Response (HTTP 200 OK):**
```json
{
  "success": true,
  "data": {
    "session_id": "sess_98e1a7b4-3c1a-4d22-b519-7d0c897f110a",
    "education_level": "SMP",
    "score": 76.5,
    "stanine_rank": 7,
    "category": "Di Atas Rata-Rata",
    "theta": 0.65,
    "sem": 0.18,
    "radar_metrics": {
      "verbal": 85,
      "numerik": 50,
      "spasial": 90,
      "logis": 65
    },
    "ai_diagnosis": {
      "superpower": "Penalaran Spasial Luar Biasa (Skor 90) - Kamu sangat cepat memvisualisasikan rotasi bentuk 3D.",
      "blind_spot": "Penalaran Numerik (Aritmatika Sosial) - Ada kecenderungan terburu-buru saat menghitung persentase bertingkat.",
      "growth_recommendation": "Kerjakan 3 latihan pemahaman rasio dan diskon bertingkat selama 15 menit per hari."
    }
  }
}
```

---

## 9. ARSITEKTUR FRONTEND (VUE 3 COMPOSITION API & STATE FLOW)

### 9.1 Dekomposisi Komponen Modular
Mematuhi rekomendasi refactoring dari codebase monolitik `App.vue`, fitur TKS dipecah menjadi komponen modular:

```text
src/components/tks/
├── TksLevelSelector.vue        # Switcher Jenjang (SMP / SMA) dengan transition styling
├── TksDiagnosticOnboarding.vue # Edukasi tujuan tes, visual instruksi, pre-flight check
├── TksExamScreen.vue           # CBT/CAT Engine (Timer, Hotkeys, Question Card, Options)
│   ├── TksQuestionViewer.vue   # Render teks wacana, rumus KaTeX, gambar spasial
│   ├── TksOptionRadio.vue      # Radio buttons responsif dengan hover feedback
│   └── TksExamProgress.vue     # Breadcrumbs nomor soal & indikator status (ragu/terjawab)
├── TksRadarSummary.vue         # Chart.js / SVG Radar Visualisasi 4 Pilar Kemampuan Skolastik
└── TksAiReportModal.vue        # Pop-up rekomendasi AI & tombol cetak PDF Rapor Siswa
```

### 9.2 State Management (Composables `useTksExam.js`)
Pemisahan logika bisnis asesmen dari komponen UI menggunakan Vue 3 Composables:
- Menjaga state lokal tersinkronisasi di `sessionStorage` (tahan reload / *refresh-safe*).
- Menghitung sisa durasi ujian berbasis *server epoch timestamp* (mencegah manipulasi jam lokal).
- Mengirim *heartbeat telemetry* tiap 60 detik untuk mendeteksi *drop connection*.

---

## 10. INTEGRITAS ASESMEN & ANTI-CHEATING ENGINE

Mengingat hasil TKS SMP dan SMA dapat digunakan untuk seleksi internal sekolah atau prediksi lolos sekolah unggulan, integritas tes wajib dilindungi:

1. **Server-Authoritative Clock:**  
   Waktu pengerjaan dihitung dari selisih `CURRENT_TIMESTAMP` di database dengan waktu `started_at`. Manipulasi jam pada browser klien tidak akan mempengaruhi batas waktu server.
2. **Tab Switch & Window Blur Detection:**  
   Frontend memonitor event `document.addEventListener('visibilitychange')`. Siswa yang meninggalkan tab ujian lebih dari batas toleransi (misal 3 kali) akan menerima peringatan keras dan status flagged pada audit log.
3. **Randomized Question Seeding & Option Shuffling:**  
   Urutan butir soal dan posisi opsi distraktor (A, B, C, D) diacak per sesi menggunakan algoritma *Fisher-Yates Shuffle* dengan *cryptographic random seed* di sisi backend.
4. **Clipboard & Inspect Element Blocker:**  
   Menonaktifkan pintasan `Ctrl+C`, `Ctrl+U`, `F12`, dan event `contextmenu` selama sesi pengerjaan ujian berlangsung.

---

## 11. MODEL BISNIS, ENTITLEMENT & MONETISASI MULTI-JENJANG

### 11.1 Matriks Paket Berlangganan & Entitlement Key

| Nama Paket | Target Pengguna | Fitur & Entitlement Keys | Harga Acuan |
| :--- | :--- | :--- | :--- |
| **TKS Diagnostic Free** | Tamu / Murid Baru (SMP & SMA) | - `tks_diag_free` (1x Sesi SPP Diagnostik)<br>- `tks_radar_basic` (Hasil radar 4 pilar) | Rp 0 (Lead Magnet) |
| **EduPath Junior Pro (SMP)** | Siswa Kelas 7–9 & Orang Tua | - `tks_smp_unlimited`<br>- `tks_smp_tryout_kedinasan`<br>- `tks_parent_report_pdf`<br>- `tks_ai_tutor_basic` | Rp 149.000 / 3 Bulan |
| **EduPath Senior Pro (SMA)** | Siswa Kelas 10–11 | - `tks_sma_unlimited`<br>- `tks_sma_kurikulum_merdeka`<br>- `tks_ai_tutor_full` | Rp 199.000 / 6 Bulan |
| **EduPath Ultimate K-12** | Siswa SMA Kelas 12 & Gap Year | Akses Penuh: **TKS SMA + SNBT UTBK 2027 + All Entitlements** | Rp 349.000 / Tahun |
| **EduPath School B2B License** | Sekolah SMP / SMA Mitra | - `tks_tenant_batch_exam`<br>- `tks_admin_school_analytics`<br>- `tks_bulk_export_csv` | Kontrak Institusi |

### 11.2 Validasi Entitlement pada Layer Backend (`api/EntitlementService.php`)
Setiap request ke endpoint ujian diproteksi oleh:
```php
if (!EntitlementService::hasAccess($studentId, 'tks_smp_unlimited')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => [
            'code' => 'FEATURE_LOCKED',
            'message' => 'Paket belajar TKS SMP diperlukan untuk mengakses bank soal ini.'
        ]
    ]);
    exit;
}
```

---

## 12. KEAMANAN, KEPATUHAN REGULASI & PRIVASI DATA ANAK (UU PDP)

Sesuai Undang-Undang Perlindungan Data Pribadi (UU PDP No. 27 Tahun 2022) Republik Indonesia:
1. **Pemrosesan Data Anak Dibawah Umur (Pasal 25):**  
   Pengguna jenjang SMP (mayoritas usia 12–15 tahun) diklasifikasikan sebagai subjek data spesifik anak. EduPath mewajibkan persetujuan wali/orang tua (*Parental Consent Checkbox*) saat registrasi siswa SMP.
2. **Minimisasi Data (Data Minimization):**  
   Sistem tidak mengumpulkan nomor NIK atau data biometrik untuk akun B2C perorangan. Cukup nama, email/nomor orang tua, dan jenjang sekolah.
3. **Penyimpanan Password & Session:**  
   Menggunakan `password_hash()` algoritma Argon2id / Bcrypt dengan `cost` 12. Token session JWT disimpan dalam cookie `HttpOnly`, `SameSite=Lax`, dan flag `Secure`.

---

## 13. RISK REGISTER & EDGE CASE MATRIX

| ID | Kategori Risiko | Skenario Edge Case | Dampak | Strategi Mitigasi Arsitektur |
| :---: | :--- | :--- | :---: | :--- |
| **R1** | Database Contamination | Soal TKS SMP bercampur ke dalam Tryout Akbar SNBT Kelas 12. | **CRITICAL** | Filter wajib pada SQL `WHERE assessment_family = 'SNBT'` di seluruh query legacy. Tambahkan unit test regresi di `test_all_requirements.php`. |
| **R2** | Psychometric Bias | Siswa menjawab benar semua pada Stage 1 CAT sehingga $\theta$ melonjak tanpa data variansi. | **HIGH** | Terapkan batas regularisasi Bayes EAP (Bounded Theta $\theta \in [-3.0, +3.0]$) dengan SEM minimal $0.15$. |
| **R3** | Concurrency Spike | 15.000 siswa SMP serentak menekan tombol "Kirim Jawaban" pada menit terakhir ujian B2B. | **HIGH** | Pola antrean asinkron (Message Queue) atau idempotent upsert dengan database connection pooling. Hindari locking transaksional berat saat submit. |
| **R4** | LLM Hallucination | AI memberikan diagnosa yang kontradiktif dengan skor (misal: skor 40 dibilang "Sangat Hebat"). | **HIGH** | Strict Few-Shot Grounding & Validator JSON Schema sebelum narasi disimpan ke database. Jika schema tidak lolos, gunakan fallback rule-based template. |
| **R5** | Connection Drop | Siswa SMP mengerjakan soal di daerah dengan sinyal seluler tidak stabil. | **MEDIUM** | Frontend IndexedDB / LocalStorage caching. Jawaban butir tersimpan lokal dan disinkronkan secara otomatis saat koneksi kembali online. |

---

## 14. PROTOKOL AUDIT AI: PROMPT PROMPTING & RUBRIK EVALUASI

> **PANDUAN BAGI PENGGUNA:**  
> Salin blok di bawah ini ke dalam AI Audit System (misal: Claude 3.5 Sonnet, GPT-4o, DeepSeek-R1, atau Llama 3) untuk menjalankan audit formal terhadap arsitektur pengembangan ini.

```text
=== START AI AUDITOR PROMPT ===

Anda bertindak sebagai PRINCIPAL SOFTWARE ARCHITECT, CHIEF PSYCHOMETRICIAN, dan SENIOR CYBERSECURITY AUDITOR.
Tugas Anda adalah melakukan audit mendalam, kritis, dan tanpa kompromi terhadap "DOKUMEN SPESIFIKASI ARSITEKTUR & PENGEMBANGAN FITUR TKS SMP & SMA EDUPATH" yang terlampir di atas.

Berikan evaluasi komprehensif yang mencakup 6 DIMENSI AUDIT berikut:

1. DIMENSI ARSITEKTUR PERANGKAT LUNAK & INTEGRITAS BASIS DATA:
   - Apakah skema DDL yang diusulkan benar-benar menjamin zero-downtime dan zero-breakage terhadap data SNBT eksis?
   - Apakah strategi indexing sudah optimal untuk query dengan throughput tinggi?
   - Apa potensi bottlenecks pada relasi tabel `questions`, `quiz_results`, dan `tks_diagnostic_sessions`?

2. DIMENSI PSIKOMETRI & KELAYAKAN ALGORITMA SKORING:
   - Evaluasi penggunaan IRT 2PL vs CTT untuk konteks siswa SMP dan SMA di Indonesia.
   - Apakah algoritma EAP / MLE yang diusulkan cukup stabil dalam menangani kondisi ekstrem (semua benar / semua salah)?
   - Apakah pemisahan taksonomi TKS vs TKA sudah tepat secara metodologi evaluasi pendidikan?

3. DIMENSI KEAMANAN, ANTI-CHEAT & COMPLIANCE:
   - Apakah arsitektur anti-cheating memadai untuk ujian online tanpa pengawas fisik?
   - Bagaimana ketahanan sistem terhadap replay attack pada endpoint `/api/v1/tks/assessment/submit-item`?
   - Tinjau kepatuhan terhadap UU PDP No. 27/2022 terkait pemrosesan data anak di bawah umur.

4. DIMENSI INTEGRASI AI & ROBUSTNESS:
   - Tinjau pembagian batas tanggung jawab antara Deterministic Scoring vs AI Interpretation. Apakah sudah kedap halusinasi?
   - Apa mitigasi terbaik jika inference latency LLM mengalami spike saat ribuan siswa menyelesaikan ujian secara bersamaan?

5. DIMENSI SKALABILITAS & PERFORMA OPERASIONAL:
   - Evaluasi kesiapan arsitektur melayani 50.000 concurrent students pada use case B2B Sekolah.
   - Di mana titik kegagalan tunggal (Single Point of Failure) pada arsitektur PHP Monolith ini dan bagaimana solusinya?

6. SCORECARD & DAFTAR REKOMENDASI (ACTIONABLE FIXES):
   - Berikan nilai skala 1 - 100 untuk setiap dimensi di atas.
   - Berikan keputusan akhir audit: [APPROVED] / [APPROVED WITH CONDITIONS] / [REJECTED].
   - Tuliskan daftar prioritas perbaikan (P0, P1, P2) yang wajib dieksekusi sebelum tahap implementasi kode.

=== END AI AUDITOR PROMPT ===
```

---
*Dokumen ini diterbitkan secara resmi sebagai blueprint teknis pengayaan platform EduPath V2.*
