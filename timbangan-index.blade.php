<!DOCTYPE html>
<html>
<head>
    <title>Pilih Timbangan</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { max-width: 800px; margin: 0 auto; }
        .grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 20px; margin: 20px 0; }
        .card { background: white; padding: 30px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); text-align: center; cursor: pointer; transition: transform 0.2s; }
        .card:hover { transform: translateY(-5px); }
        .card h3 { color: #333; margin-bottom: 15px; }
        .weight { font-size: 2em; font-weight: bold; color: #007bff; margin: 15px 0; }
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
        
        <div class="grid">
            @foreach($timbanganConfig as $id => $config)
            <div class="card" onclick="pilihTimbangan({{ $id }})">
                <h3>Timbangan {{ $id }}</h3>
                <div class="weight" id="weight{{ $id }}">0.00 kg</div>
                <div class="status offline" id="status{{ $id }}">Loading...</div>
                <div class="info">{{ $config['ip'] }} | Port: {{ $config['port'] }}</div>
            </div>
            @endforeach
        </div>
    </div>

    <script>
        // Update data via controller
        async function updateTimbangan(id) {
            try {
                const response = await fetch(`/api/timbangan/${id}/data`);
                const result = await response.json();
                
                if (result.success && result.data) {
                    const weight = result.data;
                    document.getElementById(`weight${id}`).textContent = 
                        `${parseFloat(weight.value || 0).toFixed(2)} ${weight.unit || 'kg'}`;
                    
                    const statusEl = document.getElementById(`status${id}`);
                    if (weight.connected) {
                        statusEl.textContent = `Online (${result.source})`;
                        statusEl.className = 'status online';
                    } else {
                        statusEl.textContent = 'Offline';
                        statusEl.className = 'status offline';
                    }
                } else {
                    document.getElementById(`status${id}`).textContent = 'Error';
                    document.getElementById(`status${id}`).className = 'status offline';
                }
            } catch (error) {
                document.getElementById(`status${id}`).textContent = 'Error';
                document.getElementById(`status${id}`).className = 'status offline';
            }
        }
        
        function pilihTimbangan(id) {
            window.location.href = `/timbangan/${id}`;
        }
        
        // Update setiap 3 detik
        setInterval(() => {
            @foreach($timbanganConfig as $id => $config)
            updateTimbangan({{ $id }});
            @endforeach
        }, 3000);
        
        // Load pertama kali
        @foreach($timbanganConfig as $id => $config)
        updateTimbangan({{ $id }});
        @endforeach
    </script>
</body>
</html>