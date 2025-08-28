<?php
/**
 * Supabase Configuration and API Client
 * This file handles Supabase REST API calls and configuration
 */

class SupabaseClient {
    private $url;
    private $serviceKey;
    private $anonKey;
    private $storageBucket;
    
    public function __construct() {
        $this->url = $_ENV['SUPABASE_URL'] ?? '';
        $this->serviceKey = $_ENV['SUPABASE_SERVICE_KEY'] ?? '';
        $this->anonKey = $_ENV['SUPABASE_ANON_KEY'] ?? '';
        $this->storageBucket = $_ENV['SUPABASE_STORAGE_BUCKET'] ?? 'videos';
        
        if (empty($this->url) || empty($this->serviceKey)) {
            throw new Exception('Supabase configuration incomplete');
        }
    }
    
    /**
     * Make authenticated request to Supabase REST API
     */
    public function request($endpoint, $method = 'GET', $data = null, $useServiceKey = true) {
        $url = rtrim($this->url, '/') . '/rest/v1/' . ltrim($endpoint, '/');
        
        $headers = [
            'Content-Type: application/json',
            'apikey: ' . ($useServiceKey ? $this->serviceKey : $this->anonKey),
            'Authorization: Bearer ' . ($useServiceKey ? $this->serviceKey : $this->anonKey)
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        if ($data && in_array($method, ['POST', 'PUT', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL error: " . $error);
        }
        
        return [
            'status' => $httpCode,
            'data' => json_decode($response, true),
            'raw' => $response
        ];
    }
    
    /**
     * Upload file to Supabase Storage
     */
    public function uploadFile($filePath, $storagePath, $contentType = null) {
        if (!file_exists($filePath)) {
            throw new Exception("File not found: {$filePath}");
        }
        
        $url = rtrim($this->url, '/') . '/storage/v1/object/' . $this->storageBucket . '/' . ltrim($storagePath, '/');
        
        $fileContent = file_get_contents($filePath);
        $fileName = basename($filePath);
        
        if (!$contentType) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            $contentType = finfo_file($finfo, $filePath);
            finfo_close($finfo);
        }
        
        $headers = [
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: ' . $contentType,
            'Cache-Control: 3600'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $fileContent,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Upload error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Upload failed with status: {$httpCode}");
        }
        
        $result = json_decode($response, true);
        return $result['Key'] ?? $storagePath;
    }
    
    /**
     * Delete file from Supabase Storage
     */
    public function deleteFile($storagePath) {
        $url = rtrim($this->url, '/') . '/storage/v1/object/' . $this->storageBucket . '/' . ltrim($storagePath, '/');
        
        $headers = [
            'Authorization: Bearer ' . $this->serviceKey
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => 'DELETE',
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Delete error: " . $error);
        }
        
        return $httpCode === 200;
    }
    
    /**
     * Get signed URL for file access
     */
    public function getSignedUrl($storagePath, $expiresIn = 3600) {
        $url = rtrim($this->url, '/') . '/storage/v1/object/sign/' . $this->storageBucket . '/' . ltrim($storagePath, '/');
        
        $data = [
            'expiresIn' => $expiresIn
        ];
        
        $headers = [
            'Authorization: Bearer ' . $this->serviceKey,
            'Content-Type: application/json'
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("Signed URL error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("Signed URL failed with status: {$httpCode}");
        }
        
        $result = json_decode($response, true);
        return $result['signedURL'] ?? null;
    }
    
    /**
     * List files in storage bucket
     */
    public function listFiles($folder = '', $limit = 100, $offset = 0) {
        $url = rtrim($this->url, '/') . '/storage/v1/object/list/' . $this->storageBucket;
        
        if (!empty($folder)) {
            $url .= '/' . ltrim($folder, '/');
        }
        
        $url .= '?limit=' . $limit . '&offset=' . $offset;
        
        $headers = [
            'Authorization: Bearer ' . $this->serviceKey
        ];
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("List files error: " . $error);
        }
        
        if ($httpCode !== 200) {
            throw new Exception("List files failed with status: {$httpCode}");
        }
        
        $result = json_decode($response, true);
        return $result ?? [];
    }
    
    /**
     * Get public URL for file (if bucket is public)
     */
    public function getPublicUrl($storagePath) {
        return rtrim($this->url, '/') . '/storage/v1/object/public/' . $this->storageBucket . '/' . ltrim($storagePath, '/');
    }
}
?>
