@echo off
echo ========================================
echo  Multi Timbangan Service Starter
echo ========================================
echo.

REM Buat folder untuk setiap timbangan
if not exist "timbangan_1" mkdir timbangan_1
if not exist "timbangan_2" mkdir timbangan_2
if not exist "timbangan_3" mkdir timbangan_3
if not exist "timbangan_4" mkdir timbangan_4

REM Copy file ke setiap folder dan set konfigurasi
echo Menyiapkan konfigurasi untuk 4 timbangan...

REM Timbangan 1
copy index.js timbangan_1\
copy HttpPusher.js timbangan_1\
copy FtpPusher.js timbangan_1\
copy package.json timbangan_1\
powershell -Command "(gc timbangan_1\index.js) -replace 'const TIMBANGAN_ID = 1;', 'const TIMBANGAN_ID = 1;' -replace 'const portName = ''COM7'';', 'const portName = ''COM7'';' | Out-File -encoding ASCII timbangan_1\index.js"

REM Timbangan 2
copy index.js timbangan_2\
copy HttpPusher.js timbangan_2\
copy FtpPusher.js timbangan_2\
copy package.json timbangan_2\
powershell -Command "(gc timbangan_2\index.js) -replace 'const TIMBANGAN_ID = 1;', 'const TIMBANGAN_ID = 2;' -replace 'const portName = ''COM7'';', 'const portName = ''COM8'';' | Out-File -encoding ASCII timbangan_2\index.js"

REM Timbangan 3
copy index.js timbangan_3\
copy HttpPusher.js timbangan_3\
copy FtpPusher.js timbangan_3\
copy package.json timbangan_3\
powershell -Command "(gc timbangan_3\index.js) -replace 'const TIMBANGAN_ID = 1;', 'const TIMBANGAN_ID = 3;' -replace 'const portName = ''COM7'';', 'const portName = ''COM9'';' | Out-File -encoding ASCII timbangan_3\index.js"

REM Timbangan 4
copy index.js timbangan_4\
copy HttpPusher.js timbangan_4\
copy FtpPusher.js timbangan_4\
copy package.json timbangan_4\
powershell -Command "(gc timbangan_4\index.js) -replace 'const TIMBANGAN_ID = 1;', 'const TIMBANGAN_ID = 4;' -replace 'const portName = ''COM7'';', 'const portName = ''COM10'';' | Out-File -encoding ASCII timbangan_4\index.js"

echo.
echo Menginstall dependencies untuk setiap timbangan...
cd timbangan_1 && npm install && cd ..
cd timbangan_2 && npm install && cd ..
cd timbangan_3 && npm install && cd ..
cd timbangan_4 && npm install && cd ..

echo.
echo ========================================
echo  Menjalankan 4 Service Timbangan
echo ========================================
echo  Timbangan 1: Port 3001, WebSocket 8081, COM7
echo  Timbangan 2: Port 3002, WebSocket 8082, COM8
echo  Timbangan 3: Port 3003, WebSocket 8083, COM9
echo  Timbangan 4: Port 3004, WebSocket 8084, COM10
echo ========================================
echo.

REM Jalankan semua service di background
start "Timbangan 1" cmd /k "cd timbangan_1 && node index.js"
timeout /t 2 /nobreak >nul

start "Timbangan 2" cmd /k "cd timbangan_2 && node index.js"
timeout /t 2 /nobreak >nul

start "Timbangan 3" cmd /k "cd timbangan_3 && node index.js"
timeout /t 2 /nobreak >nul

start "Timbangan 4" cmd /k "cd timbangan_4 && node index.js"

echo.
echo ✅ Semua service timbangan telah dijalankan!
echo.
echo 📊 Monitoring URLs:
echo    http://localhost:3001/api/weight (Timbangan 1)
echo    http://localhost:3002/api/weight (Timbangan 2)
echo    http://localhost:3003/api/weight (Timbangan 3)
echo    http://localhost:3004/api/weight (Timbangan 4)
echo.
echo 🔌 WebSocket URLs:
echo    ws://localhost:8081 (Timbangan 1)
echo    ws://localhost:8082 (Timbangan 2)
echo    ws://localhost:8083 (Timbangan 3)
echo    ws://localhost:8084 (Timbangan 4)
echo.
echo Tekan CTRL+C untuk menghentikan script ini
echo (Service akan tetap berjalan di window terpisah)
pause