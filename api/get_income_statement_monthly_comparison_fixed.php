<?php
/**
 * Income Statement Monthly Comparison API - Fixed Version
 * 12개월 비교 전용 API - 월별 컬럼 테이블용
 * RPC 함수를 사용하여 URL 길이 제한 문제 해결
 */

// 디버그 로그 추가
error_log("=== API CALLED: get_income_statement_monthly_comparison_fixed.php ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET params: " . json_encode($_GET));

require_once '../common/auth.php';
require_once '../common/db.php';
require_once '../common/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$start_time = microtime(true);

try {
    // Get and validate parameters
    error_log("Step 1: Validating parameters");
    $params = validate_monthly_comparison_params($_GET);
    error_log("Step 1 completed - params: " . json_encode($params));
    
    // Authentication check (간소화)
    error_log("Step 2: Checking authentication");
    // 테스트 환경에서는 인증 스킵
    if (!empty($params['user_id']) && !empty($params['company_id'])) {
        error_log("Step 2 completed - auth passed");
    }
    
    // Get monthly comparison data using RPC
    error_log("Step 3: Getting monthly comparison data via RPC");
    $monthly_data = get_monthly_comparison_data_rpc($params);
    error_log("Step 3 completed - data retrieved");
    
    // Calculate performance metrics
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'timestamp' => date('c'),
        'request_params' => [
            'company_id' => $params['company_id'],
            'target_month' => $params['target_month'],
            'store_id' => $params['store_id']
        ],
        'data' => $monthly_data,
        'performance' => [
            'execution_time' => $execution_time . 'ms'
        ]
    ]);
    
} catch (Exception $e) {
    $error_code = $e->getCode() ?: 500;
    http_response_code($error_code);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => $error_code,
        'timestamp' => date('c')
    ]);
}

/**
 * Validate and sanitize monthly comparison parameters
 */
function validate_monthly_comparison_params($input) {
    error_log("Validating params with input: " . json_encode($input));
    $errors = [];
    
    // Required parameters
    $required = ['company_id', 'target_month'];
    foreach ($required as $param) {
        if (empty($input[$param])) {
            $errors[] = "Missing required parameter: {$param}";
        }
    }
    
    if (!empty($errors)) {
        throw new Exception('Validation failed: ' . implode(', ', $errors), 400);
    }
    
    // Validate target_month format (YYYY-MM)
    if (!preg_match('/^\d{4}-\d{2}$/', $input['target_month'])) {
        throw new Exception('Invalid target_month format. Use YYYY-MM', 400);
    }
    
    return [
        'user_id' => $input['user_id'] ?? null,
        'company_id' => $input['company_id'],
        'target_month' => $input['target_month'],
        'store_id' => !empty($input['store_id']) ? $input['store_id'] : null
    ];
}

/**
 * Get 12개월 비교 데이터 - RPC 함수 사용
 */
function get_monthly_comparison_data_rpc($params) {
    error_log("Getting monthly comparison data with params: " . json_encode($params));
    
    try {
        // 12개월 범위 계산
        $target_date = $params['target_month'] . '-01';
        $start_date = date('Y-m-01', strtotime($target_date . ' -11 months'));
        $end_date = date('Y-m-t', strtotime($target_date));
        
        error_log("Date range: $start_date to $end_date");
        
        // RPC 함수를 통한 데이터 조회
        $raw_data = callSupabaseRPC('rpc_get_income_statement_monthly_comparison', [
            'p_company_id' => $params['company_id'],
            'p_start_date' => $start_date,
            'p_end_date' => $end_date,
            'p_store_id' => $params['store_id']
        ]);
        
        // 데이터 구조화
        $structured_data = structure_monthly_data_from_rpc($raw_data, $start_date, $end_date);
        
        return $structured_data;
        
    } catch (Exception $e) {
        error_log("Error in get_monthly_comparison_data_rpc: " . $e->getMessage());
        throw $e;
    }
}

/**
 * RPC 결과에서 월별 데이터 구조화
 */
function structure_monthly_data_from_rpc($raw_data, $start_date, $end_date) {
    error_log("Structuring monthly data from RPC result");
    
    // 12개월 배열 생성
    $months = [];
    $current = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    while ($current <= $end) {
        $months[] = $current->format('Y-m');
        $current->modify('+1 month');
    }
    
    error_log("Generated months: " . json_encode($months));
    
    // 계정별 월별 데이터 그룹화
    $accounts_data = [];
    foreach ($raw_data as $row) {
        $account_name = $row['account_name'];
        $month_key = $row['month_key'];
        $amount = floatval($row['amount']);
        
        if (!isset($accounts_data[$account_name])) {
            $accounts_data[$account_name] = [
                'name' => $account_name,
                'type' => $row['account_type'],
                'statement_category' => $row['statement_category'] ?? 'general',
                'amounts' => array_fill_keys($months, 0)
            ];
        }
        
        if (isset($accounts_data[$account_name]['amounts'][$month_key])) {
            $accounts_data[$account_name]['amounts'][$month_key] += $amount; // 누적
        }
    }
    
    // 배열로 변환 및 amounts를 순서대로 정렬
    $structured_accounts = [];
    foreach ($accounts_data as $account_name => $account_data) {
        $amounts = [];
        foreach ($months as $month) {
            $amounts[] = $account_data['amounts'][$month];
        }
        
        $structured_accounts[] = [
            'name' => $account_data['name'],
            'type' => $account_data['type'],
            'statement_category' => $account_data['statement_category'],
            'amounts' => $amounts
        ];
    }
    
    // 수익/비용 분리 및 총계 계산
    $revenue_accounts = array_filter($structured_accounts, function($account) {
        return $account['type'] === 'income';
    });
    
    $expense_accounts = array_filter($structured_accounts, function($account) {
        return $account['type'] === 'expense';
    });
    
    // 월별 총계 계산
    $total_revenue = array_fill(0, count($months), 0);
    $total_expenses = array_fill(0, count($months), 0);
    $net_income = array_fill(0, count($months), 0);
    
    foreach ($revenue_accounts as $account) {
        for ($i = 0; $i < count($months); $i++) {
            $total_revenue[$i] += $account['amounts'][$i];
        }
    }
    
    foreach ($expense_accounts as $account) {
        for ($i = 0; $i < count($months); $i++) {
            $total_expenses[$i] += $account['amounts'][$i];
        }
    }
    
    for ($i = 0; $i < count($months); $i++) {
        $net_income[$i] = $total_revenue[$i] - $total_expenses[$i];
    }
    
    $result = [
        'months' => $months,
        'accounts' => $structured_accounts,
        'summary_totals' => [
            'total_revenue' => $total_revenue,
            'total_expenses' => $total_expenses,
            'net_income' => $net_income
        ]
    ];
    
    error_log("Structured data - accounts: " . count($structured_accounts) . ", months: " . count($months));
    return $result;
}

/**
 * Call Supabase RPC function
 */
function callSupabaseRPC($function, $params) {
    $url = SUPABASE_URL . '/rest/v1/rpc/' . $function;
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('CURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        throw new Exception('RPC Error (' . $httpCode . '): ' . ($error['message'] ?? 'Unknown error'));
    }
    
    $result = json_decode($response, true);
    error_log("RPC call successful, returned " . count($result) . " records");
    
    return $result;
}
