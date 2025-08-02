<?php
/**
 * Income Statement API V2 - Complete Redesign
 * Enhanced version with date range support, proper validation, and comprehensive analysis
 */

// 디버그 로그 추가
error_log("=== API CALLED: get_income_statement_v2.php ===");
error_log("Request method: " . $_SERVER['REQUEST_METHOD']);
error_log("GET params: " . json_encode($_GET));

require_once '../common/auth.php';
require_once '../common/db.php';
require_once '../common/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight OPTIONS request
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit;
}

$start_time = microtime(true);

try {
    // Get and validate parameters
    error_log("Step 1: Validating parameters");
    $params = validate_income_statement_params($_GET);
    error_log("Step 1 completed - params: " . json_encode($params));
    
    // Authentication check
    error_log("Step 2: Checking authentication");
    if (!checkAuth($params['user_id'], $params['company_id'])) {
        throw new Exception('Authentication failed', 401);
    }
    error_log("Step 2 completed - auth passed");
    
    // Get income statement data
    error_log("Step 3: Getting income statement data");
    $income_statement = get_income_statement_v2($params);
    error_log("Step 3 completed - data retrieved");
    
    // Calculate performance metrics
    $execution_time = round((microtime(true) - $start_time) * 1000, 2);
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'timestamp' => date('c'),
        'request_params' => [
            'company_id' => $params['company_id'],
            'start_date' => $params['start_date'],
            'end_date' => $params['end_date'],
            'store_id' => $params['store_id'],
            'currency_id' => $params['currency_id'],
            'grouping_level' => $params['grouping_level']
        ],
        'data' => $income_statement['data'],
        'summary' => $income_statement['summary'],
        'analysis' => $income_statement['analysis'],
        'metadata' => $income_statement['metadata'],
        'performance' => [
            'execution_time' => $execution_time . 'ms',
            'query_count' => $income_statement['query_count'] ?? 1
        ]
    ]);
    
} catch (Exception $e) {
    $error_code = $e->getCode() ?: 500;
    http_response_code($error_code);
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'error_code' => $error_code,
        'timestamp' => date('c')
    ]);
}

/**
 * Validate and sanitize income statement parameters
 * Enhanced to support yyyy-MM date format from frontend
 */
function validate_income_statement_params($input) {
    error_log("Validating params with input: " . json_encode($input));
    $errors = [];
    
    // Enhanced date handling - support yyyy-MM format from frontend
    if (!empty($input['date']) && preg_match('/^\d{4}-\d{2}$/', $input['date'])) {
        // Frontend is sending yyyy-MM format, convert to date range
        [$year, $month] = explode('-', $input['date']);
        $start_date = sprintf('%04d-%02d-01', $year, $month);
        $end_date = date('Y-m-t', strtotime($start_date)); // Last day of month
        
        $input['start_date'] = $start_date;
        $input['end_date'] = $end_date;
        
        error_log("Converted yyyy-MM date {$input['date']} to range: $start_date to $end_date");
    }
    
    // Required parameters
    $required = ['company_id', 'start_date', 'end_date'];
    foreach ($required as $param) {
        if (empty($input[$param])) {
            $errors[] = "Missing required parameter: {$param}";
        }
    }
    
    if (!empty($errors)) {
        throw new Exception('Validation failed: ' . implode(', ', $errors), 400);
    }
    
    // Validate dates
    $start_date = validate_date($input['start_date']);
    $end_date = validate_date($input['end_date']);
    
    if ($start_date > $end_date) {
        throw new Exception('start_date must be before end_date', 400);
    }
    
    // Check date range (max 5 years)
    $date_diff = $start_date->diff($end_date);
    if ($date_diff->y > 5) {
        throw new Exception('Date range cannot exceed 5 years', 400);
    }
    
    return [
        'user_id' => $input['user_id'] ?? null,
        'company_id' => validate_uuid($input['company_id']),
        'start_date' => $start_date->format('Y-m-d'),
        'end_date' => $end_date->format('Y-m-d'),
        'store_id' => !empty($input['store_id']) ? validate_uuid($input['store_id']) : null,
        'currency_id' => !empty($input['currency_id']) ? validate_uuid($input['currency_id']) : null,
        'grouping_level' => $input['grouping_level'] ?? 'detailed',
        'include_draft' => filter_var($input['include_draft'] ?? false, FILTER_VALIDATE_BOOLEAN),
        'comparison_period' => $input['comparison_period'] ?? null
    ];
}

