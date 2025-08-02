<?php
// Fix the initializeAccountsData function to include category_tag

$indexFile = '/Applications/XAMPP/xamppfiles/htdocs/mcparrange-main/luxapp/finance/journal-entry/index.php';
$content = file_get_contents($indexFile);

// Pattern to find the second occurrence of initializeAccountsData function
$pattern = '/function initializeAccountsData\(\) \{\s*accountsData = <\?php\s*\$jsAccountsData = \[\];\s*foreach \(\$accounts as \$account\) \{\s*\$jsAccountsData\[\] = \[\s*\'id\' => \$account\[\'account_id\'\],\s*\'name\' => \$account\[\'account_name\'\],\s*\'type\' => \$account\[\'account_type\'\]\s*\];\s*\}\s*echo json_encode\(\$jsAccountsData\);\s*\?>;\s*\}/';

$replacement = 'function initializeAccountsData() {
            accountsData = <?php
                $jsAccountsData = [];
                foreach ($accounts as $account) {
                    $jsAccountsData[] = [
                        \'id\' => $account[\'account_id\'],
                        \'name\' => $account[\'account_name\'],
                        \'type\' => $account[\'account_type\'],
                        \'category_tag\' => $account[\'category_tag\'] ?? null
                    ];
                }
                echo json_encode($jsAccountsData);
            ?>;
        }';

// More specific pattern to find all initializeAccountsData functions
$patterns = [
    '/(\s*function initializeAccountsData\(\) \{\s*accountsData = <\?php\s*\$jsAccountsData = \[\];\s*foreach \(\$accounts as \$account\) \{\s*\$jsAccountsData\[\] = \[\s*\'id\' => \$account\[\'account_id\'\],\s*\'name\' => \$account\[\'account_name\'\],\s*\'type\' => \$account\[\'account_type\'\]\s*\];\s*\}\s*echo json_encode\(\$jsAccountsData\);\s*\?>;\s*\})/s'
];

foreach ($patterns as $pattern) {
    if (preg_match($pattern, $content)) {
        $content = preg_replace($pattern, $replacement, $content);
        echo "Found and replaced pattern\n";
        break;
    }
}

// Write back the content
file_put_contents($indexFile, $content);
echo "File updated successfully\n";

// Verify the changes
$lines = explode("\n", $content);
$found = false;
for ($i = 0; $i < count($lines); $i++) {
    if (strpos($lines[$i], 'category_tag') !== false && strpos($lines[$i], 'account') !== false) {
        echo "Verification: Found category_tag at line " . ($i + 1) . ": " . trim($lines[$i]) . "\n";
        $found = true;
    }
}

if (!$found) {
    echo "Warning: category_tag not found in the updated file\n";
}

?>
