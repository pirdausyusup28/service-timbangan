<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimbanganController extends Controller
{
    /**
     * Pilih timbangan dan baca data langsung
     */
    public function getTimbangan($id)
    {
        // Path ke file data (sesuaikan dengan lokasi PC timbangan)
        $dataPath = "\\\\192.168.1.10{$id}\\shared\\timbangan\\data\\"; // Network path
        // atau local: $dataPath = "d:\\shared\\timbangan\\data\\";
        
        $jsonFile = $dataPath . "weight_data_{$id}.json";
        
        if (file_exists($jsonFile)) {
            $data = json_decode(file_get_contents($jsonFile), true);
            
            return response()->json([
                'success' => true,
                'timbangan_id' => $id,
                'data' => $data,
                'source' => 'file_local'
            ]);
        }
        
        return response()->json([
            'success' => false,
            'message' => "Timbangan {$id} tidak ditemukan"
        ], 404);
    }
    
    /**
     * Halaman pilih timbangan
     */
    public function index()
    {
        return view('timbangan.pilih');
    }
    
    /**
     * Dashboard timbangan spesifik
     */
    public function dashboard($id)
    {
        return view('timbangan.dashboard', compact('id'));
    }
}