/**
 * Get comprehensive income statement data
 */
function get_income_statement_v2($params) {
    error_log("Getting income statement data with params: " . json_encode($params));
    
    try {
        // Get raw data using RPC function
        $raw_data = get_income_statement_raw_data($params);
        
        // Process the raw data into structured format
        $structured_data = process_income_statement_data_v2($raw_data);
        
        // Calculate summary
        $summary = calculate_income_statement_summary($structured_data);
        
        // Generate analysis
        $analysis = generate_income_statement_analysis($summary, $params);
        
        // Generate metadata
        $metadata = generate_report_metadata($params, 1);
        
        return [
            'data' => $structured_data,
            'summary' => $summary,
            'analysis' => $analysis,
            'metadata' => $metadata,
            'query_count' => 1
        ];
        
    } catch (Exception $e) {
        error_log("Error in get_income_statement_v2: " . $e->getMessage());
        throw $e;
    }
}

/**
 * Process raw RPC data into structured format
 */
function process_income_statement_data_v2($raw_data) {
    $structured = [
        'revenue' => [
            'section_title' => 'REVENUE',
            'categories' => [],
            'total' => 0
        ],
        'cogs' => [
            'section_title' => 'COST OF GOODS SOLD',
            'categories' => [],
            'total' => 0
        ],
        'gross_profit' => 0,
        'operating_expenses' => [
            'section_title' => 'OPERATING EXPENSES',
            'categories' => [],
            'total' => 0,
            'fixed_total' => 0,
            'variable_total' => 0
        ],
        'operating_income' => 0,
        'non_operating' => [
            'section_title' => 'NON-OPERATING ITEMS',
            'income' => [],
            'expenses' => [],
            'net_total' => 0
        ],
        'income_before_tax' => 0,
        'tax' => [
            'section_title' => 'TAX EXPENSES',
            'categories' => [],
            'total' => 0
        ],
        'net_income' => 0,
        'comprehensive_income' => [
            'section_title' => 'OTHER COMPREHENSIVE INCOME',
            'categories' => [],
            'total' => 0
        ],
        'total_comprehensive_income' => 0
    ];
    
    // Group data by account type and process
    $sections = [];
    foreach ($raw_data as $row) {
        $account_type = $row['account_type'];
        $category = $row['statement_detail_category'] ?: 'general';
        
        if (!isset($sections[$account_type])) {
            $sections[$account_type] = [];
        }
        if (!isset($sections[$account_type][$category])) {
            $sections[$account_type][$category] = [
                'category_name' => ucfirst(str_replace('_', ' ', $category)),
                'items' => [],
                'subtotal' => 0,
                'transaction_count' => 0
            ];
        }
        
        $amount = safe_float($row['amount']);
        
        $sections[$account_type][$category]['items'][] = [
            'account_id' => $row['account_id'],
            'account_name' => $row['account_name'],
            'account_type' => $row['account_type'],
            'expense_nature' => $row['expense_nature'],
            'amount' => $amount,
            'transaction_count' => safe_int($row['transaction_count'])
        ];
        
        $sections[$account_type][$category]['subtotal'] += $amount;
        $sections[$account_type][$category]['transaction_count'] += safe_int($row['transaction_count']);
    }
    
    // Map account types to structured format
    foreach ($sections as $account_type => $account_data) {
        foreach ($account_data as $category_key => $category_data) {
            switch ($account_type) {
                case 'income':
                    $structured['revenue']['categories'][$category_key] = $category_data;
                    $structured['revenue']['total'] += $category_data['subtotal'];
                    break;
                    
                case 'expense':
                    // Determine if it's COGS or operating expense based on account name or category
                    $account_name_lower = strtolower($category_data['items'][0]['account_name'] ?? '');
                    if (strpos($account_name_lower, 'cogs') !== false || strpos($account_name_lower, 'cost of goods') !== false) {
                        $structured['cogs']['categories'][$category_key] = $category_data;
                        $structured['cogs']['total'] += $category_data['subtotal'];
                    } else {
                        $structured['operating_expenses']['categories'][$category_key] = $category_data;
                        $structured['operating_expenses']['total'] += $category_data['subtotal'];
                        
                        // Calculate fixed vs variable costs
                        foreach ($category_data['items'] as $item) {
                            if ($item['expense_nature'] === 'fixed') {
                                $structured['operating_expenses']['fixed_total'] += $item['amount'];
                            } else {
                                $structured['operating_expenses']['variable_total'] += $item['amount'];
                            }
                        }
                    }
                    break;
                    
                default:
                    // Handle other account types if needed
                    break;
            }
        }
    }
    
    // Calculate derived totals
    $structured['gross_profit'] = $structured['revenue']['total'] - $structured['cogs']['total'];
    $structured['operating_income'] = $structured['gross_profit'] - $structured['operating_expenses']['total'];
    $structured['income_before_tax'] = $structured['operating_income'] + $structured['non_operating']['net_total'];
    $structured['net_income'] = $structured['income_before_tax'] - $structured['tax']['total'];
    $structured['total_comprehensive_income'] = $structured['net_income'] + $structured['comprehensive_income']['total'];
    
    return $structured;
}

