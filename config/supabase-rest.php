<?php
/**
 * Supabase REST API Client (PostgREST)
 * Menggunakan HTTPS untuk menghindari masalah IPv6
 */

// Load environment variables
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    $content = file_get_contents($envFile);
    $lines = preg_split('/\r?\n/', $content);
    
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        
        if (strpos($line, '=') !== false) {
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $key = trim($parts[0]);
                $value = trim($parts[1]);
                $_ENV[$key] = $value;
                putenv("$key=$value");
            }
        }
    }
}

// Supabase URL dan API Key
$supabaseUrl = $_ENV['SUPABASE_URL'] ?? getenv('SUPABASE_URL') ?? 'https://jfpzimiqkxhysemsjaba.supabase.co';
// Prioritas: SERVICE_ROLE_KEY > ANON_KEY
$supabaseKey = $_ENV['SUPABASE_SERVICE_ROLE_KEY'] ?? getenv('SUPABASE_SERVICE_ROLE_KEY') 
            ?? $_ENV['SUPABASE_ANON_KEY'] ?? getenv('SUPABASE_ANON_KEY') 
            ?? '';

/**
 * Supabase REST API Helper Functions
 */
class SupabaseRest {
    private $baseUrl;
    private $apiKey;
    
    public function __construct($url, $key) {
        $this->baseUrl = rtrim($url, '/');
        $this->apiKey = $key;
    }
    
    /**
     * GET request ke Supabase REST API
     */
    public function get($table, $params = []) {
        $url = $this->baseUrl . '/rest/v1/' . $table;
        
        if (!empty($params)) {
            $queryParts = [];
            foreach ($params as $key => $value) {
                if (strpos($value, '.') === false && $key !== 'select' && $key !== 'order') {
                    $queryParts[] = $key . '=eq.' . urlencode($value);
                } else {
                    $queryParts[] = $key . '=' . urlencode($value);
                }
            }
            $url .= '?' . implode('&', $queryParts);
        }
        
        return $this->request('GET', $url);
    }
    
    /**
     * POST request (INSERT)
     */
    public function post($table, $data) {
        $url = $this->baseUrl . '/rest/v1/' . $table;
        return $this->request('POST', $url, $data);
    }
    
    /**
     * PATCH request (UPDATE)
     */
    public function patch($table, $data, $filter = []) {
        $url = $this->baseUrl . '/rest/v1/' . $table;
        
        if (!empty($filter)) {
            // Build PostgREST filter query
            $queryParts = [];
            foreach ($filter as $key => $value) {
                if (strpos($value, '.') === false) {
                    $queryParts[] = $key . '=eq.' . urlencode($value);
                } else {
                    $queryParts[] = $key . '=' . urlencode($value);
                }
            }
            $url .= '?' . implode('&', $queryParts);
        }
        
        return $this->request('PATCH', $url, $data);
    }
    
    /**
     * DELETE request
     */
    public function delete($table, $filter = []) {
        $url = $this->baseUrl . '/rest/v1/' . $table;
        
        if (!empty($filter)) {
            // Build PostgREST filter query
            $queryParts = [];
            foreach ($filter as $key => $value) {
                if (strpos($value, '.') === false) {
                    $queryParts[] = $key . '=eq.' . urlencode($value);
                } else {
                    $queryParts[] = $key . '=' . urlencode($value);
                }
            }
            $url .= '?' . implode('&', $queryParts);
        }
        
        return $this->request('DELETE', $url);
    }
    
    /**
     * Execute HTTP request
     */
    private function request($method, $url, $data = null) {
        $ch = curl_init($url);
        
        $headers = [
            'apikey: ' . $this->apiKey,
            'Authorization: Bearer ' . $this->apiKey,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
        
        if ($method === 'POST' && isset($data['upsert'])) {
            $headers[] = 'Prefer: resolution=merge-duplicates';
            unset($data['upsert']);
        }
        
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_CUSTOMREQUEST => $method,
            CURLOPT_HTTPHEADER => $headers,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_TIMEOUT => 30
        ]);
        
        if ($data !== null && in_array($method, ['POST', 'PATCH'])) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);
        
        if ($error) {
            throw new Exception("cURL Error: " . $error);
        }
        
        $result = json_decode($response, true);
        
        if ($httpCode >= 400) {
            $errorMsg = $response;
            if (is_array($result)) {
                if (isset($result['message'])) {
                    $errorMsg = $result['message'];
                } else if (isset($result['hint'])) {
                    $errorMsg = $result['hint'];
                } else if (isset($result['details'])) {
                    $errorMsg = $result['details'];
                } else {
                    $errorMsg = json_encode($result);
                }
            }
            throw new Exception("HTTP Error $httpCode: " . $errorMsg);
        }
        
        return $result;
    }
}

$supabaseRest = null;

function getSupabaseRest() {
    global $supabaseRest, $supabaseUrl, $supabaseKey;
    
    if ($supabaseRest === null) {
        if (empty($supabaseKey)) {
            throw new Exception("SUPABASE_SERVICE_ROLE_KEY atau SUPABASE_ANON_KEY tidak ditemukan. Pastikan file .env berisi salah satu dari keduanya.");
        }
        $supabaseRest = new SupabaseRest($supabaseUrl, $supabaseKey);
    }
    
    return $supabaseRest;
}

