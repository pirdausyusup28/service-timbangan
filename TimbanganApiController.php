<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

/**
 * Controller untuk menerima data timbangan dari Node.js service
 * 
 * Endpoint: POST /api/timbangan/update
 */
class TimbanganApiController extends Controller
{
    // API Key untuk keamanan (ganti dengan key yang aman)
    private $apiKey = 'your-secret-api-key';
    
    /**
     * Menerima data timbangan dari Node.js service
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function updateWeight(Request $request): JsonResponse
    {
        try {
            // Validasi API Key
            if (!$this->validateApiKey($request)) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized'
                ], 401);
            }
            
            // Validasi data
            $validated = $request->validate([
                'value' => 'required|numeric',
                'unit' => 'required|string|max:10',
                'timestamp' => 'required|string',
                'status' => 'required|string|max:50',
                'connected' => 'required|boolean',
                'raw' => 'nullable|string'
            ]);
            
            // Tambahkan timestamp server
            $validated['server_received_at'] = now()->toISOString();\n            $validated['server_timestamp'] = now();\n            \n            // Simpan ke cache (untuk akses cepat)\n            Cache::put('timbangan_current_weight', $validated, 300); // 5 menit\n            \n            // Simpan ke file (backup)\n            $this->saveToFile($validated);\n            \n            // Simpan ke database (opsional)\n            $this->saveToDatabase($validated);\n            \n            // Log untuk monitoring\n            Log::info('Timbangan data received', [\n                'value' => $validated['value'],\n                'unit' => $validated['unit'],\n                'status' => $validated['status'],\n                'source_ip' => $request->ip()\n            ]);\n            \n            return response()->json([\n                'success' => true,\n                'message' => 'Data timbangan berhasil diterima',\n                'received_at' => $validated['server_received_at']\n            ]);\n            \n        } catch (\\Illuminate\\Validation\\ValidationException $e) {\n            return response()->json([\n                'success' => false,\n                'message' => 'Data tidak valid',\n                'errors' => $e->errors()\n            ], 422);\n            \n        } catch (\\Exception $e) {\n            Log::error('Error receiving timbangan data', [\n                'error' => $e->getMessage(),\n                'trace' => $e->getTraceAsString()\n            ]);\n            \n            return response()->json([\n                'success' => false,\n                'message' => 'Server error'\n            ], 500);\n        }\n    }\n    \n    /**\n     * Mendapatkan data timbangan terbaru\n     */\n    public function getCurrentWeight(): JsonResponse\n    {\n        try {\n            // Ambil dari cache terlebih dahulu\n            $data = Cache::get('timbangan_current_weight');\n            \n            if (!$data) {\n                // Jika tidak ada di cache, ambil dari file\n                $data = $this->getFromFile();\n            }\n            \n            if (!$data) {\n                return response()->json([\n                    'success' => false,\n                    'message' => 'Data tidak tersedia'\n                ], 404);\n            }\n            \n            // Cek apakah data masih fresh (< 1 menit)\n            $lastUpdate = \\Carbon\\Carbon::parse($data['timestamp']);\n            $isFresh = $lastUpdate->diffInSeconds(now()) < 60;\n            \n            return response()->json([\n                'success' => true,\n                'data' => $data,\n                'is_fresh' => $isFresh,\n                'age_seconds' => $lastUpdate->diffInSeconds(now())\n            ]);\n            \n        } catch (\\Exception $e) {\n            return response()->json([\n                'success' => false,\n                'message' => 'Error mengambil data'\n            ], 500);\n        }\n    }\n    \n    /**\n     * Health check endpoint\n     */\n    public function health(): JsonResponse\n    {\n        return response()->json([\n            'status' => 'ok',\n            'timestamp' => now()->toISOString(),\n            'service' => 'Timbangan API'\n        ]);\n    }\n    \n    /**\n     * Validasi API Key\n     */\n    private function validateApiKey(Request $request): bool\n    {\n        $authHeader = $request->header('Authorization');\n        \n        if (!$authHeader) {\n            return false;\n        }\n        \n        // Format: Bearer your-api-key\n        $token = str_replace('Bearer ', '', $authHeader);\n        \n        return $token === $this->apiKey;\n    }\n    \n    /**\n     * Simpan data ke file\n     */\n    private function saveToFile(array $data): void\n    {\n        try {\n            // Simpan ke storage Laravel\n            $jsonData = json_encode($data, JSON_PRETTY_PRINT);\n            Storage::put('timbangan/current_weight.json', $jsonData);\n            \n            // Simpan ke log file\n            $logEntry = sprintf(\n                \"%s - %s %s - Status: %s - Raw: %s\\n\",\n                $data['timestamp'],\n                $data['value'],\n                $data['unit'],\n                $data['status'],\n                $data['raw'] ?? ''\n            );\n            \n            Storage::append('timbangan/weight_history.log', $logEntry);\n            \n        } catch (\\Exception $e) {\n            Log::error('Error saving to file', ['error' => $e->getMessage()]);\n        }\n    }\n    \n    /**\n     * Ambil data dari file\n     */\n    private function getFromFile(): ?array\n    {\n        try {\n            if (Storage::exists('timbangan/current_weight.json')) {\n                $content = Storage::get('timbangan/current_weight.json');\n                return json_decode($content, true);\n            }\n        } catch (\\Exception $e) {\n            Log::error('Error reading from file', ['error' => $e->getMessage()]);\n        }\n        \n        return null;\n    }\n    \n    /**\n     * Simpan ke database (opsional)\n     */\n    private function saveToDatabase(array $data): void\n    {\n        try {\n            // Uncomment jika ingin simpan ke database\n            /*\n            \\App\\Models\\TimbanganData::create([\n                'value' => $data['value'],\n                'unit' => $data['unit'],\n                'raw_data' => $data['raw'],\n                'status' => $data['status'],\n                'connected' => $data['connected'],\n                'source_timestamp' => $data['timestamp'],\n                'received_at' => $data['server_timestamp']\n            ]);\n            */\n        } catch (\\Exception $e) {\n            Log::error('Error saving to database', ['error' => $e->getMessage()]);\n        }\n    }\n}