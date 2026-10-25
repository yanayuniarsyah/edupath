# AUDIT TEKNIS READ-ONLY (PHASE 0)
## Sistem Manajemen Kelas Offline Berbasis Cohort (PRD-EDU-OFFLINE-CLASS-001)

**Dokumen:** AUDIT-EDU-OFFLINE-CLASS-P0  
**Tanggal:** 25 Oktober 2026  
**Status:** Audit Selesai — Menunggu Review & Persetujuan Technical Design  
**Pemeriksa:** Lead Technical Architect & QC Auditor EduPath  

---

## 1. Eksekutif Ringkasan Audit

Sesuai instruksi pada **PRD-EDU-OFFLINE-CLASS-001 Bagian 17, 18, dan 20**, tahapan ini dijalankan dalam mode **Read-Only**:
- Tidak ada modifikasi skema database produksi.
- Tidak ada penulisan data atau file migrasi yang dieksekusi di runtime.
- Seluruh fondasi core EduPath (Autentikasi, Tenant Scoping, Midtrans Payment Engine, Entitlement Digital, dan Revenue Waterfall) telah diaudit secara forensik.

Sistem EduPath saat ini memiliki fondasi multi-tenant dan transaksi yang sangat solid (berjalan dengan arsitektur SaaS Phase 1–9). Penambahan sistem kelas offline berbasis cohort dapat dilakukan secara **100% aditif (zero breaking change)** tanpa mengganggu pendaftaran siswa, pembayaran paket online, maupun entitlement tryout yang sudah berjalan.

---

## 2. Matriks Existing vs Target

| Aspek Arsitektur | Kondisi Sistem Saat Ini (Existing) | Kebutuhan Target PRD Offline (Target) | Analisis Kompatibilitas |
|---|---|---|---|
| **Identitas & Akun** | Tabel `users`, `user_roles`, `students`, `admins`. Mendukung multi-role: `superadmin`, `admin` (cabang), `school_bk`, `teacher`, `student`, `affiliate`. | Pengguna yang sama untuk HQ Admin, Mitra Cabang, Guru BK, Pengajar, dan Siswa. | **100% Kompatibel.** Tidak perlu membuat sistem akun atau login baru. |
| **Isolasi Tenant** | Tabel `tenants`, kolom `tenant_id` pada `students`, `admins`, `affiliates`, `schools`. | Kelas offline terikat pada tenant pemilik (HQ atau Cabang/Sekolah Mitra). | **100% Kompatibel.** Dapat langsung menggunakan `tenant_id` yang ada. |
| **Produk / Paket** | Tabel `plans`, `plan_entitlements`, `products`. Berorientasi langganan digital bulanan/tahunan. | `offline_programs` dan `offline_cohorts` dengan kapasitas fisik dan jadwal pertemuan. | **Aditif.** Program & Cohort offline menjadi entitas khusus yang dapat ditautkan ke paket berbayar. |
| **Kapasitas Kursi** | Digital entitlement (unlimited / kuota tryout software). | Kapasitas fisik aman berbasis ruangan dan pengajar dengan aturan `ALL_SESSIONS`. | **Baru (Aditif).** Perlu entitas `offline_rooms`, `offline_sessions`, dan `offline_seat_reservations`. |
| **Reservasi Sementara** | Belum ada reservasi kursi berbatas waktu (TTL lock). | Kursi ditahan sementara (`HELD`) selama proses pembayaran dengan TTL 30 menit. | **Baru (Aditif).** Menjamin anti-overbooking saat checkout simultan. |
| **Jadwal & Ruang** | Belum ada manajemen sesi kelas tatap muka atau ruangan fisik. | Jadwal sesi pertemuan tunggal & berulang dengan deteksi bentrok instruktur dan ruang. | **Baru (Aditif).** Membutuhkan modul penjadwalan & availability check. |
| **Alur Pembayaran** | `orders`, `invoices`, `payments`, `payment_events_history` via Midtrans SNAP & Webhook Signature. | Pendaftaran kelas offline berbayar memanfaatkan alur checkout dan webhook yang ada. | **100% Kompatibel.** Order dibuat dengan metadata cohort, webhook memicu konfirmasi kursi. |
| **Entitlement vs Kelas** | `EntitlementService` mengelola fitur digital (`tryout_unlimited`, `premium_materials`). | Hak kelas fisik dikelola via `offline_enrollments` dan `offline_attendance`. | **Terisolasi Rapi.** Entitlement digital dan hak kelas offline tidak saling mencampuri (PRD 3.3). |
| **Revenue Sharing** | `RevenueSplitEngine` PRD v1.2: Pool $P = G - F - R - S$ lalu dibagi ke HQ & Cabang. | Class-First Costing: Pool $P = G - F - R - D$ (Biaya langsung $D$ dikurangkan di awal). | **Kompatibel.** Parameter biaya langsung ($D$) dapat disalurkan ke engine sebelum split pool. |
| **Presensi & Make-up** | Belum ada absensi kelas tatap muka. | Pencatatan attendance (`PRESENT`, `LATE`, `EXCUSED`, `ABSENT`) & make-up credit terbatas. | **Baru (Aditif).** Entitas attendance dan make-up credit mandiri. |