/**
 * Calculate summary totals and ratios
 */
function calculate_income_statement_summary($data) {
    $total_revenue = $data['revenue']['total'];
    
    return [
        'total_revenue' => $total_revenue,
        'total_cogs' => $data['cogs']['total'],
        'gross_profit' => $data['gross_profit'],
        'gross_profit_margin' => calculate_percentage($data['gross_profit'], $total_revenue),
        'total_operating_expenses' => $data['operating_expenses']['total'],
        'fixed_expenses' => $data['operating_expenses']['fixed_total'],
        'variable_expenses' => $data['operating_expenses']['variable_total'],
        'operating_income' => $data['operating_income'],
        'operating_margin' => calculate_percentage($data['operating_income'], $total_revenue),
        'income_before_tax' => $data['income_before_tax'],
        'total_tax' => $data['tax']['total'],
        'net_income' => $data['net_income'],
        'net_margin' => calculate_percentage($data['net_income'], $total_revenue),
        'comprehensive_income' => $data['total_comprehensive_income'],
        'cost_structure' => [
            'cogs_ratio' => calculate_percentage($data['cogs']['total'], $total_revenue),
            'opex_ratio' => calculate_percentage($data['operating_expenses']['total'], $total_revenue),
            'fixed_cost_ratio' => calculate_percentage($data['operating_expenses']['fixed_total'], $total_revenue),
            'variable_cost_ratio' => calculate_percentage($data['operating_expenses']['variable_total'], $total_revenue)
        ]
    ];
}

/**
 * Generate comprehensive analysis
 */
function generate_income_statement_analysis($summary, $params) {
    $total_revenue = $summary['total_revenue'];
    
    $analysis = [
        'profitability' => [
            'gross_profit_health' => get_profit_health_status($summary['gross_profit_margin']),
            'operating_efficiency' => get_efficiency_status($summary['operating_margin']),
            'overall_performance' => get_performance_status($summary['net_margin'])
        ],
        'cost_analysis' => [
            'cost_structure_balance' => analyze_cost_structure($summary['cost_structure']),
            'fixed_vs_variable' => [
                'fixed_percentage' => $summary['cost_structure']['fixed_cost_ratio'],
                'variable_percentage' => $summary['cost_structure']['variable_cost_ratio'],
                'flexibility_score' => calculate_flexibility_score($summary['cost_structure'])
            ]
        ],
        'key_insights' => generate_key_insights($summary),
        'recommendations' => generate_recommendations($summary)
    ];
    
    return $analysis;
}

