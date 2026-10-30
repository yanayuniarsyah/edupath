# EDUPATH UNIFIED PRODUCT ARCHITECTURE
## Product Requirements Document & Implementation Specification

**Version:** 1.1  
**Status:** Baseline keputusan arsitektur (Ratified)  
**Platform:** Existing EduPath  
**Scope:** Full Online, Full Offline, Hybrid  
**Academic Programs:** TKA SD, TKA SMP, TKA SMA, SNPMB/UTBK, SNPMB Alumni  

---

## A. PRODUCT VISION

EduPath adalah satu platform pembelajaran yang mendukung berbagai cara penyampaian layanan pendidikan:

1. Full Online.
2. Full Offline.
3. Hybrid.

Ketiga model menggunakan identitas pengguna, academic engine, bank soal, payment engine, entitlement, dan histori akademik yang sama.

Pengembangan dilakukan melalui integrasi bertahap terhadap sistem existing, bukan pembangunan ulang aplikasi.

### Prinsip arsitektur

- One Platform.
- One Identity.
- One Shared Product Catalog.
- One Academic Engine.
- One Payment System.
- One Entitlement Service.
- Separate Class Enrollment Lifecycle.
- Unified Student Progress.
- Multi-Tenant and Partner-Aware Operations.

### Non-goals

- Membuat aplikasi terpisah untuk online, offline, dan hybrid.
- Membuat bank soal baru khusus kelas offline.
- Mengganti autentikasi, payment, entitlement, atau CBT existing.
- Membuat duplikasi data siswa.
- Melakukan migrasi database sebelum audit schema aktual.

---

## B. ARCHITECTURE DECISIONS — RATIFIED

### B1. Shared Product Catalog

Sistem menggunakan satu master katalog produk pusat untuk seluruh tenant EduPath.

Master katalog mengatur:

- Academic program.
- Product package.
- Delivery mode.
- Package components.
- Feature entitlements.
- Harga dasar.
- Durasi.
- Status publikasi.
- Aturan upgrade.
- Versi produk.

Tenant/cabang menggunakan produk yang tersedia pada katalog pusat.

Harga dasar dan definisi paket tidak boleh berubah secara independen di setiap tenant.

Jika suatu saat diperlukan harga khusus tenant, mekanisme override harus:
- Dapat diaktifkan secara eksplisit.
- Memiliki batas kewenangan.
- Memiliki audit trail.
- Tidak mengubah harga dasar katalog pusat.
- Memiliki masa berlaku dan alasan perubahan.

### B2. Full-Price Upgrade

Upgrade paket dilakukan dengan membuat pembelian baru pada harga penuh paket tujuan.

Contoh:
- Digital Basic: Rp300.000.
- Hybrid: Rp900.000.
- Siswa membeli Digital Basic, lalu ingin upgrade ke Hybrid.
- Harga transaksi upgrade: Rp900.000.

Tidak ada pengurangan berdasarkan sisa masa aktif Digital Basic.

Aturan:
1. Upgrade menghasilkan order baru.
2. Order menggunakan payment engine existing.
3. Paket tujuan diberikan setelah pembayaran terverifikasi.
4. Paket lama tidak otomatis direfund.
5. Entitlement paket lama tetap mengikuti masa berlakunya.
6. Entitlement paket baru memiliki periode sendiri.
7. Tidak boleh ada duplikasi entitlement akibat webhook.
8. Riwayat pembelian dan upgrade harus dapat diaudit.

Upgrade tidak boleh otomatis memperpanjang atau menghapus hak lama tanpa kebijakan produk yang eksplisit.

Jika siswa sudah mempunyai entitlement yang sama dari paket lama dan paket baru, effective access dihitung dari seluruh grant yang masih valid sesuai aturan entitlement existing.

### B3. Partner Model — Both

EduPath mendukung dua model kerja sama.

**Model 1 — Partner as Tenant**

Mitra beroperasi sebagai tenant tersendiri.

Karakteristik:
- Memiliki tenant identity.
- Memiliki data operasional dalam tenant scope.
- Menggunakan katalog pusat.
- Memiliki admin operasional sendiri sesuai izin.
- Tidak memiliki akses ke tenant lain.
- Mengikuti aturan settlement dan revenue yang disepakati.

**Model 2 — Partner as Scoped Operator**

