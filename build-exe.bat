@echo off
echo Building Timbangan Service to EXE...

REM Install pkg if not installed
npm list -g pkg >nul 2>&1
if %errorlevel% neq 0 (
    echo Installing pkg...
    npm install -g pkg
)

REM Build EXE
echo Building timbangan-service.exe...
pkg timbangan-service.js --targets node18-win-x64 --output timbangan-service.exe

if exist timbangan-service.exe (
    echo.
    echo ✅ Build berhasil!
    echo File: timbangan-service.exe
    echo.
    echo Cara pakai:
    echo 1. Copy timbangan-service.exe ke PC masing-masing
    echo 2. Edit konfigurasi di dalam EXE tidak bisa, jadi:
    echo    - Buat file config.json di folder yang sama
    echo 3. Jalankan: timbangan-service.exe
    echo.
) else (
    echo ❌ Build gagal!
)

pause