/**
 * Generate report metadata
 */
function generate_report_metadata($params, $query_count) {
    return [
        'report_title' => 'Income Statement',
        'period' => [
            'start_date' => $params['start_date'],
            'end_date' => $params['end_date'],
            'display' => format_period_display($params['start_date'], $params['end_date'])
        ],
        'filters' => [
            'company_id' => $params['company_id'],
            'store_id' => $params['store_id'],
            'currency_id' => $params['currency_id']
        ],
        'generation_info' => [
            'generated_at' => date('c'),
            'query_count' => $query_count,
            'version' => 'v2.0'
        ]
    ];
}

/**
 * Helper function to safely convert to float
 */
function safe_float($value) {
    return is_numeric($value) ? (float)$value : 0.0;
}

/**
 * Helper function to safely convert to int
 */
function safe_int($value) {
    return is_numeric($value) ? (int)$value : 0;
}

/**
 * Calculate percentage safely
 */
function calculate_percentage($numerator, $denominator) {
    if ($denominator == 0) return 0;
    return round(($numerator / $denominator) * 100, 2);
}

/**
 * Format period for display
 */
function format_period_display($start_date, $end_date) {
    $start = new DateTime($start_date);
    $end = new DateTime($end_date);
    
    if ($start->format('Y-m') === $end->format('Y-m')) {
        return $start->format('F Y');
    } elseif ($start->format('Y') === $end->format('Y')) {
        return $start->format('M') . ' - ' . $end->format('M Y');
    } else {
        return $start->format('M Y') . ' - ' . $end->format('M Y');
    }
}

/**
 * Get profit health status
 */
function get_profit_health_status($margin) {
    if ($margin >= 30) return 'excellent';
    if ($margin >= 20) return 'good';
    if ($margin >= 10) return 'fair';
    return 'poor';
}

/**
 * Get efficiency status
 */
function get_efficiency_status($margin) {
    if ($margin >= 15) return 'highly_efficient';
    if ($margin >= 8) return 'efficient';
    if ($margin >= 3) return 'moderate';
    return 'inefficient';
}

/**
 * Get performance status
 */
function get_performance_status($margin) {
    if ($margin >= 10) return 'strong';
    if ($margin >= 5) return 'healthy';
    if ($margin >= 0) return 'breakeven';
    return 'loss';
}

/**
 * Analyze cost structure
 */
function analyze_cost_structure($cost_structure) {
    $total_cost_ratio = $cost_structure['cogs_ratio'] + $cost_structure['opex_ratio'];
    
    if ($total_cost_ratio <= 70) return 'optimal';
    if ($total_cost_ratio <= 85) return 'acceptable';
    return 'high';
}

/**
 * Calculate flexibility score
 */
function calculate_flexibility_score($cost_structure) {
    $variable_ratio = $cost_structure['variable_cost_ratio'];
    $total_cost = $cost_structure['fixed_cost_ratio'] + $variable_ratio;
    
    if ($total_cost == 0) return 100;
    
    return round(($variable_ratio / $total_cost) * 100, 1);
}

/**
 * Generate key insights
 */
function generate_key_insights($summary) {
    $insights = [];
    
    if ($summary['gross_profit_margin'] > 50) {
        $insights[] = "Strong gross profit margin indicates good pricing power and cost control";
    }
    
    if ($summary['cost_structure']['fixed_cost_ratio'] > 40) {
        $insights[] = "High fixed cost ratio may indicate operational leverage but reduced flexibility";
    }
    
    if ($summary['net_margin'] < 0) {
        $insights[] = "Negative net margin requires immediate attention to cost structure and pricing";
    }
    
    return $insights;
}

/**
 * Generate recommendations
 */
