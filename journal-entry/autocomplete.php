<?php
/**
 * Financial Management System - Smart Autocomplete API
 * Phase 5.3: Smart Autocomplete System
 */

require_once '../common/functions.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    exit(0);
}

try {
    $user_id = $_GET['user_id'] ?? $_POST['user_id'] ?? null;
    $company_id = $_GET['company_id'] ?? $_POST['company_id'] ?? null;
    $action = $_GET['action'] ?? $_POST['action'] ?? 'suggest';

    if (!$user_id || !$company_id) {
        throw new Exception('Missing user_id or company_id');
    }

    switch ($action) {
        case 'description_suggestions':
            echo json_encode(handleDescriptionSuggestions($user_id, $company_id));
            break;
        case 'amount_patterns':
            echo json_encode(handleAmountPatterns($user_id, $company_id));
            break;
        case 'account_combinations':
            echo json_encode(handleAccountCombinations($user_id, $company_id));
            break;
        case 'recent_descriptions':
            echo json_encode(handleRecentDescriptions($user_id, $company_id));
            break;
        case 'popular_amounts':
            echo json_encode(handlePopularAmounts($user_id, $company_id));
            break;
        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    error_log('Autocomplete API Error: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Get description suggestions based on account type
 */
function handleDescriptionSuggestions($user_id, $company_id) {
    $account_id = $_GET['account_id'] ?? $_POST['account_id'] ?? null;
    
    if (!$account_id) {
        return ['success' => false, 'error' => 'Missing account_id'];
    }
    
    global $supabase;
    
    try {
        // Get account info first
        $account_response = $supabase->query('accounts', [
            'select' => 'account_name,account_type,category_tag',
            'account_id' => 'eq.' . $account_id,
            'company_id' => 'eq.' . $company_id
        ]);
        
        if (empty($account_response)) {
            return ['success' => false, 'error' => 'Account not found'];
        }
        
        $account = $account_response[0];
        $account_type = $account['account_type'];
        
        // Get historical descriptions for this account
        $historical_response = $supabase->query('journal_lines', [
            'select' => 'line_description',
            'account_id' => 'eq.' . $account_id,
            'order' => 'created_at.desc',
            'limit' => 20
        ]);
        
        $historical_descriptions = [];
        if ($historical_response) {
            foreach ($historical_response as $line) {
                if (!empty($line['line_description'])) {
                    $historical_descriptions[] = $line['line_description'];
                }
            }
        }
        
        // Generate smart suggestions based on account type
        $smart_suggestions = getSmartDescriptionSuggestions($account_type, $account['account_name']);
        
        // Combine and deduplicate
        $all_suggestions = array_unique(array_merge($historical_descriptions, $smart_suggestions));
        
        return [
            'success' => true,
            'account_type' => $account_type,
            'account_name' => $account['account_name'],
            'category_tag' => $account['category_tag'] ?? null,
            'suggestions' => array_slice($all_suggestions, 0, 10)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get smart description suggestions based on account type
 */
function getSmartDescriptionSuggestions($account_type, $account_name) {
    $suggestions = [];
    
    switch (strtolower($account_type)) {
        case 'cash':
        case 'bank':
            $suggestions = [
                'Cash received from customer',
                'Payment from customer',
                'Cash sales revenue',
                'Bank deposit',
                'Cash refund received',
                'Payment collection'
            ];
            break;
            
        case 'revenue':
        case 'income':
            $suggestions = [
                'Product sales revenue',
                'Service revenue',
                'Consultation fee',
                'Monthly service fee',
                'Commission income',
                'Rental income'
            ];
            break;
            
        case 'expense':
            $suggestions = [
                'Office supplies expense',
                'Utility bill payment',
                'Rent expense',
                'Transportation expense',
                'Marketing expense',
                'Professional fee'
            ];
            break;
            
        case 'accounts receivable':
            $suggestions = [
                'Sales on credit',
                'Service provided on account',
                'Invoice to customer',
                'Credit sale',
                'Outstanding payment due'
            ];
            break;
            
        case 'accounts payable':
            $suggestions = [
                'Purchase on credit',
                'Supplier invoice',
                'Outstanding payment',
                'Credit purchase',
                'Vendor bill'
            ];
            break;
            
        default:
            $suggestions = [
                ucfirst(strtolower($account_name)) . ' transaction',
                'General entry',
                'Adjustment entry',
                'Transfer entry'
            ];
    }
    
    return $suggestions;
}

/**
 * Get amount patterns for specific account
 */
function handleAmountPatterns($user_id, $company_id) {
    $account_id = $_GET['account_id'] ?? $_POST['account_id'] ?? null;
    
    if (!$account_id) {
        return ['success' => false, 'error' => 'Missing account_id'];
    }
    
    global $supabase;
    
    try {
        // Get recent amounts for this account
        $amounts_response = $supabase->query('journal_lines', [
            'select' => 'debit_amount,credit_amount',
            'account_id' => 'eq.' . $account_id,
            'order' => 'created_at.desc',
            'limit' => 50
        ]);
        
        $amounts = [];
        if ($amounts_response) {
            foreach ($amounts_response as $line) {
                $amount = $line['debit_amount'] > 0 ? $line['debit_amount'] : $line['credit_amount'];
                if ($amount > 0) {
                    $amounts[] = (float)$amount;
                }
            }
        }
        
        if (empty($amounts)) {
            return [
                'success' => true,
                'patterns' => [],
                'common_amounts' => [],
                'average' => 0,
                'median' => 0
            ];
        }
        
        // Calculate statistics
        $average = array_sum($amounts) / count($amounts);
        sort($amounts);
        $median = $amounts[intval(count($amounts) / 2)];
        
        // Find most common amounts (rounded to nearest thousand)
        $rounded_amounts = array_map(function($amount) {
            return round($amount, -3); // Round to nearest thousand
        }, $amounts);
        
        $common_amounts = array_unique(array_slice(
            array_keys(array_count_values($rounded_amounts)), 0, 5
        ));
        
        sort($common_amounts);
        
        return [
            'success' => true,
            'patterns' => [
                'recent_amounts' => array_slice($amounts, 0, 10),
                'average' => round($average, 2),
                'median' => $median,
                'min' => min($amounts),
                'max' => max($amounts)
            ],
            'common_amounts' => $common_amounts
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get common account combinations
 */
function handleAccountCombinations($user_id, $company_id) {
    $account_id = $_GET['account_id'] ?? $_POST['account_id'] ?? null;
    
    if (!$account_id) {
        return ['success' => false, 'error' => 'Missing account_id'];
    }
    
    global $supabase;
    
    try {
        // Find entries that include this account
        $entries_response = $supabase->query('journal_lines', [
            'select' => 'journal_id',
            'account_id' => 'eq.' . $account_id,
            'order' => 'created_at.desc',
            'limit' => 100
        ]);
        
        if (empty($entries_response)) {
            return ['success' => true, 'combinations' => []];
        }
        
        $journal_ids = array_column($entries_response, 'journal_id');
        
        // Get all lines from these entries
        $all_lines_response = $supabase->query('journal_lines', [
            'select' => 'journal_id,account_id,accounts(account_name)',
            'journal_id' => 'in.(' . implode(',', $journal_ids) . ')',
            'account_id' => 'neq.' . $account_id
        ]);
        
        // Count combinations
        $combinations = [];
        if ($all_lines_response) {
            foreach ($all_lines_response as $line) {
                $other_account = $line['accounts']['account_name'] ?? 'Unknown Account';
                $combinations[$other_account] = ($combinations[$other_account] ?? 0) + 1;
            }
        }
        
        // Sort by frequency
        arsort($combinations);
        
        return [
            'success' => true,
            'combinations' => array_slice($combinations, 0, 10, true)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get recent descriptions used across all accounts
 */
function handleRecentDescriptions($user_id, $company_id) {
    $search_term = $_GET['search'] ?? $_POST['search'] ?? '';
    
    global $supabase;
    
    try {
        $query_params = [
            'select' => 'line_description',
            'journal_entries!inner(company_id,created_by)',
            'order' => 'created_at.desc',
            'limit' => 20
        ];
        
        // Add search filter if provided
        if (!empty($search_term)) {
            $query_params['line_description'] = 'ilike.*' . $search_term . '*';
        }
        
        $response = $supabase->query('journal_lines', $query_params);
        
        $descriptions = [];
        if ($response) {
            foreach ($response as $line) {
                if (!empty($line['line_description'])) {
                    $descriptions[] = $line['line_description'];
                }
            }
        }
        
        return [
            'success' => true,
            'descriptions' => array_unique($descriptions)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}

/**
 * Get popular amounts across all accounts
 */
function handlePopularAmounts($user_id, $company_id) {
    global $supabase;
    
    try {
        $response = $supabase->query('journal_lines', [
            'select' => 'debit_amount,credit_amount,journal_entries!inner(company_id,created_by)',
            'order' => 'created_at.desc',
            'limit' => 200
        ]);
        
        $amounts = [];
        if ($response) {
            foreach ($response as $line) {
                $amount = $line['debit_amount'] > 0 ? $line['debit_amount'] : $line['credit_amount'];
                if ($amount > 0) {
                    $rounded = round($amount, -3); // Round to nearest thousand
                    $amounts[$rounded] = ($amounts[$rounded] ?? 0) + 1;
                }
            }
        }
        
        // Sort by frequency
        arsort($amounts);
        
        return [
            'success' => true,
            'popular_amounts' => array_slice(array_keys($amounts), 0, 10)
        ];
        
    } catch (Exception $e) {
        return ['success' => false, 'error' => $e->getMessage()];
    }
}
?>
