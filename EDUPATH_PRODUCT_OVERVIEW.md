# Dokumen Produk EduPath: Platform Belajar Adaptif & Persiapan SNBT Terukur

## 1. Visi & Misi Produk
**EduPath** adalah platform *Adaptive Learning* (Pembelajaran Adaptif) berbasis AI yang dirancang khusus untuk siswa SMA dan *gap year* yang berjuang lulus UTBK / SNBT.
Visi utama EduPath adalah mengubah cara belajar dari "menghafal buta dan belajar acak" menjadi "belajar terarah berbasis data (Data-Driven Learning) dan Kalibrasi IRT (Item Response Theory)".

## 2. Target Pengguna (User Persona)
- **Siswa SMA Kelas 12**: Sedang mempersiapkan ujian UTBK secara intensif dan membutuhkan bimbingan materi yang terarah tanpa harus menghabiskan biaya besar.
- **Siswa Gap Year**: Pejuang PTN yang sudah memiliki dasar materi namun butuh perbaikan strategi spesifik dan analisa "Blind Spot" kelemahan mereka pada ujian sebelumnya.
- **Orang Tua**: Memerlukan laporan transparan tentang probabilitas anak lolos PTN impian mereka dan kemajuan harian (Streak).

## 3. Fitur Utama (Core Features)

### A. AI Adaptive Path & Evaluasi (Blind Spot Detector)
- Platform tidak sekadar memberikan soal acak, tetapi menganalisis kelemahan spesifik siswa. 
- Mencegah siswa dari jebakan hafalan buta dengan fokus pada logika dan penalaran.
- Menyesuaikan tingkat kesulitan latihan secara dinamis sehingga *skor tryout* tidak mandek.

### B. Student Potential Path (SPP)
- Uji Diagnostik Instan untuk memetakan kondisi awal siswa.
- Memberikan rekomendasi subtes prioritas yang harus dikuasai untuk meraih lonjakan nilai tertinggi di jurusan impian.

### C. Kalkulator Prediksi Skor & Peluang Lolos PTN AI (Real-time)
- Simulator interaktif bagi pengguna. Pengguna memasukkan: Target Universitas/Jurusan, Skor Tryout Terakhir, dan Komitmen Belajar Harian.
- Sistem AI memberikan: 
  - **Peluang Kelulusan** dalam persentase (%).
  - **Prediksi Lonjakan Skor AI** (+ Poin Lonjakan).
  - Status Passing Grade (Lolos / Sisa Poin).

### D. Kuis HOTS Interaktif (Mini Quiz & IRT System)
- Pengguna dapat mencoba simulasi soal HOTS secara instan tanpa perlu registrasi panjang di awal.
- Menggunakan perhitungan bobot skor Item Response Theory (IRT).
- Saat menjawab, AI memberikan *Breakdown Card*: Pembahasan Konseptual dan "Trik Kilat EduPath".

### E. User Dashboard (Gamification & Tracker)
- **Target PTN Selector**: Selalu muncul di *header* untuk memonitor sisa poin yang dibutuhkan menuju Passing Grade.
- **Streak & Coins System**: Membangun kedisiplinan dan retensi pengguna melalui *gamification*.
- **Sub-Materi & Progress (Side Menu)**: Akses ke materi belajar spesifik per subtes UTBK (cth: Penalaran Matematika, Literasi Bahasa). Pengguna gratis (Free) dibatasi tanda gembok, sementara Pro Member mendapat akses penuh.

### F. Affiliate System
- Modul dashboard afiliasi bagi pengguna (atau pihak ketiga) untuk mereferensikan EduPath dan mendapatkan komisi, sehingga dapat menumbuhkan jumlah pengguna secara organik.

## 4. Keunggulan Kompetitif (EduPath vs Bimbel Tradisional)
| Aspek | Bimbel Tradisional | EduPath |
| --- | --- | --- |
| **Pola Belajar** | Materi Pukul Rata (Sama untuk semua) | Personalisasi AI sesuai *Blind Spot* siswa |
| **Metode Soal** | Latihan Acak / Kurang Kalibrasi IRT | Simulasi standar IRT & Analisa HOTS |
| **Prediksi PTN** | Rapor periodik bulanan | Kalkulator Peluang *Real-time* berbasis data harian |

## 5. Monetisasi & Model Bisnis
- **Freemium Model**: Tes Potensi / SPP (Free), Mini Quiz (Free Demo).
- **Pro Member (Premium)**: Akses penuh ke *Diagnostic*, *Learning*, *Simulator*, dan *Studyroom*. (Ada flag `is_premium` dalam database untuk membuka proteksi gembok).

## 6. Arsitektur Frontend & Tech Stack
- **Framework**: Vue.js 3 (Vite).
- **Styling**: TailwindCSS dengan kustomisasi CSS (Glassmorphism, animasi `bouncy-card`, mesh gradients, typography "Outfit").
- **Backend / Database Layer**: PHP Scripts (`/api/*.php`) - memfasilitasi admin, migrate bank, accounts, payouts.
- **Integrasi**: Midtrans Snap (Payment Gateway), Google Analytics (GA4) / Meta Pixel.

## 7. Referensi File Utama
- `/src/App.vue` : Main Layout, Landing Page, Navigation, Modal Auth, Simulator Peluang.
- `/src/main.js` : Entry point aplikasi Vue, inisialisasi env variables (termasuk *Midtrans*).
- `/src/index.css` : Design system, Tailwind *directives*, *premium branding utilities*.
- Direktori `/api/` : Layanan Backend (PHP) yang menangani database (Users, Payouts, Admin, Bank, Tryout).

---
*Dokumen ini merupakan intisari produk berdasarkan analisis codebase frontend dan backend yang relevan untuk proses analisa AI.*
