# UAT Browser Authentication & Local Run Test

> [!NOTE]
> Demonstrasi real-time login melalui browser menggunakan akun **UAT Student** ke Frontend EduPath (Vite) yang terhubung langsung ke Backend PHP dev-server di port 8000 dan MySQL port 3306.

### Video Rekaman Sesi Browser
![Browser Login Recording](file:///C:/Users/yanay/.gemini/antigravity-ide/brain/8f536b62-bb48-4856-9240-d3666a8d8ffc/local_run_verification_1792321847858.webp)
*Video menampilkan langkah-langkah navigasi, pengetikan kredensial `student@uat.edupath.local` dan submit form login hingga berhasil masuk ke dashboard.*

---

### Verifikasi Dashboard
![Student Dashboard Result](file:///C:/Users/yanay/.gemini/antigravity-ide/brain/8f536b62-bb48-4856-9240-d3666a8d8ffc/student_dashboard_1792322108033.png)

**Laporan Hasil:**
1. **Frontend-Backend Integration:** Aplikasi frontend (Vite/Vue di Port 5173) berhasil berkomunikasi dengan backend PHP (Port 8000) dan database MySQL lokal (Port 3306).
2. **Authentication Flow:** Proses klik "Masuk", input email `student@uat.edupath.local`, dan pengisian password berhasil dikirim ke endpoint `auth.php?action=login` dan mengembalikan token yang valid.
3. **Dashboard Loading:** Setelah sukses login, dashboard siswa berhasil dirender dengan ucapan selamat datang "Halo, UAT Student!", statistik, dan menu lengkap.

