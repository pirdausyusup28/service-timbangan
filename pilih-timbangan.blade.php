<!DOCTYPE html>
<html>
<head>
    <title>Pilih Timbangan</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .timbangan-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0; }
        .timbangan-card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; cursor: pointer; transition: transform 0.2s; }
        .timbangan-card:hover { transform: translateY(-5px); }
        .timbangan-card h3 { color: #333; margin-bottom: 15px; }
        .weight-display { font-size: 2em; font-weight: bold; color: #007bff; margin: 15px 0; }
        .status { padding: 8px 15px; border-radius: 20px; font-size: 0.9em; }
        .online { background: #d4edda; color: #155724; }
        .offline { background: #f8d7da; color: #721c24; }
        .info { font-size: 0.8em; color: #666; margin-top: 10px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🏗️ Pilih Timbangan</h1>
        <p>Klik timbangan untuk melihat data realtime</p>
        
        <div class="timbangan-grid">
            <div class="timbangan-card" onclick="pilihTimbangan(1)">
                <h3>Timbangan 1</h3>
                <div class="weight-display" id="weight1">0.00 kg</div>
                <div class="status offline" id="status1">Loading...</div>
                <div class="info">PC: 192.168.1.101 | Port: 3001</div>
            </div>
            
            <div class="timbangan-card" onclick="pilihTimbangan(2)">
                <h3>Timbangan 2</h3>
                <div class="weight-display" id="weight2">0.00 kg</div>
                <div class="status offline" id="status2">Loading...</div>
                <div class="info">PC: 192.168.1.102 | Port: 3002</div>
            </div>
            
            <div class="timbangan-card" onclick="pilihTimbangan(3)">
                <h3>Timbangan 3</h3>
                <div class="weight-display" id="weight3">0.00 kg</div>
                <div class="status offline" id="status3">Loading...</div>
                <div class="info">PC: 192.168.1.103 | Port: 3003</div>
            </div>
            
            <div class="timbangan-card" onclick="pilihTimbangan(4)">
                <h3>Timbangan 4</h3>
                <div class="weight-display" id="weight4">0.00 kg</div>
                <div class="status offline" id="status4">Loading...</div>
                <div class="info">PC: 192.168.1.104 | Port: 3004</div>
            </div>
        </div>
    </div>

    <script>
        // Update data setiap timbangan
        async function updateTimbangan(id) {
            try {
                // Akses langsung ke API timbangan (tanpa key)
                const response = await fetch(`http://192.168.1.10${id}:300${id}/api/weight`);
                const data = await response.json();
                
                if (data.success) {
                    const weight = data.data;
                    document.getElementById(`weight${id}`).textContent = 
                        `${parseFloat(weight.value).toFixed(2)} ${weight.unit}`;
                    
                    const statusEl = document.getElementById(`status${id}`);
                    if (weight.connected) {
                        statusEl.textContent = 'Online';
                        statusEl.className = 'status online';
                    } else {
                        statusEl.textContent = 'Offline';
                        statusEl.className = 'status offline';
                    }
                }
            } catch (error) {
                // Coba akses via Laravel (file lokal)
                try {
                    const response = await fetch(`/api/timbangan/${id}`);
                    const data = await response.json();
                    
                    if (data.success) {
                        const weight = data.data;
                        document.getElementById(`weight${id}`).textContent = 
                            `${parseFloat(weight.value).toFixed(2)} ${weight.unit}`;
                        
                        const statusEl = document.getElementById(`status${id}`);
                        statusEl.textContent = weight.connected ? 'Online (File)' : 'Offline';
                        statusEl.className = weight.connected ? 'status online' : 'status offline';
                    }
                } catch (error2) {
                    document.getElementById(`status${id}`).textContent = 'Error';
                    document.getElementById(`status${id}`).className = 'status offline';
                }
            }
        }
        
        // Pilih timbangan
        function pilihTimbangan(id) {
            window.location.href = `/timbangan/${id}/dashboard`;
        }
        
        // Update setiap 3 detik
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