Mitra menjadi operator yang ditugaskan oleh tenant EduPath atau cabang.

Karakteristik:
- Tidak otomatis menjadi tenant baru.
- Memiliki assignment terhadap lokasi, kelas, cohort, atau sesi tertentu.
- Akses hanya untuk resource yang ditugaskan.
- Tidak dapat mengubah master katalog.
- Tidak dapat melihat keuangan global.
- Assignment dapat dicabut tanpa menghapus data historis.

Setiap partnership harus mencatat model operasional yang digunakan.

Satu kontrak atau assignment tidak boleh memberikan hak akses ambigu antara kedua model.

### B4. Configurable Cohort Renewal

Setiap program/offering mempunyai kebijakan renewal:

`new_enrollment` atau `extend_existing`.

**Mode 1 — new_enrollment**

- Membuat enrollment baru.
- Dapat memilih cohort baru.
- Memiliki periode dan histori sendiri.
- Tidak menghapus enrollment lama.

**Mode 2 — extend_existing**

- Memperpanjang enrollment yang masih memenuhi syarat.
- Menyimpan riwayat perpanjangan.
- Mempertahankan relasi cohort sesuai kebijakan program.
- Tidak boleh menimpa histori periode sebelumnya.

Konfigurasi renewal harus menentukan:
- Mode renewal.
- Eligibility.
- Batas waktu renewal.
- Harga renewal.
- Durasi tambahan.
- Apakah cohort lama dapat dipertahankan.
- Apakah perubahan cohort diperbolehkan.
- Aturan kapasitas dan jadwal.

Default jika konfigurasi belum tersedia: renewal tidak otomatis dilakukan. Admin harus menggunakan proses enrollment baru yang disetujui.

---

## C. PRODUCT CATALOG

### C1. Academic Programs

Program akademik:

- TKA SD.
- TKA SMP.
- TKA SMA.
- SNPMB/UTBK.
- SNPMB Alumni.

SNPMB Alumni menggunakan academic engine SNPMB yang sama, dengan segmentasi, learning path, jadwal, intensitas, dan mentoring yang dapat berbeda.

TKA SMA mendukung konfigurasi mata pelajaran berdasarkan ketentuan tahun ujian. Konfigurasi harus versioned.

### C2. Delivery Modes

- `online`
- `offline`
- `hybrid`

Delivery mode adalah karakteristik paket/penawaran layanan, bukan academic program baru.

### C3. Package Components

Setiap paket dapat memiliki komponen:

- Digital materials.
- Video/content access.
- Practice.
- Quiz.
- CBT/tryout.
- Analytics.
- Class enrollment.
- Instructor sessions.
- Attendance.
- Mentoring.
- Progress reports.

Komponen harus dikaitkan dengan entitlement key atau layanan operasional yang relevan.

### C4. Product Package Fields

Konsep field:
- product_id.
- program_id.
- package_code.
- package_name.
- delivery_mode.
- description.
- base_price.
- currency.
- duration_value.
- duration_unit.
- status.
- version.
- effective_from.
- effective_until.

Field ini merupakan rancangan konseptual. Audit struktur `plans`, `features`, dan tabel commerce existing sebelum menentukan migration.

### C5. Package Versioning

Perubahan harga, durasi, dan komposisi fitur tidak boleh mengubah histori transaksi lama.

Order harus merekam snapshot produk yang dibeli, termasuk:
- Package version.
- Harga transaksi.
- Komponen paket.
- Durasi.
- Ketentuan yang berlaku saat pembelian.

Produk yang sudah dibeli tidak boleh berubah secara retroaktif hanya karena katalog pusat diperbarui.

---

## D. COMMERCE, PAYMENT & ENTITLEMENT

### D1. Existing Services

Pertahankan payment dan entitlement existing.

Dokumen arsitektur menyebut:
- `api/payment.php`.
- `EntitlementService.php`.
- `RevenueSplitEngine.php`.
- `FinancialLedger.php`.
- Midtrans Snap dan webhook.

Implementasi aktual wajib diverifikasi.

### D2. Purchase Lifecycle

1. Student memilih paket.
2. Backend mengambil harga dari katalog pusat.
3. Backend membuat order dan snapshot paket.
4. Payment engine membuat transaksi.
5. Midtrans mengirim notifikasi.
6. Backend memverifikasi notifikasi dan status pembayaran.
7. Backend mengaktifkan entitlement.
8. Jika paket memiliki komponen offline, backend memproses enrollment.
9. Backend mengembalikan status pembelian kepada student.

