@echo off
echo Building Timbangan Service EXE dengan Caxa...

REM Install caxa jika belum ada
npm list -g caxa >nul 2>&1
if %errorlevel% neq 0 (
    echo Installing caxa...
    npm install -g caxa
)

REM Build EXE
echo Building EXE...
caxa --input . --output timbangan-service.exe -- "{{caxa}}/node_modules/.bin/node" "{{caxa}}/timbangan-service-exe.js"

if exist timbangan-service.exe (
    echo.
    echo ✅ EXE berhasil dibuat!
    echo File: timbangan-service.exe (ukuran: ~56MB)
    echo.
    echo 📋 Cara deploy:
    echo 1. Copy timbangan-service.exe ke PC target
    echo 2. Buat config.json di folder yang sama
    echo 3. Jalankan: timbangan-service.exe
    echo.
    echo 📁 File yang dibutuhkan di PC target:
    echo   - timbangan-service.exe
    echo   - config.json
    echo.
) else (
    echo ❌ Build gagal!
)

pause