# 📚 Dokumen Komprehensif SPP (EduPath.co.id)

> **Tujuan**: Menyajikan metodologi **Sistem Pembelajaran & Penilaian (SPP)** yang dipakai di EduPath, lengkap dengan **komponen**, **materi**, **bank soal**, serta **pemetaan empat dimensi** — Potential, Interest, Learning, Growth. Dokumen ini dirancang untuk dapat diproses langsung oleh AI (mis. fine‑tuning, analisis reliabilitas).

---

## 1️⃣ Metodologi SPP di EduPath

| Tahap | Deskripsi | Keluaran Utama |
|-------|-----------|----------------|
| **1️⃣ Analisis Kebutuhan** | Mapping kurikulum nasional (K13) → kompetensi SNMPTN. Identifikasi topik, tingkat kesulitan, bobot nilai. | `requirements.json` (daftar kompetensi & bobot) |
| **2️⃣ Desain Materi** | Penyusunan modul pembelajaran (teori + contoh) berdasarkan **Blueprint** SNMPTN. Setiap modul memakai *learning‑by‑doing* dan *micro‑learning*. |
| **3️⃣ Penyusunan Bank Soal** | Pembuatan soal dengan **struktur metadata** (kompetensi, level, tipe, bobot). Soal diacak secara adaptif untuk tiap sesi. |
| **4️⃣ Validasi & Reliability** | • **Content Validity** – pakar menilai kesesuaian tiap soal. <br>• **Statistik** – Cronbach’s α, KR‑20, Item‑Total Correlation. |
| **5️⃣ Implementasi Platform** | Integrasi ke LMS EduPath (API, UI). Soal disajikan dalam format **JSON**/CSV, materi dalam **Markdown**. |
| **6️⃣ Monitoring & Feedback** | Analisis performa (item‑analysis), pembaruan materi tiap siklus PTN. |

> **Catatan**: Metodologi ini bersifat iteratif; tiap siklus masuk PTN (biasanya tahunan) memicu **re‑validasi**.

---

## 2️⃣ Komponen Utama SPP

1. **Blueprint Kompetensi** – JSON yang memetakan *subject → kompetensi → sub‑kompetensi* (berdasarkan dokumen SNMPTN).
2. **Modul Materi** – Markdown (`.md`) dengan struktur:
   ```markdown
   # Topik
   ## Sub‑topik
   - Penjelasan teori
   - Contoh soal terapan
   - Video (link) / animasi (GIF)
   ```
3. **Bank Soal** – File `soal_<tahun>.json` (atau CSV) dengan skema **R1** (lihat bagian 3).
4. **Engine Validasi** – Skrip Python/JS yang menghitung reliabilitas dan melakukan *item analysis*.
5. **API LMS** – Endpoint `GET /materi/:id`, `GET /soal/:id`, `POST /jawaban`.
6. **Dashboard Analitik** – Visualisasi tingkat kesulitan, distribusi nilai, dan *item‑fit*.

---

## 3️⃣ Struktur Data Materi & Soal (Berbasis SNMPTN Resmi)

### 3.1 Blueprint Materi (`materi_schema.json`)
```json
{
  "subject": "Matematika",
  "year": 2024,
  "competencies": [
    {
      "code": "MTH001",
      "description": "Aljabar – Persamaan Kuadrat",
      "weight": 0.12,
      "modules": [
        {
          "id": "MTH001-01",
          "title": "Konsep Dasar",
          "contentPath": "materi/matematika/aljabar/kuadrat/konsep.md"
        },
        {
          "id": "MTH001-02",
          "title": "Contoh Soal",
          "contentPath": "materi/matematika/aljabar/kuadrat/contoh_soal.md"
        }
      ]
    }
  ]
}
```

