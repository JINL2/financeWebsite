<?php
require_once '../common/functions.php';

echo "<h1>Debug: Accounts Fetch</h1>\n";

try {
    global $supabase;
    echo "<h2>Testing Supabase Query:</h2>\n";
    
    $response = $supabase->query('accounts', [
        'select' => 'account_id,account_name,account_type,category_tag',
        'order' => 'account_type,account_name',
        'limit' => 5
    ]);
    
    echo "<pre>";
    echo "Response: " . json_encode($response, JSON_PRETTY_PRINT);
    echo "</pre>";
    
    if ($response) {
        echo "<h2>Cash Account specifically:</h2>\n";
        foreach ($response as $account) {
            if ($account['account_name'] === 'Cash') {
                echo "<pre>";
                echo "Cash Account: " . json_encode($account, JSON_PRETTY_PRINT);
                echo "</pre>";
                break;
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?>
