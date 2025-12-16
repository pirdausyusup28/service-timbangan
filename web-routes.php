<?php

// Tambahkan ini ke routes/web.php

use App\Http\Controllers\WeightController;

// Halaman utama monitoring
Route::get('/weight', [WeightController::class, 'index'])->name('weight.index');

// API routes untuk frontend
Route::prefix('api/weight')->group(function () {
    Route::get('/current', [WeightController::class, 'getCurrentWeight']);
    Route::get('/status', [WeightController::class, 'getStatus']);
    Route::post('/zero', [WeightController::class, 'sendZero']);
    Route::post('/request', [WeightController::class, 'requestData']);
    Route::post('/command', [WeightController::class, 'sendCommand']);
});