### 3.2 Bank Soal (`soal_schema.json`)
```json
{
  "subject": "Matematika",
  "year": 2024,
  "questions": [
    {
      "id": "MTH-Q-0001",
      "competencyCode": "MTH001",
      "type": "multiple_choice",
      "difficulty": "medium",
      "stem": "Jika x²‑5x+6=0, maka nilai x yang benar adalah …",
      "options": {"A":"2","B":"3","C":"4","D":"6"},
      "answer": "A",
      "explanation": "(x‑2)(x‑3)=0 → x=2 atau 3",
      "weight": 1,
      "metadata": {"source":"SNMPTN 2024","validator":"pakar_matematika","reliabilityScore":null}
    }
    // ... lebih banyak soal
  ]
}
```
> **Catatan**: `reliabilityScore` di‑isi setelah proses validasi.

### 3.3 CSV Versi Ringkas (untuk AI‑training)
```
id,competency_code,type,difficulty,stem,option_A,option_B,option_C,option_D,answer,weight,source
MTH-Q-0001,MTH001,multiple_choice,medium,"Jika x²‑5x+6=0, maka nilai x yang benar adalah …",2,3,4,6,A,1,SNMPTN 2024
```

---

## 4️⃣ Pemetaan Empat Dimensi (Potential, Interest, Learning, Growth)

### 4.1 Potential – Pola berpikir, logika, & kekuatan naluriah
| Komponen | Indikator | Cara mengukur | Format data | Kegunaan dalam SPP |
|---|---|---|---|---|
| Logika formal | Skor logika | Tes logika (multiple‑choice) | `logicScore` (0‑100) | Menentukan baseline kemampuan berpikir kritis. |
| Pemecahan masalah | Skor problem‑solving | Soal case‑study / mini‑project | `problemScore` (numeric) | Mengklasifikasikan level *potential* untuk penyesuaian jalur belajar. |
| Kekuatan naluriah | Skor intuisi | Tes “quick‑choice” (≤ 3 detik) | `intuitionScore` (numeric) | Memberi bobot pada soal *instinctive* dalam bank soal. |
*Aggregated Potential* = rata‑rata z‑score ketiga indikator → kelas **Low / Mid / High**.

### 4.2 Interest – Bidang studi & karir yang memicu antusiasme
| Komponen | Indikator | Cara mengukur | Format data | Kegunaan dalam SPP |
|---|---|---|---|---|
| Preferensi topik | Rating topik | Kuesioner likert + klik contoh materi | `topicRatings` (1‑5) | Menyesuaikan *track* belajar (Matematika, Fisika, dsb.). |
| Motivasi karir | Score karir | Inventori RIASEC atau serupa | `careerScore` (0‑100) | Menghubungkan modul *Interest* dengan rekomendasi PTN/kedinasan/TKA. |
| Engagement historis | Aktivitas LMS | Log aktivitas (durasi, frekuensi) | `engagementMetric` (numeric) | Mengukur minat secara dinamis; memperbarui rekomendasi konten. |
*Aggregated Interest* = 0.4 Preferensi + 0.3 Motivasi + 0.3 Engagement.

### 4.3 Learning – Gaya belajar (konsep, visual, studi kasus)
| Komponen | Indikator | Cara mengukur | Format data | Kegunaan dalam SPP |
|---|---|---|---|---|
| Visual | Media visual | Rating media visual | `visualScore` (numeric) | Menyesuaikan konten visual untuk gaya belajar. |
| Konseptual | Teori | Jawaban soal *concept‑only* | `conceptScore` (numeric) | Menyajikan materi konseptual lebih mendalam. |
| Studi kasus | Problem‑based | Pilihan modul case + waktu pengerjaan | `caseScore` (numeric) | Menyajikan materi berbasis studi kasus. |
*Learning profile* = vektor 3‑dimensi → *similarity* dengan materi untuk adaptasi konten.