Frontend tidak boleh menentukan harga final atau mengaktifkan entitlement sendiri.

### D3. Upgrade Lifecycle

1. Student memilih paket tujuan.
2. Backend memvalidasi eligibility upgrade.
3. Backend mengambil harga penuh paket tujuan.
4. Backend membuat order baru.
5. Student membayar harga penuh.
6. Webhook memverifikasi settlement.
7. Paket baru diaktifkan.
8. Paket lama tetap tercatat dan mengikuti masa berlakunya.
9. Semua perubahan dicatat dalam audit trail.

Tidak ada prorata dan tidak ada pengurangan nilai paket lama.

### D4. Payment Failure

- Pending: belum ada entitlement baru.
- Expired: order tidak mengaktifkan hak baru.
- Failed: tidak ada entitlement baru.
- Settlement: proses aktivasi sesuai paket.
- Duplicate webhook: tidak membuat aktivasi ganda.

### D5. Offline Seat Confirmation

Entitlement digital dan class enrollment merupakan lifecycle berbeda.

Kursi kelas hanya dikonfirmasi setelah:
- Pembayaran terverifikasi.
- Offering masih menerima pendaftaran.
- Kapasitas seluruh sesi wajib masih tersedia.
- Siswa memenuhi persyaratan program.
- Subject selection valid jika program menggunakan pilihan mata pelajaran.

Jika pembayaran berhasil tetapi kursi tidak tersedia akibat race condition, transaksi masuk exception handling dan tidak boleh memaksakan enrollment.

---

## E. OFFLINE CLASS DOMAIN

### E1. Business Rules

Keputusan existing:
- Model kelas: cohort.
- Capacity: all_sessions.
- Seat confirmation: paid.
- Makeup: limited.
- Fee calculation: class_first.
- Management: shared.
- Primary operator: EduPath.
- Partners: operational support.

### E2. Class Offering

Offering menghubungkan:
- Academic program.
- Product package.
- Tenant/operator.
- Periode.
- Lokasi.
- Cohort.
- Harga.
- Kapasitas.
- Renewal policy.
- Registration window.

### E3. Cohort

Cohort merupakan rombongan belajar dalam offering.

Setiap cohort memiliki:
- Kapasitas.
- Jadwal.
- Pengajar.
- Ruangan.
- Daftar siswa.
- Status operasional.

### E4. Sessions

Setiap cohort terdiri dari beberapa sesi.

Setiap sesi memiliki:
- Tanggal.
- Jam mulai dan selesai.
- Ruangan.
- Pengajar.
- Subject/topic.
- Kapasitas yang berlaku.
- Status.

### E5. Capacity — all_sessions

Sistem harus memvalidasi ketersediaan semua sesi wajib.

Untuk program dengan pilihan mata pelajaran, kapasitas dihitung berdasarkan sesi yang relevan dengan pilihan siswa.

Pemeriksaan dan reservasi kursi harus aman terhadap concurrent requests.

### E6. Attendance

Status:
- present.
- late.
- absent.
- excused.

Setiap perubahan presensi menyimpan actor, timestamp, dan audit trail.

### E7. Makeup

- Limited quota.
- Pengajuan terkait sesi asal.
- Validasi kesesuaian subject.
- Validasi kapasitas sesi pengganti.
- Persetujuan petugas berwenang.
- Riwayat perubahan tersimpan.

---

## F. PARTNER MANAGEMENT

### F1. Partner Entity

Sistem harus dapat membedakan:
- Partner tenant.
- Partner operator.

Keduanya tidak boleh dicampur sebagai satu bentuk akses tanpa konteks hubungan kerja sama.

### F2. Partner Tenant

Hak:
- Mengelola operasi tenant sendiri sesuai izin.
- Menggunakan produk katalog pusat.
- Mengelola offering lokal jika diberi kewenangan.
- Melihat data operasional tenant sendiri.

Batasan:
- Tidak mengubah master catalog.
- Tidak melihat data tenant lain.
- Tidak mengakses ledger global.
- Tidak mengubah payment configuration pusat.

### F3. Scoped Operator

