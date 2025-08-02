<?php
/**
 * Get Income Statement Data API
 */
require_once '../common/auth.php';
require_once '../common/db.php';
require_once '../common/functions.php';

header('Content-Type: application/json');

// Authentication
$auth = requireAuth();
$user_id = $auth['user_id'];
$company_id = $auth['company_id'];
$store_id = $_GET['store_id'] ?? null;
$date_from = $_GET['date_from'] ?? null;
$date_to = $_GET['date_to'] ?? null;

try {
    $db = new SupabaseDB();
    
    // Get income statement data from view
    $params = [
        'company_id' => 'eq.' . $company_id
    ];
    
    if ($store_id) {
        $params['store_id'] = 'eq.' . $store_id;
    }
    
    // If we have date filters, we need to use a different approach
    // Since v_income_statement_by_store might not have date filters
    // We'll use v_journal_lines_readable instead
    if ($date_from && $date_to) {
        // Get all stores for this company
        $stores = getUserStores($user_id, $company_id);
        $store_names = array_column($stores, 'store_name');
        
        // Query journal lines for the date range
        $params = [
            'entry_date' => 'gte.' . $date_from,
            'and' => '(entry_date.lte.' . $date_to . ')',
            'order' => 'entry_date.desc'
        ];
        
        if ($store_id) {
            $selected_store = array_filter($stores, function($s) use ($store_id) {
                return $s['store_id'] == $store_id;
            });
            $selected_store = array_values($selected_store);
            if (!empty($selected_store)) {
                $params['store_name'] = 'eq.' . $selected_store[0]['store_name'];
            }
        }
        
        $journal_lines = $db->query('v_journal_lines_readable', $params);
        
        // Get account types
        $accounts = $db->query('accounts', [
            'company_id' => 'eq.' . $company_id,
            'select' => 'account_id,account_name,account_type'
        ]);
        
        $account_map = [];
        foreach ($accounts as $acc) {
            $account_map[$acc['account_name']] = $acc['account_type'];
        }
        
        // Aggregate by account
        $income_statement = [];
        foreach ($journal_lines as $line) {
            // Filter by store if not already filtered
            if (!$store_id && !empty($store_names) && !in_array($line['store_name'], $store_names)) {
                continue;
            }
            
            $account_name = $line['account_name'];
            $account_type = $account_map[$account_name] ?? null;
            
            if ($account_type === 'income' || $account_type === 'expense') {
                $key = $company_id . '_' . ($line['store_id'] ?? 'null') . '_' . $account_name;
                
                if (!isset($income_statement[$key])) {
                    $income_statement[$key] = [
                        'company_id' => $company_id,
                        'store_id' => $line['store_id'] ?? null,
                        'store_name' => $line['store_name'] ?? null,
                        'account_type' => $account_type,
                        'account_name' => $account_name,
                        'amount' => 0
                    ];
                }
                
                // Add debit and subtract credit
                $income_statement[$key]['amount'] += ($line['debit'] ?? 0) - ($line['credit'] ?? 0);
            }
        }
        
        $data = array_values($income_statement);
    } else {
        // Use the regular view without date filter
        $data = $db->query('v_income_statement_by_store', $params);
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
