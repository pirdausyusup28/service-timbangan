@extends('layouts.app')

@section('title', 'Monitoring Timbangan Realtime')

@section('content')
<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h3 class="mb-0">
                        <i class="fas fa-weight"></i> Monitoring Timbangan Realtime
                        <span class="realtime-indicator ms-2" id="realtime-indicator"></span>
                    </h3>
                    <div>
                        <span id="connection-status" class="badge bg-secondary">Menghubungkan...</span>
                        <span id="ws-status" class="badge bg-info ms-1">WebSocket: Connecting...</span>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Display Berat Utama -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="weight-display text-center p-4 border rounded" id="weight-display">
                                <div class="display-1 fw-bold" id="weight-value">0.00</div>
                                <div class="h4 text-muted" id="weight-unit">kg</div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Informasi Detail -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Informasi Data</h6>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>Timestamp:</strong></td>
                                            <td id="timestamp">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Status:</strong></td>
                                            <td id="status">-</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Raw Data:</strong></td>
                                            <td id="raw-data" class="font-monospace">-</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="card bg-light">
                                <div class="card-body">
                                    <h6 class="card-title">Kontrol Timbangan</h6>
                                    <div class="d-grid gap-2">
                                        <button id="btn-zero" class="btn btn-warning">
                                            <i class="fas fa-balance-scale"></i> Zero/Tare
                                        </button>
                                        <button id="btn-request" class="btn btn-primary">
                                            <i class="fas fa-sync"></i> Request Data
                                        </button>
                                        <button id="btn-reconnect" class="btn btn-secondary">
                                            <i class="fas fa-plug"></i> Reconnect WebSocket
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Custom Command -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card">
                                <div class="card-body">
                                    <h6 class="card-title">Perintah Custom</h6>
                                    <div class="input-group">
                                        <input type="text" id="custom-command" class="form-control" 
                                               placeholder="Masukkan perintah (P, W, S, Z, dll.)">
                                        <button id="btn-send-command" class="btn btn-outline-primary">
                                            <i class="fas fa-paper-plane"></i> Kirim
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
    .weight-display {
        transition: all 0.3s ease;
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    .weight-display.updated {
        border-color: #28a745 !important;
        box-shadow: 0 0 20px rgba(40, 167, 69, 0.3);
        background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);
    }
    .realtime-indicator {
        display: inline-block;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background-color: #28a745;
        animation: pulse 1s infinite;
    }
    @keyframes pulse {
        0% { opacity: 1; transform: scale(1); }
        50% { opacity: 0.5; transform: scale(1.1); }
        100% { opacity: 1; transform: scale(1); }
    }
    .font-monospace {
        font-family: 'Courier New', monospace;
        font-size: 0.9em;
    }
</style>
@endpush

