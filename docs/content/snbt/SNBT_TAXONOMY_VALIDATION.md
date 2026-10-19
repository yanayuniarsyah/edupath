# SNBT Master Taxonomy: Validation

Dokumen ini memvalidasi *Master Taxonomy* SNBT berdasarkan *Structural*, *Academic*, dan *Assessment Usability* rule yang telah ditetapkan di Phase 2.

## 1. Structural Validation
- **Duplicate ID/Node**: `PASS`. Seluruh node yang dirancang dalam JSON file memiliki awalan spesifik (seperti `DOM-PU-IN` untuk Domain Penalaran Induktif di Penalaran Umum). Tidak ada konflik ID.
- **Orphan Node / Invalid Parent**: `PASS`. Setiap `Indicator` berinduk langsung pada `Competency_id`. Setiap `Competency` berada di dalam sebuah `Topic`. Tidak ada node yang menggantung.
- **Inconsistent Naming**: `PASS`. Telah disepakati untuk menggunakan prefix `SUB-`, `DOM-`, `TOP-`, `SUBTOP-`, `COM-`, dan `IND-`.

## 2. Academic Validation
- **Domain Overlap**: `PASS`. Domain "Pengetahuan Kuantitatif" (konsep matematis murni) dan "Penalaran Matematika" (aplikasi pemecahan masalah kontekstual) dipisahkan secara tegas agar tidak tumpang tindih.
- **Competency Scope**: `PASS`. Kompetensi ditulis dalam bentuk *verb* kognitif, misal: "Menarik kesimpulan", "Menentukan makna", "Menyelesaikan model".
- **Indicator Over-Specificity**: `PASS`. Indikator ditulis tanpa *answer key* atau *wording* spesifik soal. Misal: "Peserta dapat menyusun persamaan linear dua variabel berdasarkan stimulus naratif deskriptif tentang hubungan usia" (TIDAK ADA tulisan "Siswa dapat menjawab soal umur andi").

## 3. Assessment Usability Validation
- **Question Generation**: `PASS`. Satu `Indicator` terbukti mampu menghasilkan banyak spesifikasi soal dan puluhan bentuk soal nyata yang berbeda-beda nilainya selama masih memenuhi indikator yang ditetapkan.
- **Material Mapping**: `PASS`. Materi (teks, *summary*) dapat secara dinamis diikatkan (*bind*) langsung ke entitas `Topic` atau `Competency` tanpa harus peduli *parent/domain*-nya, mendukung konsep *reusable content chunks*.
