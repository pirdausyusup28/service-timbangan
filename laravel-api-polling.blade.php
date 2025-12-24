<!DOCTYPE html>
<html>
<head>
    <title>Timbangan Realtime - API Polling</title>
    <style>
        .timbangan { display: inline-block; margin: 20px; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        .weight { font-size: 2em; font-weight: bold; }
        .online { background: #d4edda; }
        .offline { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>4 Timbangan Realtime (API Polling)</h1>
    
    <div class="timbangan" id="timbangan1">
        <h3>Timbangan 1</h3>
        <div class="weight" id="weight1">0.00 kg</div>
        <div id="status1">Loading...</div>
    </div>
    
    <div class="timbangan" id="timbangan2">
        <h3>Timbangan 2</h3>
        <div class="weight" id="weight2">0.00 kg</div>
        <div id="status2">Loading...</div>
    </div>
    
    <div class="timbangan" id="timbangan3">
        <h3>Timbangan 3</h3>
        <div class="weight" id="weight3">0.00 kg</div>
        <div id="status3">Loading...</div>
    </div>
    
    <div class="timbangan" id="timbangan4">
        <h3>Timbangan 4</h3>
        <div class="weight" id="weight4">0.00 kg</div>
        <div id="status4">Loading...</div>
    </div>

    <script>
        async function updateTimbangan(id) {
            try {
                const response = await fetch(`http://192.168.1.10${id}:300${id}/api/weight`); // Ganti IP
                const data = await response.json();
                
                if (data.success) {
                    const weight = data.data;
                    document.getElementById(`weight${id}`).textContent = 
                        `${parseFloat(weight.value).toFixed(2)} ${weight.unit}`;
                    
                    document.getElementById(`status${id}`).textContent = 
                        weight.connected ? 'Online' : 'Offline';
                    
                    document.getElementById(`timbangan${id}`).className = 
                        weight.connected ? 'timbangan online' : 'timbangan offline';
                }
            } catch (error) {
                document.getElementById(`status${id}`).textContent = 'Error';
                document.getElementById(`timbangan${id}`).className = 'timbangan offline';
            }
        }
        
        // Update setiap 1 detik
        setInterval(() => {
            for (let i = 1; i <= 4; i++) {
                updateTimbangan(i);
            }
        }, 1000);
        
        // Load pertama kali
        for (let i = 1; i <= 4; i++) {
            updateTimbangan(i);
        }
    </script>
</body>
</html>