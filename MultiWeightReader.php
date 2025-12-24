<?php

/**
 * MultiWeightReader - Helper untuk membaca data dari 4 timbangan
 * 
 * Cara penggunaan di Laravel:
 * 1. Copy file ini ke app/Helpers/MultiWeightReader.php
 * 2. Set path data: MultiWeightReader::setDataPath('/path/to/nodejs/data/');
 * 3. Baca data: $weight = MultiWeightReader::getWeight(1); // Timbangan 1
 */

class MultiWeightReader
{
    private static $dataPath = __DIR__ . '/data/';
    
    /**
     * Set path ke folder data Node.js
     */
    public static function setDataPath($path)
    {
        self::$dataPath = rtrim($path, '/') . '/';
    }
    
    /**
     * Baca data timbangan berdasarkan ID (1-4)
     */
    public static function getWeight($timbanganId)
    {
        try {
            $jsonFile = self::$dataPath . "weight_data_{$timbanganId}.json";
            
            if (!file_exists($jsonFile)) {
                return [
                    'timbangan_id' => $timbanganId,
                    'value' => 0,
                    'unit' => 'kg',
                    'timestamp' => date('c'),
                    'status' => 'file_not_found',
                    'connected' => false,
                    'raw' => 'File tidak ditemukan'
                ];
            }
            
            $jsonContent = file_get_contents($jsonFile);
            $data = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON format');
            }
            
            return $data;
            
        } catch (Exception $e) {
            return [
                'timbangan_id' => $timbanganId,
                'value' => 0,
                'unit' => 'kg',
                'timestamp' => date('c'),
                'status' => 'error',
                'connected' => false,
                'raw' => 'Error: ' . $e->getMessage()
            ];
        }
    }
    
    /**
     * Baca data dari file TXT (alternatif)
     */
    public static function getWeightFromTxt($timbanganId)
    {
        try {
            $txtFile = self::$dataPath . "weight_data_{$timbanganId}.txt";
            
            if (!file_exists($txtFile)) {
                return [
                    'timbangan_id' => $timbanganId,
                    'value' => 0,
                    'unit' => 'kg',
                    'timestamp' => date('c'),
                    'status' => 'file_not_found',
                    'connected' => false
                ];
            }
            
            $content = trim(file_get_contents($txtFile));
            $parts = explode('|', $content);
            
            // Format: value|unit|timestamp|status|connected|timbangan_id
            if (count($parts) >= 6) {
                return [
                    'timbangan_id' => intval($parts[5]),
                    'value' => floatval($parts[0]),
                    'unit' => $parts[1],
                    'timestamp' => $parts[2],
                    'status' => $parts[3],
                    'connected' => $parts[4] === 'true'
                ];
            }
            
            throw new Exception('Invalid TXT format');
            
        } catch (Exception $e) {
            return [
                'timbangan_id' => $timbanganId,
                'value' => 0,
                'unit' => 'kg',
                'timestamp' => date('c'),
                'status' => 'error',
                'connected' => false
            ];
        }
    }
    
    /**
     * Baca data semua timbangan (1-4)
     */
    public static function getAllWeights()
    {
        $results = [];
        
        for ($i = 1; $i <= 4; $i++) {
            $results[$i] = self::getWeight($i);
        }
        
        return $results;
    }
    
    /**
     * Cek apakah timbangan terhubung
     */
    public static function isConnected($timbanganId)
    {
        $data = self::getWeight($timbanganId);
        return $data['connected'] ?? false;
    }
    
    /**
     * Dapatkan nilai berat saja (float)
     */
    public static function getWeightValue($timbanganId)
    {
        $data = self::getWeight($timbanganId);
        return $data['value'] ?? 0.0;
    }
    
    /**
     * Dapatkan berat dengan satuan (string)
     */
    public static function getWeightWithUnit($timbanganId)
    {
        $data = self::getWeight($timbanganId);
        return ($data['value'] ?? 0) . ' ' . ($data['unit'] ?? 'kg');
    }
    
    /**
     * Baca history timbangan
     */
    public static function getHistory($timbanganId, $lines = 10)
    {
        try {
            $logFile = self::$dataPath . "weight_log_{$timbanganId}.txt";
            
            if (!file_exists($logFile)) {
                return [];
            }
            
            $content = file_get_contents($logFile);
            $allLines = explode("\n", trim($content));
            
            $lastLines = array_slice($allLines, -$lines);
            
            return array_filter($lastLines);
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Cek apakah data masih fresh
     */
    public static function isDataFresh($timbanganId, $maxAgeSeconds = 30)
    {
        $data = self::getWeight($timbanganId);
        
        if (!isset($data['timestamp'])) {
            return false;
        }
        
        $dataTime = strtotime($data['timestamp']);
        $currentTime = time();
        
        return ($currentTime - $dataTime) <= $maxAgeSeconds;
    }
    
    /**
     * Get status semua timbangan
     */
    public static function getAllStatus()
    {
        $status = [];
        
        for ($i = 1; $i <= 4; $i++) {
            $data = self::getWeight($i);
            $status[$i] = [
                'timbangan_id' => $i,
                'connected' => $data['connected'],
                'status' => $data['status'],
                'value' => $data['value'],
                'unit' => $data['unit'],
                'is_fresh' => self::isDataFresh($i),
                'last_update' => $data['timestamp']
            ];
        }
        
        return $status;
    }
}