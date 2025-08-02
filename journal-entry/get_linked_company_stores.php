<?php
/**
 * Get Linked Company Stores API
 * Internal Transaction에서 사용할 Linked Company의 Store 목록을 가져옴
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// Preflight 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../common/config.php';

try {
    // 파라미터 검증
    $linked_company_id = $_GET['linked_company_id'] ?? null;
    
    if (!$linked_company_id) {
        throw new Exception('linked_company_id parameter is required');
    }
    
    // Supabase REST API URL 구성
    $base_url = SUPABASE_URL . '/rest/v1/stores';
    $filters = [];
    $filters[] = "company_id=eq.{$linked_company_id}";
    $filters[] = "is_deleted=eq.false";
    $filters[] = "select=store_id,store_name,company_id,store_code";
    $filters[] = "order=store_name";
    
    $query_string = implode('&', $filters);
    $full_url = $base_url . '?' . $query_string;
    
    // HTTP 요청 컨텍스트
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'apikey: ' . SUPABASE_ANON_KEY,
                'Authorization: Bearer ' . SUPABASE_ANON_KEY,
                'Content-Type: application/json'
            ]
        ]
    ]);
    
    // Supabase API 요청
    $response = file_get_contents($full_url, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch stores from Supabase');
    }
    
    $stores = json_decode($response, true);
    
    if ($stores === null) {
        throw new Exception('Invalid JSON response from Supabase');
    }
    
    // 응답 준비
    $result = [
        'success' => true,
        'data' => $stores,
        'total_count' => count($stores),
        'linked_company_id' => $linked_company_id
    ];
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Error in get_linked_company_stores.php: ' . $e->getMessage());
    
    $error_result = [
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [],
        'total_count' => 0
    ];
    
    http_response_code(500);
    echo json_encode($error_result, JSON_UNESCAPED_UNICODE);
}
?>
