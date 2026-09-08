# Panduan GitHub & Implementasi Bimbel Online di IDwebhost untuk Pemula

Panduan ini dibuat khusus untuk orang awam agar mudah memahami cara menyimpan kode aplikasi "Bimbel Online" di GitHub dan mengunggahnya (deploy) ke hosting **IDwebhost**.

Berdasarkan file Anda, aplikasi Bimbel ini sepertinya menggunakan **Vue.js** untuk tampilan depan (Frontend) dan **Node.js** untuk server/backend.

---

## Bagian 1: Mengenal GitHub (Untuk Pemula)

**Apa itu GitHub?**
Bayangkan GitHub seperti Google Drive, tetapi khusus untuk menyimpan kode pemrograman. GitHub membantu Anda menyimpan cadangan kode, melacak perubahan, dan bekerja sama dengan tim.

### Langkah 1: Membuat Akun & Menginstal Git
1. Buka [github.com](https://github.com/) dan buat akun gratis (Sign Up).
2. Unduh dan instal program bernama **Git** di komputer Anda dari [git-scm.com](https://git-scm.com/). (Pilih Next terus sampai selesai saat instalasi).

### Langkah 2: Mengunggah Kode Bimbel ke GitHub
1. Buka GitHub, klik tombol **"New"** untuk membuat tempat penyimpanan baru (disebut *Repository*).
2. Beri nama, misalnya `bimbel-online`. Biarkan pilihan diatur ke **Private** (agar kode Anda rahasia), lalu klik **Create repository**.
3. Buka folder proyek Bimbel Anda (`7 BIMBEL`) di komputer, klik kanan, dan pilih **"Open Git Bash here"** (jika menggunakan Windows).
4. Ketik perintah berikut satu per satu dan tekan Enter:
   ```bash
   git init
   git add .
   git commit -m "Upload pertama aplikasi bimbel"
   git branch -M main
   ```
5. Salin perintah penghubung dari GitHub (biasanya berbentuk `git remote add origin https://github.com/...`), tempel di Git Bash, lalu tekan Enter.
6. Ketik `git push -u origin main` dan tekan Enter. (Masukkan username dan password/token GitHub jika diminta).

> [!TIP]
> Sekarang kode Anda sudah aman tersimpan di internet (GitHub)!

---

## Bagian 2: Persiapan di IDwebhost

IDwebhost menggunakan sistem bernama **cPanel** untuk mengatur hosting. 

1. Login ke akun IDwebhost Anda, lalu masuk ke **cPanel**.
2. Pastikan paket hosting Anda mendukung **Node.js** (biasanya ada menu "Setup Node.js App" di cPanel). Jika tidak ada, Anda mungkin perlu menghubungi CS IDwebhost untuk mengaktifkannya atau upgrade paket.

---

## Bagian 3: Mengunggah (Deploy) Aplikasi ke IDwebhost

Karena aplikasi Anda terbagi menjadi dua bagian (Frontend Vue dan Backend Node.js), kita harus mengunggahnya secara terpisah.

### Tahap A: Backend (Server Node.js)
Backend adalah mesin di balik layar yang mengatur data.

1. Di cPanel IDwebhost, cari menu **Setup Node.js App**.
2. Klik **Create Application**.
3. Isi pengaturan berikut:
   - **Node.js Version:** Pilih versi terbaru yang direkomendasikan (misal 14 atau 16).
   - **Application mode:** Production.
   - **Application root:** Ketik `server` (ini akan membuat folder khusus backend).
   - **Application URL:** Pilih domain/subdomain Anda (misal: `api.domainanda.com`).
   - **Application startup file:** `server.js` atau `index.js` (sesuaikan dengan file utama di folder server Anda).
4. Klik **Create**.
5. Buka menu **File Manager** di cPanel.
6. Buka folder `server` yang baru dibuat.
7. Masukkan semua isi dari folder `7 BIMBEL/server` di komputer Anda ke dalam folder `server` di File Manager cPanel. (Cara termudah: jadikan folder `server` di komputer Anda menjadi `.zip`, upload ke File Manager, lalu ekstrak).
8. Jangan lupa membuat file `.env` di File Manager dan isi dengan konfigurasi database Anda.
9. Kembali ke menu **Setup Node.js App**, edit aplikasi Anda, dan klik tombol **Run NPM Install** untuk mengunduh semua kebutuhan server.
10. Klik **Restart** untuk menjalankan server.

### Tahap B: Frontend (Tampilan Vue.js)
Frontend adalah tampilan yang dilihat oleh pengguna/murid.

1. Di komputer Anda, buka terminal/CMD di dalam folder proyek Bimbel.
2. Karena aplikasi ini dibuat menggunakan Vue (terlihat dari `AdminPanel.vue` dan `postcss.config.cjs`), Anda harus "membangun" (build) aplikasinya terlebih dahulu agar bisa dibaca oleh browser.
3. Ketik perintah:
   ```bash
   npm run build
   ```
4. Tunggu sampai selesai. Akan muncul folder baru bernama `dist` (atau `build`).
5. Jadikan folder `dist` tersebut menjadi file `.zip`.
6. Kembali ke **File Manager** di cPanel IDwebhost.
7. Buka folder `public_html` (ini adalah folder utama untuk tampilan website Anda).
8. Upload file `.zip` tadi ke dalam folder `public_html`.
9. Ekstrak file `.zip` tersebut. Pastikan file `index.html` dan folder-folder lainnya berada persis di dalam `public_html`, bukan di dalam sub-folder.

> [!IMPORTANT]
> Pastikan Frontend Anda sudah dikonfigurasi untuk menembak ke URL Backend yang benar (misalnya `https://api.domainanda.com`) sebelum melakukan langkah `npm run build`.

---

## Selesai! 🎉

Sekarang coba buka nama domain website Anda di browser. Aplikasi Bimbel Online Anda seharusnya sudah bisa diakses. 

Jika ada error (seperti *503 Service Unavailable* atau database tidak terhubung):
1. Cek file `.env` di bagian backend.
2. Pastikan database (MySQL/MongoDB) sudah dibuat di cPanel IDwebhost dan koneksinya benar.
3. Cek catatan error di menu Node.js App.
