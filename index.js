// Install dulu: npm install serialport
const { SerialPort } = require('serialport');
const { ReadlineParser } = require('@serialport/parser-readline');
const readline = require('readline');

// Konfigurasi Serial Port
const portName = 'COM7';
const baudRates = [2400, 4800, 9600, 19200]; // Baudrate yang akan dicoba
let currentBaudIndex = 0;
let currentPort = null;

// Setup readline untuk input dari keyboard
const rl = readline.createInterface({
  input: process.stdin,
  output: process.stdout
});

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
    });

    // Event port tertutup
    port.on('close', () => {
      console.log('🔌 Port tertutup');
    });
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
    port.close(() => {
      console.log('✅ Port ditutup. Bye!');
      process.exit(0);
    });
  });
}

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
  
  // Mulai mencoba koneksi
  tryConnect(baudRates[currentBaudIndex]);
}

// Jalankan program
main();