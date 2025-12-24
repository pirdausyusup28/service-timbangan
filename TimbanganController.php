<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

/**
 * Contoh Controller Laravel untuk membaca data timbangan
 * 
 * Cara setup:
 * 1. Copy WeightReader.php ke app/Helpers/WeightReader.php
 * 2. Sesuaikan path file di WeightReader::setFilePaths()
 * 3. Tambahkan route di web.php atau api.php
 */

class TimbanganController extends Controller
{
    public function __construct()
    {
        // Set path ke file data timbangan Node.js
        // Sesuaikan dengan lokasi service Node.js Anda
        $basePath = 'd:/nodejs/service-timbangan/data';
        
        require_once app_path('Helpers/WeightReader.php');
        
        \WeightReader::setFilePaths(
            $basePath . '/weight_data.json',
            $basePath . '/weight_data.txt',
            $basePath . '/weight_log.txt'
        );
    }
    
    /**
     * Halaman utama timbangan
     */
    public function index()
    {
        $weightData = \WeightReader::getCurrentWeight();
        $isConnected = \WeightReader::isConnected();
        $history = \WeightReader::getWeightHistory(5);
        
        return view('timbangan.index', compact('weightData', 'isConnected', 'history'));
    }
    
    /**
     * API endpoint untuk mendapatkan data timbangan (JSON)
     */
    public function getWeight(): JsonResponse
    {
        $data = \WeightReader::getCurrentWeight();
        $isFresh = \WeightReader::isDataFresh(30); // Data fresh jika < 30 detik
        
        return response()->json([
            'success' => true,
            'data' => $data,
            'is_fresh' => $isFresh,
            'weight_only' => \WeightReader::getWeightValue(),
            'weight_with_unit' => \WeightReader::getWeightWithUnit()
        ]);
    }
    
    /**
     * API endpoint untuk cek status koneksi
     */
    public function getStatus(): JsonResponse
    {
        $isConnected = \WeightReader::isConnected();
        $data = \WeightReader::getCurrentWeight();
        $isFresh = \WeightReader::isDataFresh(30);
        
        return response()->json([
            'success' => true,
            'connected' => $isConnected,
            'status' => $data['status'] ?? 'unknown',
            'last_update' => $data['timestamp'] ?? null,
            'is_fresh' => $isFresh
        ]);
    }
    
    /**
     * API endpoint untuk mendapatkan history
     */
    public function getHistory(Request $request): JsonResponse
    {
        $lines = $request->get('lines', 10);
        $history = \WeightReader::getWeightHistory($lines);
        
        return response()->json([
            'success' => true,
            'history' => $history,
            'count' => count($history)
        ]);
    }
    
    /**
     * Halaman realtime dengan auto-refresh
     */
    public function realtime()
    {
        return view('timbangan.realtime');
    }
    
    /**
     * API untuk polling data (untuk AJAX)
     */
    public function poll(): JsonResponse
    {
        $data = \WeightReader::getCurrentWeight();
        $isFresh = \WeightReader::isDataFresh(10); // Data fresh jika < 10 detik
        
        return response()->json([
            'timestamp' => now()->toISOString(),
            'weight' => $data,
            'is_fresh' => $isFresh,
            'should_alert' => !$isFresh || !$data['connected']
        ]);
    }
}