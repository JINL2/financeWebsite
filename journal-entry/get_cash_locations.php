<?php
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../common/auth.php';
require_once '../common/functions.php';

try {
    // Get Supabase client
    global $supabase;
    
    // Get parameters from request
    $company_id = $_GET['company_id'] ?? '';
    $store_id = $_GET['store_id'] ?? null;
    $location_type = $_GET['location_type'] ?? null;
    
    if (empty($company_id)) {
        throw new Exception('Company ID is required');
    }
    
    // Query cash locations
    $query = [
        'select' => 'cash_location_id,location_name,location_type,currency_code,bank_name,location_info,store_id',
        'company_id' => 'eq.' . $company_id,
        'order' => 'location_name.asc'
    ];
    
    // Filter by store_id if provided
    if ($store_id) {
        $query['store_id'] = 'eq.' . $store_id;
    } else {
        // If no store is selected, only show company-level locations (store_id is null)
        $query['store_id'] = 'is.null';
    }
    
    if ($location_type) {
        $query['location_type'] = 'eq.' . $location_type;
    }
    
    $response = $supabase->query('cash_locations', $query);
    
    if ($response) {
        $cash_locations = $response ?? [];
        
        // Format response for frontend
        $formatted_locations = [];
        foreach ($cash_locations as $location) {
            $formatted_locations[] = [
                'cash_location_id' => $location['cash_location_id'],
                'location_name' => $location['location_name'],
                'location_type' => $location['location_type'],
                'currency_code' => $location['currency_code'] ?? 'KRW',
                'bank_name' => $location['bank_name'] ?? '',
                'location_info' => $location['location_info'] ?? '',
                'display_name' => $location['location_name'] . 
                    ($location['bank_name'] ? ' (' . $location['bank_name'] . ')' : '') .
                    ($location['location_type'] ? ' [' . ucfirst($location['location_type']) . ']' : '')
            ];
        }
        
        echo json_encode([
            'success' => true,
            'data' => $formatted_locations,
            'total_count' => count($formatted_locations)
        ]);
        
    } else {
        throw new Exception('Failed to fetch cash locations');
    }
    
} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'data' => []
    ]);
}
?>
