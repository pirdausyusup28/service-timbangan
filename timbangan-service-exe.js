const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const readline = require('readline');
const express = require('express');
const cors = require('cors');
const WebSocket = require('ws');
const fs = require('fs');
const path = require('path');

// ============================================
// LOAD KONFIGURASI DARI FILE EKSTERNAL
// ============================================
let config = {
  timbangan_id: 1,
  port_name: 'COM7',
  api_port: 3001,
  ws_port: 8081,
  baud_rates: [2400, 4800, 9600, 19200],
  enable_http_push: false,
  enable_ftp_push: false
};

// Coba baca config.json
const configFile = path.join(process.cwd(), 'config.json');
if (fs.existsSync(configFile)) {
  try {
    const configData = fs.readFileSync(configFile, 'utf8');
    config = { ...config, ...JSON.parse(configData) };
    console.log('✅ Konfigurasi dimuat dari config.json');
  } catch (error) {
    console.log('⚠️  Error membaca config.json, menggunakan default');
  }
} else {
  console.log('⚠️  config.json tidak ditemukan, menggunakan default');
}

// Gunakan konfigurasi
const TIMBANGAN_ID = config.timbangan_id;
const portName = config.port_name;
const baudRates = config.baud_rates;
const API_PORT = config.api_port;
const WS_PORT = config.ws_port;

// File output
const OUTPUT_DIR = path.join(process.cwd(), 'data');
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
    fs.writeFileSync(WEIGHT_JSON_FILE, JSON.stringify(weightData, null, 2));
    
    const txtContent = `${weightData.value}|${weightData.unit}|${weightData.timestamp}|${weightData.status}|${weightData.connected}|${weightData.timbangan_id}`;
    fs.writeFileSync(WEIGHT_TXT_FILE, txtContent);
    
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

  latestWeight = {
    timbangan_id: TIMBANGAN_ID,
    value: parseFloat(value) || 0,
    unit: unit || 'kg',
    raw: `Test value: ${value}`,
    timestamp: new Date().toISOString(),
    status: 'test',
    connected: true
  };
  
  saveWeightToFiles(latestWeight);
  broadcastWeight(latestWeight);
  
  res.json({
    success: true,
    message: `Test weight set to ${value} ${unit || 'kg'} (Timbangan ${TIMBANGAN_ID})`,
    data: latestWeight
  });
});

app.listen(API_PORT, '0.0.0.0', () => {
  console.log(`\n🌐 REST API Timbangan ${TIMBANGAN_ID} berjalan di http://localhost:${API_PORT}`);
  console.log(`🔌 WebSocket: ws://localhost:${WS_PORT}`);
  console.log(`📁 File output:`);
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
    console.log('📊 Menunggu data dari timbangan...\n');

    latestWeight.connected = true;
    latestWeight.status = 'connected';
    saveWeightToFiles(latestWeight);
    broadcastWeight(latestWeight);

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

    port.on('error', (err) => {
      console.error('❌ Error:', err.message);
      latestWeight.status = 'error';
      latestWeight.connected = false;
      saveWeightToFiles(latestWeight);
      broadcastWeight(latestWeight);
    });

    port.on('close', () => {
      console.log('🔌 Port tertutup');
      latestWeight.status = 'disconnected';
      latestWeight.connected = false;
      saveWeightToFiles(latestWeight);
      broadcastWeight(latestWeight);
    });
  });
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

async function main() {
  console.log(`🏗️  Serial Port Reader untuk Timbangan ${TIMBANGAN_ID} - Armada X0168`);
  console.log('================================================\n');
  
  console.log('📋 Konfigurasi:');
  console.log(`   Timbangan ID: ${TIMBANGAN_ID}`);
  console.log(`   COM Port: ${portName}`);
  console.log(`   API Port: ${API_PORT}`);
  console.log(`   WebSocket: ${WS_PORT}\n`);
  
  ensureOutputDirectory();
  updateWeight(0, 'kg', 'Menunggu koneksi...');
  tryConnect(baudRates[currentBaudIndex]);
}

main();