# Phase 1: Test Matrix EduPath.co.id

| Flow | Test Case | Expected Result | Actual Result | Status | Evidence/Notes |
|---|---|---|---|---|---|
| **Public** | Load Homepage | Halaman tampil sempurna | - | NOT RUN | - |
| **Public** | Registrasi Akun Siswa Baru | Akun terbuat di `users` dan `students`, JWT terbentuk | - | NOT RUN | - |
| **Auth** | Login Siswa Valid | Mengembalikan Token dan identitas | - | NOT RUN | - |
| **Auth** | Login Admin | Mengembalikan Token Role Admin | - | NOT RUN | - |
| **Auth** | Login Gagal | Muncul error 4xx | - | NOT RUN | - |
| **Payment** | Akses SPP sebelum bayar | Muncul lock screen atau error 403 | - | NOT RUN | - |
| **Payment** | Buat Order / Checkout | Order terdaftar dengan status `pending` di DB | - | NOT RUN | - |
| **Payment** | Webhook Midtrans Success | Mengubah status order & generate entitlement | - | NOT RUN | - |
| **Entitlement** | Akses Premium sesudah bayar | Fitur premium SPP terbuka | - | NOT RUN | - |
| **Entitlement** | Kadaluwarsa Paket | Hak akses hilang/fallback ke Free | - | NOT RUN | - |
