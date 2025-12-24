<!DOCTYPE html>
<html>
<head>
    <title>Timbangan Realtime - WebSocket</title>
    <style>
        .timbangan { display: inline-block; margin: 20px; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        .weight { font-size: 2em; font-weight: bold; }
        .online { background: #d4edda; }
        .offline { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>4 Timbangan Realtime</h1>
    
    <div class="timbangan" id="timbangan1">
        <h3>Timbangan 1</h3>
        <div class="weight" id="weight1">0.00 kg</div>
        <div id="status1">Connecting...</div>
    </div>
    
    <div class="timbangan" id="timbangan2">
        <h3>Timbangan 2</h3>
        <div class="weight" id="weight2">0.00 kg</div>
        <div id="status2">Connecting...</div>
    </div>
    
    <div class="timbangan" id="timbangan3">
        <h3>Timbangan 3</h3>
        <div class="weight" id="weight3">0.00 kg</div>
        <div id="status3">Connecting...</div>
    </div>
    
    <div class="timbangan" id="timbangan4">
        <h3>Timbangan 4</h3>
        <div class="weight" id="weight4">0.00 kg</div>
        <div id="status4">Connecting...</div>
    </div>

    <script>
        // WebSocket connections untuk 4 timbangan
        const sockets = {};
        
        function connectTimbangan(id) {
            const ws = new WebSocket(`ws://192.168.1.10${id}:808${id}`); // Ganti IP sesuai PC
            
            ws.onopen = function() {
                document.getElementById(`status${id}`).textContent = 'Connected';
                document.getElementById(`timbangan${id}`).className = 'timbangan online';
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
                document.getElementById(`status${id}`).textContent = 'Disconnected';
                document.getElementById(`timbangan${id}`).className = 'timbangan offline';
                
                // Reconnect setelah 3 detik
                setTimeout(() => connectTimbangan(id), 3000);
            };
            
            sockets[id] = ws;
        }
        
        // Connect ke semua timbangan
        for (let i = 1; i <= 4; i++) {
            connectTimbangan(i);
        }
    </script>
</body>
</html>