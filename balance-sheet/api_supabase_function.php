<?php
/**
 * Balance Sheet API - Supabase Function Implementation
 * 새로운 수파베이스 PostgreSQL Function 기반 API
 * 
 * @author William
 * @version 1.0.0 - Function API Implementation
 * @created 2025-07-24
 */

// 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 에러 리포팅 설정
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 시작 시간 기록
$start_time = microtime(true);

// Supabase 설정
$SUPABASE_URL = 'https://atkekzwgukdvucqntryo.supabase.co';
$SUPABASE_KEY = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8';

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_balance_sheet':
            echo json_encode(getBalanceSheetFromFunction($_GET), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        case 'test':
            echo json_encode(testConnectionFunction(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        default:
            echo json_encode(errorResponse('Invalid or missing action parameter', 400), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    error_log('Balance Sheet Function API Error: ' . $e->getMessage());
    echo json_encode(errorResponse('Internal server error: ' . $e->getMessage(), 500), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * 수파베이스 Function을 통한 Balance Sheet 조회
 */
function getBalanceSheetFromFunction($params) {
    global $start_time;
    
    try {
        // 입력 검증
        $validation_errors = validateInput($params);
        if (!empty($validation_errors)) {
            return errorResponse('Validation failed', 400, $validation_errors);
        }
        
        $company_id = $params['company_id'];
        $store_id = $params['store_id'] ?? null;
        $date_input = $params['as_of_date'] ?? date('Y-m');
        
        // 날짜 형식 변환: 'YYYY-MM' -> 시작일과 종료일
        $dates = parseDateInput($date_input);
        
        // Function 호출을 위한 RPC 요청 구성
        $rpc_params = [
            'p_company_id' => $company_id,
            'p_start_date' => $dates['start_date'],
            'p_end_date' => $dates['end_date']
        ];
        
        // 매장 ID가 있는 경우 추가
        if ($store_id && !empty($store_id)) {
            $rpc_params['p_store_id'] = $store_id;
        }
        
        // Supabase Function 호출
        $function_result = callSupabaseFunction('get_balance_sheet', $rpc_params);
        
        if (!$function_result) {
            return errorResponse('Function returned no data', 404);
        }
        
        // Function 결과가 JSON 문자열인 경우 디코딩
        if (is_string($function_result)) {
            $function_result = json_decode($function_result, true);
        }
        
        // Function에서 에러가 반환된 경우 처리
        if (isset($function_result['success']) && !$function_result['success']) {
            return errorResponse($function_result['error'] ?? 'Function error', 500);
        }
        
        // 성능 정보 추가
        $function_result['performance'] = [
            'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'data_source' => 'supabase_function',
            'function_name' => 'get_balance_sheet',
            'parameters_used' => $rpc_params
        ];
        
        return $function_result;
        
    } catch (Exception $e) {
        error_log('getBalanceSheetFromFunction Error: ' . $e->getMessage());
        return errorResponse('Failed to retrieve balance sheet data: ' . $e->getMessage(), 500);
    }
}

/**
 * Supabase Function 호출
 */
function callSupabaseFunction($function_name, $params) {
    global $SUPABASE_URL, $SUPABASE_KEY;
    
    $url = $SUPABASE_URL . '/rest/v1/rpc/' . $function_name;
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($params),
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $SUPABASE_KEY,
            'Authorization: Bearer ' . $SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ]
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    curl_close($curl);
    
    if ($error) {
        throw new Exception('Curl error: ' . $error);
    }
    
    if ($httpCode >= 400) {
        throw new Exception('HTTP error: ' . $httpCode . ' - ' . $response);
    }
    
    return json_decode($response, true);
}

/**
 * 날짜 입력 파싱
 */
function parseDateInput($date_input) {
    // YYYY-MM 형식인 경우
    if (preg_match('/^\d{4}-\d{2}$/', $date_input)) {
        $year = substr($date_input, 0, 4);
        $month = substr($date_input, 5, 2);
        
        return [
            'start_date' => $year . '-' . $month . '-01',
            'end_date' => date('Y-m-t', strtotime($year . '-' . $month . '-01'))
        ];
    }
    
    // YYYY-MM-DD 형식인 경우
    if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $date_input)) {
        return [
            'start_date' => date('Y-m-01', strtotime($date_input)),
            'end_date' => date('Y-m-t', strtotime($date_input))
        ];
    }
    
    // 기본값: 현재 월
    $current_date = date('Y-m-01');
    return [
        'start_date' => $current_date,
        'end_date' => date('Y-m-t', strtotime($current_date))
    ];
}

/**
 * 입력 검증
 */
function validateInput($params) {
    $errors = [];
    
    // company_id 필수 확인
    if (empty($params['company_id'])) {
        $errors[] = 'Missing required parameter: company_id';
    } elseif (!isValidUuid($params['company_id'])) {
        $errors[] = 'Invalid company_id format (must be UUID)';
    }
    
    // store_id 형식 확인 (선택적)
    if (!empty($params['store_id']) && !isValidUuid($params['store_id'])) {
        $errors[] = 'Invalid store_id format (must be UUID)';
    }
    
    // 날짜 형식 확인 (선택적)
    if (!empty($params['as_of_date'])) {
        $date = $params['as_of_date'];
        if (!preg_match('/^\d{4}-\d{2}(-\d{2})?$/', $date)) {
            $errors[] = 'Invalid date format (use YYYY-MM or YYYY-MM-DD)';
        }
    }
    
    return $errors;
}

/**
 * UUID 형식 검증
 */
function isValidUuid($uuid) {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[1-5][0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
}

/**
 * 연결 테스트
 */
function testConnectionFunction() {
    try {
        // 간단한 Function 테스트
        $test_params = [
            'p_company_id' => 'ebd66ba7-fde7-4332-b6b5-0d8a7f615497',
            'p_start_date' => '2025-07-01',
            'p_end_date' => '2025-07-31'
        ];
        
        $result = callSupabaseFunction('get_balance_sheet', $test_params);
        
        return [
            'success' => true,
            'message' => 'Function API connection successful',
            'test_result' => $result ? 'Function returned data' : 'Function returned empty',
            'timestamp' => date('Y-m-d H:i:s')
        ];
        
    } catch (Exception $e) {
        return [
            'success' => false,
            'error' => 'Function API connection failed: ' . $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * 에러 응답 생성
 */
function errorResponse($message, $code = 400, $details = null) {
    $response = [
        'success' => false,
        'error' => [
            'message' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s')
        ]
    ];
    
    if ($details) {
        $response['error']['details'] = $details;
    }
    
    return $response;
}

?>
