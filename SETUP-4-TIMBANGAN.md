# 🏗️ Setup 4 Timbangan - Node.js Service

## 📋 Yang Harus Diganti untuk Setiap PC/Timbangan

### File: `timbangan-service.js`

Buka file `timbangan-service.js` dan ganti bagian ini:

```javascript
// ============================================
// KONFIGURASI - GANTI BAGIAN INI UNTUK SETIAP TIMBANGAN
// ============================================
const TIMBANGAN_ID = 1;        // GANTI: 1, 2, 3, atau 4
const portName = 'COM7';       // GANTI: COM7, COM8, COM9, COM10
// ============================================
```

## 🖥️ Setup untuk 4 PC/Timbangan

### PC 1 - Timbangan 1:
```javascript
const TIMBANGAN_ID = 1;
const portName = 'COM7';       // Sesuaikan dengan port timbangan 1
```

### PC 2 - Timbangan 2:
```javascript
const TIMBANGAN_ID = 2;
const portName = 'COM7';       // Sesuaikan dengan port timbangan 2
```

### PC 3 - Timbangan 3:
```javascript
const TIMBANGAN_ID = 3;
const portName = 'COM7';       // Sesuaikan dengan port timbangan 3
```

### PC 4 - Timbangan 4:
```javascript
const TIMBANGAN_ID = 4;
const portName = 'COM7';       // Sesuaikan dengan port timbangan 4
```

## 🚀 Cara Menjalankan

### 1. Install Dependencies (di setiap PC):
```bash
npm install serialport express cors ws
```

### 2. Jalankan Service (di setiap PC):
```bash
node timbangan-service.js
```

### 3. Port yang Akan Digunakan:
- **Timbangan 1**: API Port 3001, WebSocket 8081
- **Timbangan 2**: API Port 3002, WebSocket 8082  
- **Timbangan 3**: API Port 3003, WebSocket 8083
- **Timbangan 4**: API Port 3004, WebSocket 8084

## 📁 File Output

Setiap timbangan akan membuat file dengan ID:
- `data/weight_data_1.json` (Timbangan 1)
- `data/weight_data_2.json` (Timbangan 2)
- `data/weight_data_3.json` (Timbangan 3)
- `data/weight_data_4.json` (Timbangan 4)

## 🔗 Akses dari Laravel

### Setup di Laravel:
```php
// Include helper
require_once app_path('Helpers/MultiWeightReader.php');

// Set path ke folder data (bisa network path)
MultiWeightReader::setDataPath('\\\\192.168.1.100\\shared\\timbangan\\data\\');

// Baca data timbangan spesifik
$timbangan1 = MultiWeightReader::getWeight(1);
$timbangan2 = MultiWeightReader::getWeight(2);
$timbangan3 = MultiWeightReader::getWeight(3);
$timbangan4 = MultiWeightReader::getWeight(4);

// Atau baca semua sekaligus
$allTimbangan = MultiWeightReader::getAllWeights();
```

### Contoh Penggunaan:
```php
// Di Controller Laravel
public function dashboard()
{
    require_once app_path('Helpers/MultiWeightReader.php');
    
    // Set path data (sesuaikan dengan network/local path)
    MultiWeightReader::setDataPath('d:/shared/timbangan/data/');
    
    $data = [
        'timbangan1' => MultiWeightReader::getWeight(1),
        'timbangan2' => MultiWeightReader::getWeight(2),
        'timbangan3' => MultiWeightReader::getWeight(3),
        'timbangan4' => MultiWeightReader::getWeight(4),
        'status' => MultiWeightReader::getAllStatus()
    ];
    
    return view('dashboard', $data);
}
```

### Di Blade Template:
```php
<div class="timbangan-grid">
    <div class="timbangan-card">
        <h3>Timbangan 1</h3>
        <div class="weight">{{ $timbangan1['value'] }} {{ $timbangan1['unit'] }}</div>
        <div class="status {{ $timbangan1['connected'] ? 'online' : 'offline' }}">
            {{ $timbangan1['connected'] ? 'Online' : 'Offline' }}
        </div>
    </div>
    
    <div class="timbangan-card">
        <h3>Timbangan 2</h3>
        <div class="weight">{{ $timbangan2['value'] }} {{ $timbangan2['unit'] }}</div>
        <div class="status {{ $timbangan2['connected'] ? 'online' : 'offline' }}">
            {{ $timbangan2['connected'] ? 'Online' : 'Offline' }}
        </div>
    </div>
    
    <!-- Timbangan 3 & 4 ... -->
</div>
```

## 🌐 Network Setup (Jika PC Terpisah)

### 1. Share Folder Data:
Di PC yang menjalankan Laravel, share folder `data/` agar bisa diakses dari PC lain.

### 2. Set Path di Laravel:
```php
// Jika data di PC lain (network path)
MultiWeightReader::setDataPath('\\\\192.168.1.100\\shared\\timbangan\\data\\');

// Jika data di PC yang sama (local path)  
MultiWeightReader::setDataPath('d:/nodejs/service-timbangan/data/');
```

## 📊 API Endpoints

Setiap timbangan memiliki endpoint sendiri:

### Timbangan 1 (PC 1):
- `http://192.168.1.101:3001/api/weight`
- `http://192.168.1.101:3001/api/status`

### Timbangan 2 (PC 2):
- `http://192.168.1.102:3002/api/weight`
- `http://192.168.1.102:3002/api/status`

### Dan seterusnya...

## 🔧 Troubleshooting

### 1. Port COM Tidak Ditemukan:
- Cek Device Manager
- Pastikan driver timbangan terinstall
- Ganti `portName` sesuai port yang benar

### 2. File Tidak Ditemukan di Laravel:
- Pastikan path benar di `MultiWeightReader::setDataPath()`
- Cek permission folder
- Pastikan Node.js service berjalan

### 3. Data Tidak Update:
- Cek koneksi timbangan di console Node.js
- Pastikan timbangan mengirim data
- Test dengan `POST /api/test-weight`

## ✅ Checklist Setup

- [ ] Copy `timbangan-service.js` ke 4 PC
- [ ] Ganti `TIMBANGAN_ID` dan `portName` di setiap PC
- [ ] Install dependencies: `npm install`
- [ ] Jalankan service: `node timbangan-service.js`
- [ ] Copy `MultiWeightReader.php` ke Laravel
- [ ] Set path data di Laravel
- [ ] Test baca data: `MultiWeightReader::getWeight(1)`