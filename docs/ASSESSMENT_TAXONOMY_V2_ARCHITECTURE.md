# PHASE 1 — AUDIT & DESAIN ARSITEKTUR ASSESSMENT UNIVERSAL EDUPATH

## 1. Executive Summary
Dokumen ini berisi hasil audit arsitektur sistem EduPath saat ini dan desain konseptual untuk **Universal Assessment Taxonomy V2**. Sistem saat ini terbukti sangat terikat pada struktur SNBT (Taxonomy, Database, API, Frontend). Arsitektur V2 dirancang untuk memisahkan jenjang pendidikan (Education Level), jenis asesmen (Assessment Type), dan metadata soal lainnya, agar sistem dapat mendukung TKA (SD, SMP, SMA, SMK) tanpa merusak ekosistem SNBT yang sudah berjalan. Berdasarkan audit, fase perancangan ini telah menghasilkan roadmap migrasi yang aman. Keputusan akhir untuk melanjutkan ke Phase 2 adalah **GO**.

## 2. Verified Current Architecture
- **Status:** VERIFIED
- Sistem saat ini menggunakan monolithic assessment model yang berasumsi bahwa setiap soal dan kuis adalah bagian dari persiapan UTBK/SNBT.
- **Tingkat Keterikatan (Coupling):** Tinggi. Kategori seperti "Penalaran Umum" dan "Pengetahuan Kuantitatif" di-hardcode di level UI (`EduData.js`, `AdminPanel.vue`) dan level Backend (`api/quiz.php`).

## 3. Current Assessment Flow
- **Status:** VERIFIED
- Alur saat ini: Siswa memilih `quiz_type` (LATIHAN, TRYOUT, ASESMEN).
- Jika TRYOUT, backend (`api/quiz.php`) langsung melakukan distribusi soal berdasarkan proporsi spesifik SNBT (misal: Penalaran Umum = 30 soal, Pengetahuan Kuantitatif = 25 soal, dst).
- Jika LATIHAN, mengambil acak berdasarkan `subtes`.

## 4. Current Taxonomy
- **Status:** VERIFIED
- Taksonomi yang eksis (hardcoded di `EduData.js` dan API):
    - **TPS**: Penalaran Umum, Pengetahuan Kuantitatif, Pemahaman Bacaan & Menulis, Pengetahuan & Pemahaman Umum.
    - **Literasi**: Bahasa Indonesia, Bahasa Inggris, Penalaran Matematika.
- Tidak ada konsep `Education Level` (jenjang pendidikan) atau `Assessment Type` (TKA vs SNBT) secara eksplisit di struktur taksonomi; semuanya diasumsikan UTBK.

## 5. Current Database Model
- **Status:** VERIFIED
- Tabel `questions` menggunakan kolom: `id`, `subtes`, `bab`, `difficulty`, `irt_score`, `question`, `options`, `correct`, `is_active`, `is_qc_passed`.
- Pembaruan skema (via `update_p0_schema.php`) menambahkan: `source_name`, `source_year`, `source_reference`, `domain`, `sub_materi`, `version`.
- **Kelemahan:** Tidak ada kolom `education_level` (SD/SMP/SMA/SMK) dan `assessment_type` (TKA/SNBT). `subtes` dan `bab` sering kali diisi dengan format SNBT.

## 6. Current API Model
- **Status:** VERIFIED
- `api/quiz.php` mengatur penyajian soal. Jika `quiz_type == 'tryout'`, distribusi soal di-hardcode berdasarkan kategori SNBT.
- Endpoints belum mendeteksi atau menerima parameter `education_level`.

## 7. Current Scoring Model
- **Status:** VERIFIED
- Skor kuis/latihan bergantung pada `irt_score` dari masing-masing soal (IRT disederhanakan).
- Tryout menggunakan model pembobotan dan distribusi proporsional per subtes SNBT. Belum ada scoring engine terpisah yang membedakan formula TKA vs SNBT.

## 8. Current Importer
- **Status:** VERIFIED
- `api/importer.php` memproses file CSV dengan kolom `sub_materi`, `classification`, `cognitive_demand`, `difficulty`, `question`, dll.
- Importer sudah cukup generik secara struktur kolom (memanfaatkan `sub_materi`), namun penggunaannya di frontend/backend terikat pada asumsi klasifikasi SNBT (LATIHAN, TRYOUT, ASESMEN).

