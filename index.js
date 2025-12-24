const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const readline = require('readline');
const express = require('express');
const cors = require('cors');
const WebSocket = require('ws');
const fs = require('fs');
const path = require('path');
const HttpPusher = require('./HttpPusher');
const FtpPusher = require('./FtpPusher');

// ============================================
// KONFIGURASI
// ============================================
const TIMBANGAN_ID = 1; // ID Timbangan (1, 2, 3, atau 4)
const portName = 'COM7';
const baudRates = [2400, 4800, 9600, 19200];
const API_PORT = 3000 + TIMBANGAN_ID; // Port unik per timbangan (3001, 3002, 3003, 3004)
const WS_PORT = 8080 + TIMBANGAN_ID; // WebSocket port unik (8081, 8082, 8083, 8084)

// Konfigurasi file output untuk Laravel
const OUTPUT_DIR = path.join(__dirname, 'data');
const WEIGHT_JSON_FILE = path.join(OUTPUT_DIR, `weight_data_${TIMBANGAN_ID}.json`);
const WEIGHT_TXT_FILE = path.join(OUTPUT_DIR, `weight_data_${TIMBANGAN_ID}.txt`);
const WEIGHT_LOG_FILE = path.join(OUTPUT_DIR, `weight_log_${TIMBANGAN_ID}.txt`);

// ============================================
// KONFIGURASI ONLINE PUSH
// ============================================

// Konfigurasi HTTP Push ke server online
const HTTP_CONFIG = {
  baseUrl: 'https://yourdomain.com', // Ganti dengan domain Hostinger Anda
  apiKey: 'your-secret-api-key',     // Ganti dengan API key Anda
  endpoint: '/api/timbangan/update',
  timeout: 10000,
  retryAttempts: 3,
  retryDelay: 3000
};

// Konfigurasi FTP Push ke Hostinger
const FTP_CONFIG = {
  host: 'ftp.yourdomain.com',        // Ganti dengan FTP host Hostinger
  user: 'your-ftp-username',         // Username FTP Hostinger
  password: 'your-ftp-password',     // Password FTP Hostinger
  secure: false,                     // true jika pakai FTPS
  remotePath: `/public_html/storage/timbangan/timbangan_${TIMBANGAN_ID}/`,
  localPath: './data/',
  uploadInterval: 5000               // Upload setiap 5 detik
};

// Aktifkan/nonaktifkan pusher
const ENABLE_HTTP_PUSH = true;  // Set false untuk disable HTTP push
const ENABLE_FTP_PUSH = false;   // Set true untuk enable FTP push

// Inisialisasi pushers
let httpPusher = null;
let ftpPusher = null;

if (ENABLE_HTTP_PUSH) {
  httpPusher = new HttpPusher(HTTP_CONFIG);
}

if (ENABLE_FTP_PUSH) {
  ftpPusher = new FtpPusher(FTP_CONFIG);
}

// ============================================
// STATE MANAGEMENT
// ============================================
let currentBaudIndex = 0;
let currentPort = null;
let latestWeight = {
  timbangan_id: TIMBANGAN_ID,
  value: 0,
  unit: 'kg',
  raw: 'Nilai default startup',
  timestamp: new Date().toISOString(),
  status: 'disconnected',
  connected: false
};

// Setup readline untuk input dari keyboard
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

// ============================================
// WEBSOCKET SETUP
// ============================================
const wss = new WebSocket.Server({ 
  port: WS_PORT,
  host: '0.0.0.0' // Bind ke semua interface untuk akses dari PC lain
});
const wsClients = new Set();

console.log(`🔌 WebSocket server berjalan di ws://localhost:${WS_PORT}`);

wss.on('connection', function connection(ws) {
  console.log('👤 Client baru terhubung via WebSocket');
  wsClients.add(ws);
  
  // Kirim data terbaru ke client yang baru terhubung
  ws.send(JSON.stringify({
    type: 'weight_update',
    data: latestWeight
  }));
  
  ws.on('close', function() {
    console.log('👤 Client WebSocket terputus');
    wsClients.delete(ws);
  });
  
  ws.on('error', function(error) {
    console.error('WebSocket error:', error);
    wsClients.delete(ws);
  });
});

