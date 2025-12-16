<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Timbangan Realtime - WebSocket</title>
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
            transition: all 0.3s ease;
        }
        .weight-display.updated {
            border-color: #28a745;
            box-shadow: 0 0 10px rgba(40, 167, 69, 0.3);
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
        .realtime-indicator {
            display: inline-block;
            width: 10px;
            height: 10px;
            border-radius: 50%;
            background-color: #28a745;
            animation: pulse 1s infinite;
        }
        @keyframes pulse {
            0% { opacity: 1; }
            50% { opacity: 0.5; }
            100% { opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>
            Monitoring Timbangan Realtime 
            <span class="realtime-indicator" id="realtime-indicator"></span>
        </h1>
        
        <!-- Status Koneksi -->
        <div id="connection-status" class="status disconnected">
            Menghubungkan...
        </div>
        
        <!-- Display Berat -->
        <div class="weight-display" id="weight-display">
            <div id="weight-value">0.00</div>
            <div id="weight-unit">kg</div>
        </div>
        
        <!-- Info Tambahan -->
        <div class="info">
            <p><strong>Timestamp:</strong> <span id="timestamp">-</span></p>
            <p><strong>Raw Data:</strong> <span id="raw-data">-</span></p>
            <p><strong>Status:</strong> <span id="status">-</span></p>
            <p><strong>WebSocket:</strong> <span id="ws-status">Connecting...</span></p>
        </div>
        
        <!-- Kontrol -->
        <div class="controls">
            <button id="btn-zero" class="btn btn-warning">Zero/Tare</button>
            <button id="btn-request" class="btn btn-primary">Request Data</button>
            <button id="btn-reconnect" class="btn btn-secondary">Reconnect WS</button>
        </div>
    </div>

    <script>
        $(document).ready(function() {
            const API_BASE = 'http://localhost:3000/api';
            const WS_URL = 'ws://localhost:8080';
            let ws = null;
            let isConnected = false;
            let reconnectInterval = null;
            
            // Fungsi untuk update tampilan
            function updateDisplay(data) {
                $('#weight-value').text(data.value.toFixed(2));
                $('#weight-unit').text(data.unit);
                $('#timestamp').text(new Date(data.timestamp).toLocaleString('id-ID'));
                $('#raw-data').text(data.raw);
                $('#status').text(data.status);
                
                // Animasi update
                $('#weight-display').addClass('updated');
                setTimeout(() => {
                    $('#weight-display').removeClass('updated');
                }, 300);
                
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
            
            // Fungsi untuk koneksi WebSocket
            function connectWebSocket() {
                try {
                    ws = new WebSocket(WS_URL);
                    
                    ws.onopen = function() {
                        console.log('WebSocket terhubung');
                        $('#ws-status').text('Connected').css('color', 'green');
                        $('#realtime-indicator').show();
                        
                        // Clear reconnect interval jika ada
                        if (reconnectInterval) {
                            clearInterval(reconnectInterval);
                            reconnectInterval = null;
                        }
                    };
                    
                    ws.onmessage = function(event) {
                        try {
                            const message = JSON.parse(event.data);
                            if (message.type === 'weight_update') {
                                updateDisplay(message.data);
                            }
                        } catch (e) {
                            console.error('Error parsing WebSocket message:', e);
                        }
                    };
                    
                    ws.onclose = function() {
                        console.log('WebSocket terputus');
                        $('#ws-status').text('Disconnected').css('color', 'red');
                        $('#realtime-indicator').hide();
                        
                        // Auto reconnect setiap 5 detik
                        if (!reconnectInterval) {
                            reconnectInterval = setInterval(() => {
                                console.log('Mencoba reconnect WebSocket...');
                                connectWebSocket();
                            }, 5000);
                        }
                    };
                    
                    ws.onerror = function(error) {
                        console.error('WebSocket error:', error);
                        $('#ws-status').text('Error').css('color', 'orange');
                    };
                    
                } catch (e) {
                    console.error('Error creating WebSocket:', e);
                    $('#ws-status').text('Error').css('color', 'red');
                }
            }
            
            // Fungsi fallback dengan polling jika WebSocket gagal
            function startPolling() {
                setInterval(() => {
                    if (!ws || ws.readyState !== WebSocket.OPEN) {
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
                            }
                        });
                    }
                }, 2000); // Polling setiap 2 detik sebagai fallback
            }
            
            // Inisialisasi
            connectWebSocket();
            startPolling(); // Fallback polling
            
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
            
            $('#btn-reconnect').click(function() {
                if (ws) {
                    ws.close();
                }
                connectWebSocket();
            });
        });
    </script>
</body>
</html>