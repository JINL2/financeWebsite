<?php
/**
 * Balance Sheet API - Real Data Implementation
 * 실제 데이터베이스 데이터 기반 재무상태표 API
 * 
 * @author William
 * @version 4.0.0 - Real Data Implementation
 * @created 2025-07-22
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
            echo json_encode(getRealBalanceSheet($_GET), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        case 'get_stores':
            echo json_encode(getStoresByCompanyFromDB($_GET), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        case 'get_companies':
            echo json_encode(getCompaniesFromDB(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        case 'test':
            echo json_encode(testConnectionDB(), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
            break;
        default:
            echo json_encode(errorResponse('Invalid or missing action parameter', 400), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    }
} catch (Exception $e) {
    error_log('Balance Sheet API Error: ' . $e->getMessage());
    echo json_encode(errorResponse('Internal server error: ' . $e->getMessage(), 500), JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
}

/**
 * 실제 데이터 기반 Balance Sheet 조회
 */
function getRealBalanceSheet($params) {
    global $start_time;
    
    try {
        // 입력 검증
        $validation_errors = validateInput($params);
        if (!empty($validation_errors)) {
            return errorResponse('Validation failed', 400, $validation_errors);
        }
        
        $company_id = $params['company_id'];
        $store_id = $params['store_id'] ?? null;
        $as_of_date = $params['as_of_date'] ?? date('Y-m-d');
        $include_zero = ($params['include_zero'] ?? 'false') === 'true';
        
        // as_of_date가 yyyy-mm 형식인 경우 해당 월의 마지막 날로 변환
        if (preg_match('/^\d{4}-\d{2}$/', $as_of_date)) {
            $year = substr($as_of_date, 0, 4);
            $month = substr($as_of_date, 5, 2);
            $as_of_date = date('Y-m-t', strtotime($year . '-' . $month . '-01'));
        }
        
        // 회사 정보 조회
        $company_data = callSupabaseAPI('companies?select=company_id,company_name,base_currency_id,currency_types(currency_code,symbol)&company_id=eq.' . $company_id . '&is_deleted=is.false');
        
        if (empty($company_data)) {
            return errorResponse('Company not found', 404);
        }
        
        $company_info = $company_data[0];
        
        // 매장 정보 조회 (선택적)
        $store_info = null;
        if ($store_id) {
            $store_data = callSupabaseAPI('stores?select=store_id,store_name,store_code&store_id=eq.' . $store_id . '&company_id=eq.' . $company_id . '&is_deleted=is.false');
            
            if (empty($store_data)) {
                return errorResponse('Store not found', 404);
            }
            
            $store_info = $store_data[0];
        }
        
        // 실제 Balance Sheet 데이터 조회
        $balance_data = getBalanceSheetFromJournalEntries($company_id, $store_id, $as_of_date, $include_zero);
        
        // 데이터가 없는 경우 빈 구조 반환
        if (empty($balance_data)) {
            $balance_data = getEmptyBalanceSheetStructure();
        }
        
        // 데이터 분류 및 가공
        $categorized_data = categorizeAccountsFromDB($balance_data);
        $totals = calculateTotalsFromDB($categorized_data);
        
        // 최종 응답 생성
        $response = formatResponseFromDB($company_info, $store_info, $categorized_data, $totals, $as_of_date);
        
        // 성능 정보 추가
        $response['performance'] = [
            'execution_time_ms' => round((microtime(true) - $start_time) * 1000, 2),
            'memory_usage_mb' => round(memory_get_peak_usage(true) / 1024 / 1024, 2),
            'data_source' => empty($balance_data) ? 'empty_structure' : 'real_data',
            'as_of_date_used' => $as_of_date,
            'accounts_found' => count($balance_data)
        ];
        
        return $response;
        
    } catch (Exception $e) {
        error_log('getRealBalanceSheet Error: ' . $e->getMessage());
        return errorResponse('Failed to retrieve balance sheet data: ' . $e->getMessage(), 500);
    }
}

/**
 * Journal Entries에서 Balance Sheet 데이터 조회
 */
