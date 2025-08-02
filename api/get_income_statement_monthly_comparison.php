<?php
/**
 * Income Statement Monthly Comparison API
 * 12개월 비교 전용 API - 월별 컬럼 테이블용
 * 기존 get_income_statement_v2.php는 절대 건들지 말것
 */

// 디버그 로그 추가
error_log("=== API CALLED: get_income_statement_monthly_comparison.php ===");
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
    
    // Authentication check
    error_log("Step 2: Checking authentication");
    if (!checkAuth($params['user_id'], $params['company_id'])) {
        throw new Exception('Authentication failed', 401);
    }
    error_log("Step 2 completed - auth passed");
    
    // Get monthly comparison data
    error_log("Step 3: Getting monthly comparison data");
    $monthly_data = get_monthly_comparison_data($params);
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
        'company_id' => validate_uuid($input['company_id']),
        'target_month' => $input['target_month'],
        'store_id' => !empty($input['store_id']) ? validate_uuid($input['store_id']) : null
    ];
}

/**
 * Get 12개월 비교 데이터
 */
function get_monthly_comparison_data($params) {
    error_log("Getting monthly comparison data with params: " . json_encode($params));
    
    try {
        // 12개월 범위 계산
        $target_date = $params['target_month'] . '-01';
        $start_date = date('Y-m-01', strtotime($target_date . ' -11 months'));
        $end_date = date('Y-m-t', strtotime($target_date));
        
        error_log("Date range: $start_date to $end_date");
        
        // 월별 데이터 조회
        $raw_data = get_monthly_raw_data($params['company_id'], $start_date, $end_date, $params['store_id']);
        
        // 데이터 구조화
        $structured_data = structure_monthly_data($raw_data, $start_date, $end_date);
        
        return $structured_data;
        
    } catch (Exception $e) {
        error_log("Error in get_monthly_comparison_data: " . $e->getMessage());
        throw $e;
    }
}

/**
 * 월별 원시 데이터 조회
 */
function get_monthly_raw_data($company_id, $start_date, $end_date, $store_id = null) {
    error_log("=== Getting monthly raw data ===");
    error_log("Company: $company_id");
    error_log("Date range: $start_date to $end_date");
    error_log("Store ID: " . ($store_id ?: 'all stores'));
    
    try {
        // Supabase RPC 함수 호출
        $rpc_params = [
            'p_company_id' => $company_id,
            'p_start_date' => $start_date,
            'p_end_date' => $end_date
        ];
        
        if (!empty($store_id)) {
            $rpc_params['p_store_id'] = $store_id;
        }
        
        error_log("RPC parameters: " . json_encode($rpc_params));
        
        // 테스트 환경인 경우 대체 쿼리 사용
        if (strpos($company_id, 'test-') === 0) {
            error_log("Test environment detected, using fallback data");
            return get_monthly_data_fallback($company_id, $start_date, $end_date, $store_id);
        }
        
        // RPC 함수 호출
        try {
            $raw_data = callSupabaseRPC('rpc_get_income_statement_monthly_comparison', $rpc_params);
            error_log("RPC call successful, returned " . count($raw_data) . " records");
        } catch (Exception $e) {
            error_log("RPC call failed: " . $e->getMessage());
            // RPC 함수가 없을 경우 대체 쿼리 사용
            return get_monthly_data_fallback($company_id, $start_date, $end_date, $store_id);
        }
        
        return $raw_data;
        
    } catch (Exception $e) {
        error_log("Error in get_monthly_raw_data: " . $e->getMessage());
        throw $e;
    }
}

/**
 * RPC 함수가 없을 경우 대체 쿼리
 */
