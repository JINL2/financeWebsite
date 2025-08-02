<?php
/**
 * Server-side Excel Export for Employee Salary
 * This endpoint generates CSV files server-side and provides secure download
 */

// Disable warnings and notices for clean JSON output
error_reporting(E_ERROR | E_PARSE);
ini_set('display_errors', 0);

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

require_once '../common/functions.php';
require_once '../common/db.php';

// Initialize Supabase client
$supabaseClient = new SupabaseDB();

try {
    // Get request parameters
    $company_id = $_POST['company_id'] ?? $_GET['company_id'] ?? null;
    $store_ids = $_POST['store_ids'] ?? $_GET['store_ids'] ?? null;
    $year_month = $_POST['year_month'] ?? $_GET['year_month'] ?? null;
    $filename = $_POST['filename'] ?? $_GET['filename'] ?? 'Employee_Salary_Export';

    // Validate required parameters
    if (!$company_id || !$year_month) {
        throw new Exception('Missing required parameters: company_id, year_month');
    }

    // Parse store_ids if it's a JSON string
    if (is_string($store_ids)) {
        $store_ids = json_decode($store_ids, true);
    }

    // If no specific stores, get all stores for the company using REST API
    if (empty($store_ids)) {
        $stores = $supabaseClient->query('stores', [
            'company_id' => 'eq.' . $company_id,
            'select' => 'store_id'
        ]);
        $store_ids = array_column($stores, 'store_id');
    }

    if (empty($store_ids)) {
        throw new Exception('No stores found for the specified company');
    }

    // Get data from v_shift_request using REST API
    $next_month = getNextMonth($year_month);
    
    $params = [
        'store_id' => 'in.(' . implode(',', $store_ids) . ')',
        'request_date' => 'gte.' . $year_month . '-01',
        'request_date' => 'lt.' . $next_month,
        'order' => 'request_date.desc,user_name.asc'
    ];
    
    $data = $supabaseClient->query('v_shift_request', $params);

    if (empty($data)) {
        throw new Exception('No data found for the specified filters');
    }

    // Generate CSV content
    $csv_content = generateCSVContent($data);
    
    // Clean filename for security
    $safe_filename = preg_replace('/[^a-zA-Z0-9\-_\s]/', '', $filename);
    $safe_filename = trim($safe_filename);
    if (empty($safe_filename)) {
        $safe_filename = 'Employee_Salary_Export';
    }
    
    // Create unique filename to prevent conflicts
    $unique_filename = $safe_filename . '_' . date('Y-m-d_H-i-s') . '.csv';
    
    // Create downloads directory if it doesn't exist
    $downloads_dir = '../downloads';
    if (!file_exists($downloads_dir)) {
        mkdir($downloads_dir, 0755, true);
    }
    
    // Save file to server
    $file_path = $downloads_dir . '/' . $unique_filename;
    if (file_put_contents($file_path, $csv_content) === false) {
        throw new Exception('Failed to create file on server');
    }

    // Return success response with download URL
    echo json_encode([
        'success' => true,
        'message' => 'File generated successfully',
        'filename' => $unique_filename,
        'download_url' => 'api/download-file.php?file=' . urlencode($unique_filename),
        'file_size' => filesize($file_path),
        'record_count' => count($data)
    ]);

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Helper function to get next month
 */
function getNextMonth($yearMonth) {
    $parts = explode('-', $yearMonth);
    $year = (int)$parts[0];
    $month = (int)$parts[1];
    
    $next_month = $month === 12 ? 1 : $month + 1;
    $next_year = $month === 12 ? $year + 1 : $year;
    
    return sprintf('%04d-%02d-01', $next_year, $next_month);
}

/**
 * Generate CSV content from data array
 */
function generateCSVContent($data) {
    if (empty($data)) {
        return '';
    }
    
    // Start with BOM for proper UTF-8 encoding
    $csv_content = "\xEF\xBB\xBF";
    
    // Add headers
    $headers = array_keys($data[0]);
    $csv_content .= implode(',', array_map('escapeCsvField', $headers)) . "\n";
    
    // Add data rows
    foreach ($data as $row) {
        $values = array_map(function($value) {
            return escapeCsvField($value);
        }, array_values($row));
        $csv_content .= implode(',', $values) . "\n";
    }
    
    return $csv_content;
}

/**
 * Escape CSV field to handle commas, quotes, and newlines
 */
function escapeCsvField($field) {
    if ($field === null || $field === '') {
        return '';
    }
    
    // Handle arrays by converting to JSON string
    if (is_array($field)) {
        $field = json_encode($field);
    } else {
        $field = (string)$field;
    }
    
    // If field contains comma, quote, or newline, wrap in quotes and escape internal quotes
    if (strpos($field, ',') !== false || strpos($field, '"') !== false || strpos($field, "\n") !== false) {
        $field = '"' . str_replace('"', '""', $field) . '"';
    }
    
    return $field;
}
?>