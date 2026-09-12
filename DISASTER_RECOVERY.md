# EduPath SaaS Disaster Recovery & Deployment Guide

## 1. Database Backup
Karena aplikasi berjalan di ekosistem Shared Hosting (cPanel) / standard LAMP/LEMP stack, mekanisme backup paling aman adalah menggunakan tools bawaan panel atau `mysqldump`.
**Dilarang menaruh script backup (PHP/Shell) yang terekspos di public web root (`public_html` atau direktori API ini) karena berisiko tinggi membocorkan *database dump*.**

**Prosedur Backup:**
1. Login ke panel server (cPanel/Plesk) atau via SSH.
2. Eksekusi ekspor: `mysqldump -u [user] -p [database_name] > backup_edupath_$(date +%F).sql`
3. Simpan file SQL tersebut di direktori yang **tidak dapat diakses oleh publik** (di luar `public_html`).

## 2. Restore Validation & Disaster Recovery
**Aturan Utama:** Dilarang melakukan *restore* langsung ke database *production* jika terjadi error minor. Restore hanya dilakukan pada skenario **Disaster Recovery** (kerusakan data masif atau kehilangan server).

**Prosedur Recovery (RTO/RPO bergantung pada jadwal cron backup hosting):**
1. Siapkan database staging/test yang kosong.
2. *Import* file SQL backup terakhir ke database staging.
3. Arahkan koneksi `.env` lokal ke database staging tersebut.
4. Verifikasi *foreign keys* (khususnya relasi UUID `tenants`, `users`, `user_roles`, `students`) dan integritas data SaaS.
5. Jika validasi lulus, jadwalkan *maintenance window* untuk melakukan restore ke production.

## 3. Migration Safety
Semua migrasi untuk EduPath SaaS didesain secara *idempotent* (menggunakan `IF NOT EXISTS` atau `INSERT IGNORE`).
**Cara Menjalankan Migrasi:**
1. Migrasi harus dijalankan berurutan (contoh: `phase3a_...sql`, `phase3b_...sql`, dst).
2. Eksekusi via PhpMyAdmin atau command line MySQL: `mysql -u root -p db_name < api/database/migrations/phase3a_tenant_foundation.sql`
3. Migrasi bersifat *non-destructive* (tidak melakukan DROP TABLE utama). Rollback untuk schema update (seperti `ALTER TABLE`) wajib dilakukan secara manual via SQL script kebalikan. Jangan melakukan *drop* pada environment produksi!

## 4. Deployment Safety
- **Environment Variables**: File `.env` **HARUS** di-ignore oleh Git. Deployment ke production wajib membuat file `.env` secara manual menggunakan panduan dari `.env.example`.
- **CORS & Security**: Pengaturan CORS di `config.php` telah terikat pada variabel lingkungan (APP_ENV). Pastikan `APP_ENV=production` saat deploy agar error trace dan origin liar diblokir otomatis.
- **Log Data**: Data *audit_logs* diamankan dengan constraint relasional. Tidak boleh menghapus log ini kecuali ada *retention policy* terpisah.

**PENTING**: Dokumentasi ini ditulis tanpa asumsi ketersediaan layanan *Cloud-native* mahal. Standar operasional ini sangat kompatibel dengan server Shared Hosting dan ekosistem PHP/MySQL yang ada.