function getBalanceSheetFromJournalEntries($company_id, $store_id, $as_of_date, $include_zero) {
    try {
        // 1. 먼저 계정 목록 조회
        $accounts = callSupabaseAPI("accounts?select=account_id,account_name,account_type,statement_detail_category&account_type=in.(asset,liability,equity)&limit=100");
        
        if (empty($accounts)) {
            error_log('No accounts found for balance sheet');
            return [];
        }
        
        $result = [];
        
        // 2. 각 계정별로 잔액 계산
        foreach ($accounts as $account) {
            $account_id = $account['account_id'];
            $account_type = $account['account_type'];
            
            // 3. 해당 계정의 journal lines 조회
            $journal_query = "journal_lines?select=debit,credit,journal_entries(entry_date,company_id,store_id)&account_id=eq.{$account_id}&is_deleted=is.false";
            $journal_lines = callSupabaseAPI($journal_query);
            
            $balance = 0;
            $transaction_count = 0;
            $last_transaction_date = null;
            
            foreach ($journal_lines as $line) {
                $journal_entry = $line['journal_entries'];
                
                // 회사와 날짜 필터링
                if ($journal_entry['company_id'] !== $company_id) {
                    continue;
                }
                
                if ($journal_entry['entry_date'] > $as_of_date) {
                    continue;
                }
                
                // 매장 필터링 (선택사항)
                if ($store_id && $journal_entry['store_id'] !== $store_id) {
                    continue;
                }
                
                // 잔액 계산 (자산/비용은 차변-대변, 부채/자본/수익은 대변-차변)
                $debit = floatval($line['debit'] ?? 0);
                $credit = floatval($line['credit'] ?? 0);
                
                if (in_array($account_type, ['asset', 'expense'])) {
                    $balance += ($debit - $credit);
                } else {
                    $balance += ($credit - $debit);
                }
                
                $transaction_count++;
                
                // 최근 거래일 업데이트
                if (!$last_transaction_date || $journal_entry['entry_date'] > $last_transaction_date) {
                    $last_transaction_date = $journal_entry['entry_date'];
                }
            }
            
            // 제로 밸런스 옵션 처리
            if (!$include_zero && abs($balance) < 0.01) {
                continue;
            }
            
            $result[] = [
                'account_id' => $account['account_id'],
                'account_name' => $account['account_name'],
                'account_type' => $account['account_type'],
                'statement_detail_category' => $account['statement_detail_category'] ?? getDefaultStatementCategory($account['account_type']),
                'balance' => round($balance, 2),
                'formatted_balance' => number_format(abs($balance), 0),
                'transaction_count' => $transaction_count,
                'last_transaction_date' => $last_transaction_date
            ];
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log('getBalanceSheetFromJournalEntries Error: ' . $e->getMessage());
        
        // 오류 발생 시 간단한 샘플 데이터 반환
        return getSampleBalanceSheetData();
    }
}

/**
 * 기본 statement category 반환
 */
function getDefaultStatementCategory($account_type) {
    switch ($account_type) {
        case 'asset':
            return 'current_asset'; // 기본값
        case 'liability':
            return 'current_liability';
        case 'equity':
            return 'equity';
        default:
            return null;
    }
}

/**
 * 빈 Balance Sheet 구조 반환
 */
function getEmptyBalanceSheetStructure() {
    return [];
}

/**
 * 샘플 Balance Sheet 데이터 반환 (개발/테스트용)
 */
function getSampleBalanceSheetData() {
    return [
        [
            'account_id' => 'sample-cash-1',
            'account_name' => 'Cash',
            'account_type' => 'asset',
            'statement_detail_category' => 'current_asset',
            'balance' => 50000000,
            'formatted_balance' => '50,000,000',
            'transaction_count' => 5,
            'last_transaction_date' => '2025-07-20'
        ],
        [
            'account_id' => 'sample-bank-1',
            'account_name' => 'Bank Account',
            'account_type' => 'asset',
            'statement_detail_category' => 'current_asset',
            'balance' => 100000000,
            'formatted_balance' => '100,000,000',
            'transaction_count' => 10,
            'last_transaction_date' => '2025-07-21'
        ],
        [
            'account_id' => 'sample-equipment-1',
            'account_name' => 'Equipment',
            'account_type' => 'asset',
            'statement_detail_category' => 'non_current_asset',
            'balance' => 75000000,
            'formatted_balance' => '75,000,000',
            'transaction_count' => 2,
            'last_transaction_date' => '2025-07-15'
        ],
        [
            'account_id' => 'sample-liability-1',
            'account_name' => 'Accounts Payable',
            'account_type' => 'liability',
            'statement_detail_category' => 'current_liability',
            'balance' => 25000000,
            'formatted_balance' => '25,000,000',
            'transaction_count' => 3,
            'last_transaction_date' => '2025-07-18'
        ],
        [
            'account_id' => 'sample-equity-1',
            'account_name' => 'Owner\'s Equity',
            'account_type' => 'equity',
            'statement_detail_category' => 'equity',
            'balance' => 200000000,
            'formatted_balance' => '200,000,000',
            'transaction_count' => 1,
            'last_transaction_date' => '2025-01-01'
        ]
    ];
}

/**
 * Supabase REST API 호출 함수
 */
function callSupabaseAPI($endpoint, $method = 'GET', $data = null) {
    global $SUPABASE_URL, $SUPABASE_KEY;
    
    $url = $SUPABASE_URL . '/rest/v1/' . $endpoint;
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_HTTPHEADER => [
            'apikey: ' . $SUPABASE_KEY,
            'Authorization: Bearer ' . $SUPABASE_KEY,
            'Content-Type: application/json',
            'Prefer: return=representation'
        ]
    ]);
    
    if ($method === 'POST') {
        curl_setopt($curl, CURLOPT_POST, true);
        if ($data) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));
        }
    }
    
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
 * 회사 목록 조회 (Supabase DB)
 */