Assignment dapat mencakup:
- Tenant.
- Lokasi.
- Offering.
- Cohort.
- Session.
- Periode assignment.

Hak akses harus divalidasi pada setiap request backend.

### F4. Revocation

Ketika assignment dicabut:
- Akses operasional langsung dihentikan.
- Histori kelas dan presensi tidak dihapus.
- Data transaksi tidak berubah.
- Assignment tercatat sebagai revoked.

---

## G. COHORT RENEWAL CONFIGURATION

### G1. Renewal Policy

Konfigurasi minimal:
- renewal_mode.
- eligibility_rule.
- renewal_open_at.
- renewal_close_at.
- renewal_price.
- extension_duration.
- allowed_cohort_change.
- capacity_validation_mode.
- policy_version.

### G2. New Enrollment

Sistem membuat enrollment baru dan menghubungkannya dengan enrollment sebelumnya melalui referensi renewal.

### G3. Extend Existing

Sistem membuat renewal record yang mereferensikan enrollment lama.

Tanggal dan periode baru disimpan sebagai histori, bukan menghapus periode lama.

### G4. Eligibility

Contoh syarat:
- Enrollment lama masih aktif atau berada dalam grace period.
- Offering renewal tersedia.
- Pembayaran renewal berhasil.
- Siswa memenuhi syarat program.
- Tidak ada konflik cohort atau jadwal.
- Kapasitas tersedia jika renewal membutuhkan sesi baru.

---

## H. UNIFIED ACADEMIC ENGINE

Seluruh mode menggunakan sumber akademik yang sama.

Struktur:
Produk → Program → Subject → Topik → Subtopik → Materi → Soal.

Sumber belajar:
- Digital material.
- Practice.
- Quiz.
- CBT.
- Tryout.
- Instructor feedback.
- Attendance.

Unified progress menggabungkan:
- Materi selesai.
- Hasil latihan.
- Hasil tryout.
- Perkembangan kompetensi.
- Kehadiran.
- Evaluasi pengajar.

Jangan membuat bank soal offline atau histori akademik terpisah.

---

## I. RBAC & TENANT ISOLATION

Pertahankan role existing dan perluas permission melalui pola yang sudah berjalan.

Role yang disebut dalam dokumen arsitektur:
- superadmin.
- admin.
- teacher.
- school_bk.
- student.
- affiliate.

Partner operator tidak boleh otomatis memperoleh akses admin.

Akses partner ditentukan oleh assignment dan resource scope.

Semua validasi harus dilakukan backend:
- Authenticated user.
- Role/permission.
- Tenant scope.
- Resource ownership.
- Assignment validity.
- Entitlement.

Frontend visibility bukan pengganti otorisasi backend.

---

## J. CONCEPTUAL DATA MODEL

Entitas konseptual:

Catalog:
- product_catalog.
- product_packages.
- package_components.
- product_versions.
- package_price_versions.

Commerce:
- orders (existing).
- subscriptions (existing, if applicable).
- entitlements (existing).
- purchase snapshots.
- upgrade references.

Class:
- class_offerings.
- cohorts/classes (reuse existing).
- class_enrollments (reuse/extend existing class_students if suitable).
- class_sessions (reuse/extend class_schedules).
- attendance (reuse existing).
- makeup_requests.
- renewal_records.
- renewal_policies.

Partner:
- partner_profiles.
- partner_tenant_relationships.
- partner_assignments.
- partner_assignment_scopes.

Audit:
- catalog_audit_logs.
- enrollment_audit_logs.
- partner_assignment_logs.
- renewal_audit_logs.

Jangan menganggap semua nama tersebut harus menjadi tabel baru. Lakukan schema mapping terlebih dahulu.

---

## K. API SPECIFICATION — PROPOSED

Endpoint berikut adalah kontrak konseptual. Audit API existing sebelum menentukan URL final.

Catalog:
- GET /api/products
- GET /api/products/{id}
- GET /api/programs
- GET /api/packages
- GET /api/packages/{id}

Admin catalog:
- POST /api/admin/catalog/products
- POST /api/admin/catalog/packages
- PUT /api/admin/catalog/packages/{id}
- POST /api/admin/catalog/packages/{id}/publish

Purchase:
- POST /api/purchases
- POST /api/purchases/{id}/upgrade
- GET /api/purchases/{id}