// Fungsi untuk broadcast data ke semua WebSocket clients
function broadcastWeight(weightData) {
  const message = JSON.stringify({
    type: 'weight_update',
    data: weightData
  });
  
  wsClients.forEach(client => {
    if (client.readyState === WebSocket.OPEN) {
      try {
        client.send(message);
      } catch (error) {
        console.error('Error sending WebSocket message:', error);
        wsClients.delete(client);
      }
    }
  });
}

// Fungsi untuk update manual (untuk testing)
function setTestWeight(value, unit = 'kg', raw = 'Manual test') {
  latestWeight = {
    timbangan_id: TIMBANGAN_ID,
    value: parseFloat(value) || 0,
    unit: unit,
    raw: raw,
    timestamp: new Date().toISOString(),
    status: 'test',
    connected: true
  };
  
  console.log(`🧪 Test weight set to: ${value} ${unit} (Timbangan ${TIMBANGAN_ID})`);
  broadcastWeight(latestWeight);
}

// ============================================
// REST API SETUP
// ============================================
const app = express();
app.use(cors());
app.use(express.json());

// Endpoint untuk mendapatkan data timbangan terbaru
app.get('/api/weight', (req, res) => {
  res.json({
    success: true,
    data: latestWeight
  });
});

// Endpoint untuk mengirim perintah ke timbangan
app.post('/api/command', (req, res) => {
  const { command } = req.body;
  
  if (!currentPort || !currentPort.isOpen) {
    return res.status(503).json({
      success: false,
      message: 'Port tidak terhubung'
    });
  }

  if (!command) {
    return res.status(400).json({
      success: false,
      message: 'Command tidak boleh kosong'
    });
  }

  sendCommand(currentPort, command + '\r\n');
  res.json({
    success: true,
    message: `Perintah '${command}' terkirim`
  });
});

// Endpoint untuk zero/tare
app.post('/api/zero', (req, res) => {
  if (!currentPort || !currentPort.isOpen) {
    return res.status(503).json({
      success: false,
      message: 'Port tidak terhubung'
    });
  }

  sendCommand(currentPort, 'Z\r\n');
  res.json({
    success: true,
    message: 'Perintah Zero/Tare terkirim'
  });
});

// Endpoint untuk request weight
app.post('/api/request', (req, res) => {
  if (!currentPort || !currentPort.isOpen) {
    return res.status(503).json({
      success: false,
      message: 'Port tidak terhubung'
    });
  }

  sendCommand(currentPort, 'P\r\n');
  res.json({
    success: true,
    message: 'Request data terkirim'
  });
});

// Endpoint untuk status koneksi
app.get('/api/status', (req, res) => {
  const httpStatus = httpPusher ? httpPusher.getStatus() : null;
  const ftpStatus = ftpPusher ? ftpPusher.getStatus() : null;
  
  res.json({
    success: true,
    data: {
      connected: latestWeight.connected,
      status: latestWeight.status,
      port: portName,
      baudRate: baudRates[currentBaudIndex],
      online_push: {
        http: httpStatus,
        ftp: ftpStatus
      }
    }
  });
});

// Endpoint untuk set test weight (untuk testing)
app.post('/api/test-weight', (req, res) => {
  const { value, unit } = req.body;
  
  if (value === undefined || value === null) {
    return res.status(400).json({
      success: false,
      message: 'Value tidak boleh kosong'
    });
  }

  setTestWeight(value, unit || 'kg', `Test value: ${value}`);
  
  res.json({
    success: true,
    message: `Test weight set to ${value} ${unit || 'kg'}`,
    data: latestWeight
  });
});

// Endpoint untuk list semua port
app.get('/api/ports', async (req, res) => {
  try {
    const ports = await SerialPort.list();
    res.json({
      success: true,
      data: ports
    });
  } catch (error) {
    res.status(500).json({
      success: false,
      message: error.message
    });
  }
});

// Health check
app.get('/health', (req, res) => {
  res.json({ status: 'ok', timestamp: new Date().toISOString() });
});

