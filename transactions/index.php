<?php
/**
 * Financial Management System - Transaction History (Updated with Date Filter)
 * Uses same approach as dashboard for consistency
 */
require_once '../common/auth.php';
require_once '../common/functions.php';

// 파라미터 받기 및 검증
$user_id = $_GET['user_id'] ?? null;
$company_id = $_GET['company_id'] ?? null;
$store_id = $_GET['store_id'] ?? null;

// 인증 검증
if (!$user_id || !$company_id) {
    header('Location: ../login/');
    exit;
}

// Get user info and accessible companies/stores
$user = getCurrentUser($user_id);
$companies = getUserCompanies($user_id);
$stores = getUserStores($user_id, $company_id);

// Filter parameters
$filters = [
    'store_id' => isset($_GET['store_id']) && $_GET['store_id'] !== '' ? $_GET['store_id'] : null,
    'account_name' => isset($_GET['account_name']) && $_GET['account_name'] !== '' ? $_GET['account_name'] : null,
    'created_by' => isset($_GET['created_by']) && $_GET['created_by'] !== '' ? $_GET['created_by'] : null,
    'date_from' => $_GET['date_from'] ?? null,
    'date_to' => $_GET['date_to'] ?? null,
    'keyword' => isset($_GET['keyword']) && $_GET['keyword'] !== '' ? $_GET['keyword'] : null
];

