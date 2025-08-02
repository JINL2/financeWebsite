<?php
/**
 * Cash Control API
 */
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once 'config.php';
require_once 'db.php';

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    switch ($action) {
        case 'get_cash_locations':
            getCashLocations();
            break;
        default:
            throw new Exception('Invalid action');
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

function getCashLocations() {
    $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? '';
    $store_id = $_POST['store_id'] ?? $_GET['store_id'] ?? '';
    
    if (empty($company_id)) {
        throw new Exception('Company ID is required');
    }
    
    try {
        // Build the query based on filters
        $query = "
            SELECT 
                cash_location_id,
                company_id,
                store_id,
                location_name,
                location_type,
                location_info,
                currency_code,
                bank_account,
                bank_name,
                icon
            FROM cash_locations 
            WHERE company_id = :company_id 
            AND deleted_at IS NULL
        ";
        
        $params = ['company_id' => $company_id];
        
        // Add store filter if specific store is selected
        if (!empty($store_id) && $store_id !== 'all') {
            $query .= " AND store_id = :store_id";
            $params['store_id'] = $store_id;
        }
        
        $query .= " ORDER BY location_type, location_name";
        
        // Use Supabase REST API to query cash_locations
        $db = new SupabaseDB();
        
        // Build conditions for Supabase API
        $conditions = [
            'company_id' => $company_id,
            'deleted_at' => ['operator' => 'is', 'value' => 'null']
        ];
        
        // Add store filter if specific store is selected
        if (!empty($store_id) && $store_id !== 'all') {
            $conditions['store_id'] = $store_id;
        }
        
        $locations = $db->getMany('cash_locations', $conditions, 'location_type,location_name');
        
        if ($locations !== null) {
            
            // Process locations to add proper icons based on location_type
            foreach ($locations as &$location) {
                // Set default icon based on location_type if no custom icon is set
                if (empty($location['icon'])) {
                    switch ($location['location_type']) {
                        case 'bank':
                            $location['icon'] = '🏦';
                            break;
                        case 'vault':
                            $location['icon'] = '🔐';
                            break;
                        case 'cash':
                        case 'cashier':
                        default:
                            $location['icon'] = '💵';
                            break;
                    }
                }
                
                // Map location_type to CSS class for styling
                switch ($location['location_type']) {
                    case 'bank':
                        $location['css_class'] = 'bank';
                        $location['bootstrap_icon'] = 'bi-bank';
                        break;
                    case 'vault':
                        $location['css_class'] = 'vault';
                        $location['bootstrap_icon'] = 'bi-safe';
                        break;
                    case 'cash':
                    case 'cashier':
                        $location['css_class'] = 'cashier';
                        $location['bootstrap_icon'] = 'bi-cash-coin';
                        break;
                    default:
                        $location['css_class'] = 'cashier';
                        $location['bootstrap_icon'] = 'bi-cash-coin';
                        break;
                }
            }
            
            echo json_encode([
                'success' => true,
                'data' => $locations
            ]);
        }
        
    } catch (Exception $e) {
        throw new Exception('Database error: ' . $e->getMessage());
    }
}
?>
