/**
 * Konfigurasi untuk Push Data Timbangan ke Server Online
 * 
 * Copy file ini ke config.js dan sesuaikan dengan server Anda
 */

module.exports = {
    // ============================================
    // KONFIGURASI HTTP PUSH
    // ============================================
    http: {
        enabled: true,                              // Set false untuk disable
        baseUrl: 'https://yourdomain.com',          // Domain Hostinger Anda
        apiKey: 'your-secret-api-key-here',         // API Key untuk keamanan
        endpoint: '/api/timbangan/update',          // Endpoint Laravel
        timeout: 10000,                             // Timeout dalam ms
        retryAttempts: 3,                           // Jumlah retry
        retryDelay: 3000                            // Delay retry dalam ms
    },
    
    // ============================================
    // KONFIGURASI FTP PUSH
    // ============================================
    ftp: {
        enabled: false,                             // Set true untuk enable
        host: 'ftp.yourdomain.com',                 // FTP Host Hostinger
        user: 'your-ftp-username',                  // Username FTP
        password: 'your-ftp-password',              // Password FTP
        secure: false,                              // true untuk FTPS
        remotePath: '/public_html/storage/timbangan/', // Path di server
        localPath: './data/',                       // Path lokal
        uploadInterval: 5000                        // Upload interval (ms)
    },
    
    // ============================================
    // KONFIGURASI DATABASE PUSH (Opsional)
    // ============================================
    database: {
        enabled: false,                             // Set true untuk enable
        type: 'mysql',                              // mysql, postgresql, sqlite
        host: 'localhost',
        port: 3306,
        database: 'timbangan_db',
        username: 'db_user',
        password: 'db_password',
        table: 'weight_data',
        batchSize: 10,                              // Batch insert
        flushInterval: 30000                        // Flush setiap 30 detik
    },
    
    // ============================================
    // KONFIGURASI WEBSOCKET PUSH (Opsional)
    // ============================================
    websocket: {
        enabled: false,                             // Set true untuk enable
        serverUrl: 'wss://yourdomain.com/ws',       // WebSocket server
        reconnectInterval: 5000,                    // Reconnect interval
        heartbeatInterval: 30000                    // Heartbeat interval
    }
};

/**
 * CARA SETUP DI HOSTINGER:
 * 
 * 1. HTTP PUSH (Recommended):
 *    - Upload TimbanganApiController.php ke Laravel
 *    - Tambahkan route: POST /api/timbangan/update
 *    - Set baseUrl ke domain Hostinger Anda
 *    - Ganti apiKey dengan key yang aman
 * 
 * 2. FTP PUSH:
 *    - Dapatkan kredensial FTP dari cPanel Hostinger
 *    - Set remotePath ke folder yang bisa diakses Laravel
 *    - Laravel baca file dari Storage::get()
 * 
 * 3. DATABASE PUSH:
 *    - Buat tabel di database Hostinger
 *    - Install mysql2 atau pg untuk koneksi database
 *    - Set kredensial database
 * 
 * KEAMANAN:
 * - Ganti semua password dan API key
 * - Gunakan HTTPS untuk HTTP push
 * - Batasi akses IP jika perlu
 * - Enable firewall di Hostinger
 */