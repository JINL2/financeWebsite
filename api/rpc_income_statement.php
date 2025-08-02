<?php
/**
 * RPC Income Statement API
 * Handles Supabase RPC function calls for income statement data
 */

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit();
}

// Get request body
$input = file_get_contents('php://input');
$request = json_decode($input, true);

if (!$request || !isset($request['action']) || !isset($request['params'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid request format']);
    exit();
}

$action = $request['action'];
$params = $request['params'];

// Supabase configuration
$supabaseUrl = 'https://atkekzwgukdvucqntryo.supabase.co';
$supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8';

try {
    switch ($action) {
        case 'get_income_statement_v2':
            $result = callRPCFunction('rpc_get_income_statement_v2', $params);
            break;
            
        case 'get_income_statement':
            $result = callRPCFunction('get_income_statement', $params);
            break;
            
        case 'get_income_statement_monthly_comparison':
            $result = callRPCFunction('rpc_get_income_statement_monthly_comparison', $params);
            break;
            
        default:
            echo json_encode(['success' => false, 'error' => 'Unknown action: ' . $action]);
            exit();
    }
    
    echo json_encode($result);
    
} catch (Exception $e) {
    error_log('RPC Income Statement API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Internal server error: ' . $e->getMessage()
    ]);
}

/**
 * Call Supabase RPC function
 */
function callRPCFunction($functionName, $params) {
    global $supabaseUrl, $supabaseKey;
    
    $url = $supabaseUrl . '/rest/v1/rpc/' . $functionName;
    
    // Prepare headers
    $headers = [
        'apikey: ' . $supabaseKey,
        'Authorization: Bearer ' . $supabaseKey,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    // Initialize cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    // Execute request
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
    
    // Check for cURL errors
    if ($error) {
        throw new Exception('cURL Error: ' . $error);
    }
    
    // Check HTTP status
    if ($httpCode !== 200) {
        throw new Exception('HTTP Error ' . $httpCode . ': ' . $response);
    }
    
    // Decode response
    $data = json_decode($response, true);
    
    if (json_last_error() !== JSON_ERROR_NONE) {
        throw new Exception('JSON Decode Error: ' . json_last_error_msg());
    }
    
    // Log successful call
    error_log("RPC Function '{$functionName}' called successfully. Response count: " . (is_array($data) ? count($data) : 'N/A'));
    
    return [
        'success' => true,
        'data' => $data,
        'function' => $functionName,
        'params' => $params,
        'count' => is_array($data) ? count($data) : null
    ];
}
?>
