# Content Architecture: Validation & Status

## VERIFIED
- **Database Schema**: Skema saat ini memisahkan `materials` dan `questions` namun menggunakan teknik denormalisasi untuk taksonomi (menggunakan kolom `topic`, `subtopic`, dll, berupa teks biasa).
- **SPP Independence**: Fitur SPP terbukti menggunakan tabel terpisah (`spp_diagnostic_questions`) yang tidak mengganggu bank soal SNBT. Desain saat ini hanya berfokus pada asesmen profil diagnostik (tanpa label psikologis pada logic utama).
- **Assessment Engine**: Sudah ada draf `phase2c_canonical_schema.sql` yang mengatur Assessment terpisah dari Content, yang membuktikan sistem sudah dalam transisi ke arsitektur modular CBT.

## PROPOSED (Rekomendasi Arsitektur Selanjutnya)
- **Taxonomy Normalization**: Sangat disarankan untuk memisahkan tabel taksonomi master (misal tabel `taxonomy_nodes` atau kumpulan tabel `programs`, `assessments`, `domains`, dll.) agar tidak terjadi salah ketik (typo) dan grouping data yang tidak valid.
- **Relational Integrity**: `materials` dan `questions` sebaiknya hanya menyimpan `competency_id` atau `indicator_id`, sementara parent hierarchy (Topic, Domain, dll) di-lookup dari tabel taksonomi master.
- **Material Chunks**: Schema `materials` saat ini perlu di-*alter* atau diganti agar bisa memisahkan `learning_objective`, `worked_example`, dan `practice`.

## NOT VALIDATED
- **Learning Analytics / Recommendation Engine**: Belum ada verifikasi sejauh mana SPP saat ini diolah bersama Academic Performance karena modul Recommendation Engine bukan prioritas untuk di-*deploy* di Phase 1.
- **Existing Content Migration**: Belum dipastikan apakah puluhan/ratusan konten (materi/soal) yang sudah ada di database *production* bisa secara otomatis di-mapping ke struktur `Domain` baru tanpa proses manual (tagging ulang).

## UNRESOLVED QUESTIONS
- Apakah kita akan membuat tabel tersendiri untuk masing-masing level (misal: `table_domains`, `table_topics`), atau menggunakan *Single Table Inheritance* (contoh: `taxonomy_terms` dengan kolom `level` dan `parent_id`) untuk fleksibilitas jika suatu saat nambah level taksonomi baru?
