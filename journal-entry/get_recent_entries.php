<?php
/**
 * Recent Entries API for Journal Entry Module
 * Phase 4.3.3: Recent Entries functionality
 */

require_once '../common/auth.php';
require_once '../common/functions.php';

header('Content-Type: application/json');

try {
    // Get authentication parameters
    $user_id = $_GET['user_id'] ?? '';
    $company_id = $_GET['company_id'] ?? '';
    
    // Check if user is authenticated
    if (empty($user_id) || empty($company_id)) {
        throw new Exception('Unauthorized - user_id and company_id parameters required');
    }
    
    // Get action parameter
    $action = $_GET['action'] ?? 'list';
    $method = $_SERVER['REQUEST_METHOD'];
    
    switch ($action) {
        case 'list':
            handleListRecentEntries($user_id, $company_id);
            break;
            
        case 'search':
            handleAdvancedSearch($user_id, $company_id);
            break;
            
        case 'get':
            handleGetEntryDetails($user_id, $company_id);
            break;
            
        case 'copy':
            if ($method === 'POST') {
                handleCopyEntry($user_id, $company_id);
            } else {
                throw new Exception('POST method required for copy action');
            }
            break;
            
        default:
            throw new Exception('Invalid action');
    }

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}

/**
 * Get list of recent journal entries
 */