// Start API Server - bind ke semua interface untuk akses dari PC lain
app.listen(API_PORT, '0.0.0.0', () => {
  console.log(`\n🌐 REST API berjalan di http://localhost:${API_PORT}`);
  console.log(`�  WebSocket server berjalan di ws://localhost:${WS_PORT}`);
  console.log(`📊 Endpoint utama:`);
  console.log(`   GET  http://localhost:${API_PORT}/api/weight   - Ambil data timbangan`);
  console.log(`   GET  http://localhost:${API_PORT}/api/status   - Cek status koneksi`);
  console.log(`   POST http://localhost:${API_PORT}/api/command  - Kirim perintah custom`);
  console.log(`   POST http://localhost:${API_PORT}/api/zero     - Zero/Tare`);
  console.log(`   POST http://localhost:${API_PORT}/api/request  - Request data`);
  console.log(`   POST http://localhost:${API_PORT}/api/test-weight - Set test weight`);
  console.log(`   GET  http://localhost:${API_PORT}/api/ports    - List semua port`);
  console.log(`\n🔌 WebSocket: ws://localhost:${WS_PORT} - Untuk data realtime\n`);
});

// ============================================
// SERIAL PORT FUNCTIONS
// ============================================

// Pastikan direktori output ada
function ensureOutputDirectory() {
  if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    console.log(`📁 Direktori ${OUTPUT_DIR} dibuat`);
  }
}

// Fungsi untuk menyimpan data ke file
function saveWeightToFiles(weightData) {
  try {
    // Simpan ke file JSON (untuk Laravel baca sebagai JSON)
    fs.writeFileSync(WEIGHT_JSON_FILE, JSON.stringify(weightData, null, 2));
    
    // Simpan ke file TXT (format sederhana untuk Laravel)
    const txtContent = `${weightData.value}|${weightData.unit}|${weightData.timestamp}|${weightData.status}|${weightData.connected}`;
    fs.writeFileSync(WEIGHT_TXT_FILE, txtContent);
    
    // Append ke log file (history)
    const logEntry = `${weightData.timestamp} - ${weightData.value} ${weightData.unit} - Status: ${weightData.status} - Raw: ${weightData.raw}\n`;
    fs.appendFileSync(WEIGHT_LOG_FILE, logEntry);
    
    console.log(`💾 Data disimpan: ${weightData.value} ${weightData.unit}`);
  } catch (error) {
    console.error('❌ Error menyimpan file:', error.message);
  }
}

// Update state dengan data baru
function updateWeight(value, unit, raw) {
  latestWeight = {
    timbangan_id: TIMBANGAN_ID,
    value: parseFloat(value) || 0,
    unit: unit || 'kg',
    raw: raw || '',
    timestamp: new Date().toISOString(),
    status: 'active',
    connected: true
  };
  
  // Simpan ke file untuk Laravel
  saveWeightToFiles(latestWeight);
  
  // Push ke server online (HTTP)
  if (ENABLE_HTTP_PUSH && httpPusher) {
    httpPusher.pushWeight(latestWeight).catch(err => {
      console.error('HTTP Push error:', err.message);
    });
  }
  
  // Broadcast ke semua WebSocket clients
  broadcastWeight(latestWeight);
}

