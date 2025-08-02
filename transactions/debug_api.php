<?php
/**
 * Debug script for transactions API
 */
error_reporting(E_ALL);
ini_set('display_errors', 1);

echo "<h1>Debugging Transactions API</h1>";

// Include necessary files
require_once '../common/auth.php';
require_once '../common/db.php';

try {
    echo "<h2>1. Testing DB Connection</h2>";
    $db = new SupabaseDB();
    echo "✓ DB connection established<br>";
    
    echo "<h2>2. Testing Simple Query</h2>";
    $accounts = $db->query('accounts', ['limit' => 1]);
    echo "✓ Simple query works. First account: " . print_r($accounts[0] ?? 'None', true) . "<br>";
    
    echo "<h2>3. Testing RPC Function</h2>";
    $rpcResult = $db->callRPC('get_transactions_as_json', [
        'p_company_id' => 'ebd66ba7-fde7-4332-b6b5-0d8a7f615497',
        'p_date_from' => '2025-07-01',
        'p_date_to' => '2025-07-15',
        'p_store_id' => null,
        'p_store_filter_type' => 'all',
        'p_account_id' => null,
        'p_keyword' => null,
        'p_limit' => 3,
        'p_offset' => 0
    ]);
    
    echo "✓ RPC call successful<br>";
    echo "Response type: " . gettype($rpcResult) . "<br>";
    echo "Response structure: <pre>" . print_r($rpcResult, true) . "</pre>";
    
    if (isset($rpcResult['data'])) {
        echo "✓ Data array found with " . count($rpcResult['data']) . " entries<br>";
    } else {
        echo "❌ No data array found<br>";
    }
    
    if (isset($rpcResult['total_count'])) {
        echo "✓ Total count found: " . $rpcResult['total_count'] . "<br>";
    } else {
        echo "❌ No total_count found<br>";
    }
    
} catch (Exception $e) {
    echo "<h2>❌ Error Occurred:</h2>";
    echo "<p style='color: red;'>" . $e->getMessage() . "</p>";
    echo "<pre>" . $e->getTraceAsString() . "</pre>";
}

echo "<h2>4. Direct API Test</h2>";
echo '<a href="api.php?action=get_transactions&user_id=0d2e61ad-e230-454e-8b90-efbe1c1a9268&company_id=ebd66ba7-fde7-4332-b6b5-0d8a7f615497&store_id=&date_from=&date_to=&account_name=&created_by=&keyword=" target="_blank">Test API Directly</a>';
?>
