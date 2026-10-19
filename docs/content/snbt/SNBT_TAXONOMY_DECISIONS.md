# SNBT Master Taxonomy: Key Decisions

Dokumen ini mencatat keputusan-keputusan strategis terkait desain taksonomi yang membutuhkan kesepakatan (judgement) internal dari EduPath.

## 1. Pemisahan Competency dan Topic
- **Keputusan**: `Topic` murni merepresentasikan subjek konten (misal: "Sistem Persamaan"), sedangkan `Competency` merupakan kata kerja kognitif yang merepresentasikan *skill* (misal: "Menyelesaikan model matematika dari masalah kontekstual").
- **Alasan**: Mencegah redundansi. Satu topik yang sama (misal: Persamaan) dapat melahirkan beberapa kompetensi (misal: mendefinisikan, menyelesaikan, mengevaluasi).

## 2. Pengabaian "Subtopic" pada Beberapa Domain
- **Keputusan**: Tidak semua Domain/Topic harus memiliki level `Subtopic`. Pada "Pemahaman Bacaan dan Menulis" (PBM), beberapa topik seperti "Ejaan" bisa langsung menunjuk ke `Competency` tanpa memerlukan `Subtopic`.
- **Alasan**: Menghindari pembuatan node *dummy* atau hierarki paksaan yang membuat *learning tree* menjadi tidak seimbang secara semantik.

## 3. Desain Indicator sebagai Question Specification Blueprint
- **Keputusan**: `Indicator` dirancang agar tidak *bias* terhadap satu soal saja, namun spesifik untuk merumuskan satu format logika soal.
- **Contoh**: Alih-alih "Siswa dapat menjawab soal umur Andi", indikator ditulis sebagai "Peserta dapat menyusun persamaan linear dua variabel berdasarkan stimulus naratif deskriptif tentang hubungan usia."
- **Alasan**: Hal ini memungkinkan *Content Generator* atau guru untuk memproduksi ratusan soal variatif di bawah satu id indikator yang sama.

## 4. Format Identifier (ID) Node
- **Keputusan**: ID taksonomi (dalam JSON) menggunakan prefix alfanumerik yang melambangkan lokasinya, misal `SNBT-TPS-PU-D1-T1-C1-I1`.
- **Alasan**: *Human readable* bagi *content author*, namun tetap stabil. Namun di production database, node ini tetap akan memiliki primary key UUID. (JSON di Phase 2 ini menggunakan human-readable ID untuk proses review).
