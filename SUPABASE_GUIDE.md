# Supabase Finance System Guide

## 🚨 CRITICAL RULES FOR AI - MUST READ

### ABSOLUTE RESTRICTIONS

1. **NEVER MODIFY EXISTING TABLES**
   - DO NOT rename tables
   - DO NOT alter table structures  
   - DO NOT drop or recreate tables
   - Creating NEW tables is allowed

2. **NO RLS (Row Level Security)**
   - RLS has been completely removed from all tables
   - DO NOT enable RLS on any table
   - DO NOT create any RLS policies
   - DO NOT suggest or implement RLS in any form

3. **MODIFICATION RULES**
   - ✅ ALLOWED: Create NEW functions
   - ✅ ALLOWED: Create NEW tables
   - ✅ ALLOWED: Create NEW views
   - ❌ FORBIDDEN: Modify EXISTING functions without explicit permission
   - ❌ FORBIDDEN: Modify EXISTING tables without explicit permission
   - ❌ FORBIDDEN: Modify EXISTING views without explicit permission

4. **PERMISSION REQUIRED**
   - If you need to modify anything that already exists, you MUST:
     1. Ask for permission first
     2. Explain what you want to change and why
     3. Wait for explicit approval
     4. Only proceed if approved

### Example Permission Request
```
"I need to modify the existing function 'get_user_companies_and_stores' to add a new parameter. 
Reason: [explain why]
Changes: [list specific changes]
May I proceed with this modification?"
```

## 🔑 Supabase Project Information
- **Project URL**: https://atkekzwgukdvucqntryo.supabase.co
- **Project ID**: atkekzwgukdvucqntryo
- **ANON KEY**: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8`

## 🚀 How to Use Supabase

### 1. Required Information
To access Supabase, you need:
```
1. SUPABASE_ANON_KEY: ✅ Stored (see ANON KEY above)
2. Test user_id: Real user UUID (request when needed)
```

### 2. Query User Information
```php
// Step 1: Get user's company/store information
$result = callRPC('get_user_companies_and_stores', ['p_user_id' => $user_id]);

// Result can be in two formats:
// Format 1: Single object (new format)
{
    "user_id": "...",
    "companies": [
        {
            "company_id": "...",
            "company_name": "...",
            "stores": [...]
        }
    ]
}

// Format 2: Array (old format)
[
    {
        "company_id": "...",
        "company_name": "...",
        "store_id": "...",
        "store_name": "..."
    }
]
```

### 3. Step-by-Step Data Query Guide

#### Step 1: Check Company Information
```php
// Get accessible companies for user
$companies = callRPC('get_user_companies_and_stores', ['p_user_id' => $user_id]);
// Select first company
$company_id = $companies[0]['company_id'];
```

#### Step 2: Query Accounts
```php
// Company's account list
$accounts = query('accounts', [
    'company_id' => 'eq.' . $company_id,
    'is_active' => 'eq.true',
    'order' => 'account_name'
]);
```

#### Step 3: Query Cash Locations
```php
// Cash location list
$cash_locations = query('cash_locations', [
    'company_id' => 'eq.' . $company_id,
    'is_active' => 'eq.true'
]);
```

#### Step 4: Query Counterparties
```php
// Counterparty list
$counterparties = query('counterparties', [
    'company_id' => 'eq.' . $company_id,
    'is_active' => 'eq.true',
    'order' => 'name'
]);
```

#### Step 5: Query Transactions
```php
// Recent transactions
$transactions = query('v_journal_lines_readable', [
    'company_id' => 'eq.' . $company_id,
    'order' => 'entry_date.desc,created_at.desc',
    'limit' => 10
]);
```

## 📊 Main View Tables Usage

### 1. v_journal_lines_readable (Transaction History)
```php
// Filter example
$params = [
    'company_id' => 'eq.' . $company_id,
    'store_id' => 'eq.' . $store_id,        // Specific store
    'created_by' => 'eq.' . $user_id,       // Specific user
    'account_id' => 'eq.' . $account_id,    // Specific account
    'entry_date' => 'gte.2024-01-01',       // Date filter
    'order' => 'entry_date.desc',
    'limit' => 100
];
```

### 2. v_balance_sheet_by_store (Balance Sheet)
```php
// Balance sheet data
$balance_sheet = query('v_balance_sheet_by_store', [
    'company_id' => 'eq.' . $company_id
]);

// Calculate totals by account type
$totals = ['asset' => 0, 'liability' => 0, 'equity' => 0];
foreach ($balance_sheet as $item) {
    $totals[$item['account_type']] += $item['amount'];
}
```

### 3. v_income_statement_by_store (Income Statement)
```php
// Income statement data
$income_statement = query('v_income_statement_by_store', [
    'company_id' => 'eq.' . $company_id
]);
```

### 4. cash_locations_with_total_amount (Cash Balances)
```php
// Cash balances by location
$cash_balances = query('cash_locations_with_total_amount', [
    'company_id' => 'eq.' . $company_id
]);
```

## 🔧 RPC Functions Usage

### 1. insert_journal_with_everything (Journal Entry)
```php
$params = [
    'p_base_amount' => 100000,
    'p_company_id' => $company_id,
    'p_created_by' => $user_id,
    'p_description' => 'Cash sales',
    'p_entry_date' => '2024-12-15 00:00:00',
    'p_store_id' => $store_id,
    'p_lines' => json_encode([
        [
            'account_name' => 'Cash',
            'debit' => 100000,
            'credit' => 0
        ],
        [
            'account_name' => 'Sales',
            'debit' => 0,
            'credit' => 100000
        ]
    ])
];

$journal_id = callRPC('insert_journal_with_everything', $params);
```

### 2. get_user_companies_and_stores (Permission Check)
```php
$access = callRPC('get_user_companies_and_stores', [
    'p_user_id' => $user_id
]);
```

## 🛠️ Helper Functions

### REST API Call
```php
function callSupabaseAPI($endpoint, $params = []) {
    $url = 'https://atkekzwgukdvucqntryo.supabase.co/rest/v1' . $endpoint;
    
    if (!empty($params)) {
        $queryParts = [];
        foreach ($params as $key => $value) {
            if ($key === 'order' || $key === 'select') {
                $queryParts[] = $key . '=' . $value;
            } else {
                $queryParts[] = $key . '=' . urlencode($value);
            }
        }
        $url .= '?' . implode('&', $queryParts);
    }
    
    $anon_key = 'eyJhbGciOiJIUzI1N