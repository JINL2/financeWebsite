<?php
/**
 * Financial Management System - Employee Salary Management
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
    <title>Employee Salary - Financial Management System</title>
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
            --shadow-lg: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
            --gradient-primary: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
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

        /* Filter Card Styling */
        .filter-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
            overflow: visible;
        }

        .filter-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-title {
            margin: 0;
            font-weight: 600;
            font-size: 1rem;
            color: var(--text-primary);
        }

        .filter-content {
            padding: 1.5rem;
        }

        .form-label {
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
        }

        .form-select, .form-control {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem 1rem;
            font-size: 0.9rem;
            transition: all 0.3s ease;
        }

        .form-select:focus, .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        /* Search Button - Exact same style as Balance Sheet */
        .btn.btn-primary.btn-search,
        .btn-primary.btn-search,
        #refreshBtn.btn-primary,
        button#refreshBtn {
            background: var(--gradient-primary) !important;
            border: none !important;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white !important;
        }

        .btn.btn-primary.btn-search:hover,
        .btn-primary.btn-search:hover,
        #refreshBtn.btn-primary:hover,
        button#refreshBtn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3) !important;
            color: white !important;
        }

        /* Export Excel Button - Modern gradient style */
        #exportExcelBtn {
            background: linear-gradient(135deg, #10b981 0%, #059669 50%, #047857 100%) !important;
            border: none !important;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            color: white !important;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.2);
        }

        #exportExcelBtn:hover {
            transform: translateY(-2px) !important;
            box-shadow: 0 8px 25px rgba(16, 185, 129, 0.4) !important;
            background: linear-gradient(135deg, #059669 0%, #047857 50%, #065f46 100%) !important;
            color: white !important;
        }

        #exportExcelBtn:active {
            transform: translateY(0px) !important;
            box-shadow: 0 4px 15px rgba(16, 185, 129, 0.3) !important;
        }

        /* Date Picker Styling */
        .date-picker-container {
            position: relative;
            overflow: visible;
        }

        .date-picker-input {
            width: 100%;
            padding: 0.75rem 1rem;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            font-size: 0.9rem;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .date-picker-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        .quick-period-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .quick-period-buttons .btn {
            border-radius: 6px;
            font-size: 0.8rem;
            padding: 0.4rem 0.8rem;
        }

        .calendar-dropdown {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 12px;
            box-shadow: var(--shadow-lg);
            z-index: 9999;
            display: none;
            margin-top: 0.5rem;
            max-height: 400px;
            overflow-y: auto;
        }

        .calendar-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 1rem;
            border-bottom: 1px solid var(--border-color);
            background: var(--light-bg);
        }

        .calendar-nav-btn, .calendar-close-btn {
            background: none;
            border: none;
            padding: 0.5rem;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .calendar-nav-btn:hover, .calendar-close-btn:hover {
            background: var(--hover-bg);
        }

        .year-display {
            font-weight: 600;
            color: var(--text-primary);
        }

        .year-month-grid {
            padding: 1rem;
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 0.5rem;
        }

        .year-month-item {
            padding: 0.75rem;
            text-align: center;
            border-radius: 8px;
            cursor: pointer;
            transition: all 0.3s ease;
            border: 1px solid transparent;
        }

        .year-month-item:hover {
            background: var(--hover-bg);
            border-color: var(--primary-color);
        }

        .calendar-instruction {
            padding: 0.75rem 1rem;
            background: var(--light-bg);
            border-top: 1px solid var(--border-color);
            font-size: 0.8rem;
            color: var(--text-secondary);
            text-align: center;
        }

        .form-check {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .form-check-input {
            margin: 0;
        }

        /* Table Header Styling - Same as Search Button */
        .table-dark {
            background: var(--gradient-primary) !important;
            border: none !important;
        }
        
        .table-dark th {
            background: transparent !important;
            border: none !important;
            color: white !important;
        }

        @media (max-width: 768px) {
            .page-container {
                padding: 1rem 0;
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
                        <a class="nav-link" href="../dashboard/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
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
                        <a class="nav-link dropdown-toggle active" href="#" id="managementDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
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
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Employee Salary Management</h1>
            <p class="page-subtitle">Manage employee salaries and payroll information</p>
        </div>

        <!-- Filter Section -->
        <div class="filter-card mb-4">
            <div class="filter-header">
                <h6 class="filter-title">
                    <i class="bi bi-funnel me-2"></i>
                    Filter Options
                </h6>
            </div>
            
            <div class="filter-content">
                <div class="row g-3">
                    <!-- Company Filter -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Company</label>
                        <select class="form-select" id="companyFilter">
                            <option value="">Select Company</option>
                        </select>
                    </div>
                    
                    <!-- Store Filter -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">Store Filter</label>
                        <select class="form-select" id="storeFilter">
                            <option value="">All Stores</option>
                        </select>
                    </div>
                    
                    <!-- Date Selection -->
                    <div class="col-md-4">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-event me-1"></i>Select Period
                        </label>
                        <div class="date-picker-container">
                            <input type="text" 
                                   id="datePicker" 
                                   class="date-picker-input" 
                                   placeholder="August 1-31, 2025"
                                   readonly>
                            
                            <!-- Quick Period Selection Buttons -->
                            <div class="quick-period-buttons mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="thisMonthBtn">This Month</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="lastMonthBtn">Last Month</button>
                            </div>
                            
                            <div class="calendar-dropdown" id="calendarDropdown">
                                <div class="calendar-header">
                                    <button type="button" class="calendar-nav-btn" id="prevYear">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <span id="currentYearDisplay" class="year-display">Year Range</span>
                                    <button type="button" class="calendar-nav-btn" id="nextYear">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                    <button type="button" class="calendar-close-btn" id="closeCalendar">
                                        <i class="bi bi-x-lg"></i>
                                    </button>
                                </div>
                                <div class="year-month-grid" id="yearMonthGrid">
                                    <!-- Dynamic content will be generated here -->
                                </div>
                                <div class="calendar-instruction">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Click on any month to select that period
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-12">
                        <!-- Search Button -->
                        <button class="btn btn-primary btn-search" id="refreshBtn">
                            <i class="bi bi-search me-2"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/@supabase/supabase-js@2"></script>
    <!-- SheetJS for Excel Export -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
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

        // Load user companies and stores from SessionStorage
        function loadUserCompaniesAndStores() {
            try {
                // Load from SessionStorage
                const storedData = sessionStorage.getItem('userCompaniesData');
                if (storedData) {
                    userCompaniesData = JSON.parse(storedData);
                    console.log('💾 User companies data loaded from SessionStorage');
                    updateCompanyDropdown();
                } else {
                    console.warn('⚠️ No user companies data found in SessionStorage');
                    // Fallback to current state
                    document.getElementById('companySelect').innerHTML = 
                        `<option value="${params.company_id}" selected>Current Company</option>`;
                }
            } catch (error) {
                console.error('Error loading user companies from SessionStorage:', error);
                // Fallback to current state
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
            
            // NavigationState에 데이터 저장 (다른 페이지에서 사용)
            if (window.NavigationState && window.NavigationState.setUserData) {
                window.NavigationState.setUserData(userCompaniesData);
            }
            
            // Also update the filter company dropdown
            updateFilterCompanyDropdown();
            
            // Initialize store filter
            updateStoreFilter();
        }
        
        // Change company
        function changeCompany(companyId) {
            if (companyId && companyId !== params.company_id) {
                // 네비게이션 상태 업데이트
                if (window.updateNavigationCompany) {
                    window.updateNavigationCompany(companyId);
                }
                
                // 새로운 URL로 이동
                window.location.href = `?user_id=${params.user_id}&company_id=${companyId}`;
            }
        }

        // Update filter company dropdown
        function updateFilterCompanyDropdown() {
            const select = document.getElementById('companyFilter');
            if (!userCompaniesData || !userCompaniesData.companies) {
                select.innerHTML = '<option value="">No companies available</option>';
                return;
            }
            
            select.innerHTML = '<option value="">Select Company</option>';
            
            userCompaniesData.companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.company_id;
                option.textContent = company.company_name;
                option.selected = company.company_id === params.company_id;
                select.appendChild(option);
            });
            
            // Add event listener for company filter change
            select.addEventListener('change', function() {
                updateStoreFilter(); // Update stores when company changes
            });
        }
        
        // Update store filter
        function updateStoreFilter() {
            const select = document.getElementById('storeFilter');
            select.innerHTML = '<option value="">All Stores</option>';
            
            if (!userCompaniesData || !userCompaniesData.companies) {
                console.log('No companies data available');
                return;
            }
            
            // Get currently selected company from the filter dropdown
            const companyFilter = document.getElementById('companyFilter');
            const selectedCompanyId = companyFilter ? companyFilter.value : params.company_id;
            
            console.log('Updating stores for selected company:', selectedCompanyId);
            
            // Find the selected company's stores
            const selectedCompany = userCompaniesData.companies.find(company => 
                company.company_id === selectedCompanyId
            );
            
            if (!selectedCompany) {
                console.log('Selected company not found:', selectedCompanyId);
                console.log('Available companies:', userCompaniesData.companies.map(c => ({ id: c.company_id, name: c.company_name })));
                return;
            }
            
            if (!selectedCompany.stores || selectedCompany.stores.length === 0) {
                console.log('No stores data available for selected company:', selectedCompany.company_name);
                return;
            }
            
            console.log('Updating store filter with stores:', selectedCompany.stores);
            
            selectedCompany.stores.forEach(store => {
                const option = document.createElement('option');
                option.value = store.store_id;
                option.textContent = store.store_name;
                select.appendChild(option);
            });
        }
        
        // Date picker functionality
        let currentYear = new Date().getFullYear();
        let selectedPeriod = null;
        
        function initializeDatePicker() {
            const datePicker = document.getElementById('datePicker');
            const calendarDropdown = document.getElementById('calendarDropdown');
            const thisMonthBtn = document.getElementById('thisMonthBtn');
            const lastMonthBtn = document.getElementById('lastMonthBtn');

            const prevYearBtn = document.getElementById('prevYear');
            const nextYearBtn = document.getElementById('nextYear');
            const closeCalendarBtn = document.getElementById('closeCalendar');
            
            // Set default to current month
            const now = new Date();
            const currentMonth = now.toLocaleString('default', { month: 'long' });
            const currentYearStr = now.getFullYear();
            datePicker.value = `${currentMonth} 1-31, ${currentYearStr}`;
            
            // Date picker click
            datePicker.addEventListener('click', function() {
                calendarDropdown.style.display = calendarDropdown.style.display === 'block' ? 'none' : 'block';
                if (calendarDropdown.style.display === 'block') {
                    updateCalendarGrid();
                }
            });
            
            // Quick period buttons
            thisMonthBtn.addEventListener('click', function() {
                const now = new Date();
                const month = now.toLocaleString('default', { month: 'long' });
                const year = now.getFullYear();
                datePicker.value = `${month} 1-31, ${year}`;
                selectedPeriod = { year, month: now.getMonth() + 1 };
            });
            
            lastMonthBtn.addEventListener('click', function() {
                const now = new Date();
                now.setMonth(now.getMonth() - 1);
                const month = now.toLocaleString('default', { month: 'long' });
                const year = now.getFullYear();
                datePicker.value = `${month} 1-31, ${year}`;
                selectedPeriod = { year, month: now.getMonth() + 1 };
            });
            

            
            // Calendar navigation
            prevYearBtn.addEventListener('click', function() {
                currentYear--;
                updateCalendarGrid();
            });
            
            nextYearBtn.addEventListener('click', function() {
                currentYear++;
                updateCalendarGrid();
            });
            
            closeCalendarBtn.addEventListener('click', function() {
                calendarDropdown.style.display = 'none';
            });
            
            // Close calendar when clicking outside
            document.addEventListener('click', function(e) {
                if (!datePicker.contains(e.target) && !calendarDropdown.contains(e.target)) {
                    calendarDropdown.style.display = 'none';
                }
            });
        }
        
        function updateCalendarGrid() {
            const yearDisplay = document.getElementById('currentYearDisplay');
            const monthGrid = document.getElementById('yearMonthGrid');
            
            yearDisplay.textContent = currentYear;
            
            const months = [
                'January', 'February', 'March', 'April', 'May', 'June',
                'July', 'August', 'September', 'October', 'November', 'December'
            ];
            
            monthGrid.innerHTML = '';
            
            months.forEach((month, index) => {
                const monthItem = document.createElement('div');
                monthItem.className = 'year-month-item';
                monthItem.textContent = month;
                monthItem.addEventListener('click', function() {
                    const datePicker = document.getElementById('datePicker');
                    datePicker.value = `${month} 1-31, ${currentYear}`;
                    selectedPeriod = { year: currentYear, month: index + 1 };
                    document.getElementById('calendarDropdown').style.display = 'none';
                });
                monthGrid.appendChild(monthItem);
            });
        }
        
        // Initialize Supabase client
        const { createClient } = supabase;
        const supabaseUrl = 'https://atkekzwgukdvucqntryo.supabase.co';
        const supabaseKey = 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8';
        const supabaseClient = createClient(supabaseUrl, supabaseKey);
        
        // 새로운 클라이언트 사이드 Export Excel 기능 - SheetJS 사용
        async function exportToExcel() {
            console.log('🚀 Starting client-side Excel export...');
            
            try {
                // Prevent multiple clicks
                if (window.exportInProgress) {
                    console.log('Export already in progress, ignoring click');
                    return;
                }
                window.exportInProgress = true;
                
                const exportBtn = document.getElementById('exportExcelBtn');
                if (!exportBtn) {
                    console.error('Export button not found!');
                    alert('Export button not found. Please try again.');
                    window.exportInProgress = false;
                    return;
                }
                
                const originalText = exportBtn.innerHTML;
                exportBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>데이터 수집 중...';
                exportBtn.disabled = true;
                
                // Get filter values
                const companyId = document.getElementById('companyFilter').value || params.company_id;
                const storeId = document.getElementById('storeFilter').value;
                
                console.log('📊 Filter values:', {
                    companyId: companyId,
                    storeId: storeId
                });
                
                // Validate required fields
                if (!companyId) {
                    alert('Please select a company');
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                    window.exportInProgress = false;
                    return;
                }
                
                // Get period information
                let yearMonth = '2025-08';
                let fileName = '2025-08_Employee_Salary';
                
                if (selectedPeriod) {
                    const year = selectedPeriod.year;
                    const month = String(selectedPeriod.month).padStart(2, '0');
                    yearMonth = `${year}-${month}`;
                    fileName = `${year}-${month}_Employee_Salary`;
                } else {
                    const datePicker = document.getElementById('datePicker');
                    if (datePicker && datePicker.value) {
                        const dateMatch = datePicker.value.match(/(\w+)\s+\d+-\d+,\s+(\d{4})/);
                        if (dateMatch) {
                            const monthName = dateMatch[1];
                            const year = dateMatch[2];
                            const monthNumber = new Date(`${monthName} 1, ${year}`).getMonth() + 1;
                            const monthStr = String(monthNumber).padStart(2, '0');
                            yearMonth = `${year}-${monthStr}`;
                            fileName = `${year}-${monthStr}_Employee_Salary`;
                        }
                    }
                }
                
                console.log('📅 Date parsing result:', { yearMonth, fileName });
                
                exportBtn.innerHTML = '<i class="bi bi-database me-1"></i>데이터베이스 조회 중...';
                
                // Step 1: Get store IDs if "All Stores" selected
                let storeIdsToQuery = [];
                if (storeId) {
                    storeIdsToQuery = [storeId];
                    console.log('🏪 Using specific store:', storeId);
                } else {
                    console.log('🏪 Getting all stores for company:', companyId);
                    const { data: stores, error: storeError } = await supabaseClient
                        .from('stores')
                        .select('store_id')
                        .eq('company_id', companyId);
                    
                    if (storeError) {
                        throw new Error('Error fetching stores: ' + storeError.message);
                    }
                    
                    storeIdsToQuery = stores.map(store => store.store_id);
                    console.log('🏪 Found stores:', storeIdsToQuery);
                }
                
                exportBtn.innerHTML = '<i class="bi bi-table me-1"></i>v_shift_request 데이터 조회 중...';
                
                // Step 2: Query v_shift_request table with filters
                const requestDate = yearMonth + '-01';
                const nextMonthDate = getNextMonth(yearMonth);
                
                let query = supabaseClient
                    .from('v_shift_request')
                    .select('*')
                    .gte('request_date', requestDate)
                    .lt('request_date', nextMonthDate);
                
                // Apply store filter
                if (storeIdsToQuery.length > 0) {
                    query = query.in('store_id', storeIdsToQuery);
                }
                
                console.log('🔍 Executing query with params:', {
                    requestDate,
                    nextMonthDate,
                    storeIds: storeIdsToQuery
                });
                
                const { data: shiftData, error: queryError } = await query;
                
                if (queryError) {
                    throw new Error('Error querying v_shift_request: ' + queryError.message);
                }
                
                if (!shiftData || shiftData.length === 0) {
                    alert('선택된 필터 조건에 해당하는 데이터가 없습니다.');
                    exportBtn.innerHTML = originalText;
                    exportBtn.disabled = false;
                    window.exportInProgress = false;
                    return;
                }
                
                console.log('📋 Retrieved data:', {
                    recordCount: shiftData.length,
                    sampleRecord: shiftData[0]
                });
                
                exportBtn.innerHTML = '<i class="bi bi-file-earmark-excel me-1"></i>엑셀 파일 생성 중...';
                
                // Step 3: Convert to Excel using SheetJS
                const worksheet = XLSX.utils.json_to_sheet(shiftData);
                const workbook = XLSX.utils.book_new();
                
                // Add worksheet with proper name
                XLSX.utils.book_append_sheet(workbook, worksheet, 'Employee Salary Data');
                
                // Apply basic formatting
                const range = XLSX.utils.decode_range(worksheet['!ref']);
                
                // Auto-fit columns (approximate)
                const colWidths = [];
                for (let C = range.s.c; C <= range.e.c; ++C) {
                    let max_width = 10;
                    for (let R = range.s.r; R <= range.e.r; ++R) {
                        const cell_address = XLSX.utils.encode_cell({ c: C, r: R });
                        const cell = worksheet[cell_address];
                        if (cell && cell.v) {
                            const cell_width = String(cell.v).length;
                            if (cell_width > max_width) {
                                max_width = cell_width;
                            }
                        }
                    }
                    colWidths.push({ width: Math.min(max_width + 2, 50) });
                }
                worksheet['!cols'] = colWidths;
                
                exportBtn.innerHTML = '<i class="bi bi-download me-1"></i>다운로드 중...';
                
                // Step 4: Download the file with enhanced filename
                const timestamp = new Date().toISOString().slice(0, 16).replace('T', '_').replace(/:/g, '-');
                const companyName = document.getElementById('companyFilter').selectedOptions[0]?.text || 'Company';
                const cleanCompanyName = companyName.replace(/[^a-zA-Z0-9가-힣]/g, '_').substring(0, 20);
                const finalFileName = `${cleanCompanyName}_Employee_Salary_${yearMonth}_${timestamp}.xlsx`;
                
                console.log('📁 Generated filename:', finalFileName);
                
                // Enhanced download with better filename handling
                try {
                    // Method 1: Try XLSX.writeFile (most compatible)
                    XLSX.writeFile(workbook, finalFileName, {
                        bookType: 'xlsx',
                        type: 'binary',
                        compression: true
                    });
                    console.log('✅ XLSX.writeFile successful');
                } catch (xlsxError) {
                    console.warn('XLSX.writeFile failed, trying alternative method:', xlsxError);
                    
                    // Method 2: Manual blob download with explicit headers
                    const excelBuffer = XLSX.write(workbook, {
                        bookType: 'xlsx',
                        type: 'array',
                        compression: true
                    });
                    
                    const blob = new Blob([excelBuffer], {
                        type: 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;charset=utf-8'
                    });
                    
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);
                    
                    // Enhanced download attributes
                    link.href = url;
                    link.download = finalFileName;
                    link.setAttribute('download', finalFileName); // Explicit download attribute
                    link.style.display = 'none';
                    link.style.visibility = 'hidden';
                    
                    // Force download behavior
                    document.body.appendChild(link);
                    
                    // Multiple click attempts for better compatibility
                    link.click();
                    
                    // Fallback click
                    setTimeout(() => {
                        if (document.body.contains(link)) {
                            const clickEvent = new MouseEvent('click', {
                                view: window,
                                bubbles: true,
                                cancelable: true
                            });
                            link.dispatchEvent(clickEvent);
                        }
                    }, 100);
                    
                    // Cleanup
                    setTimeout(() => {
                        if (document.body.contains(link)) {
                            document.body.removeChild(link);
                        }
                        URL.revokeObjectURL(url);
                    }, 2000);
                    
                    console.log('✅ Manual blob download successful');
                }
                
                console.log('✅ Export completed successfully!');
                console.log('📁 File downloaded:', finalFileName);
                console.log('📊 Records exported:', shiftData.length);
                console.log('🕒 Timestamp added for uniqueness');
                console.log('📂 Check your Downloads folder for:', finalFileName);
                console.log('💡 Note: In localhost, browser may show UUID in notification, but actual file has correct name');
                
                // Show success notification
                showClientDownloadSuccess(finalFileName, shiftData.length);
                
                // Reset button state
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
                window.exportInProgress = false;
                
            } catch (error) {
                console.error('❌ Export error:', error);
                alert('데이터 내보내기 중 오류가 발생했습니다: ' + error.message);
                
                // Reset button state
                const exportBtn = document.getElementById('exportExcelBtn');
                if (exportBtn) {
                    exportBtn.innerHTML = '<i class="bi bi-file-earmark-excel me-1"></i>Export Excel';
                    exportBtn.disabled = false;
                }
                window.exportInProgress = false;
            }
        }
        

        

        
        // Helper function to get next month for date range filtering
        function getNextMonth(yearMonth) {
            const [year, month] = yearMonth.split('-').map(Number);
            const nextMonth = month === 12 ? 1 : month + 1;
            const nextYear = month === 12 ? year + 1 : year;
            return `${nextYear}-${String(nextMonth).padStart(2, '0')}-01`;
        }
        
        // 클라이언트 사이드 다운로드 성공 알림
        function showClientDownloadSuccess(fileName, recordCount) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #10b981, #059669);
                color: white;
                padding: 16px 24px;
                border-radius: 12px;
                box-shadow: 0 8px 32px rgba(16, 185, 129, 0.3);
                z-index: 10000;
                font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
                font-size: 14px;
                max-width: 400px;
                animation: slideIn 0.3s ease-out;
            `;
            
            notification.innerHTML = `
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div style="font-size: 24px;">🎉</div>
                    <div>
                        <div style="font-weight: 600; margin-bottom: 4px;">클라이언트 사이드 다운로드 완료!</div>
                        <div style="font-size: 12px; opacity: 0.9; margin-bottom: 2px;">${fileName}</div>
                        <div style="font-size: 11px; opacity: 0.8;">
                            📊 ${recordCount.toLocaleString()}개 레코드 | 📁 브라우저 다운로드 폴더에 저장됨
                        </div>
                        <div style="font-size: 10px; opacity: 0.7; margin-top: 4px;">
                            ✅ SheetJS 라이브러리 | 🚀 빠른 클라이언트 처리 | 📋 모든 컬럼 포함
                        </div>
                    </div>
                </div>
            `;
            
            // Add animation styles if not already added
            if (!document.getElementById('downloadAnimationStyles')) {
                const style = document.createElement('style');
                style.id = 'downloadAnimationStyles';
                style.textContent = `
                    @keyframes slideIn {
                        from { transform: translateX(100%); opacity: 0; }
                        to { transform: translateX(0); opacity: 1; }
                    }
                    @keyframes slideOut {
                        from { transform: translateX(0); opacity: 1; }
                        to { transform: translateX(100%); opacity: 0; }
                    }
                `;
                document.head.appendChild(style);
            }
            
            document.body.appendChild(notification);
            
            // Auto remove after 6 seconds
            setTimeout(() => {
                notification.style.animation = 'slideOut 0.3s ease-in';
                setTimeout(() => {
                    if (notification.parentNode) {
                        notification.parentNode.removeChild(notification);
                    }
                }, 300);
            }, 6000);
        }
        

        
        // Search button functionality
        async function performSearch() {
            try {
                // Show loading state
                const searchBtn = document.getElementById('refreshBtn');
                const originalText = searchBtn.innerHTML;
                searchBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Searching...';
                searchBtn.disabled = true;
                
                // Get filter values
                const companyId = document.getElementById('companyFilter').value;
                const storeId = document.getElementById('storeFilter').value;
                const datePicker = document.getElementById('datePicker');
                
                // Validate required fields
                if (!companyId) {
                    alert('Please select a company');
                    searchBtn.innerHTML = originalText;
                    searchBtn.disabled = false;
                    return;
                }
                
                // Parse date from datePicker (format: "August 1-31, 2025")
                let requestDate = '2025-08-01'; // default
                if (selectedPeriod) {
                    const year = selectedPeriod.year;
                    const month = String(selectedPeriod.month).padStart(2, '0');
                    requestDate = `${year}-${month}-01`;
                } else if (datePicker.value) {
                    // Parse from display text (fallback)
                    const dateMatch = datePicker.value.match(/(\w+)\s+\d+-\d+,\s+(\d{4})/);
                    if (dateMatch) {
                        const monthName = dateMatch[1];
                        const year = dateMatch[2];
                        const monthNumber = new Date(`${monthName} 1, ${year}`).getMonth() + 1;
                        requestDate = `${year}-${String(monthNumber).padStart(2, '0')}-01`;
                    }
                }
                
                console.log('Search parameters:', {
                    company_id: companyId,
                    store_id: storeId || null,
                    request_date: requestDate
                });
                
                // Call RPC function
                const { data, error } = await supabaseClient.rpc('employee_salary_store', {
                    p_company_id: companyId,
                    p_request_date: requestDate,
                    p_store_id: storeId || null
                });
                
                if (error) {
                    console.error('RPC Error:', error);
                    alert('Error fetching employee salary data: ' + error.message);
                } else {
                    console.log('Employee salary data:', data);
                    displayEmployeeSalaryData(data);
                }
                
                // Reset button state
                searchBtn.innerHTML = originalText;
                searchBtn.disabled = false;
                
            } catch (error) {
                console.error('Search error:', error);
                alert('An error occurred while searching. Please try again.');
                
                // Reset button state
                const searchBtn = document.getElementById('refreshBtn');
                searchBtn.innerHTML = '<i class="bi bi-search me-2"></i> Search';
                searchBtn.disabled = false;
            }
        }
        
        // Display employee salary data
        function displayEmployeeSalaryData(data) {
            console.log('Displaying employee salary data:', data);
            
            if (!data || !data.employee || !data.detail) {
                console.warn('No employee salary data found');
                alert('No employee salary data found for the selected filters.');
                return;
            }
            
            // Create or update results section
            let resultsSection = document.getElementById('salaryResults');
            if (!resultsSection) {
                resultsSection = document.createElement('div');
                resultsSection.id = 'salaryResults';
                resultsSection.className = 'mt-4';
                document.querySelector('.container-fluid.page-container').appendChild(resultsSection);
            }
            
            // Store data globally for filtering
            window.salaryData = data;
            
            // Build employee dropdown options
            let employeeOptions = '<option value="">All Employees</option>';
            data.employee.forEach(employee => {
                employeeOptions += `<option value="${employee.user_id}">${employee.user_name}</option>`;
            });
            
            // Build results HTML
            let html = `
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">
                            <i class="bi bi-people me-2"></i>
                            Employee Salary Results (${data.employee.length} employees)
                        </h5>
                        <div class="d-flex align-items-center gap-3">
                            <button class="btn btn-success btn-sm" id="exportExcelBtn" onclick="exportToExcel()">
                                <i class="bi bi-file-earmark-excel me-1"></i>Export Excel
                            </button>
                            <div class="d-flex align-items-center">
                                <label class="form-label me-2 mb-0 fw-semibold">Select Employee:</label>
                                <select class="form-select" id="employeeFilter" style="width: 200px;" onchange="filterByEmployee(this.value)">
                                    ${employeeOptions}
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped table-hover">
                                <thead class="table-dark">
                                    <tr>
                                        <th>Employee Name</th>
                                        <th>Salary Type</th>
                                        <th>Base Salary</th>
                                        <th>Paid Hours</th>
                                        <th>Late Minutes</th>
                                        <th>Late Deduction</th>
                                        <th>Overtime Minutes</th>
                                        <th>Overtime Amount</th>
                                        <th>Bonus</th>
                                        <th>Total Pay</th>
                                    </tr>
                                </thead>
                                <tbody id="salaryTableBody">
                                </tbody>
            `;
            
            html += `
                            </table>
                        </div>
                    </div>
                </div>
            `;
            
            resultsSection.innerHTML = html;
            
            // Render table rows
            renderTableRows();
        }
        
        // Render table rows based on current filter
        function renderTableRows(filterUserId = null) {
            const data = window.salaryData;
            if (!data) return;
            
            const tbody = document.getElementById('salaryTableBody');
            if (!tbody) return;
            
            // Create a map of employee details
            const employeeDetailsMap = {};
            data.detail.forEach(detail => {
                employeeDetailsMap[detail.user_id] = detail;
            });
            
            let html = '';
            
            // Filter employees if needed
            let employeesToShow = data.employee;
            if (filterUserId) {
                employeesToShow = data.employee.filter(emp => emp.user_id === filterUserId);
            }
            
            // Build table rows
            employeesToShow.forEach(employee => {
                const detail = employeeDetailsMap[employee.user_id];
                if (detail) {
                    const symbol = detail.symbol || '₫';
                    html += `
                        <tr>
                            <td><strong>${employee.user_name}</strong></td>
                            <td><span class="badge bg-${detail.salary_type === 'hourly' ? 'primary' : 'success'}">${detail.salary_type}</span></td>
                            <td>${symbol}${Number(detail.salary_amount).toLocaleString()}</td>
                            <td>${Number(detail.paid_hours).toFixed(2)}h</td>
                            <td class="${detail.late_minutes > 0 ? 'text-warning' : ''}">${detail.late_minutes}min</td>
                            <td class="${detail.late_deduct_amount > 0 ? 'text-danger' : ''}">${symbol}${Number(detail.late_deduct_amount).toLocaleString()}</td>
                            <td class="${detail.overtime_minutes > 0 ? 'text-info' : ''}">${detail.overtime_minutes}min</td>
                            <td class="${detail.overtime_amount > 0 ? 'text-success' : ''}">${symbol}${Number(detail.overtime_amount).toLocaleString()}</td>
                            <td class="${detail.bonus_amount > 0 ? 'text-success' : ''}">${symbol}${Number(detail.bonus_amount).toLocaleString()}</td>
                            <td class="fw-bold text-primary">${symbol}${Number(detail.total_pay_with_bonus).toLocaleString()}</td>
                        </tr>
                    `;
                }
            });
            
            tbody.innerHTML = html;
        }
        
        // Filter by employee function
        function filterByEmployee(userId) {
            renderTableRows(userId || null);
        }
        
        function initializeSearchButton() {
            const searchBtn = document.getElementById('refreshBtn');
            searchBtn.addEventListener('click', performSearch);
        }
        
        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUserCompaniesAndStores();
            initializeDatePicker();
            initializeSearchButton();
        });
    </script>
    
    <!-- Navigation Enhancement Script -->
    <script src="../assets/js/navigation-enhancement.js"></script>
</body>
</html>