// Fungsi untuk mencoba koneksi dengan baud rate tertentu
function tryConnect(baudRate) {
  console.log(`\n=== Mencoba koneksi ke ${portName} dengan baud rate: ${baudRate} ===`);
  
  const port = new SerialPort({
    path: portName,
    baudRate: baudRate,
    dataBits: 8,
    stopBits: 1,
    parity: 'none',
    autoOpen: false
  });

  currentPort = port;

  // Parser untuk membaca data per baris
  const parser = port.pipe(new ReadlineParser({ delimiter: '\r\n' }));

  port.open((err) => {
    if (err) {
      console.error('❌ Error membuka port:', err.message);
      latestWeight.status = 'error';
      latestWeight.connected = false;
      broadcastWeight(latestWeight);
      
      // Coba baud rate berikutnya
      currentBaudIndex++;
      if (currentBaudIndex < baudRates.length) {
        setTimeout(() => tryConnect(baudRates[currentBaudIndex]), 1000);
      } else {
        console.log('\n⚠️  Semua baud rate sudah dicoba.');
        console.log('💡 Tips:');
        console.log('   1. Pastikan COM7 tidak digunakan aplikasi lain');
        console.log('   2. Cek Device Manager apakah COM7 aktif');
        console.log('   3. Pastikan kabel terhubung dengan baik');
        
        // Set nilai default 0 jika tidak terkoneksi
        updateWeight(0, 'kg', 'Tidak ada koneksi');
      }
      return;
    }

    console.log(`✅ Port ${portName} terbuka dengan baud rate ${baudRate}`);
    console.log('📊 Menunggu data dari timbangan...');
    console.log('\n🎮 Perintah yang tersedia:');
    console.log('   P  - Request Print/Data (simulasi tombol PRINT)');
    console.log('   W  - Request Weight');
    console.log('   Z  - Zero/Tare');
    console.log('   S  - Status');
    console.log('   ?  - Query');
    console.log('   T  - Test berbagai perintah');
    console.log('   Q  - Quit');
    console.log('\n💡 Ketik perintah lalu Enter, atau tunggu data otomatis');
    console.log('   (Tekan Ctrl+C untuk keluar)\n');

    // Update status connected
    latestWeight.connected = true;
    latestWeight.status = 'connected';
    broadcastWeight(latestWeight);

    // Setup keyboard input
    setupKeyboardInput(port);

    // Event ketika data diterima (per baris)
    parser.on('data', (data) => {
      const timestamp = new Date().toLocaleTimeString();
      console.log(`\n[${timestamp}] 📦 Data diterima:`);
      console.log('   Text:', data);
      
      // Parsing data timbangan (format bisa berbeda-beda)
      const cleaned = data.trim();
      
      // Coba ekstrak angka dan satuan
      const weightMatch = cleaned.match(/([+-]?\d+\.?\d*)\s*(kg|g|lb)?/i);
      if (weightMatch) {
        const weight = weightMatch[1];
        const unit = weightMatch[2] || 'kg';
        console.log(`   ⚖️  Berat: ${weight} ${unit}`);
        
        // Update state
        updateWeight(weight, unit, cleaned);
      } else {
        // Jika tidak ada match, set 0
        console.log('   ⚠️  Format tidak dikenali, set ke 0');
        updateWeight(0, 'kg', cleaned);
      }
      
      console.log('---');
    });

    // Event untuk data mentah (tanpa parsing)
    port.on('data', (data) => {
      // Tampilkan juga dalam format hex untuk debugging
      console.log('🔍 Raw Hex:', data.toString('hex'));
      console.log('🔍 Raw ASCII:', data.toString('ascii').replace(/[^\x20-\x7E]/g, '.'));
    });

    // Event error
    port.on('error', (err) => {
      console.error('❌ Error:', err.message);
      latestWeight.status = 'error';
      latestWeight.connected = false;
      broadcastWeight(latestWeight);
      updateWeight(0, 'kg', 'Error: ' + err.message);
    });

    // Event port tertutup
    port.on('close', () => {
      console.log('🔌 Port tertutup');
      latestWeight.status = 'disconnected';
      latestWeight.connected = false;
      broadcastWeight(latestWeight);
      updateWeight(0, 'kg', 'Port tertutup');
    });

    // Request data setiap 2 detik (opsional, bisa diaktifkan)
    // setInterval(() => {
    //   if (port.isOpen) {
    //     sendCommand(port, 'P\r\n');
    //   }
    // }, 2000);
  });
}

// Fungsi untuk setup keyboard input
function setupKeyboardInput(port) {
  rl.on('line', (input) => {
    const cmd = input.toUpperCase().trim();
    
    switch(cmd) {
      case 'P':
        console.log('📤 Mengirim perintah: P (Print/Request Data)');
        sendCommand(port, 'P\r\n');
        break;
      case 'W':
        console.log('📤 Mengirim perintah: W (Weight)');
        sendCommand(port, 'W\r\n');
        break;
      case 'Z':
        console.log('📤 Mengirim perintah: Z (Zero/Tare)');
        sendCommand(port, 'Z\r\n');
        break;
      case 'S':
        console.log('📤 Mengirim perintah: S (Status)');
        sendCommand(port, 'S\r\n');
        break;
      case '?':
        console.log('📤 Mengirim perintah: ? (Query)');
        sendCommand(port, '?\r\n');
        break;
      case 'T':
        console.log('🧪 Testing berbagai perintah...');
        testCommands(port);
        break;
      case 'Q':
        console.log('👋 Keluar...');
        port.close(() => {
          process.exit(0);
        });
        break;
      default:
        if (cmd) {
          console.log(`📤 Mengirim custom command: ${cmd}`);
          sendCommand(port, cmd + '\r\n');
        }
    }
  });
}

// Fungsi untuk mengirim perintah
function sendCommand(port, command) {
  port.write(command, (err) => {
    if (err) {
      console.error('❌ Error mengirim perintah:', err.message);
    } else {
      console.log('✅ Perintah terkirim');
    }
  });
}