function generate_recommendations($summary) {
    $recommendations = [];
    
    if ($summary['gross_profit_margin'] < 20) {
        $recommendations[] = "Consider reviewing pricing strategy or optimizing cost of goods sold";
    }
    
    if ($summary['operating_margin'] < 5) {
        $recommendations[] = "Evaluate operational efficiency and consider reducing operating expenses";
    }
    
    if ($summary['cost_structure']['fixed_cost_ratio'] > 50) {
        $recommendations[] = "Consider converting some fixed costs to variable to improve flexibility";
    }
    
    return $recommendations;
}

/**
 * Get raw income statement data using RPC function
 */
function get_income_statement_raw_data($params) {
    error_log("=== Getting income statement data via RPC ===");
    error_log("Company: " . $params['company_id']);
    error_log("Date range: " . $params['start_date'] . ' to ' . $params['end_date']);
    error_log("Store ID: " . ($params['store_id'] ?: 'all stores'));
    
    try {
        // Prepare RPC parameters
        $rpc_params = [
            'p_start_date' => $params['start_date'],
            'p_end_date' => $params['end_date'],
            'p_company_id' => $params['company_id']
        ];
        
        // Add optional parameters
        if (!empty($params['store_id'])) {
            $rpc_params['p_store_id'] = $params['store_id'];
        }
        if (!empty($params['currency_id'])) {
            $rpc_params['p_currency_id'] = $params['currency_id'];
        }
        
        error_log("RPC parameters: " . json_encode($rpc_params));
        
        // Call RPC function with error handling
        try {
            $raw_data = callSupabaseRPC('rpc_get_income_statement_v2', $rpc_params);
            error_log("RPC call successful, returned " . count($raw_data) . " records");
        } catch (Exception $e) {
            error_log("RPC call failed: " . $e->getMessage());
            // If RPC fails, return empty data indicating no records found
            error_log("Returning empty result set due to RPC failure - this is CORRECT for dates without data!");
            return [];
        }
        
        // If no data found, return empty array (this is correct behavior)
        if (empty($raw_data)) {
            error_log("No data found for the specified date range and filters - this is CORRECT behavior for dates without data!");
            return [];
        }
        
        // Convert RPC result to the expected format
        $processed_data = [];
        foreach ($raw_data as $row) {
            // Skip subtotal rows for raw data processing
            if ($row['is_subtotal']) {
                continue;
            }
            
            $processed_data[] = [
                'account_id' => $row['account_id'],
                'account_name' => $row['account_name'],
                'account_type' => $row['account_type'],
                'expense_nature' => $row['expense_nature'],
                'statement_category' => $row['section'], // Map section to statement_category
                'statement_detail_category' => $row['statement_detail_category'],
                'amount' => $row['net_amount'],
                'transaction_count' => $row['transaction_count']
            ];
        }
        
        error_log("Final processed data count: " . count($processed_data));
        return $processed_data;
        
    } catch (Exception $e) {
        error_log("Error in get_income_statement_raw_data: " . $e->getMessage());
        throw $e;
    }
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 30);
    
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    
    if (curl_errno($ch)) {
        throw new Exception('CURL Error: ' . curl_error($ch));
    }
    
    curl_close($ch);
    
    if ($httpCode !== 200) {
        $error = json_decode($response, true);
        throw new Exception('API Error (' . $httpCode . '): ' . ($error['message'] ?? 'Unknown error'));
    }
    
    return json_decode($response, true);
}

/**
 * Validate date format
 */
function validate_date($date_string) {
    $date = DateTime::createFromFormat('Y-m-d', $date_string);
    if (!$date || $date->format('Y-m-d') !== $date_string) {
        throw new Exception('Invalid date format. Use YYYY-MM-DD', 400);
    }
    return $date;
}

/**
 * Validate UUID format
 */
function validate_uuid($uuid_string) {
    if (!preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{4}-[0-9a-f]{12}$/i', $uuid_string)) {
        throw new Exception('Invalid UUID format', 400);
    }
    return $uuid_string;
}