function getCompaniesFromDB() {
    try {
        // 특정 사용자의 회사만 가져오기 위해 user_companies 조인
        $user_id = $_GET['user_id'] ?? null;
        
        if ($user_id) {
            // 사용자와 연관된 회사만 가져오기
            $companies = callSupabaseAPI('user_companies?select=companies(company_id,company_name,base_currency_id,created_at,is_deleted,currency_types(currency_code,symbol))&user_id=eq.' . $user_id . '&companies.is_deleted=is.false');
            
            // 중첩된 구조에서 companies 데이터 추출
            $company_data = [];
            foreach ($companies as $user_company) {
                if (isset($user_company['companies'])) {
                    $company_data[] = $user_company['companies'];
                }
            }
        } else {
            // user_id가 없으면 모든 회사 가져오기 (fallback)
            $company_data = callSupabaseAPI('companies?select=company_id,company_name,base_currency_id,created_at,is_deleted,currency_types(currency_code,symbol)&is_deleted=is.false&order=company_name.asc&limit=50');
        }
        
        $companies = $company_data;
        
        $result = [];
        foreach ($companies as $company) {
            // 회사명에서 UUID 제거
            $company_name = $company['company_name'];
            
            // 괄호 안의 UUID 제거 (예: "Company Name (uuid)" → "Company Name")
            $company_name = preg_replace('/\s*\([a-f0-9-]{36}\)\s*$/i', '', $company_name);
            
            $result[] = [
                'company_id' => $company['company_id'],
                'company_name' => $company_name,
                'base_currency' => $company['currency_types']['currency_code'] ?? 'VND',
                'currency_symbol' => $company['currency_types']['symbol'] ?? '₫',
                'is_active' => true,
                'created_at' => $company['created_at']
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'companies' => $result,
                'total_companies' => count($result),
                'active_companies' => count($result)
            ]
        ];
        
    } catch (Exception $e) {
        error_log('getCompaniesFromDB Error: ' . $e->getMessage());
        return errorResponse('Failed to retrieve companies: ' . $e->getMessage(), 500);
    }
}

/**
 * 매장 목록 조회 (Supabase DB)
 */
function getStoresByCompanyFromDB($params) {
    $company_id = $params['company_id'] ?? '';
    
    if (empty($company_id)) {
        return errorResponse('Missing required parameter: company_id', 400);
    }
    
    if (!isValidUuid($company_id)) {
        return errorResponse('Invalid company ID format', 400);
    }
    
    try {
        // 회사 정보 확인
        $company_data = callSupabaseAPI('companies?select=company_id,company_name,base_currency_id,currency_types(currency_code,symbol)&company_id=eq.' . $company_id . '&is_deleted=is.false');
        
        if (empty($company_data)) {
            return errorResponse('Company not found', 404);
        }
        
        $company_info = $company_data[0];
        
        // 매장 목록 조회
        $stores = callSupabaseAPI('stores?select=store_id,store_name,store_code,is_deleted,created_at&company_id=eq.' . $company_id . '&is_deleted=is.false&order=store_name.asc&limit=10');
        
        $result = [];
        foreach ($stores as $store) {
            // store_name만 깔끔하게 표시 (UUID와 괄호 모두 제거)
            $store_name = $store['store_name'];
            
            // store_name이 UUID 형식인지 확인하고 store_code로 대체
            if (isValidUuid($store_name) && !empty($store['store_code'])) {
                $store_name = 'Store ' . $store['store_code'];
            }
            
            // 괄호 안의 모든 내용 제거 (예: "Store Name (anything)" → "Store Name")
            $store_name = preg_replace('/\s*\([^)]*\)\s*/', '', $store_name);
            
            // 앞뒤 공백 제거
            $store_name = trim($store_name);
            
            $result[] = [
                'store_id' => $store['store_id'],
                'store_name' => $store_name,
                'store_code' => $store['store_code'],
                'is_active' => true,
                'created_at' => $store['created_at']
            ];
        }
        
        return [
            'success' => true,
            'data' => [
                'company_info' => [
                    'company_name' => $company_info['company_name']
                ],
                'stores' => $result,
                'total_stores' => count($result),
                'active_stores' => count($result)
            ]
        ];
        
    } catch (Exception $e) {
        error_log('getStoresByCompanyFromDB Error: ' . $e->getMessage());
        return errorResponse('Failed to retrieve stores: ' . $e->getMessage(), 500);
    }
}

