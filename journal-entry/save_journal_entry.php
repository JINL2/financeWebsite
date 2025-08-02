<?php
/**
 * Financial Management System - Journal Entry Save API
 * Updated to use insert_journal_with_everything SQL function
 */

// Set response header to JSON
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

require_once '../common/auth.php';
require_once '../common/functions.php';
require_once 'apply_counterparty_cash_location.php';

try {
    // Get input data
    $input = file_get_contents('php://input');
    $data = json_decode($input, true);
    
    if (!$data) {
        throw new Exception('Invalid JSON data');
    }
    
    // Get authentication parameters
    $user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? '';
    $company_id = $_GET['company_id'] ?? $_POST['company_id'] ?? '';
    
    // Check if user is authenticated
    if (empty($user_id) || empty($company_id)) {
        throw new Exception('Unauthorized - user_id and company_id parameters required');
    }
    
    // Handle auto-save and draft mode
    $is_draft = isset($data['is_draft']) && $data['is_draft'] === true;
    $is_auto_save = isset($data['auto_save']) && $data['auto_save'] === true;
    
    // Debug: Log received data
    error_log('Received data: ' . json_encode($data));
    
    // 🔥 NEW: counterparty_cash_location_id 추출 (최상위 레벨에서)
    $counterparty_cash_location_id = $data['counterparty_cash_location_id'] ?? null;
    error_log('Extracted counterparty_cash_location_id: ' . ($counterparty_cash_location_id ?? 'null'));
    
    // Validation based on save type
    if (!$is_auto_save) {
        // Manual save - strict validation
        if (!isset($data['p_company_id'])) {
            throw new Exception('Company ID is required');
        }
        if (!isset($data['p_entry_date'])) {
            throw new Exception('Entry date is required');
        }
        // Store ID can be empty/null, just check if it exists in the data
        if (!isset($data['p_store_id']) || empty($data['p_store_id'])) {
            error_log('Store ID not set or empty, setting to null');
            $data['p_store_id'] = null;
        }
    } else {
        // Auto save - basic validation
        if (!isset($data['p_company_id'])) {
            throw new Exception('Company ID is required');
        }
        if (!isset($data['p_store_id']) || empty($data['p_store_id'])) {
            $data['p_store_id'] = null;
        }
    }
    
    // Validate company_id matches authenticated user
    if ($data['p_company_id'] !== $company_id) {
        throw new Exception('Unauthorized company access');
    }
    
    // Validate journal lines
    if (!$is_auto_save) {
        if (!isset($data['p_lines']) || !is_array($data['p_lines']) || count($data['p_lines']) < 2) {
            throw new Exception('At least 2 journal lines are required');
        }
    } else {
        if (!isset($data['p_lines']) || !is_array($data['p_lines'])) {
            $data['p_lines'] = [];
        }
    }
    
    // 💰 4단계: Cash 계정 Location 필수 검증
    define('CASH_ACCOUNT_ID', 'd4a7a16e-45a1-47fe-992b-ff807c8673f0');
    $cash_location_required = false;
    
    foreach ($data['p_lines'] as $line) {
        if (isset($line['account_id']) && $line['account_id'] === CASH_ACCOUNT_ID) {
            $cash_location_required = true;
            break;
        }
    }
    
    // Cash 계정이 사용된 경우 Location 필수 검증
    if ($cash_location_required && !$is_auto_save) {
        if (!isset($data['p_if_cash_location_id']) || empty($data['p_if_cash_location_id'])) {
            throw new Exception('현금 계정을 사용할 때는 위치 정보가 필수입니다.');
        }
    }
    
    // Validate balance and format lines for SQL function
    $total_debit = 0;
    $total_credit = 0;
    $formatted_lines = [];
    
    foreach ($data['p_lines'] as $line) {
        if (!isset($line['account_id']) || empty($line['account_id'])) {
            throw new Exception('Account selection is required for all lines');
        }
        
        $debit = floatval($line['debit'] ?? 0);
        $credit = floatval($line['credit'] ?? 0);
        
        if ($debit === 0 && $credit === 0) {
            throw new Exception('Each line must have either debit or credit amount');
        }
        
        if ($debit > 0 && $credit > 0) {
            throw new Exception('A line cannot have both debit and credit amounts');
        }
        
        $total_debit += $debit;
        $total_credit += $credit;
        
        // Format line for SQL function
        $formatted_line = [
            'account_id' => $line['account_id'],
            'description' => $line['description'] ?? '',
            'debit' => $debit,
            'credit' => $credit
        ];
        
        // Add optional debt information
        if (isset($line['debt']) && !empty($line['debt'])) {
            $formatted_line['debt'] = [
                'counterparty_id' => $line['debt']['counterparty_id'] ?? null,
                'direction' => $line['debt']['direction'] ?? null,
                'category' => $line['debt']['category'] ?? null,
                'interest_rate' => $line['debt']['interest_rate'] ?? 0,
                'interest_account_id' => $line['debt']['interest_account_id'] ?? null,
                'interest_due_day' => $line['debt']['interest_due_day'] ?? null,
                'issue_date' => $line['debt']['issue_date'] ?? null,
                'due_date' => $line['debt']['due_date'] ?? null,
                'description' => $line['debt']['description'] ?? '',
                'linkedCounterparty_store_id' => $line['debt']['linkedCounterparty_store_id'] ?? null,
                'counterparty_cash_location_id' => $line['debt']['counterparty_cash_location_id'] ?? null
            ];
        }
        
        // Add optional fixed asset information
        if (isset($line['fix_asset']) && !empty($line['fix_asset'])) {
            $formatted_line['fix_asset'] = $line['fix_asset'];
        }
        
        // Add optional cash location information
        if (isset($line['cash']) && !empty($line['cash'])) {
            $formatted_line['cash'] = $line['cash'];
        }
        
        $formatted_lines[] = $formatted_line;
    }
    
    // Balance validation
    if (!$is_auto_save && abs($total_debit - $total_credit) > 0.01) {
        throw new Exception('Total debits must equal total credits');
    } elseif ($is_auto_save) {
        if (abs($total_debit - $total_credit) > 0.01) {
            error_log("Auto-save warning: Balance not equal - Debit: $total_debit, Credit: $total_credit");
        }
    }
    
    // Reference number generation removed
    
    // Get Supabase client
    global $supabase;
    
    // 🔥 기존 API에서 counterparty_cash_location_id 제거 후 전송
    $supabase_data = $data;
    unset($supabase_data['counterparty_cash_location_id']); // 기존 API는 이 필드를 모름
    
    // Call the comprehensive SQL function
    $function_params = [
        'p_base_amount' => $total_debit,
        'p_company_id' => $supabase_data['p_company_id'],
        'p_created_by' => $user_id,
        'p_description' => $supabase_data['p_description'] ?? '',
        'p_entry_date' => $supabase_data['p_entry_date'] ?? date('Y-m-d H:i:s'),
        'p_lines' => $formatted_lines,
        'p_counterparty_id' => $supabase_data['p_counterparty_id'] ?? null,
        'p_if_cash_location_id' => $supabase_data['p_if_cash_location_id'] ?? null,
        'p_store_id' => $supabase_data['p_store_id'] ?? null
    ];
    
    // Execute the comprehensive function
    $result = $supabase->callRPC('insert_journal_with_everything', $function_params);
    
    if (!$result) {
        throw new Exception('Failed to save journal entry via comprehensive function');
    }
    
    // RPC 함수는 직접 journal_id를 반환할 수 있음
    $journal_id = is_array($result) && isset($result[0]) ? $result[0] : $result;
    
    // 🔥 NEW: Post-Processing - Counterparty Cash Location 적용
    if (!empty($counterparty_cash_location_id) && !empty($journal_id)) {
        error_log('Starting Post-Processing for counterparty_cash_location_id: ' . $counterparty_cash_location_id);
        
        $cash_location_result = applyCounterpartyCashLocation($journal_id, $counterparty_cash_location_id);
        
        if ($cash_location_result['success']) {
            error_log('Counterparty Cash Location 적용 성공: ' . json_encode($cash_location_result));
        } else {
            // 에러 로깅하지만 전체 실패로 처리하지는 않음 (기존 Journal Entry는 이미 저장됨)
            error_log('Counterparty Cash Location 적용 실패: ' . $cash_location_result['error']);
            error_log('Journal Entry는 성공적으로 저장됨, Cash Location만 실패');
        }
    }
    
    // Return success response
    $message = $is_auto_save ? 'Journal entry auto-saved successfully' : 'Journal entry saved successfully';
    
    echo json_encode([
        'success' => true,
        'message' => $message,
        'journal_id' => $journal_id,
        'total_amount' => $total_debit,
        'is_draft' => $is_draft,
        'is_auto_save' => $is_auto_save
    ]);
    
} catch (Exception $e) {
    // Log error
    error_log('Journal Entry Save Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

// getNextSequenceNumber function removed
?>