---

## 3. Daftar Tabel dan Endpoint yang Dapat Digunakan Kembali

### 3.1 Tabel Database yang Dapat Digunakan Kembali Tanpa Perubahan
1. **`tenants`** — Sumber isolasi cabang dan sekolah mitra.
2. **`users` & `user_roles`** — Kredensial autentikasi universal dan kontrol hak akses per role.
3. **`students`** — Data profil siswa peserta kelas offline.
4. **`schools`** — Profil sekolah mitra dan koordinator Guru BK.
5. **`orders`** — Rekaman transaksi finansial, nominal, SNAP token, kanal distribusi, dan status pembayaran.
6. **`invoices` & `payments`** — Snapshot audit perpajakan, diskon, dan riwayat upaya pembayaran gateway.
7. **`payment_events_history`** — Pencegah webhook ganda (idempotency guard) dan audit payload gateway.
8. **`financial_ledger`** — Pencatatan jurnal pembagian pendapatan setelah dipotong biaya langsung.
9. **`audit_logs`** — Pencatatan jejak audit perubahan jadwal, pembatalan, dan persetujuan biaya.

### 3.2 Endpoint & Modul Backend yang Digunakan Kembali
1. **`api/auth.php`** — Endpoint login, verifikasi JWT Bearer, dan pembagian hak akses front login vs admin portal.
2. **`api/payment.php`** — Alur pembuatan transaksi SNAP Midtrans dan penerima webhook verifikasi tanda tangan SHA-512.
3. **`api/RevenueSplitEngine.php`** — Engine matematika pembagian hasil yang mendukung pengurangan biaya operasional.
4. **`api/FinancialLedger.php`** — Engine pencatatan pembukuan jurnal keuangan multi-pihak.
5. **`api/rate_limit.php`** — Pustaka pembatas laju request untuk mencegah brute force dan spam reservasi kursi.

---

## 4. Gap Analysis untuk Fitur Kelas Offline

Berdasarkan audit komparatif, terdapat 10 entitas struktural baru yang perlu ditambahkan:

```
[Program Offline]
       │
       ▼
 [Cohort Angkatan] ──── (Kapasitas Efektif = Min Seluruh Sesi)
       │
       ├─────────────────────────────────┐
       ▼                                 ▼
 [Ruangan (Rooms)]             [Sesi Pertemuan (Sessions)]
       │                                 │
       ▼                                 ▼
(Kapasitas Aman)               (Instruktur & Jadwal Bebas Bentrok)
                                         │
       ┌─────────────────────────────────┴─────────────────────────────────┐
       ▼                                                                   ▼
[Reservasi Sementara (Seat Reservation)]                      [Presensi Sesi (Attendance)]
       │                                                                   │
       ▼ (Setelah Bayar Lunas)                                             ▼
[Pendaftaran Terkonfirmasi (Enrollment)]                      [Hak Pengganti (Makeup Credit)]
```