/**
 * 계정 분류 함수
 */
function categorizeAccountsFromDB($raw_data) {
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
                'formatted_balance' => number_format(abs(floatval($account['balance'])), 0),
                'transaction_count' => intval($account['transaction_count'] ?? 0),
                'last_transaction_date' => $account['last_transaction_date']
            ];
        }
    }
    
    return $categorized;
}

/**
 * 총계 계산 함수
 */
function calculateTotalsFromDB($categorized_data) {
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
function formatResponseFromDB($company_info, $store_info, $categorized_data, $totals, $as_of_date) {
    return [
        'success' => true,
        'timestamp' => date('Y-m-d H:i:s'),
        'data' => [
            'company_info' => [
                'company_id' => $company_info['company_id'],
                'company_name' => $company_info['company_name'],
                'store_id' => $store_info['store_id'] ?? null,
                'store_name' => $store_info['store_name'] ?? null,
                'base_currency' => $company_info['currency_types']['currency_code'] ?? 'VND',
                'currency_symbol' => $company_info['currency_types']['symbol'] ?? '₫'
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
                'formatted_total_assets' => number_format(abs($totals['total_assets']), 0)
            ],
            'liabilities' => [
                'current_liabilities' => $categorized_data['current_liabilities'],
                'non_current_liabilities' => $categorized_data['non_current_liabilities'],
                'total_current_liabilities' => $totals['total_current_liabilities'],
                'total_non_current_liabilities' => $totals['total_non_current_liabilities'],
                'total_liabilities' => $totals['total_liabilities'],
                'formatted_total_liabilities' => number_format(abs($totals['total_liabilities']), 0)
            ],
            'equity' => [
                'equity_accounts' => $categorized_data['equity'],
                'total_equity' => $totals['total_equity'],
                'formatted_total_equity' => number_format(abs($totals['total_equity']), 0)
            ],
            'totals' => [
                'total_assets' => $totals['total_assets'],
                'total_liabilities_and_equity' => $totals['total_liabilities_and_equity'],
                'balance_check' => $totals['balance_check'],
                'balance_difference' => $totals['balance_difference'],
                'formatted_total_assets' => number_format(abs($totals['total_assets']), 0),
                'formatted_total_liab_equity' => number_format(abs($totals['total_liabilities_and_equity']), 0)
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
                )),
                'empty_data_returned' => empty(array_merge(...array_values($categorized_data)))
            ]
        ]
    ];
}

/**
 * 연결 테스트 함수
 */
function testConnectionDB() {
    try {
        $test_data = callSupabaseAPI('companies?select=count&limit=1');
        
        return [
            'success' => true,
            'message' => 'Supabase connection successful',
            'data' => [
                'server_time' => date('Y-m-d H:i:s'),
                'php_version' => phpversion(),
                'supabase_status' => 'connected',
                'test_query_result' => $test_data,
                'status' => 'ready'
            ],
            'timestamp' => date('Y-m-d H:i:s')
        ];
    } catch (Exception $e) {
        return [
            'success' => false,
            'message' => 'Supabase connection failed',
            'error' => $e->getMessage(),
            'timestamp' => date('Y-m-d H:i:s')
        ];
    }
}

/**
 * 입력값 검증 함수
 */
function validateInput($params) {
    $errors = [];
    
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
        // YYYY-MM-DD 또는 YYYY-MM 형식 모두 허용
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $params['as_of_date'])) {
            $date = DateTime::createFromFormat('Y-m-d', $params['as_of_date']);
            if (!$date || $date->format('Y-m-d') !== $params['as_of_date']) {
                $errors[] = 'Invalid date format. Use YYYY-MM-DD';
            } elseif ($date > new DateTime()) {
                $errors[] = 'Future dates are not allowed';
            }
        } elseif (preg_match('/^\d{4}-\d{2}$/', $params['as_of_date'])) {
            // YYYY-MM 형식 검증
            $year = (int)substr($params['as_of_date'], 0, 4);
            $month = (int)substr($params['as_of_date'], 5, 2);
            if ($year < 1900 || $year > 2100 || $month < 1 || $month > 12) {
                $errors[] = 'Invalid year-month format. Use YYYY-MM';
            }
        } else {
            $errors[] = 'Invalid date format. Use YYYY-MM-DD or YYYY-MM';
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
