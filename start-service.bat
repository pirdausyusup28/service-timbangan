@echo off
title Timbangan Service

REM Cek apakah config.json ada
if not exist config.json (
    echo ❌ File config.json tidak ditemukan!
    echo.
    echo Buat file config.json dengan isi:
    echo {
    echo   "timbangan_id": 1,
    echo   "port_name": "COM7",
    echo   "api_port": 3001,
    echo   "ws_port": 8081
    echo }
    echo.
    pause
    exit /b 1
)

REM Tampilkan konfigurasi
echo ========================================
echo  Timbangan Service
echo ========================================
echo.
echo 📋 Konfigurasi:
type config.json
echo.
echo ========================================
echo.

REM Jalankan service
echo 🚀 Menjalankan service...
echo Tekan Ctrl+C untuk berhenti
echo.
node timbangan-service-exe.js

pause