const ftp = require('basic-ftp');
const fs = require('fs');
const path = require('path');

/**
 * FTP Pusher - Upload file data ke Hostinger via FTP
 */
class FtpPusher {
    constructor(config) {
        this.config = {
            host: config.host, // ftp.yourdomain.com
            user: config.user,
            password: config.password,
            secure: config.secure || false, // true untuk FTPS
            remotePath: config.remotePath || '/public_html/storage/timbangan/',
            localPath: config.localPath || './data/',
            uploadInterval: config.uploadInterval || 5000 // 5 detik
        };
        
        this.client = new ftp.Client();
        this.isConnected = false;
        this.lastUploadTime = null;
        this.uploadTimer = null;
    }
    
    /**
     * Koneksi ke FTP server
     */
    async connect() {
        try {
            console.log(`🔌 Menghubungkan ke FTP: ${this.config.host}`);
            
            await this.client.access({
                host: this.config.host,
                user: this.config.user,
                password: this.config.password,
                secure: this.config.secure
            });
            
            this.isConnected = true;
            console.log(`✅ Terhubung ke FTP server`);
            
            // Pastikan direktori remote ada
            await this.ensureRemoteDirectory();
            
            return true;
            
        } catch (error) {
            this.isConnected = false;
            console.error(`❌ Gagal koneksi FTP: ${error.message}`);
            return false;
        }
    }
    
    /**
     * Pastikan direktori remote ada
     */
    async ensureRemoteDirectory() {
        try {
            await this.client.ensureDir(this.config.remotePath);
            console.log(`📁 Direktori remote OK: ${this.config.remotePath}`);
        } catch (error) {
            console.error(`❌ Gagal buat direktori remote: ${error.message}`);
        }
    }
    
    /**
     * Upload file ke server
     */
    async uploadFiles() {
        if (!this.isConnected) {
            console.log('⚠️  FTP tidak terhubung, mencoba koneksi ulang...');
            const connected = await this.connect();
            if (!connected) return false;
        }
        
        try {
            const files = [
                'weight_data.json',
                'weight_data.txt',
                'weight_log.txt'
            ];
            
            for (const filename of files) {
                const localFile = path.join(this.config.localPath, filename);
                const remoteFile = this.config.remotePath + filename;
                
                if (fs.existsSync(localFile)) {
                    await this.client.uploadFrom(localFile, remoteFile);
                    console.log(`📤 Uploaded: ${filename}`);
                } else {
                    console.log(`⚠️  File tidak ada: ${localFile}`);
                }
            }
            
            this.lastUploadTime = new Date();
            console.log(`✅ Semua file berhasil di-upload ke FTP`);
            
            return true;
            
        } catch (error) {
            console.error(`❌ Gagal upload FTP: ${error.message}`);
            this.isConnected = false;
            return false;
        }
    }
    
    /**
     * Start auto upload
     */
    startAutoUpload() {
        if (this.uploadTimer) {
            clearInterval(this.uploadTimer);
        }
        
        console.log(`🔄 Auto upload FTP dimulai (interval: ${this.config.uploadInterval/1000}s)`);
        
        this.uploadTimer = setInterval(async () => {
            await this.uploadFiles();
        }, this.config.uploadInterval);
        
        // Upload pertama kali
        setTimeout(() => this.uploadFiles(), 2000);
    }
    
    /**
     * Stop auto upload
     */
    stopAutoUpload() {
        if (this.uploadTimer) {
            clearInterval(this.uploadTimer);
            this.uploadTimer = null;
            console.log('⏹️  Auto upload FTP dihentikan');
        }
    }
    
    /**
     * Disconnect FTP
     */
    async disconnect() {
        try {
            this.stopAutoUpload();
            this.client.close();
            this.isConnected = false;
            console.log('🔌 FTP disconnected');
        } catch (error) {
            console.error('Error disconnecting FTP:', error.message);
        }
    }
    
    /**
     * Get status
     */
    getStatus() {
        return {
            isConnected: this.isConnected,
            lastUploadTime: this.lastUploadTime,
            host: this.config.host,
            remotePath: this.config.remotePath
        };
    }
}

module.exports = FtpPusher;