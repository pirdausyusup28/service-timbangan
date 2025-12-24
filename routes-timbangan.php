<?php

// routes/web.php

use App\Http\Controllers\TimbanganController;

// Halaman pilih timbangan
Route::get('/timbangan', [TimbanganController::class, 'index']);

// Dashboard timbangan spesifik
Route::get('/timbangan/{id}/dashboard', [TimbanganController::class, 'dashboard']);

// API untuk akses data timbangan (tanpa key)
Route::get('/api/timbangan/{id}', [TimbanganController::class, 'getTimbangan']);

/**
 * Cara akses:
 * 
 * 1. Pilih Timbangan: http://yourdomain.com/timbangan
 * 2. Dashboard: http://yourdomain.com/timbangan/1/dashboard
 * 3. API Data: http://yourdomain.com/api/timbangan/1
 * 
 * Akses langsung ke PC:
 * - http://192.168.1.101:3001/api/weight (Timbangan 1)
 * - http://192.168.1.102:3002/api/weight (Timbangan 2)
 * - http://192.168.1.103:3003/api/weight (Timbangan 3)
 * - http://192.168.1.104:3004/api/weight (Timbangan 4)
 */