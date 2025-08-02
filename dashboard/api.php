<?php
/**
 * 대시보드 API - 에러 처리 포함 버전
 * 없는 RPC 함수는 기본값 반환
 */
require_once '../common/auth.php';
require_once '../common/db.php';
require_once '../common/functions.php';

header('Content-Type: application/json');

$auth = requireAuth();
$user_id = $auth['user_id'];
$company_id = $auth['company_id'];
$store_id = $_GET['store_id'] ?? null;
$action = $_GET['action'] ?? '';
$date_from = $_GET['date_from'] ?? null;
$date_to = $_GET['date_to'] ?? null;

$db = new SupabaseDB();

// 회사별 통화 정보 가져오기 - 공통 함수 사용
$currency_info = getCompanyCurrency($company_id);
$currency_code = $currency_info['currency_code'];
$currency_symbol = $currency_info['currency_symbol'];
$currency_name = $currency_info['currency_name'];

try {
    switch ($action) {
        case 'get_summary':
            $response = [
                'balance' => [
                    'total_debit' => 0,
                    'total_credit' => 0,
                    'balance_difference' => 0
                ],
                'income' => [
                    'total_income' => 0,
                    'total_expense' => 0,
                    'net_income' => 0
                ]
            ];
            
            // 1. Income Statement Summary - 유저 날짜 사용하도록 수정
            try {
                // 사용자의 컴퓨터/핸드폰 날짜를 가져오기 (클라이언트에서 전달받음)
                $request_date = $_GET['request_date'] ?? date('Y-m-d'); // 기본값으로 서버 날짜 사용
                
                // get_income_statement_summary 함수는 단일 날짜만 받음
                // 매개변수: p_company_id (uuid), p_request_date (date), p_store_id (uuid, optional)
                $rpcParams = [
                    'p_company_id' => $company_id,
                    'p_request_date' => $request_date
                ];
                
                // store_id가 있고 'All Stores'가 아닌 경우만 추가
                if ($store_id && $store_id !== 'null') {
                    $rpcParams['p_store_id'] = $store_id;
                }
                
                // 디버깅: 매개변수 확인
                $response['debug_params'] = $rpcParams;
                
                $incomeSummary = $db->callRPC('get_income_statement_summary', $rpcParams);
                
                // 디버깅: RPC 응답 확인
                $response['debug_rpc_response'] = $incomeSummary;
                
                if (!empty($incomeSummary)) {
                    // RPC 응답 파싱 수정 - 실제 응답 구조에 맞게 조정
                    if (isset($incomeSummary['revenue']) && isset($incomeSummary['expense'])) {
                        // 직접 반환되는 경우
                        $summary = $incomeSummary;
                    } elseif (is_array($incomeSummary) && count($incomeSummary) > 0) {
                        // 배열로 반환되는 경우 - 첫 번째 요소가 결과
                        $summary = $incomeSummary[0];
                    }
                    
                    if (isset($summary)) {
                        $response['income']['total_income'] = floatval($summary['revenue'] ?? 0);
                        $response['income']['total_expense'] = floatval($summary['expense'] ?? 0);
                        $response['income']['net_income'] = floatval($summary['revenue'] ?? 0) - floatval($summary['expense'] ?? 0);
                    }
                }
            } catch (Exception $e) {
                error_log("Income Statement RPC Error: " . $e->getMessage());
                // 에러 시 기본값 유지 (0, 0, 0)
            }
            
            // Balance Sheet Summary removed as per requirements
            
            echo json_encode([
                'success' => true,
                'data' => $response,
                'currency_symbol' => $currency_symbol,
                'currency_code' => $currency_code
            ]);
            break;
            
        case 'get_recent_transactions':
            try {
                // v_journal_lines_complete 뷰 사용하여 거래 내역 조회
                $params = [
                    'order' => 'entry_date.desc,journal_created_at.desc',
                    'limit' => 500,  // 충분히 가져와서 필터링
                    'select' => '*',
                    'company_id' => 'eq.' . $company_id  // company_id 필터 추가
                ];
                
                // 날짜 필터
                if ($date_from) {
                    $params['entry_date'] = 'gte.' . $date_from;
                }
                if ($date_to) {
                    if (isset($params['entry_date'])) {
                        $params['entry_date'] .= '&entry_date=lte.' . $date_to;
                    } else {
                        $params['entry_date'] = 'lte.' . $date_to;
                    }
                }
                
                // 기본값: 현재 월
                if (!$date_from && !$date_to) {
                    $currentMonth = date('Y-m');
                    $params['entry_date'] = 'gte.' . $currentMonth . '-01';
                }
                
                // 스토어 필터가 있는 경우 데이터베이스 레벨에서 필터링
                if ($store_id) {
                    $params['store_id'] = 'eq.' . $store_id;
                }
                
                $journalLines = $db->query('v_journal_lines_complete', $params);
                
                if (empty($journalLines)) {
                    echo json_encode([
                        'success' => true,
                        'data' => []
                    ]);
                    break;
                }
                
                // journal_id별로 그룹화
                $processedJournals = [];
                $journalOrder = []; // 순서 유지용
                
                foreach ($journalLines as $line) {
                    $journalId = $line['journal_id'];
                    
                    if (!isset($processedJournals[$journalId])) {
                        $journalOrder[] = $journalId; // 순서 저장
                        $processedJournals[$journalId] = [
                            'journal_id' => $journalId,
                            'entry_date' => $line['entry_date'],
                            'description' => $line['journal_description'] ?? '',
                            'company_name' => $line['company_name'] ?? '',
                            'created_by' => $line['created_by_name'] ?? 'Unknown',
                            'counterparty_name' => $line['counterparty_name'] ?? $line['journal_counterparty_name'] ?? null,
                            'total_debit' => 0,
                            'lines' => [],
                            'has_filtered_lines' => false  // 필터링된 라인이 있는지 체크
                        ];
                    }
                    
                    // 라인 추가
                    $processedJournals[$journalId]['lines'][] = [
                        'account_name' => $line['account_name'] ?? 'Unknown',
                        'debit' => floatval($line['debit'] ?? 0),
                        'credit' => floatval($line['credit'] ?? 0),
                        'description' => $line['line_description'] ?? '',
                        'cash_location_name' => $line['cash_location_name'] ?? null,
                        'store_name' => $line['store_name'] ?? null,
                        'store_id' => $line['store_id'] ?? null
                    ];
                    
                    // 스토어 필터에 맞는 라인이 있으면 표시
                    if (!$store_id || $line['store_id'] == $store_id) {
                        $processedJournals[$journalId]['has_filtered_lines'] = true;
                    }
                    
                    // 총 차변 계산
                    $processedJournals[$journalId]['total_debit'] += floatval($line['debit'] ?? 0);
                }
                
                // 순서대로 배열 만들기 (최근 10개만)
                // 스토어 필터가 있는 경우 해당 스토어의 라인을 포함한 journal만 표시
                $recentEntries = [];
                $count = 0;
                foreach ($journalOrder as $journalId) {
                    if ($count >= 10) break;
                    // 필터링된 라인이 있는 journal만 포함
                    if (!$store_id || $processedJournals[$journalId]['has_filtered_lines']) {
                        unset($processedJournals[$journalId]['has_filtered_lines']); // 임시 필드 제거
                        $recentEntries[] = $processedJournals[$journalId];
                        $count++;
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $recentEntries,
                    'currency_symbol' => $currency_symbol,
                    'currency_code' => $currency_code
                ]);
                
            } catch (Exception $e) {
                error_log("Recent Transactions Error: " . $e->getMessage());
                error_log("Error details: " . print_r($e, true));
                // 에러시 빈 배열 반환
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'error' => $e->getMessage()
                ]);
            }
            break;
            
        case 'get_cash_balance':
            try {
                $cashSummary = $db->callRPC('get_cash_balance_summary', [
                    'p_company_id' => $company_id,
                    'p_store_id' => $store_id,
                    'p_include_headquarters' => true
                ]);
                
                $totalCash = 0;
                $locationCount = 0;
                
                if (!empty($cashSummary) && isset($cashSummary[0])) {
                    $totalCash = floatval($cashSummary[0]['total_cash']);
                    $locationCount = intval($cashSummary[0]['location_count']);
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_cash' => $totalCash,
                        'location_count' => $locationCount
                    ]
                ]);
            } catch (Exception $e) {
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'total_cash' => 0,
                        'location_count' => 0
                    ]
                ]);
            }
            break;
            
        default:
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'error' => 'Invalid action'
            ]);
    }
    
} catch (Exception $e) {
    // 전체 에러 처리
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
