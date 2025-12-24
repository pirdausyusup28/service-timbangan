<?php
// Controller Laravel untuk endpoint file polling

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class TimbanganPollingController extends Controller
{
    public function getAllWeights()
    {
        require_once app_path('Helpers/MultiWeightReader.php');
        
        // Set path data
        \MultiWeightReader::setDataPath('d:/nodejs/service-timbangan/data/');
        
        $weights = [];
        for ($i = 1; $i <= 4; $i++) {
            $weights[$i] = \MultiWeightReader::getWeight($i);
        }
        
        return response()->json([
            'success' => true,
            'data' => $weights,
            'timestamp' => now()->toISOString()
        ]);
    }
}

// Route: Route::get('/api/timbangan/all', [TimbanganPollingController::class, 'getAllWeights']);
?>