function handleListRecentEntries($user_id, $company_id) {
    global $supabase;
    
    $limit = intval($_GET['limit'] ?? 10);
    $offset = intval($_GET['offset'] ?? 0);
    
    // Ensure reasonable limits
    $limit = min(max($limit, 1), 50);
    $offset = max($offset, 0);
    
    try {
        // Query recent journal entries
        $entries_response = $supabase->query('journal_entries', [
            'select' => 'journal_id,entry_date,description,base_amount,created_at,is_draft,store_id',
            'company_id' => 'eq.' . $company_id,
            'created_by' => 'eq.' . $user_id,
            'is_deleted' => 'eq.false',
            'order' => 'created_at.desc',
            'limit' => $limit,
            'offset' => $offset
        ]);
        
        if (!$entries_response) {
            $entries_response = [];
        }
        
        // Get store names
        $store_ids = array_unique(array_filter(array_column($entries_response, 'store_id')));
        $stores_lookup = [];
        
        if (!empty($store_ids)) {
            $stores_response = $supabase->query('stores', [
                'select' => 'store_id,store_name',
                'store_id' => 'in.(' . implode(',', $store_ids) . ')'
            ]);
            
            foreach ($stores_response as $store) {
                $stores_lookup[$store['store_id']] = $store['store_name'];
            }
        }
        
        // Get line counts for each entry
        $journal_ids = array_column($entries_response, 'journal_id');
        $line_counts = [];
        
        if (!empty($journal_ids)) {
            foreach ($journal_ids as $journal_id) {
                $lines_response = $supabase->query('journal_lines', [
                    'select' => 'line_id',
                    'journal_id' => 'eq.' . $journal_id,
                    'is_deleted' => 'eq.false'
                ]);
                $line_counts[$journal_id] = count($lines_response);
            }
        }
        
        $entries = [];
        foreach ($entries_response as $entry) {
            $entries[] = [
                'journal_id' => $entry['journal_id'],
                'entry_date' => $entry['entry_date'],
                'description' => $entry['description'] ?: 'No description',
                'store_name' => $entry['store_id'] ? ($stores_lookup[$entry['store_id']] ?? 'Unknown Store') : 'No Store',
                'total_amount' => floatval($entry['base_amount']),
                'line_count' => $line_counts[$entry['journal_id']] ?? 0,
                'created_at' => $entry['created_at'],
                'is_draft' => $entry['is_draft'],
                'status' => $entry['is_draft'] ? 'Draft' : 'Completed'
            ];
        }
        
        echo json_encode([
            'success' => true,
            'entries' => $entries,
            'pagination' => [
                'limit' => $limit,
                'offset' => $offset,
                'count' => count($entries)
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to fetch recent entries: ' . $e->getMessage());
    }
}

/**
 * Get detailed information for a specific journal entry
 */
function handleGetEntryDetails($user_id, $company_id) {
    global $supabase;
    
    $entry_id = $_GET['entry_id'] ?? '';
    if (empty($entry_id)) {
        throw new Exception('Entry ID is required');
    }
    
    try {
        // Get journal entry details
        $entry_response = $supabase->query('journal_entries', [
            'select' => '*',
            'journal_id' => 'eq.' . $entry_id,
            'company_id' => 'eq.' . $company_id,
            'created_by' => 'eq.' . $user_id,
            'is_deleted' => 'eq.false'
        ]);
        
        if (empty($entry_response)) {
            throw new Exception('Journal entry not found');
        }
        
        $entry = $entry_response[0];
        
        // Get journal lines
        $lines_response = $supabase->query('journal_lines', [
            'select' => 'line_id,account_id,description,debit,credit',
            'journal_id' => 'eq.' . $entry_id,
            'is_deleted' => 'eq.false',
            'order' => 'created_at'
        ]);
        
        // Get account names for the lines
        $account_ids = array_unique(array_column($lines_response, 'account_id'));
        $accounts_response = $supabase->query('accounts', [
            'select' => 'account_id,account_name',
            'account_id' => 'in.(' . implode(',', $account_ids) . ')',
            'company_id' => 'eq.' . $company_id
        ]);
        
        // Create account lookup
        $account_lookup = [];
        foreach ($accounts_response as $account) {
            $account_lookup[$account['account_id']] = $account['account_name'];
        }
        
        // Process lines with account names
        $processed_lines = [];
        foreach ($lines_response as $line) {
            $processed_lines[] = [
                'line_id' => $line['line_id'],
                'account_id' => $line['account_id'],
                'account_name' => $account_lookup[$line['account_id']] ?? 'Unknown Account',
                'description' => $line['description'],
                'debit' => floatval($line['debit']),
                'credit' => floatval($line['credit'])
            ];
        }
        
        echo json_encode([
            'success' => true,
            'entry' => [
                'journal_id' => $entry['journal_id'],
                'entry_date' => $entry['entry_date'],
                'store_id' => $entry['store_id'],
                'description' => $entry['description'],
                'base_amount' => floatval($entry['base_amount']),
                'created_at' => $entry['created_at'],
                'is_draft' => $entry['is_draft'],
                'lines' => $processed_lines
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to get entry details: ' . $e->getMessage());
    }
}

/**
 * Phase 5.5: Advanced Search and Filtering
 */
function handleAdvancedSearch($user_id, $company_id) {
    global $supabase;
    
    // Get search parameters
    $search_text = $_GET['search'] ?? '';
    $date_from = $_GET['date_from'] ?? '';
    $date_to = $_GET['date_to'] ?? '';
    $store_id = $_GET['store_id'] ?? '';
    $amount_range = $_GET['amount_range'] ?? '';
    $status = $_GET['status'] ?? '';
    $exact_match = $_GET['exact_match'] === 'true';
    $case_sensitive = $_GET['case_sensitive'] === 'true';
    $include_auto_saved = $_GET['include_auto_saved'] === 'true';
    $search_in_lines = $_GET['search_in_lines'] === 'true';
    $sort_by = $_GET['sort_by'] ?? 'created_at';
    $sort_order = $_GET['sort_order'] ?? 'desc';
    $limit = min(100, max(1, intval($_GET['limit'] ?? 20)));
    $offset = max(0, intval($_GET['offset'] ?? 0));
    
    try {
        // Build base query
        $conditions = [
            'company_id' => 'eq.' . $company_id,
            'created_by' => 'eq.' . $user_id,
            'is_deleted' => 'eq.false'
        ];
        
        // Date range filter
        if (!empty($date_from)) {
            $conditions['entry_date'] = 'gte.' . $date_from;
        }
        if (!empty($date_to)) {
            $conditions['entry_date'] = 'lte.' . $date_to;
        }
        
        // Store filter
        if (!empty($store_id)) {
            $conditions['store_id'] = 'eq.' . $store_id;
        }
        
        // Status filter
        if (!empty($status)) {
            switch ($status) {
                case 'draft':
                    $conditions['is_draft'] = 'eq.true';
                    break;
                case 'completed':
                    $conditions['is_draft'] = 'eq.false';
                    break;
                case 'auto-saved':
                    // For auto-saved, we need to check description or reference patterns
                    $conditions['description'] = 'ilike.*auto*';
                    break;
            }
        }
        
        // Include/exclude auto-saved entries
        if (!$include_auto_saved) {
            // Exclude entries with '-AUTO' in reference or 'auto' in description
            // This will be handled in post-processing due to Supabase query limitations
        }
        
        // Text search in description
        if (!empty($search_text)) {
            if ($exact_match) {
                if ($case_sensitive) {
                    $conditions['description'] = 'like.*' . $search_text . '*';
                } else {
                    $conditions['description'] = 'ilike.*' . $search_text . '*';
                }
            } else {
                // Fuzzy search - split into words
                $search_words = explode(' ', trim($search_text));
                $primary_word = $search_words[0];
                if ($case_sensitive) {
                    $conditions['description'] = 'like.*' . $primary_word . '*';
                } else {
                    $conditions['description'] = 'ilike.*' . $primary_word . '*';
                }
            }
        }
        
        // Build order clause
        $order_clause = $sort_by . '.' . $sort_order;
        $conditions['order'] = $order_clause;
        $conditions['limit'] = $limit;
        $conditions['offset'] = $offset;
        
        // Execute main query
        $entries_response = $supabase->query('journal_entries', [
            'select' => 'journal_id,entry_date,description,base_amount,created_at,is_draft,store_id'
        ] + $conditions);
        
        if (empty($entries_response)) {
            $entries_response = [];
        }
        
        // Post-process for advanced filtering
        $filtered_entries = [];
        foreach ($entries_response as $entry) {
            $include_entry = true;
            
            // Amount range filter
            if (!empty($amount_range) && $include_entry) {
                $amount = floatval($entry['base_amount']);
                switch ($amount_range) {
                    case '0-50000':
                        $include_entry = $amount <= 50000;
                        break;
                    case '50000-200000':
                        $include_entry = $amount > 50000 && $amount <= 200000;
                        break;
                    case '200000-1000000':
                        $include_entry = $amount > 200000 && $amount <= 1000000;
                        break;
                    case '1000000-5000000':
                        $include_entry = $amount > 1000000 && $amount <= 5000000;
                        break;
                    case '5000000+':
                        $include_entry = $amount > 5000000;
                        break;
                }
            }
            
            // Auto-saved filter
            if (!$include_auto_saved && $include_entry) {
                // reference_number 관련 코드 제거됨
                $desc = $entry['description'] ?? '';
                // auto-saved 필터를 description만으로 처리
                if (stripos($desc, 'auto') !== false) {
                    $include_entry = false;
                }
            }
            
            // Advanced text search
            if (!empty($search_text) && $include_entry && !$exact_match) {
                $search_words = explode(' ', trim($search_text));
                $found_words = 0;
                $search_fields = [
                    $entry['description'] ?? '',
                    // reference_number 제거됨
                    strval($entry['base_amount'])
                ];
                
                $search_content = implode(' ', $search_fields);
                if (!$case_sensitive) {
                    $search_content = strtolower($search_content);
                }
                
                foreach ($search_words as $word) {
                    $check_word = $case_sensitive ? $word : strtolower($word);
                    if (strpos($search_content, $check_word) !== false) {
                        $found_words++;
                    }
                }
                
                // Require at least 50% of words to match for fuzzy search
                $required_matches = max(1, ceil(count($search_words) * 0.5));
                $include_entry = $found_words >= $required_matches;
            }
            
            if ($include_entry) {
                $filtered_entries[] = $entry;
            }
        }
        
        // Search in journal lines if requested
        if ($search_in_lines && !empty($search_text) && !empty($filtered_entries)) {
            $journal_ids = array_column($filtered_entries, 'journal_id');
            
            // Get lines for all entries
            $lines_response = $supabase->query('journal_lines', [
                'select' => 'journal_id,description',
                'journal_id' => 'in.(' . implode(',', $journal_ids) . ')',
                'is_deleted' => 'eq.false'
            ]);
            
            // Build lines lookup
            $entry_lines = [];
            foreach ($lines_response as $line) {
                $entry_lines[$line['journal_id']][] = $line['description'] ?? '';
            }
            
            // Filter entries based on line content
            $line_filtered_entries = [];
            foreach ($filtered_entries as $entry) {
                $journal_id = $entry['journal_id'];
                $lines_content = implode(' ', $entry_lines[$journal_id] ?? []);
                
                if (!$case_sensitive) {
                    $lines_content = strtolower($lines_content);
                    $search_text = strtolower($search_text);
                }
                
                if (strpos($lines_content, $search_text) !== false) {
                    $line_filtered_entries[] = $entry;
                }
            }
            
            $filtered_entries = $line_filtered_entries;
        }
        
        // Get store names and line counts
        $store_ids = array_unique(array_filter(array_column($filtered_entries, 'store_id')));
        $stores_lookup = [];
        
        if (!empty($store_ids)) {
            $stores_response = $supabase->query('stores', [
                'select' => 'store_id,store_name',
                'store_id' => 'in.(' . implode(',', $store_ids) . ')'
            ]);
            
            foreach ($stores_response as $store) {
                $stores_lookup[$store['store_id']] = $store['store_name'];
            }
        }
        
        // Get line counts
        $journal_ids = array_column($filtered_entries, 'journal_id');
        $line_counts = [];
        
        if (!empty($journal_ids)) {
            foreach ($journal_ids as $journal_id) {
                $lines_response = $supabase->query('journal_lines', [
                    'select' => 'line_id',
                    'journal_id' => 'eq.' . $journal_id,
                    'is_deleted' => 'eq.false'
                ]);
                $line_counts[$journal_id] = count($lines_response);
            }
        }
        
        // Format results
        $entries = [];
        foreach ($filtered_entries as $entry) {
            $entries[] = [
                'journal_id' => $entry['journal_id'],
                'entry_date' => $entry['entry_date'],
                'description' => $entry['description'] ?: 'No description',
                'store_name' => $entry['store_id'] ? ($stores_lookup[$entry['store_id']] ?? 'Unknown Store') : 'No Store',
                'total_amount' => floatval($entry['base_amount']),
                'line_count' => $line_counts[$entry['journal_id']] ?? 0,
                'created_at' => $entry['created_at'],
                'is_draft' => $entry['is_draft'],
                'status' => $entry['is_draft'] ? 'Draft' : 'Completed',
            // reference_number 제거됨
            ];
        }
        
        echo json_encode([
            'success' => true,
            'entries' => $entries,
            'search_info' => [
                'total_found' => count($entries),
                'search_text' => $search_text,
                'filters_applied' => [
                    'date_range' => !empty($date_from) || !empty($date_to),
                    'store_filter' => !empty($store_id),
                    'amount_filter' => !empty($amount_range),
                    'status_filter' => !empty($status),
                    'text_search' => !empty($search_text),
                    'line_search' => $search_in_lines && !empty($search_text)
                ],
                'pagination' => [
                    'limit' => $limit,
                    'offset' => $offset,
                    'count' => count($entries)
                ]
            ]
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Advanced search failed: ' . $e->getMessage());
    }
}

/**
 * Copy an existing journal entry for modification
 */
function handleCopyEntry($user_id, $company_id) {
    $input = json_decode(file_get_contents('php://input'), true);
    $entry_id = $input['entry_id'] ?? '';
    
    if (empty($entry_id)) {
        throw new Exception('Entry ID is required');
    }
    
    try {
        // Get the entry details using the existing function
        $_GET['entry_id'] = $entry_id;
        ob_start();
        handleGetEntryDetails($user_id, $company_id);
        $response_json = ob_get_clean();
        $response_data = json_decode($response_json, true);
        
        if (!$response_data['success']) {
            throw new Exception('Failed to retrieve entry for copying');
        }
        
        $entry = $response_data['entry'];
        
        // Prepare data for form (remove sensitive/auto-generated fields)
        $copy_data = [
            'entry_date' => date('Y-m-d'), // Set to today's date
            'store_id' => $entry['store_id'],
            'description' => $entry['description'] . ' (Copy)',
            'lines' => []
        ];
        
        // Process lines (remove line IDs, keep structure)
        foreach ($entry['lines'] as $line) {
            $copy_data['lines'][] = [
                'account_id' => $line['account_id'],
                'account_name' => $line['account_name'],
                'description' => $line['description'],
                'debit' => $line['debit'],
                'credit' => $line['credit']
            ];
        }
        
        echo json_encode([
            'success' => true,
            'entry_data' => $copy_data,
            'message' => 'Entry copied successfully. Please review the date and amounts.'
        ]);
        
    } catch (Exception $e) {
        throw new Exception('Failed to copy entry: ' . $e->getMessage());
    }
}
?>
