<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TimbanganController extends Controller
{
    private $timbanganConfig = [
        1 => ['ip' => '192.168.1.101', 'port' => 3001, 'ws' => 8081],
        2 => ['ip' => '192.168.1.102', 'port' => 3002, 'ws' => 8082],
        3 => ['ip' => '192.168.1.103', 'port' => 3003, 'ws' => 8083],
        4 => ['ip' => '192.168.1.104', 'port' => 3004, 'ws' => 8084],
    ];
    
    /**
     * Halaman pilih timbangan
     */
    public function index()
    {
        return view('timbangan.index', [
            'timbanganConfig' => $this->timbanganConfig
        ]);
    }
    
    /**
     * Dashboard timbangan spesifik
     */
    public function show($id)
    {
        if (!isset($this->timbanganConfig[$id])) {
            abort(404, 'Timbangan tidak ditemukan');
        }
        
        $config = $this->timbanganConfig[$id];
        
        return view('timbangan.show', [
            'timbanganId' => $id,
            'config' => $config
        ]);
    }
    
    /**
     * API: Get data timbangan
     */
    public function getData($id)
    {
        if (!isset($this->timbanganConfig[$id])) {
            return response()->json([
                'success' => false,
                'message' => 'Timbangan tidak ditemukan'
            ], 404);
        }
        
        $config = $this->timbanganConfig[$id];
        
        try {
            // Coba akses langsung ke PC timbangan
            $response = Http::timeout(5)->get("http://{$config['ip']}:{$config['port']}/api/weight");
            
            if ($response->successful()) {
                $data = $response->json();
                return response()->json([
                    'success' => true,
                    'source' => 'direct_api',
                    'timbangan_id' => $id,
                    'data' => $data['data'] ?? null
                ]);
            }
        } catch (\Exception $e) {
            // Fallback ke file lokal jika API gagal
        }
        
        // Fallback: baca file lokal
        return $this->getDataFromFile($id);
    }
    
    /**
     * Baca data dari file lokal
     */
    private function getDataFromFile($id)
    {
        $filePath = storage_path("app/timbangan/weight_data_{$id}.json");
        
        if (file_exists($filePath)) {
            $data = json_decode(file_get_contents($filePath), true);
            
            return response()->json([
                'success' => true,
                'source' => 'local_file',
                'timbangan_id' => $id,
                'data' => $data
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => 'Data timbangan tidak tersedia',
            'timbangan_id' => $id
        ], 404);
    }
    
    /**
     * API: Get semua timbangan
     */
    public function getAllData()
    {
        $results = [];
        
        foreach ($this->timbanganConfig as $id => $config) {
            $response = $this->getData($id);
            $results[$id] = $response->getData(true);
        }
        
        return response()->json([
            'success' => true,
            'data' => $results
        ]);
    }
    
    /**
     * API: Get config timbangan untuk frontend
     */
    public function getConfig()
    {
        return response()->json([
            'success' => true,
            'config' => $this->timbanganConfig
        ]);
    }
}