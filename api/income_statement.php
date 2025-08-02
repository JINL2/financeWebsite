<?php
/**
 * Income Statement API using rpc_get_income_statement
 * Based on plan_income.md specifications
 */

require_once '../common/auth.php';
require_once '../common/functions.php';

// Set JSON response header
header('Content-Type: application/json');

// Get parameters
$user_id = $_GET['user_id'] ?? null;
$company_id = $_GET['company_id'] ?? null;
$store_id = $_GET['store_id'] ?? null;
$year = $_GET['year'] ?? date('Y');
$month = $_GET['month'] ?? date('n');
$period = $_GET['period'] ?? null;

// If period not specified, create from year and month
if (!$period) {
    $period = sprintf('%04d-%02d-01', $year, $month);
}

// Authentication check
if (!$user_id || !$company_id) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => 'Missing required parameters: user_id and company_id'
    ]);
    exit;
}

// Check user access
if (!checkAuth($user_id, $company_id)) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'error' => 'Access denied'
    ]);
    exit;
}

try {
    // Call the RPC function
    $params = [
        'p_company_id' => $company_id,
        'p_period' => $period
    ];
    
    if ($store_id) {
        $params['p_store_id'] = $store_id;
    } else {
        $params['p_store_id'] = null;
    }
    
    $result = callSupabaseRPC('rpc_get_income_statement', $params);
    
    if (isset($result['error'])) {
        throw new Exception($result['error']['message'] ?? 'RPC call failed');
    }
    
    // Process the data to match the expected format
    $processedData = processIncomeStatementData($result);
    
    // Calculate summary totals
    $summary = calculateSummary($processedData);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'period' => $period,
        'company_id' => $company_id,
        'store_id' => $store_id,
        'data' => $processedData,
        'summary' => $summary,
        'raw_data' => $result // For debugging
    ]);
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Process income statement data from RPC result
 */
function processIncomeStatementData($rpcResult) {
    if (!is_array($rpcResult)) {
        return [];
    }
    
    $structured = [
        'sales_revenue' => [
            'category' => 'Revenue',
            'items' => [],
            'subtotal' => 0
        ],
        'cogs' => [
            'category' => 'Cost of Goods Sold',
            'items' => [],
            'subtotal' => 0
        ],
        'operating_expense' => [
            'category' => 'Operating Expenses',
            'items' => [],
            'subtotal' => 0
        ],
        'tax' => [
            'category' => 'Tax',
            'items' => [],
            'subtotal' => 0
        ],
        'comprehensive_income' => [
            'category' => 'Other Comprehensive Income',
            'items' => [],
            'subtotal' => 0
        ]
    ];
    
    // Group data by category
    foreach ($rpcResult as $row) {
        $category = $row['statement_detail_category'] ?? 'other';
        
        if (!isset($structured[$category])) {
            $structured[$category] = [
                'category' => ucfirst(str_replace('_', ' ', $category)),
                'items' => [],
                'subtotal' => 0
            ];
        }
        
        if ($row['is_subtotal']) {
            $structured[$category]['subtotal'] = floatval($row['amount']);
            $structured[$category]['subtotal_label'] = $row['account_name'];
        } else {
            $structured[$category]['items'][] = [
                'account_name' => $row['account_name'],
                'amount' => floatval($row['amount'])
            ];
        }
    }
    
    return $structured;
}

/**
 * Calculate summary totals
 */
function calculateSummary($data) {
    $totalRevenue = abs($data['sales_revenue']['subtotal'] ?? 0);
    $totalCost = abs($data['cogs']['subtotal'] ?? 0);
    $totalOperatingExpense = abs($data['operating_expense']['subtotal'] ?? 0);
    $totalTax = abs($data['tax']['subtotal'] ?? 0);
    $otherIncome = $data['comprehensive_income']['subtotal'] ?? 0;
    
    $grossProfit = $totalRevenue - $totalCost;
    $netIncomeBeforeTax = $grossProfit - $totalOperatingExpense;
    $netIncome = $netIncomeBeforeTax - $totalTax;
    $comprehensiveIncome = $netIncome + $otherIncome;
    
    $grossProfitMargin = $totalRevenue > 0 ? round(($grossProfit / $totalRevenue) * 100, 2) : 0;
    $netProfitMargin = $totalRevenue > 0 ? round(($netIncome / $totalRevenue) * 100, 2) : 0;
    
    return [
        'total_revenue' => $totalRevenue,
        'total_cost' => $totalCost,
        'gross_profit' => $grossProfit,
        'gross_profit_margin' => $grossProfitMargin,
        'total_operating_expense' => $totalOperatingExpense,
        'net_income_before_tax' => $netIncomeBeforeTax,
        'total_tax' => $totalTax,
        'net_income' => $netIncome,
        'net_profit_margin' => $netProfitMargin,
        'other_comprehensive_income' => $otherIncome,
        'comprehensive_income' => $comprehensiveIncome
    ];
}

/**
 * Call Supabase RPC function
 */
function callSupabaseRPC($function, $params) {
    $url = SUPABASE_URL . '/rest/v1/rpc/' . $function;
    
    $headers = [
        'apikey: ' . SUPABASE_ANON_KEY,
        'Authorization: Bearer ' . SUPABASE_ANON_KEY,
        'Content-Type: application/json',
        'Prefer: return=representation'
    ];
    
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($params));
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('CURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        throw new Exception('API Error: ' . ($error['message'] ?? 'Unknown error'));
    }
    
    return json_decode($response, true);
}