function get_monthly_data_fallback($company_id, $start_date, $end_date, $store_id = null) {
    error_log("Using fallback query for monthly data");
    
    try {
        // 직접 SQL 쿼리로 월별 데이터 조회
        $query = "
        WITH RECURSIVE month_series AS (
          SELECT DATE_TRUNC('month', $3::date) as month_date
          UNION ALL
          SELECT month_date + INTERVAL '1 month'
          FROM month_series
          WHERE month_date < DATE_TRUNC('month', $4::date)
        ),
        account_monthly AS (
          SELECT 
            TO_CHAR(ms.month_date, 'YYYY-MM') as month_key,
            ms.month_date,
            a.id as account_id,
            a.account_name,
            a.account_type,
            a.statement_category,
            a.statement_detail_category,
            COALESCE(SUM(CASE 
              WHEN a.account_type = 'income' THEN je.amount
              WHEN a.account_type = 'expense' THEN -je.amount
              ELSE je.amount
            END), 0) as amount
          FROM month_series ms
          CROSS JOIN accounts a
          LEFT JOIN journal_entries je ON (
            DATE_TRUNC('month', je.transaction_date) = ms.month_date
            AND je.account_id = a.id
            AND je.company_id = $1
            AND ($2::uuid IS NULL OR je.store_id = $2)
          )
          WHERE a.company_id = $1
            AND a.account_type IN ('income', 'expense')
          GROUP BY ms.month_date, a.id, a.account_name, a.account_type, a.statement_category, a.statement_detail_category
          ORDER BY a.account_name, ms.month_date
        )
        SELECT * FROM account_monthly;
        ";
        
        $params_array = [$company_id, $store_id, $start_date, $end_date];
        $raw_data = callSupabaseQuery($query, $params_array);
        
        error_log("Fallback query successful, returned " . count($raw_data) . " records");
        return $raw_data;
        
    } catch (Exception $e) {
        error_log("Error in fallback query: " . $e->getMessage());
        throw $e;
    }
}

/**
 * 월별 데이터 구조화
 */
function structure_monthly_data($raw_data, $start_date, $end_date) {
    error_log("Structuring monthly data");
    
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
            $accounts_data[$account_name]['amounts'][$month_key] = $amount;
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
        throw new Exception('API Error (' . $httpCode . '): ' . ($error['message'] ?? 'Unknown error'));
    }
    
    return json_decode($response, true);
}

/**
 * Call Supabase Query directly (테스트 환경용 샘플 데이터 반환)
 */
function callSupabaseQuery($query, $params) {
    // 테스트 환경에서는 샘플 데이터 반환
    if (strpos($params[0], 'test-') === 0) {
        return get_test_monthly_data();
    }
    
    // 이 함수는 common/db.php에서 구현된 함수를 사용
    // 실제 구현은 프로젝트의 DB 연결 방식에 따라 달라질 수 있음
    throw new Exception('Direct query function not implemented. Please use RPC function instead.');
}

/**
 * 테스트용 월별 데이터 생성
 */
function get_test_monthly_data() {
    $months = [];
    $current = new DateTime('2024-08-01');
    $end = new DateTime('2025-07-31');
    
    $test_data = [];
    $accounts = [
        ['name' => 'Sales Revenue', 'type' => 'income', 'category' => 'revenue'],
        ['name' => 'Service Revenue', 'type' => 'income', 'category' => 'revenue'],
        ['name' => 'Cost of Goods Sold', 'type' => 'expense', 'category' => 'cost_of_sales'],
        ['name' => 'Office Rent', 'type' => 'expense', 'category' => 'operating_expenses'],
        ['name' => 'Marketing Expense', 'type' => 'expense', 'category' => 'operating_expenses'],
        ['name' => 'Utilities', 'type' => 'expense', 'category' => 'operating_expenses']
    ];
    
    while ($current <= $end) {
        $month_key = $current->format('Y-m');
        
        foreach ($accounts as $account) {
            // 무작위 금액 생성 (수익은 양수, 비용은 음수)
            $base_amount = rand(500000, 2000000);
            if ($account['type'] === 'expense') {
                $base_amount = -$base_amount;
            }
            
            $test_data[] = [
                'month_key' => $month_key,
                'month_date' => $current->format('Y-m-d'),
                'account_id' => 'test-' . strtolower(str_replace(' ', '-', $account['name'])),
                'account_name' => $account['name'],
                'account_type' => $account['type'],
                'statement_category' => $account['category'],
                'statement_detail_category' => $account['category'],
                'amount' => $base_amount
            ];
        }
        
        $current->modify('+1 month');
    }
    
    return $test_data;
}

/**
 * Validate UUID format (테스트 환경을 위해 test- 접두사 허용)
 */
function validate_uuid($uuid_string) {
    // 테스트 환경: test- 접두사 허용
    if (strpos($uuid_string, 'test-') === 0) {
        return $uuid_string;
    }
    
    // 실제 UUID 형식 검증
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid_string)) {
        throw new Exception('Invalid UUID format', 400);
    }
    return $uuid_string;
}
