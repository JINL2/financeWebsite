<?php
/**
 * Supabase 데이터베이스 연결 클래스 (환경변수 기반)
 */
require_once 'config.php';

/**
 * PostgreSQL PDO 연결
 */
function getDBConnection() {
    static $pdo = null;
    
    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'db.your-project.supabase.co';
        $port = getenv('DB_PORT') ?: '5432';
        $dbname = getenv('DB_NAME') ?: 'postgres';
        $username = getenv('DB_USERNAME') ?: 'postgres';
        $password = getenv('DB_PASSWORD') ?: 'your-database-password';
        
        $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        
        try {
            $pdo = new PDO($dsn, $username, $password, [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false
            ]);
        } catch (PDOException $e) {
            error_log("Database connection failed: " . $e->getMessage());
            throw new Exception('Database connection failed');
        }
    }
    
    return $pdo;
}

class SupabaseDB {
    private $url;
    private $headers;
    
    public function __construct() {
        $this->url = SUPABASE_URL;
        $this->headers = [
            'apikey: ' . SUPABASE_ANON_KEY,
            'Authorization: Bearer ' . SUPABASE_ANON_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ];
    }
    
    /**
     * REST API 호출
     */
    public function query($endpoint, $params = [], $method = 'GET', $data = null) {
        $url = $this->url . '/rest/v1/' . $endpoint;
        
        // 파라미터 처리
        if (!empty($params) && is_array($params)) {
            $queryParts = [];
            foreach ($params as $key => $value) {
                if ($key === 'order' || $key === 'select' || $key === 'or') {
                    // These parameters should not be URL encoded
                    $queryParts[] = $key . '=' . $value;
                } else if ($key === 'limit' || $key === 'offset') {
                    $queryParts[] = $key . '=' . $value;
                } else if (strpos($value, ',') !== false || strpos($value, 'gte.') !== false || strpos($value, 'lte.') !== false || strpos($value, 'eq.') !== false || strpos($value, 'in.') !== false || strpos($value, 'is.') !== false) {
                    // Supabase operators should not be URL encoded
                    $queryParts[] = $key . '=' . $value;
                } else {
                    $queryParts[] = $key . '=' . urlencode($value);
                }
            }
            $url .= '?' . implode('&', $queryParts);
        }
        
        // Debug: 생성된 URL 확인
        error_log("Supabase API URL: " . $url);
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, $method);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        if ($data !== null) {
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
        }
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            throw new Exception('API Error: ' . $response);
        }
    }
    
    /**
     * RPC 함수 호출
     */
    public function callRPC($function, $params = []) {
        $url = $this->url . '/rest/v1/rpc/' . $function;
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, $this->headers);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        
        if ($httpCode >= 200 && $httpCode < 300) {
            return json_decode($response, true);
        } else {
            throw new Exception('RPC Error: ' . $response);
        }
    }
    
    /**
     * 단일 레코드 조회
     */
    public function getOne($table, $conditions = []) {
        $params = [];
        foreach ($conditions as $key => $value) {
            $params[$key] = 'eq.' . $value;
        }
        $params['limit'] = 1;
        
        $result = $this->query($table, $params);
        return !empty($result) ? $result[0] : null;
    }
    
    /**
     * 여러 레코드 조회
     */
    public function getMany($table, $conditions = [], $order = null, $limit = null) {
        $params = [];
        foreach ($conditions as $key => $value) {
            if (is_array($value)) {
                $params[$key] = $value['operator'] . '.' . $value['value'];
            } else {
                $params[$key] = 'eq.' . $value;
            }
        }
        
        if ($order) {
            $params['order'] = $order;
        }
        
        if ($limit) {
            $params['limit'] = $limit;
        }
        
        return $this->query($table, $params);
    }
    
    /**
     * Execute raw SQL query using PDO connection
     */
    public function executeSQL($sql, $params = []) {
        try {
            error_log('Executing SQL: ' . $sql);
            error_log('SQL Params: ' . json_encode($params));
            $pdo = getDBConnection();
            $stmt = $pdo->prepare($sql);
            $stmt->execute($params);
            $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
            error_log('SQL Result count: ' . count($result));
            return $result;
        } catch (PDOException $e) {
            error_log('SQL Error: ' . $e->getMessage());
            error_log('SQL Query: ' . $sql);
            error_log('SQL Params: ' . json_encode($params));
            throw new Exception('Database connection failed');
        }
    }
}
