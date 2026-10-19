# Phase 1: Audit Findings EduPath.co.id

## API INVENTORY
| Endpoint/File | Method | Auth | Role | DB | Purpose | Status |
| ------------- | ------ | ---- | ---- | -- | ------- | ------ |
| `api/auth.php?action=register` | POST | None | - | `users`, `students`, `user_roles` | Register new student | VERIFIED |
| `api/auth.php?action=login` | POST | None | - | `users`, `students`, `user_roles`, `admins` | Authenticate | VERIFIED |
| `api/payment.php?action=create` | POST | Required | student | `students`, `plans`, `orders` | Create Midtrans Snap | VERIFIED |
| `api/payment.php?action=webhook` | POST | Midtrans | - | `orders`, `payments`, `subscriptions` | Handle Midtrans callback | VERIFIED |
| `api/spp.php?action=data` | GET | None | - | `target_universities`, `target_programs` | Pre-login adaptive data | VERIFIED |
| `api/spp.php?action=create_attempt` | POST | Required | student | `spp_attempts` | Create SPP session | VERIFIED |
| `api/spp.php?action=submit_attempt` | POST | Required | student | `spp_attempts`, `spp_responses` | Submit SPP answers | VERIFIED |
| `api/spp.php?action=get_result` | GET | Required | student | `spp_attempts`, `spp_scores`, `spp_profiles` | Get student result | VERIFIED |
| `api/spp.php?action=get_school_aggregate` | GET | Required | admin, superadmin | `spp_attempts` | Admin reports | VERIFIED |

## AUTHORIZATION FINDINGS
- **VERIFIED**: `payment.php` `create` secara implisit menggunakan `$payload->id` untuk lookup `student` tenant, mencegah manipulasi ID parameter dari client.
- **VERIFIED**: `spp.php` pada action `get_result` meneruskan `payload->id` dari JWT langsung ke fungsi `getResult($attempt_id, $payload->id)` yang me-running query `WHERE id = ? AND student_id = ?`, mem-block student lain untuk melihat hasil student yang berbeda (bebas dari IDOR).
- **VERIFIED**: `auth.php` (login) membaca tenant_id dari user_roles. Jika `tenant_active` === 0, access is blocked (403).

## AUTH FINDINGS
- **VERIFIED**: Pembuatan identitas (`register`) menggunakan `$pdo->beginTransaction()` secara benar sebelum meng-insert ke `users` dan `students`. Jika duplikat email (`users.identity_key`), ia membatalkan transaksi.
- **VERIFIED**: Login mechanism auto-upgrades plaintext/MD5 hashes ke Bcrypt. 

## PAYMENT FINDINGS
- **VERIFIED**: `payment.php` mengambil harga paket (`price`, `discount`) langsung dari database menggunakan `$plan_id`, bukan mempercayai total nilai yang dikirim oleh client frontend.
- **VERIFIED**: `payment.php` menerapkan 11% tax (hardcoded) yang ditambahkan ke `subtotal`.

## WEBHOOK FINDINGS
- **VERIFIED**: Signature validation dilakukan secara benar (SHA512).
- **VERIFIED**: Terhadap *Idempotency*. Jika status sama dikirim dua kali, diantisipasi oleh `payment_events_history` dan `is_duplicate_event` di-set menjadi true, yang akan mencegah pembuatan ulang row ke `subscriptions`.
- **VERIFIED**: Terdapat mitigasi "state safety", mencegah order yang telah berstatus `paid` atau `refunded` di-downgrade menjadi `failed` atau `pending`.
- **VERIFIED**: Menggunakan *Pessimistic Locking* (`FOR UPDATE`) pada tabel `orders` untuk mencegah *race condition*.

## ENTITLEMENT FINDINGS
- **VERIFIED**: Tabel `entitlements` dikelola terpisah dan dicek melalui `EntitlementService::hasEntitlement()`. Validasi menggunakan constraint DB `revoked_at IS NULL AND (expires_at IS NULL OR expires_at > NOW())`.
- **NOT VALIDATED**: Belum terverifikasi endpoint mana yang memanggil `hasEntitlement()` untuk memblokir akses jika student mencoba menggunakan fitur tanpa paket aktif, karena belum melihat implementasi `quiz.php` (Tryout CBT).

## SPP API FINDINGS
- **VERIFIED**: Endpoint `spp.php?action=data` mengambil soal TANPA mengambil atribut `answer`, `explanation`, `concept`, dll. sehingga *client* (sebelum login) tidak bisa melihat kunci jawaban (No sensitive data exposure).

## DATABASE SECURITY FINDINGS
- **VERIFIED SAFE**: Hampir seluruh input parameter pada `auth.php`, `payment.php`, dan `spp_service.php` menggunakan SQL Prepared Statements (`$pdo->prepare()`) sehingga terlindungi dari basic SQL Injection.

## SECRET/CONFIG FINDINGS
- **VERIFIED**: `.env` dan rahasia tidak diekspos melalui API response (sudah dilakukan pengecekan pada response payload `login` dan `get_result`).
- **PROPOSED / REMAINING RISK**: Masih ada risiko `.env` berada di dalam document root yang disajikan webserver (`public_html`).

## DEPLOYMENT FINDINGS
- **VERIFIED**: File `.gitignore` mengkonfirmasi klaim bahwa `dist/` sengaja *di-comment* (`# dist/`) dan ada instruksi tertulis `# cPanel deployment menggunakan git push, bukan dist` oleh author aslinya. Hal ini membuktikan bahwa cPanel *git hooks* lah yang menjalankan pull, bukan build di server.

## PRODUCTION PARITY
- **NOT VALIDATED**: `PRODUCTION PARITY` belum bisa diverifikasi secara mandiri dari eksternal karena server cPanel aslinya tidak berada di *local environment* ini.

---
*(End of Findings)*