## 9. Target Universal Taxonomy
- **Status:** PROPOSED
- Konsep pemisahan dimensi:
    - **Education Level:** SD, SMP, SMA, SMK, UMUM (untuk SNBT)
    - **Assessment Type:** TKA, SNBT, SPP
    - **Subject:** (Matematika, Biologi, Penalaran Umum, dll.)
    - **Domain:** (Aljabar, Geometri, dll.)
    - **Sub Materi:** (Topik spesifik)
    - **Competency:** (Standar kompetensi lulusan)
    - **Cognitive Demand:** C1 - C6
    - **Difficulty:** Easy, Medium, Hard, HOTS
    - **Question Format:** Pilihan Ganda, Majemuk, Esai Singkat (masa depan)

## 10. TKA SD Mapping
- **Status:** PROPOSED PRODUCT STRUCTURE
- **Mata Pelajaran:** Matematika, Bahasa Indonesia, IPA, IPS, PPKn.
- **Karakteristik:** Fokus pada literasi dasar dan numerasi dasar.
- **Struktur Assessment:** Ujian sumatif / asesmen formatif dengan jumlah soal yang lebih sedikit.

## 11. TKA SMP Mapping
- **Status:** PROPOSED PRODUCT STRUCTURE
- **Mata Pelajaran:** Matematika, Bahasa Indonesia, Bahasa Inggris, IPA, IPS, PPKn.
- **Karakteristik:** Pengenalan penalaran logis tingkat menengah, sains terpadu.

## 12. TKA SMA/MA Mapping
- **Status:** PROPOSED PRODUCT STRUCTURE
- **Mata Pelajaran Saintek:** Matematika Peminatan, Fisika, Kimia, Biologi.
- **Mata Pelajaran Soshum:** Ekonomi, Geografi, Sosiologi, Sejarah.
- **Mata Pelajaran Wajib:** Matematika Wajib, Bahasa Indonesia, Bahasa Inggris, PPKn.
- **Karakteristik:** Pendalaman analitis dan persiapan Ujian Sekolah.

## 13. TKA SMK Mapping
- **Status:** PROPOSED PRODUCT STRUCTURE
- **Mata Pelajaran:** Matematika Terapan, Bahasa Indonesia, Bahasa Inggris, dan Dasar Kejuruan (Vokasi).
- **Karakteristik:** Soal terapan (applied science & mathematics).

## 14. SNBT Mapping
- **Status:** VERIFIED (Current) -> PROPOSED (Mapped)
- **Education Level:** UMUM (atau Kelas 12 / Alumni)
- **Assessment Type:** SNBT
- **Subject:** Penalaran Umum, Pengetahuan Kuantitatif, dll. (Dipetakan dari `subtes` saat ini).
- Taksonomi lama (`subtes` = "Penalaran Umum") dipetakan secara mulus menjadi `assessment_type` = "SNBT", `subject` = "Penalaran Umum".

## 15. Database Impact
- **Status:** PROPOSED
- **Tabel `questions`:** Perlu penambahan kolom `education_level` (VARCHAR) dan `assessment_type` (VARCHAR).
- **Tabel `quiz_results` / `quiz_attempts`:** Perlu mencatat `assessment_type` dan `education_level` agar riwayat progres tidak tumpang tindih.
- **Tabel `progress`:** Kolom `subtes` perlu didampingi oleh context `assessment_type`.
- **Migration Strategy:** Tambahkan kolom baru dengan nilai default (misal: `education_level` = 'SMA/UMUM', `assessment_type` = 'SNBT' untuk semua record lama).

## 16. API Impact
- **Status:** PROPOSED
- **`api/quiz.php` (Start Quiz):** Payload perlu menerima `education_level` dan `assessment_type`. Hardcode distribusi SNBT (`$tryout_distribution`) harus dibungkus dalam kondisi `if ($assessment_type === 'SNBT')`.
- Tidak ada _breaking change_ jika parameter baru bersifat opsional dan fallback ke 'SNBT'.

## 17. Frontend Impact
- **Status:** PROPOSED
- Komponen `AdminPanel.vue`: Dropdown subtes harus dinamis dan difilter berdasarkan pilihan `Assessment Type` dan `Education Level` (menghilangkan hardcode).
- Komponen `TryOutCBT.vue`: Tampilan timer, UI peta soal, dan navigasi tetap bisa digunakan kembali secara universal tanpa memandang TKA atau SNBT.

