@echo off
title EduPath - Start All Services
color 0A

cd /d "%~dp0"

echo.
echo  ============================================
echo   EduPath - Menjalankan Semua Service Lokal
echo  ============================================
echo.

:: 1. Cek / Jalankan MySQL (XAMPP)
netstat -ano | findstr :3306 > nul
if %errorlevel% neq 0 (
  echo [1/3] Menjalankan MySQL database (XAMPP)...
  if exist "C:\xampp\mysql_start.bat" (
    start "EduPath MySQL" cmd /c "C:\xampp\mysql_start.bat"
  ) else (
    echo [PERINGATAN] XAMPP MySQL tidak ditemukan di C:\xampp. Pastikan MySQL sudah running.
  )
  ping -n 3 127.0.0.1 > nul
) else (
  echo [1/3] MySQL port 3306 sudah aktif.
)

:: 2. Jalankan PHP Backend Server (Port 8000)
netstat -ano | findstr :8000 > nul
if %errorlevel% neq 0 (
  echo [2/3] Menjalankan PHP Backend Server (port 8000)...
  start "EduPath Backend (PHP 8000)" cmd /k "cd /d "%~dp0" && php -S localhost:8000"
  ping -n 2 127.0.0.1 > nul
) else (
  echo [2/3] PHP Backend di port 8000 sudah aktif.
)

:: 3. Jalankan Vite Frontend Dev Server (Port 5173)
echo [3/3] Menjalankan Frontend Vite...
start "EduPath Frontend (Vite)" cmd /k "cd /d "%~dp0" && npm run dev"

echo.
echo  ============================================
echo   Semua service berjalan!
echo.
echo   Frontend : http://localhost:5173
echo   Backend  : http://localhost:8000
echo   Database : localhost:3306 (MySQL)
echo  ============================================
echo.
echo  Browser akan terbuka otomatis dalam 3 detik...
ping -n 4 127.0.0.1 > nul
start http://localhost:5173
exit
