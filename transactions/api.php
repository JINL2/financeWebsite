<?php
/**
 * Financial Management System - Transactions API (Fixed to match dashboard)
 * Uses same approach as dashboard for consistency
 */
require_once '../common/auth.php';
require_once '../common/db.php';
require_once '../common/functions.php';

header('Content-Type: application/json');

// Authentication check
$auth = requireAuth();
$user_id = $auth['user_id'] ?? $_GET['user_id'] ?? null;
$company_id = $auth['company_id'] ?? $_GET['company_id'] ?? null;

if (!$user_id || !$company_id) {
    http_response_code(400);
    die(json_encode(['error' => 'user_id and company_id are required']));
}

$action = $_GET['action'] ?? '';
$db = new SupabaseDB();

// Get company currency using common function
$currency_info = getCompanyCurrency($company_id);
$currency_code = $currency_info['currency_code'];
$currency_symbol = $currency_info['currency_symbol'];
$currency_name = $currency_info['currency_name'];

try {
    switch ($action) {
        case 'get_filters':
            // Get filter options for dropdowns
            $store_id = $_GET['store_id'] ?? null;
            
            // Get accounts
            $accounts = $db->query('accounts', [
                'order' => 'account_name'
            ]);
            
            // Get company users
            $users = $db->callRPC('get_company_users', [
                'p_company_id' => $company_id
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'accounts' => $accounts,
                    'users' => $users
                ]
            ]);
            break;
            
        case 'get_transactions':
            // Get filter parameters
            $store_id = $_GET['store_id'] ?? null;
            $date_from = $_GET['date_from'] ?? null;
            $date_to = $_GET['date_to'] ?? null;
            $account_name = $_GET['account_name'] ?? null;
            $created_by = $_GET['created_by'] ?? null;
            $keyword = $_GET['keyword'] ?? null;
            $page = intval($_GET['page'] ?? 1);
            $limit = intval($_GET['limit'] ?? 20);
            
            // Build query parameters for v_journal_lines_complete
            $params = [
                'order' => 'entry_date.desc,journal_created_at.desc',
                'limit' => 1000, // Get a large number first for grouping
                'select' => '*',
                'company_id' => 'eq.' . $company_id
            ];
            
            // Apply filters
            if ($date_from) {
                $params['entry_date'] = 'gte.' . $date_from;
            }
            if ($date_to) {
                if (isset($params['entry_date'])) {
                    $params['entry_date'] .= '&entry_date=lte.' . $date_to;
                } else {
                    $params['entry_date'] = 'lte.' . $date_to;
                }
            }
            
            // Default to current month if no date filters
            if (!$date_from && !$date_to) {
                $currentMonth = date('Y-m');
                $params['entry_date'] = 'gte.' . $currentMonth . '-01';
            }
            
            // Store filter
            if ($store_id) {
                $params['store_id'] = 'eq.' . $store_id;
            }
            
            // Account filter
            if ($account_name) {
                $params['account_name'] = 'ilike.*' . $account_name . '*';
            }
            
            // Keyword filter
            if ($keyword) {
                $params['or'] = "(journal_description.ilike.*{$keyword}*,line_description.ilike.*{$keyword}*)";
            }
            
            // Get journal lines
            $journalLines = $db->query('v_journal_lines_complete', $params);
            
            if (empty($journalLines)) {
                echo json_encode([
                    'success' => true,
                    'data' => [],
                    'pagination' => [
                        'total' => 0,
                        'page' => $page,
                        'limit' => $limit,
                        'totalPages' => 0,
                        'hasMore' => false
                    ]
                ]);
                break;
            }
            
            // Group by journal_id (same as dashboard)
            $processedJournals = [];
            $journalOrder = [];
            
            foreach ($journalLines as $line) {
                $journalId = $line['journal_id'];
                
                if (!isset($processedJournals[$journalId])) {
                    $journalOrder[] = $journalId;
                    $processedJournals[$journalId] = [
                        'journal_id' => $journalId,
                        'entry_date' => $line['entry_date'],
                        'description' => $line['journal_description'] ?? '',
                        'company_name' => $line['company_name'] ?? '',
                        'created_by' => $line['created_by_name'] ?? 'Unknown',
                        'created_by_id' => $line['created_by'] ?? null,
                        'counterparty_name' => $line['counterparty_name'] ?? $line['journal_counterparty_name'] ?? null,
                        'total_debit' => 0,
                        'total_credit' => 0,
                        'lines' => []
                    ];
                }
                
                // Add line
                $processedJournals[$journalId]['lines'][] = [
                    'line_id' => $line['line_id'],
                    'journal_id' => $line['journal_id'],
                    'account_id' => $line['account_id'],
                    'account_name' => $line['account_name'] ?? 'Unknown',
                    'debit' => floatval($line['debit'] ?? 0),
                    'credit' => floatval($line['credit'] ?? 0),
                    'description' => $line['line_description'] ?? '',
                    'cash_location_name' => $line['cash_location_name'] ?? null,
                    'store_name' => $line['store_name'] ?? null,
                    'store_id' => $line['store_id'] ?? null,
                    'counterparty_name' => $line['counterparty_name'] ?? null
                ];
                
                // Calculate totals
                $processedJournals[$journalId]['total_debit'] += floatval($line['debit'] ?? 0);
                $processedJournals[$journalId]['total_credit'] += floatval($line['credit'] ?? 0);
            }
            
            // Filter by created_by if specified
            if ($created_by) {
                $filteredJournals = [];
                foreach ($journalOrder as $journalId) {
                    if ($processedJournals[$journalId]['created_by_id'] === $created_by) {
                        $filteredJournals[] = $journalId;
                    }
                }
                $journalOrder = $filteredJournals;
            }
            
            // Apply pagination
            $totalEntries = count($journalOrder);
            $totalPages = ceil($totalEntries / $limit);
            $offset = ($page - 1) * $limit;
            
            $paginatedJournals = array_slice($journalOrder, $offset, $limit);
            
            // Build final result
            $result = [];
            foreach ($paginatedJournals as $journalId) {
                $result[] = $processedJournals[$journalId];
            }
            
            echo json_encode([
                'success' => true,
                'data' => $result,
                'pagination' => [
                    'total' => $totalEntries,
                    'page' => $page,
                    'limit' => $limit,
                    'totalPages' => $totalPages,
                    'hasMore' => $page < $totalPages
                ],
                'currency_symbol' => $currency_symbol,
                'currency_code' => $currency_code
            ]);
            break;
            
        case 'get_journal_details':
            // Get journal entry details
            $journal_id = $_GET['journal_id'] ?? null;
            
            if (!$journal_id) {
                http_response_code(400);
                die(json_encode(['error' => 'journal_id is required']));
            }
            
            // Get journal entry
            $journal = $db->query('journal_entries', [
                'journal_id' => 'eq.' . $journal_id,
                'company_id' => 'eq.' . $company_id
            ]);
            
            if (empty($journal)) {
                http_response_code(404);
                die(json_encode(['error' => 'Journal entry not found']));
            }
            
            // Get journal lines from complete view
            $lines = $db->query('v_journal_lines_complete', [
                'journal_id' => 'eq.' . $journal_id,
                'order' => 'line_id'
            ]);
            
            echo json_encode([
                'success' => true,
                'data' => [
                    'journal' => $journal[0],
                    'lines' => $lines
                ],
                'currency_symbol' => $currency_symbol,
                'currency_code' => $currency_code
            ]);
            break;
            
        default:
            http_response_code(400);
            echo json_encode(['error' => 'Invalid action']);
    }
    
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>