<?php
/**
 * Get Counterparties API - Real Supabase Integration
 * Fetches all available counterparties/companies for the current company
 * Used in Enhanced Debt Modal for selecting debt counterparties
 */

// Enable CORS for API requests
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');
header('Content-Type: application/json');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Get authentication parameters
$user_id = $_GET['user_id'] ?? '';

// Check if user is authenticated
if (empty($user_id)) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - user_id parameter required']);
    exit;
}

try {
    // Get company ID from request
    $company_id = $_GET['company_id'] ?? null;
    
    if (!$company_id) {
        throw new Exception('Company ID is required');
    }

    // Load Supabase configuration
    require_once '../common/config.php';
    
    // Check Supabase configuration
    if (!defined('SUPABASE_URL') || !defined('SUPABASE_ANON_KEY')) {
        throw new Exception('Supabase configuration is missing');
    }
    
    // Build Supabase API URL for counterparties
    $base_url = SUPABASE_URL . '/rest/v1/counterparties';
    $filters = [];
    $filters[] = "company_id=eq.{$company_id}";
    $filters[] = "is_deleted=eq.false";
    
    // Add select fields
    $select_fields = "counterparty_id,name,type,email,phone,address,is_internal,linked_company_id";
    $filters[] = "select={$select_fields}";
    
    $query_string = implode('&', $filters);
    $full_url = $base_url . '?' . $query_string;
    
    // HTTP request context
    $context = stream_context_create([
        'http' => [
            'method' => 'GET',
            'header' => [
                'apikey: ' . SUPABASE_ANON_KEY,
                'Authorization: Bearer ' . SUPABASE_ANON_KEY,
                'Content-Type: application/json'
            ]
        ]
    ]);
    
    // Make API request to Supabase
    $response = file_get_contents($full_url, false, $context);
    
    if ($response === false) {
        throw new Exception('Failed to fetch counterparties from Supabase');
    }
    
    $counterparties = json_decode($response, true);
    
    if ($counterparties === null) {
        throw new Exception('Invalid JSON response from Supabase');
    }
    
    // Format counterparties for frontend
    $formatted_counterparties = [];
    
    foreach ($counterparties as $counterparty) {
        $display_name = $counterparty['name'];
        
        // Add type indicator for clarity
        if (!empty($counterparty['type'])) {
            $display_name .= ' [' . ucfirst($counterparty['type']) . ']';
        }
        
        // Add (Internal) indicator for internal counterparties
        if ($counterparty['is_internal']) {
            $display_name .= ' (Internal)';
        }
        
        $formatted_counterparties[] = [
            'counterparty_id' => $counterparty['counterparty_id'],
            'counterparty_name' => $display_name,
            'name' => $counterparty['name'],
            'type' => $counterparty['type'],
            'is_internal' => $counterparty['is_internal'],
            'linked_company_id' => $counterparty['linked_company_id'],
            'contact_info' => [
                'email' => $counterparty['email'],
                'phone' => $counterparty['phone'],
                'address' => $counterparty['address']
            ]
        ];
    }
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'data' => $formatted_counterparties,
        'count' => count($formatted_counterparties),
        'message' => 'Counterparties loaded successfully from database'
    ]);

} catch (Exception $e) {
    // Log error for debugging
    error_log('Get Counterparties Error: ' . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to load counterparties: ' . $e->getMessage(),
        'data' => []
    ]);
}
?>
