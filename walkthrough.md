# UAT Browser Authentication Test

> [!NOTE]
> Demonstrasi real-time login melalui browser menggunakan akun **UAT Student** ke Frontend EduPath (Vite) yang terhubung langsung ke Backend PHP dev-server di port 8000.

### Video Rekaman Sesi Browser
![Browser Login Recording](file:///C:/Users/yanay/.gemini/antigravity-ide/brain/333539c5-7aa7-4ce2-9f32-e53d25feed58/uat_student_login_retry_1789135555388.webp)
*Video menampilkan langkah-langkah navigasi, pengetikan kredensial `student@uat.edupath.local` dan submit form login hingga berhasil.*

---

### Verifikasi Dashboard
![Student Dashboard Result](file:///C:/Users/yanay/.gemini/antigravity-ide/brain/333539c5-7aa7-4ce2-9f32-e53d25feed58/student_dashboard_1789135785407.png)

**Laporan Hasil:**
1. **Frontend-Backend Integration:** Aplikasi frontend (Vite/Vue di Port 5173) berhasil berkomunikasi dengan backend PHP (Port 8000) tanpa isu CORS setelah dilakukan penyesuaian `.env`.
2. **Authentication Flow:** Proses klik "Masuk", input email `student@uat.edupath.local`, dan pengisian password berhasil dikirim ke endpoint `auth.php?action=login`.
3. **Dashboard Loading:** Setelah sukses login, sistem merender Dashboard Siswa secara aktual lengkap dengan UI components yang relevan:
   - "Halo, Siswa Mandiri!" (Nama alias fallback front-end atau data dummy user UAT).
   - Menu aksi SPP, Tryout, Belajar Materi, dan Proyeksi PTN tampil normal.
   - Status user ter-autentikasi terkonfirmasi dari munculnya Avatar `SM` di pojok kiri bawah.
