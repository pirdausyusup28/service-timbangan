# 🏗️ Timbangan Realtime - Laravel Integration

Service Node.js untuk membaca data timbangan dan menyimpannya ke file yang bisa dibaca oleh aplikasi Laravel secara realtime.

## 📁 File Output untuk Laravel

Service ini akan membuat 3 file di folder `data/`:

1. **`weight_data.json`** - Data timbangan dalam format JSON
2. **`weight_data.txt`** - Data timbangan dalam format text sederhana  
3. **`weight_log.txt`** - History/log semua pembacaan timbangan

## 🚀 Cara Setup

### 1. Jalankan Node.js Service

```bash
cd d:\nodejs\service-timbangan
npm install
node index.js
```

### 2. Setup di Laravel

#### A. Copy Helper Class
```bash
# Copy WeightReader.php ke Laravel project
cp WeightReader.php /path/to/laravel/app/Helpers/WeightReader.php
```

#### B. Copy Controller (Opsional)
```bash
# Copy controller contoh
cp TimbanganController.php /path/to/laravel/app/Http/Controllers/TimbanganController.php
```

#### C. Tambahkan Routes
Tambahkan isi dari `laravel-routes-example.php` ke file `routes/web.php` atau `routes/api.php`

#### D. Copy View (Opsional)
```bash
# Copy view untuk halaman realtime
cp timbangan-realtime.blade.php /path/to/laravel/resources/views/timbangan/realtime.blade.php
```

## 📊 Format Data

### JSON Format (`weight_data.json`)
```json
{
  "value": 15.75,
  "unit": "kg",
  "raw": "15.75 kg",
  "timestamp": "2024-01-15T10:30:45.123Z",
  "status": "active",
  "connected": true
}
```

### TXT Format (`weight_data.txt`)
```
15.75|kg|2024-01-15T10:30:45.123Z|active|true
```
Format: `value|unit|timestamp|status|connected`

### Log Format (`weight_log.txt`)
```
2024-01-15T10:30:45.123Z - 15.75 kg - Status: active - Raw: 15.75 kg
2024-01-15T10:30:47.456Z - 16.20 kg - Status: active - Raw: 16.20 kg
```

## 💻 Penggunaan di Laravel

### Cara Sederhana
```php
<?php
// Include helper
require_once app_path('Helpers/WeightReader.php');

// Set path file (sesuaikan dengan lokasi Node.js service)
WeightReader::setFilePaths('d:/nodejs/service-timbangan/data/weight_data.json');

// Ambil data
$weight = WeightReader::getCurrentWeight();
$weightValue = WeightReader::getWeightValue(); // float
$weightWithUnit = WeightReader::getWeightWithUnit(); // "15.75 kg"
$isConnected = WeightReader::isConnected(); // boolean
$isFresh = WeightReader::isDataFresh(30); // Data < 30 detik

echo "Berat: " . $weightWithUnit;
echo "Status: " . ($isConnected ? "Terhubung" : "Terputus");
```

### Dalam Controller
```php
<?php
namespace App\Http\Controllers;

class YourController extends Controller
{
    public function index()
    {
        require_once app_path('Helpers/WeightReader.php');
        
        WeightReader::setFilePaths('d:/nodejs/service-timbangan/data/weight_data.json');
        
        $data = [
            'weight' => WeightReader::getCurrentWeight(),
            'connected' => WeightReader::isConnected(),
            'history' => WeightReader::getWeightHistory(10)
        ];
        
        return view('dashboard', $data);
    }
}
```

### API Endpoint
```php
<?php
// Route: GET /api/weight
public function getWeight()
{
    require_once app_path('Helpers/WeightReader.php');
    WeightReader::setFilePaths('d:/nodejs/service-timbangan/data/weight_data.json');
    
    return response()->json([
        'success' => true,
        'data' => WeightReader::getCurrentWeight(),
        'weight_only' => WeightReader::getWeightValue(),
        'is_fresh' => WeightReader::isDataFresh(30)
    ]);
}
```

### Realtime dengan AJAX
```javascript
// Polling setiap 2 detik
setInterval(async () => {
    const response = await fetch('/api/weight');
    const data = await response.json();
    
    document.getElementById('weight').textContent = 
        data.weight_only + ' ' + data.data.unit;
    
    document.getElementById('status').textContent = 
        data.data.connected ? 'Terhubung' : 'Terputus';
}, 2000);
```

## 🔧 Konfigurasi Path

Sesuaikan path file di Laravel sesuai lokasi Node.js service:

```php
// Jika Node.js service di server yang sama
WeightReader::setFilePaths('/path/to/nodejs/service-timbangan/data/weight_data.json');

// Jika menggunakan network share (Windows)
WeightReader::setFilePaths('\\\\server-ip\\shared\\service-timbangan\\data\\weight_data.json');

// Jika menggunakan symbolic link
WeightReader::setFilePaths('/var/www/shared/timbangan/weight_data.json');
```

## 📡 Endpoint API Node.js

Service Node.js juga menyediakan REST API dan WebSocket:

- **REST API**: `http://localhost:3000/api/weight`
- **WebSocket**: `ws://localhost:8081`

Anda bisa menggunakan API ini sebagai alternatif membaca file:

```php
// Alternatif: Baca via HTTP API
$response = file_get_contents('http://localhost:3000/api/weight');
$data = json_decode($response, true);
$weight = $data['data'];
```

## 🔍 Troubleshooting

### File Tidak Ditemukan
- Pastikan Node.js service berjalan
- Periksa path file di `WeightReader::setFilePaths()`
- Pastikan folder `data/` ada dan writable

### Data Tidak Update
- Cek koneksi timbangan di Node.js service
- Periksa log di console Node.js
- Pastikan file tidak di-lock oleh aplikasi lain

### Permission Error
```bash
# Linux/Mac: Berikan permission
chmod 755 /path/to/data/folder
chmod 644 /path/to/data/*.json

# Windows: Pastikan folder accessible
```

## 📋 Checklist Setup

- [ ] Node.js service berjalan
- [ ] File `weight_data.json` terbuat di folder `data/`
- [ ] WeightReader.php di-copy ke Laravel
- [ ] Path file disesuaikan di Laravel
- [ ] Routes ditambahkan (jika pakai controller)
- [ ] Test baca data: `WeightReader::getCurrentWeight()`

## 🎯 Tips Penggunaan

1. **Caching**: Gunakan Laravel cache untuk mengurangi I/O file
2. **Validation**: Selalu cek `isDataFresh()` untuk data realtime
3. **Error Handling**: WeightReader selalu return data, cek field `status`
4. **Performance**: Untuk aplikasi high-traffic, pertimbangkan Redis/Database
5. **Monitoring**: Monitor file `weight_log.txt` untuk debugging

## 📞 Support

Jika ada masalah:
1. Cek console Node.js service
2. Periksa file log: `weight_log.txt`
3. Test manual: buka `weight_data.json` di browser/editor
4. Pastikan timbangan terhubung ke COM port yang benar