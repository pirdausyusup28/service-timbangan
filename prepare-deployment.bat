@echo off
echo Preparing deployment files untuk 4 PC...

REM Buat folder untuk setiap PC
for %%i in (1,2,3,4) do (
    if not exist "deploy-pc%%i" mkdir "deploy-pc%%i"
    
    REM Copy EXE
    copy timbangan-service.exe "deploy-pc%%i\"
    
    REM Buat config.json untuk setiap PC
    echo Creating config for PC %%i...
    (
        echo {
        echo   "timbangan_id": %%i,
        echo   "port_name": "COM7",
        echo   "api_port": 300%%i,
        echo   "ws_port": 808%%i,
        echo   "baud_rates": [2400, 4800, 9600, 19200],
        echo   "enable_http_push": true,
        echo   "enable_ftp_push": false,
        echo   "http_config": {
        echo     "baseUrl": "https://yourdomain.com",
        echo     "apiKey": "your-secret-api-key-123",
        echo     "endpoint": "/api/timbangan/update",
        echo     "timeout": 10000,
        echo     "retryAttempts": 3,
        echo     "retryDelay": 3000
        echo   }
        echo }
    ) > "deploy-pc%%i\config.json"
    
    REM Buat launcher
    (
        echo @echo off
        echo title Timbangan Service PC %%i
        echo echo Starting Timbangan %%i...
        echo timbangan-service.exe
        echo pause
    ) > "deploy-pc%%i\start.bat"
)

echo.
echo ✅ Deployment files ready!
echo.
echo 📁 Folders created:
echo   - deploy-pc1\ (Timbangan 1, Port 3001, WS 8081)
echo   - deploy-pc2\ (Timbangan 2, Port 3002, WS 8082)  
echo   - deploy-pc3\ (Timbangan 3, Port 3003, WS 8083)
echo   - deploy-pc4\ (Timbangan 4, Port 3004, WS 8084)
echo.
echo 📋 Each folder contains:
echo   - timbangan-service.exe
echo   - config.json (pre-configured)
echo   - start.bat (launcher)
echo.
echo 🔧 Edit config.json in each folder to set:
echo   - baseUrl (your domain)
echo   - apiKey (your secret key)
echo   - port_name (COM port for each PC)
echo.
pause