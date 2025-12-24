<!DOCTYPE html>
<html>
<head>
    <title>Timbangan Realtime - WebSocket</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; }
        .timbangan { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        .weight { font-size: 3em; font-weight: bold; text-align: center; margin: 20px 0; }
        .status { padding: 10px; border-radius: 5px; text-align: center; margin: 10px 0; }
        .online { background: #4CAF50; color: white; }
        .offline { background: #f44336; color: white; }
        .connecting { background: #ff9800; color: white; }
        h1 { text-align: center; color: #333; }
        h3 { color: #666; margin-bottom: 15px; }
        .info { font-size: 0.9em; color: #666; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏗️ 4 Timbangan Realtime (WebSocket)</h1>
        
        <div class="grid">
            <div class="timbangan" id="timbangan1">
                <h3>Timbangan 1</h3>
                <div class="weight" id="weight1">0.00 kg</div>
                <div class="status connecting" id="status1">Connecting...</div>
                <div class="info">WebSocket: <span id="ws1">-</span></div>
            </div>
            
            <div class="timbangan" id="timbangan2">
                <h3>Timbangan 2</h3>
                <div class="weight" id="weight2">0.00 kg</div>
                <div class="status connecting" id="status2">Connecting...</div>
                <div class="info">WebSocket: <span id="ws2">-</span></div>
            </div>
            
            <div class="timbangan" id="timbangan3">
                <h3>Timbangan 3</h3>
                <div class="weight" id="weight3">0.00 kg</div>
                <div class="status connecting" id="status3">Connecting...</div>
                <div class="info">WebSocket: <span id="ws3">-</span></div>
            </div>
            
            <div class="timbangan" id="timbangan4">
                <h3>Timbangan 4</h3>
                <div class="weight" id="weight4">0.00 kg</div>
                <div class="status connecting" id="status4">Connecting...</div>
                <div class="info">WebSocket: <span id="ws4">-</span></div>
            </div>
        </div>
    </div>

    <script>
        // GANTI IP SESUAI PC MASING-MASING TIMBANGAN
        const timbanganConfig = {
            1: { ip: '192.168.1.101', port: 8081 }, // PC Timbangan 1
            2: { ip: '192.168.1.102', port: 8082 }, // PC Timbangan 2  
            3: { ip: '192.168.1.103', port: 8083 }, // PC Timbangan 3
            4: { ip: '192.168.1.104', port: 8084 }  // PC Timbangan 4
        };
        
        const sockets = {};
        
        function connectTimbangan(id) {
            const config = timbanganConfig[id];
            const wsUrl = `ws://${config.ip}:${config.port}`;
            
            document.getElementById(`ws${id}`).textContent = wsUrl;
            
            try {
                const ws = new WebSocket(wsUrl);
                
                ws.onopen = function() {
                    console.log(`Timbangan ${id} connected`);
                    document.getElementById(`status${id}`).textContent = 'Online';
                    document.getElementById(`status${id}`).className = 'status online';
                };
                
                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    if (data.type === 'weight_update') {
                        const weight = data.data;
                        document.getElementById(`weight${id}`).textContent = 
                            `${parseFloat(weight.value).toFixed(2)} ${weight.unit}`;
                    }
                };
                
                ws.onclose = function() {
                    console.log(`Timbangan ${id} disconnected`);
                    document.getElementById(`status${id}`).textContent = 'Offline';
                    document.getElementById(`status${id}`).className = 'status offline';
                    
                    // Reconnect setelah 3 detik
                    setTimeout(() => connectTimbangan(id), 3000);
                };
                
                ws.onerror = function(error) {
                    console.error(`Timbangan ${id} error:`, error);
                    document.getElementById(`status${id}`).textContent = 'Error';
                    document.getElementById(`status${id}`).className = 'status offline';
                };
                
                sockets[id] = ws;
                
            } catch (error) {
                console.error(`Failed to connect Timbangan ${id}:`, error);
                document.getElementById(`status${id}`).textContent = 'Failed';
                document.getElementById(`status${id}`).className = 'status offline';
                
                // Retry setelah 5 detik
                setTimeout(() => connectTimbangan(id), 5000);
            }
        }
        
        // Connect ke semua timbangan
        for (let i = 1; i <= 4; i++) {
            connectTimbangan(i);
        }
        
        // Cleanup saat page unload
        window.addEventListener('beforeunload', function() {
            Object.values(sockets).forEach(ws => {
                if (ws.readyState === WebSocket.OPEN) {
                    ws.close();
                }
            });
        });
    </script>
</body>
</html>