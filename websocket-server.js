// Tambahkan ini ke index.js Anda untuk WebSocket support
const WebSocket = require('ws');

// Buat WebSocket server
const wss = new WebSocket.Server({ port: 8080 });

console.log('🔌 WebSocket server berjalan di ws://localhost:8080');

// Array untuk menyimpan semua client yang terhubung
const clients = new Set();

wss.on('connection', function connection(ws) {
    console.log('👤 Client baru terhubung via WebSocket');
    clients.add(ws);
    
    // Kirim data terbaru ke client yang baru terhubung
    ws.send(JSON.stringify({
        type: 'weight_update',
        data: latestWeight
    }));
    
    ws.on('close', function() {
        console.log('👤 Client WebSocket terputus');
        clients.delete(ws);
    });
    
    ws.on('error', function(error) {
        console.error('WebSocket error:', error);
        clients.delete(ws);
    });
});

// Fungsi untuk broadcast data ke semua client WebSocket
function broadcastWeight(weightData) {
    const message = JSON.stringify({
        type: 'weight_update',
        data: weightData
    });
    
    clients.forEach(client => {
        if (client.readyState === WebSocket.OPEN) {
            client.send(message);
        }
    });
}

// Modifikasi fungsi updateWeight untuk broadcast
const originalUpdateWeight = updateWeight;
updateWeight = function(value, unit, raw) {
    originalUpdateWeight(value, unit, raw);
    
    // Broadcast ke semua WebSocket clients
    broadcastWeight(latestWeight);
};