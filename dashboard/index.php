<?php
/**
 * Financial Management System - Modern Dashboard
 * Updated with new design system
 */
require_once '../common/auth.php';
require_once '../common/functions.php';
require_once '../common/db.php';

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
// Companies will be loaded dynamically via API
$companies = [];
$stores = getUserStores($user_id, $company_id);

// Get company currency using common function
$currency_info = getCompanyCurrency($company_id);
$currency = $currency_info['currency_code'];
$currency_symbol = $currency_info['currency_symbol'];
$currency_name = $currency_info['currency_name'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Financial Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/modern-dropdown.css" rel="stylesheet">
    <style>
        :root {
            --primary-color: #2563eb;
            --secondary-color: #64748b;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --info-color: #0891b2;
            --light-bg: #f8fafc;
            --card-bg: #ffffff;
            --border-color: #e2e8f0;
            --text-primary: #1e293b;
            --text-secondary: #64748b;
            --text-muted: #94a3b8;
            --hover-bg: rgba(37, 99, 235, 0.05);
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
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

        /* Performance Section */
        .performance-section {
            margin-bottom: 2rem;
        }

        .performance-title {
            font-size: 1.75rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        /* Financial Cards */
        .financial-overview {
            margin-bottom: 2rem;
        }

        .financial-card {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            transition: all 0.3s ease;
            height: 100%;
        }

        .financial-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
        }

        .financial-card-header {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }

        .financial-card-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 1rem;
            font-size: 1.25rem;
        }

        .icon-revenue {
            background: rgba(5, 150, 105, 0.1);
            color: var(--success-color);
        }

        .icon-expense {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }

        .financial-card-title {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin: 0;
        }

        .financial-card-value {
            font-size: 1.75rem;
            font-weight: 700;
            margin: 0;
            line-height: 1.2;
        }

        .value-positive {
            color: var(--success-color);
        }

        .value-negative {
            color: var(--danger-color);
        }

        .value-neutral {
            color: var(--text-primary);
        }


        /* Quick Actions */
        .quick-actions {
            margin-bottom: 2rem;
        }

        .quick-actions-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 1.5rem;
            color: var(--text-primary);
        }

        .action-buttons {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
        }

        .action-btn {
            display: flex;
            align-items: center;
            padding: 1rem 1.5rem;
            background: var(--card-bg);
            border: 2px solid var(--border-color);
            border-radius: 12px;
            text-decoration: none;
            color: var(--text-primary);
            font-weight: 600;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-sm);
        }

        .action-btn:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-md);
            text-decoration: none;
            color: var(--text-primary);
        }

        .action-btn.btn-income {
            border-color: var(--success-color);
        }

        .action-btn.btn-income:hover {
            background: var(--success-color);
            color: white;
        }

        .action-btn.btn-expense {
            border-color: var(--danger-color);
        }

        .action-btn.btn-expense:hover {
            background: var(--danger-color);
            color: white;
        }

        .action-btn.btn-report {
            border-color: var(--info-color);
        }

        .action-btn.btn-report:hover {
            background: var(--info-color);
            color: white;
        }

        .action-btn i {
            margin-right: 0.75rem;
            font-size: 1.25rem;
        }

        /* Recent Transactions */
        .recent-transactions {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .transactions-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
        }

        .transactions-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0;
            color: var(--text-primary);
        }

        .view-all-btn {
            color: var(--primary-color);
            text-decoration: none;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .view-all-btn:hover {
            color: var(--primary-color);
            text-decoration: none;
        }

        .transaction-table {
            border-radius: 12px;
            overflow: hidden;
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
        }

        .transaction-table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .transaction-table tbody tr:hover {
            background-color: rgba(37, 99, 235, 0.05);
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

        @media (max-width: 768px) {
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
            
            .financial-card-value {
                font-size: 1.5rem;
            }

            .action-buttons {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark">
        <div class="container-fluid">
            <a class="navbar-brand" href="../dashboard/">
                <i class="bi bi-graph-up-arrow me-2"></i>
                Financial Management
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav">
                    <li class="nav-item">
                        <a class="nav-link active" href="../dashboard/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../transactions/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-list-ul me-1"></i>Transactions</a>
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
                    <div class="company-dropdown-container me-3">
                        <span class="company-dropdown-label">
                            <i class="bi bi-building me-1"></i>Company:
                        </span>
                        <select id="companySelect" class="form-select form-select-sm d-inline-block w-auto" onchange="changeCompany(this.value)" title="Select Company">
                            <option value="">Loading companies...</option>
                        </select>
                    </div>
                    <a href="../login/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>" class="btn btn-outline-light btn-sm">
                        <i class="bi bi-box-arrow-right me-1"></i>Logout
                    </a>
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid page-container">

        <!-- Loading indicator -->
        <div id="loading" class="text-center my-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading data...</p>
        </div>

        <!-- Error alert -->
        <div id="error-alert" class="alert alert-danger" style="display: none;"></div>

        <!-- Performance Section -->
        <div class="performance-section" id="performance-section" style="display: none;">
            <h2 class="performance-title">Performance</h2>
            <div class="financial-overview">
                <div class="row g-4">
                    <div class="col-lg-6">
                        <div class="financial-card">
                            <div class="financial-card-header">
                                <div class="financial-card-icon icon-revenue">
                                    <i class="bi bi-arrow-up-circle"></i>
                                </div>
                                <div>
                                    <h6 class="financial-card-title">Revenue</h6>
                                </div>
                            </div>
                            <h3 class="financial-card-value value-positive total-income">₫0</h3>
                        </div>
                    </div>
                    <div class="col-lg-6">
                        <div class="financial-card">
                            <div class="financial-card-header">
                                <div class="financial-card-icon icon-expense">
                                    <i class="bi bi-arrow-down-circle"></i>
                                </div>
                                <div>
                                    <h6 class="financial-card-title">Operating Expense</h6>
                                </div>
                            </div>
                            <h3 class="financial-card-value value-negative total-expense">₫0</h3>
                        </div>
                    </div>
                </div>
            </div>
        </div>


        <!-- Combined Filter Section -->
        <div class="filter-section">
            <div class="row">
                <div class="col-lg-6">
                    <h6 class="mb-3 fw-bold text-secondary">Store Filter</h6>
                    <div id="storeFilters" class="store-filters">
                        <button class="store-filter-btn active" onclick="filterByStore(null)">All Stores</button>
                        <!-- Stores will be loaded dynamically -->
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

        <!-- Quick Actions -->
        <div class="quick-actions" id="quick-actions" style="display: none;">
            <h5 class="quick-actions-title">Quick Actions</h5>
            <div class="action-buttons">
                <a href="../journal-entry/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>&type=income" 
                   class="action-btn btn-income">
                    <i class="bi bi-plus-circle"></i>
                    Add Income
                </a>
                <a href="../journal-entry/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>&type=expense" 
                   class="action-btn btn-expense">
                    <i class="bi bi-dash-circle"></i>
                    Add Expense
                </a>
                <a href="../balance-sheet/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>" 
                   class="action-btn btn-report">
                    <i class="bi bi-file-earmark-bar-graph"></i>
                    Balance Sheet
                </a>
                <a href="../income-statement/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>" 
                   class="action-btn btn-report">
                    <i class="bi bi-graph-up"></i>
                    Income Statement
                </a>
            </div>
        </div>

        <!-- Recent Transactions -->
        <div class="recent-transactions" id="recent-transactions" style="display: none;">
            <div class="transactions-header">
                <h5 class="transactions-title">Recent Journal Entries</h5>
                <a href="../transactions/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?><?= $store_id ? '&store_id=' . $store_id : '' ?>" class="view-all-btn">
                    View All
                    <i class="bi bi-arrow-right"></i>
                </a>
            </div>
            
            <div class="table-responsive">
                <table class="table transaction-table">
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
                    <tbody>
                        <tr>
                            <td colspan="6" class="text-center text-muted">Loading transactions...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Current parameters
        const params = {
            user_id: '<?= $user_id ?>',
            company_id: '<?= $company_id ?>',
            store_id: '<?= $store_id ?>'
        };
        
        // Currency
        let currency = '<?= $currency ?>';
        let currencySymbol = '<?= $currency_symbol ?>';
        
        // Global state for user companies and stores
        let userCompaniesData = null;
        
        // Date range variables
        let dateFrom = null;
        let dateTo = null;

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
            // 네비게이션 상태 업데이트
            if (window.updateNavigationStore) {
                window.updateNavigationStore(storeId);
            }
            
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
            
            // Update URL to reflect the filter
            let newUrl = `?user_id=${params.user_id}&company_id=${params.company_id}`;
            if (storeId) {
                newUrl += `&store_id=${storeId}`;
            }
            
            // Update browser history without reloading
            history.pushState(null, null, newUrl);
            
            // Reload data with new filter
            loadDashboardData();
            
            console.log('Store filter changed to:', storeId);
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
                loadDashboardData();
            });
        });

        // Handle custom date range changes
        document.getElementById('dateFrom').addEventListener('change', function() {
            dateFrom = this.value;
            // Remove active class from shortcut buttons
            document.querySelectorAll('.shortcut-btn').forEach(b => b.classList.remove('active'));
            loadDashboardData();
        });

        document.getElementById('dateTo').addEventListener('change', function() {
            dateTo = this.value;
            // Remove active class from shortcut buttons
            document.querySelectorAll('.shortcut-btn').forEach(b => b.classList.remove('active'));
            loadDashboardData();
        });

        // Load dashboard data
        async function loadDashboardData() {
            // Show loading
            document.getElementById('loading').style.display = 'block';
            document.getElementById('error-alert').style.display = 'none';
            
            try {
                // Build API URL with date parameters
                let apiUrl = `api.php?action=get_summary&user_id=${params.user_id}&company_id=${params.company_id}`;
                
                // 사용자의 컴퓨터/핸드폰 현재 날짜를 yyyy-MM-dd 형태로 전송
                const currentDate = new Date().toISOString().split('T')[0]; // yyyy-MM-dd 형태
                apiUrl += `&request_date=${currentDate}`;
                
                if (params.store_id) {
                    apiUrl += `&store_id=${params.store_id}`;
                }
                if (dateFrom) {
                    apiUrl += `&date_from=${dateFrom}`;
                }
                if (dateTo) {
                    apiUrl += `&date_to=${dateTo}`;
                }
                
                // Fetch summary data from dashboard API
                const summaryResponse = await fetch(apiUrl);
                const summaryData = await summaryResponse.json();
                
                if (summaryData.success) {
                    // Update currency symbol if provided
                    if (summaryData.currency_symbol) {
                        currencySymbol = summaryData.currency_symbol;
                    }
                    
                    // Income statement data
                    const income = summaryData.data.income;
                    const totalIncome = parseFloat(income.total_income) || 0;
                    const totalExpense = parseFloat(income.total_expense) || 0;
                    const netIncome = parseFloat(income.net_income) || 0;
                    
                    // Update UI - safely update elements
                    const totalIncomeElement = document.querySelector('.total-income');
                    const totalExpenseElement = document.querySelector('.total-expense');
                    
                    if (totalIncomeElement) {
                        totalIncomeElement.textContent = formatCurrency(totalIncome);
                    }
                    if (totalExpenseElement) {
                        totalExpenseElement.textContent = formatCurrency(totalExpense);
                    }
                    
                    // Show sections
                    document.getElementById('performance-section').style.display = 'block';
                    document.getElementById('quick-actions').style.display = 'block';
                }
                
                // Fetch recent transactions from dashboard API
                let transUrl = `api.php?action=get_recent_transactions&user_id=${params.user_id}&company_id=${params.company_id}`;
                if (params.store_id) {
                    transUrl += `&store_id=${params.store_id}`;
                }
                if (dateFrom) {
                    transUrl += `&date_from=${dateFrom}`;
                }
                if (dateTo) {
                    transUrl += `&date_to=${dateTo}`;
                }
                
                const transResponse = await fetch(transUrl);
                const transData = await transResponse.json();
                
                if (transData.success) {
                    updateTransactionTable(transData.data);
                    document.getElementById('recent-transactions').style.display = 'block';
                }
                
            } catch (error) {
                console.error('Error loading dashboard data:', error);
                document.getElementById('error-alert').textContent = 'Failed to load data. Please try again.';
                document.getElementById('error-alert').style.display = 'block';
            } finally {
                // Hide loading
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Update transaction table with grouped journal entries
        function updateTransactionTable(journalEntries) {
            const tbody = document.querySelector('.transaction-table tbody');
            tbody.innerHTML = '';
            
            if (journalEntries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted">No transactions found.</td></tr>';
                return;
            }
            
            journalEntries.forEach(entry => {
                // Journal Entry 헤더 행
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
                
                // 각 라인 표시
                if (entry.lines && entry.lines.length > 0) {
                    entry.lines.forEach((line, index) => {
                        // Location/Party 정보 결정
                        let locationPartyInfo = '';
                        
                        // Cash 계정인 경우 - 현금 위치만 표시
                        if (line.account_name === 'Cash' && line.cash_location_name) {
                            locationPartyInfo = `<span class="location-badge"><i class="bi bi-geo-alt"></i> ${line.cash_location_name}</span>`;
                        }
                        // Debt 계정인 경우 - 거래처만 표시
                        else if ((line.account_name === 'Notes Payable' || 
                                  line.account_name === 'Accounts Payable' || 
                                  line.account_name === 'Notes Receivable' ||
                                  line.account_name === 'Accounts Receivable') && 
                                 entry.counterparty_name) {
                            locationPartyInfo = `<span class="location-badge"><i class="bi bi-building"></i> ${entry.counterparty_name}</span>`;
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

        // Load user companies and stores from Supabase
        async function loadUserCompaniesAndStores() {
            try {
                // Create form data for the RPC call
                const formData = new FormData();
                formData.append('action', 'get_user_companies_and_stores');
                formData.append('user_id', params.user_id);
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.data) {
                    userCompaniesData = data.data;
                    
                    // SessionStorage에 저장 (다른 페이지에서 재사용)
                    sessionStorage.setItem('userCompaniesData', JSON.stringify(userCompaniesData));
                    console.log('💾 User companies data saved to SessionStorage');
                    
                    updateCompanyDropdown();
                    console.log('User companies loaded:', userCompaniesData);
                } else {
                    console.error('Failed to load user companies:', data.error || 'Unknown error');
                    // Fallback to current state if API fails
                    document.getElementById('companySelect').innerHTML = 
                        `<option value="${params.company_id}" selected>Current Company</option>`;
                }
            } catch (error) {
                console.error('Error loading user companies:', error);
                // Fallback to current state if API fails
                document.getElementById('companySelect').innerHTML = 
                    `<option value="${params.company_id}" selected>Current Company</option>`;
            }
        }
        
        // Update company dropdown with loaded data
        function updateCompanyDropdown() {
            const select = document.getElementById('companySelect');
            if (!userCompaniesData || !userCompaniesData.companies) {
                select.innerHTML = '<option value="">No companies available</option>';
                return;
            }
            
            select.innerHTML = '';
            
            userCompaniesData.companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.company_id;
                option.textContent = company.company_name;
                option.selected = company.company_id === params.company_id;
                select.appendChild(option);
            });
            
            // 현재 선택된 회사의 Store Filter 업데이트
            updateStoreFilter(params.company_id);
            
            // NavigationState에 데이터 저장 (다른 페이지에서 사용)
            if (window.NavigationState && window.NavigationState.setUserData) {
                window.NavigationState.setUserData(userCompaniesData);
            }
        }
        
        // Update store filter based on selected company
        function updateStoreFilter(companyId) {
            const storeFiltersContainer = document.getElementById('storeFilters');
            if (!userCompaniesData || !userCompaniesData.companies) {
                return;
            }
            
            // 선택된 회사 찾기
            const selectedCompany = userCompaniesData.companies.find(company => company.company_id === companyId);
            
            if (!selectedCompany) {
                storeFiltersContainer.innerHTML = '<button class="store-filter-btn active" onclick="filterByStore(null)">All Stores</button>';
                return;
            }
            
            // Store Filter 버튼들 생성
            let storeFilterHTML = '<button class="store-filter-btn active" onclick="filterByStore(null)">All Stores</button>';
            
            if (selectedCompany.stores && selectedCompany.stores.length > 0) {
                selectedCompany.stores.forEach(store => {
                    const isActive = params.store_id === store.store_id ? 'active' : '';
                    storeFilterHTML += `
                        <button class="store-filter-btn ${isActive}" onclick="filterByStore('${store.store_id}')">
                            ${store.store_name}
                        </button>`;
                });
            }
            
            storeFiltersContainer.innerHTML = storeFilterHTML;
        }
        
        // Change company
        function changeCompany(companyId) {
            if (companyId && companyId !== params.company_id) {
                // 네비게이션 상태 업데이트
                if (window.updateNavigationCompany) {
                    window.updateNavigationCompany(companyId);
                }
                
                // Store Filter를 먼저 업데이트 (페이지 이동 전에 미리보기)
                updateStoreFilter(companyId);
                
                // 파라미터 업데이트
                params.company_id = companyId;
                params.store_id = null; // 회사 변경 시 스토어 초기화
                
                // URL 업데이트
                const newUrl = `?user_id=${params.user_id}&company_id=${companyId}`;
                history.pushState(null, null, newUrl);
                
                // 데이터 다시 로드
                loadDashboardData();
                
                console.log('Company changed to:', companyId);
            }
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            initializeDateRange();
            loadUserCompaniesAndStores(); // Load companies first
            loadDashboardData();
        });
    </script>
    
    <!-- Navigation Enhancement Script -->
    <script src="../assets/js/navigation-enhancement.js"></script>
</body>
</html>