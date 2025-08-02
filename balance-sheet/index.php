<?php
// 파라미터 받기 및 검증
$user_id = $_GET['user_id'] ?? null;
$company_id = $_GET['company_id'] ?? null; 
$store_id = $_GET['store_id'] ?? null;

// 인증 검증
if (!$user_id || !$company_id) {
    header('Location: ../login/');
    exit;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Balance Sheet - Financial Statements</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link rel="stylesheet" href="style.css">

    <!-- 차트 시각화용 Chart.js 라이브러리 -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chart.js/3.9.1/chart.min.js"></script>
    
    <!-- 파라미터 전달 -->
    <script>
    const USER_ID = '<?= $user_id ?>';
    const COMPANY_ID = '<?= $company_id ?>';
    const STORE_ID = '<?= $store_id ?>' || null;
    
    // Global page state parameters for consistency
    const params = {
        user_id: USER_ID,
        company_id: COMPANY_ID,
        store_id: STORE_ID
    };
    
    // Global state for user companies and stores
    let userCompaniesData = null;
    </script>
</head>
<body>
    <!-- Navigation Bar -->
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
                        <a class="nav-link" href="../transactions/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-list-ul me-1"></i>Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../journal-entry/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-journal-plus me-1"></i>Journal Entry</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle active" href="#" id="financialStatementsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-file-earmark-text me-1"></i>Financial Statements
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item active" href="../balance-sheet/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">Balance Sheet</a></li>
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
                    <span class="navbar-text me-3" id="userInfo">
                        <i class="bi bi-person-circle me-1"></i> User
                    </span>
                    <div class="company-dropdown-container me-3">
                        <span class="company-dropdown-label">
                            <i class="bi bi-building me-1"></i>Company:
                        </span>
                        <select class="form-select form-select-sm d-inline-block w-auto" id="companySelect" onchange="changeCompany(this.value)" title="Select Company">
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
            <h1 class="page-title">Balance Sheet</h1>
            <p class="page-subtitle" id="pageSubtitle">Real-time view of company's assets, liabilities, and equity</p>
        </div>

        <!-- Loading Spinner -->
        <div class="loading-overlay d-none" id="loadingSpinner">
            <div class="loading-content">
                <div class="spinner-border text-primary" role="status" style="width: 3rem; height: 3rem;">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-3">Loading balance sheet...</p>
            </div>
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
                                   placeholder="Click to select year and month"
                                   readonly>
                            
                            <!-- Quick Period Selection Buttons -->
                            <div class="quick-period-buttons mt-2">
                                <button type="button" class="btn btn-outline-primary btn-sm" id="thisMonthBtn">This Month</button>
                                <button type="button" class="btn btn-outline-secondary btn-sm" id="lastMonthBtn">Last Month</button>
                                <button type="button" class="btn btn-outline-info btn-sm" id="thisYearBtn">This Year</button>
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
                    <div class="col-md-6">
                        <!-- Search Button -->
                        <button class="btn btn-primary btn-search" id="refreshBtn">
                            <i class="bi bi-search me-2"></i> Search
                        </button>
                    </div>
                    <div class="col-md-6">
                        <!-- Zero Balance Toggle -->
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" id="includeZeroBalance">
                            <label class="form-check-label" for="includeZeroBalance">
                                Include zero balance accounts
                            </label>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Error Alert -->
        <div class="alert alert-danger d-none" id="errorAlert">
            <i class="bi bi-exclamation-triangle me-2"></i>
            <span id="errorMessage"></span>
        </div>

        <!-- Balance Sheet Content -->
        <div class="balance-sheet-content d-none" id="balanceSheetContent" style="display: none;">
            <!-- Balance Sheet Grid -->
            <div class="balance-sheet-grid">
                <!-- Assets Card -->
                <div class="balance-card">
                    <div class="card-header-assets">
                        <h5 class="card-title">
                            <i class="bi bi-building"></i>
                            Assets
                        </h5>
                        <p class="card-subtitle">Company's resources and property</p>
                        <div class="card-total" id="totalAssetsAmount">₫0</div>
                    </div>
                    <div class="card-content">
                        <!-- 유동자산 -->
                        <div class="account-category" id="currentAssetsSection">
                            <h6 class="category-header">
                                <i class="bi bi-cash-coin me-2"></i>Current Assets
                            </h6>
                            <div class="account-list" id="currentAssetsList">
                                <!-- JavaScript에서 동적으로 채워짐 -->
                            </div>
                            <div class="category-total">
                                Subtotal: <span id="currentAssetsTotal">₫0</span>
                            </div>
                        </div>
                        
                        <!-- 비유동자산 -->
                        <div class="account-category" id="nonCurrentAssetsSection">
                            <h6 class="category-header">
                                <i class="bi bi-gear me-2"></i>Non-Current Assets
                            </h6>
                            <div class="account-list" id="nonCurrentAssetsList">
                                <!-- JavaScript에서 동적으로 채워짐 -->
                            </div>
                            <div class="category-total">
                                Subtotal: <span id="nonCurrentAssetsTotal">₫0</span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Liabilities & Equity Card -->
                <div class="balance-card">
                    <div class="card-header-liabilities">
                        <h5 class="card-title">
                            <i class="bi bi-credit-card"></i>
                            Liabilities & Equity
                        </h5>
                        <p class="card-subtitle">Company's debts and owner's equity</p>
                        <div class="card-total" id="totalLiabilitiesAndEquityAmount">₫0</div>
                    </div>
                    <div class="card-content">
                        <!-- 부채 -->
                        <div class="account-category" id="liabilitiesSection">
                            <h6 class="category-header">
                                <i class="bi bi-receipt me-2"></i>Liabilities
                            </h6>
                            
                            <!-- 유동부채 -->
                            <div class="subcategory" id="currentLiabilitiesSection">
                                <div class="subcategory-header">Current Liabilities</div>
                                <div class="account-list" id="currentLiabilitiesList">
                                    <!-- JavaScript에서 동적으로 채워짐 -->
                                </div>
                            </div>
                            
                            <!-- 비유동부채 -->
                            <div class="subcategory" id="nonCurrentLiabilitiesSection">
                                <div class="subcategory-header">Non-Current Liabilities</div>
                                <div class="account-list" id="nonCurrentLiabilitiesList">
                                    <!-- JavaScript에서 동적으로 채워짐 -->
                                </div>
                            </div>
                            
                            <div class="category-total">
                                Total Liabilities: <span id="totalLiabilitiesAmount">₫0</span>
                            </div>
                        </div>
                        
                        <!-- 자본 -->
                        <div class="account-category" id="equitySection">
                            <h6 class="category-header">
                                <i class="bi bi-pie-chart me-2"></i>Equity
                            </h6>
                            <div class="account-list" id="equityList">
                                <!-- JavaScript에서 동적으로 채워짐 -->
                            </div>
                            <div class="category-total">
                                Total Equity: <span id="totalEquityAmount">₫0</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Charts and Analytics Section -->
            <div class="charts-analytics-section mb-4" id="chartsSection">
                <div class="row g-3">
                    <!-- Assets Composition Chart -->
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h6><i class="bi bi-pie-chart me-2"></i>Assets Composition</h6>
                            </div>
                            <div class="chart-content">
                                <canvas id="assetsChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Liabilities vs Equity Chart -->
                    <div class="col-md-6">
                        <div class="chart-card">
                            <div class="chart-header">
                                <h6><i class="bi bi-bar-chart me-2"></i>Liabilities vs Equity</h6>
                            </div>
                            <div class="chart-content">
                                <canvas id="liabEquityChart" width="300" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- KPI Indicators -->
                <div class="kpi-section mt-3">
                    <div class="row g-3">
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-title">Debt Ratio</div>
                                <div class="kpi-value" id="debtRatio">0%</div>
                                <div class="kpi-description">Liabilities ÷ Assets</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-warning" id="debtRatioBar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-title">Equity Ratio</div>
                                <div class="kpi-value" id="equityRatio">0%</div>
                                <div class="kpi-description">Equity ÷ Assets</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-success" id="equityRatioBar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-title">Current Ratio</div>
                                <div class="kpi-value" id="liquidityRatio">0%</div>
                                <div class="kpi-description">Current Assets ÷ Current Liabilities</div>
                                <div class="progress mt-2">
                                    <div class="progress-bar bg-info" id="liquidityRatioBar" style="width: 0%"></div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="kpi-card">
                                <div class="kpi-title">Total Assets</div>
                                <div class="kpi-value" id="totalAssetsKpi">₫0</div>
                                <div class="kpi-description">Total Asset Value</div>
                                <div class="kpi-change mt-2" id="assetsChange">+0.0%</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Balance Verification -->
            <div class="balance-verification-card" id="balanceVerification">
                <div class="verification-icon" id="verificationIcon">
                    <i class="bi bi-check-circle"></i>
                </div>
                <div class="verification-content">
                    <h6 class="verification-title" id="verificationTitle">Balance Verification</h6>
                    <p class="verification-message" id="verificationMessage">Verifies the Assets = Liabilities + Equity equation.</p>
                    <div class="verification-details">
                        <div class="detail-item">
                            <span class="detail-label">Total Assets:</span>
                            <span class="detail-value" id="totalAssetsValue">₫0</span>
                        </div>
                        <div class="detail-item">
                            <span class="detail-label">Total Liabilities + Equity:</span>
                            <span class="detail-value" id="totalLiabEquityValue">₫0</span>
                        </div>
                        <div class="detail-item" id="differenceDetail">
                            <span class="detail-label">Difference:</span>
                            <span class="detail-value" id="balanceDifference">₫0</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Company Info Card -->
            <div class="company-info-card mt-3" id="companyInfoCard">
                <div class="company-info-header">
                    <h6><i class="bi bi-building me-2"></i>Company Information</h6>
                </div>
                <div class="company-info-content">
                    <div class="info-item">
                        <span class="info-label">Company Name:</span>
                        <span class="info-value" id="companyName">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Base Currency:</span>
                        <span class="info-value" id="baseCurrency">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">As of Date:</span>
                        <span class="info-value" id="asOfDate">-</span>
                    </div>
                    <div class="info-item">
                        <span class="info-label">Last Updated:</span>
                        <span class="info-value" id="lastUpdate">-</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState">
            <div class="empty-icon">
                <i class="bi bi-inbox"></i>
            </div>
            <h5>Please Set Filters</h5>
            <p>Please select company, date range and click Search to view balance sheet data.</p>
            <button class="btn btn-primary" onclick="resetFilters()">
                <i class="bi bi-arrow-clockwise me-2"></i>Reset Filters
            </button>
        </div>
    </div>

    <!-- Scripts -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="script.js"></script>
    
    <!-- Navigation Enhancement Script -->
    <script src="../assets/js/navigation-enhancement.js"></script>
</body>
</html>