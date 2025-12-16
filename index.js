const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const readline = require('readline');
const express = require('express');
const cors = require('cors');

// ============================================
// KONFIGURASI
// ============================================
const portName = 'COM7';
const baudRates = [2400, 4800, 9600, 19200];
const API_PORT = 3000; // Port untuk REST API

// ============================================
// STATE MANAGEMENT
// ============================================
let currentBaudIndex = 0;
let currentPort = null;
let latestWeight = {
  value: 0,
  unit: 'kg',
  raw: '',
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
  res.json({
    success: true,
    data: {
      connected: latestWeight.connected,
      status: latestWeight.status,
      port: portName,
      baudRate: baudRates[currentBaudIndex]
    }
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

// Start API Server
app.listen(API_PORT, () => {
  console.log(`\n🌐 REST API berjalan di http://localhost:${API_PORT}`);
  console.log(`📊 Endpoint utama:`);
  console.log(`   GET  http://localhost:${API_PORT}/api/weight   - Ambil data timbangan`);
  console.log(`   GET  http://localhost:${API_PORT}/api/status   - Cek status koneksi`);
  console.log(`   POST http://localhost:${API_PORT}/api/command  - Kirim perintah custom`);
  console.log(`   POST http://localhost:${API_PORT}/api/zero     - Zero/Tare`);
  console.log(`   POST http://localhost:${API_PORT}/api/request  - Request data`);
  console.log(`   GET  http://localhost:${API_PORT}/api/ports    - List semua port\n`);
});

// ============================================
// SERIAL PORT FUNCTIONS
// ============================================

// Update state dengan data baru
function updateWeight(value, unit, raw) {
  latestWeight = {
    value: parseFloat(value) || 0,
    unit: unit || 'kg',
    raw: raw || '',
    timestamp: new Date().toISOString(),
    status: 'active',
    connected: true
  };
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
      updateWeight(0, 'kg', 'Error: ' + err.message);
    });

    // Event port tertutup
    port.on('close', () => {
      console.log('🔌 Port tertutup');
      latestWeight.status = 'disconnected';
      latestWeight.connected = false;
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
  console.log('🏗️  Serial Port Reader untuk Timbangan Armada X0168');
  console.log('================================================\n');
  
  // List port yang tersedia
  await listPorts();
  
  // Set nilai default 0 saat startup
  updateWeight(0, 'kg', 'Menunggu koneksi...');
  
  // Mulai mencoba koneksi
  tryConnect(baudRates[currentBaudIndex]);
}

// Jalankan program
main();
