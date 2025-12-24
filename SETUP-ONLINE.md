# 🌐 Setup Online Push ke Hostinger

Panduan lengkap untuk push data timbangan ke server Hostinger agar bisa diakses secara online.

## 🎯 Metode Push yang Tersedia

### 1. **HTTP API Push** (Recommended) ⭐
- Data dikirim via HTTP POST ke Laravel API
- Realtime, reliable, dan secure
- Mudah monitoring dan debugging

### 2. **FTP File Upload**
- Upload file JSON/TXT via FTP
- Cocok jika tidak bisa setup API
- Sedikit delay karena upload berkala

### 3. **Database Direct Push** (Advanced)
- Langsung insert ke database MySQL
- Paling cepat, tapi butuh setup database

## 🚀 Setup HTTP API Push (Recommended)

### Step 1: Setup di Laravel (Hostinger)

#### A. Upload Controller
```bash
# Upload TimbanganApiController.php ke:
/public_html/app/Http/Controllers/Api/TimbanganApiController.php
```

#### B. Tambahkan Routes
Tambahkan ke `routes/api.php`:
```php
Route::prefix('timbangan')->group(function () {
    Route::post('/update', [TimbanganApiController::class, 'updateWeight']);
    Route::get('/current', [TimbanganApiController::class, 'getCurrentWeight']);
    Route::get('/health', [TimbanganApiController::class, 'health']);
});
```

#### C. Set API Key
Edit `TimbanganApiController.php`:
```php
private $apiKey = 'ganti-dengan-key-yang-aman-123456';
```

#### D. Test Endpoint
```bash
# Test health check
curl https://yourdomain.com/api/timbangan/health

# Test manual push
curl -X POST https://yourdomain.com/api/timbangan/update \
  -H "Authorization: Bearer your-api-key" \
  -H "Content-Type: application/json" \
  -d '{"value":15.5,"unit":"kg","timestamp":"2024-01-01T10:00:00Z","status":"test","connected":true}'
```

### Step 2: Setup di Node.js Service

#### A. Install Dependencies
```bash
cd d:\nodejs\service-timbangan
npm install axios basic-ftp
```

#### B. Konfigurasi
Edit `index.js`, bagian `HTTP_CONFIG`:
```javascript
const HTTP_CONFIG = {
  baseUrl: 'https://yourdomain.com',        // Domain Hostinger Anda
  apiKey: 'ganti-dengan-key-yang-aman-123456', // Sama dengan Laravel
  endpoint: '/api/timbangan/update',
  timeout: 10000,
  retryAttempts: 3,
  retryDelay: 3000
};

const ENABLE_HTTP_PUSH = true;  // Aktifkan HTTP push
```

#### C. Test Koneksi
```bash
node index.js
# Lihat log: "✅ Koneksi ke server OK"
```

## 🔧 Setup FTP Push (Alternative)

### Step 1: Dapatkan Kredensial FTP

1. Login ke cPanel Hostinger
2. Buka **File Manager** atau **FTP Accounts**
3. Catat:
   - FTP Host: `ftp.yourdomain.com`
   - Username: `username@yourdomain.com`
   - Password: `your-password`

### Step 2: Konfigurasi Node.js

Edit `index.js`, bagian `FTP_CONFIG`:
```javascript
const FTP_CONFIG = {
  host: 'ftp.yourdomain.com',
  user: 'username@yourdomain.com',
  password: 'your-ftp-password',
  secure: false,
  remotePath: '/public_html/storage/timbangan/',
  localPath: './data/',
  uploadInterval: 5000
};

const ENABLE_FTP_PUSH = true;  // Aktifkan FTP push
```

### Step 3: Setup Laravel untuk Baca File FTP

```php
// Di Controller Laravel
public function getCurrentWeight()
{
    $filePath = storage_path('app/timbangan/weight_data.json');
    
    if (file_exists($filePath)) {
        $data = json_decode(file_get_contents($filePath), true);
        return response()->json(['success' => true, 'data' => $data]);
    }
    
    return response()->json(['success' => false, 'message' => 'Data not found']);
}
```

