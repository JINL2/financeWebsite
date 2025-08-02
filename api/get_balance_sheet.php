<?php
/**
 * Get Balance Sheet Data API
 */
require_once '../common/auth.php';
require_once '../common/db.php';

header('Content-Type: application/json');

// Authentication
$auth = requireAuth();
$user_id = $auth['user_id'];
$company_id = $auth['company_id'];
$store_id = $_GET['store_id'] ?? null;

try {
    $db = new SupabaseDB();
    
    // Get balance sheet data from view
    $params = [
        'company_id' => 'eq.' . $company_id
    ];
    
    if ($store_id) {
        $params['store_id'] = 'eq.' . $store_id;
    }
    
    $data = $db->query('v_balance_sheet_by_store', $params);
    
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