// Get company currency using common function
$currency_info = getCompanyCurrency($company_id);
$currency = $currency_info['currency_code'];
$currency_symbol = $currency_info['currency_symbol'];
$currency_name = $currency_info['currency_name'];
?>
<!DOCTYPE html>
<html lang="en-US">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Transactions - Financial Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --hover-bg: rgba(37, 99, 235, 0.05);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            background-color: var(--light-bg);
            color: var(--text-primary);
        }

        .navbar {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
            border: none;
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
        }

        .navbar-brand {
            font-weight: 700;
            font-size: 1.25rem;
        }

        .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
            transition: all 0.3s ease;
        }

        .nav-link:hover {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        /* Active page indicator */
        .navbar-nav .nav-link.active {
            color: #ffffff !important;
            background: rgba(37, 99, 235, 0.25) !important;
            border: 1px solid rgba(37, 99, 235, 0.4);
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.3);
            position: relative;
        }

        /* Active page bottom accent line */
        .navbar-nav .nav-link.active::after {
            content: '';
            position: absolute;
            bottom: -6px;
            left: 50%;
            transform: translateX(-50%);
            width: 70%;
            height: 3px;
            background: var(--primary-color);
            border-radius: 2px;
        }

        /* Company dropdown specific styling */
        .navbar .form-select {
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            background: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            min-width: 220px;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            outline: none !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            transform: none !important;
            border-radius: 8px !important;
        }

        .navbar .form-select:hover {
            border-color: rgba(255, 255, 255, 0.6) !important;
            background: rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
        }

        .navbar .form-select option {
            color: var(--text-primary);
            background: white;
            padding: 0.75rem;
        }

        /* Company dropdown label styling */
        .company-dropdown-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .company-dropdown-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
            font-weight: 500;
            white-space: nowrap;
        }

        .page-container {
            padding: 2rem 0;
        }

        .page-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
        }

        /* Filter Section */
        .filter-section {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            margin-bottom: 2rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .store-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1rem;
        }

        .store-filter-btn {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--text-secondary);
            border-radius: 10px;
            text-decoration: none;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .store-filter-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            text-decoration: none;
            transform: translateY(-1px);
        }

        .store-filter-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        /* Date range filter styles */
        .date-range-selector {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        .shortcut-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .shortcut-btn {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--text-secondary);
            border-radius: 10px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .shortcut-btn:hover {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .shortcut-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .custom-date-range {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            margin-top: 0.5rem;
        }

        .custom-date-range input {
            flex: 1;
            max-width: 150px;
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.5rem 1rem;
            font-weight: 500;
        }

        .custom-date-range span {
            color: var(--text-secondary);
            font-weight: 500;
        }

        .custom-date-range input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.2);
        }

        .results-section {
            background: var(--card-bg);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .results-header {
            background: var(--light-bg);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .results-title {
            font-size: 1.25rem;
            font-weight: 700;
            color: var(--text-primary);
            margin: 0;
        }

        .results-count {
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .transaction-table {
            width: 100%;
            border-collapse: collapse;
        }

        .transaction-table th {
            background: var(--light-bg);
            border: none;
            font-weight: 600;
            color: var(--text-secondary);
            padding: 1rem;
            font-size: 0.875rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-align: left;
        }

        .transaction-table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .transaction-table tbody tr:hover {
            background-color: var(--hover-bg);
        }

        .transaction-table tbody tr:last-child td {
            border-bottom: none;
        }

        .amount-positive {
            color: var(--success-color);
            font-weight: 600;
        }

        .amount-negative {
            color: var(--danger-color);
            font-weight: 600;
        }

        .location-badge {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .store-badge {
            background: rgba(100, 116, 139, 0.1);
            color: var(--text-secondary);
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
        }

        /* Journal Entry Grouping */
        .journal-header {
            background-color: #f8f9fa !important;
            font-weight: 500;
        }
        
        .journal-header td {
            padding: 12px 15px !important;
            border-bottom: 2px solid #dee2e6;
        }
        
        .journal-line {
            background-color: #ffffff;
        }
        
        .journal-line:hover {
            background-color: rgba(37, 99, 235, 0.05);
        }
        
        .journal-line td {
            padding: 8px 15px;
        }

        .pagination-container {
            background: var(--light-bg);
            border-top: 1px solid var(--border-color);
            padding: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 1rem;
        }

        .page-btn-new {
            background: white;
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            min-width: 40px;
            text-align: center;
        }

        .page-btn-new:hover:not(:disabled):not(.active) {
            border-color: var(--primary-color);
            color: var(--primary-color);
        }

        .page-btn-new.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
        }

        .page-btn-new:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .pagination-controls-new {
            display: flex;
            align-items: center;
            gap: 0.25rem;
        }

        .pagination-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            color: var(--text-secondary);
            font-size: 0.875rem;
        }

        .page-indicator {
            background: var(--primary-color);
            color: white;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            font-size: 0.875rem;
        }

        .btn-success {
            background: var(--success-color);
            border: 2px solid var(--success-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-success:hover {
            background: #047857;
            border-color: #047857;
            color: white;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
            text-decoration: none;
        }

        @media (max-width: 768px) {
            .pagination-container {
                flex-direction: column;
                text-align: center;
            }
            
            .page-container {
                padding: 1rem 0;
            }
            
            .store-filters {
                justify-content: center;
            }
            
            .shortcut-buttons {
                justify-content: center;
            }
            
            .custom-date-range {
                justify-content: center;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">
                <i class="bi bi-graph-up-arrow me-2"></i>
                Financial Management
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link" href="../dashboard/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../transactions/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-list-ul me-1"></i>Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../journal-entry/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-journal-plus me-1"></i>Journal Entry</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="financialStatementsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-text me-1"></i>Financial Statements
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../balance-sheet/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">Balance Sheet</a></li>
                            <li><a class="dropdown-item" href="../income-statement/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">Income Statement</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-gear me-1"></i>Management
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../cash-control/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">Cash Control</a></li>
                            <li><a class="dropdown-item" href="../employee-salary/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">Employee Salary</a></li>
                        </ul>
                    </li>
                </ul>
                <div class="ms-auto">
                    <span class="navbar-text me-3">
                        <i class="bi bi-person-circle me-1"></i> <?= h($user['full_name'] ?? 'User') ?>
                    </span>
                    <div class="company-dropdown-container">
                        <span class="company-dropdown-label">
                            <i class="bi bi-building me-1"></i>Company:
                        </span>
                        <select class="form-select form-select-sm d-inline-block w-auto" onchange="changeCompany(this.value)" title="Select Company">
                            <?php foreach ($companies as $comp): ?>
                            <option value="<?= $comp['company_id'] ?>" <?= $comp['company_id'] == $company_id ? 'selected' : '' ?>>
                                <?= h($comp['company_name']) ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <a href="../login/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>" class="btn btn-outline-light btn-sm ms-3">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid page-container">
        <!-- Page Header -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h1 class="page-title">Transaction History</h1>
                    <p class="page-subtitle">View and manage all financial transactions</p>
                </div>
                <a href="../journal-entry/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>New Entry
                </a>
            </div>
        </div>

        <!-- Combined Filter Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-lg-6">
                    <h6 class="mb-3 fw-bold text-secondary">Store Filter</h6>
                    <div class="store-filters">
                        <button class="store-filter-btn <?= !$filters['store_id'] ? 'active' : '' ?>" 
                                onclick="filterByStore(null)">All Stores</button>
                        <?php foreach ($stores as $store): ?>
                        <button class="store-filter-btn <?= $filters['store_id'] == $store['store_id'] ? 'active' : '' ?>" 
                                onclick="filterByStore('<?= $store['store_id'] ?>')">
                            <?= h($store['store_name']) ?>
                        </button>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="col-lg-6">
                    <h6 class="mb-3 fw-bold text-secondary">Period Filter</h6>
                    <div class="date-range-selector">
                        <div class="shortcut-buttons">
                            <button class="shortcut-btn" data-range="today">Today</button>
                            <button class="shortcut-btn" data-range="yesterday">Yesterday</button>
                            <button class="shortcut-btn" data-range="this_week">This Week</button>
                            <button class="shortcut-btn active" data-range="this_month">This Month</button>
                        </div>
                        <div class="custom-date-range">
                            <input type="date" id="dateFrom" class="form-control">
                            <span>to</span>
                            <input type="date" id="dateTo" class="form-control">
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Results Section -->
        <div class="results-section" id="results-section">
            <div class="results-header">
                <h5 class="results-title">Recent Journal Entries</h5>
                <span class="results-count" id="results-count">Loading data...</span>
            </div>

            <div class="table-responsive">
                <table class="transaction-table" id="transactions-table">
                    <thead>
                        <tr>
                            <th>Date & Description</th>
                            <th>Account</th>
                            <th>Debit</th>
                            <th>Credit</th>
                            <th>Location</th>
                            <th>Store</th>
                        </tr>
                    </thead>
                    <tbody id="transactions-tbody">
                        <tr>
                            <td colspan="6" class="text-center text-muted">Loading transactions...</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Pagination Controls -->
            <div class="pagination-container" id="pagination-container" style="display: none;">
                <!-- Pagination will be generated here -->
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Current parameters
        const params = {
            user_id: '<?= $user_id ?>',
            company_id: '<?= $company_id ?>',
            store_id: '<?= $filters['store_id'] ?>'
        };
        
        // Currency
        let currency = '<?= $currency ?>';
        let currencySymbol = '<?= $currency_symbol ?>';
        
        // Date range variables
        let dateFrom = null;
        let dateTo = null;
        
        // Pagination variables
        let currentPage = 1;
        let totalPages = 1;
        let totalEntries = 0;
        let currentLimit = 50;

        // Format number with commas
        function numberWithCommas(x) {
            return x.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",");
        }

        // Format currency
        function formatCurrency(amount) {
            return currencySymbol + numberWithCommas(Math.round(amount));
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { year: 'numeric', month: 'short', day: 'numeric' });
        }

        // Get date range based on shortcut
        function getDateRange(range) {
            const today = new Date();
            const yesterday = new Date(today);
            yesterday.setDate(today.getDate() - 1);
            
            switch(range) {
                case 'today':
                    return {
                        from: formatDateForInput(today),
                        to: formatDateForInput(today)
                    };
                case 'yesterday':
                    return {
                        from: formatDateForInput(yesterday),
                        to: formatDateForInput(yesterday)
                    };
                case 'this_week':
                    const weekStart = new Date(today);
                    weekStart.setDate(today.getDate() - today.getDay());
                    return {
                        from: formatDateForInput(weekStart),
                        to: formatDateForInput(today)
                    };
                case 'this_month':
                    const monthStart = new Date(today.getFullYear(), today.getMonth(), 1);
                    return {
                        from: formatDateForInput(monthStart),
                        to: formatDateForInput(today)
                    };
                default:
                    return {
                        from: formatDateForInput(new Date(today.getFullYear(), today.getMonth(), 1)),
                        to: formatDateForInput(today)
                    };
            }
        }

        // Format date for input
        function formatDateForInput(date) {
            return date.toISOString().split('T')[0];
        }

        // Initialize date range
        function initializeDateRange() {
            const defaultRange = getDateRange('this_month');
            dateFrom = defaultRange.from;
            dateTo = defaultRange.to;
            
            document.getElementById('dateFrom').value = dateFrom;
            document.getElementById('dateTo').value = dateTo;
        }

        // Filter by store
        function filterByStore(storeId) {
            // Update active button
            document.querySelectorAll('.store-filter-btn').forEach(btn => {
                btn.classList.remove('active');
            });
            
            if (storeId === null) {
                // All stores selected
                document.querySelector('.store-filter-btn[onclick="filterByStore(null)"]').classList.add('active');
                params.store_id = null;
            } else {
                // Specific store selected
                document.querySelector(`.store-filter-btn[onclick="filterByStore('${storeId}')"]`).classList.add('active');
                params.store_id = storeId;
            }
            
            // Reload data with new filter
            loadTransactions(1);
        }

        // Handle shortcut button clicks
        document.querySelectorAll('.shortcut-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                // Remove active class from all buttons
                document.querySelectorAll('.shortcut-btn').forEach(b => b.classList.remove('active'));
                // Add active class to clicked button
                this.classList.add('active');
                
                // Get date range and update inputs
                const range = getDateRange(this.dataset.range);
                dateFrom = range.from;
                dateTo = range.to;
                
                document.getElementById('dateFrom').value = dateFrom;
                document.getElementById('dateTo').value = dateTo;
                
                // Reload data
                loadTransactions(1);
            });
        });

        // Handle custom date range changes
        document.getElementById('dateFrom').addEventListener('change', function() {
            dateFrom = this.value;
            // Remove active class from shortcut buttons
            document.querySelectorAll('.shortcut-btn').forEach(b => b.classList.remove('active'));
            loadTransactions(1);
        });

        document.getElementById('dateTo').addEventListener('change', function() {
            dateTo = this.value;
            // Remove active class from shortcut buttons
            document.querySelectorAll('.shortcut-btn').forEach(b => b.classList.remove('active'));
            loadTransactions(1);
        });

        // Load transactions data
        async function loadTransactions(page = 1) {
            try {
                // Show loading
                document.getElementById('results-count').textContent = 'Loading data...';
                
                // Build API URL
                let apiUrl = `api.php?action=get_transactions&user_id=${params.user_id}&company_id=${params.company_id}&page=${page}&limit=${currentLimit}`;
                if (params.store_id) {
                    apiUrl += `&store_id=${params.store_id}`;
                }
                if (dateFrom) {
                    apiUrl += `&date_from=${dateFrom}`;
                }
                if (dateTo) {
                    apiUrl += `&date_to=${dateTo}`;
                }
                
                const response = await fetch(apiUrl);
                const data = await response.json();
                
                if (data.success) {
                    // Update currency symbol if provided
                    if (data.currency_symbol) {
                        currencySymbol = data.currency_symbol;
                    }
                    
                    // Update transaction table
                    updateTransactionTable(data.data);
                    
                    // Update pagination
                    updatePagination(data.pagination);
                    
                    // Update results count
                    updateResultsCount(data.pagination);
                    
                    // Show results section
                    document.getElementById('results-section').style.display = 'block';
                }
                
            } catch (error) {
                console.error('Error loading transactions:', error);
                document.getElementById('results-count').textContent = 'Error loading data. Please try again.';
            }
        }

        // Update transaction table with grouped journal entries (same as dashboard)
        function updateTransactionTable(journalEntries) {
            const tbody = document.querySelector('#transactions-table tbody');
            tbody.innerHTML = '';
            
            if (journalEntries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No transactions found.</td></tr>';
                return;
            }
            
            journalEntries.forEach(entry => {
                // Journal Entry header row
                const headerRow = `
                    <tr class="table-secondary journal-header">
                        <td colspan="6">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <strong>${formatDate(entry.entry_date)}</strong>
                                    ${entry.description ? ` - ${entry.description}` : ''}
                                    ${entry.counterparty_name ? ` <span class="location-badge"><i class="bi bi-building"></i> ${entry.counterparty_name}</span>` : ''}
                                </div>
                                <div class="text-muted small">
                                    <i class="bi bi-person"></i> ${entry.created_by}
                                    <span class="ms-2">Total: ${formatCurrency(entry.total_debit)}</span>
                                </div>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += headerRow;
                
                // Each journal line
                if (entry.lines && entry.lines.length > 0) {
                    entry.lines.forEach(line => {
                        // Location/Party info
                        let locationPartyInfo = '';
                        
                        // Cash account - show cash location
                        if (line.account_name === 'Cash' && line.cash_location_name) {
                            locationPartyInfo = `<span class="location-badge"><i class="bi bi-geo-alt"></i> ${line.cash_location_name}</span>`;
                        }
                        // Debt accounts - show counterparty
                        else if ((line.account_name === 'Notes Payable' || 
                                  line.account_name === 'Accounts Payable' || 
                                  line.account_name === 'Notes Receivable' ||
                                  line.account_name === 'Accounts Receivable') && 
                                 line.counterparty_name) {
                            locationPartyInfo = `<span class="location-badge"><i class="bi bi-building"></i> ${line.counterparty_name}</span>`;
                        }
                        
                        const lineRow = `
                            <tr class="journal-line">
                                <td>
                                    <div class="fw-bold">${formatDate(entry.entry_date)}</div>
                                    <div class="text-muted small">${line.description || ''}</div>
                                </td>
                                <td>${line.account_name || ''}</td>
                                <td><span class="amount-positive">${line.debit > 0 ? formatCurrency(line.debit) : '-'}</span></td>
                                <td><span class="amount-negative">${line.credit > 0 ? formatCurrency(line.credit) : '-'}</span></td>
                                <td>${locationPartyInfo}</td>
                                <td><span class="store-badge">${line.store_name || entry.company_name || ''}</span></td>
                            </tr>
                        `;
                        tbody.innerHTML += lineRow;
                    });
                }
            });
        }

        // Update pagination controls
        function updatePagination(pagination) {
            currentPage = pagination.page;
            totalPages = pagination.totalPages;
            totalEntries = pagination.total;
            
            const container = document.getElementById('pagination-container');
            if (!container) return;
            
            if (totalPages <= 1) {
                container.style.display = 'none';
                return;
            }
            
            container.style.display = 'flex';
            container.innerHTML = generatePaginationHTML();
        }

        // Generate pagination HTML
        function generatePaginationHTML() {
            let html = `
                <div class="pagination-info">
                    <span class="page-indicator">${currentPage}/${totalPages}</span>
                    <span>Total: ${totalEntries} entries</span>
                </div>
                <div class="pagination-controls-new">
            `;
            
            // Previous button
            if (currentPage > 1) {
                html += `<button class="page-btn-new" onclick="goToPage(${currentPage - 1})">&laquo; Previous</button>`;
            }
            
            // Page numbers
            const maxVisible = 5;
            let startPage = Math.max(1, currentPage - Math.floor(maxVisible / 2));
            let endPage = Math.min(totalPages, startPage + maxVisible - 1);
            
            for (let i = startPage; i <= endPage; i++) {
                const isActive = i === currentPage;
                html += `
                    <button class="page-btn-new ${isActive ? 'active' : ''}" 
                            onclick="goToPage(${i})" 
                            ${isActive ? 'disabled' : ''}>
                        ${i}
                    </button>
                `;
            }
            
            // Next button
            if (currentPage < totalPages) {
                html += `<button class="page-btn-new" onclick="goToPage(${currentPage + 1})">Next &raquo;</button>`;
            }
            
            html += '</div>';
            return html;
        }

        // Go to specific page
        function goToPage(page) {
            if (page < 1 || page > totalPages || page === currentPage) return;
            loadTransactions(page);
        }

        // Update results count display
        function updateResultsCount(pagination) {
            const resultsCount = document.getElementById('results-count');
            if (!resultsCount) return;
            
            const startEntry = ((pagination.page - 1) * currentLimit) + 1;
            const endEntry = Math.min(pagination.page * currentLimit, pagination.total);
            
            resultsCount.textContent = `Showing ${startEntry}-${endEntry} of ${pagination.total} entries`;
        }

        // Change company
        function changeCompany(companyId) {
            // Stay on transactions page but change company
            window.location.href = `?user_id=${params.user_id}&company_id=${companyId}`;
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeDateRange();
            loadTransactions(1);
        });
    </script>
</body>
</html>