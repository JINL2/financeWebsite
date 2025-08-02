<?php
/**
 * Get Cash Location Balance API
 * Fetches current balance for a specific cash location
 */

header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once '../common/auth.php';
require_once '../common/functions.php';

try {
    // Get parameters
    $cash_location_id = $_GET['cash_location_id'] ?? '';
    $company_id = $_GET['company_id'] ?? '';
    
    if (empty($cash_location_id)) {
        throw new Exception('Cash location ID is required');
    }
    
    if (empty($company_id)) {
        throw new Exception('Company ID is required');
    }
    
    global $supabase;
    
    // First, verify the cash location exists and belongs to the company
    $locationResponse = $supabase->query('cash_locations', [
        'select' => 'cash_location_id,location_name,currency_code',
        'cash_location_id' => 'eq.' . $cash_location_id,
        'company_id' => 'eq.' . $company_id
    ]);
    
    if (!$locationResponse || count($locationResponse) === 0) {
        throw new Exception('Cash location not found or access denied');
    }
    
    $location = $locationResponse[0];
    
    // Calculate current balance by summing all cash transactions for this location
    // This is a simplified calculation - in a real system you'd want to optimize this
    $balanceQuery = "
        SELECT 
            COALESCE(SUM(
                CASE 
                    WHEN jl.debit_amount > 0 THEN jl.debit_amount
                    ELSE -jl.credit_amount
                END
            ), 0) as current_balance
        FROM journal_lines jl
        JOIN journal_entries je ON jl.journal_entry_id = je.journal_entry_id
        JOIN accounts a ON jl.account_id = a.account_id
        WHERE jl.cash_location_id = ?
          AND je.company_id = ?
          AND a.category_tag = 'cash'
          AND je.entry_status = 'posted'
    ";
    
    // Execute RPC function to get balance
    $rpcResult = $supabase->rpc('get_cash_location_balance', [
        'p_cash_location_id' => $cash_location_id,
        'p_company_id' => $company_id
    ]);
    
    $currentBalance = 0;
    if ($rpcResult && is_array($rpcResult) && count($rpcResult) > 0) {
        $currentBalance = floatval($rpcResult[0]['current_balance'] ?? 0);
    }
    
    // Get recent transactions for this location (last 5)
    $recentTransactions = $supabase->query('journal_lines', [
        'select' => 'journal_entry_id,debit_amount,credit_amount,description,journal_entries(entry_date)',
        'cash_location_id' => 'eq.' . $cash_location_id,
        'order' => 'created_at.desc',
        'limit' => 5
    ]);
    
    // Format response
    $response = [
        'success' => true,
        'data' => [
            'cash_location_id' => $location['cash_location_id'],
            'location_name' => $location['location_name'],
            'currency_code' => $location['currency_code'] ?? 'KRW',
            'current_balance' => $currentBalance,
            'balance_formatted' => number_format($currentBalance, 2),
            'last_updated' => date('Y-m-d H:i:s'),
            'recent_transactions' => $recentTransactions ?? []
        ]
    ];
    
    echo json_encode($response);
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => null
    ]);
}
?>
