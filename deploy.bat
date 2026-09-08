@echo off
title Auto Deploy EduPath ke cPanel
color 0B

echo ===================================================
echo   EDUPATH AUTO DEPLOYMENT (Build - Commit - Push)
echo ===================================================
echo.

:: 1. Mem-build Frontend
echo [1/3] Membangun (Build) file produksi terbaru...
call npm run build
if %errorlevel% neq 0 (
    color 0C
    echo.
    echo [ERROR] Gagal melakukan build. Silakan cek kode Anda.
    pause
    exit /b %errorlevel%
)
echo [OK] Build berhasil.
echo.

:: 2. Menambahkan file ke Git
echo [2/3] Menyiapkan file untuk dikirim ke GitHub...
git add .
echo [OK] File siap dikirim.
echo.

:: 3. Mengirim ke GitHub
echo [3/3] Mengirim file ke GitHub...
:: Meminta input catatan dari pengguna
set /p commit_msg="Masukkan catatan perubahan (tekan Enter untuk default 'Update fitur'): "
if "%commit_msg%"=="" set commit_msg=Update fitur

git commit -m "%commit_msg%"
git push
if %errorlevel% neq 0 (
    color 0C
    echo.
    echo [ERROR] Gagal mengirim ke GitHub. Pastikan internet Anda jalan.
    pause
    exit /b %errorlevel%
)

color 0A
echo.
echo ===================================================
echo  [SUKSES] KODE BERHASIL DIKIRIM KE GITHUB!
echo ===================================================
echo.
echo  Sekarang cPanel akan menarik update tersebut secara
echo  otomatis jika Webhook sudah disetting, ATAU Anda 
echo  bisa klik 'Pull' secara manual di cPanel.
echo.
pause
