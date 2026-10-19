# EduPath Content Architecture: Current State

Berdasarkan audit schema di repositori (khususnya `database.sql` dan file migrasi seperti `phase7_taxonomy_structure.sql`), arsitektur konten yang berjalan saat ini adalah sebagai berikut:

## 1. Questions Model (Existing)
Tabel `questions` menyimpan bank soal SNBT. Struktur taksonominya di-embed langsung ke dalam tabel (denormalized).
Kolom yang relevan:
- `exam` (e.g., 'SNBT')
- `test_component` (e.g., 'TPS')
- `subtest` (e.g., 'Penalaran Umum')
- `topic`
- `subtopic`
- `skill`
- `indicator`

**Kekurangan saat ini**:
Tingkatan hierarki langsung ditempel sebagai text/varchar (string) di setiap baris soal, tidak ada tabel relasional khusus taksonomi yang mengikat (seperti tabel `topics`, `subtopics`, dll). Tingkat `Domain` dan `Competency` tidak secara eksplisit diwakili kecuali secara implisit pada `skill` atau `subtopic`.

## 2. Materials Model (Existing)
Tabel `materials` menyimpan materi belajar. Struktur taksonominya identik dengan `questions` (juga denormalized).
Kolom yang relevan:
- `exam`
- `test_component`
- `subtest`
- `topic`
- `subtopic`
- `skill`
- `indicator`

**Kekurangan saat ini**:
Sama dengan `questions`, materi tidak dikaitkan secara ID relasional terhadap pohon taksonomi, melainkan sekadar _string matching_.

## 3. SPP Model (Existing)
SPP disimpan dalam struktur independen yang terisolasi dari taksonomi SNBT biasa.
Tabel `spp_diagnostic_questions`:
- `subject`
- `category`
- `skill`
- `difficulty`

Ini sudah sesuai dengan prinsip SPP yang harus terpisah dari konten akademis murni, yang berfokus pada "Potential, Interest, Learning, Growth".

## 4. Assessment Canonical Schema (Existing Phase 2C)
Terdapat struktur migrasi `phase2c_canonical_schema.sql` untuk Assessment:
- `assessments`
- `assessment_versions`
- `questions_canonical`
- `question_revisions`
- `assessment_questions`
- `attempts`
- `attempt_responses`
- `attempt_results`

Ini memisahkan engine CBT dari content, yang mana merupakan hal bagus. Namun, skema ini belum menampung _Taxonomy/Hierarchy_ relasional (Program -> Domain -> Competency -> dll).
