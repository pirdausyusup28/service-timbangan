const axios = require('axios');

/**
 * HTTP Pusher - Push data timbangan ke server online (Hostinger)
 */
class HttpPusher {
    constructor(config) {
        this.config = {
            baseUrl: config.baseUrl, // https://yourdomain.com
            apiKey: config.apiKey || 'your-api-key',
            endpoint: config.endpoint || '/api/timbangan/update',
            timeout: config.timeout || 5000,
            retryAttempts: config.retryAttempts || 3,
            retryDelay: config.retryDelay || 2000
        };
        
        this.isOnline = false;
        this.lastPushTime = null;
        this.failedAttempts = 0;
    }
    
    /**
     * Push data ke server online
     */
    async pushWeight(weightData) {
        if (!this.config.baseUrl) {
            console.log('⚠️  HTTP Push: URL server tidak dikonfigurasi');
            return false;
        }
        
        const url = this.config.baseUrl + this.config.endpoint;
        
        const payload = {
            ...weightData,
            source: 'nodejs-service',
            local_timestamp: new Date().toISOString()
        };
        
        try {
            console.log(`🌐 Pushing ke server: ${url}`);
            
            const response = await axios.post(url, payload, {
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': `Bearer ${this.config.apiKey}`,
                    'X-Source': 'timbangan-service'
                },
                timeout: this.config.timeout
            });
            
            if (response.status === 200 || response.status === 201) {
                this.isOnline = true;
                this.lastPushTime = new Date();
                this.failedAttempts = 0;
                
                console.log(`✅ Data berhasil di-push ke server`);
                console.log(`   Response: ${response.data.message || 'OK'}`);
                
                return true;
            } else {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }
            
        } catch (error) {
            this.isOnline = false;
            this.failedAttempts++;
            
            console.error(`❌ Gagal push ke server (attempt ${this.failedAttempts}):`);
            console.error(`   Error: ${error.message}`);
            
            // Retry jika belum mencapai batas
            if (this.failedAttempts < this.config.retryAttempts) {
                console.log(`🔄 Retry dalam ${this.config.retryDelay/1000} detik...`);
                setTimeout(() => {
                    this.pushWeight(weightData);
                }, this.config.retryDelay);
            }
            
            return false;
        }
    }
    
    /**
     * Test koneksi ke server
     */
    async testConnection() {
        const testUrl = this.config.baseUrl + '/api/health';
        
        try {
            const response = await axios.get(testUrl, {
                timeout: this.config.timeout,
                headers: {
                    'Authorization': `Bearer ${this.config.apiKey}`
                }
            });
            
            console.log(`✅ Koneksi ke server OK: ${this.config.baseUrl}`);
            return true;
            
        } catch (error) {
            console.error(`❌ Tidak bisa terhubung ke server: ${error.message}`);
            return false;
        }
    }
    
    /**
     * Get status pusher
     */
    getStatus() {
        return {
            isOnline: this.isOnline,
            lastPushTime: this.lastPushTime,
            failedAttempts: this.failedAttempts,
            serverUrl: this.config.baseUrl
        };
    }
}

module.exports = HttpPusher;