Daftar tabel aditif yang akan dibuat pada Phase 1 Technical Design:
1. **`offline_programs`**: Definisi kurikulum, jenjang target, jumlah sesi total, aturan make-up.
2. **`offline_cohorts`**: Periode angkatan, kapasitas target, status pendaftaran (`OPEN`, `FULL`, `CLOSED`).
3. **`offline_rooms`**: Ruang kelas fisik, lokasi cabang, kapasitas daya tampung aman.
4. **`offline_sessions`**: Pertemuan terperinci (tanggal, jam, ruangan, guru utama & pengganti, status).
5. **`offline_seat_reservations`**: Penahan kursi sementara saat checkout dengan waktu kedaluwarsa (`expires_at`).
6. **`offline_enrollments`**: Kepesertaan siswa resmi pada cohort tertentu (terkait ke `orders.id`).
7. **`offline_waitlists`**: Antrean calon peserta saat kelas penuh.
8. **`offline_attendance`**: Absensi per sesi per siswa (`PRESENT`, `LATE`, `EXCUSED`, `ABSENT`).
9. **`offline_makeup_credits`**: Kuota sesi pengganti akibat pembatalan penyelenggara atau izin sah.
10. **`offline_direct_costs`**: Catatan biaya langsung kelas (honor guru, sewa ruang, modul cetak) sebelum pembagian pool.

---

## 5. Analisis Risiko Integrasi & Keamanan

### 5.1 Risiko Overbooking Konkuren (Race Condition)
- **Skenario:** Sisa 1 kursi terakhir pada cohort. Dua siswa menekan tombol "Daftar & Bayar" pada milidetik yang sama.
- **Mitigasi:** Eksekusi reservasi menggunakan transaksi pesimistis di backend:
  ```sql
  SELECT SUM(confirmed_seats + active_held_reservations) FROM ... FOR UPDATE;
  ```
  Jika total mencapai kapasitas efektif, request kedua ditolak seketika atau dialihkan ke daftar tunggu.

### 5.2 Risiko Pembayaran Berhasil Setelah Reservasi Kedaluwarsa
- **Skenario:** Siswa mendapatkan kode bayar QRIS dengan TTL reservasi 30 menit. Siswa baru menyelesaikan pembayaran di menit ke-35, sementara kursi sudah dilepas ke siswa lain.
- **Mitigasi (AC-06 / TC-10):** Webhook Midtrans tetap memproses pembayaran sebagai `paid`, namun alur reservasi mendeteksi bahwa TTL telah habis. Sistem memeriksa kapasitas:
  - Jika kursi masih tersedia: Konfirmasi otomatis dipulihkan.
  - Jika kursi sudah penuh: Status order ditandai `reconciliation_required`, siswa dimasukkan ke *Priority Waitlist*, dan notifikasi rekonsiliasi/opsi refund muncul di dashboard admin tanpa memicu overbooking.

### 5.3 Risiko Kebocoran Data Antar-Tenant (Cross-School / Cross-Branch Breach)
- **Skenario:** Admin Mitra Cabang Bandung dapat melihat atau mengedit daftar siswa kelas cabang Cilacap.
- **Mitigasi:** Seluruh query backend di-scope secara ketat menggunakan payload JWT:
  ```php
  if ($payload->role !== 'superadmin') {
      $sql .= " AND c.tenant_id = ?";
      $params[] = $payload->tenant_id;
  }
  ```
  Pengujian isolasi cabang akan dimasukkan dalam quality gate test suite.

### 5.4 Risiko Regresi Core Payment & Entitlement
- **Skenario:** Perubahan alur order untuk kelas offline mengganggu transaksi tryout SNBT atau merusak aktivasi entitlement digital.
- **Mitigasi:** Isolasi pemrosesan order. Pada tabel `orders`, ditambahkan penanda `order_category = 'OFFLINE_CLASS'` atau pemisahan handler saat fulfillment. Pembelian paket online tetap melewati `EntitlementService`, sedangkan offline class mengaktifkan `offline_enrollments`.

---

## 6. Rekomendasi Pola Migrasi Minimal

1. **Strategi Zero-Downtime:** Seluruh tabel baru menggunakan skema aditif independen (`CREATE TABLE IF NOT EXISTS offline_*`).
2. **Tidak Mengubah Skema Core:** Kolom pada tabel `students`, `orders`, `invoices`, dan `subscriptions` tidak diubah tipe datanya. Hanya ditambahkan indeks atau relasi foreign key opsional (`ON DELETE RESTRICT`).
3. **Pemberian ID Standar:** Seluruh entitas baru menggunakan identifier UUID v4 (36 karakter) konsisten dengan standar SaaS Phase 1–9.
4. **Jejak Audit Terintegrasi:** Setiap modifikasi status cohort, pembatalan sesi, dan pengeluaran biaya langsung dicatat pada tabel `audit_logs` yang sudah ada.

