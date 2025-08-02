<?php
/**
 * Balance Sheet API
 * 재무상태표 데이터를 조회하는 REST API
 * 
 * @author William
 * @version 1.0.0
 * @created 2025-07-18
 */

// 헤더 설정
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// 에러 리포팅 설정
error_reporting(E_ALL);
ini_set('display_errors', 0); // 프로덕션에서는 0

// 시작 시간 기록 (성능 측정용)
$start_time = microtime(true);

try {
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_balance_sheet':
            echo json_encode(getBalanceSheet($_GET), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        case 'get_stores':
            echo json_encode(getStoresByCompany($_GET), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        case 'get_companies':
            echo json_encode(getCompanies(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        case 'debug':
            echo json_encode([
                'success' => true,
                'GET_params' => $_GET,
                'REQUEST_URI' => $_SERVER['REQUEST_URI'] ?? 'not available',
                'QUERY_STRING' => $_SERVER['QUERY_STRING'] ?? 'not available'
            ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        case 'test':
            echo json_encode(testConnection(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        default:
            echo json_encode(errorResponse('Invalid or missing action parameter', 400, [
                'available_actions' => ['get_balance_sheet', 'get_stores', 'get_companies', 'test'],
                'examples' => [
                    'get_balance_sheet' => '?action=get_balance_sheet&company_id=your-company-id',
                    'get_stores' => '?action=get_stores&company_id=your-company-id',
                    'get_companies' => '?action=get_companies'
                ]
            ]), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    error_log('Balance Sheet API Error: ' . $e->getMessage());
    echo json_encode(errorResponse('Internal server error', 500), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * 재무상태표 데이터 조회 메인 함수
 */
function getBalanceSheet($params) {
    global $start_time;
    
    try {
        // 1. 입력 검증
        $validation_errors = validateInput($params);
        if (!empty($validation_errors)) {
            return errorResponse('Validation failed', 400, $validation_errors);
        }
        
        // 2. 파라미터 정리
        $company_id = $params['company_id'];
        $store_id = $params['store_id'] ?? null;
        $as_of_date = $params['as_of_date'] ?? date('Y-m-d');
        $include_zero = ($params['include_zero'] ?? 'false') === 'true';
        
        // 3. 회사/매장 정보 조회 (현재는 샘플 데이터 사용)
        $company_info = getCompanyInfo($company_id, $store_id);
        if (!$company_info) {
            return errorResponse('Company not found', 404);
        }
        
        $store_info = null;
        if ($store_id) {
            $store_info = getStoreInfo($store_id, $company_id);
            if (!$store_info) {
                return errorResponse('Store not found', 404);
            }
        }
        
        // 4. 계정별 잔액 조회 (현재는 샘플 데이터 사용)
        $account_balances = getAccountBalances($company_id, $store_id, $as_of_date, $include_zero);
        
        // 5. 데이터 분류 및 가공
        $categorized_data = categorizeAccounts($account_balances);
        $totals = calculateTotals($categorized_data);
        
        // 6. 최종 응답 생성
        $response = formatResponse($company_info, $store_info, $categorized_data, $totals, $as_of_date);
        
        // 성능 정보 추가
        $response['performance'] = [
            'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2)
        ];
        
        return $response;
        
    } catch (Exception $e) {
        error_log('getBalanceSheet Error: ' . $e->getMessage());
        return errorResponse('Failed to retrieve balance sheet data', 500);
    }
}

/**
 * 연결 테스트 함수
 */
function testConnection() {
    return [
        'success' => true,
        'message' => 'API is working properly',
        'data' => [
            'server_time' => date('Y-m-d H:i:s'),
            'php_version' => phpversion(),
            'status' => 'ready'
        ],
        'timestamp' => date('Y-m-d H:i:s')
    ];
}

/**
 * 입력값 검증 함수
 */
function validateInput($params) {
    $errors = [];
    
    // action 검증
    if (empty($params['action'])) {
        $errors[] = 'Action parameter is required';
    } elseif ($params['action'] !== 'get_balance_sheet') {
        $errors[] = 'Invalid action: ' . $params['action'];
    }
    
    // company_id 검증
    if (empty($params['company_id'])) {
        $errors[] = 'Company ID is required';
    } elseif (!isValidUuid($params['company_id'])) {
        $errors[] = 'Invalid company ID format (must be UUID)';
    }
    
    // store_id 검증 (선택사항)
    if (!empty($params['store_id']) && !isValidUuid($params['store_id'])) {
        $errors[] = 'Invalid store ID format (must be UUID)';
    }
    
    // as_of_date 검증
    if (!empty($params['as_of_date'])) {
        $date = DateTime::createFromFormat('Y-m-d', $params['as_of_date']);
        if (!$date || $date->format('Y-m-d') !== $params['as_of_date']) {
            $errors[] = 'Invalid date format. Use YYYY-MM-DD';
        } elseif ($date > new DateTime()) {
            $errors[] = 'Future dates are not allowed';
        }
    }
    
    // include_zero 검증
    if (!empty($params['include_zero']) && !in_array($params['include_zero'], ['true', 'false'])) {
        $errors[] = 'include_zero must be true or false';
    }
    
    return $errors;
}

/**
 * UUID 형식 검증
 */
function isValidUuid($uuid) {
    return preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $uuid);
}

/**
 * 회사 정보 조회 (샘플 데이터)
 */
function getCompanyInfo($company_id, $store_id = null) {
    // 유효한 회사 ID 목록 (실제로는 DB에서 조회)
    $valid_companies = [
        'c6701145-ad2a-4a3d-bfb4-69d220428b28' => [
            'company_id' => 'c6701145-ad2a-4a3d-bfb4-69d220428b28',
            'company_name' => '123456',
            'base_currency_code' => 'VND',
            'currency_symbol' => '₫'
        ],
        '6f1a7f34-9551-40cf-9e28-111d9c786fcc' => [
            'company_id' => '6f1a7f34-9551-40cf-9e28-111d9c786fcc',
            'company_name' => '22',
            'base_currency_code' => 'VND',
            'currency_symbol' => '₫'
        ],
        '7a2545e0-e112-4b0c-9c59-221a530c4602' => [
            'company_id' => '7a2545e0-e112-4b0c-9c59-221a530c4602',
            'company_name' => 'test1',
            'base_currency_code' => 'VND',
            'currency_symbol' => '₫'
        ]
    ];
    
    return $valid_companies[$company_id] ?? null;
}

/**
 * 매장 정보 조회 (샘플 데이터)
 */
function getStoreInfo($store_id, $company_id) {
    // 샘플 매장 데이터
    $valid_stores = [
        'store-uuid-1' => [
            'store_id' => 'store-uuid-1',
            'store_name' => 'Test Store A'
        ]
    ];
    
    return $valid_stores[$store_id] ?? null;
}

/**
 * 계정별 잔액 조회 (샘플 데이터 - 실제 Supabase 데이터 기반)
 */
function getAccountBalances($company_id, $store_id, $as_of_date, $include_zero) {
    // 실제 Supabase에서 조회한 데이터 기반 샘플
    $sample_data = [
        [
            'account_id' => 'uuid1',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'statement_detail_category' => 'current_asset',
            'balance' => '1363347533.00',
            'transaction_count' => '45',
            'last_transaction_date' => '2025-07-15'
        ],
        [
            'account_id' => 'uuid2',
            'account_name' => 'Accounts Receivable',
            'account_type' => 'asset',
            'statement_detail_category' => 'current_asset',
            'balance' => '16603701.00',
            'transaction_count' => '12',
            'last_transaction_date' => '2025-07-10'
        ],
        [
            'account_id' => 'uuid3',
            'account_name' => 'Note Receivable',
            'account_type' => 'asset',
            'statement_detail_category' => 'current_asset',
            'balance' => '3422533340.00',
            'transaction_count' => '8',
            'last_transaction_date' => '2025-07-12'
        ],
        [
            'account_id' => 'uuid4',
            'account_name' => 'Deposit',
            'account_type' => 'asset',
            'statement_detail_category' => 'current_asset',
            'balance' => '895000000.00',
            'transaction_count' => '5',
            'last_transaction_date' => '2025-06-30'
        ],
        [
            'account_id' => 'uuid5',
            'account_name' => 'office equipment',
            'account_type' => 'asset',
            'statement_detail_category' => 'non_current_asset',
            'balance' => '822457665.00',
            'transaction_count' => '8',
            'last_transaction_date' => '2025-06-20'
        ],
        [
            'account_id' => 'uuid6',
            'account_name' => 'interior',
            'account_type' => 'asset',
            'statement_detail_category' => 'non_current_asset',
            'balance' => '1313281374.00',
            'transaction_count' => '6',
            'last_transaction_date' => '2025-05-15'
        ],
        [
            'account_id' => 'uuid7',
            'account_name' => 'Photobooth Machine',
            'account_type' => 'asset',
            'statement_detail_category' => 'non_current_asset',
            'balance' => '1277645764.00',
            'transaction_count' => '4',
            'last_transaction_date' => '2025-04-10'
        ],
        [
            'account_id' => 'uuid8',
            'account_name' => 'Intangible Assets',
            'account_type' => 'asset',
            'statement_detail_category' => 'non_current_asset',
            'balance' => '265682656.00',
            'transaction_count' => '3',
            'last_transaction_date' => '2025-03-01'
        ],
        [
            'account_id' => 'uuid9',
            'account_name' => 'Accumulated Depreciation',
            'account_type' => 'asset',
            'statement_detail_category' => 'non_current_asset',
            'balance' => '-363090112.00',
            'transaction_count' => '12',
            'last_transaction_date' => '2025-07-01'
        ],
        [
            'account_id' => 'uuid10',
            'account_name' => 'Notes Payable',
            'account_type' => 'liability',
            'statement_detail_category' => 'current_liability',
            'balance' => '3865022866.00',
            'transaction_count' => '15',
            'last_transaction_date' => '2025-07-12'
        ],
        [
            'account_id' => 'uuid11',
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'statement_detail_category' => 'current_liability',
            'balance' => '6888.00',
            'transaction_count' => '2',
            'last_transaction_date' => '2025-07-05'
        ],
        [
            'account_id' => 'uuid12',
            'account_name' => "Owner's Investment",
            'account_type' => 'equity',
            'statement_detail_category' => 'equity',
            'balance' => '5097685058.00',
            'transaction_count' => '3',
            'last_transaction_date' => '2025-01-01'
        ],
        [
            'account_id' => 'uuid13',
            'account_name' => 'Dividend',
            'account_type' => 'equity',
            'statement_detail_category' => 'equity',
            'balance' => '-1236159462.00',
            'transaction_count' => '5',
            'last_transaction_date' => '2025-06-15'
        ]
    ];
    
    // include_zero가 false이면 잔액이 0이 아닌 것만 반환
    if (!$include_zero) {
        $sample_data = array_filter($sample_data, function($account) {
            return abs(floatval($account['balance'])) > 0.01;
        });
    }
    
    return $sample_data;
}

/**
 * 계정 분류 함수
 */
function categorizeAccounts($raw_data) {
    $categorized = [
        'current_assets' => [],
        'non_current_assets' => [],
        'current_liabilities' => [],
        'non_current_liabilities' => [],
        'equity' => []
    ];
    
    foreach ($raw_data as $account) {
        $key = '';
        if ($account['account_type'] === 'asset') {
            $key = ($account['statement_detail_category'] === 'current_asset') 
                ? 'current_assets' : 'non_current_assets';
        } elseif ($account['account_type'] === 'liability') {
            $key = ($account['statement_detail_category'] === 'current_liability')
                ? 'current_liabilities' : 'non_current_liabilities';
        } elseif ($account['account_type'] === 'equity') {
            $key = 'equity';
        }
        
        if ($key) {
            $categorized[$key][] = [
                'account_id' => $account['account_id'],
                'account_name' => $account['account_name'],
                'balance' => floatval($account['balance']),
                'formatted_balance' => number_format($account['balance'], 0),
                'transaction_count' => intval($account['transaction_count']),
                'last_transaction_date' => $account['last_transaction_date']
            ];
        }
    }
    
    return $categorized;
}

/**
 * 총계 계산 함수
 */
function calculateTotals($categorized_data) {
    $totals = [];
    
    // 유동자산 합계
    $totals['total_current_assets'] = array_sum(
        array_column($categorized_data['current_assets'], 'balance')
    );
    
    // 비유동자산 합계  
    $totals['total_non_current_assets'] = array_sum(
        array_column($categorized_data['non_current_assets'], 'balance')
    );
    
    // 자산 총계
    $totals['total_assets'] = $totals['total_current_assets'] + 
                              $totals['total_non_current_assets'];
    
    // 유동부채 합계
    $totals['total_current_liabilities'] = array_sum(
        array_column($categorized_data['current_liabilities'], 'balance')
    );
    
    // 비유동부채 합계
    $totals['total_non_current_liabilities'] = array_sum(
        array_column($categorized_data['non_current_liabilities'], 'balance')
    );
    
    // 부채 총계
    $totals['total_liabilities'] = $totals['total_current_liabilities'] + 
                                   $totals['total_non_current_liabilities'];
    
    // 자본 총계
    $totals['total_equity'] = array_sum(
        array_column($categorized_data['equity'], 'balance')
    );
    
    // 부채+자본 총계
    $totals['total_liabilities_and_equity'] = $totals['total_liabilities'] + 
                                              $totals['total_equity'];
    
    // 균형식 검증
    $totals['balance_check'] = abs($totals['total_assets'] - 
                                   $totals['total_liabilities_and_equity']) < 0.01;
    
    // 균형 차이 (디버깅용)
    $totals['balance_difference'] = $totals['total_assets'] - 
                                    $totals['total_liabilities_and_equity'];
    
    return $totals;
}

/**
 * 최종 응답 형식화 함수
 */
function formatResponse($company_info, $store_info, $categorized_data, $totals, $as_of_date) {
    return [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => [
            'company_info' => [
                'company_id' => $company_info['company_id'],
                'company_name' => $company_info['company_name'],
                'store_id' => $store_info['store_id'] ?? null,
                'store_name' => $store_info['store_name'] ?? null,
                'base_currency' => $company_info['base_currency_code'] ?? 'VND',
                'currency_symbol' => $company_info['currency_symbol'] ?? '₫'
            ],
            'period_info' => [
                'as_of_date' => $as_of_date,
                'report_type' => 'balance_sheet',
                'scope' => $store_info ? 'store' : 'company'
            ],
            'assets' => [
                'current_assets' => $categorized_data['current_assets'],
                'non_current_assets' => $categorized_data['non_current_assets'],
                'total_current_assets' => $totals['total_current_assets'],
                'total_non_current_assets' => $totals['total_non_current_assets'],
                'total_assets' => $totals['total_assets'],
                'formatted_total_assets' => number_format($totals['total_assets'], 0)
            ],
            'liabilities' => [
                'current_liabilities' => $categorized_data['current_liabilities'],
                'non_current_liabilities' => $categorized_data['non_current_liabilities'],
                'total_current_liabilities' => $totals['total_current_liabilities'],
                'total_non_current_liabilities' => $totals['total_non_current_liabilities'],
                'total_liabilities' => $totals['total_liabilities'],
                'formatted_total_liabilities' => number_format($totals['total_liabilities'], 0)
            ],
            'equity' => [
                'equity_accounts' => $categorized_data['equity'],
                'total_equity' => $totals['total_equity'],
                'formatted_total_equity' => number_format($totals['total_equity'], 0)
            ],
            'totals' => [
                'total_assets' => $totals['total_assets'],
                'total_liabilities_and_equity' => $totals['total_liabilities_and_equity'],
                'balance_check' => $totals['balance_check'],
                'balance_difference' => $totals['balance_difference'],
                'formatted_total_assets' => number_format($totals['total_assets'], 0),
                'formatted_total_liab_equity' => number_format($totals['total_liabilities_and_equity'], 0)
            ],
            'statistics' => [
                'total_accounts' => count($categorized_data['current_assets']) + 
                                   count($categorized_data['non_current_assets']) + 
                                   count($categorized_data['current_liabilities']) + 
                                   count($categorized_data['non_current_liabilities']) + 
                                   count($categorized_data['equity']),
                'accounts_with_balance' => count(array_filter(
                    array_merge(...array_values($categorized_data)), 
                    fn($acc) => abs($acc['balance']) > 0.01
                ))
            ]
        ]
    ];
}

/**
 * 매장 목록 조회 함수 (샘플 데이터)
 */
function getStoresByCompany($params) {
    $company_id = $params['company_id'] ?? '';
    
    // company_id 검증
    if (empty($company_id)) {
        return errorResponse('Missing required parameter: company_id', 400);
    }
    
    if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
        return errorResponse('Invalid company ID format', 400);
    }
    
    // 회사 정보 확인
    $company_info = getCompanyInfo($company_id);
    if (!$company_info) {
        return errorResponse('Company not found', 404);
    }
    
    // 샘플 매장 데이터
    $sample_stores = [
        [
            'store_id' => 'store-uuid-001',
            'store_name' => '본점',
            'store_code' => 'HQ001',
            'is_active' => true,
            'created_at' => '2024-01-15 09:00:00'
        ],
        [
            'store_id' => 'store-uuid-002', 
            'store_name' => '강남점',
            'store_code' => 'GN002',
            'is_active' => true,
            'created_at' => '2024-03-20 10:30:00'
        ],
        [
            'store_id' => 'store-uuid-003',
            'store_name' => '마포점',
            'store_code' => 'MP003', 
            'is_active' => true,
            'created_at' => '2024-05-10 14:15:00'
        ],
        [
            'store_id' => 'store-uuid-004',
            'store_name' => '이태원점',
            'store_code' => 'ITW004',
            'is_active' => false,
            'created_at' => '2024-02-28 11:45:00'
        ]
    ];
    
    // 회사별 매장 필터링 (실제로는 모든 회사에 동일한 매장 반환 - 데모용)
    $stores = $sample_stores;
    
    return [
        'success' => true,
        'data' => [
            'company_info' => [
                'company_name' => $company_info['company_name']
            ],
            'stores' => $stores,
            'total_stores' => count($stores),
            'active_stores' => count(array_filter($stores, fn($store) => $store['is_active']))
        ]
    ];
}

/**
 * 회사 목록 조회 함수
 */
function getCompanies() {
    // 샘플 회사 데이터
    $sample_companies = [
        [
            'company_id' => 'c6701145-ad2a-4a3d-bfb4-69d220428b28',
            'company_name' => '123456',
            'base_currency' => 'VND',
            'is_active' => true,
            'created_at' => '2024-01-01 00:00:00'
        ],
        [
            'company_id' => '6f1a7f34-9551-40cf-9e28-111d9c786fcc', 
            'company_name' => '22',
            'base_currency' => 'VND',
            'is_active' => true,
            'created_at' => '2024-02-15 10:30:00'
        ],
        [
            'company_id' => '7a2545e0-e112-4b0c-9c59-221a530c4602',
            'company_name' => 'test1',
            'base_currency' => 'VND',
            'is_active' => true,
            'created_at' => '2024-07-21 12:00:00'
        ],
        [
            'company_id' => 'sample-company-uuid-003',
            'company_name' => '샘플 회사 C',
            'base_currency' => 'USD',
            'is_active' => false,
            'created_at' => '2024-03-10 14:20:00'
        ]
    ];
    
    return [
        'success' => true,
        'data' => [
            'companies' => $sample_companies,
            'total_companies' => count($sample_companies),
            'active_companies' => count(array_filter($sample_companies, fn($company) => $company['is_active']))
        ]
    ];
}

/**
 * 에러 응답 생성 함수
 */
function errorResponse($message, $code = 400, $details = null) {
    http_response_code($code);
    return [
        'success' => false,
        'error' => [
            'message' => $message,
            'code' => $code,
            'timestamp' => date('Y-m-d H:i:s'),
            'details' => $details
        ]
    ];
}

?>
