@echo off
title EduPath - Hentikan Layanan Lokal
color 0C

echo.
echo Menghentikan service lokal EduPath...
taskkill /f /im php.exe 2>nul
taskkill /f /im node.exe /fi "WINDOWTITLE eq *Vite*" 2>nul
echo Service lokal selesai dihentikan.
timeout /t 2 > nul
exit

