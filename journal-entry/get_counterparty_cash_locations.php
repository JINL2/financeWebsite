<?php
/**
 * Get Counterparty Cash Locations API
 * Internal Transaction용 상대방 Cash Location 조회
 * 
 * URL: /luxapp/finance/journal-entry/get_counterparty_cash_locations.php
 * Method: GET
 * Parameters:
 *   - linked_company_id (required): 연결된 회사 ID
 *   - store_id (optional): 특정 매장의 Location만 조회
 *   - location_type (optional): cash/bank/vault 필터
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
    $store_id = $_GET['store_id'] ?? null;
    $location_type = $_GET['location_type'] ?? null;
    
    if (!$linked_company_id) {
        throw new Exception('linked_company_id parameter is required');
    }
    
    // Supabase REST API URL 구성
    $base_url = SUPABASE_URL . '/rest/v1/cash_locations';
    $filters = [];
    
    // 회사 ID 필터
    $filters[] = "company_id=eq.{$linked_company_id}";
    
    // 삭제되지 않은 것만
    $filters[] = "deleted_at=is.null";
    
    // Store ID 필터 (선택사항)
    if ($store_id) {
        $filters[] = "store_id=eq.{$store_id}";
    }
    
    // Location Type 필터 (선택사항)
    if ($location_type) {
        $filters[] = "location_type=eq.{$location_type}";
    }
    
    // Store 정보도 함께 가져오기 위한 select 구문
    $filters[] = "select=cash_location_id,location_name,location_type,store_id,currency_code,location_info,icon";
    $filters[] = "order=location_name";
    
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
        throw new Exception('Failed to fetch cash locations from Supabase');
    }
    
    $cash_locations = json_decode($response, true);
    
    if ($cash_locations === null) {
        throw new Exception('Invalid JSON response from Supabase');
    }
    
    // Store 정보도 함께 조회해서 매핑
    $formatted_locations = [];
    
    foreach ($cash_locations as $location) {
        $store_name = null;
        
        // Store 정보 조회 (store_id가 있는 경우)
        if ($location['store_id']) {
            $store_url = SUPABASE_URL . '/rest/v1/stores';
            $store_filters = [];
            $store_filters[] = "store_id=eq.{$location['store_id']}";
            $store_filters[] = "select=store_name";
            
            $store_query_string = implode('&', $store_filters);
            $store_full_url = $store_url . '?' . $store_query_string;
            
            $store_response = file_get_contents($store_full_url, false, $context);
            
            if ($store_response) {
                $store_data = json_decode($store_response, true);
                if (!empty($store_data) && isset($store_data[0]['store_name'])) {
                    $store_name = $store_data[0]['store_name'];
                }
            }
        }
        
        // Location Type에 따른 아이콘 설정 (DB에 아이콘이 없는 경우 기본값)
        $icon = $location['icon'] ?? '💰';
        if (!$icon) {
            switch ($location['location_type']) {
                case 'cash':
                    $icon = '💵';
                    break;
                case 'bank':
                    $icon = '🏦';
                    break;
                case 'vault':
                    $icon = '🔒';
                    break;
                case 'transfer':
                    $icon = '💸';
                    break;
                case 'digital':
                    $icon = '💳';
                    break;
                default:
                    $icon = '💰';
            }
        }
        
        $formatted_locations[] = [
            'cash_location_id' => $location['cash_location_id'],
            'location_name' => $location['location_name'],
            'location_type' => $location['location_type'],
            'store_name' => $store_name ?: 'N/A',
            'store_id' => $location['store_id'],
            'currency_code' => $location['currency_code'] ?? 'KRW',
            'icon' => $icon,
            'location_info' => $location['location_info'] ?? ''
        ];
    }
    
    // 성공 응답
    $result = [
        'success' => true,
        'data' => $formatted_locations,
        'total_count' => count($formatted_locations),
        'linked_company_id' => $linked_company_id,
        'store_id' => $store_id,
        'location_type' => $location_type
    ];
    
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
    
} catch (Exception $e) {
    error_log('Error in get_counterparty_cash_locations.php: ' . $e->getMessage());
    
    $error_result = [
        'success' => false,
        'error' => $e->getMessage(),
        'data' => [],
        'total_count' => 0,
        'debug_info' => [
            'linked_company_id' => $linked_company_id ?? null,
            'store_id' => $store_id ?? null,
            'location_type' => $location_type ?? null
        ]
    ];
    
    http_response_code(500);
    echo json_encode($error_result, JSON_UNESCAPED_UNICODE);
}
?>
