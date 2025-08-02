<?php
/**
 * Debug script to check HQ data
 */
require_once '../common/auth.php';
require_once '../common/db.php';

header('Content-Type: text/plain');

// Authentication check
$auth = requireAuth();
$user_id = $auth['user_id'] ?? $_GET['user_id'] ?? null;
$company_id = $auth['company_id'] ?? $_GET['company_id'] ?? null;

if (!$user_id || !$company_id) {
    die("user_id and company_id are required");
}

$db = new SupabaseDB();

echo "=== HQ Data Investigation ===\n\n";
echo "Company ID: $company_id\n";
echo "User ID: $user_id\n\n";

try {
    // 1. Check total journal entries for company
    echo "1. Total journal entries for company:\n";
    $journals = $db->query('journal_entries', [
        'company_id' => 'eq.' . $company_id,
        'select' => 'count'
    ]);
    echo "   Total: " . ($journals[0]['count'] ?? 0) . "\n\n";

    // 2. Check journal lines with null store_id
    echo "2. Checking journal lines with null store_id:\n";
    $nullStoreLines = $db->query('journal_lines', [
        'store_id' => 'is.null',
        'select' => 'line_id,journal_id,account_id',
        'limit' => 5
    ]);
    echo "   Found " . count($nullStoreLines) . " lines with null store_id\n";
    foreach ($nullStoreLines as $line) {
        echo "   - Line ID: " . $line['line_id'] . ", Journal ID: " . $line['journal_id'] . "\n";
    }
    echo "\n";

    // 3. Check if v_journal_lines_complete exists and has data
    echo "3. Checking v_journal_lines_complete view:\n";
    try {
        $viewData = $db->query('v_journal_lines_complete', [
            'company_id' => 'eq.' . $company_id,
            'store_id' => 'is.null',
            'select' => 'line_id,journal_id,account_name,store_name',
            'limit' => 5
        ]);
        echo "   Found " . count($viewData) . " lines in view with null store_id\n";
        foreach ($viewData as $line) {
            echo "   - Journal ID: " . $line['journal_id'] . ", Account: " . $line['account_name'] . ", Store: " . ($line['store_name'] ?? 'NULL') . "\n";
        }
    } catch (Exception $e) {
        echo "   ERROR accessing view: " . $e->getMessage() . "\n";
    }
    echo "\n";

    // 4. Get sample journal entries and check their lines
    echo "4. Sample journal entries and their lines:\n";
    $sampleJournals = $db->query('journal_entries', [
        'company_id' => 'eq.' . $company_id,
        'select' => 'journal_id,entry_date,description',
        'order' => 'entry_date.desc',
        'limit' => 3
    ]);
    
    foreach ($sampleJournals as $journal) {
        echo "   Journal ID: " . $journal['journal_id'] . " (Date: " . $journal['entry_date'] . ")\n";
        
        // Get lines for this journal
        $lines = $db->query('journal_lines', [
            'journal_id' => 'eq.' . $journal['journal_id'],
            'select' => 'line_id,account_id,store_id,debit,credit'
        ]);
        
        foreach ($lines as $line) {
            echo "     - Line: store_id=" . ($line['store_id'] ?? 'NULL') . ", debit=" . $line['debit'] . ", credit=" . $line['credit'] . "\n";
        }
    }
    echo "\n";

    // 5. Test the actual API query for HQ
    echo "5. Testing API query for HQ:\n";
    $journalParams = [
        'company_id' => 'eq.' . $company_id,
        'select' => 'journal_id',
        'order' => 'entry_date.desc,created_at.desc',
        'limit' => 10
    ];
    
    $journalEntries = $db->query('journal_entries', $journalParams);
    $journalIds = array_column($journalEntries, 'journal_id');
    echo "   Found " . count($journalIds) . " recent journals\n";
    
    if (!empty($journalIds)) {
        // Check which have HQ lines
        $hqLines = $db->query('v_journal_lines_complete', [
            'journal_id' => 'in.(' . implode(',', $journalIds) . ')',
            'store_id' => 'is.null',
            'select' => 'journal_id,line_id,account_name'
        ]);
        echo "   Found " . count($hqLines) . " HQ lines in these journals\n";
        
        // Group by journal
        $hqJournals = [];
        foreach ($hqLines as $line) {
            $jid = $line['journal_id'];
            if (!isset($hqJournals[$jid])) {
                $hqJournals[$jid] = [];
            }
            $hqJournals[$jid][] = $line['account_name'];
        }
        echo "   Journals with HQ lines: " . count($hqJournals) . "\n";
        foreach ($hqJournals as $jid => $accounts) {
            echo "     - Journal $jid: " . implode(", ", array_unique($accounts)) . "\n";
        }
    }

    // 6. Check stores table
    echo "\n6. Checking stores table:\n";
    $stores = $db->query('stores', [
        'company_id' => 'eq.' . $company_id,
        'select' => 'store_id,store_name',
        'order' => 'store_name'
    ]);
    echo "   Total stores: " . count($stores) . "\n";
    foreach ($stores as $store) {
        echo "   - " . $store['store_id'] . ": " . $store['store_name'] . "\n";
    }

} catch (Exception $e) {
    echo "\nERROR: " . $e->getMessage() . "\n";
    echo "Stack trace:\n" . $e->getTraceAsString() . "\n";
}
