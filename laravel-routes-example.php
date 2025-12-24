<?php

/**
 * Contoh Routes untuk Laravel - Timbangan Integration
 * 
 * Tambahkan routes ini ke file routes/web.php atau routes/api.php
 */

use App\Http\Controllers\TimbanganController;

// Routes untuk Web Interface
Route::prefix('timbangan')->group(function () {
    // Halaman utama timbangan
    Route::get('/', [TimbanganController::class, 'index'])->name('timbangan.index');
    
    // Halaman realtime
    Route::get('/realtime', [TimbanganController::class, 'realtime'])->name('timbangan.realtime');
});

// API Routes untuk AJAX/JSON
Route::prefix('api/timbangan')->group(function () {
    // Mendapatkan data timbangan terbaru
    Route::get('/weight', [TimbanganController::class, 'getWeight']);
    
    // Cek status koneksi
    Route::get('/status', [TimbanganController::class, 'getStatus']);
    
    // Mendapatkan history
    Route::get('/history', [TimbanganController::class, 'getHistory']);
    
    // Polling untuk realtime (digunakan oleh JavaScript)
    Route::get('/poll', [TimbanganController::class, 'poll']);
});

/**
 * Contoh penggunaan di Controller lain:
 * 
 * // Di controller manapun
 * public function someMethod()
 * {
 *     // Include WeightReader
 *     require_once app_path('Helpers/WeightReader.php');
 *     
 *     // Set path file (sesuaikan dengan lokasi Node.js service)
 *     \WeightReader::setFilePaths(
 *         'd:/nodejs/service-timbangan/data/weight_data.json'
 *     );
 *     
 *     // Ambil data
 *     $currentWeight = \WeightReader::getCurrentWeight();
 *     $weightValue = \WeightReader::getWeightValue();
 *     $isConnected = \WeightReader::isConnected();
 *     
 *     // Gunakan data...
 *     return view('some-view', compact('currentWeight', 'weightValue', 'isConnected'));
 * }
 */