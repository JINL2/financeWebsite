<?php
/**
 * Common functions file
 */
require_once 'db.php';

// 🔄 Global Supabase connection
$supabase = new SupabaseDB();

/**
 * HTML escape
 */
function h($str) {
    return htmlspecialchars($str ?? '', ENT_QUOTES, 'UTF-8');
}

/**
 * Money formatting
 */
function formatMoney($amount, $decimals = 0) {
    return number_format($amount, $decimals);
}

/**
 * Date formatting
 */
function formatDate($date, $format = 'Y-m-d') {
    if (empty($date)) return '';
    return date($format, strtotime($date));
}

/**
 * Create pagination info
 */
function getPaginationInfo($page, $limit, $total) {
    $page = max(1, intval($page));
    $limit = max(1, intval($limit));
    $total = max(0, intval($total));
    
    $totalPages = ceil($total / $limit);
    $offset = ($page - 1) * $limit;
    
    return [
        'page' => $page,
        'limit' => $limit,
        'offset' => $offset,
        'total' => $total,
        'totalPages' => $totalPages,
        'hasNext' => $page < $totalPages,
        'hasPrev' => $page > 1
    ];
}

/**
 * Get account type in English
 */
function getAccountTypeEnglish($type) {
    $types = [
        'asset' => 'Assets',
        'liability' => 'Liabilities',
        'equity' => 'Equity',
        'income' => 'Revenue',
        'expense' => 'Expenses'
    ];
    return $types[$type] ?? $type;
}

/**
 * Debit/Credit classification
 */
function getDebitCreditType($amount, $isDebit = true) {
    if ($isDebit && $amount > 0) {
        return ['type' => 'debit', 'class' => 'text-success', 'prefix' => '+'];
    } elseif (!$isDebit && $amount > 0) {
        return ['type' => 'credit', 'class' => 'text-danger', 'prefix' => '-'];
    }
    return ['type' => '', 'class' => '', 'prefix' => ''];
}

/**
 * Debug output
 */
function debug($data, $die = false) {
    echo '<pre>';
    print_r($data);
    echo '</pre>';
    if ($die) {
        die();
    }
}

/**
 * Get currency symbol - DEPRECATED, use getCompanyCurrency instead
 */
function getCurrencySymbol($currency) {
    $symbols = [
        'KRW' => '₩',
        'USD' => '$',
        'EUR' => '€',
        'JPY' => '¥',
        'GBP' => '£',
        'CNY' => '¥',
        'VND' => '₫',
        'THB' => '฿',
        'SGD' => 'S$',
        'HKD' => 'HK$',
        'AUD' => 'A$',
        'CAD' => 'C$'
    ];
    
    return $symbols[$currency] ?? $currency . ' ';
}

/**
 * 회사의 통화 정보 가져오기
 * @param string $company_id
 * @return array ['currency_code' => 'VND', 'currency_symbol' => '₫', 'currency_name' => 'Vietnamese Dong']
 */
function getCompanyCurrency($company_id) {
    require_once __DIR__ . '/db.php';
    $db = new SupabaseDB();
    
    try {
        $company_info = $db->query('companies', [
            'company_id' => 'eq.' . $company_id,
            'select' => 'company_id,company_name,base_currency_id,currency_types(currency_code,symbol,currency_name)'
        ]);
        
        if (!empty($company_info) && isset($company_info[0]['currency_types'])) {
            return [
                'currency_code' => $company_info[0]['currency_types']['currency_code'] ?? 'VND',
                'currency_symbol' => $company_info[0]['currency_types']['symbol'] ?? '₫',
                'currency_name' => $company_info[0]['currency_types']['currency_name'] ?? 'Vietnamese Dong'
            ];
        }
    } catch (Exception $e) {
        error_log("Currency fetch error: " . $e->getMessage());
    }
    
    // 기본값 반환
    return [
        'currency_code' => 'VND',
        'currency_symbol' => '₫',
        'currency_name' => 'Vietnamese Dong'
    ];
}

/**
 * Get user info
 */
function getUserInfo($user_id) {
    // For now, return basic info
    // In production, this would query the database
    return [
        'user_id' => $user_id,
        'full_name' => 'User',
        'email' => 'user@example.com',
        'first_name' => 'User',
        'last_name' => ''
    ];
}

/**
 * Get company info
 */
function getCompanyInfo($company_id) {
    // For now, return basic info
    // In production, this would query the database
    return [
        'company_id' => $company_id,
        'company_name' => 'Cameraon&Headsup'
    ];
}

/**
 * Get company stores
 */
function getCompanyStores($company_id) {
    require_once __DIR__ . '/db.php';
    $db = new SupabaseDB();
    
    try {
        $stores = $db->query('stores', [
            'company_id' => 'eq.' . $company_id,
            'is_deleted' => 'eq.false',
            'select' => 'store_id,store_name,store_code',
            'order' => 'store_name.asc'
        ]);
        
        return $stores ?: [];
    } catch (Exception $e) {
        error_log("Get company stores error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get user companies and stores
 */
function getUserCompaniesAndStores($user_id) {
    // For now, return test data with multiple companies
    return [
        'user_id' => $user_id,
        'companies' => [
            [
                'company_id' => 'ebd66ba7-fde7-4332-b6b5-0d8a7f615497',
                'company_name' => 'Cameraon&Headsup',
                'stores' => [
                    [
                        'store_id' => 'store1',
                        'store_name' => 'Main Store',
                        'company_id' => 'ebd66ba7-fde7-4332-b6b5-0d8a7f615497'
                    ],
                    [
                        'store_id' => 'store2',
                        'store_name' => 'Branch Store',
                        'company_id' => 'ebd66ba7-fde7-4332-b6b5-0d8a7f615497'
                    ]
                ]
            ],
            [
                'company_id' => 'test-company-2',
                'company_name' => 'Test Company 2',
                'stores' => [
                    [
                        'store_id' => 'store3',
                        'store_name' => 'Store 3',
                        'company_id' => 'test-company-2'
                    ]
                ]
            ],
            [
                'company_id' => 'test-company-3',
                'company_name' => 'Test Company 3',
                'stores' => [
                    [
                        'store_id' => 'store4',
                        'store_name' => 'Store 4',
                        'company_id' => 'test-company-3'
                    ]
                ]
            ]
        ]
    ];
}

?>
