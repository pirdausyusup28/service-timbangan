<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timbangan Realtime</title>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <style>
        .weight-display {
            font-size: 3rem;
            font-weight: bold;
            text-align: center;
            padding: 2rem;
            border: 2px solid #ddd;
            border-radius: 10px;
            margin: 2rem 0;
        }
        .status {
            padding: 0.5rem 1rem;
            border-radius: 5px;
            color: white;
            font-weight: bold;
        }
        .connected { background-color: #28a745; }
        .disconnected { background-color: #dc3545; }
        .error { background-color: #ffc107; color: black; }
    </style>
</head>
<body>
    <div class="container">
        <h1>Monitoring Timbangan Realtime</h1>
        
        <!-- Status Koneksi -->
        <div id="connection-status" class="status disconnected">
            Menghubungkan...
        </div>
        
        <!-- Display Berat -->
        <div class="weight-display">
            <div id="weight-value">0.00</div>
            <div id="weight-unit">kg</div>
        </div>
        
        <!-- Info Tambahan -->
        <div class="info">
            <p><strong>Timestamp:</strong> <span id="timestamp">-</span></p>
            <p><strong>Raw Data:</strong> <span id="raw-data">-</span></p>
            <p><strong>Status:</strong> <span id="status">-</span></p>
        </div>
        
        <!-- Kontrol -->
        <div class="controls">
            <button id="btn-zero" class="btn btn-warning">Zero/Tare</button>
            <button id="btn-request" class="btn btn-primary">Request Data</button>
            <button id="btn-refresh" class="btn btn-secondary">Refresh Manual</button>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const API_BASE = 'http://localhost:3000/api';
            let isConnected = false;
            
            // Fungsi untuk update tampilan
            function updateDisplay(data) {
                $('#weight-value').text(data.value.toFixed(2));
                $('#weight-unit').text(data.unit);
                $('#timestamp').text(new Date(data.timestamp).toLocaleString('id-ID'));
                $('#raw-data').text(data.raw);
                $('#status').text(data.status);
                
                // Update status koneksi
                if (data.connected) {
                    $('#connection-status')
                        .removeClass('disconnected error')
                        .addClass('connected')
                        .text('Terhubung');
                    isConnected = true;
                } else {
                    $('#connection-status')
                        .removeClass('connected')
                        .addClass('disconnected')
                        .text('Terputus');
                    isConnected = false;
                }
            }
            
            // Fungsi untuk fetch data dari API
            function fetchWeight() {
                $.ajax({
                    url: API_BASE + '/weight',
                    method: 'GET',
                    timeout: 5000,
                    success: function(response) {
                        if (response.success) {
                            updateDisplay(response.data);
                        }
                    },
                    error: function(xhr, status, error) {
                        console.error('Error fetching weight:', error);
                        $('#connection-status')
                            .removeClass('connected disconnected')
                            .addClass('error')
                            .text('Error API');
                    }
                });
            }
            
            // Polling setiap 1 detik
            setInterval(fetchWeight, 1000);
            
            // Fetch pertama kali
            fetchWeight();
            
            // Event handlers untuk tombol
            $('#btn-zero').click(function() {
                if (!isConnected) {
                    alert('Timbangan tidak terhubung!');
                    return;
                }
                
                $.ajax({
                    url: API_BASE + '/zero',
                    method: 'POST',
                    success: function(response) {
                        if (response.success) {
                            alert('Perintah Zero/Tare berhasil dikirim');
                        }
                    },
                    error: function() {
                        alert('Gagal mengirim perintah Zero/Tare');
                    }
                });
            });
            
            $('#btn-request').click(function() {
                if (!isConnected) {
                    alert('Timbangan tidak terhubung!');
                    return;
                }
                
                $.ajax({
                    url: API_BASE + '/request',
                    method: 'POST',
                    success: function(response) {
                        if (response.success) {
                            alert('Request data berhasil dikirim');
                        }
                    },
                    error: function() {
                        alert('Gagal mengirim request data');
                    }
                });
            });
            
            $('#btn-refresh').click(function() {
                fetchWeight();
            });
        });
    </script>
</body>
</html>