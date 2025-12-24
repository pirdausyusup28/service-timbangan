const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const readline = require('readline');
const express = require('express');
const cors = require('cors');
const WebSocket = require('ws');
const fs = require('fs');
const path = require('path');

// ============================================
// KONFIGURASI - GANTI BAGIAN INI UNTUK SETIAP TIMBANGAN
// ============================================
const TIMBANGAN_ID = 1;        // GANTI: 1, 2, 3, atau 4
const portName = 'COM7';       // GANTI: COM7, COM8, COM9, COM10
// ============================================

const baudRates = [2400, 4800, 9600, 19200];
const API_PORT = 3000 + TIMBANGAN_ID; // Port otomatis: 3001, 3002, 3003, 3004
const WS_PORT = 8080 + TIMBANGAN_ID;  // WebSocket: 8081, 8082, 8083, 8084

// File output dengan ID timbangan
const OUTPUT_DIR = path.join(__dirname, 'data');
const WEIGHT_JSON_FILE = path.join(OUTPUT_DIR, `weight_data_${TIMBANGAN_ID}.json`);
const WEIGHT_TXT_FILE = path.join(OUTPUT_DIR, `weight_data_${TIMBANGAN_ID}.txt`);
const WEIGHT_LOG_FILE = path.join(OUTPUT_DIR, `weight_log_${TIMBANGAN_ID}.txt`);

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

const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

// ============================================
// WEBSOCKET SETUP
// ============================================
const wss = new WebSocket.Server({ 
  port: WS_PORT,
  host: '0.0.0.0'
});
const wsClients = new Set();

console.log(`🔌 WebSocket server berjalan di ws://localhost:${WS_PORT}`);

wss.on('connection', function connection(ws) {
  console.log('👤 Client baru terhubung via WebSocket');
  wsClients.add(ws);
  
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

// ============================================
// FILE OPERATIONS
// ============================================
function ensureOutputDirectory() {
  if (!fs.existsSync(OUTPUT_DIR)) {
    fs.mkdirSync(OUTPUT_DIR, { recursive: true });
    console.log(`📁 Direktori ${OUTPUT_DIR} dibuat`);
  }
}

function saveWeightToFiles(weightData) {
  try {
    // Simpan ke file JSON
    fs.writeFileSync(WEIGHT_JSON_FILE, JSON.stringify(weightData, null, 2));
    
    // Simpan ke file TXT (format: value|unit|timestamp|status|connected|timbangan_id)
    const txtContent = `${weightData.value}|${weightData.unit}|${weightData.timestamp}|${weightData.status}|${weightData.connected}|${weightData.timbangan_id}`;
    fs.writeFileSync(WEIGHT_TXT_FILE, txtContent);
    
    // Append ke log file
    const logEntry = `${weightData.timestamp} - Timbangan ${weightData.timbangan_id} - ${weightData.value} ${weightData.unit} - Status: ${weightData.status} - Raw: ${weightData.raw}\n`;
    fs.appendFileSync(WEIGHT_LOG_FILE, logEntry);
    
    console.log(`💾 Data disimpan: ${weightData.value} ${weightData.unit} (Timbangan ${weightData.timbangan_id})`);
  } catch (error) {
    console.error('❌ Error menyimpan file:', error.message);
  }
}

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
  
  saveWeightToFiles(latestWeight);
  broadcastWeight(latestWeight);
}

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
  saveWeightToFiles(latestWeight);
  broadcastWeight(latestWeight);
}

// ============================================
// REST API SETUP
// ============================================
const app = express();
app.use(cors());
app.use(express.json());

app.get('/api/weight', (req, res) => {
  res.json({
    success: true,
    data: latestWeight
  });
});

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

app.get('/api/status', (req, res) => {
  res.json({
    success: true,
    data: {
      timbangan_id: TIMBANGAN_ID,
      connected: latestWeight.connected,
      status: latestWeight.status,
      port: portName,
      baudRate: baudRates[currentBaudIndex],
      api_port: API_PORT,
      ws_port: WS_PORT
    }
  });
});

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
    message: `Test weight set to ${value} ${unit || 'kg'} (Timbangan ${TIMBANGAN_ID})`,
    data: latestWeight
  });
});

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

app.get('/health', (req, res) => {
  res.json({ 
    status: 'ok', 
    timestamp: new Date().toISOString(),
    timbangan_id: TIMBANGAN_ID
  });
});

app.listen(API_PORT, '0.0.0.0', () => {
  console.log(`\n🌐 REST API Timbangan ${TIMBANGAN_ID} berjalan di http://localhost:${API_PORT}`);
  console.log(`🔌 WebSocket: ws://localhost:${WS_PORT}`);
  console.log(`📊 Endpoint utama:`);
  console.log(`   GET  http://localhost:${API_PORT}/api/weight   - Ambil data timbangan`);
  console.log(`   GET  http://localhost:${API_PORT}/api/status   - Cek status koneksi`);
  console.log(`   POST http://localhost:${API_PORT}/api/test-weight - Set test weight`);
  console.log(`\n📁 File output:`);
  console.log(`   JSON: ${WEIGHT_JSON_FILE}`);
  console.log(`   TXT:  ${WEIGHT_TXT_FILE}`);
  console.log(`   LOG:  ${WEIGHT_LOG_FILE}\n`);
});

