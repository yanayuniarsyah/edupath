@echo off
title EduPath - Start All Services
color 0A

cd /d "%~dp0"

echo.
echo  ============================================
echo   EduPath - Menjalankan Semua Service
echo  ============================================
echo.

:: ── Install frontend dependencies jika belum ──
if not exist node_modules (
  echo [1/3] Install frontend dependencies...
  call npm install
)

:: ── Install backend dependencies jika belum ──
if not exist server\node_modules (
  echo [2/3] Install backend dependencies...
  cd server
  call npm install
  cd ..
)

echo.
echo [OK] Menjalankan server backend di port 3001...
start "EduPath Backend (port 3001)" cmd /k "cd /d "%~dp0server" && node server.js"

:: Jeda agar backend sempat start
ping -n 3 127.0.0.1 > nul

echo [OK] Menjalankan Vite dev server...
start "EduPath Frontend (Vite)" cmd /k "cd /d "%~dp0" && npm run dev"

echo.
echo  ============================================
echo   Semua service berjalan!
echo.
echo   Frontend : http://localhost:5173
echo   Backend  : http://localhost:3001
echo   API Test : http://localhost:3001/api/health
echo  ============================================
echo.
echo  Tutup jendela ini jika mau. Service tetap jalan.
pause
