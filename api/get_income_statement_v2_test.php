<?php
/**
 * Income Statement API V2 - Simplified Test Version
 */

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

try {
    // Get and validate parameters
    $user_id = $_GET['user_id'] ?? null;
    $company_id = $_GET['company_id'] ?? null;
    $start_date = $_GET['start_date'] ?? null;
    $end_date = $_GET['end_date'] ?? null;
    $store_id = $_GET['store_id'] ?? null;
    
    if (!$company_id || !$start_date || !$end_date) {
        throw new Exception('Missing required parameters', 400);
    }
    
    // Authentication check
    if (!checkAuth($user_id, $company_id)) {
        throw new Exception('Authentication failed', 401);
    }
    
    // For now, return mock data to test the frontend
    $mock_data = [
        'revenue' => [
            'section_title' => 'REVENUE',
            'categories' => [
                'sales_revenue' => [
                    'category_name' => 'Sales Revenue',
                    'items' => [
                        [
                            'account_id' => '123e4567-e89b-12d3-a456-426614174000',
                            'account_name' => 'Sales Revenue',
                            'account_type' => 'income',
                            'expense_nature' => null,
                            'amount' => 1500000,
                            'transaction_count' => 45
                        ],
                        [
                            'account_id' => '123e4567-e89b-12d3-a456-426614174001',
                            'account_name' => 'Service Revenue',
                            'account_type' => 'income',
                            'expense_nature' => null,
                            'amount' => 800000,
                            'transaction_count' => 23
                        ]
                    ],
                    'subtotal' => 2300000,
                    'transaction_count' => 68
                ]
            ],
            'total' => 2300000
        ],
        'cogs' => [
            'section_title' => 'COST OF GOODS SOLD',
            'categories' => [
                'cogs' => [
                    'category_name' => 'Cost of Goods Sold',
                    'items' => [
                        [
                            'account_id' => '123e4567-e89b-12d3-a456-426614174002',
                            'account_name' => 'COGS - Product',
                            'account_type' => 'expense',
                            'expense_nature' => 'variable',
                            'amount' => 600000,
                            'transaction_count' => 32
                        ]
                    ],
                    'subtotal' => 600000,
                    'transaction_count' => 32
                ]
            ],
            'total' => 600000
        ],
        'gross_profit' => 1700000,
        'operating_expenses' => [
            'section_title' => 'OPERATING EXPENSES',
            'categories' => [
                'operating_expense' => [
                    'category_name' => 'Operating Expenses',
                    'items' => [
                        [
                            'account_id' => '123e4567-e89b-12d3-a456-426614174003',
                            'account_name' => 'Rent Expense',
                            'account_type' => 'expense',
                            'expense_nature' => 'fixed',
                            'amount' => 300000,
                            'transaction_count' => 1
                        ],
                        [
                            'account_id' => '123e4567-e89b-12d3-a456-426614174004',
                            'account_name' => 'Employee Salary',
                            'account_type' => 'expense',
                            'expense_nature' => 'fixed',
                            'amount' => 500000,
                            'transaction_count' => 15
                        ],
                        [
                            'account_id' => '123e4567-e89b-12d3-a456-426614174005',
                            'account_name' => 'Marketing Expense',
                            'account_type' => 'expense',
                            'expense_nature' => 'variable',
                            'amount' => 150000,
                            'transaction_count' => 8
                        ]
                    ],
                    'subtotal' => 950000,
                    'transaction_count' => 24
                ]
            ],
            'total' => 950000,
            'fixed_total' => 800000,
            'variable_total' => 150000
        ],
        'operating_income' => 750000,
        'non_operating' => [
            'section_title' => 'NON-OPERATING ITEMS',
            'income' => [],
            'expenses' => [],
            'net_total' => 0
        ],
        'income_before_tax' => 750000,
        'tax' => [
            'section_title' => 'TAX EXPENSES',
            'categories' => [],
            'total' => 0
        ],
        'net_income' => 750000,
        'comprehensive_income' => [
            'section_title' => 'OTHER COMPREHENSIVE INCOME',
            'categories' => [],
            'total' => 0
        ],
        'total_comprehensive_income' => 750000
    ];
    
    $summary = [
        'total_revenue' => 2300000,
        'total_cogs' => 600000,
        'gross_profit' => 1700000,
        'gross_profit_margin' => 73.91,
        'total_operating_expenses' => 950000,
        'fixed_expenses' => 800000,
        'variable_expenses' => 150000,
        'operating_income' => 750000,
        'operating_margin' => 32.61,
        'income_before_tax' => 750000,
        'total_tax' => 0,
        'net_income' => 750000,
        'net_margin' => 32.61,
        'comprehensive_income' => 750000,
        'cost_structure' => [
            'cogs_ratio' => 26.09,
            'opex_ratio' => 41.30,
            'fixed_cost_ratio' => 34.78,
            'variable_cost_ratio' => 6.52
        ]
    ];
    
    $analysis = [
        'profitability' => [
            'gross_profit_health' => 'excellent',
            'operating_efficiency' => 'highly_efficient',
            'overall_performance' => 'strong'
        ],
        'cost_analysis' => [
            'cost_structure_balance' => 'optimal',
            'fixed_vs_variable' => [
                'fixed_percentage' => 34.78,
                'variable_percentage' => 6.52,
                'flexibility_score' => 15.8
            ]
        ],
        'key_insights' => [
            "Strong gross profit margin indicates good pricing power and cost control",
            "Excellent operating efficiency with healthy profit margins"
        ],
        'recommendations' => [
            "Continue monitoring cost structure balance",
            "Consider opportunities to increase variable cost flexibility"
        ]
    ];
    
    $metadata = [
        'report_title' => 'Income Statement',
        'period' => [
            'start_date' => $start_date,
            'end_date' => $end_date,
            'display' => date('F Y', strtotime($start_date))
        ],
        'filters' => [
            'company_id' => $company_id,
            'store_id' => $store_id
        ],
        'generation_info' => [
            'generated_at' => date('c'),
            'query_count' => 1,
            'version' => 'v2.0-test'
        ]
    ];
    
    // Return successful response
    echo json_encode([
        'success' => true,
        'timestamp' => date('c'),
        'request_params' => [
            'company_id' => $company_id,
            'start_date' => $start_date,
            'end_date' => $end_date,
            'store_id' => $store_id,
            'grouping_level' => 'detailed'
        ],
        'data' => $mock_data,
        'summary' => $summary,
        'analysis' => $analysis,
        'metadata' => $metadata,
        'performance' => [
            'execution_time' => '15.2ms',
            'query_count' => 1
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
