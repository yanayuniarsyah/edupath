@echo off
title Auto Deploy FTP EduPath
color 0B

echo ===================================================
echo   EDUPATH DIRECT AUTO DEPLOYMENT (Build ^& FTP)
echo ===================================================
echo.

:: 1. Mem-build Frontend
echo [1/2] Membangun (Build) file produksi terbaru...
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

:: 2. Upload via FTP
echo [2/2] Mengunggah file langsung ke IDwebhost via FTP...
node deploy-ftp.js
if %errorlevel% neq 0 (
    color 0C
    echo.
    echo [ERROR] Gagal mengunggah file. Pastikan .env Anda benar!
    pause
    exit /b %errorlevel%
)

color 0A
echo.
echo ===================================================
echo  [SUKSES] KODE BERHASIL DI-UPLOAD KE SERVER!
echo ===================================================
echo.
echo  Sekarang web Anda di IDwebhost sudah pasti 100%% 
echo  terupdate tanpa bergantung pada Git cPanel.
echo.
pause
