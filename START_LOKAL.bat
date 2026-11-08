@echo off
title EduPath - Startup Lokal 1-Klik
color 0A

cd /d "%~dp0"

echo.
echo ============================================================
echo   EduPath - Sistem Bimbel Online dan Offline
echo   Menjalankan Layanan Lokal 1-Klik
echo ============================================================
echo.

:: 1. Database MySQL
netstat -ano | findstr :3306 > nul
if %errorlevel% equ 0 goto mysql_ready
echo [1/3] Menyalakan MySQL XAMPP Port 3306...
if exist "C:\xampp\mysql\bin\mysqld.exe" (
    start "EduPath MySQL Database" "C:\xampp\mysql\bin\mysqld.exe" --defaults-file=C:\xampp\mysql\bin\my.ini --standalone
) else (
    echo [PERINGATAN] mysqld.exe tidak ditemukan di C:\xampp. Pastikan MySQL sudah aktif.
)
ping -n 3 127.0.0.1 > nul
goto check_php

:mysql_ready
echo [1/3] Database MySQL Port 3306 sudah AKTIF.

:check_php
:: 2. PHP Backend
netstat -ano | findstr :8000 > nul
if %errorlevel% equ 0 goto php_ready
echo [2/3] Menyalakan PHP Backend Server Port 8000...
start "EduPath Backend PHP" cmd /k "php -S localhost:8000 router.php"
ping -n 3 127.0.0.1 > nul
goto check_vite

:php_ready
echo [2/3] PHP Backend Server Port 8000 sudah AKTIF.

:check_vite
:: 3. Frontend Vite
netstat -ano | findstr :5173 > nul
if %errorlevel% equ 0 goto vite_ready
echo [3/3] Menyalakan Frontend Vite Dev Server Port 5173...
start "EduPath Frontend Vite" cmd /k "npm run dev"
ping -n 4 127.0.0.1 > nul
goto finish

:vite_ready
echo [3/3] Frontend Vite Port 5173 sudah AKTIF.

:finish
echo.
echo ============================================================
echo   SEMUA LAYANAN BERJALAN DENGAN SUKSES!
echo.
echo   - Frontend : http://localhost:5173
echo   - Backend  : http://localhost:8000
echo   - Database : localhost:3306 - MySQL
echo ============================================================
echo.
echo Membuka browser dalam 2 detik...
ping -n 3 127.0.0.1 > nul
start http://localhost:5173
exit
