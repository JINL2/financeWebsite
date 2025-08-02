<?php
/**
 * Supabase API Wrapper for RPC calls
 * Handles calls to get_user_companies_and_stores and other RPC functions
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

require_once 'config.php';
require_once 'db.php';
require_once 'functions.php';

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

try {
    $action = $_POST['action'] ?? $_GET['action'] ?? '';
    
    if (empty($action)) {
        throw new Exception('Action parameter is required');
    }
    
    $db = new SupabaseDB();
    
    switch ($action) {
        case 'get_user_companies_and_stores':
            $user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
            
            if (empty($user_id)) {
                throw new Exception('user_id parameter is required');
            }
            
            // Validate UUID format
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $user_id)) {
                throw new Exception('Invalid user_id format');
            }
            
            // Call the RPC function
            $result = $db->callRPC('get_user_companies_and_stores', [
                'p_user_id' => $user_id
            ]);
            
            // Check if result is valid
            if ($result === null || $result === false) {
                throw new Exception('No data returned from RPC function');
            }
            
            // The RPC returns a single JSON object, not an array
            if (is_array($result) && count($result) === 1 && isset($result[0])) {
                $data = $result[0];
            } else {
                $data = $result;
            }
            
            echo json_encode([
                'success' => true,
                'data' => $data,
                'message' => 'User companies and stores loaded successfully'
            ]);
            break;
            
        case 'get_user_info':
            $user_id = $_POST['user_id'] ?? $_GET['user_id'] ?? '';
            
            if (empty($user_id)) {
                throw new Exception('user_id parameter is required');
            }
            
            // Get user info from users table
            $user = $db->query('users', [
                'user_id' => 'eq.' . $user_id,
                'select' => 'user_id,first_name,last_name,email,profile_image',
                'limit' => 1
            ]);
            
            if (empty($user)) {
                throw new Exception('User not found');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $user[0],
                'message' => 'User info loaded successfully'
            ]);
            break;
            
        case 'get_balance_sheet':
            $company_id = $_POST['p_company_id'] ?? $_GET['p_company_id'] ?? '';
            $start_date = $_POST['p_start_date'] ?? $_GET['p_start_date'] ?? '';
            $end_date = $_POST['p_end_date'] ?? $_GET['p_end_date'] ?? '';
            $store_id = $_POST['p_store_id'] ?? $_GET['p_store_id'] ?? null;
            
            if (empty($company_id)) {
                throw new Exception('p_company_id parameter is required');
            }
            
            if (empty($start_date)) {
                throw new Exception('p_start_date parameter is required');
            }
            
            if (empty($end_date)) {
                throw new Exception('p_end_date parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            // Validate store_id if provided
            if (!empty($store_id) && !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $store_id)) {
                throw new Exception('Invalid store_id format');
            }
            
            // Convert empty store_id to null
            if (empty($store_id)) {
                $store_id = null;
            }
            
            // Call the RPC function
            $result = $db->callRPC('get_balance_sheet', [
                'p_company_id' => $company_id,
                'p_start_date' => $start_date,
                'p_end_date' => $end_date,
                'p_store_id' => $store_id
            ]);
            
            // Check if result is valid
            if ($result === null || $result === false) {
                throw new Exception('No data returned from RPC function');
            }
            
            // The RPC returns a single JSON object, not an array
            if (is_array($result) && count($result) === 1 && isset($result[0])) {
                $data = $result[0];
            } else {
                $data = $result;
            }
            
            echo json_encode($data);
            break;
            
        case 'get_income_statement':
            $company_id = $_POST['p_company_id'] ?? $_GET['p_company_id'] ?? '';
            $start_date = $_POST['p_start_date'] ?? $_GET['p_start_date'] ?? '';
            $end_date = $_POST['p_end_date'] ?? $_GET['p_end_date'] ?? '';
            $store_id = $_POST['p_store_id'] ?? $_GET['p_store_id'] ?? null;
            
            // Validation
            if (empty($company_id)) {
                throw new Exception('p_company_id parameter is required');
            }
            
            if (empty($start_date)) {
                throw new Exception('p_start_date parameter is required');
            }
            
            if (empty($end_date)) {
                throw new Exception('p_end_date parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            // Validate date format (YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                throw new Exception('Invalid date format. Use YYYY-MM-DD');
            }
            
            // Validate store_id if provided
            if (!empty($store_id) && !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $store_id)) {
                throw new Exception('Invalid store_id format');
            }
            
            // Convert empty store_id to null
            if (empty($store_id)) {
                $store_id = null;
            }
            
            // Call the RPC function
            $result = $db->callRPC('get_income_statement', [
                'p_company_id' => $company_id,
                'p_start_date' => $start_date,
                'p_end_date' => $end_date,
                'p_store_id' => $store_id
            ]);
            
            // Check if result is valid
            if ($result === null || $result === false) {
                throw new Exception('No data returned from RPC function');
            }
            
            // The RPC returns a single JSON object, not an array
            if (is_array($result) && count($result) === 1 && isset($result[0])) {
                $data = $result[0];
            } else {
                $data = $result;
            }
            
            echo json_encode($data);
            break;
            
        case 'get_income_statement_v2':
            $company_id = $_POST['p_company_id'] ?? $_GET['p_company_id'] ?? '';
            $start_date = $_POST['p_start_date'] ?? $_GET['p_start_date'] ?? '';
            $end_date = $_POST['p_end_date'] ?? $_GET['p_end_date'] ?? '';
            $store_id = $_POST['p_store_id'] ?? $_GET['p_store_id'] ?? null;
            
            // Validation
            if (empty($company_id)) {
                throw new Exception('p_company_id parameter is required');
            }
            
            if (empty($start_date)) {
                throw new Exception('p_start_date parameter is required');
            }
            
            if (empty($end_date)) {
                throw new Exception('p_end_date parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            // Validate date format (YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                throw new Exception('Invalid date format. Use YYYY-MM-DD');
            }
            
            // Validate store_id if provided
            if (!empty($store_id) && !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $store_id)) {
                throw new Exception('Invalid store_id format');
            }
            
            // Convert empty store_id to null
            if (empty($store_id)) {
                $store_id = null;
            }
            
            // Call the RPC function
            $result = $db->callRPC('get_income_statement_v2', [
                'p_company_id' => $company_id,
                'p_start_date' => $start_date,
                'p_end_date' => $end_date,
                'p_store_id' => $store_id
            ]);
            
            // Check if result is valid
            if ($result === null || $result === false) {
                throw new Exception('No data returned from RPC function');
            }
            
            // The RPC returns a single JSON object, not an array
            if (is_array($result) && count($result) === 1 && isset($result[0])) {
                $data = $result[0];
            } else {
                $data = $result;
            }
            
            echo json_encode($data);
            break;
            
        case 'get_income_statement_monthly':
            $company_id = $_POST['p_company_id'] ?? $_GET['p_company_id'] ?? '';
            $start_date = $_POST['p_start_date'] ?? $_GET['p_start_date'] ?? '';
            $end_date = $_POST['p_end_date'] ?? $_GET['p_end_date'] ?? '';
            $store_id = $_POST['p_store_id'] ?? $_GET['p_store_id'] ?? null;
            
            // Validation
            if (empty($company_id)) {
                throw new Exception('p_company_id parameter is required');
            }
            
            if (empty($start_date)) {
                throw new Exception('p_start_date parameter is required');
            }
            
            if (empty($end_date)) {
                throw new Exception('p_end_date parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            // Validate date format (YYYY-MM-DD)
            if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $start_date) || !preg_match('/^\d{4}-\d{2}-\d{2}$/', $end_date)) {
                throw new Exception('Invalid date format. Use YYYY-MM-DD');
            }
            
            // Validate store_id if provided
            if (!empty($store_id) && !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $store_id)) {
                throw new Exception('Invalid store_id format');
            }
            
            // Convert empty store_id to null
            if (empty($store_id)) {
                $store_id = null;
            }
            
            // Call the RPC function
            $result = $db->callRPC('get_income_statement_monthly', [
                'p_company_id' => $company_id,
                'p_start_date' => $start_date,
                'p_end_date' => $end_date,
                'p_store_id' => $store_id
            ]);
            
            // Check if result is valid
            if ($result === null || $result === false) {
                throw new Exception('No data returned from RPC function');
            }
            
            // The RPC returns a single JSON object
            if (is_array($result) && count($result) === 1 && isset($result[0]['get_income_statement_monthly'])) {
                $data = $result[0]['get_income_statement_monthly'];
            } else {
                $data = $result;
            }
            
            echo json_encode($data);
            break;
            
        case 'get_company_currency_symbol':
            $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? '';
            
            if (empty($company_id)) {
                throw new Exception('company_id parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            // Get company's base_currency_id from companies table
            $company = $db->query('companies', [
                'company_id' => 'eq.' . $company_id,
                'select' => 'base_currency_id',
                'limit' => 1
            ]);
            
            if (empty($company) || empty($company[0]['base_currency_id'])) {
                throw new Exception('Company not found or no currency set');
            }
            
            $currency_id = $company[0]['base_currency_id'];
            
            // Get currency symbol from currency_types table
            $currency = $db->query('currency_types', [
                'currency_id' => 'eq.' . $currency_id,
                'select' => 'symbol',
                'limit' => 1
            ]);
            
            if (empty($currency) || empty($currency[0]['symbol'])) {
                throw new Exception('Currency not found');
            }
            
            echo json_encode([
                'success' => true,
                'symbol' => $currency[0]['symbol'],
                'message' => 'Currency symbol loaded successfully'
            ]);
            break;
            
        case 'get_cash_balance_amounts':
            $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? '';
            $store_id = $_POST['store_id'] ?? $_GET['store_id'] ?? null;
            
            if (empty($company_id)) {
                throw new Exception('company_id parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            // Validate store_id if provided
            if (!empty($store_id) && !preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $store_id)) {
                throw new Exception('Invalid store_id format');
            }
            
            // Prepare parameters for RPC call
            $rpc_params = [
                'p_company_id' => $company_id
            ];
            
            // Add store_id if provided
            if (!empty($store_id)) {
                $rpc_params['p_store_id'] = $store_id;
            }
            
            // Call the RPC function
            $result = $db->callRPC('get_cash_balance_amounts', $rpc_params);
            
            // Check if result is valid
            if ($result === null || $result === false) {
                throw new Exception('No data returned from RPC function');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'message' => 'Balance amounts loaded successfully'
            ]);
            break;
            
        case 'get_cash_locations_for_store':
            $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? '';
            $store_id = $_POST['store_id'] ?? $_GET['store_id'] ?? null;
            $is_headquarters = $_POST['is_headquarters'] ?? $_GET['is_headquarters'] ?? 'false';
            
            if (empty($company_id)) {
                throw new Exception('company_id parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            // Prepare query parameters
            $query_params = [
                'company_id' => 'eq.' . $company_id,
                'select' => 'cash_location_id,location_name,location_type',
                'order' => 'location_name.asc'
            ];
            
            // Handle store filtering
            if ($is_headquarters === 'true') {
                // For headquarters, get locations where store_id is null
                $query_params['store_id'] = 'is.null';
            } else if (!empty($store_id)) {
                // Validate store_id format
                if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $store_id)) {
                    throw new Exception('Invalid store_id format');
                }
                $query_params['store_id'] = 'eq.' . $store_id;
            } else {
                // If no specific store and not headquarters, return empty result
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'message' => 'No store specified'
                ]);
                break;
            }
            
            // Get cash locations from cash_locations table
            $cash_locations = $db->query('cash_locations', $query_params);
            
            if ($cash_locations === null || $cash_locations === false) {
                throw new Exception('Failed to fetch cash locations');
            }
            
            echo json_encode([
                'success' => true,
                'data' => $cash_locations,
                'message' => 'Cash locations loaded successfully'
            ]);
            break;
            
        case 'get_company_currencies':
            $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? '';
            
            if (empty($company_id)) {
                throw new Exception('company_id parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            try {
                // Get company currencies using REST API
                $company_currencies = $db->query('company_currency', [
                    'company_id' => 'eq.' . $company_id,
                    'select' => 'currency_id,currency_types(currency_id,currency_code,currency_name,symbol)'
                ]);
                
                if (!$company_currencies) {
                    $company_currencies = [];
                }
                
                // Transform the data structure
                $result = [];
                foreach ($company_currencies as $cc) {
                    $currency_info = $cc['currency_types'];
                    if ($currency_info) {
                        $result[] = [
                            'currency_id' => $currency_info['currency_id'],
                            'currency_code' => $currency_info['currency_code'],
                            'currency_name' => $currency_info['currency_name'],
                            'symbol' => $currency_info['symbol']
                        ];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Company currencies loaded successfully'
                ]);
            } catch (Exception $e) {
                error_log('Currency API Error: ' . $e->getMessage());
                throw new Exception('Failed to load currencies: ' . $e->getMessage());
            }
            break;
        
        case 'get_company_currencies_with_denominations':
            $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? '';
            
            if (empty($company_id)) {
                throw new Exception('company_id parameter is required');
            }
            
            // Validate UUID format for company_id
            if (!preg_match('/^[a-f0-9]{8}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{4}-[a-f0-9]{12}$/i', $company_id)) {
                throw new Exception('Invalid company_id format');
            }
            
            try {
                // Get company currencies using REST API instead of raw SQL
                $company_currencies = $db->query('company_currency', [
                    'company_id' => 'eq.' . $company_id,
                    'select' => 'currency_id,currency_types(currency_id,currency_code,currency_name,symbol)'
                ]);
                
                if (!$company_currencies) {
                    $company_currencies = [];
                }
                
                // Transform the data structure
                $result = [];
                foreach ($company_currencies as $cc) {
                    $currency_info = $cc['currency_types'];
                    if ($currency_info) {
                        // Get denominations for this currency
                        $denominations = $db->query('currency_denominations', [
                            'company_id' => 'eq.' . $company_id,
                            'currency_id' => 'eq.' . $currency_info['currency_id'],
                            'select' => 'denomination_id,value',
                            'order' => 'value.desc'
                        ]);
                        
                        $result[] = [
                            'currency_id' => $currency_info['currency_id'],
                            'currency_code' => $currency_info['currency_code'],
                            'currency_name' => $currency_info['currency_name'],
                            'symbol' => $currency_info['symbol'],
                            'denominations' => $denominations ?: []
                        ];
                    }
                }
                
                echo json_encode([
                    'success' => true,
                    'data' => $result,
                    'message' => 'Company currencies and denominations loaded successfully'
                ]);
            } catch (Exception $e) {
                error_log('Currency API Error: ' . $e->getMessage());
                throw new Exception('Failed to load currencies: ' . $e->getMessage());
            }
            break;
            
        case 'call_rpc':
            $function_name = $_POST['function_name'] ?? $_GET['function_name'] ?? '';
            $parameters = $_POST['parameters'] ?? $_GET['parameters'] ?? '';
            
            if (empty($function_name)) {
                throw new Exception('function_name parameter is required');
            }
            
            if (empty($parameters)) {
                throw new Exception('parameters parameter is required');
            }
            
            // Decode parameters from JSON
            $params_array = json_decode($parameters, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Invalid JSON in parameters: ' . json_last_error_msg());
            }
            
            // Call the RPC function
            $result = $db->callRPC($function_name, $params_array);
            
            // List of void functions that return null but should be considered successful
            $void_functions = [
                'insert_cashier_amount_lines',
                'insert_journal_with_everything',
                // Add other void functions here as needed
            ];
            
            // Check if result is valid (special handling for void functions)
            if ($result === null || $result === false) {
                if (in_array($function_name, $void_functions)) {
                    // For void functions, null is expected and means success
                    echo json_encode([
                        'success' => true,
                        'data' => null,
                        'message' => 'RPC function executed successfully'
                    ]);
                } else {
                    throw new Exception('No data returned from RPC function');
                }
            } else {
                // For functions that return JSON directly (like bank_amount_insert_v2), 
                // check if it's already a structured response
                if (is_array($result) && isset($result[0]) && is_array($result[0]) && isset($result[0]['success'])) {
                    // Function returned a JSON response, extract it
                    echo json_encode($result[0]);
                } else {
                    // Normal data response
                    echo json_encode([
                        'success' => true,
                        'data' => $result,
                        'message' => 'RPC function executed successfully'
                    ]);
                }
            }
            break;
            
        default:
            throw new Exception('Unknown action: ' . $action);
    }
    
} catch (Exception $e) {
    error_log('Supabase API Error: ' . $e->getMessage());
    
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'message' => 'Failed to process request'
    ]);
}
?>