## 📱 Akses Data dari Website

### Via JavaScript (AJAX)
```javascript
// Ambil data timbangan
async function getWeight() {
    try {
        const response = await fetch('/api/timbangan/current');
        const data = await response.json();
        
        if (data.success) {
            document.getElementById('weight').textContent = 
                data.data.value + ' ' + data.data.unit;
        }
    } catch (error) {
        console.error('Error:', error);
    }
}

// Auto refresh setiap 3 detik
setInterval(getWeight, 3000);
```

### Via PHP
```php
// Di Blade template atau Controller
$response = file_get_contents('https://yourdomain.com/api/timbangan/current');
$data = json_decode($response, true);

if ($data['success']) {
    $weight = $data['data']['value'] . ' ' . $data['data']['unit'];
    $status = $data['data']['connected'] ? 'Online' : 'Offline';
}
```

## 🔒 Keamanan

### 1. API Key
```php
// Gunakan key yang kuat
private $apiKey = 'timbangan_' . hash('sha256', 'your-secret-phrase-here');
```

### 2. IP Whitelist (Opsional)
```php
// Di Controller
private function validateIP(Request $request): bool
{
    $allowedIPs = ['192.168.1.100', '203.0.113.1']; // IP Node.js service
    return in_array($request->ip(), $allowedIPs);
}
```

### 3. Rate Limiting
```php
// Di routes/api.php
Route::middleware(['throttle:60,1'])->group(function () {
    Route::post('/timbangan/update', [TimbanganApiController::class, 'updateWeight']);
});
```

## 📊 Monitoring & Debugging

### 1. Log di Laravel
```bash
# Lihat log Laravel
tail -f /public_html/storage/logs/laravel.log
```

### 2. Log di Node.js
```bash
# Console output Node.js service
✅ Data berhasil di-push ke server
❌ Gagal push ke server (attempt 1): Connection timeout
```

### 3. Health Check
```bash
# Cek status service
curl https://yourdomain.com/api/timbangan/health

# Response:
{
  "status": "ok",
  "timestamp": "2024-01-01T10:00:00Z",
  "service": "Timbangan API"
}
```

## 🚨 Troubleshooting

### Error: Connection Timeout
```bash
# Cek firewall Hostinger
# Pastikan port 80/443 terbuka
# Test dengan curl manual
```

### Error: 401 Unauthorized
```bash
# Cek API key di Node.js dan Laravel
# Pastikan format header: "Authorization: Bearer your-key"
```

### Error: 422 Validation Error
```bash
# Cek format data yang dikirim
# Pastikan semua field required ada
```

### FTP Connection Failed
```bash
# Cek kredensial FTP di cPanel
# Pastikan FTP service aktif
# Test dengan FTP client manual
```

## 📈 Performance Tips

1. **Caching**: Gunakan Redis/Memcached di Laravel
2. **Database**: Simpan ke database untuk query kompleks
3. **CDN**: Gunakan Cloudflare untuk static files
4. **Compression**: Enable gzip di server
5. **Batch**: Kirim data batch jika volume tinggi

## 🔄 Auto Restart Service

### Windows (Task Scheduler)
```batch
@echo off
cd /d "d:\nodejs\service-timbangan"
node index.js
```

### Linux (systemd)
```ini
[Unit]
Description=Timbangan Service
After=network.target

[Service]
Type=simple
User=nodejs
WorkingDirectory=/path/to/service-timbangan
ExecStart=/usr/bin/node index.js
Restart=always

[Install]
WantedBy=multi-user.target
```

## 📞 Support

Jika ada masalah:
1. Cek log Node.js service
2. Cek log Laravel (`storage/logs/laravel.log`)
3. Test endpoint manual dengan curl/Postman
4. Periksa konfigurasi firewall/DNS
5. Hubungi support Hostinger jika perlu