Class:
- GET /api/classes
- POST /api/classes
- GET /api/classes/{id}/sessions
- POST /api/classes/{id}/enrollments
- POST /api/enrollments/{id}/renew
- POST /api/sessions/{id}/attendance
- POST /api/makeup-requests

Partner:
- POST /api/admin/partners
- POST /api/admin/partners/{id}/assignments
- DELETE /api/admin/partners/{id}/assignments/{assignment_id}
- GET /api/partners/assignments

Renewal:
- GET /api/enrollments/{id}/renewal-options
- POST /api/enrollments/{id}/renew
- GET /api/enrollments/{id}/renewal-history

Semua endpoint:
- Mengikuti response envelope existing.
- Menggunakan autentikasi existing.
- Menggunakan CSRF untuk mutasi cookie-authenticated.
- Menggunakan prepared statements.
- Memvalidasi tenant dan resource scope.
- Menggunakan idempotency untuk operasi kritis.
- Tidak mempercayai harga dari frontend.

---

## L. ACCEPTANCE CRITERIA

AC-01: Satu katalog pusat digunakan oleh seluruh tenant.

AC-02: Tenant tidak dapat mengubah master catalog tanpa izin pusat.

AC-03: Perubahan katalog tidak mengubah snapshot transaksi lama.

AC-04: Upgrade selalu menggunakan harga penuh paket tujuan.

AC-05: Upgrade tidak menghasilkan prorata otomatis.

AC-06: Paket lama tetap memiliki histori dan masa berlaku sendiri.

AC-07: Webhook ganda tidak menggandakan entitlement.

AC-08: Partner tenant hanya mengakses tenant sendiri.

AC-09: Partner operator hanya mengakses assignment yang aktif.

AC-10: Revocation menghentikan akses tanpa menghapus histori.

AC-11: Offering dapat mengatur renewal_mode.

AC-12: New enrollment menghasilkan enrollment baru.

AC-13: Extend existing menyimpan histori periode renewal.

AC-14: Capacity all_sessions divalidasi secara atomik.

AC-15: Makeup mengikuti kuota dan kapasitas.

AC-16: Online, offline, dan hybrid menggunakan academic engine yang sama.

AC-17: Student progress tetap satu profil.

AC-18: Existing login, payment, entitlement, CBT, SPP, school dashboard, dan tenant isolation tidak mengalami regression.

---

## M. IMPLEMENTATION ROADMAP

Phase 0 — Existing System Audit:
- Repository inventory.
- Database schema mapping.
- Payment/entitlement audit.
- Class module audit.
- RBAC and tenant isolation audit.
- Existing API contract.
- Gap analysis.

Phase 1 — Shared Product Catalog:
- Master program.
- Product package.
- Package components.
- Product versioning.
- Central pricing.
- Tenant catalog access.

Phase 2 — Purchase & Upgrade:
- Order snapshot.
- Full-price upgrade.
- Entitlement grant linkage.
- Payment idempotency.
- Purchase history.

Phase 3 — Offline Class:
- Offering.
- Cohort.
- Enrollment.
- Session.
- Capacity.
- Attendance.
- Makeup.

Phase 4 — Partner Management:
- Partner tenant.
- Scoped operator.
- Assignment.
- Revocation.
- Audit.

Phase 5 — Renewal:
- Renewal policy.
- New enrollment.
- Extend existing.
- Renewal history.
- Capacity and payment validation.

Phase 6 — Hybrid & Unified Progress:
- Combined access.
- Student dashboard.
- Unified progress.
- School analytics.

Phase 7 — Pilot & Release:
- Regression.
- Security.
- Payment simulation.
- Concurrent enrollment tests.
- UAT.
- Production release approval.

---

## N. RELEASE RULES

- Audit before migration.
- No production migration without approval.
- Backup before migration.
- Migration must be reversible where practical.
- Existing API contract must remain compatible.
- No full-file rewrite of large stable Vue files.
- No changes to Midtrans, FinancialLedger, RevenueSplitEngine, or EntitlementService without impact analysis.
- No feature marked complete without QA evidence.
- No deployment without explicit release approval.

Final objective:

EduPath becomes one unified platform with a shared product catalog, full-price upgrade, two partner operating models, configurable cohort renewal, and a unified academic experience across Online, Offline, and Hybrid delivery.
