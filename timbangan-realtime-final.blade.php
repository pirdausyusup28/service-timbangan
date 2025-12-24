<!DOCTYPE html>
<html>
<head>
    <title>Timbangan Realtime</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 1200px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0; }
        .timbangan { background: white; padding: 30px; border-radius: 15px; box-shadow: 0 5px 15px rgba(0,0,0,0.1); text-align: center; }
        .weight { font-size: 4em; font-weight: bold; margin: 20px 0; }
        .unit { font-size: 1.5em; color: #666; }
        .status { padding: 10px 20px; border-radius: 25px; font-weight: bold; margin: 15px 0; }
        .online { background: #4CAF50; color: white; }
        .offline { background: #f44336; color: white; }
        .connecting { background: #ff9800; color: white; }
        .info { font-size: 0.9em; color: #666; margin: 10px 0; }
        h1 { text-align: center; color: #333; }
        h3 { color: #555; margin-bottom: 20px; }
        .selector { text-align: center; margin: 20px 0; }
        .selector button { padding: 10px 20px; margin: 5px; border: none; border-radius: 5px; background: #007bff; color: white; cursor: pointer; }
        .selector button.active { background: #28a745; }
        .single-view { display: none; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏗️ Timbangan Realtime</h1>
        
        <!-- Selector View -->
        <div class="selector">
            <button onclick="showAll()" class="active" id="btn-all">Semua Timbangan</button>
            <button onclick="showSingle(1)" id="btn-1">Timbangan 1</button>
            <button onclick="showSingle(2)" id="btn-2">Timbangan 2</button>
            <button onclick="showSingle(3)" id="btn-3">Timbangan 3</button>
            <button onclick="showSingle(4)" id="btn-4">Timbangan 4</button>
        </div>
        
        <!-- All View -->
        <div id="all-view">
            <div class="grid">
                <div class="timbangan" id="timbangan1">
                    <h3>Timbangan 1</h3>
                    <div class="weight" id="weight1">0.00</div>
                    <div class="unit">kg</div>
                    <div class="status connecting" id="status1">Connecting...</div>
                    <div class="info">PC: 192.168.1.101 | WS: 8081</div>
                </div>
                
                <div class="timbangan" id="timbangan2">
                    <h3>Timbangan 2</h3>
                    <div class="weight" id="weight2">0.00</div>
                    <div class="unit">kg</div>
                    <div class="status connecting" id="status2">Connecting...</div>
                    <div class="info">PC: 192.168.1.102 | WS: 8082</div>
                </div>
                
                <div class="timbangan" id="timbangan3">
                    <h3>Timbangan 3</h3>
                    <div class="weight" id="weight3">0.00</div>
                    <div class="unit">kg</div>
                    <div class="status connecting" id="status3">Connecting...</div>
                    <div class="info">PC: 192.168.1.103 | WS: 8083</div>
                </div>
                
                <div class="timbangan" id="timbangan4">
                    <h3>Timbangan 4</h3>
                    <div class="weight" id="weight4">0.00</div>
                    <div class="unit">kg</div>
                    <div class="status connecting" id="status4">Connecting...</div>
                    <div class="info">PC: 192.168.1.104 | WS: 8084</div>
                </div>
            </div>
        </div>
        
        <!-- Single Views -->
        <div id="single-1" class="single-view">
            <div style="max-width: 400px; margin: 0 auto;">
                <div class="timbangan">
                    <h3>Timbangan 1</h3>
                    <div class="weight" id="single-weight1">0.00</div>
                    <div class="unit">kg</div>
                    <div class="status connecting" id="single-status1">Connecting...</div>
                    <div class="info">PC: 192.168.1.101 | Port: 3001 | WebSocket: 8081</div>
                </div>
            </div>
        </div>
        
        <div id="single-2" class="single-view">
            <div style="max-width: 400px; margin: 0 auto;">
                <div class="timbangan">
                    <h3>Timbangan 2</h3>
                    <div class="weight" id="single-weight2">0.00</div>
                    <div class="unit">kg</div>
                    <div class="status connecting" id="single-status2">Connecting...</div>
                    <div class="info">PC: 192.168.1.102 | Port: 3002 | WebSocket: 8082</div>
                </div>
            </div>
        </div>
        
        <div id="single-3" class="single-view">
            <div style="max-width: 400px; margin: 0 auto;">
                <div class="timbangan">
                    <h3>Timbangan 3</h3>
                    <div class="weight" id="single-weight3">0.00</div>
                    <div class="unit">kg</div>
                    <div class="status connecting" id="single-status3">Connecting...</div>
                    <div class="info">PC: 192.168.1.103 | Port: 3003 | WebSocket: 8083</div>
                </div>
            </div>
        </div>
        
        <div id="single-4" class="single-view">
            <div style="max-width: 400px; margin: 0 auto;">
                <div class="timbangan">
                    <h3>Timbangan 4</h3>
                    <div class="weight" id="single-weight4">0.00</div>
                    <div class="unit">kg</div>
                    <div class="status connecting" id="single-status4">Connecting...</div>
                    <div class="info">PC: 192.168.1.104 | Port: 3004 | WebSocket: 8084</div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // GANTI IP SESUAI PC TIMBANGAN
        const timbanganConfig = {
            1: { ip: '192.168.1.101', ws: 8081 },
            2: { ip: '192.168.1.102', ws: 8082 },
            3: { ip: '192.168.1.103', ws: 8083 },
            4: { ip: '192.168.1.104', ws: 8084 }
        };
        
        const sockets = {};
        
        // Connect WebSocket untuk setiap timbangan
        function connectTimbangan(id) {
            const config = timbanganConfig[id];
            const wsUrl = `ws://${config.ip}:${config.ws}`;
            
            try {
                const ws = new WebSocket(wsUrl);
                
                ws.onopen = function() {
                    console.log(`Timbangan ${id} connected`);
                    updateStatus(id, 'Online', 'online');
                };
                
                ws.onmessage = function(event) {
                    const data = JSON.parse(event.data);
                    if (data.type === 'weight_update') {
                        const weight = data.data;
                        updateWeight(id, weight.value, weight.unit);
                    }
                };
                
                ws.onclose = function() {
                    console.log(`Timbangan ${id} disconnected`);
                    updateStatus(id, 'Offline', 'offline');
                    
                    // Auto reconnect setelah 3 detik
                    setTimeout(() => connectTimbangan(id), 3000);
                };
                
                ws.onerror = function(error) {
                    console.error(`Timbangan ${id} error:`, error);
                    updateStatus(id, 'Error', 'offline');
                };
                
                sockets[id] = ws;
                
            } catch (error) {
                console.error(`Failed to connect Timbangan ${id}:`, error);
                updateStatus(id, 'Failed', 'offline');
                
                // Retry setelah 5 detik
                setTimeout(() => connectTimbangan(id), 5000);
            }
        }
        
        // Update weight display
        function updateWeight(id, value, unit) {
            const weightValue = parseFloat(value || 0).toFixed(2);
            
            // Update di grid view
            document.getElementById(`weight${id}`).textContent = weightValue;
            
            // Update di single view
            const singleWeight = document.getElementById(`single-weight${id}`);
            if (singleWeight) {
                singleWeight.textContent = weightValue;
            }
        }
        
        // Update status display
        function updateStatus(id, text, className) {
            // Update di grid view
            const statusEl = document.getElementById(`status${id}`);
            statusEl.textContent = text;
            statusEl.className = `status ${className}`;
            
            // Update di single view
            const singleStatus = document.getElementById(`single-status${id}`);
            if (singleStatus) {
                singleStatus.textContent = text;
                singleStatus.className = `status ${className}`;
            }
        }
        
        // View switcher
        function showAll() {
            document.getElementById('all-view').style.display = 'block';
            for (let i = 1; i <= 4; i++) {
                document.getElementById(`single-${i}`).style.display = 'none';
            }
            
            // Update button states
            document.querySelectorAll('.selector button').forEach(btn => btn.classList.remove('active'));
            document.getElementById('btn-all').classList.add('active');
        }
        
        function showSingle(id) {
            document.getElementById('all-view').style.display = 'none';
            for (let i = 1; i <= 4; i++) {
                document.getElementById(`single-${i}`).style.display = i === id ? 'block' : 'none';
            }
            
            // Update button states
            document.querySelectorAll('.selector button').forEach(btn => btn.classList.remove('active'));
            document.getElementById(`btn-${id}`).classList.add('active');
        }
        
        // Initialize connections
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