@push('scripts')
<script>
$(document).ready(function() {
    // Konfigurasi
    const config = {
        apiBase: '{{ url("/api/weight") }}',
        wsUrl: 'ws://localhost:8080',
        pollingInterval: 2000,
        wsReconnectInterval: 5000
    };
    
    let ws = null;
    let isConnected = false;
    let reconnectInterval = null;
    let pollingInterval = null;
    
    // Fungsi untuk update tampilan
    function updateDisplay(data) {
        $('#weight-value').text(parseFloat(data.value).toFixed(2));
        $('#weight-unit').text(data.unit);
        $('#timestamp').text(new Date(data.timestamp).toLocaleString('id-ID'));
        $('#raw-data').text(data.raw || '-');
        $('#status').text(data.status);
        
        // Animasi update
        $('#weight-display').addClass('updated');
        setTimeout(() => $('#weight-display').removeClass('updated'), 500);
        
        // Update status koneksi
        updateConnectionStatus(data.connected);
    }
    
    function updateConnectionStatus(connected) {
        isConnected = connected;
        const statusEl = $('#connection-status');
        
        if (connected) {
            statusEl.removeClass('bg-danger bg-secondary')
                   .addClass('bg-success')
                   .text('Terhubung');
        } else {
            statusEl.removeClass('bg-success bg-secondary')
                   .addClass('bg-danger')
                   .text('Terputus');
        }
        
        // Enable/disable buttons
        $('#btn-zero, #btn-request, #btn-send-command').prop('disabled', !connected);
    }
    
    // WebSocket functions
    function connectWebSocket() {
        try {
            ws = new WebSocket(config.wsUrl);
            
            ws.onopen = function() {
                console.log('WebSocket terhubung');
                $('#ws-status').removeClass('bg-danger bg-warning')
                              .addClass('bg-success')
                              .text('WebSocket: Connected');
                $('#realtime-indicator').show();
                
                if (reconnectInterval) {
                    clearInterval(reconnectInterval);
                    reconnectInterval = null;
                }
                
                // Stop polling ketika WebSocket aktif
                if (pollingInterval) {
                    clearInterval(pollingInterval);
                    pollingInterval = null;
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
                $('#ws-status').removeClass('bg-success bg-warning')
                              .addClass('bg-danger')
                              .text('WebSocket: Disconnected');
                $('#realtime-indicator').hide();
                
                // Start polling sebagai fallback
                startPolling();
                
                // Auto reconnect
                if (!reconnectInterval) {
                    reconnectInterval = setInterval(() => {
                        console.log('Mencoba reconnect WebSocket...');
                        connectWebSocket();
                    }, config.wsReconnectInterval);
                }
            };
            
            ws.onerror = function(error) {
                console.error('WebSocket error:', error);
                $('#ws-status').removeClass('bg-success bg-danger')
                              .addClass('bg-warning')
                              .text('WebSocket: Error');
            };
            
        } catch (e) {
            console.error('Error creating WebSocket:', e);
            $('#ws-status').removeClass('bg-success bg-danger')
                          .addClass('bg-warning')
                          .text('WebSocket: Error');
            startPolling();
        }
    }
    
    // Polling fallback
    function startPolling() {
        if (pollingInterval) return; // Sudah ada polling
        
        pollingInterval = setInterval(() => {
            // Hanya polling jika WebSocket tidak aktif
            if (!ws || ws.readyState !== WebSocket.OPEN) {
                fetchWeightData();
            }
        }, config.pollingInterval);
    }
    
    function fetchWeightData() {
        $.ajax({
            url: config.apiBase + '/current',
            method: 'GET',
            timeout: 5000,
            success: function(response) {
                if (response.success) {
                    updateDisplay(response.data);
                }
            },
            error: function(xhr, status, error) {
                console.error('Error fetching weight:', error);
                updateConnectionStatus(false);
            }
        });
    }
    
    // API call functions
    function sendApiRequest(endpoint, method = 'POST', data = null) {
        if (!isConnected && endpoint !== '/current') {
            showAlert('Timbangan tidak terhubung!', 'warning');
            return;
        }
        
        const options = {
            url: config.apiBase + endpoint,
            method: method,
            timeout: 5000,
            success: function(response) {
                if (response.success) {
                    showAlert(response.message || 'Perintah berhasil dikirim', 'success');
                } else {
                    showAlert(response.message || 'Gagal mengirim perintah', 'error');
                }
            },
            error: function(xhr, status, error) {
                showAlert('Gagal mengirim perintah: ' + error, 'error');
            }
        };
        
        if (data) {
            options.data = data;
            options.headers = {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            };
        }
        
        $.ajax(options);
    }
    
    function showAlert(message, type = 'info') {
        const alertClass = {
            'success': 'alert-success',
            'error': 'alert-danger',
            'warning': 'alert-warning',
            'info': 'alert-info'
        }[type] || 'alert-info';
        
        const alert = $(`
            <div class="alert ${alertClass} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        `);
        
        $('.container-fluid').prepend(alert);
        
        // Auto remove after 3 seconds
        setTimeout(() => {
            alert.fadeOut(() => alert.remove());
        }, 3000);
    }
    
    // Event handlers
    $('#btn-zero').click(() => sendApiRequest('/zero'));
    $('#btn-request').click(() => sendApiRequest('/request'));
    
    $('#btn-reconnect').click(function() {
        if (ws) ws.close();
        connectWebSocket();
    });
    
    $('#btn-send-command').click(function() {
        const command = $('#custom-command').val().trim();
        if (!command) {
            showAlert('Masukkan perintah terlebih dahulu', 'warning');
            return;
        }
        
        sendApiRequest('/command', 'POST', { command: command });
        $('#custom-command').val('');
    });
    
    $('#custom-command').keypress(function(e) {
        if (e.which === 13) { // Enter key
            $('#btn-send-command').click();
        }
    });
    
    // Inisialisasi
    connectWebSocket();
    startPolling(); // Fallback polling
    
    // Cleanup saat page unload
    $(window).on('beforeunload', function() {
        if (ws) ws.close();
        if (pollingInterval) clearInterval(pollingInterval);
        if (reconnectInterval) clearInterval(reconnectInterval);
    });
});
</script>
@endpush