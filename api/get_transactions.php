<?php
/**
 * Get Transactions API
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
$limit = $_GET['limit'] ?? 100;
$offset = $_GET['offset'] ?? 0;

try {
    $db = new SupabaseDB();
    
    // Build query parameters for v_journal_lines_readable
    // This view should have journal entry data with readable names
    $params = [
        'order' => 'entry_date.desc,created_at.desc',
        'limit' => $limit,
        'offset' => $offset,
        'select' => '*'
    ];
    
    // The view might already be filtered by user permissions
    // Let's try without any filters first
    $data = $db->query('v_journal_lines_readable', $params);
    
    // If we get data, filter it in PHP based on company and add counterparty info
    if ($data && is_array($data)) {
        // Get all stores for this company
        $stores = getUserStores($user_id, $company_id);
        $store_ids = array_column($stores, 'store_id');
        $store_names = array_column($stores, 'store_name');
        
        // Get journal entries to find counterparty info
        $journal_ids = array_unique(array_column($data, 'journal_id'));
        if (!empty($journal_ids)) {
            $journal_entries = $db->query('journal_entries', [
                'journal_id' => 'in.(' . implode(',', $journal_ids) . ')',
                'select' => 'journal_id,counterparty_id'
            ]);
            
            $journal_counterparty_map = [];
            foreach ($journal_entries as $je) {
                if ($je['counterparty_id']) {
                    $journal_counterparty_map[$je['journal_id']] = $je['counterparty_id'];
                }
            }
            
            // Get counterparty names
            if (!empty($journal_counterparty_map)) {
                $counterparty_ids = array_unique(array_values($journal_counterparty_map));
                $counterparties = $db->query('counterparties', [
                    'counterparty_id' => 'in.(' . implode(',', $counterparty_ids) . ')',
                    'select' => 'counterparty_id,name'
                ]);
                
                $counterparty_names = [];
                foreach ($counterparties as $cp) {
                    $counterparty_names[$cp['counterparty_id']] = $cp['name'];
                }
            }
        }
        
        // Filter transactions by store names if store_id is not available
        if ($store_id || !empty($store_names)) {
            $filtered_data = [];
            
            foreach ($data as $transaction) {
                // Add counterparty info if available
                if (isset($journal_counterparty_map[$transaction['journal_id']])) {
                    $cp_id = $journal_counterparty_map[$transaction['journal_id']];
                    $transaction['counterparty_name'] = $counterparty_names[$cp_id] ?? null;
                } else {
                    $transaction['counterparty_name'] = null;
                }
                
                // Check if this transaction belongs to one of our stores
                if (isset($transaction['store_name'])) {
                    if ($store_id) {
                        // Find the store name for the given store_id
                        $selected_store = array_filter($stores, function($s) use ($store_id) {
                            return $s['store_id'] == $store_id;
                        });
                        $selected_store = array_values($selected_store);
                        if (!empty($selected_store) && $transaction['store_name'] == $selected_store[0]['store_name']) {
                            $filtered_data[] = $transaction;
                        }
                    } else if (in_array($transaction['store_name'], $store_names)) {
                        $filtered_data[] = $transaction;
                    }
                } else if (!$store_id) {
                    // If no store_name field, include it when no specific store is selected
                    $filtered_data[] = $transaction;
                }
            }
            
            $data = array_slice($filtered_data, 0, $limit);
        }
    }
    
    echo json_encode([
        'success' => true,
        'data' => $data,
        'count' => count($data)
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