// ============================================
// SERIAL PORT FUNCTIONS
// ============================================
function tryConnect(baudRate) {
  console.log(`\n=== Mencoba koneksi Timbangan ${TIMBANGAN_ID} ke ${portName} dengan baud rate: ${baudRate} ===`);
  
  const port = new SerialPort({
    path: portName,
    baudRate: baudRate,
    dataBits: 8,
    stopBits: 1,
    parity: 'none',
    autoOpen: false
  });

  currentPort = port;
  const parser = port.pipe(new ReadlineParser({ delimiter: '\r\n' }));

  port.open((err) => {
    if (err) {
      console.error('❌ Error membuka port:', err.message);
      latestWeight.status = 'error';
      latestWeight.connected = false;
      saveWeightToFiles(latestWeight);
      broadcastWeight(latestWeight);
      
      currentBaudIndex++;
      if (currentBaudIndex < baudRates.length) {
        setTimeout(() => tryConnect(baudRates[currentBaudIndex]), 1000);
      } else {
        console.log('\n⚠️  Semua baud rate sudah dicoba.');
        updateWeight(0, 'kg', 'Tidak ada koneksi');
      }
      return;
    }

    console.log(`✅ Timbangan ${TIMBANGAN_ID} terhubung di ${portName} dengan baud rate ${baudRate}`);
    console.log('📊 Menunggu data dari timbangan...');
    console.log('\n🎮 Perintah yang tersedia:');
    console.log('   P  - Request Print/Data');
    console.log('   W  - Request Weight');
    console.log('   Z  - Zero/Tare');
    console.log('   T  - Test berbagai perintah');
    console.log('   Q  - Quit\n');

    latestWeight.connected = true;
    latestWeight.status = 'connected';
    saveWeightToFiles(latestWeight);
    broadcastWeight(latestWeight);

    setupKeyboardInput(port);

    parser.on('data', (data) => {
      const timestamp = new Date().toLocaleTimeString();
      console.log(`\n[${timestamp}] 📦 Data diterima (Timbangan ${TIMBANGAN_ID}):`);
      console.log('   Text:', data);
      
      const cleaned = data.trim();
      const weightMatch = cleaned.match(/([+-]?\d+\.?\d*)\s*(kg|g|lb)?/i);
      
      if (weightMatch) {
        const weight = weightMatch[1];
        const unit = weightMatch[2] || 'kg';
        console.log(`   ⚖️  Berat: ${weight} ${unit}`);
        updateWeight(weight, unit, cleaned);
      } else {
        console.log('   ⚠️  Format tidak dikenali, set ke 0');
        updateWeight(0, 'kg', cleaned);
      }
      
      console.log('---');
    });

    port.on('data', (data) => {
      console.log('🔍 Raw Hex:', data.toString('hex'));
      console.log('🔍 Raw ASCII:', data.toString('ascii').replace(/[^\x20-\x7E]/g, '.'));
    });

    port.on('error', (err) => {
      console.error('❌ Error:', err.message);
      latestWeight.status = 'error';
      latestWeight.connected = false;
      saveWeightToFiles(latestWeight);
      broadcastWeight(latestWeight);
      updateWeight(0, 'kg', 'Error: ' + err.message);
    });

    port.on('close', () => {
      console.log('🔌 Port tertutup');
      latestWeight.status = 'disconnected';
      latestWeight.connected = false;
      saveWeightToFiles(latestWeight);
      broadcastWeight(latestWeight);
      updateWeight(0, 'kg', 'Port tertutup');
    });
  });
}

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

function sendCommand(port, command) {
  port.write(command, (err) => {
    if (err) {
      console.error('❌ Error mengirim perintah:', err.message);
    } else {
      console.log('✅ Perintah terkirim');
    }
  });
}

function testCommands(port) {
  const commands = ['P\r\n', 'W\r\n', 'S\r\n', '?\r\n', 'R\r\n'];
  let index = 0;
  
  const sendNext = () => {
    if (index < commands.length) {
      const cmd = commands[index];
      const displayCmd = cmd.replace(/\r/g, '\\r').replace(/\n/g, '\\n');
      console.log(`\n🧪 Test ${index + 1}/${commands.length}: ${displayCmd}`);
      
      port.write(cmd, (err) => {
        if (!err) {
          console.log('   Terkirim, tunggu 2 detik...');
        }
      });
      
      index++;
      setTimeout(sendNext, 2000);
    } else {
      console.log('\n✅ Test selesai.\n');
    }
  };
  
  sendNext();
}

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
    console.log('');
  });
}

async function main() {
  console.log(`🏗️  Serial Port Reader untuk Timbangan ${TIMBANGAN_ID} - Armada X0168`);
  console.log('================================================\n');
  
  ensureOutputDirectory();
  await listPorts();
  
  console.log(`📡 Konfigurasi Timbangan ${TIMBANGAN_ID}:`);
  console.log(`   COM Port: ${portName}`);
  console.log(`   API Port: ${API_PORT}`);
  console.log(`   WebSocket: ${WS_PORT}\n`);
  
  updateWeight(0, 'kg', 'Menunggu koneksi...');
  tryConnect(baudRates[currentBaudIndex]);
}

main();