---

## 7. Rencana Backup, Pemulihan, dan Rollback

- **Sebelum Eksekusi Migrasi (Phase 2):**
  1. Menjalankan backup database skema dan data via skrip `api/backup_db.php`.
  2. Menyimpan snapshot tabel `orders`, `students`, `plans`, dan `tenants`.
- **Rencana Rollback:**
  - Karena seluruh tabel offline bersifat aditif, rollback darurat cukup mengeksekusi DDL pembatalan tanpa risiko merusak data siswa atau transaksi online:
  ```sql
  DROP TABLE IF EXISTS offline_direct_costs, offline_makeup_credits, offline_attendance,
                        offline_waitlists, offline_seat_reservations, offline_enrollments,
                        offline_sessions, offline_rooms, offline_cohorts, offline_programs;
  ```

---

## 8. Daftar Test yang Dapat Dijalankan (Quality Gates Matrix)

Test suite otomatis akan dibangun di `api/tests/test_offline_classes.php` untuk memvalidasi ke-28 skenario pengujian PRD:

| Test ID | Skenario Uji Kritis | Target Verifikasi |
|---|---|---|
| **TC-01 s/d TC-03** | Validasi Kapasitas Efektif | Kapasitas cohort dihitung dari batas terkecil seluruh sesi wajib ($C_{efektif} = \min(C_c, C_s)$). |
| **TC-04 s/d TC-06** | Concurrency & Anti-Overbooking | 2 reservasi konkuren untuk 1 kursi terakhir: 1 sukses, 1 ditolak/waitlist. |
| **TC-07 s/d TC-10** | Integrasi Pembayaran & Rekonsiliasi | Pembayaran valid mengonfirmasi kursi; pembayaran terlambat memicu rekonsiliasi aman. |
| **TC-11 s/d TC-13** | Pencegahan Bentrok Jadwal | Sistem menolak jadwal jika instruktur atau ruangan bertabrakan pada waktu yang sama. |
| **TC-14 s/d TC-15** | Perubahan & Pembatalan Sesi | Kapasitas tidak dapat diturunkan di bawah enrollment aktif; pembatalan mencatat audit. |
| **TC-16 s/d TC-18** | Absensi & Make-up Credits | Make-up kredit memiliki masa berlaku, pencegahan pemakaian ganda, dan validasi izin. |
| **TC-19 s/d TC-21** | Isolasi Tenant & Keamanan | Admin Cabang & Siswa dilarang keras mengakses data tenant lain (HTTP 403). |
| **TC-22 s/d TC-25** | Class-First Costing & Ledger | Biaya langsung disetujui memotong pool sebelum pembagian hasil ($P = G - F - R - D$). |
| **TC-26 s/d TC-28** | Regresi Core EduPath | Alur pendaftaran tryout online, payment snapshot, dan entitlement tetap 100% lulus. |

---

## 9. Daftar Keputusan Desain yang Membutuhkan Persetujuan Eksplisit

Sebelum melangkah ke **Phase 1 Technical Design & Coding**, berikut parameter bisnis yang dikunci dan membutuhkan konfirmasi pengelola:

1. **Durasi Reservasi Sementara (Hold TTL):**
   - *Rekomendasi Teknis:* **30 Menit** (selaras dengan masa berlaku standar QRIS & Virtual Account Midtrans).
2. **Batas Maksimum Hak Sesi Pengganti (Make-up Credits):**
   - *Rekomendasi Teknis:* Maksimal **2 Sesi Pengganti** per cohort dengan masa kedaluwarsa **30 hari** sejak diterbitkan.
3. **Batas Waktu Keputusan Kelayakan Finansial Cohort (Target Minimum Peserta):**
   - *Rekomendasi Teknis:* Keputusan pembatalan/penjadwalan ulang otomatis dilakukan paling lambat **H-3 (72 jam)** sebelum sesi pertama dimulai.
4. **Persetujuan Skema Tabel Aditif:**
   - Seluruh tabel kelas offline menggunakan prefix `offline_*` agar terpisah jelas dari modul CBT online.

---

**Hasil Audit:** Sistem EduPath siap untuk memasuki **Phase 1 (Technical Design)** segera setelah parameter di atas disetujui. Tidak ditemukan hambatan arsitektural atau risiko bentrok pada sistem yang ada.
