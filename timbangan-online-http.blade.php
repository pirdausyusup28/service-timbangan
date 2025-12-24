<!DOCTYPE html>
<html>
<head>
    <title>Timbangan Online - HTTP Polling</title>
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
        .info { font-size: 0.9em; color: #666; margin: 10px 0; }
        h1 { text-align: center; color: #333; }
        h3 { color: #555; margin-bottom: 20px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏗️ Timbangan Online (HTTP Polling)</h1>
        <p style="text-align: center; color: #666;">Update setiap 3 detik via HTTP API</p>
        
        <div class="grid">
            <div class="timbangan" id="timbangan1">
                <h3>Timbangan 1</h3>
                <div class="weight" id="weight1">0.00</div>
                <div class="unit">kg</div>
                <div class="status offline" id="status1">Loading...</div>
                <div class="info">API: http://203.0.113.100:3001</div>
            </div>
            
            <div class="timbangan" id="timbangan2">
                <h3>Timbangan 2</h3>
                <div class="weight" id="weight2">0.00</div>
                <div class="unit">kg</div>
                <div class="status offline" id="status2">Loading...</div>
                <div class="info">API: http://203.0.113.100:3002</div>
            </div>
            
            <div class="timbangan" id="timbangan3">
                <h3>Timbangan 3</h3>
                <div class="weight" id="weight3">0.00</div>
                <div class="unit">kg</div>
                <div class="status offline" id="status3">Loading...</div>
                <div class="info">API: http://203.0.113.100:3003</div>
            </div>
            
            <div class="timbangan" id="timbangan4">
                <h3>Timbangan 4</h3>
                <div class="weight" id="weight4">0.00</div>
                <div class="unit">kg</div>
                <div class="status offline" id="status4">Loading...</div>
                <div class="info">API: http://203.0.113.100:3004</div>
            </div>
        </div>
    </div>

    <script>
        // GANTI dengan IP PUBLIC kantor/lokasi timbangan
        const timbanganConfig = {
            1: { ip: '203.0.113.100', port: 3001 },  // IP Public + Port
            2: { ip: '203.0.113.100', port: 3002 },
            3: { ip: '203.0.113.100', port: 3003 },
            4: { ip: '203.0.113.100', port: 3004 }
        };
        
        // Update data via HTTP API
        async function updateTimbangan(id) {
            try {
                const config = timbanganConfig[id];
                const apiUrl = `http://${config.ip}:${config.port}/api/weight`;
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                if (data.success) {
                    const weight = data.data;
                    
                    // Update weight
                    document.getElementById(`weight${id}`).textContent = 
                        parseFloat(weight.value || 0).toFixed(2);
                    
                    // Update status
                    const statusEl = document.getElementById(`status${id}`);
                    if (weight.connected) {
                        statusEl.textContent = 'Online';
                        statusEl.className = 'status online';
                    } else {
                        statusEl.textContent = 'Offline';
                        statusEl.className = 'status offline';
                    }
                } else {
                    throw new Error('API returned error');
                }
                
            } catch (error) {
                console.error(`Timbangan ${id} error:`, error);
                document.getElementById(`status${id}`).textContent = 'Error';
                document.getElementById(`status${id}`).className = 'status offline';
            }
        }
        
        // Update semua timbangan setiap 3 detik
        setInterval(() => {
            for (let i = 1; i <= 4; i++) {
                updateTimbangan(i);
            }
        }, 3000);
        
        // Load pertama kali
        for (let i = 1; i <= 4; i++) {
            updateTimbangan(i);
        }
    </script>
</body>
</html>