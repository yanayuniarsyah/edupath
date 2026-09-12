# UAT Accounts (Permanent Test Accounts)

> [!IMPORTANT]
> **UAT ACCOUNTS ARE PERSISTENT TEST ACCOUNTS — DO NOT CLEANUP**
>
> Akun-akun di bawah ini khusus ditujukan untuk manual testing/UAT pada database `edupath_test`. Automatisasi Forensic QC tidak akan menghapus akun-akun ini pada saat cleanup.

| Name | Email | Role | Tenant | Login | Purpose |
| ---- | ----- | ---- | ------ | ----- | ------- |
| UAT Super Admin | `superadmin@uat.edupath.local` | `superadmin` | *Global (None)* | `api/admin.php?action=login` | SaaS administration, tenant management, packages/plans, global reporting |
| UAT Tenant Admin | `admin@uat.edupath.local` | `admin` | `uat-edupath` (UAT EduPath Tenant) | `api/admin.php?action=login` | Tenant operations, students, classes, questions, assessments |
| UAT Student | `student@uat.edupath.local` | `student` | `uat-edupath` (UAT EduPath Tenant) | `api/auth.php?action=login` | Login, dashboard, learning material, tryout/asesmen/latihan |
| UAT Affiliate | `affiliate@uat.edupath.local` | `affiliate` | `uat-edupath` (UAT EduPath Tenant) | *N/A (No Endpoint yet)* | Referral link/code attribution, commission tracking |
| UAT Tutor | `tutor@uat.edupath.local` | `teacher` | `uat-edupath` (UAT EduPath Tenant) | *N/A (No Endpoint yet)* | Class monitoring, assessment tracking |

### Catatan Tambahan:
- **Role Audit:** Role `superadmin`, `admin`, dan `student` tervalidasi penuh baik secara Schema Database maupun implementasi Logic Login API (`auth.php` & `admin.php`).
- **Affiliate & Tutor:** Di sistem database existing, Role "Tutor" direpresentasikan sebagai `teacher`. Data untuk Affiliate (tabel `affiliates`) dan Teacher (tabel `admins` + role `teacher`) telah dimasukkan ke database, namun arsitektur backend eksisting saat ini belum memiliki Endpoint HTTP khusus untuk mereka melakukan operasi login.
- **Security:** Seluruh password di-hash menggunakan `BCRYPT` bawaan sistem, sesuai standard Production. Tidak ada plain-text password di database maupun perubahan logic auth khusus UAT. Identifikasi persistensi difokuskan pada pola domain `@uat.edupath.local` untuk mencegah auto-cleanup saat test runner beroperasi.
- **Verification:** Login `superadmin`, `admin`, dan `student` telah terbukti berjalan mulus di REST API, lengkap dengan validasi pembatasan (boundaries) akses RBAC *cross-tenant*.
