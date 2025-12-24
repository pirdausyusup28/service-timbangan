@echo off
title Timbangan Service Installer

echo ========================================
echo  Timbangan Service Portable Installer
echo ========================================
echo.

REM Cek apakah Node.js terinstall
node --version >nul 2>&1
if %errorlevel% neq 0 (
    echo ❌ Node.js tidak terinstall!
    echo.
    echo Silakan download dan install Node.js dari:
    echo https://nodejs.org/
    echo.
    pause
    exit /b 1
)

echo ✅ Node.js terdeteksi
node --version

REM Buat folder service
set SERVICE_DIR=%~dp0timbangan-service
if not exist "%SERVICE_DIR%" mkdir "%SERVICE_DIR%"

REM Copy files
echo.
echo 📁 Menyalin files...
copy timbangan-service-exe.js "%SERVICE_DIR%\timbangan-service.js" >nul
copy config.json "%SERVICE_DIR%\" >nul
copy package.json "%SERVICE_DIR%\" >nul

REM Install dependencies
echo 📦 Installing dependencies...
cd "%SERVICE_DIR%"
npm install --production

REM Buat shortcut untuk menjalankan
echo.
echo 🔧 Membuat launcher...
echo @echo off > start-timbangan.bat
echo title Timbangan Service >> start-timbangan.bat
echo cd /d "%SERVICE_DIR%" >> start-timbangan.bat
echo node timbangan-service.js >> start-timbangan.bat
echo pause >> start-timbangan.bat

echo.
echo ✅ Instalasi selesai!
echo.
echo 📋 Cara pakai:
echo 1. Edit config.json untuk set ID timbangan dan COM port
echo 2. Jalankan: start-timbangan.bat
echo 3. Copy folder ini ke PC lain (sudah portable)
echo.
echo 📁 Folder service: %SERVICE_DIR%
echo.
pause