## 18. Scoring Impact
- **Status:** PROPOSED
- Sistem scoring perlu diabstraksi.
- TKA mungkin menggunakan _True/False Raw Score_ (Benar = +1, Salah = 0) atau persentase konvensional.
- SNBT menggunakan model IRT atau pembobotan khusus. Backend harus menggunakan Factory Pattern (`ScoringEngineFactory`) untuk memilih algoritma berdasarkan `assessment_type`.

## 19. Importer Impact
- **Status:** PROPOSED
- `api/importer.php` harus diperbarui agar menerima kolom `education_level` dan `assessment_type` dari CSV.
- File CSV template perlu memfasilitasi variasi ini, namun _processing engine_-nya tetap satu (tidak perlu membuat importer terpisah per jenjang).

## 20. Product/Entitlement Impact
- **Status:** PROPOSED
- Tabel `plans` saat ini berbasis pada `features` JSON.
- Akses TKA vs SNBT dapat dikelola melalui JSON `features` ini (misal: `{"access": ["snbt", "tka_smp"]}`). Entitlement check di API cukup memvalidasi field JSON ini tanpa mengubah schema tabel.

## 21. Migration Strategy
- **Status:** PROPOSED
1. **Schema Update:** Tambahkan `education_level` dan `assessment_type` ke `questions` dan `quiz_attempts`. Set default ke "UMUM" dan "SNBT".
2. **Data Backfill:** Update semua data soal yang ada saat ini agar memetakan nilai default tersebut.
3. **API Backward Compatibility:** Endpoint `quiz.php` menggunakan default 'SNBT' jika frontend belum mengirimkan tipe asesmen.
4. **Frontend Refactor:** Hapus hardcode di Vue. Ganti dengan config dinamis dari API/EduData yang mendukung struktur tree (Level -> Tipe -> Subject).

## 22. Risk Register
| Risiko | Severity | Evidence | Dampak | Mitigasi |
| ------ | -------- | -------- | ------ | -------- |
| Hardcoded SNBT logic di Frontend | High | `EduData.js`, `AdminPanel.vue` | TKA tidak bisa muncul di menu / bercampur dengan SNBT | Pisahkan state configuration, buat mapping dictionary dinamis. |
| Hardcoded Tryout Distribution di API | High | `api/quiz.php` (line 97) | Tryout TKA akan error karena mencari subject "Penalaran Umum" | Bungkus logic proporsi di dalam konfigurasi berbasis `assessment_type`. |
| Importer Compatibility | Medium | `api/importer.php` | Soal TKA akan masuk sebagai soal SNBT secara default | Tambahkan kolom `education_level` dan `assessment_type` pada template & staging table. |
| Missing Indexes | Low | `database.sql` | Performa pencarian soal menurun jika row bertambah | Tambahkan composite index `(assessment_type, education_level, subtes)`. |

## 23. Acceptance Criteria Phase 2
- [ ] Struktur DB diperbarui dengan Migration script (tanpa merusak data lama).
- [ ] Backend (API) dipisahkan logic routing-nya menggunakan Factory Pattern untuk konfigurasi kuis & scoring.
- [ ] Frontend di-refactor agar Taxonomy bersifat data-driven dari server.
- [ ] Importer dimodifikasi untuk mendukung Universal Taxonomy.

---

# PHASE 2 READINESS

**GO**

**Alasan:**
1. **Evidence Terkumpul:** Sistem yang eksis sepenuhnya dimengerti. Titik-titik _coupling_ (hardcoded SNBT) telah ditemukan dengan jelas di frontend (`EduData.js`, `AdminPanel.vue`) dan backend (`api/quiz.php`).
2. **Tidak Ada Blocker:** Struktur database eksis (`questions`) cukup fleksibel dan hanya membutuhkan penambahan kolom metadata konseptual tanpa perlu merombak tabel secara destruktif.
3. **Migration Path Jelas:** Backward compatibility terjamin karena kita bisa menetapkan nilai default "SNBT" untuk semua _record_ dan _request_ lama. Sistem baru dapat dibangun di atas fondasi yang sudah ada tanpa perlu membuat sistem paralel.
