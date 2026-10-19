# Content Architecture: Gap Analysis

Perbandingan antara struktur Schema Content yang saat ini ada di DB (CURRENT) dan struktur arsitektur yang diharapkan (TARGET).

## 1. Hierarchy Representation
- **CURRENT**: Struktur hierarki (seperti subtest, bab/topic) disimpan langsung sebagai teks dalam tabel `questions` dan `materials` secara denormalized. 
- **TARGET**: Membutuhkan model relasional (atau referensial ID) berantai: Program -> Assessment -> Subtest -> Domain -> Topic -> Subtopic -> Competency -> Indicator.
- **GAP**: Saat ini tidak ada tabel Master Taxonomy (`programs`, `domains`, `topics`, dll.). Tidak ada _Referential Integrity_ yang memastikan sebuah soal terkait dengan Competency yang baku. Filter atau _Grouping_ hanya bisa mengandalkan `GROUP BY string`.

## 2. Taxonomy Fields on Question Model
- **EXISTING**: `exam`, `test_component`, `subtest`, `topic`, `subtopic`, `skill`, `indicator`.
- **PROPOSED (New Fields)**: `program`, `assessment`, `domain`, `competency`.
- **GAP**: 
  - `exam` saat ini mewakili `program`.
  - `test_component` mewakili `assessment`.
  - `domain` **sepenuhnya tidak ada** di schema saat ini.
  - `skill` saat ini berfungsi mirip `competency`, tapi butuh distandardisasi penamaannya.
  - `stimulus_type` dan `version` belum ada di struktur lama.

## 3. Taxonomy Fields on Material Model
- **EXISTING**: `exam`, `test_component`, `subtest`, `topic`, `subtopic`, `skill`, `indicator`, `title`, `content`.
- **PROPOSED (New Fields)**: `program_id`, `topic_id`, `competency_id`, `learning_objective`, `prerequisite`, `worked_example`, `practice`, `summary`.
- **GAP**:
  - Saat ini Material hanya memiliki 1 kolom `content` bertipe `LONGTEXT`.
  - Target mewajibkan pembagian struktur konten menjadi: `learning_objective`, `prerequisite`, `content` (inti), `worked_example`, `practice`, dan `summary`. Schema lama tidak mendukung partisi instruksional ini.

## 4. Assessment Engine vs Content
- **CURRENT**: Engine CBT di `phase2c_canonical_schema.sql` (tabel `assessments`, `questions_canonical`, `assessment_questions`) sudah mulai memisahkan soal dari pelaksanaan ujian.
- **TARGET**: Tetap mempertahankan pemisahan ini, tetapi tabel `questions_canonical` harus memiliki metadata taksonomi yang lengkap.
- **GAP**: `questions_canonical` saat ini sangat kosong (hanya `id` dan `type`). Meta taxonomy masih ada di tabel `questions` lama yang monolitik.

## 5. SPP Isolation
- **CURRENT**: Tabel `spp_diagnostic_questions` berdiri sendiri, tidak bercampur dengan tabel taksonomi akademis SNBT.
- **TARGET**: Mempertahankan SPP pada track-nya sendiri dengan dimensi Potential, Interest, Learning, Growth.
- **GAP**: Secara model, tidak ada GAP (Sudah sesuai target). Tinggal memastikan engine rekomendasi nantinya terpisah dari engine scoring ini.
