<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class WeightController extends Controller
{
    private $nodeApiBase = 'http://localhost:3000/api';
    
    public function index()
    {
        return view('weight.realtime');
    }
    
    public function getCurrentWeight()
    {
        try {
            $response = Http::timeout(5)->get($this->nodeApiBase . '/weight');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil data dari Node.js server'
            ], 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Node.js server tidak dapat dijangkau: ' . $e->getMessage()
            ], 503);
        }
    }
    
    public function getStatus()
    {
        try {
            $response = Http::timeout(5)->get($this->nodeApiBase . '/status');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil status'
            ], 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Node.js server tidak dapat dijangkau'
            ], 503);
        }
    }
    
    public function sendZero()
    {
        try {
            $response = Http::timeout(5)->post($this->nodeApiBase . '/zero');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim perintah zero'
            ], 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Node.js server tidak dapat dijangkau'
            ], 503);
        }
    }
    
    public function requestData()
    {
        try {
            $response = Http::timeout(5)->post($this->nodeApiBase . '/request');
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim request data'
            ], 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Node.js server tidak dapat dijangkau'
            ], 503);
        }
    }
    
    public function sendCommand(Request $request)
    {
        $request->validate([
            'command' => 'required|string|max:50'
        ]);
        
        try {
            $response = Http::timeout(5)->post($this->nodeApiBase . '/command', [
                'command' => $request->command
            ]);
            
            if ($response->successful()) {
                return response()->json($response->json());
            }
            
            return response()->json([
                'success' => false,
                'message' => 'Gagal mengirim perintah'
            ], 503);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Node.js server tidak dapat dijangkau'
            ], 503);
        }
    }
}