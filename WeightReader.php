<?php

/**
 * WeightReader - Helper class untuk membaca data timbangan dari Node.js
 * 
 * Cara penggunaan di Laravel:
 * 1. Copy file ini ke app/Helpers/WeightReader.php
 * 2. Gunakan: $weight = WeightReader::getCurrentWeight();
 */

class WeightReader
{
    // Path ke file data timbangan (sesuaikan dengan lokasi Node.js service)
    private static $jsonFile = __DIR__ . '/data/weight_data.json';
    private static $txtFile = __DIR__ . '/data/weight_data.txt';
    private static $logFile = __DIR__ . '/data/weight_log.txt';
    
    /**
     * Baca data timbangan dari file JSON
     * 
     * @return array|null
     */
    public static function getCurrentWeight()
    {
        try {
            if (!file_exists(self::$jsonFile)) {
                return [
                    'value' => 0,
                    'unit' => 'kg',
                    'timestamp' => date('c'),
                    'status' => 'file_not_found',
                    'connected' => false,
                    'raw' => 'File tidak ditemukan'
                ];
            }
            
            $jsonContent = file_get_contents(self::$jsonFile);
            $data = json_decode($jsonContent, true);
            
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON format');
            }
            
            return $data;
            
        } catch (Exception $e) {
            return [
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
     * Baca data timbangan dari file TXT (format: value|unit|timestamp|status|connected)
     * 
     * @return array|null
     */
    public static function getCurrentWeightFromTxt()
    {
        try {
            if (!file_exists(self::$txtFile)) {
                return [
                    'value' => 0,
                    'unit' => 'kg',
                    'timestamp' => date('c'),
                    'status' => 'file_not_found',
                    'connected' => false
                ];
            }
            
            $content = trim(file_get_contents(self::$txtFile));
            $parts = explode('|', $content);
            
            if (count($parts) >= 5) {
                return [
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
                'value' => 0,
                'unit' => 'kg',
                'timestamp' => date('c'),
                'status' => 'error',
                'connected' => false
            ];
        }
    }
    
    /**
     * Cek apakah timbangan terhubung
     * 
     * @return bool
     */
    public static function isConnected()
    {
        $data = self::getCurrentWeight();
        return $data['connected'] ?? false;
    }
    
    /**
     * Dapatkan nilai berat saja (float)
     * 
     * @return float
     */
    public static function getWeightValue()
    {
        $data = self::getCurrentWeight();
        return $data['value'] ?? 0.0;
    }
    
    /**
     * Dapatkan berat dengan satuan (string)
     * 
     * @return string
     */
    public static function getWeightWithUnit()
    {
        $data = self::getCurrentWeight();
        return ($data['value'] ?? 0) . ' ' . ($data['unit'] ?? 'kg');
    }
    
    /**
     * Baca log history timbangan (10 baris terakhir)
     * 
     * @param int $lines Jumlah baris yang ingin dibaca
     * @return array
     */
    public static function getWeightHistory($lines = 10)
    {
        try {
            if (!file_exists(self::$logFile)) {
                return [];
            }
            
            $content = file_get_contents(self::$logFile);
            $allLines = explode("\n", trim($content));
            
            // Ambil baris terakhir
            $lastLines = array_slice($allLines, -$lines);
            
            return array_filter($lastLines); // Hapus baris kosong
            
        } catch (Exception $e) {
            return [];
        }
    }
    
    /**
     * Set path custom untuk file data (jika berbeda lokasi)
     * 
     * @param string $jsonPath
     * @param string $txtPath
     * @param string $logPath
     */
    public static function setFilePaths($jsonPath, $txtPath = null, $logPath = null)
    {
        self::$jsonFile = $jsonPath;
        if ($txtPath) self::$txtFile = $txtPath;
        if ($logPath) self::$logFile = $logPath;
    }
    
    /**
     * Cek apakah data masih fresh (tidak lebih dari X detik)
     * 
     * @param int $maxAgeSeconds
     * @return bool
     */
    public static function isDataFresh($maxAgeSeconds = 30)
    {
        $data = self::getCurrentWeight();
        
        if (!isset($data['timestamp'])) {
            return false;
        }
        
        $dataTime = strtotime($data['timestamp']);
        $currentTime = time();
        
        return ($currentTime - $dataTime) <= $maxAgeSeconds;
    }
}