### 4.4 Growth – Konsistensi, resiliensi (grit), determinasi
| Komponen | Indikator | Cara mengukur | Format data | Kegunaan dalam SPP |
|---|---|---|---|---|
| Konsistensi | Login streak | Streak login harian, frekuensi sesi | `streakDays` (integer) | Memantau kehadiran siswa. |
| Resiliensi (grit) | Grit score | Persentase penyelesaian soal tingkat tinggi setelah kegagalan | `gritScore` (numeric) | Menilai ketahanan belajar. |
| Determinasi | Determinasi belajar | Target belajar vs realisasi (menit) | `determinasiScore` (numeric) | Mengukur komitmen terhadap target. |
*Growth Index* = 0.3 Konsistensi + 0.4 Resiliensi + 0.3 Determinisasi → kelas **Low / Mid / High**.

---

## 5️⃣ Proses Validasi & Reliability (Contoh Skrip)
```python
import pandas as pd
from scipy.stats import cronbach_alpha

# Load soal CSV
soal = pd.read_csv('soal_2024.csv')
# Load jawaban siswa (matrix N x Q)
responses = pd.read_csv('responses_2024.csv')

alpha_by_comp = {}
for comp in soal['competency_code'].unique():
    q_ids = soal[soal['competency_code'] == comp]['id']
    mat = responses[q_ids]
    alpha, _ = cronbach_alpha(mat)
    alpha_by_comp[comp] = alpha

print(alpha_by_comp)
```
> **Interpretasi**: α ≥ 0.80 → reliabilitas tinggi. Nilai < 0.70 → perlu revisi soal.

---

## 6️⃣ Contoh Alur Penggunaan di Platform EduPath
1. **User** mengisi onboarding questionnaire (Potential + Interest + Learning + Growth). 
2. Sistem **menyimpan** data ke `user_profile.json`. 
3. Engine **meng‑match** profil dengan *blueprint* materi & bank soal. 
4. Dashboard menampilkan empat gauge (Potential, Interest, Learning, Growth). 
5. Selama belajar, nilai‑nilai profil **terupdate** secara otomatis. 
6. Jika ada gauge turun < 30 %, sistem memberi rekomendasi intervensi (mentor, micro‑course, motivasi reminder).

---

## 7️⃣ Ringkasan Kebutuhan Data untuk AI
| Data | Format | Ukuran (perkiraan) | Kegunaan AI |
|------|--------|-------------------|-------------|
| Blueprint materi | JSON | 50 KB | Generasi teks, rekomendasi adaptif |
| Bank soal (JSON) | JSON | 2 MB (≈ 10 k soal) | Fine‑tuning model soal, analisis item |
| Jawaban siswa | CSV | 5 MB (≈ 10 k siswa × 200 soal) | Model prediksi kesulitan, clustering belajar |
| Profil pengguna | JSON | 10 KB per user | Personalization engine |
| Metadata validasi | JSON | 200 KB | Evaluasi reliability, penyesuaian bobot |

---

## 8️⃣ Checklist Implementasi
- [ ] **Sinkronisasi** blueprint dengan dokumen SNMPTN terbaru (tahun 2024). 
- [ ] **Generate** modul materi Markdown sesuai standar EduPath. 
- [ ] **Populate** bank soal menggunakan skema JSON di atas. 
- [ ] **Implement** onboarding questionnaire (Potential‑Interest‑Learning‑Growth). 
- [ ] **Store** profil pengguna ke `user_profile.json`. 
- [ ] **Update** engine rekomendasi untuk memakai empat dimensi. 
- [ ] **Run** skrip validasi reliability; update `reliabilityScore`. 
- [ ] **Deploy** API endpoint `/materi`, `/soal`, `/profile`. 
- [ ] **Integrasi** UI (micro‑animations, dark mode) untuk pengalaman premium. 
- [ ] **Uji** dengan grup pilot (≥ 100 siswa) → analisis statistik. 

---

*Dokumen ini siap dipakai oleh tim konten, tim data, dan tim AI EduPath.*
