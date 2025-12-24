@echo off
echo Setup 4 Timbangan Service...

REM Buat folder
for %%i in (1,2,3,4) do (
    if not exist "timbangan_%%i" mkdir timbangan_%%i
    copy index.js timbangan_%%i\
    copy HttpPusher.js timbangan_%%i\
    copy FtpPusher.js timbangan_%%i\
    copy package.json timbangan_%%i\
    cd timbangan_%%i && npm install && cd ..
)

REM Update konfigurasi untuk setiap timbangan
powershell -Command "(gc timbangan_1\index.js) -replace 'const TIMBANGAN_ID = 1;', 'const TIMBANGAN_ID = 1;' | Out-File -encoding ASCII timbangan_1\index.js"

powershell -Command "(gc timbangan_2\index.js) -replace 'const TIMBANGAN_ID = 1;', 'const TIMBANGAN_ID = 2;' -replace 'const portName = ''COM7'';', 'const portName = ''COM8'';' | Out-File -encoding ASCII timbangan_2\index.js"

powershell -Command "(gc timbangan_3\index.js) -replace 'const TIMBANGAN_ID = 1;', 'const TIMBANGAN_ID = 3;' -replace 'const portName = ''COM7'';', 'const portName = ''COM9'';' | Out-File -encoding ASCII timbangan_3\index.js"

powershell -Command "(gc timbangan_4\index.js) -replace 'const TIMBANGAN_ID = 1;', 'const TIMBANGAN_ID = 4;' -replace 'const portName = ''COM7'';', 'const portName = ''COM10'';' | Out-File -encoding ASCII timbangan_4\index.js"

echo Setup selesai!
echo.
echo Jalankan service:
echo   cd timbangan_1 && node index.js  (Port 3001, WS 8081, COM7)
echo   cd timbangan_2 && node index.js  (Port 3002, WS 8082, COM8)  
echo   cd timbangan_3 && node index.js  (Port 3003, WS 8083, COM9)
echo   cd timbangan_4 && node index.js  (Port 3004, WS 8084, COM10)
pause