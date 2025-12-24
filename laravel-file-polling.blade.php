<!DOCTYPE html>
<html>
<head>
    <title>Timbangan Realtime - File Polling</title>
    <style>
        .timbangan { display: inline-block; margin: 20px; padding: 20px; border: 1px solid #ccc; border-radius: 10px; }
        .weight { font-size: 2em; font-weight: bold; }
        .online { background: #d4edda; }
        .offline { background: #f8d7da; }
    </style>
</head>
<body>
    <h1>4 Timbangan Realtime (File Polling)</h1>
    
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
        async function updateAllTimbangan() {
            try {
                const response = await fetch('/api/timbangan/all');
                const result = await response.json();
                
                if (result.success) {
                    for (let i = 1; i <= 4; i++) {
                        const weight = result.data[i];
                        
                        document.getElementById(`weight${i}`).textContent = 
                            `${parseFloat(weight.value).toFixed(2)} ${weight.unit}`;
                        
                        document.getElementById(`status${i}`).textContent = 
                            weight.connected ? 'Online' : 'Offline';
                        
                        document.getElementById(`timbangan${i}`).className = 
                            weight.connected ? 'timbangan online' : 'timbangan offline';
                    }
                }
            } catch (error) {
                console.error('Error:', error);
            }
        }
        
        // Update setiap 2 detik (file polling lebih lambat)
        setInterval(updateAllTimbangan, 2000);
        
        // Load pertama kali
        updateAllTimbangan();
    </script>
</body>
</html>