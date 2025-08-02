<?php
/**
 * Export Employee Salary Data to CSV
 */

// Suppress all PHP errors and warnings to prevent JSON corruption
error_reporting(0);
ini_set('display_errors', 0);

// Clean any previous output
if (ob_get_level()) {
    ob_end_clean();
}
ob_start();

// Set headers for JSON response
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-Requested-With');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    ob_end_clean();
    exit;
}

// Only allow POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    ob_end_flush();
    exit;
}

try {
    // Get parameters
    $company_id = $_POST['company_id'] ?? null;
    $store_ids = isset($_POST['store_ids']) ? json_decode($_POST['store_ids'], true) : [];
    $year_month = $_POST['year_month'] ?? null;
    $filename = $_POST['filename'] ?? 'employee_salary_export';
    
    // Validate required parameters
    if (!$company_id) {
        throw new Exception('Company ID is required');
    }
    
    if (!$year_month) {
        throw new Exception('Year month is required');
    }
    
    // Parse year-month to get request date
    $request_date = $year_month . '-01';
    
    // Initialize Supabase client
    $supabase_url = 'https://atkekzwgukdvucqntryo.supabase.co';
    $supabase_key = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8';
    
    // Prepare RPC call parameters
    $rpc_params = [
        'p_company_id' => $company_id,
        'p_request_date' => $request_date
    ];
    
    // Add store_id if specific store is selected
    if (!empty($store_ids) && count($store_ids) === 1) {
        $rpc_params['p_store_id'] = $store_ids[0];
    } else {
        $rpc_params['p_store_id'] = null;
    }
    
    // Make RPC call to Supabase
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $supabase_url . '/rest/v1/rpc/employee_salary_store',
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'apikey: ' . $supabase_key,
            'Authorization: Bearer ' . $supabase_key
        ],
        CURLOPT_POSTFIELDS => json_encode($rpc_params)
    ]);
    
    $response = curl_exec($ch);
    $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    
    if ($http_code !== 200 || !$response) {
        throw new Exception('Failed to fetch data from Supabase: HTTP ' . $http_code);
    }
    
    $data = json_decode($response, true);
    
    if (!$data || !isset($data['employee']) || !isset($data['detail'])) {
        throw new Exception('Invalid data format received from Supabase');
    }
    
    // Create employee details map
    $employee_details_map = [];
    foreach ($data['detail'] as $detail) {
        $employee_details_map[$detail['user_id']] = $detail;
    }
    
    // Prepare CSV data
    $csv_data = [];
    
    // CSV Headers
    $headers = [
        'Employee Name',
        'Salary Type', 
        'Base Salary',
        'Paid Hours',
        'Late Minutes',
        'Late Deduction',
        'Overtime Minutes', 
        'Overtime Amount',
        'Bonus',
        'Total Pay'
    ];
    
    $csv_data[] = $headers;
    
    // Add data rows
    foreach ($data['employee'] as $employee) {
        if (isset($employee_details_map[$employee['user_id']])) {
            $detail = $employee_details_map[$employee['user_id']];
            $symbol = $detail['symbol'] ?? '';
            
            $row = [
                $employee['user_name'],
                $detail['salary_type'],
                $symbol . number_format($detail['salary_amount']),
                number_format($detail['paid_hours'], 2) . 'h',
                $detail['late_minutes'] . 'min',
                $symbol . number_format($detail['late_deduct_amount']),
                $detail['overtime_minutes'] . 'min',
                $symbol . number_format($detail['overtime_amount']),
                $symbol . number_format($detail['bonus_amount']),
                $symbol . number_format($detail['total_pay_with_bonus'])
            ];
            
            $csv_data[] = $row;
        }
    }
    
    // Generate CSV content
    $csv_content = '';
    
    // Add BOM for proper UTF-8 encoding in Excel
    $csv_content .= "\xEF\xBB\xBF";
    
    foreach ($csv_data as $row) {
        $escaped_row = [];
        foreach ($row as $field) {
            // Escape fields that contain commas, quotes, or newlines
            if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
                $field = '"' . str_replace('"', '""', $field) . '"';
            }
            $escaped_row[] = $field;
        }
        $csv_content .= implode(',', $escaped_row) . "\n";
    }
    
    // Ensure filename has .csv extension and is safe for download
    $safe_filename = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $filename);
    $final_filename = $safe_filename . '.csv';
    
    // Additional safety - ensure filename is not too long
    if (strlen($final_filename) > 100) {
        $final_filename = substr($safe_filename, 0, 95) . '.csv';
    }
    
    // Direct download approach - no temporary files
    ob_clean();
    
    // Set headers for CSV download - Enhanced filename handling
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $final_filename . '"; filename*=UTF-8\'\''. rawurlencode($final_filename));
    header('Content-Description: File Transfer');
    header('Content-Transfer-Encoding: binary');
    header('Content-Length: ' . strlen($csv_content));
    header('Cache-Control: no-cache, no-store, must-revalidate');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    // Output CSV content directly
    echo $csv_content;
    ob_end_flush();
    exit;
    
} catch (Exception $e) {
    http_response_code(400);
    ob_clean();
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
    ob_end_flush();
}
?>