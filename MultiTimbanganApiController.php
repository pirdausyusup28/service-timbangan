<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Controller untuk menerima data dari multiple timbangan (1-4)
 * 
 * Endpoint: POST /api/timbangan/update
 */
class MultiTimbanganApiController extends Controller
{
    private $apiKey = 'your-secret-api-key';
    
    /**
     * Menerima data timbangan dari Node.js service
     */
    public function updateWeight(Request $request): JsonResponse
    {
        try {
            if (!$this->validateApiKey($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            $validated = $request->validate([
                'timbangan_id' => 'required|integer|min:1|max:4',
                'value' => 'required|numeric',
                'unit' => 'required|string|max:10',
                'timestamp' => 'required|string',
                'status' => 'required|string|max:50',
                'connected' => 'required|boolean',
                'raw' => 'nullable|string'
            ]);
            
            $validated['server_received_at'] = now()->toISOString();
            $validated['server_timestamp'] = now();
            
            $timbanganId = $validated['timbangan_id'];
            
            // Simpan ke cache per timbangan
            Cache::put("timbangan_current_weight_{$timbanganId}", $validated, 300);
            
            // Simpan ke file
            $this->saveToFile($validated, $timbanganId);
            
            // Log
            Log::info('Timbangan data received', [
                'timbangan_id' => $timbanganId,
                'value' => $validated['value'],
                'unit' => $validated['unit'],
                'status' => $validated['status']
            ]);
            
            return response()->json([
                'success' => true,
                'message' => "Data timbangan {$timbanganId} berhasil diterima",
                'received_at' => $validated['server_received_at']
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Server error'
            ], 500);
        }
    }
    
    /**
     * Mendapatkan data timbangan berdasarkan ID
     */
    public function getCurrentWeight($id = null): JsonResponse
    {
        try {
            if ($id) {
                // Ambil data timbangan spesifik
                $data = Cache::get("timbangan_current_weight_{$id}");
                
                if (!$data) {
                    $data = $this->getFromFile($id);
                }
                
                if (!$data) {
                    return response()->json([
                        'success' => false,
                        'message' => "Data timbangan {$id} tidak tersedia"
                    ], 404);
                }
                
                $lastUpdate = \Carbon\Carbon::parse($data['timestamp']);
                $isFresh = $lastUpdate->diffInSeconds(now()) < 60;
                
                return response()->json([
                    'success' => true,
                    'data' => $data,
                    'is_fresh' => $isFresh,
                    'age_seconds' => $lastUpdate->diffInSeconds(now())
                ]);
            } else {
                // Ambil data semua timbangan
                $allData = [];
                
                for ($i = 1; $i <= 4; $i++) {
                    $data = Cache::get("timbangan_current_weight_{$i}");
                    if (!$data) {
                        $data = $this->getFromFile($i);
                    }
                    
                    if ($data) {
                        $lastUpdate = \Carbon\Carbon::parse($data['timestamp']);
                        $data['is_fresh'] = $lastUpdate->diffInSeconds(now()) < 60;
                        $data['age_seconds'] = $lastUpdate->diffInSeconds(now());
                        $allData[$i] = $data;
                    }
                }
                
                return response()->json([
                    'success' => true,
                    'data' => $allData,
                    'count' => count($allData)
                ]);
            }
            
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error mengambil data'
            ], 500);
        }
    }
    
    /**
     * Health check
     */
    public function health(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'service' => 'Multi Timbangan API'
        ]);
    }
    
    private function validateApiKey(Request $request): bool
    {
        $authHeader = $request->header('Authorization');
        if (!$authHeader) return false;
        
        $token = str_replace('Bearer ', '', $authHeader);
        return $token === $this->apiKey;
    }
    
    private function saveToFile(array $data, int $timbanganId): void
    {
        try {
            $jsonData = json_encode($data, JSON_PRETTY_PRINT);
            Storage::put("timbangan/current_weight_{$timbanganId}.json", $jsonData);
            
            $logEntry = sprintf(
                "%s - Timbangan %d - %s %s - Status: %s - Raw: %s\n",
                $data['timestamp'],
                $timbanganId,
                $data['value'],
                $data['unit'],
                $data['status'],
                $data['raw'] ?? ''
            );
            
            Storage::append("timbangan/weight_history_{$timbanganId}.log", $logEntry);
            
        } catch (\Exception $e) {
            Log::error('Error saving to file', ['error' => $e->getMessage()]);
        }
    }
    
    private function getFromFile(int $timbanganId): ?array
    {
        try {
            if (Storage::exists("timbangan/current_weight_{$timbanganId}.json")) {
                $content = Storage::get("timbangan/current_weight_{$timbanganId}.json");
                return json_decode($content, true);
            }
        } catch (\Exception $e) {
            Log::error('Error reading from file', ['error' => $e->getMessage()]);
        }
        
        return null;
    }
}