// Fungsi untuk test berbagai perintah umum timbangan
function testCommands(port) {
  const commands = [
    'P\r\n',      // Print
    'W\r\n',      // Weight
    'S\r\n',      // Status
    '?\r\n',      // Query
    'R\r\n',      // Read
    'D\r\n',      // Data
    '\x05',       // ENQ (Enquiry)
    'SI\r\n',     // Send Immediate
    'SIR\r\n',    // Send Immediate Repeated
  ];
  
  let index = 0;
  
  const sendNext = () => {
    if (index < commands.length) {
      const cmd = commands[index];
      const displayCmd = cmd.replace(/\r/g, '\\r').replace(/\n/g, '\\n').replace(/\x05/g, '<ENQ>');
      console.log(`\n🧪 Test ${index + 1}/${commands.length}: ${displayCmd}`);
      
      port.write(cmd, (err) => {
        if (!err) {
          console.log('   Terkirim, tunggu 2 detik...');
        }
      });
      
      index++;
      setTimeout(sendNext, 2000);
    } else {
      console.log('\n✅ Test selesai. Perhatikan output di atas untuk melihat perintah mana yang berhasil.\n');
    }
  };
  
  sendNext();
}

// Handle Ctrl+C untuk menutup port dengan benar
process.on('SIGINT', () => {
  console.log('\n\n⏹️  Menutup koneksi...');
  rl.close();
  if (currentPort) {
    currentPort.close(() => {
      console.log('✅ Port ditutup. Bye!');
      process.exit(0);
    });
  } else {
    process.exit(0);
  }
});

// Fungsi untuk list semua port yang tersedia
async function listPorts() {
  console.log('🔍 Mencari port serial yang tersedia...\n');
  const ports = await SerialPort.list();
  
  if (ports.length === 0) {
    console.log('❌ Tidak ada port serial yang ditemukan');
    return;
  }

  console.log('📋 Port yang tersedia:');
  ports.forEach((port, index) => {
    console.log(`   ${index + 1}. ${port.path}`);
    if (port.manufacturer) console.log(`      Manufacturer: ${port.manufacturer}`);
    if (port.serialNumber) console.log(`      Serial: ${port.serialNumber}`);
    if (port.pnpId) console.log(`      PnP ID: ${port.pnpId}`);
    console.log('');
  });
}

// Main Program
async function main() {
  console.log(`🏗️  Serial Port Reader untuk Timbangan ${TIMBANGAN_ID} - Armada X0168`);
  console.log('================================================\n');
  
  // Pastikan direktori output ada
  ensureOutputDirectory();
  
  // List port yang tersedia
  await listPorts();
  
  console.log(`📁 File output untuk Laravel (Timbangan ${TIMBANGAN_ID}):`);
  console.log(`   JSON: ${WEIGHT_JSON_FILE}`);
  console.log(`   TXT:  ${WEIGHT_TXT_FILE}`);
  console.log(`   LOG:  ${WEIGHT_LOG_FILE}\n`);
  
  // Setup online push
  console.log('🌐 Konfigurasi Online Push:');
  
  if (ENABLE_HTTP_PUSH && httpPusher) {
    console.log(`   HTTP Push: ENABLED`);
    console.log(`   Target: ${HTTP_CONFIG.baseUrl}`);
    
    // Test koneksi HTTP
    setTimeout(async () => {
      await httpPusher.testConnection();
    }, 3000);
  } else {
    console.log(`   HTTP Push: DISABLED`);
  }
  
  if (ENABLE_FTP_PUSH && ftpPusher) {
    console.log(`   FTP Push: ENABLED`);
    console.log(`   Target: ${FTP_CONFIG.host}`);
    
    // Koneksi dan start auto upload FTP
    setTimeout(async () => {
      const connected = await ftpPusher.connect();
      if (connected) {
        ftpPusher.startAutoUpload();
      }
    }, 5000);
  } else {
    console.log(`   FTP Push: DISABLED`);
  }
  
  console.log('');
  console.log(`🔌 Port: ${API_PORT} | WebSocket: ${WS_PORT} | COM: ${portName}\n`);
  
  // Set nilai default 0 saat startup
  updateWeight(0, 'kg', 'Menunggu koneksi...');
  
  // Mulai mencoba koneksi
  tryConnect(baudRates[currentBaudIndex]);
}

// Jalankan program
main();