<?php
/**
 * Financial Management System - Journal Entry
 * Phase 5.4: Bulk Edit and Copy Features Implementation
 * Updated to use page state parameters instead of API calls
 */
require_once '../common/auth.php';
require_once '../common/functions.php';

// 파라미터 받기 및 검증
$user_id = $_GET['user_id'] ?? null;
$company_id = $_GET['company_id'] ?? null;
$store_id = $_GET['store_id'] ?? null; // 페이지 스테이트에서 받은 store_id

// 인증 검증
if (!$user_id || !$company_id) {
    header('Location: ../login/');
    exit;
}

// Get user info (basic info only, no companies/stores API calls)
$user = getCurrentUser($user_id);

// Get company currency using common function
$currency_info = getCompanyCurrency($company_id);
$currency = $currency_info['currency_code'];
$currency_symbol = $currency_info['currency_symbol'];
$currency_name = $currency_info['currency_name'];

// 🔄 Get Chart of Accounts for dropdown
function getChartOfAccounts($company_id) {
    global $supabase;
    
    try {
        // accounts 테이블에는 company_id 컬럼이 없으므로 모든 계정을 로드
        $response = $supabase->query('accounts', [
            'select' => 'account_id,account_name,account_type,category_tag',
            'order' => 'account_type,account_name'
        ]);
        
        return $response ?? [];
    } catch (Exception $e) {
        error_log('Error getting Chart of Accounts: ' . $e->getMessage());
        return [];
    }
}

$accounts = getChartOfAccounts($company_id);

// 🎨 Generate account options HTML
function generateAccountOptions($accounts) {
    $html = '<option value="">Select Account</option>';
    
    $grouped = [];
    foreach ($accounts as $account) {
        $type = ucfirst($account['account_type']);
        $grouped[$type][] = $account;
    }
    
    foreach ($grouped as $type => $typeAccounts) {
        $html .= '<optgroup label="' . $type . '">';
        foreach ($typeAccounts as $account) {
            $html .= '<option value="' . htmlspecialchars($account['account_id']) . '">' . 
                     htmlspecialchars($account['account_name']) . '</option>';
        }
        $html .= '</optgroup>';
    }
    
    return $html;
}

$accountOptions = generateAccountOptions($accounts);
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Journal Entry - Financial Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <!-- 🔴 CRITICAL FIX: Remove modern-dropdown.css to prevent multiple arrows -->
    <!-- <link href="../assets/css/modern-dropdown.css" rel="stylesheet"> -->
    <style>
        /* 🎨 CSS Variables from commondesign.md */
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

        /* 🧭 Navigation - Copied from transactions/index.php */
        .navbar {
            background: linear-gradient(135deg, #1e293b 0%, #334155 100%) !important;
            box-shadow: var(--shadow-md);
            padding: 1rem 0;
        }

        .navbar-brand, .nav-link {
            color: rgba(255, 255, 255, 0.9) !important;
            font-weight: 500;
        }

        .nav-link:hover {
            color: white !important;
            background-color: rgba(255, 255, 255, 0.1);
            border-radius: 8px;
        }

        /* 🎯 Active page indicator - SET TO JOURNAL-ENTRY */
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

        /* Dropdown active states */
        .navbar-nav .dropdown-toggle.active {
            color: #ffffff !important;
            background: rgba(37, 99, 235, 0.25) !important;
            border: 1px solid rgba(37, 99, 235, 0.4);
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.3);
        }

        /* 🚨 CRITICAL FIX: Company dropdown from troubleshooting.md */
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
            
            /* CRITICAL: Custom arrow prevents multiple arrows */
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 18px 14px;
            padding-right: 2.8rem;
        }

        .navbar .form-select:hover {
            border-color: rgba(255, 255, 255, 0.6) !important;
            background: rgba(255, 255, 255, 0.2) !important;
            transform: translateY(-1px) !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
        }

        .navbar .form-select:focus {
            border-color: rgba(255, 255, 255, 0.6) !important;
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            transform: translateY(-1px) !important;
            outline: none !important;
        }

        .navbar .form-select:active {
            border-color: rgba(255, 255, 255, 0.6) !important;
            background: rgba(255, 255, 255, 0.2) !important;
            color: white !important;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2) !important;
            transform: translateY(-1px) !important;
        }

        /* Reset focus when dropdown is closed */
        .navbar .form-select:not(:focus-visible) {
            border-color: rgba(255, 255, 255, 0.3) !important;
            background: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
            transform: none !important;
        }

        .navbar .form-select:not(:focus-visible):hover {
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

        .navbar .form-select option:hover {
            background: var(--hover-bg);
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

        /* 📄 Page Structure */
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

        /* 🎨 Prevent conflicts with page content forms */
        .form-control, .form-select:not(.navbar .form-select) {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:not(.navbar .form-select):focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.2);
        }

        /* 📊 Journal Lines Table Styling - Phase 3.1 */
        .base-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            min-height: 700px;
            display: flex;
            flex-direction: column;
        }
        
        /* 🎯 Entry Information Card - Compact Size */
        .entry-info-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 12px;
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
            margin-bottom: 1.5rem;
            min-height: auto; /* Remove fixed min-height */
            display: flex;
            flex-direction: column;
        }

        .base-card .table-responsive {
            flex: 1;
            min-height: 400px;
            max-height: 500px;
            overflow-y: auto;
        }

        .base-card .card-title {
            font-size: 1.1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 0;
        }

        #journal-lines-table {
            margin-bottom: 0;
        }

        #journal-lines-table th {
            background: var(--light-bg);
            border-bottom: 2px solid var(--border-color);
            font-weight: 600;
            font-size: 0.875rem;
            color: var(--text-secondary);
            padding: 1rem 0.75rem;
        }

        #journal-lines-table td {
            padding: 1rem 0.75rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
            min-height: 60px;
        }

        .journal-line:hover {
            background-color: var(--hover-bg);
        }

        .line-number {
            font-weight: 600;
            color: var(--text-secondary);
        }

        .account-select, .debit-input, .credit-input {
            border: 1px solid var(--border-color);
            font-size: 0.875rem;
        }

        .account-select:focus, .debit-input:focus, .credit-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        /* Hide number input arrows/spinners */
        .debit-input, .credit-input {
            -webkit-appearance: textfield;
            -moz-appearance: textfield;
            appearance: textfield;
        }

        .debit-input::-webkit-outer-spin-button,
        .debit-input::-webkit-inner-spin-button,
        .credit-input::-webkit-outer-spin-button,
        .credit-input::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }

        .debit-input[type=number],
        .credit-input[type=number] {
            -moz-appearance: textfield;
        }

        .remove-line-btn {
            border: 1px solid var(--danger-color);
            color: var(--danger-color);
            background: transparent;
            transition: all 0.3s ease;
        }

        .remove-line-btn:hover:not(:disabled) {
            background: var(--danger-color);
            color: white;
            transform: translateY(-1px);
        }

        .remove-line-btn:disabled {
            opacity: 0.3;
            cursor: not-allowed;
        }

        /* Add Line Button */
        .btn-success {
            background: var(--success-color);
            border-color: var(--success-color);
            transition: all 0.3s ease;
        }

        .btn-success:hover {
            background: #047857;
            border-color: #047857;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(5, 150, 105, 0.3);
        }

        /* 🚀 Phase 4.3.1: Auto-save status styling */
        .auto-save-status {
            position: fixed;
            top: 80px;
            right: 20px;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            z-index: 1050;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
            transition: all 0.3s ease;
        }
        
        .auto-save-status.success {
            background: var(--success-color);
            color: white;
            border: 1px solid var(--success-color);
        }
        
        .auto-save-status.error {
            background: var(--danger-color);
            color: white;
            border: 1px solid var(--danger-color);
        }

        /* 🔍 Searchable Account Dropdown Styles */
        .account-dropdown-container {
            position: relative;
            width: 100%;
            min-width: 250px;
        }

        .account-search-input {
            width: 100%;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            padding: 0.375rem 0.75rem;
            font-size: 0.875rem;
            background-color: white;
            cursor: text;
        }

        .account-search-input:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
            outline: none;
        }

        .account-dropdown-list {
            position: absolute;
            top: 100%;
            left: 0;
            right: 0;
            background: white;
            border: 1px solid var(--border-color);
            border-radius: 6px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            max-height: 320px;
            overflow-y: auto;
            z-index: 1000;
            margin-top: 2px;
        }

        .account-dropdown-item {
            padding: 0.75rem 1rem;
            cursor: pointer;
            border-bottom: 1px solid #f1f5f9;
            transition: background-color 0.2s;
            line-height: 1.4;
        }

        .account-dropdown-item:hover {
            background-color: var(--hover-bg);
        }

        .account-dropdown-item:last-child {
            border-bottom: none;
        }

        .account-dropdown-item.highlighted {
            background-color: var(--primary-color);
            color: white;
        }

        .account-dropdown-group {
            padding: 0.625rem 1rem;
            background-color: #f8fafc;
            font-weight: 600;
            font-size: 0.8rem;
            color: var(--text-secondary);
            border-bottom: 1px solid #e2e8f0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .account-dropdown-no-results {
            padding: 0.75rem;
            text-align: center;
            color: #6b7280;
            font-style: italic;
        }

        /* 📱 Responsive Design */
        @media (max-width: 768px) {
            .page-container {
                padding: 1rem 0;
            }
            
            .page-title {
                font-size: 1.5rem;
            }
            
            .navbar .form-select {
                min-width: 140px !important;
                font-size: 0.85rem !important;
            }

            .table-responsive {
                border: 1px solid var(--border-color);
                border-radius: 8px;
            }

            #journal-lines-table th:nth-child(3),
            #journal-lines-table td:nth-child(3) {
                display: none;
            }

            .base-card {
                padding: 1rem;
            }
            
            .entry-info-card {
                padding: 1rem;
            }
            
            .auto-save-status {
                top: 60px;
                right: 10px;
                font-size: 0.8rem;
                padding: 0.4rem 0.8rem;
            }
        }

        /* ✨ 작업 5-1: Cash 안내 메시지용 애니메이션 추가 */
        @keyframes fadeInSlide {
            0% {
                opacity: 0;
                transform: translateY(-10px);
            }
            100% {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes fadeOutSlide {
            0% {
                opacity: 1;
                transform: translateY(0);
            }
            100% {
                opacity: 0;
                transform: translateY(-10px);
            }
        }

        /* Cash 안내 메시지 스타일 개선 */
        .cash-guidance-message {
            animation: fadeInSlide 0.3s ease-in-out;
        }

        .cash-guidance-message.fade-out {
            animation: fadeOutSlide 0.2s ease-in-out;
        }

        /* 🔧 Actions 열 위치 고정 - Delete 버튼 위치 일관성 확보 */
        #journal-lines-table {
            table-layout: fixed;
        }

        #journal-lines-table th:last-child,
        #journal-lines-table td:last-child {
            position: sticky;
            right: 0;
            background: var(--card-bg);
            z-index: 10;
            border-left: 1px solid var(--border-color);
            min-width: 60px;
            width: 60px;
        }

        /* Location 열 스타일 - 항상 표시하되 내용만 동적으로 변경 */
        .location-selector-container {
            display: table-cell !important; /* Force show */
            min-width: 120px;
            width: 120px;
            text-align: center;
        }

        .location-selector-container select {
            display: none;
        }

        .location-selector-container.show select {
            display: block;
        }

        /* 테이블 헤더 고정 너비 설정 */
        #journal-lines-table th:nth-child(1) { width: 5%; }
        #journal-lines-table th:nth-child(2) { width: 30%; }
        #journal-lines-table th:nth-child(3) { width: 25%; }
        #journal-lines-table th:nth-child(4) { width: 12%; }
        #journal-lines-table th:nth-child(5) { width: 12%; }
        #journal-lines-table th:nth-child(6) { width: 11%; }
        #journal-lines-table th:nth-child(7) { width: 5%; }

        /* 테이블 셀 고정 너비 설정 */
        #journal-lines-table td:nth-child(1) { width: 5%; }
        #journal-lines-table td:nth-child(2) { width: 30%; }
        #journal-lines-table td:nth-child(3) { width: 25%; }
        #journal-lines-table td:nth-child(4) { width: 12%; }
        #journal-lines-table td:nth-child(5) { width: 12%; }
        #journal-lines-table td:nth-child(6) { width: 11%; }
        #journal-lines-table td:nth-child(7) { width: 5%; }

        /* 🎯 Category Tag 기반 동적 UI 스타일 */
        .counterparty-selector-container {
            margin-top: 0.5rem;
            padding: 0.75rem;
            background-color: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 6px;
            animation: fadeInSlide 0.3s ease-in-out;
        }

        .counterparty-selector-container .form-label {
            font-size: 0.8rem;
            font-weight: 500;
            color: var(--text-secondary);
            margin-bottom: 0.25rem;
        }

        .debt-details-button,
        .asset-details-button {
            margin-top: 0.5rem;
            font-size: 0.8rem;
            padding: 0.375rem 0.75rem;
            transition: all 0.2s ease;
        }

        .debt-details-button:hover,
        .asset-details-button:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        .cash-balance-info {
            padding: 0.375rem 0.5rem;
            background-color: #f0f9ff;
            border: 1px solid #bfdbfe;
            border-radius: 4px;
            font-size: 0.8rem;
        }

        .cash-guidance-message {
            border-left: 3px solid #06b6d4;
            font-size: 0.8rem;
        }

        .cash-guidance-message.fade-out {
            animation: fadeOutSlide 0.2s ease-in-out;
        }

        /* Location 드롭다운 활성화 시 스타일 */
        .location-selector-container.show {
            background-color: var(--light-bg);
            border-radius: 6px;
            padding: 0.25rem;
        }

        .location-selector-container.show select {
            border: 1px solid var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        /* Counterparty 드롭다운 스타일 */
        .counterparty-select {
            border: 1px solid var(--border-color);
            border-radius: 4px;
            font-size: 0.85rem;
        }

        .counterparty-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.25);
        }

        /* Internal counterparty 표시 */
        .counterparty-select option[data-is-internal="true"] {
            background-color: #fef3c7;
            color: #92400e;
            font-weight: 500;
        }

        /* 반응형 디자인 - 모바일에서 추가 필드들 */
        @media (max-width: 768px) {
            .counterparty-selector-container {
                padding: 0.5rem;
                margin-top: 0.375rem;
            }

            .debt-details-button,
            .asset-details-button {
                font-size: 0.75rem;
                padding: 0.25rem 0.5rem;
            }

            .cash-balance-info {
                padding: 0.25rem 0.375rem;
                font-size: 0.75rem;
            }
        }
    </style>
</head>
<body>
    <!-- 🧭 Navigation - Copied from transactions/index.php with journal-entry active -->
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
                        <!-- 🎯 SET ACTIVE TO JOURNAL-ENTRY -->
                        <a class="nav-link active" href="../journal-entry/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>"><i class="bi bi-journal-plus me-1"></i>Journal Entry</a>
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
        <!-- 📄 Page Header with Action Buttons -->
        <div class="page-header">
            <div class="d-flex justify-content-between align-items-start">
                <div>
                    <h1 class="page-title">Journal Entry</h1>
                    <p class="page-subtitle">Create new accounting entries using double-entry bookkeeping</p>
                </div>
                <div class="d-flex gap-2">
                    <button class="btn btn-outline-secondary" onclick="showRecentEntriesModal()">
                        <i class="bi bi-clock-history me-1"></i>Recent Entries
                    </button>
                    <button class="btn btn-primary" onclick="showTemplatesModal()">
                        <i class="bi bi-file-earmark-text me-1"></i>Templates
                    </button>
                    <button class="btn btn-success" onclick="createNewEntry()">
                        <i class="bi bi-plus-circle me-1"></i>New Entry
                    </button>
                </div>
            </div>
        </div>

        <!-- 📝 Journal Entry Form -->
        <div class="row">
            <div class="col-lg-8">
                <!-- Entry Information Card -->
                <div class="entry-info-card mb-4">
                    <h6 class="card-title mb-3">
                        <i class="bi bi-info-circle me-2"></i>Entry Information
                    </h6>
                    <div class="row">
                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Entry Date <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="entry_date" name="entry_date" required value="<?= date('Y-m-d') ?>">
                        </div>
                        <div class="col-lg-6 mb-3">
                            <label class="form-label">Store <span class="text-danger">*</span></label>
                            <select class="form-select" id="store_id" name="store_id" required>
                                <option value="">Select Store</option>
                                <!-- Stores will be loaded dynamically -->
                            </select>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-lg-12 mb-3">
                            <label class="form-label">Description</label>
                            <input type="text" class="form-control" id="description" name="description" placeholder="Enter entry description">
                        </div>
                    </div>
                </div>
                
                <!-- Journal Lines Card -->
                <div class="base-card">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="card-title mb-0">
                            <i class="bi bi-table me-2"></i>Journal Lines
                        </h6>
                        <button type="button" class="btn btn-sm btn-success" onclick="addJournalLine()">
                            <i class="bi bi-plus-circle me-1"></i>Add Line
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover" id="journal-lines-table">
                            <thead class="table-light">
                                <tr>
                                    <th width="5%">#</th>
                                    <th width="30%">Account</th>
                                    <th width="25%">Description</th>
                                    <th width="12%">Debit</th>
                                    <th width="12%">Credit</th>
                                    <th width="11%">Location</th>
                                    <th width="5%">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="journal-lines-tbody">
                                <!-- Initial 2 lines -->
                                <tr class="journal-line" data-line-index="1">
                                    <td class="text-center line-number">1</td>
                                    <td>
                                        <div class="account-dropdown-container">
                                            <input type="text" class="form-control form-control-sm account-search-input" 
                                                   placeholder="Search accounts..." 
                                                   onkeyup="filterAccounts(this)" 
                                                   onblur="hideAccountDropdown(this)"
                                                   onfocus="showAccountDropdown(this)"
                                                   autocomplete="off">
                                            <input type="hidden" class="account-id-hidden" name="account_id[]">
                                            <div class="account-dropdown-list" style="display: none;">
                                                <!-- 동적으로 계정 목록 생성 -->
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="line_description[]" placeholder="Enter description" onchange="validateForm()">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm debit-input" name="debit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance(); validateForm()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm credit-input" name="credit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance(); validateForm()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                                    </td>
                                    <td class="location-selector-container">
                                        <select class="form-select form-select-sm cash-location-select" style="display: none;">
                                            <option value="">Select Location</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" onclick="removeJournalLine(1)" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                                <tr class="journal-line" data-line-index="2">
                                    <td class="text-center line-number">2</td>
                                    <td>
                                        <div class="account-dropdown-container">
                                            <input type="text" class="form-control form-control-sm account-search-input" 
                                                   placeholder="Search accounts..." 
                                                   onkeyup="filterAccounts(this)" 
                                                   onblur="hideAccountDropdown(this)"
                                                   onfocus="showAccountDropdown(this)"
                                                   autocomplete="off">
                                            <input type="hidden" class="account-id-hidden" name="account_id[]">
                                            <div class="account-dropdown-list" style="display: none;">
                                                <!-- 동적으로 계정 목록 생성 -->
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        <input type="text" class="form-control form-control-sm" name="line_description[]" placeholder="Enter description" onchange="validateForm()">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm debit-input" name="debit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance(); validateForm()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                                    </td>
                                    <td>
                                        <input type="number" class="form-control form-control-sm credit-input" name="credit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance(); validateForm()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                                    </td>
                                    <td class="location-selector-container">
                                        <select class="form-select form-select-sm cash-location-select" style="display: none;">
                                            <option value="">Select Location</option>
                                        </select>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" onclick="removeJournalLine(2)" disabled>
                                            <i class="bi bi-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <!-- Phase 4.1: Save Button and Form Validation -->
                    <div class="mt-4">
                        <div class="row align-items-center">
                            <div class="col-md-6">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="balance-check" disabled>
                                    <label class="form-check-label" for="balance-check">
                                        <span id="balance-status" class="text-muted">Balance not verified</span>
                                    </label>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="d-flex justify-content-end gap-2">
                                    <button type="button" class="btn btn-outline-secondary" onclick="resetForm()">
                                        <i class="bi bi-arrow-counterclockwise me-1"></i>Reset
                                    </button>
                                    <button type="button" class="btn btn-primary" id="save-btn" disabled onclick="saveJournalEntry()">
                                        <i class="bi bi-check-circle me-1"></i>Save Journal Entry
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Phase 4.4: Success/Error Messages -->
                    <div class="alert alert-success mt-3" id="success-message" style="display: none;">
                        <i class="bi bi-check-circle me-2"></i>
                        <strong>Success!</strong> <span id="success-text">Journal entry saved successfully.</span>
                    </div>
                    <div class="alert alert-danger mt-3" id="error-message" style="display: none;">
                        <i class="bi bi-exclamation-triangle me-2"></i>
                        <strong>Error!</strong> <span id="error-text"></span>
                    </div>
                    
                    <!-- Auto-save status indicator -->
                    <div id="auto-save-status" class="auto-save-status" style="display: none;"></div>
                </div>
            </div>
            
            <div class="col-lg-4">
                <!-- Quick Info Card -->
                <div class="base-card mb-4">
                    <h6 class="card-title mb-3">
                        <i class="bi bi-calculator me-2"></i>Balance Check
                    </h6>
                    <div class="balance-info">
                        <div class="d-flex justify-content-between py-2">
                            <span>Total Debit:</span>
                            <span class="text-primary fw-bold"><?= $currency_symbol ?> 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-2">
                            <span>Total Credit:</span>
                            <span class="text-success fw-bold"><?= $currency_symbol ?> 0.00</span>
                        </div>
                        <hr>
                        <div class="d-flex justify-content-between py-2">
                            <span class="fw-bold">Difference:</span>
                            <span class="text-muted fw-bold"><?= $currency_symbol ?> 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Phase 5: Advanced Details Modal -->
    <div class="modal fade" id="advancedDetailsModal" tabindex="-1" aria-labelledby="advancedDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="advancedDetailsModalLabel">
                        <i class="bi bi-gear me-2"></i>Advanced Line Details
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="modal-body-content">
                    <!-- Dynamic content based on line type -->
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveAdvancedDetails()">
                        <i class="bi bi-check-lg me-1"></i>Save Details
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- ✨ 작업 5-3: CSS 애니메이션 스타일 추가 -->
    <style>
        /* 애니메이션 정의 */
        @keyframes fadeInSlide {
            from {
                opacity: 0;
                transform: translateY(-10px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        
        @keyframes fadeOutSlide {
            from {
                opacity: 1;
                transform: translateY(0);
            }
            to {
                opacity: 0;
                transform: translateY(-10px);
            }
        }
        
        @keyframes pulse-glow {
            0% {
                box-shadow: 0 0 8px rgba(255, 193, 7, 0.4);
            }
            50% {
                box-shadow: 0 0 16px rgba(255, 193, 7, 0.7);
            }
            100% {
                box-shadow: 0 0 8px rgba(255, 193, 7, 0.4);
            }
        }
        
        @keyframes shake {
            0%, 100% {
                transform: translateX(0);
            }
            25% {
                transform: translateX(-5px);
            }
            75% {
                transform: translateX(5px);
            }
        }
        
        @keyframes fadeIn {
            from {
                opacity: 0;
            }
            to {
                opacity: 1;
            }
        }
        
        /* Location 열 애니메이션 스타일 */
        .location-selector-container {
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
        }
        
        /* Location 필드 하이라이트 스타일 */
        .location-required-highlight {
            position: relative;
        }
        
        .location-required-highlight::before {
            content: '⚠️';
            position: absolute;
            right: -25px;
            top: 50%;
            transform: translateY(-50%);
            font-size: 14px;
            animation: pulse-glow 2s infinite;
        }
        
        /* Location 경고 메시지 스타일 */
        .location-warning {
            font-size: 0.75rem;
            line-height: 1.2;
        }
        
        /* Location 성공 아이콘 스타일 */
        .location-success {
            display: inline-block;
            vertical-align: middle;
        }
        
        /* Cash 안내 메시지 스타일 */
        .cash-guidance-message {
            font-size: 0.85em;
            padding: 8px 12px;
            margin-top: 8px;
            border-left: 4px solid #0dcaf0;
            background-color: #e7f3ff;
            border-radius: 6px;
            animation: fadeInSlide 0.3s ease-in-out;
        }
        
        /* Location 드롭다운 반응형 스타일 */
        .cash-location-select {
            transition: all 0.3s ease;
        }
        
        .cash-location-select:focus {
            border-color: #0dcaf0;
            box-shadow: 0 0 0 0.2rem rgba(13, 202, 240, 0.25);
        }
        
        /* 호버 효과 */
        .cash-location-select:hover {
            border-color: #0dcaf0;
        }
        
        /* 오류 상태 스타일 */
        .cash-location-select.is-invalid {
            border-color: #dc3545;
            background-color: #fff5f5;
        }
        
        /* 성공 상태 스타일 */
        .cash-location-select.is-valid {
            border-color: #28a745;
            background-color: #f8fff9;
        }
    </style>
    
    <script src="advanced_search.js"></script>
    <script src="real_time_validation.js"></script>
    <script src="quick_actions_fix.js"></script>
    <script src="category_tag_handler.js"></script>
    <script src="details_modals.js"></script>
    <script src="form_data_collector.js"></script>
    <script>
        // 🏢 Company ID for JavaScript access
        const companyId = '<?= $company_id ?>';
        window.companyId = companyId;
        console.log('Company ID initialized:', companyId);
        
        // 🔍 Searchable Account Dropdown Functions
        let accountsData = []; // 전역 변수로 계정 데이터 저장
        let currentFocusedDropdown = null;
        
        // 계정 데이터 초기화
        function initializeAccountsData() {
            accountsData = <?php
                $jsAccountsData = [];
                foreach ($accounts as $account) {
                    $jsAccountsData[] = [
                        'id' => $account['account_id'],
                        'name' => $account['account_name'],
                        'type' => $account['account_type'],
                        'category_tag' => $account['category_tag'] ?? null
                    ];
                }
                echo json_encode($jsAccountsData);
            ?>;
        }
        
        // 계정 필터링 함수
        function filterAccounts(input) {
            const query = input.value.toLowerCase();
            const container = input.closest('.account-dropdown-container');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            if (query.length === 0) {
                showAllAccounts(dropdownList);
            } else {
                const filteredAccounts = accountsData.filter(account => 
                    account.name.toLowerCase().includes(query)
                );
                showFilteredAccounts(dropdownList, filteredAccounts);
            }
            
            dropdownList.style.display = 'block';
        }
        
        // 모든 계정 표시
        function showAllAccounts(dropdownList) {
            const groupedAccounts = groupAccountsByType(accountsData);
            let html = '';
            
            Object.keys(groupedAccounts).forEach(type => {
                html += `<div class="account-dropdown-group">${type.charAt(0).toUpperCase() + type.slice(1)}</div>`;
                groupedAccounts[type].forEach(account => {
                    const categoryTag = account.category_tag || '';
                    html += `<div class="account-dropdown-item" data-account-id="${account.id}" data-category-tag="${categoryTag}" onclick="selectAccount(this)">${account.name}</div>`;
                });
            });
            
            dropdownList.innerHTML = html;
        }
        
        // 필터된 계정 표시
        function showFilteredAccounts(dropdownList, filteredAccounts) {
            let html = '';
            
            if (filteredAccounts.length === 0) {
                html = '<div class="account-dropdown-no-results">No accounts found</div>';
            } else {
                filteredAccounts.forEach(account => {
                    const categoryTag = account.category_tag || '';
                    html += `<div class="account-dropdown-item" data-account-id="${account.id}" data-category-tag="${categoryTag}" onclick="selectAccount(this)">${account.name}</div>`;
                });
            }
            
            dropdownList.innerHTML = html;
        }
        
        // 계정 선택 함수
        function selectAccount(item) {
            const accountId = item.dataset.accountId;
            const accountName = item.textContent;
            const categoryTag = item.dataset.categoryTag;
            const container = item.closest('.account-dropdown-container');
            const input = container.querySelector('.account-search-input');
            const hiddenInput = container.querySelector('.account-id-hidden');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            input.value = accountName;
            hiddenInput.value = accountId;
            dropdownList.style.display = 'none';
            
            // Category Tag 기반 동적 UI 활성화
            const lineElement = container.closest('.journal-line');
            if (lineElement && typeof onAccountSelected === 'function') {
                onAccountSelected(lineElement, accountId, categoryTag);
            }
            
            // 유효성 검사 실행
            validateForm();
        }
        
        // 드롭다운 표시
        function showAccountDropdown(input) {
            currentFocusedDropdown = input;
            const container = input.closest('.account-dropdown-container');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            if (input.value.length === 0) {
                showAllAccounts(dropdownList);
            } else {
                filterAccounts(input);
            }
            
            dropdownList.style.display = 'block';
        }
        
        // 드롭다운 숨기기
        function hideAccountDropdown(input) {
            setTimeout(() => {
                const container = input.closest('.account-dropdown-container');
                const dropdownList = container.querySelector('.account-dropdown-list');
                dropdownList.style.display = 'none';
                currentFocusedDropdown = null;
            }, 200);
        }
        
        // 계정 타입별 그룹화
        function groupAccountsByType(accounts) {
            const grouped = {};
            accounts.forEach(account => {
                if (!grouped[account.type]) {
                    grouped[account.type] = [];
                }
                grouped[account.type].push(account);
            });
            return grouped;
        }
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            initializeAccountsData();
        });
        
        // 클릭 이벤트로 드롭다운 숨기기
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.account-dropdown-container')) {
                document.querySelectorAll('.account-dropdown-list').forEach(list => {
                    list.style.display = 'none';
                });
            }
        });
        
        // Recent Entries Functions
        function showRecentEntriesModal() {
            const modal = new bootstrap.Modal(document.getElementById('recentEntriesModal'));
            modal.show();
            loadRecentEntriesList();
        }
        
        // Recent Entries 버튼 클릭 이벤트 연결
        document.addEventListener('DOMContentLoaded', function() {
            const recentEntriesBtn = document.querySelector('button[onclick*="Recent Entries"]');
            if (recentEntriesBtn) {
                recentEntriesBtn.onclick = showRecentEntriesModal;
            }
        });
        
        function loadRecentEntriesList(offset = 0, limit = 10) {
            const url = `get_recent_entries.php?action=list&offset=${offset}&limit=${limit}&user_id=${userId}&company_id=${companyId}`;
            
            // Show loading spinner in modal
            const modalBody = document.querySelector('#recentEntriesModal .modal-body');
            modalBody.innerHTML = `
                <div class="d-flex justify-content-center align-items-center py-4">
                    <div class="spinner-border text-primary me-3" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div>
                        <h6 class="mb-1">최근 전표를 불러오는 중입니다...</h6>
                        <small class="text-muted">잠시만 기다려주세요.</small>
                    </div>
                </div>
            `;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayRecentEntries(data.entries);
                    } else {
                        console.error('Failed to load recent entries:', data.error);
                        alert('Failed to load recent entries: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading recent entries:', error);
                    const modalBody = document.querySelector('#recentEntriesModal .modal-body');
                    modalBody.innerHTML = `
                        <div class="alert alert-danger">
                            <i class="bi bi-exclamation-circle me-2"></i>
                            최근 전표를 불러오는 중 오류가 발생했습니다.
                            <div class="mt-2">
                                <button class="btn btn-outline-primary btn-sm" onclick="loadRecentEntriesList()">
                                    <i class="bi bi-arrow-clockwise me-1"></i>다시 시도
                                </button>
                            </div>
                        </div>
                    `;
                });
        }
        
        function displayRecentEntries(entries) {
            const tbody = document.getElementById('recent-entries-list');
            if (!tbody) {
                console.error('Recent entries list element not found');
                return;
            }
            
            if (entries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">No recent entries found</td></tr>';
                return;
            }
            
            tbody.innerHTML = entries.map(entry => `
                <tr>
                    <td>${entry.entry_date}</td>
                    <td>${entry.description}</td>
                    <td>${entry.store_name}</td>
                    <td class="text-end">${parseFloat(entry.total_amount).toLocaleString()}</td>
                    <td><span class="badge ${entry.is_draft ? 'bg-warning' : 'bg-success'}">${entry.status}</span></td>
                    <td>
                        <button class="btn btn-sm btn-outline-primary" onclick="viewEntryDetails('${entry.journal_id}')">
                            <i class="bi bi-eye"></i>
                        </button>
                        <button class="btn btn-sm btn-outline-secondary" onclick="copyEntry('${entry.journal_id}')">
                            <i class="bi bi-copy"></i>
                        </button>
                    </td>
                </tr>
            `).join('');
        }
        
        function viewEntryDetails(entryId) {
            const url = `get_recent_entries.php?action=get&entry_id=${entryId}`;
            
            fetch(url)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showEntryDetailsModal(data.entry);
                    } else {
                        alert('Failed to load entry details: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error loading entry details:', error);
                    alert('Error loading entry details: ' + error.message);
                });
        }
        
        function showEntryDetailsModal(entry) {
            // Implementation for showing entry details modal
            alert('Entry Details: ' + entry.description + '\nAmount: ' + entry.base_amount);
        }
        
        function copyEntry(entryId) {
            const url = 'get_recent_entries.php?action=copy';
            
            fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ entry_id: entryId })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        alert('Entry copied! ' + data.message);
                        // Populate form with copied data
                        populateFormWithCopiedData(data.entry_data);
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('recentEntriesModal'));
                        if (modal) modal.hide();
                    } else {
                        alert('Failed to copy entry: ' + data.error);
                    }
                })
                .catch(error => {
                    console.error('Error copying entry:', error);
                    alert('Error copying entry: ' + error.message);
                });
        }
        
        function populateFormWithCopiedData(entryData) {
            // Populate basic fields
            document.getElementById('entry_date').value = entryData.entry_date;
            document.getElementById('store_id').value = entryData.store_id || '';
            document.getElementById('description').value = entryData.description;
            
            // Clear existing lines
            const tbody = document.getElementById('journal-lines-tbody');
            tbody.innerHTML = '';
            
            // Add copied lines
            entryData.lines.forEach((line, index) => {
                addNewJournalLine();
                const lineRow = tbody.children[index];
                
                // Set account
                const accountInput = lineRow.querySelector('.account-search-input');
                const accountHidden = lineRow.querySelector('.account-id-hidden');
                if (accountInput && accountHidden) {
                    accountInput.value = line.account_name;
                    accountHidden.value = line.account_id;
                }
                
                // Set description
                const descInput = lineRow.querySelector('.line-description');
                if (descInput) descInput.value = line.description;
                
                // Set amounts
                const debitInput = lineRow.querySelector('.debit-amount');
                const creditInput = lineRow.querySelector('.credit-amount');
                if (debitInput) debitInput.value = line.debit;
                if (creditInput) creditInput.value = line.credit;
            });
            
            validateForm();
        }
        
        // 키보드 네비게이션 지원
        document.addEventListener('keydown', function(e) {
            if (currentFocusedDropdown) {
                const container = currentFocusedDropdown.closest('.account-dropdown-container');
                const dropdownList = container.querySelector('.account-dropdown-list');
                const items = dropdownList.querySelectorAll('.account-dropdown-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const highlighted = dropdownList.querySelector('.highlighted');
                    if (highlighted) {
                        highlighted.classList.remove('highlighted');
                        const next = highlighted.nextElementSibling;
                        if (next && next.classList.contains('account-dropdown-item')) {
                            next.classList.add('highlighted');
                        } else if (items[0]) {
                            items[0].classList.add('highlighted');
                        }
                    } else if (items[0]) {
                        items[0].classList.add('highlighted');
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const highlighted = dropdownList.querySelector('.highlighted');
                    if (highlighted) {
                        highlighted.classList.remove('highlighted');
                        const prev = highlighted.previousElementSibling;
                        if (prev && prev.classList.contains('account-dropdown-item')) {
                            prev.classList.add('highlighted');
                        } else {
                            const lastItem = items[items.length - 1];
                            if (lastItem) lastItem.classList.add('highlighted');
                        }
                    } else {
                        const lastItem = items[items.length - 1];
                        if (lastItem) lastItem.classList.add('highlighted');
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const highlighted = dropdownList.querySelector('.highlighted');
                    if (highlighted) {
                        selectAccount(highlighted);
                    }
                } else if (e.key === 'Escape') {
                    dropdownList.style.display = 'none';
                    currentFocusedDropdown = null;
                }
            }
        });
        
        // 🔧 Change company function - removed (handled by navigation enhancement)
        // Company changes are now handled by the global navigation system

        // 🎯 Action Button Functions
        // 🚀 Phase 4.3.2: Templates functionality
        let selectedTemplateId = null;
        
        function showTemplatesModal() {
            selectedTemplateId = null;
            document.getElementById('apply-template-btn').disabled = true;
            loadTemplatesList();
            const modal = new bootstrap.Modal(document.getElementById('templatesModal'));
            modal.show();
        }
        
        // 템플릿 목록 로드
        function loadTemplatesList() {
            const listContainer = document.getElementById('templates-list');
            listContainer.innerHTML = `
                <div class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <p class="mt-2 mb-0">Loading templates...</p>
                </div>
            `;
            
            fetch('templates.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTemplatesList(data.templates);
                    } else {
                        listContainer.innerHTML = `
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                                <p class="mt-2 mb-0">Failed to load templates</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading templates:', error);
                    listContainer.innerHTML = `
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-wifi-off fs-1 opacity-50"></i>
                            <p class="mt-2 mb-0">Network error</p>
                        </div>
                    `;
                });
        }
        
        function createNewEntry() {
            // Clear form and focus on entry date
            document.getElementById('entry_date').value = '<?= date('Y-m-d') ?>';
            document.getElementById('store_id').value = '';
            document.getElementById('description').value = '';
            document.getElementById('entry_date').focus();
        }

        // 🚀 Phase 4.3.4: Quick Action Functions - IMPLEMENTED
        window.addQuickLine = function(type) {
            // Clear existing lines first
            clearAllJournalLines();
            
            // Define Quick Action templates
            const quickTemplates = {
                'cash': {
                    description: 'Cash transaction',
                    lines: [
                        {
                            account_name: 'Cash',
                            line_description: 'Cash received',
                            debit: true,
                            amount: ''
                        },
                        {
                            account_name: 'Sales Revenue',
                            line_description: 'Sales revenue',
                            debit: false,
                            amount: ''
                        }
                    ]
                },
                'bank': {
                    description: 'Bank transaction',
                    lines: [
                        {
                            account_name: 'Bank Account',
                            line_description: 'Bank deposit',
                            debit: true,
                            amount: ''
                        },
                        {
                            account_name: 'Sales Revenue',
                            line_description: 'Sales revenue',
                            debit: false,
                            amount: ''
                        }
                    ]
                },
                'expense': {
                    description: 'Expense entry',
                    lines: [
                        {
                            account_name: 'Office Expenses',
                            line_description: 'Office expense',
                            debit: true,
                            amount: ''
                        },
                        {
                            account_name: 'Cash',
                            line_description: 'Cash payment',
                            debit: false,
                            amount: ''
                        }
                    ]
                },
                'revenue': {
                    description: 'Revenue entry',
                    lines: [
                        {
                            account_name: 'Accounts Receivable',
                            line_description: 'Services provided',
                            debit: true,
                            amount: ''
                        },
                        {
                            account_name: 'Service Revenue',
                            line_description: 'Service revenue',
                            debit: false,
                            amount: ''
                        }
                    ]
                }
            };
            
            const template = quickTemplates[type];
            if (!template) {
                showError('Unknown quick action type: ' + type);
                return;
            }
            
            // Apply template to form
            applyQuickTemplate(template);
            
            // Show success message
            showSuccess(`${type.charAt(0).toUpperCase() + type.slice(1)} template applied successfully! Please enter amounts and review before saving.`);
        }
        
        // Apply Quick Template to form using new searchable dropdown system
        function applyQuickTemplate(template) {
            try {
                // Set description
                document.getElementById('description').value = template.description;
                
                // Apply each line using the new searchable dropdown system
                template.lines.forEach((lineData, index) => {
                    // Get the account ID by name
                    const accountId = findAccountIdByName(lineData.account_name);
                    if (!accountId) {
                        console.warn(`Account not found: ${lineData.account_name}`);
                        return;
                    }
                    
                    // Get the account name from our data
                    const account = accountsData.find(acc => acc.id === accountId);
                    if (!account) {
                        console.warn(`Account data not found for ID: ${accountId}`);
                        return;
                    }
                    
                    // Get or create the line element
                    let lineIndex = index + 1;
                    let lineRow = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
                    
                    // If line doesn't exist, add a new one
                    if (!lineRow) {
                        addJournalLine();
                        lineRow = document.querySelector(`tr[data-line-index="${lineCounter}"]`);
                        lineIndex = lineCounter;
                    }
                    
                    if (lineRow) {
                        // Set account using new searchable dropdown system
                        const accountInput = lineRow.querySelector('.account-search-input');
                        const hiddenInput = lineRow.querySelector('.account-id-hidden');
                        
                        if (accountInput && hiddenInput) {
                            accountInput.value = account.name;
                            hiddenInput.value = account.id;
                            
                            // ✨ 작업 5-2: Cash 계정 감지 및 Location 활성화 추가
                            const accountType = detectCashAccountType(accountId, account.categoryTag);
                            if (accountType === 'cash') {
                                console.log('Quick Template: Cash 계정 감지됨 - Location 활성화');
                                showCashLocationColumn(lineRow);
                                showCashAccountGuidance(lineRow);
                                focusLocationDropdown(lineRow);
                            } else {
                                hideCashLocationColumn(lineRow);
                                removeCashAccountGuidance(lineRow);
                            }
                        }
                        
                        // Set line description
                        const descInput = lineRow.querySelector('input[name="line_description[]"]');
                        if (descInput) {
                            descInput.value = lineData.line_description;
                        }
                        
                        // Focus on amount field for user input
                        if (index === 0) {
                            const amountField = lineData.debit ? 
                                lineRow.querySelector('.debit-input') : 
                                lineRow.querySelector('.credit-input');
                            if (amountField) {
                                setTimeout(() => {
                                    amountField.focus();
                                    amountField.select();
                                }, 100);
                            }
                        }
                    }
                });
                
                // Update balance and validation
                updateBalance();
                validateForm();
                updateRemoveButtons();
                
                // Add auto-save listeners to new lines
                addJournalLineAutoSaveListeners();
                
            } catch (error) {
                console.error('Error applying quick template:', error);
                showError('Failed to apply template. Please try again.');
            }
        }
        
        // Find account ID by account name
        function findAccountIdByName(accountName) {
            // 새로운 searchable dropdown에서는 accountsData 배열을 사용
            if (!accountsData || accountsData.length === 0) return null;
            
            // 정확한 이름 매치 먼저 시도
            for (let account of accountsData) {
                if (account.name.trim() === accountName) {
                    return account.id;
                }
            }
            
            // 정확한 매치가 없으면 부분 매치 시도
            for (let account of accountsData) {
                if (account.name.toLowerCase().includes(accountName.toLowerCase())) {
                    return account.id;
                }
            }
            
            return null;
        }
            
            dropdownList.innerHTML = html;
        }
        
        // 필터된 계정 표시
        function showFilteredAccounts(dropdownList, filteredAccounts) {
            let html = '';
            
            if (filteredAccounts.length === 0) {
                html = '<div class="account-dropdown-no-results">No accounts found</div>';
            } else {
                filteredAccounts.forEach(account => {
                    html += `<div class="account-dropdown-item" data-account-id="${account.id}" data-category-tag="${account.category_tag || ''}" onclick="selectAccount(this)">${account.name}</div>`;
                });
            }
            
            dropdownList.innerHTML = html;
        }
        
        // 계정 선택 함수
        function selectAccount(item) {
            const accountId = item.dataset.accountId;
            const accountName = item.textContent;
            const container = item.closest('.account-dropdown-container');
            const input = container.querySelector('.account-search-input');
            const hiddenInput = container.querySelector('.account-id-hidden');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            input.value = accountName;
            hiddenInput.value = accountId;
            dropdownList.style.display = 'none';
            
            // 유효성 검사 실행
            validateForm();
        }
        
        // 드롭다운 표시
        function showAccountDropdown(input) {
            currentFocusedDropdown = input;
            const container = input.closest('.account-dropdown-container');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            if (input.value.length === 0) {
                showAllAccounts(dropdownList);
            } else {
                filterAccounts(input);
            }
            
            dropdownList.style.display = 'block';
        }
        
        // 드롭다운 숨기기
        function hideAccountDropdown(input) {
            setTimeout(() => {
                const container = input.closest('.account-dropdown-container');
                const dropdownList = container.querySelector('.account-dropdown-list');
                dropdownList.style.display = 'none';
                currentFocusedDropdown = null;
            }, 200);
        }
        
        // 계정 타입별 그룹화
        function groupAccountsByType(accounts) {
            const grouped = {};
            accounts.forEach(account => {
                if (!grouped[account.type]) {
                    grouped[account.type] = [];
                }
                grouped[account.type].push(account);
            });
            return grouped;
        }
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            initializeAccountsData();
        });
        
        // 클릭 이벤트로 드롭다운 숨기기
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.account-dropdown-container')) {
                document.querySelectorAll('.account-dropdown-list').forEach(list => {
                    list.style.display = 'none';
                });
            }
        });
        
        // 키보드 네비게이션 지원
        document.addEventListener('keydown', function(e) {
            if (currentFocusedDropdown) {
                const container = currentFocusedDropdown.closest('.account-dropdown-container');
                const dropdownList = container.querySelector('.account-dropdown-list');
                const items = dropdownList.querySelectorAll('.account-dropdown-item');
                
                if (e.key === 'ArrowDown') {
                    e.preventDefault();
                    const highlighted = dropdownList.querySelector('.highlighted');
                    if (highlighted) {
                        highlighted.classList.remove('highlighted');
                        const next = highlighted.nextElementSibling;
                        if (next && next.classList.contains('account-dropdown-item')) {
                            next.classList.add('highlighted');
                        } else if (items[0]) {
                            items[0].classList.add('highlighted');
                        }
                    } else if (items[0]) {
                        items[0].classList.add('highlighted');
                    }
                } else if (e.key === 'ArrowUp') {
                    e.preventDefault();
                    const highlighted = dropdownList.querySelector('.highlighted');
                    if (highlighted) {
                        highlighted.classList.remove('highlighted');
                        const prev = highlighted.previousElementSibling;
                        if (prev && prev.classList.contains('account-dropdown-item')) {
                            prev.classList.add('highlighted');
                        } else {
                            const lastItem = items[items.length - 1];
                            if (lastItem) lastItem.classList.add('highlighted');
                        }
                    } else {
                        const lastItem = items[items.length - 1];
                        if (lastItem) lastItem.classList.add('highlighted');
                    }
                } else if (e.key === 'Enter') {
                    e.preventDefault();
                    const highlighted = dropdownList.querySelector('.highlighted');
                    if (highlighted) {
                        selectAccount(highlighted);
                    }
                } else if (e.key === 'Escape') {
                    dropdownList.style.display = 'none';
                    currentFocusedDropdown = null;
                }
            }
        });
        


        // 🎯 Action Button Functions
        // 🚀 Phase 4.3.2: Templates functionality
        let selectedTemplateId = null;
        
        function showTemplatesModal() {
            selectedTemplateId = null;
            document.getElementById('apply-template-btn').disabled = true;
            loadTemplatesList();
            const modal = new bootstrap.Modal(document.getElementById('templatesModal'));
            modal.show();
        }
        
        // 템플릿 목록 로드
        function loadTemplatesList() {
            const listContainer = document.getElementById('templates-list');
            listContainer.innerHTML = `
                <div class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <p class="mt-2 mb-0">Loading templates...</p>
                </div>
            `;
            
            fetch('templates.php?action=list')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTemplatesList(data.templates);
                    } else {
                        listContainer.innerHTML = `
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-exclamation-triangle fs-1 opacity-50"></i>
                                <p class="mt-2 mb-0">Failed to load templates</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading templates:', error);
                    listContainer.innerHTML = `
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-wifi-off fs-1 opacity-50"></i>
                            <p class="mt-2 mb-0">Network error</p>
                        </div>
                    `;
                });
        }
        
        // 템플릿 목록 표시
        function displayTemplatesList(templates) {
            const listContainer = document.getElementById('templates-list');
            
            if (templates.length === 0) {
                listContainer.innerHTML = `
                    <div class="text-center text-muted py-3">
                        <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                        <p class="mt-2 mb-0">No templates found</p>
                        <small>Save your first template!</small>
                    </div>
                `;
                return;
            }
            
            let html = '';
            templates.forEach(template => {
                const date = new Date(template.created_at).toLocaleDateString();
                html += `
                    <div class="list-group-item list-group-item-action template-item" 
                         data-template-id="${template.template_id}" 
                         onclick="selectTemplate('${template.template_id}')">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="flex-grow-1">
                                <h6 class="mb-1">${template.template_name}</h6>
                                <small class="text-muted">Created: ${date}</small>
                            </div>
                            <button class="btn btn-sm btn-outline-danger" 
                                    onclick="event.stopPropagation(); deleteTemplate('${template.template_id}')">
                                <i class="bi bi-trash"></i>
                            </button>
                        </div>
                    </div>
                `;
            });
            
            listContainer.innerHTML = html;
        }
        
        // 템플릿 선택
        function selectTemplate(templateId) {
            // 이전 선택 해제
            document.querySelectorAll('.template-item').forEach(item => {
                item.classList.remove('active');
            });
            
            // 새 선택 활성화
            const selectedItem = document.querySelector(`[data-template-id="${templateId}"]`);
            if (selectedItem) {
                selectedItem.classList.add('active');
                selectedTemplateId = templateId;
                document.getElementById('apply-template-btn').disabled = false;
                
                // 템플릿 미리보기 로드
                loadTemplatePreview(templateId);
            }
        }
        
        // 템플릿 미리보기 로드
        function loadTemplatePreview(templateId) {
            const previewContainer = document.getElementById('template-preview');
            previewContainer.innerHTML = `
                <div class="text-center text-muted py-3">
                    <div class="spinner-border spinner-border-sm" role="status"></div>
                    <p class="mt-2 mb-0">Loading preview...</p>
                </div>
            `;
            
            fetch(`templates.php?action=get&template_id=${templateId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayTemplatePreview(data.template);
                    } else {
                        previewContainer.innerHTML = `
                            <div class="text-center text-muted py-3">
                                <i class="bi bi-exclamation-triangle text-warning"></i>
                                <p class="mt-2 mb-0">Failed to load preview</p>
                            </div>
                        `;
                    }
                })
                .catch(error => {
                    console.error('Error loading template preview:', error);
                    previewContainer.innerHTML = `
                        <div class="text-center text-muted py-3">
                            <i class="bi bi-wifi-off text-danger"></i>
                            <p class="mt-2 mb-0">Network error</p>
                        </div>
                    `;
                });
        }
        
        // 템플릿 미리보기 표시
        function displayTemplatePreview(template) {
            const previewContainer = document.getElementById('template-preview');
            const data = template.template_data;
            
            let html = `
                <div class="mb-3">
                    <h6 class="text-primary">${template.template_name}</h6>
                    <small class="text-muted">Created: ${new Date(template.created_at).toLocaleDateString()}</small>
                </div>
                
                <div class="mb-3">
                    <strong>Store:</strong> ${data.store_name || 'Not specified'}<br>
                    <strong>Description:</strong> ${data.description || 'Not specified'}
                </div>
                
                <div class="table-responsive">
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Account</th>
                                <th>Description</th>
                                <th>Debit</th>
                                <th>Credit</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            if (data.journal_lines && data.journal_lines.length > 0) {
                data.journal_lines.forEach(line => {
                    html += `
                        <tr>
                            <td>${line.account_name || 'Select Account'}</td>
                            <td>${line.description || ''}</td>
                            <td>${line.debit_amount ? '₫ Amount' : ''}</td>
                            <td>${line.credit_amount ? '₫ Amount' : ''}</td>
                        </tr>
                    `;
                });
            }
            
            html += `
                        </tbody>
                    </table>
                </div>
                
                <div class="text-muted">
                    <small><i class="bi bi-info-circle"></i> Amounts will be empty when applied - you'll need to enter them manually.</small>
                </div>
            `;
            
            previewContainer.innerHTML = html;
        }
        
        // 현재 전표를 템플릿으로 저장
        function saveCurrentAsTemplate() {
            // 기본 검증
            if (!validateBasicInfo()) {
                showError('Please fill in the basic information before saving as template.');
                return;
            }
            
            const journalLines = getJournalLines();
            if (journalLines.length < 2) {
                showError('Template must have at least 2 journal lines.');
                return;
            }
            
            // 템플릿 이름 입력
            const templateName = prompt('Enter template name:');
            if (!templateName || templateName.trim() === '') {
                return;
            }
            
            // 현재 폼 데이터 수집
            const formData = collectFormData();
            
            // 템플릿 저장
            const saveBtn = document.querySelector('[onclick="saveCurrentAsTemplate()"]');
            const originalText = saveBtn.innerHTML;
            saveBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Saving...';
            saveBtn.disabled = true;
            
            fetch('templates.php?action=save', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    template_name: templateName.trim(),
                    template_data: formData
                })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Template saved successfully!');
                    loadTemplatesList(); // 목록 새로고침
                } else {
                    showError(data.error || 'Failed to save template');
                }
            })
            .catch(error => {
                console.error('Error saving template:', error);
                showError('Network error occurred while saving template.');
            })
            .finally(() => {
                saveBtn.innerHTML = originalText;
                saveBtn.disabled = false;
            });
        }
        
        // 선택된 템플릿 적용
        function applySelectedTemplate() {
            if (!selectedTemplateId) {
                showError('Please select a template first.');
                return;
            }
            
            const applyBtn = document.getElementById('apply-template-btn');
            const originalText = applyBtn.innerHTML;
            applyBtn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Applying...';
            applyBtn.disabled = true;
            
            fetch(`templates.php?action=get&template_id=${selectedTemplateId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        applyTemplateToForm(data.template.template_data);
                        showSuccess('Template applied successfully!');
                        
                        // 모달 닫기
                        const modal = bootstrap.Modal.getInstance(document.getElementById('templatesModal'));
                        modal.hide();
                    } else {
                        showError(data.error || 'Failed to apply template');
                    }
                })
                .catch(error => {
                    console.error('Error applying template:', error);
                    showError('Network error occurred while applying template.');
                })
                .finally(() => {
                    applyBtn.innerHTML = originalText;
                    applyBtn.disabled = false;
                });
        }
        
        // 템플릿을 폼에 적용
        function applyTemplateToForm(templateData) {
            try {
                // Store 설정 (ID로 찾아서 설정)
                if (templateData.store_id) {
                    const storeSelect = document.getElementById('store_id');
                    storeSelect.value = templateData.store_id;
                }
                
                // Description 설정
                if (templateData.description) {
                    document.getElementById('description').value = templateData.description;
                }
                
                // Journal lines 설정
                if (templateData.journal_lines && templateData.journal_lines.length > 0) {
                    // 기존 라인 제거 (첫 번째 제외)
                    const lines = document.querySelectorAll('.journal-line');
                    for (let i = lines.length - 1; i > 0; i--) {
                        lines[i].remove();
                    }
                    
                    // 템플릿 라인 적용
                    templateData.journal_lines.forEach((lineData, index) => {
                        let line;
                        if (index === 0) {
                            // 첫 번째 라인은 기존 것 사용
                            line = document.querySelector('.journal-line');
                        } else {
                            // 새 라인 추가
                            addJournalLine();
                            line = document.querySelectorAll('.journal-line')[index];
                        }
                        
                        // 계정 설정
                        if (lineData.account_id) {
                            const accountSelect = line.querySelector('.account-select');
                            accountSelect.value = lineData.account_id;
                        }
                        
                        // 설명 설정
                        if (lineData.description) {
                            const descInput = line.querySelector('.line-description');
                            descInput.value = lineData.description;
                        }
                        
                        // 금액은 템플릿에서 비워두기 (사용자가 입력해야 함)
                    });
                    
                    // 라인 이벤트 리스너 다시 추가
                    addJournalLineAutoSaveListeners();
                }
                
                // 폼 검증 업데이트
                updateBalance();
                updateRemoveButtons();
                validateForm();
                
            } catch (error) {
                console.error('Error applying template:', error);
                showError('Failed to apply template to form.');
            }
        }
        
        // 템플릿 삭제
        function deleteTemplate(templateId) {
            if (!confirm('Are you sure you want to delete this template?')) {
                return;
            }
            
            fetch(`templates.php?action=delete&template_id=${templateId}`, {
                method: 'DELETE'
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Template deleted successfully!');
                    loadTemplatesList(); // 목록 새로고침
                    
                    // 삭제된 템플릿이 선택되어 있었다면 선택 해제
                    if (selectedTemplateId === templateId) {
                        selectedTemplateId = null;
                        document.getElementById('apply-template-btn').disabled = true;
                        document.getElementById('template-preview').innerHTML = `
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-eye fs-1 opacity-50"></i>
                                <p class="mt-2 mb-0">Select a template to preview</p>
                            </div>
                        `;
                    }
                } else {
                    showError(data.error || 'Failed to delete template');
                }
            })
            .catch(error => {
                console.error('Error deleting template:', error);
                showError('Network error occurred while deleting template.');
            });
        }

        // 🚀 Phase 4.3.3: Recent Entries functionality
        function showRecentEntriesModal() {
            loadRecentEntriesList();
            const modal = new bootstrap.Modal(document.getElementById('recentEntriesModal'));
            modal.show();
        }
        
        // Load recent entries list
        function loadRecentEntriesList(limit = 10, offset = 0) {
            const tbody = document.getElementById('recent-entries-tbody');
            
            // Show loading spinner
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                        <div class="mt-2">Loading recent entries...</div>
                    </td>
                </tr>
            `;
            
            fetch(`get_recent_entries.php?limit=${limit}&offset=${offset}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        displayRecentEntriesList(data.entries);
                    } else {
                        showRecentEntriesError('Failed to load recent entries: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading recent entries:', error);
                    showRecentEntriesError('Network error occurred. Please try again.');
                });
        }
        
        // Display recent entries in the table
        function displayRecentEntriesList(entries) {
            const tbody = document.getElementById('recent-entries-tbody');
            
            if (entries.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7" class="text-center text-muted">
                            <i class="bi bi-inbox fs-1 d-block mb-2"></i>
                            No recent entries found.
                        </td>
                    </tr>
                `;
                return;
            }
            
            tbody.innerHTML = '';
            entries.forEach(entry => {
                const row = document.createElement('tr');
                
                // Format date
                const entryDate = new Date(entry.entry_date).toLocaleDateString();
                
                // Format amount with currency
                const formattedAmount = '<?= $currency_symbol ?>' + entry.total_amount.toLocaleString();
                
                // Status badge
                const statusBadge = entry.is_draft ? 
                    '<span class="badge bg-warning">Draft</span>' : 
                    '<span class="badge bg-success">Completed</span>';
                
                row.innerHTML = `
                    <td>${entryDate}</td>
                    <td>${entry.store_name}</td>
                    <td>
                        <div class="text-truncate" style="max-width: 200px;" title="${entry.description}">
                            ${entry.description}
                        </div>
                    </td>
                    <td><span class="badge bg-secondary">${entry.line_count}</span></td>
                    <td class="text-end">${formattedAmount}</td>
                    <td>${statusBadge}</td>
                    <td>
                        <div class="btn-group btn-group-sm" role="group">
                            <button class="btn btn-outline-info" onclick="viewEntryDetails('${entry.journal_id}')" title="View Details">
                                <i class="bi bi-eye"></i>
                            </button>
                            <button class="btn btn-outline-primary" onclick="copyEntryAsNew('${entry.journal_id}')" title="Copy Entry">
                                <i class="bi bi-copy"></i>
                            </button>
                            <button class="btn btn-outline-secondary" onclick="editEntry('${entry.journal_id}')" title="Edit Entry">
                                <i class="bi bi-pencil"></i>
                            </button>
                        </div>
                    </td>
                `;
                
                tbody.appendChild(row);
            });
        }
        
        // Show error message in recent entries table
        function showRecentEntriesError(message) {
            const tbody = document.getElementById('recent-entries-tbody');
            tbody.innerHTML = `
                <tr>
                    <td colspan="7" class="text-center text-danger">
                        <i class="bi bi-exclamation-triangle fs-1 d-block mb-2"></i>
                        ${message}
                        <div class="mt-2">
                            <button class="btn btn-outline-primary btn-sm" onclick="loadRecentEntriesList()">
                                <i class="bi bi-arrow-clockwise me-1"></i>Try Again
                            </button>
                        </div>
                    </td>
                </tr>
            `;
        }
        
        // Refresh recent entries list
        function refreshRecentEntries() {
            loadRecentEntriesList();
        }
        
        // Filter recent entries by date range
        function filterRecentEntries(days) {
            // For now, just refresh the list
            // In a future version, we can add date filtering
            loadRecentEntriesList();
            
            // Update button states
            document.querySelectorAll('#recentEntriesModal .btn-outline-primary').forEach(btn => {
                btn.classList.remove('active');
            });
            event.target.classList.add('active');
        }
        
        // View entry details (for future implementation)
        function viewEntryDetails(entryId) {
            fetch(`get_recent_entries.php?action=get&entry_id=${entryId}&user_id=${userId}&company_id=${companyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        // For now, show a simple alert with details
                        const entry = data.entry;
                        let details = `Entry Details:\n\n`;
                        details += `Date: ${entry.entry_date}\n`;
                        details += `Description: ${entry.description}\n`;
                        details += `Amount: <?= $currency_symbol ?>${entry.base_amount.toLocaleString()}\n\n`;
                        details += `Lines (${entry.lines.length}):\n`;
                        entry.lines.forEach((line, index) => {
                            details += `${index + 1}. ${line.account_name}\n`;
                            details += `   Debit: ${line.debit ? '<?= $currency_symbol ?>' + line.debit.toLocaleString() : '-'}\n`;
                            details += `   Credit: ${line.credit ? '<?= $currency_symbol ?>' + line.credit.toLocaleString() : '-'}\n`;
                        });
                        alert(details);
                    } else {
                        showError('Failed to load entry details: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading entry details:', error);
                    showError('Network error occurred while loading entry details.');
                });
        }
        
        // Copy entry as new
        function copyEntryAsNew(entryId) {
            if (!confirm('Copy this entry as a new journal entry? The date will be set to today.')) {
                return;
            }
            
            fetch(`get_recent_entries.php?action=copy&user_id=${userId}&company_id=${companyId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ entry_id: entryId })
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    applyEntryToForm(data.entry_data);
                    showSuccess('Entry copied successfully! Please review the date and amounts before saving.');
                    
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('recentEntriesModal'));
                    modal.hide();
                } else {
                    showError('Failed to copy entry: ' + (data.error || 'Unknown error'));
                }
            })
            .catch(error => {
                console.error('Error copying entry:', error);
                showError('Network error occurred while copying entry.');
            });
        }
        
        // Edit entry (load existing entry for modification)
        function editEntry(entryId) {
            if (!confirm('Load this entry for editing? Any unsaved changes will be lost.')) {
                return;
            }
            
            fetch(`get_recent_entries.php?action=get&entry_id=${entryId}&user_id=${userId}&company_id=${companyId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        loadEntryForEdit(data.entry);
                        showSuccess('Entry loaded for editing. Make your changes and save.');
                        
                        // Close the modal
                        const modal = bootstrap.Modal.getInstance(document.getElementById('recentEntriesModal'));
                        modal.hide();
                    } else {
                        showError('Failed to load entry: ' + (data.error || 'Unknown error'));
                    }
                })
                .catch(error => {
                    console.error('Error loading entry:', error);
                    showError('Network error occurred while loading entry.');
                });
        }
        
        // Apply entry data to form (for copying)
        function applyEntryToForm(entryData) {
            // Clear existing form
            resetForm();
            
            // Set basic info
            document.getElementById('entry_date').value = entryData.entry_date;
            if (entryData.store_id) {
                document.getElementById('store_id').value = entryData.store_id;
            }
            document.getElementById('description').value = entryData.description;
            
            // Clear existing lines and add new ones
            clearAllJournalLines();
            
            entryData.lines.forEach((line, index) => {
                if (index > 0) {
                    addJournalLine();
                }
                
                const lineIndex = index + 1;
                const accountSelect = document.getElementById(`account_${lineIndex}`);
                const descriptionInput = document.getElementById(`line_description_${lineIndex}`);
                const debitInput = document.getElementById(`debit_${lineIndex}`);
                const creditInput = document.getElementById(`credit_${lineIndex}`);
                
                if (accountSelect) accountSelect.value = line.account_id;
                if (descriptionInput) descriptionInput.value = line.description;
                if (debitInput && line.debit > 0) debitInput.value = line.debit;
                if (creditInput && line.credit > 0) creditInput.value = line.credit;
            });
            
            // Update totals and validate
            updateBalance();
            validateForm();
        }
        
        // Load entry for editing (preserves journal_id for updates)
        function loadEntryForEdit(entry) {
            // Store the journal_id for future updates
            window.editingEntryId = entry.journal_id;
            
            // Apply the entry data to the form
            applyEntryToForm(entry);
            
            // Update save button text to indicate editing mode
            const saveBtn = document.getElementById('save-btn');
            if (saveBtn) {
                saveBtn.innerHTML = '<i class="bi bi-pencil-square me-1"></i>Update Entry';
                saveBtn.classList.remove('btn-success');
                saveBtn.classList.add('btn-warning');
            }
        }

        function createNewEntry() {
            // Clear form and focus on entry date
            document.getElementById('entry_date').value = '<?= date('Y-m-d') ?>';
            document.getElementById('store_id').value = '';
            document.getElementById('description').value = '';
            document.getElementById('entry_date').focus();
        }

        // 🚀 Phase 4.3.4: Quick Action Functions - IMPLEMENTED
        window.addQuickLine = function(type) {
            // Clear existing lines first
            clearAllJournalLines();
            
            // Define Quick Action templates
            const quickTemplates = {
                'cash': {
                    description: 'Cash transaction',
                    lines: [
                        {
                            account_name: 'Cash',
                            line_description: 'Cash received',
                            debit: true,
                            amount: ''
                        },
                        {
                            account_name: 'Sales Revenue',
                            line_description: 'Sales revenue',
                            debit: false,
                            amount: ''
                        }
                    ]
                },
                'bank': {
                    description: 'Bank transaction',
                    lines: [
                        {
                            account_name: 'Bank Account',
                            line_description: 'Bank deposit',
                            debit: true,
                            amount: ''
                        },
                        {
                            account_name: 'Sales Revenue',
                            line_description: 'Sales revenue',
                            debit: false,
                            amount: ''
                        }
                    ]
                },
                'expense': {
                    description: 'Expense entry',
                    lines: [
                        {
                            account_name: 'Office Expenses',
                            line_description: 'Office expense',
                            debit: true,
                            amount: ''
                        },
                        {
                            account_name: 'Cash',
                            line_description: 'Cash payment',
                            debit: false,
                            amount: ''
                        }
                    ]
                },
                'revenue': {
                    description: 'Revenue entry',
                    lines: [
                        {
                            account_name: 'Accounts Receivable',
                            line_description: 'Services provided',
                            debit: true,
                            amount: ''
                        },
                        {
                            account_name: 'Service Revenue',
                            line_description: 'Service revenue',
                            debit: false,
                            amount: ''
                        }
                    ]
                }
            };
            
            const template = quickTemplates[type];
            if (!template) {
                showError('Unknown quick action type: ' + type);
                return;
            }
            
            // Apply template to form
            applyQuickTemplate(template);
            
            // Show success message
            showSuccess(`${type.charAt(0).toUpperCase() + type.slice(1)} template applied successfully! Please enter amounts and review before saving.`);
        }
        
        // Apply Quick Template to form
        function applyQuickTemplate(template) {
            try {
                // Set description
                document.getElementById('description').value = template.description;
                
                // Apply each line
                template.lines.forEach((lineData, index) => {
                    // Get the account ID by name
                    const accountId = findAccountIdByName(lineData.account_name);
                    if (!accountId) {
                        console.warn(`Account not found: ${lineData.account_name}`);
                        return;
                    }
                    
                    // Get or create the line element
                    let lineIndex = index + 1;
                    let lineRow = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
                    
                    // If line doesn't exist, add a new one
                    if (!lineRow) {
                        addJournalLine();
                        lineRow = document.querySelector(`tr[data-line-index="${lineCounter}"]`);
                        lineIndex = lineCounter;
                    }
                    
                    if (lineRow) {
                        // Set account
                        const accountSelect = lineRow.querySelector('.account-search-input');
                        if (accountSelect) {
                            accountSelect.value = accountId;
                        }
                        
                        // Set line description
                        const descInput = lineRow.querySelector('.line-description');
                        if (descInput) {
                            descInput.value = lineData.line_description;
                        }
                        
                        // Focus on amount field for user input
                        if (index === 0) {
                            const amountField = lineData.debit ? 
                                lineRow.querySelector('.debit-input') : 
                                lineRow.querySelector('.credit-input');
                            if (amountField) {
                                setTimeout(() => {
                                    amountField.focus();
                                    amountField.select();
                                }, 100);
                            }
                        }
                    }
                });
                
                // Update balance and validation
                updateBalance();
                validateForm();
                updateRemoveButtons();
                
                // Add auto-save listeners to new lines
                addJournalLineAutoSaveListeners();
                
            } catch (error) {
                console.error('Error applying quick template:', error);
                showError('Failed to apply template. Please try again.');
            }
        }
        
        // Find account ID by account name
        function findAccountIdByName(accountName) {
            // 새로운 searchable dropdown에서는 accountsData 배열을 사용
            if (!accountsData || accountsData.length === 0) return null;
            
            // 정확한 이름 매치 먼저 시도
            for (let account of accountsData) {
                if (account.name.trim() === accountName) {
                    return account.id;
                }
            }
            
            // 정확한 매치가 없으면 부분 매치 시도
            for (let account of accountsData) {
                if (account.name.toLowerCase().includes(accountName.toLowerCase())) {
                    return account.id;
                }
            }
            
            return null;
        }
        
        // 🔍 **Phase 4.3.5: 고급 검증 및 경고 시스템 - IMPLEMENTED**
        
        // 계정 유형별 정상적인 Debit/Credit 패턴 정의
        const accountNormalBalances = {
            'asset': 'debit',      // 자산: 차변 증가
            'expense': 'debit',    // 비용: 차변 증가
            'income': 'credit',    // 수익: 대변 증가
            'liability': 'credit', // 부체: 대변 증가
            'equity': 'credit'     // 자본: 대변 증가
        };
        
        // 계정 유형 데이터 (서버에서 가져온 데이터)
        const accountTypes = {
            'a33984c9-6e78-46c8-8aed-2f7183cd7320': 'asset',     // Accounts Receivable
            'f0e7baca-c465-4efe-9b5a-cbb942caaf49': 'asset',     // Bank Account
            '6909d222-b796-481b-ab6b-f0c4a51d7029': 'asset',     // Cash
            'b8423e76-9d14-4665-a5c0-5732fd697c41': 'asset',     // Inventory
            'b54c891d-479d-48fe-b0f5-a301e41b1cc0': 'equity',    // Owner Equity
            '302b68fe-6810-493d-9f89-6a5173bff612': 'equity',    // Retained Earnings
            'f98b20a0-5106-40b5-b8cc-83de39f670e5': 'expense',   // Cost of Goods Sold
            '49002feb-9580-418a-b701-052f87d7ebf8': 'expense',   // Office Expenses
            'fcd9750c-2785-4d04-8161-672e6a1d1017': 'expense',   // Rent Expense
            '95787233-f017-4485-bb73-f9e8dff6546f': 'expense',   // Salary Expense
            '83c8bd23-c479-4d9a-967a-6cc5cfcbf6d6': 'expense',   // Utilities Expense
            'fb79e7f0-fe29-4bfa-b4b9-7e2365cc1389': 'income',    // Sales Revenue
            '0a67430c-899c-4186-809d-225f23cc3ff6': 'income',    // Service Revenue
            '95364463-beaf-4574-a17f-7656d73939c6': 'liability', // Accounts Payable
            '415c5a5a-8a7c-4cfe-a86c-74027190531e': 'liability'  // Notes Payable
        };
        
        // 계정 이름 매핑
        const accountNames = {
            'a33984c9-6e78-46c8-8aed-2f7183cd7320': 'Accounts Receivable',
            'f0e7baca-c465-4efe-9b5a-cbb942caaf49': 'Bank Account',
            '6909d222-b796-481b-ab6b-f0c4a51d7029': 'Cash',
            'b8423e76-9d14-4665-a5c0-5732fd697c41': 'Inventory',
            'b54c891d-479d-48fe-b0f5-a301e41b1cc0': 'Owner Equity',
            '302b68fe-6810-493d-9f89-6a5173bff612': 'Retained Earnings',
            'f98b20a0-5106-40b5-b8cc-83de39f670e5': 'Cost of Goods Sold',
            '49002feb-9580-418a-b701-052f87d7ebf8': 'Office Expenses',
            'fcd9750c-2785-4d04-8161-672e6a1d1017': 'Rent Expense',
            '95787233-f017-4485-bb73-f9e8dff6546f': 'Salary Expense',
            '83c8bd23-c479-4d9a-967a-6cc5cfcbf6d6': 'Utilities Expense',
            'fb79e7f0-fe29-4bfa-b4b9-7e2365cc1389': 'Sales Revenue',
            '0a67430c-899c-4186-809d-225f23cc3ff6': 'Service Revenue',
            '95364463-beaf-4574-a17f-7656d73939c6': 'Accounts Payable',
            '415c5a5a-8a7c-4cfe-a86c-74027190531e': 'Notes Payable'
        };
        
        // 고급 검증 및 경고 시스템
        function performAdvancedValidation() {
            const warnings = [];
            const errors = [];
            
            // 1. 계정 유형별 Debit/Credit 적절성 검증
            const debitCreditWarnings = validateDebitCreditAppropriates();
            warnings.push(...debitCreditWarnings);
            
            // 2. 이상 거래 금액 경고
            const amountWarnings = validateTransactionAmounts();
            warnings.push(...amountWarnings);
            
            // 3. 중복 Reference Number 검증
            const referenceWarnings = validateReferenceNumber();
            warnings.push(...referenceWarnings);
            
            // 4. 입력 패턴 분석 및 제안
            const patternSuggestions = analyzeInputPatterns();
            warnings.push(...patternSuggestions);
            
            // 경고 및 오류 표시
            displayValidationResults(warnings, errors);
            
            return {
                warnings: warnings,
                errors: errors,
                isValid: errors.length === 0
            };
        }
        
        // 계정 유형별 Debit/Credit 적절성 검증
        function validateDebitCreditAppropriates() {
            const warnings = [];
            const journalLines = document.querySelectorAll('.journal-line');
            
            journalLines.forEach((line, index) => {
                // 새로운 searchable dropdown에서 계정 ID 가져오기
                const hiddenInput = line.querySelector('.account-id-hidden');
                const accountId = hiddenInput ? hiddenInput.value : '';
                const debitInput = line.querySelector('.debit-input');
                const creditInput = line.querySelector('.credit-input');
                
                if (!accountId) return;
                const accountType = accountTypes[accountId];
                const accountName = accountNames[accountId];
                const normalSide = accountNormalBalances[accountType];
                
                const debitAmount = parseFloat(debitInput.value) || 0;
                const creditAmount = parseFloat(creditInput.value) || 0;
                
                // 비정상적인 입력 검상
                if (normalSide === 'debit' && creditAmount > 0 && debitAmount === 0) {
                    warnings.push({
                        type: 'account_side_warning',
                        line: index + 1,
                        message: `경고: ${accountName}는 일반적으로 차변(증가)에 기록됩니다. 대변에 기록하는 것이 맞나요?`,
                        severity: 'warning'
                    });
                } else if (normalSide === 'credit' && debitAmount > 0 && creditAmount === 0) {
                    warnings.push({
                        type: 'account_side_warning',
                        line: index + 1,
                        message: `경고: ${accountName}는 일반적으로 대변(증가)에 기록됩니다. 차변에 기록하는 것이 맞나요?`,
                        severity: 'warning'
                    });
                }
            });
            
            return warnings;
        }
        
        // 이상 거래 금액 경고
        function validateTransactionAmounts() {
            const warnings = [];
            const journalLines = document.querySelectorAll('.journal-line');
            const highAmountThreshold = 100000000; // 1억원
            const unusualAmountThreshold = 10000000; // 1천만원
            
            journalLines.forEach((line, index) => {
                const debitInput = line.querySelector('.debit-input');
                const creditInput = line.querySelector('.credit-input');
                // 새로운 searchable dropdown에서 계정 ID 가져오기
                const hiddenInput = line.querySelector('.account-id-hidden');
                const accountId = hiddenInput ? hiddenInput.value : '';
                
                const debitAmount = parseFloat(debitInput.value) || 0;
                const creditAmount = parseFloat(creditInput.value) || 0;
                const amount = Math.max(debitAmount, creditAmount);
                
                if (amount > highAmountThreshold) {
                    warnings.push({
                        type: 'high_amount_warning',
                        line: index + 1,
                        message: `경고: 라인 ${index + 1}의 금액이 ${(amount / 100000000).toFixed(1)}억원으로 매우 높습니다. 금액을 확인해 주세요.`,
                        severity: 'error'
                    });
                } else if (amount > unusualAmountThreshold) {
                    warnings.push({
                        type: 'unusual_amount_warning',
                        line: index + 1,
                        message: `주의: 라인 ${index + 1}의 금액이 ${(amount / 10000000).toFixed(1)}천만원입니다. 금액이 정확한지 확인해 주세요.`,
                        severity: 'warning'
                    });
                }
                
                // 소수점 이하 금액 경고
                if (amount > 0 && amount < 1) {
                    warnings.push({
                        type: 'small_amount_warning',
                        line: index + 1,
                        message: `주의: 라인 ${index + 1}의 금액이 1원 미만입니다. 금액을 확인해 주세요.`,
                        severity: 'info'
                    });
                }
            });
            
            return warnings;
        }
        
        // Reference Number 검증 제거됨
                    });
                }
                
                // TODO: 실제 데이터베이스 중복 검상은 저장 시 서버에서 처리
                // 여기서는 기본적인 형식 검증만 수행
            }
            
            return warnings;
        }
        
        // 입력 패턴 분석 및 제안
        function analyzeInputPatterns() {
            const suggestions = [];
            const journalLines = document.querySelectorAll('.journal-line');
            let activeLinesCount = 0;
            let hasEmptyDescriptions = false;
            let hasIncompleteLines = false;
            
            journalLines.forEach((line, index) => {
                // 새로운 searchable dropdown에서 계정 ID 가져오기
                const hiddenInput = line.querySelector('.account-id-hidden');
                const accountId = hiddenInput ? hiddenInput.value : '';
                const debitInput = line.querySelector('.debit-input');
                const creditInput = line.querySelector('.credit-input');
                const descInput = line.querySelector('.line-description');
                
                const hasAccount = accountId !== '';
                const hasAmount = (parseFloat(debitInput.value) || 0) > 0 || (parseFloat(creditInput.value) || 0) > 0;
                const hasDescription = descInput.value.trim() !== '';
                
                if (hasAccount || hasAmount) {
                    activeLinesCount++;
                    
                    if (!hasDescription) {
                        hasEmptyDescriptions = true;
                    }
                    
                    if (hasAccount && !hasAmount) {
                        hasIncompleteLines = true;
                    }
                }
            });
            
            // 전표 라인 수 및 완성도 분석
            if (activeLinesCount < 2) {
                suggestions.push({
                    type: 'pattern_suggestion',
                    message: `제안: 복식부기 원칙에 따라 최소 2개 이상의 라인이 필요합니다.`,
                    severity: 'info'
                });
            } else if (activeLinesCount > 10) {
                suggestions.push({
                    type: 'pattern_suggestion',
                    message: `주의: 라인이 ${activeLinesCount}개로 매우 많습니다. 전표를 분할하는 것을 고려해 보세요.`,
                    severity: 'info'
                });
            }
            
            if (hasEmptyDescriptions) {
                suggestions.push({
                    type: 'pattern_suggestion',
                    message: `제안: 모든 라인에 설명을 입력하면 추후 추적이 용이합니다.`,
                    severity: 'info'
                });
            }
            
            if (hasIncompleteLines) {
                suggestions.push({
                    type: 'pattern_suggestion',
                    message: `주의: 계정이 선택되었지만 금액이 입력되지 않은 라인이 있습니다.`,
                    severity: 'warning'
                });
            }
            
            return suggestions;
        }
        
        // 검증 결과 표시
        function displayValidationResults(warnings, errors) {
            // 기존 경고 메시지 제거
            document.querySelectorAll('.validation-warning').forEach(el => el.remove());
            
            if (warnings.length === 0 && errors.length === 0) {
                return;
            }
            
            // 경고 및 오류 표시 영역 생성
            const validationContainer = document.createElement('div');
            validationContainer.className = 'validation-warning mt-3';
            
            let html = '<div class="alert alert-warning">';
            html += '<h6><i class="bi bi-exclamation-triangle me-2"></i>고급 검증 결과</h6>';
            html += '<ul class="mb-0">';
            
            // 오류 표시 (우선순위)
            errors.forEach(error => {
                html += `<li class="text-danger"><strong>오류:</strong> ${error.message}</li>`;
            });
            
            // 경고 표시
            warnings.forEach(warning => {
                const colorClass = warning.severity === 'error' ? 'text-danger' : 
                                   warning.severity === 'warning' ? 'text-warning' : 'text-info';
                const prefix = warning.severity === 'error' ? '오류' : 
                               warning.severity === 'warning' ? '경고' : '정보';
                html += `<li class="${colorClass}"><strong>${prefix}:</strong> ${warning.message}</li>`;
            });
            
            html += '</ul></div>';
            validationContainer.innerHTML = html;
            
            // 밸런스 체크 섹션 뒤에 삽입
            const balanceSection = document.querySelector('.base-card:has(#balance-check)');
            if (balanceSection) {
                balanceSection.insertAdjacentElement('afterend', validationContainer);
            } else {
                // 대체 위치: 폼의 끝에 삽입
                const formContainer = document.querySelector('.base-card:has(#journal-lines-table)');
                if (formContainer) {
                    formContainer.appendChild(validationContainer);
                }
            }
        }
        
        // 입력 이벤트에 고급 검증 추가
        function addAdvancedValidationListeners() {
            // 기존 입력 이벤트에 고급 검증 추가
            document.addEventListener('input', function(e) {
                if (e.target.matches('.account-search-input, .debit-input, .credit-input, .line-description')) {
                    // 지연 실행으로 성능 최적화
                    clearTimeout(window.advancedValidationTimeout);
                    window.advancedValidationTimeout = setTimeout(() => {
                        performAdvancedValidation();
                    }, 1000); // 1초 지연
                }
            });
            
            // 계정 선택 시 즉시 검증 (새로운 dropdown에서는 계정 선택이 selectAccount 함수에서 처리됨)
            // selectAccount 함수에서 이미 validateForm()을 호출하므로 여기서는 제거
        }

        // 📊 Journal Lines Management - Phase 3.2
        let lineCounter = 2; // Start with 2 lines already created
        
        // 🔄 Account options for dynamic rows
        const accountOptions = `<?= $accountOptions ?>`;

        function addJournalLine() {
            lineCounter++;
            const tbody = document.getElementById('journal-lines-tbody');
            
            const newRow = document.createElement('tr');
            newRow.className = 'journal-line';
            newRow.setAttribute('data-line-index', lineCounter);
            
            newRow.innerHTML = `
                <td class="text-center line-number">${lineCounter}</td>
                <td>
                    <div class="account-dropdown-container">
                        <input type="text" class="form-control form-control-sm account-search-input" 
                               placeholder="Search accounts..." 
                               onkeyup="filterAccounts(this)" 
                               onblur="hideAccountDropdown(this)"
                               onfocus="showAccountDropdown(this)"
                               autocomplete="off">
                        <input type="hidden" class="account-id-hidden" name="account_id[]">
                        <div class="account-dropdown-list" style="display: none;">
                            <!-- 동적으로 계정 목록 생성 -->
                        </div>
                    </div>
                </td>
                <td>
                    <input type="text" class="form-control form-control-sm" name="line_description[]" placeholder="Enter description">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm debit-input" name="debit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                </td>
                <td>
                    <input type="number" class="form-control form-control-sm credit-input" name="credit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                </td>
                <td class="location-selector-container">
                    <select class="form-select form-select-sm cash-location-select" style="display: none;">
                        <option value="">Select Location</option>
                    </select>
                </td>
                <td class="text-center">
                    <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" onclick="removeJournalLine(${lineCounter})">
                        <i class="bi bi-trash"></i>
                    </button>
                </td>
            `;
            
            tbody.appendChild(newRow);
            updateRemoveButtons();
            
            // 🚀 새로 추가된 라인에 자동 저장 리스너 추가
            addJournalLineAutoSaveListeners();
        }

        function removeJournalLine(lineIndex) {
            const rows = document.querySelectorAll('.journal-line');
            
            // Must keep at least 2 lines
            if (rows.length <= 2) {
                alert('At least 2 journal lines are required for double-entry bookkeeping.');
                return;
            }
            
            const rowToRemove = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
            if (rowToRemove) {
                rowToRemove.remove();
                updateLineNumbers();
                updateRemoveButtons();
                updateBalance();
            }
        }

        function updateLineNumbers() {
            const rows = document.querySelectorAll('.journal-line');
            rows.forEach((row, index) => {
                const lineNumber = index + 1;
                row.setAttribute('data-line-index', lineNumber);
                row.querySelector('.line-number').textContent = lineNumber;
                row.querySelector('.remove-line-btn').setAttribute('onclick', `removeJournalLine(${lineNumber})`);
            });
            lineCounter = rows.length;
        }

        function updateRemoveButtons() {
            const removeButtons = document.querySelectorAll('.remove-line-btn');
            const totalLines = removeButtons.length;
            
            removeButtons.forEach((button, index) => {
                // Disable remove button if only 2 lines remain
                button.disabled = totalLines <= 2;
            });
        }
        
        // Clear all journal lines except the first two
        window.clearAllJournalLines = function() {
            const tbody = document.getElementById('journal-lines-tbody');
            const rows = tbody.querySelectorAll('.journal-line');
            
            // Remove all lines except the first two
            for (let i = rows.length - 1; i >= 2; i--) {
                rows[i].remove();
            }
            
            // Clear the content of the first two lines
            for (let i = 0; i < Math.min(2, rows.length); i++) {
                const row = rows[i];
                const accountSelect = row.querySelector('.account-select');
                const descriptionInput = row.querySelector('.line-description');
                const debitInput = row.querySelector('.debit-input');
                const creditInput = row.querySelector('.credit-input');
                
                if (accountSelect) accountSelect.value = '';
                if (descriptionInput) descriptionInput.value = '';
                if (debitInput) debitInput.value = '';
                if (creditInput) creditInput.value = '';
            }
            
            // Reset line counter
            lineCounter = 2;
            
            // Update line numbers and totals
            updateLineNumbers();
            updateBalance();
            validateForm();
        }

        // 🧮 Balance Calculation - Phase 3.1
        // 💰 Debit/Credit Mutual Exclusivity - Phase 3.2
        function handleDebitCreditInput(input) {
            const row = input.closest('tr');
            const debitInput = row.querySelector('.debit-input');
            const creditInput = row.querySelector('.credit-input');
            
            if (input.classList.contains('debit-input') && input.value) {
                // If debit has value, clear credit
                creditInput.value = '';
            } else if (input.classList.contains('credit-input') && input.value) {
                // If credit has value, clear debit
                debitInput.value = '';
            }
            
            updateBalance();
        }

        // 🧠 Balance Calculation - Phase 3.2
        function updateBalance() {
            const debitInputs = document.querySelectorAll('.debit-input');
            const creditInputs = document.querySelectorAll('.credit-input');
            
            let totalDebit = 0;
            let totalCredit = 0;
            
            debitInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                totalDebit += value;
            });
            
            creditInputs.forEach(input => {
                const value = parseFloat(input.value) || 0;
                totalCredit += value;
            });
            
            const difference = totalDebit - totalCredit;
            
            // Update balance display in sidebar
            updateBalanceDisplay(totalDebit, totalCredit, difference);
        }

        function updateBalanceDisplay(debitTotal, creditTotal, difference) {
            const currencySymbol = '<?= $currency_symbol ?>';
            
            // Update sidebar balance info
            const balanceInfo = document.querySelector('.balance-info');
            if (balanceInfo) {
                balanceInfo.innerHTML = `
                    <div class="d-flex justify-content-between py-2">
                        <span>Total Debit:</span>
                        <span class="text-primary fw-bold">${currencySymbol} ${debitTotal.toFixed(2)}</span>
                    </div>
                    <div class="d-flex justify-content-between py-2">
                        <span>Total Credit:</span>
                        <span class="text-success fw-bold">${currencySymbol} ${creditTotal.toFixed(2)}</span>
                    </div>
                    <hr>
                    <div class="d-flex justify-content-between py-2">
                        <span class="fw-bold">Difference:</span>
                        <span class="fw-bold ${difference === 0 ? 'text-success' : 'text-danger'}">
                            ${currencySymbol} ${Math.abs(difference).toFixed(2)}
                        </span>
                    </div>
                `;
            }
        }

        // 🎯 **Phase 4.2: Form Validation System**
        function validateForm() {
            // Add small delay to ensure DOM is updated
            setTimeout(() => {
                const isBasicInfoValid = validateBasicInfo();
                const isBalanceValid = validateBalance();
                const isLinesValid = validateJournalLines();
                
                const isValid = isBasicInfoValid && isBalanceValid && isLinesValid;
                
                // Debug logging
                console.log('🔍 Validation Debug:', {
                    isBasicInfoValid,
                    isBalanceValid,
                    isLinesValid,
                    isValid
                });
                
                // Update save button state
                const saveButton = document.getElementById('save-btn');
                if (saveButton) {
                    if (isValid) {
                        saveButton.disabled = false;
                        saveButton.classList.remove('btn-outline-secondary');
                        saveButton.classList.add('btn-success');
                        console.log('✅ Save button enabled!');
                    } else {
                        saveButton.disabled = true;
                        saveButton.classList.remove('btn-success');
                        saveButton.classList.add('btn-outline-secondary');
                        console.log('❌ Save button disabled - validation failed');
                    }
                }
                
                // Update balance check
                updateBalanceCheck(isBalanceValid);
            }, 100);
            
            return true; // Return true immediately to avoid blocking
        }
        
        function validateBasicInfo() {
            const entryDate = document.getElementById('entry_date').value;
            const storeId = document.getElementById('store_id').value;
            
            return entryDate && storeId;
        }
        
        function validateBalance() {
            const debitInputs = document.querySelectorAll('.debit-input');
            const creditInputs = document.querySelectorAll('.credit-input');
            
            let totalDebit = 0;
            let totalCredit = 0;
            
            debitInputs.forEach(input => {
                totalDebit += parseFloat(input.value) || 0;
            });
            
            creditInputs.forEach(input => {
                totalCredit += parseFloat(input.value) || 0;
            });
            
            console.log(`🔍 Balance Check: Debit=${totalDebit}, Credit=${totalCredit}, Difference=${Math.abs(totalDebit - totalCredit)}`);
            
            return Math.abs(totalDebit - totalCredit) < 0.01 && totalDebit > 0;
        }
        
        // 💰 Cash 계정 확인 함수 (4단계: Cash Location 필수 검증용)
        function isCashAccount(accountId) {
            // Cash 계정 ID로 직접 비교
            const CASH_ACCOUNT_ID = 'd4a7a16e-45a1-47fe-992b-ff807c8673f0';
            return accountId === CASH_ACCOUNT_ID;
        }
        
        function validateJournalLines() {
            const journalLines = document.querySelectorAll('.journal-line');
            
            console.log('🔍 Journal Lines Count:', journalLines.length);
            
            if (journalLines.length < 2) {
                console.log('❌ Not enough journal lines');
                return false;
            }
            
            for (let i = 0; i < journalLines.length; i++) {
                const line = journalLines[i];
                const hiddenInput = line.querySelector('.account-id-hidden');
                const debitInput = line.querySelector('.debit-input');
                const creditInput = line.querySelector('.credit-input');
                
                if (!hiddenInput || !debitInput || !creditInput) {
                    console.log(`❌ Line ${i+1}: Missing form elements`);
                    return false;
                }
                
                // Get fresh values from DOM
                const accountId = hiddenInput.value;
                let debitAmount = parseFloat(debitInput.value) || 0;
                let creditAmount = parseFloat(creditInput.value) || 0;
                
                // Alternative way to get values if first method fails
                if (debitAmount === 0 && debitInput.value) {
                    debitAmount = parseFloat(debitInput.value.replace(/[^0-9.-]/g, '')) || 0;
                }
                if (creditAmount === 0 && creditInput.value) {
                    creditAmount = parseFloat(creditInput.value.replace(/[^0-9.-]/g, '')) || 0;
                }
                
                console.log(`Line ${i+1}: Account=${accountId}, Debit=${debitAmount}, Credit=${creditAmount}`);
                console.log(`Line ${i+1} Debug: DebitInput.value='${debitInput.value}', CreditInput.value='${creditInput.value}'`);
                
                // Each line must have account selected and either debit or credit amount
                if (!accountId || accountId === '' || (debitAmount === 0 && creditAmount === 0)) {
                    console.log(`❌ Line ${i+1} is invalid - Account: '${accountId}', Debit: ${debitAmount}, Credit: ${creditAmount}`);
                    return false;
                }
                
                // 💰 4단계: Cash 계정 Location 필수 검증
                if (isCashAccount(accountId)) {
                    const locationSelect = line.querySelector('.cash-location-select');
                    const locationValue = locationSelect ? locationSelect.value : '';
                    
                    if (!locationValue || locationValue === '') {
                        console.log(`❌ Line ${i+1}: Cash account requires location selection`);
                        showError('현금 계정은 위치 정보가 필요합니다. (Line ${i+1})');
                        return false;
                    }
                    
                    console.log(`✅ Line ${i+1}: Cash account has location selected: ${locationValue}`);
                }
            }
            
            console.log('✅ All journal lines are valid');
            return true;
        }
        
        function updateBalanceCheck(isBalanceValid) {
            const balanceCheck = document.getElementById('balance-check');
            const balanceStatus = document.getElementById('balance-status');
            
            if (isBalanceValid) {
                balanceCheck.checked = true;
                balanceCheck.classList.add('text-success');
                balanceStatus.textContent = 'Balance verified ✓';
                balanceStatus.className = 'text-success';
            } else {
                balanceCheck.checked = false;
                balanceCheck.classList.remove('text-success');
                balanceStatus.textContent = 'Balance not verified';
                balanceStatus.className = 'text-danger';
            }
        }
        
        // 🎯 **Phase 4.2: Save Journal Entry Function**
        function saveJournalEntry() {
            if (!validateForm()) {
                showError('Please complete all required fields and ensure balance is correct.');
                return;
            }
            
            const formData = collectFormData();
            
            // Show loading state
            const saveButton = document.getElementById('save-btn');
            saveButton.disabled = true;
            saveButton.innerHTML = '<i class="bi bi-hourglass-split me-1"></i>Saving...';
            
            // Send data to server
            fetch(`save_journal_entry.php?user_id=${userId}&company_id=${companyId}`, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    showSuccess('Journal entry saved successfully!');
                    // Optional: Reset form or redirect
                    setTimeout(() => {
                        resetForm();
                    }, 2000);
                } else {
                    showError(data.error || 'Failed to save journal entry');
                }
            })
            .catch(error => {
                console.error('Error saving journal entry:', error);
                showError('Network error occurred. Please try again.');
            })
            .finally(() => {
                // Reset button state
                saveButton.disabled = false;
                saveButton.innerHTML = '<i class="bi bi-check-circle me-1"></i>Save Journal Entry';
            });
        }
        
        function collectFormData() {
            const formData = {
                company_id: '<?= $company_id ?>',
                store_id: document.getElementById('store_id').value,
                entry_date: document.getElementById('entry_date').value,
                description: document.getElementById('description').value,
                lines: []
            };
            
            // 💰 4단계: Cash Location 정보 수집
            let cashLocationId = null;
            
            // Collect journal lines
            const journalLines = document.querySelectorAll('.journal-line');
            journalLines.forEach(line => {
                // 새로운 searchable dropdown에서 계정 ID 가져오기
                const hiddenInput = line.querySelector('.account-id-hidden');
                const accountId = hiddenInput ? hiddenInput.value : '';
                const description = line.querySelector('input[name="line_description[]"]').value;
                const debitAmount = parseFloat(line.querySelector('.debit-input').value) || 0;
                const creditAmount = parseFloat(line.querySelector('.credit-input').value) || 0;
                
                if (accountId && (debitAmount > 0 || creditAmount > 0)) {
                    const lineData = {
                        account_id: accountId,
                        description: description,
                        debit_amount: debitAmount,
                        credit_amount: creditAmount
                    };
                    
                    // Cash 계정인 경우 Location 정보 추가
                    if (isCashAccount(accountId)) {
                        const locationSelect = line.querySelector('.cash-location-select');
                        const locationValue = locationSelect ? locationSelect.value : '';
                        
                        if (locationValue) {
                            cashLocationId = locationValue;
                            lineData.cash_location_id = locationValue;
                            console.log('💰 Cash location collected:', locationValue);
                        }
                    }
                    
                    formData.lines.push(lineData);
                }
            });
            
            // Cash Location이 있으면 최상위 레벨에도 추가
            if (cashLocationId) {
                formData.cash_location_id = cashLocationId;
                console.log('💰 Cash location added to form data:', cashLocationId);
            }
            
            return formData;
        }
        
        function resetForm() {
            // Reset without confirmation
                // Reset basic fields
                document.getElementById('entry_date').value = '<?= date('Y-m-d') ?>';
                document.getElementById('store_id').value = '';
                document.getElementById('description').value = '';
                
                // Reset journal lines to default 2 lines
                const tbody = document.getElementById('journal-lines-tbody');
                tbody.innerHTML = `
                    <tr class="journal-line" data-line-index="1">
                        <td class="text-center line-number">1</td>
                        <td>
                            <select class="form-select form-select-sm account-select" name="account_id[]" required>
                                <?= $accountOptions ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="line_description[]" placeholder="Enter description">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm debit-input" name="debit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm credit-input" name="credit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" onclick="removeJournalLine(1)" disabled>
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                    <tr class="journal-line" data-line-index="2">
                        <td class="text-center line-number">2</td>
                        <td>
                            <select class="form-select form-select-sm account-select" name="account_id[]" required>
                                <?= $accountOptions ?>
                            </select>
                        </td>
                        <td>
                            <input type="text" class="form-control form-control-sm" name="line_description[]" placeholder="Enter description">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm debit-input" name="debit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                        </td>
                        <td>
                            <input type="number" class="form-control form-control-sm credit-input" name="credit_amount[]" min="0" step="0.01" placeholder="0" onchange="updateBalance()" oninput="handleDebitCreditInput(this)" style="-webkit-appearance: textfield; -moz-appearance: textfield; appearance: textfield;">
                        </td>
                        <td class="text-center">
                            <button type="button" class="btn btn-sm btn-outline-danger remove-line-btn" onclick="removeJournalLine(2)" disabled>
                                <i class="bi bi-trash"></i>
                            </button>
                        </td>
                    </tr>
                `;
                
                lineCounter = 2;
                updateBalance();
                updateRemoveButtons();
                validateForm();
                
                // Hide messages
                hideAllMessages();
                
                // Focus on entry date
                document.getElementById('entry_date').focus();
        }
        
        function showSuccess(message) {
            hideAllMessages();
            const successDiv = document.getElementById('success-message');
            const successText = document.getElementById('success-text');
            successText.textContent = message;
            successDiv.style.display = 'block';
            
            // Auto-hide after 5 seconds
            setTimeout(() => {
                successDiv.style.display = 'none';
            }, 5000);
        }
        
        function showError(message) {
            hideAllMessages();
            const errorDiv = document.getElementById('error-message');
            const errorText = document.getElementById('error-text');
            errorText.textContent = message;
            errorDiv.style.display = 'block';
            
            // Auto-hide after 8 seconds
            setTimeout(() => {
                errorDiv.style.display = 'none';
            }, 8000);
        }
        
        function hideAllMessages() {
            document.getElementById('success-message').style.display = 'none';
            document.getElementById('error-message').style.display = 'none';
        }

        // 🚀 Phase 4.3.1: Auto-save functionality
        let autoSaveTimer;
        let lastAutoSave = 0;
        let isDrafted = false;
        
        function scheduleAutoSave() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(() => {
                if (validateBasicInfo() && validateJournalLines() && !isFormEmpty()) {
                    autoSaveJournalEntry();
                }
            }, 30000); // 30초 후 자동 저장
        }
        
        function autoSaveJournalEntry() {
            const formData = collectFormData();
            formData.is_draft = true; // 임시 저장 표시
            formData.auto_save = true; // 자동 저장 플래그
            
            fetch(`save_journal_entry.php?user_id=${userId}&company_id=${companyId}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(formData)
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    lastAutoSave = Date.now();
                    isDrafted = true;
                    showAutoSaveStatus('Auto-saved at ' + new Date().toLocaleTimeString());
                    // reference number 관련 코드 제거됨
                }
            })
            .catch(error => {
                console.log('Auto-save failed:', error);
                showAutoSaveStatus('Auto-save failed', true);
            });
        }
        
        function isFormEmpty() {
            const entryDate = document.getElementById('entry_date').value;
            const store = document.getElementById('store_id').value;
            const description = document.getElementById('description').value;
            
            if (!entryDate || !store || !description.trim()) {
                return true;
            }
            
            // Check if any journal line has data
            const journalLines = document.querySelectorAll('.journal-line');
            let hasData = false;
            
            journalLines.forEach(line => {
                // 새로운 searchable dropdown에서 계정 ID 가져오기
                const hiddenInput = line.querySelector('.account-id-hidden');
                const accountId = hiddenInput ? hiddenInput.value : '';
                const debitAmount = parseFloat(line.querySelector('.debit-input').value) || 0;
                const creditAmount = parseFloat(line.querySelector('.credit-input').value) || 0;
                
                if (accountId && (debitAmount > 0 || creditAmount > 0)) {
                    hasData = true;
                }
            });
            
            return !hasData;
        }
        
        function showAutoSaveStatus(message, isError = false) {
            const statusDiv = document.getElementById('auto-save-status');
            if (statusDiv) {
                statusDiv.textContent = message;
                statusDiv.className = 'auto-save-status ' + (isError ? 'error' : 'success');
                statusDiv.style.display = 'block';
                
                // Auto-hide after 3 seconds
                setTimeout(() => {
                    statusDiv.style.display = 'none';
                }, 3000);
            }
        }
        
        function clearAutoSave() {
            clearTimeout(autoSaveTimer);
            isDrafted = false;
            lastAutoSave = 0;
            const statusDiv = document.getElementById('auto-save-status');
            if (statusDiv) {
                statusDiv.style.display = 'none';
            }
        }
        
        // Auto-save 트리거 이벤트 추가
        function addAutoSaveListeners() {
            // 기본 필드 변경 시 자동 저장 스케줄링
            const fields = ['entry_date', 'store_id', 'description'];
            fields.forEach(fieldId => {
                const field = document.getElementById(fieldId);
                if (field) {
                    field.addEventListener('change', scheduleAutoSave);
                    field.addEventListener('input', scheduleAutoSave);
                }
            });
        }
        
        // 저널 라인에 자동 저장 리스너 추가
        function addJournalLineAutoSaveListeners() {
            const journalLines = document.querySelectorAll('.journal-line');
            journalLines.forEach(line => {
                // Account select
                const accountSelect = line.querySelector('.account-select');
                if (accountSelect) {
                    accountSelect.addEventListener('change', scheduleAutoSave);
                }
                
                // Description input
                const descInput = line.querySelector('input[name="line_description[]"]');
                if (descInput) {
                    descInput.addEventListener('input', scheduleAutoSave);
                    descInput.addEventListener('change', scheduleAutoSave);
                }
                
                // Debit input
                const debitInput = line.querySelector('.debit-input');
                if (debitInput) {
                    debitInput.addEventListener('input', scheduleAutoSave);
                    debitInput.addEventListener('change', scheduleAutoSave);
                }
                
                // Credit input
                const creditInput = line.querySelector('.credit-input');
                if (creditInput) {
                    creditInput.addEventListener('input', scheduleAutoSave);
                    creditInput.addEventListener('change', scheduleAutoSave);
                }
            });
        }

        // 🎯 **Phase 5.3: Smart Autocomplete System - IMPLEMENTED**
        
        // 🎯 **Phase 5.4: Bulk Edit and Copy Features - NEW IMPLEMENTATION**
        
        // Smart Autocomplete for account-based suggestions
        function setupSmartAutocomplete() {
            // Account change → Description suggestions
            document.addEventListener('change', function(e) {
                if (e.target.matches('.account-select')) {
                    const accountId = e.target.value;
                    if (accountId) {
                        loadDescriptionSuggestions(accountId, e.target.closest('.journal-line'));
                        loadAmountPatterns(accountId, e.target.closest('.journal-line'));
                    }
                }
            });
            
            // Description input → Real-time autocomplete
            document.addEventListener('input', function(e) {
                if (e.target.matches('.line-description')) {
                    showDescriptionAutocomplete(e.target);
                }
            });
            
            // Amount input → Pattern suggestions
            document.addEventListener('focus', function(e) {
                if (e.target.matches('.debit-input, .credit-input')) {
                    const line = e.target.closest('.journal-line');
                    const accountId = line.querySelector('.account-select').value;
                    if (accountId) {
                        showAmountSuggestions(e.target, accountId);
                    }
                }
            });
        }
        
        // Load description suggestions for selected account
        function loadDescriptionSuggestions(accountId, lineElement) {
            const params = new URLSearchParams({
                action: 'description_suggestions',
                user_id: '<?= $user_id ?>',
                company_id: '<?= $company_id ?>',
                account_id: accountId
            });
            
            fetch(`autocomplete.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.suggestions.length > 0) {
                        attachDescriptionDropdown(lineElement, data.suggestions);
                        
                        // Auto-fill if only one historical suggestion
                        if (data.suggestions.length === 1) {
                            const descInput = lineElement.querySelector('.line-description');
                            if (!descInput.value) {
                                descInput.value = data.suggestions[0];
                                showAutoCompleteNotification('Description auto-filled based on account history');
                            }
                        }
                    }
                })
                .catch(error => console.log('Description suggestions failed:', error));
        }
        
        // Load amount patterns for selected account
        function loadAmountPatterns(accountId, lineElement) {
            const params = new URLSearchParams({
                action: 'amount_patterns',
                user_id: '<?= $user_id ?>',
                company_id: '<?= $company_id ?>',
                account_id: accountId
            });
            
            fetch(`autocomplete.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.common_amounts.length > 0) {
                        lineElement.dataset.commonAmounts = JSON.stringify(data.common_amounts);
                        lineElement.dataset.avgAmount = data.patterns.average || 0;
                    }
                })
                .catch(error => console.log('Amount patterns failed:', error));
        }
        
        // Attach description dropdown to line
        function attachDescriptionDropdown(lineElement, suggestions) {
            const descInput = lineElement.querySelector('.line-description');
            
            // Remove existing dropdown
            const existingDropdown = lineElement.querySelector('.autocomplete-dropdown');
            if (existingDropdown) {
                existingDropdown.remove();
            }
            
            // Create new dropdown
            const dropdown = document.createElement('div');
            dropdown.className = 'autocomplete-dropdown';
            dropdown.style.cssText = `
                position: absolute;
                top: 100%;
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #ddd;
                border-radius: 4px;
                box-shadow: 0 4px 8px rgba(0,0,0,0.1);
                max-height: 200px;
                overflow-y: auto;
                z-index: 1000;
                display: none;
            `;
            
            suggestions.forEach((suggestion, index) => {
                const item = document.createElement('div');
                item.className = 'autocomplete-item';
                item.style.cssText = `
                    padding: 8px 12px;
                    cursor: pointer;
                    border-bottom: 1px solid #eee;
                    transition: background-color 0.2s;
                `;
                item.textContent = suggestion;
                
                item.addEventListener('mouseenter', () => {
                    item.style.backgroundColor = '#f8f9fa';
                });
                
                item.addEventListener('mouseleave', () => {
                    item.style.backgroundColor = 'white';
                });
                
                item.addEventListener('click', () => {
                    descInput.value = suggestion;
                    dropdown.style.display = 'none';
                    showAutoCompleteNotification('Description applied from history');
                    validateForm();
                });
                
                dropdown.appendChild(item);
            });
            
            // Position dropdown
            const inputContainer = descInput.parentElement;
            inputContainer.style.position = 'relative';
            inputContainer.appendChild(dropdown);
            
            // Show dropdown on focus
            descInput.addEventListener('focus', () => {
                if (suggestions.length > 0) {
                    dropdown.style.display = 'block';
                }
            });
            
            // Hide dropdown on blur (with delay for clicks)
            descInput.addEventListener('blur', () => {
                setTimeout(() => {
                    dropdown.style.display = 'none';
                }, 200);
            });
        }
        
        // Show description autocomplete while typing
        function showDescriptionAutocomplete(input) {
            const searchTerm = input.value.trim();
            if (searchTerm.length < 2) return;
            
            const params = new URLSearchParams({
                action: 'recent_descriptions',
                user_id: '<?= $user_id ?>',
                company_id: '<?= $company_id ?>',
                search: searchTerm
            });
            
            fetch(`autocomplete.php?${params}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.descriptions.length > 0) {
                        showInstantDropdown(input, data.descriptions);
                    }
                })
                .catch(error => console.log('Real-time autocomplete failed:', error));
        }
        
        // Show instant dropdown for search results
        function showInstantDropdown(input, items) {
            // Remove existing instant dropdown
            const existing = document.querySelector('.instant-autocomplete');
            if (existing) existing.remove();
            
            const dropdown = document.createElement('div');
            dropdown.className = 'instant-autocomplete';
            dropdown.style.cssText = `
                position: absolute;
                top: calc(100% + 2px);
                left: 0;
                right: 0;
                background: white;
                border: 1px solid #007bff;
                border-radius: 4px;
                box-shadow: 0 6px 12px rgba(0,123,255,0.15);
                max-height: 150px;
                overflow-y: auto;
                z-index: 1001;
            `;
            
            items.slice(0, 5).forEach(item => {
                const div = document.createElement('div');
                div.style.cssText = `
                    padding: 6px 10px;
                    cursor: pointer;
                    font-size: 0.9em;
                    border-bottom: 1px solid #f0f0f0;
                `;
                div.textContent = item;
                
                div.addEventListener('mouseenter', () => {
                    div.style.backgroundColor = '#e3f2fd';
                });
                
                div.addEventListener('mouseleave', () => {
                    div.style.backgroundColor = 'white';
                });
                
                div.addEventListener('click', () => {
                    input.value = item;
                    dropdown.remove();
                    showAutoCompleteNotification('Description selected from history');
                });
                
                dropdown.appendChild(div);
            });
            
            // Position and show
            const container = input.parentElement;
            container.style.position = 'relative';
            container.appendChild(dropdown);
            
            // Auto-remove on blur
            input.addEventListener('blur', () => {
                setTimeout(() => {
                    if (dropdown.parentElement) {
                        dropdown.remove();
                    }
                }, 200);
            }, { once: true });
        }
        
        // Show amount suggestions based on patterns
        function showAmountSuggestions(input, accountId) {
            const line = input.closest('.journal-line');
            const commonAmounts = line.dataset.commonAmounts;
            const avgAmount = line.dataset.avgAmount;
            
            if (!commonAmounts) return;
            
            try {
                const amounts = JSON.parse(commonAmounts);
                if (amounts.length === 0) return;
                
                // Remove existing suggestions
                const existing = document.querySelector('.amount-suggestions');
                if (existing) existing.remove();
                
                const suggestions = document.createElement('div');
                suggestions.className = 'amount-suggestions';
                suggestions.style.cssText = `
                    position: absolute;
                    top: calc(100% + 2px);
                    left: 0;
                    right: 0;
                    background: #f8f9fa;
                    border: 1px solid #28a745;
                    border-radius: 4px;
                    padding: 8px;
                    font-size: 0.8em;
                    z-index: 999;
                    box-shadow: 0 4px 8px rgba(40,167,69,0.15);
                `;
                
                let html = '<div style="color: #28a745; font-weight: 500; margin-bottom: 4px;">💡 Common amounts for this account:</div>';
                
                amounts.slice(0, 3).forEach(amount => {
                    const formatted = new Intl.NumberFormat('vi-VN').format(amount);
                    html += `<button type="button" class="btn btn-sm btn-outline-success me-1 mb-1" 
                            onclick="applyAmountSuggestion('${input.className}', ${amount})" 
                            style="font-size: 0.75em; padding: 2px 6px;">
                            ₫${formatted}
                        </button>`;
                });
                
                if (avgAmount > 0) {
                    const avgFormatted = new Intl.NumberFormat('vi-VN').format(avgAmount);
                    html += `<div style="margin-top: 4px; color: #6c757d; font-size: 0.75em;">📊 Average: ₫${avgFormatted}</div>`;
                }
                
                suggestions.innerHTML = html;
                
                // Position and show
                const container = input.parentElement;
                container.style.position = 'relative';
                container.appendChild(suggestions);
                
                // Auto-remove on blur
                input.addEventListener('blur', () => {
                    setTimeout(() => {
                        if (suggestions.parentElement) {
                            suggestions.remove();
                        }
                    }, 300);
                }, { once: true });
                
            } catch (e) {
                console.log('Error parsing amount suggestions:', e);
            }
        }
        
        // Apply amount suggestion
        function applyAmountSuggestion(inputClass, amount) {
            const inputs = document.querySelectorAll('.' + inputClass.split(' ').join('.'));
            const activeInput = document.activeElement;
            
            if (activeInput && inputs.length > 0) {
                activeInput.value = amount;
                updateBalance();
                validateForm();
                showAutoCompleteNotification(`Amount ₫${new Intl.NumberFormat('vi-VN').format(amount)} applied from history`);
                
                // Remove suggestions
                const suggestions = document.querySelector('.amount-suggestions');
                if (suggestions) suggestions.remove();
            }
        }
        
        // Show autocomplete notification
        function showAutoCompleteNotification(message) {
            // Remove existing notification
            const existing = document.getElementById('autocomplete-notification');
            if (existing) existing.remove();
            
            const notification = document.createElement('div');
            notification.id = 'autocomplete-notification';
            notification.style.cssText = `
                position: fixed;
                top: 20px;
                right: 20px;
                background: linear-gradient(135deg, #28a745, #20c997);
                color: white;
                padding: 8px 16px;
                border-radius: 6px;
                font-size: 0.85em;
                box-shadow: 0 4px 12px rgba(40, 167, 69, 0.3);
                z-index: 9999;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;
            notification.innerHTML = `<i class="bi bi-magic me-1"></i>${message}`;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto-remove
            setTimeout(() => {
                notification.style.transform = 'translateX(100%)';
                setTimeout(() => {
                    if (notification.parentElement) {
                        notification.remove();
                    }
                }, 300);
            }, 3000);
        }
        
        // Smart Template Auto-application
        function setupSmartTemplateDetection() {
            // Monitor account combinations for template suggestions
            document.addEventListener('change', function(e) {
                if (e.target.matches('.account-select')) {
                    detectTemplatePattern();
                }
            });
        }
        
        // Detect if current account selection matches a known template pattern
        function detectTemplatePattern() {
            const lines = document.querySelectorAll('.journal-line');
            const accounts = [];
            
            lines.forEach(line => {
                const accountSelect = line.querySelector('.account-select');
                if (accountSelect.value) {
                    const accountText = accountSelect.options[accountSelect.selectedIndex].text;
                    accounts.push(accountText);
                }
            });
            
            // Check for common patterns
            if (accounts.length >= 2) {
                const pattern = accounts.sort().join(' + ');
                
                // Check against known quick action patterns
                const patterns = {
                    'Cash + Sales Revenue': 'cash',
                    'Bank Account + Sales Revenue': 'bank',
                    'Cash + Office Expenses': 'expense',
                    'Accounts Receivable + Service Revenue': 'revenue'
                };
                
                const matchedTemplate = patterns[pattern];
                if (matchedTemplate) {
                    showTemplateDetectionNotification(matchedTemplate, pattern);
                }
            }
        }
        
        // Show template detection notification
        function showTemplateDetectionNotification(templateType, pattern) {
            const notification = document.createElement('div');
            notification.style.cssText = `
                position: fixed;
                top: 80px;
                right: 20px;
                background: linear-gradient(135deg, #007bff, #0056b3);
                color: white;
                padding: 12px 16px;
                border-radius: 8px;
                font-size: 0.9em;
                box-shadow: 0 6px 16px rgba(0, 123, 255, 0.3);
                z-index: 9998;
                max-width: 300px;
                transform: translateX(100%);
                transition: transform 0.3s ease;
            `;
            
            notification.innerHTML = `
                <div style="margin-bottom: 8px;">
                    <i class="bi bi-lightbulb me-1"></i>
                    <strong>Smart Template Detected!</strong>
                </div>
                <div style="font-size: 0.8em; margin-bottom: 8px;">Pattern: ${pattern}</div>
                <button onclick="applyDetectedTemplate('${templateType}')" 
                        class="btn btn-sm btn-light me-2" 
                        style="font-size: 0.75em;">Apply ${templateType.charAt(0).toUpperCase() + templateType.slice(1)} Template</button>
                <button onclick="this.parentElement.remove()" 
                        class="btn btn-sm btn-outline-light" 
                        style="font-size: 0.75em;">Dismiss</button>
            `;
            
            document.body.appendChild(notification);
            
            // Animate in
            setTimeout(() => {
                notification.style.transform = 'translateX(0)';
            }, 100);
            
            // Auto-dismiss after 8 seconds
            setTimeout(() => {
                if (notification.parentElement) {
                    notification.style.transform = 'translateX(100%)';
                    setTimeout(() => {
                        if (notification.parentElement) {
                            notification.remove();
                        }
                    }, 300);
                }
            }, 8000);
        }
        
        // Apply detected template
        function applyDetectedTemplate(templateType) {
            addQuickLine(templateType);
            
            // Remove notification
            const notifications = document.querySelectorAll('[style*="Smart Template Detected"]');
            notifications.forEach(n => n.remove());
        }
        
        // Initialize Smart Autocomplete System
        function initializeSmartAutocomplete() {
            setupSmartAutocomplete();
            setupSmartTemplateDetection();
            console.log('✅ Phase 5.3: Smart Autocomplete System initialized');
        }
        
        // 🎯 **Phase 5.4: Bulk Edit and Copy Features System**
        
        // Global variables for bulk edit mode
        let bulkEditMode = false;
        let clipboardLines = [];
        
        // Show Bulk Edit Modal
        function showBulkEditModal() {
            const modal = new bootstrap.Modal(document.getElementById('bulkEditModal'));
            updateSelectedLinesCount();
            modal.show();
        }
        
        // Enable Bulk Edit Mode
        function enableBulkEditMode() {
            bulkEditMode = true;
            addLineSelectionCheckboxes();
            showBulkEditToolbar();
            
            // Close modal
            const modal = bootstrap.Modal.getInstance(document.getElementById('bulkEditModal'));
            modal.hide();
            
            showSuccess('Bulk edit mode enabled! Select lines to edit multiple entries at once.');
        }
        
        // Add selection checkboxes to all journal lines
        function addLineSelectionCheckboxes() {
            const journalRows = document.querySelectorAll('.journal-line');
            
            journalRows.forEach((row, index) => {
                // Skip if checkbox already exists
                if (row.querySelector('.line-selector')) return;
                
                const lineIndex = row.getAttribute('data-line-index');
                const checkbox = document.createElement('td');
                checkbox.innerHTML = `
                    <input type="checkbox" class="form-check-input line-selector" 
                           data-line-index="${lineIndex}" 
                           onchange="updateSelectedLinesCount()">
                `;
                
                // Insert checkbox as first column
                row.insertBefore(checkbox, row.firstChild);
            });
            
            // Update table header
            const thead = document.querySelector('#journal-table thead tr');
            if (!thead.querySelector('.select-header')) {
                const headerCheckbox = document.createElement('th');
                headerCheckbox.className = 'select-header';
                headerCheckbox.innerHTML = `
                    <input type="checkbox" class="form-check-input" 
                           onchange="toggleAllLines(this.checked)">
                `;
                thead.insertBefore(headerCheckbox, thead.firstChild);
            }
        }
        
        // Show bulk edit toolbar
        function showBulkEditToolbar() {
            const toolbar = document.createElement('div');
            toolbar.id = 'bulk-edit-toolbar';
            toolbar.className = 'alert alert-info d-flex justify-content-between align-items-center mt-3';
            toolbar.innerHTML = `
                <div>
                    <i class="bi bi-check2-all me-2"></i>
                    <strong>Bulk Edit Mode Active</strong>
                    <span class="ms-2 badge bg-primary" id="toolbar-selected-count">0 selected</span>
                </div>
                <div>
                    <button class="btn btn-success btn-sm me-2" onclick="showBulkEditModal()">
                        <i class="bi bi-tools me-1"></i>Bulk Actions
                    </button>
                    <button class="btn btn-outline-secondary btn-sm" onclick="disableBulkEditMode()">
                        <i class="bi bi-x-circle me-1"></i>Exit Bulk Mode
                    </button>
                </div>
            `;
            
            const journalTable = document.getElementById('journal-table');
            journalTable.parentNode.insertBefore(toolbar, journalTable.nextSibling);
        }
        
        // Disable bulk edit mode
        function disableBulkEditMode() {
            bulkEditMode = false;
            
            // Remove checkboxes
            document.querySelectorAll('.line-selector').forEach(checkbox => {
                checkbox.closest('td').remove();
            });
            
            // Remove header checkbox
            const headerCheckbox = document.querySelector('.select-header');
            if (headerCheckbox) headerCheckbox.remove();
            
            // Remove toolbar
            const toolbar = document.getElementById('bulk-edit-toolbar');
            if (toolbar) toolbar.remove();
            
            showSuccess('Bulk edit mode disabled.');
        }
        
        // Toggle all lines selection
        function toggleAllLines(checked) {
            document.querySelectorAll('.line-selector').forEach(checkbox => {
                checkbox.checked = checked;
            });
            updateSelectedLinesCount();
        }
        
        // Select all lines
        function selectAllLines() {
            toggleAllLines(true);
        }
        
        // Clear line selection
        function clearLineSelection() {
            toggleAllLines(false);
        }
        
        // Update selected lines count
        function updateSelectedLinesCount() {
            const selectedCheckboxes = document.querySelectorAll('.line-selector:checked');
            const count = selectedCheckboxes.length;
            
            // Update modal count
            const modalCount = document.getElementById('selected-lines-count');
            if (modalCount) {
                modalCount.textContent = `${count} lines selected`;
            }
            
            // Update toolbar count
            const toolbarCount = document.getElementById('toolbar-selected-count');
            if (toolbarCount) {
                toolbarCount.textContent = `${count} selected`;
            }
        }
        
        // Apply bulk account setting
        function applyBulkAccount() {
            const selectedAccount = document.getElementById('bulk-account-select').value;
            if (!selectedAccount) {
                showError('Please select an account first.');
                return;
            }
            
            const selectedCheckboxes = document.querySelectorAll('.line-selector:checked');
            if (selectedCheckboxes.length === 0) {
                showError('Please select at least one line.');
                return;
            }
            
            let successCount = 0;
            selectedCheckboxes.forEach(checkbox => {
                const lineIndex = checkbox.getAttribute('data-line-index');
                const row = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
                const accountSelect = row.querySelector('.account-select');
                
                if (accountSelect) {
                    accountSelect.value = selectedAccount;
                    successCount++;
                }
            });
            
            showSuccess(`Account applied to ${successCount} lines successfully!`);
            updateBalance();
        }
        
        // Apply bulk description setting
        function applyBulkDescription() {
            const description = document.getElementById('bulk-description-input').value.trim();
            if (!description) {
                showError('Please enter a description first.');
                return;
            }
            
            const selectedCheckboxes = document.querySelectorAll('.line-selector:checked');
            if (selectedCheckboxes.length === 0) {
                showError('Please select at least one line.');
                return;
            }
            
            let successCount = 0;
            selectedCheckboxes.forEach(checkbox => {
                const lineIndex = checkbox.getAttribute('data-line-index');
                const row = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
                const descriptionInput = row.querySelector('.line-description');
                
                if (descriptionInput) {
                    descriptionInput.value = description;
                    successCount++;
                }
            });
            
            showSuccess(`Description applied to ${successCount} lines successfully!`);
            
            // Clear input
            document.getElementById('bulk-description-input').value = '';
        }
        
        // Copy selected lines to clipboard
        function copySelectedLines() {
            const selectedCheckboxes = document.querySelectorAll('.line-selector:checked');
            if (selectedCheckboxes.length === 0) {
                showError('Please select at least one line to copy.');
                return;
            }
            
            clipboardLines = [];
            selectedCheckboxes.forEach(checkbox => {
                const lineIndex = checkbox.getAttribute('data-line-index');
                const row = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
                
                const lineData = {
                    account_id: row.querySelector('.account-select')?.value || '',
                    line_description: row.querySelector('.line-description')?.value || '',
                    debit_amount: row.querySelector('.debit-input')?.value || '',
                    credit_amount: row.querySelector('.credit-input')?.value || ''
                };
                
                clipboardLines.push(lineData);
            });
            
            // Update UI
            document.getElementById('paste-lines-btn').disabled = false;
            document.getElementById('clipboard-status').textContent = `${clipboardLines.length} lines copied to clipboard`;
            
            showSuccess(`${clipboardLines.length} lines copied to clipboard!`);
        }
        
        // Paste lines from clipboard
        function pasteLines() {
            if (clipboardLines.length === 0) {
                showError('No lines in clipboard to paste.');
                return;
            }
            
            let pastedCount = 0;
            clipboardLines.forEach(lineData => {
                // Add new line
                addJournalLine();
                
                // Get the newly added line (last one)
                const allLines = document.querySelectorAll('.journal-line');
                const newLine = allLines[allLines.length - 1];
                
                // Apply data to new line
                if (lineData.account_id) {
                    newLine.querySelector('.account-select').value = lineData.account_id;
                }
                if (lineData.line_description) {
                    newLine.querySelector('.line-description').value = lineData.line_description;
                }
                if (lineData.debit_amount) {
                    newLine.querySelector('.debit-input').value = lineData.debit_amount;
                }
                if (lineData.credit_amount) {
                    newLine.querySelector('.credit-input').value = lineData.credit_amount;
                }
                
                pastedCount++;
            });
            
            // If in bulk edit mode, add checkboxes to new lines
            if (bulkEditMode) {
                addLineSelectionCheckboxes();
            }
            
            showSuccess(`${pastedCount} lines pasted successfully!`);
            updateBalance();
            addJournalLineAutoSaveListeners();
        }
        
        // Delete selected lines
        function deleteSelectedLines() {
            const selectedCheckboxes = document.querySelectorAll('.line-selector:checked');
            if (selectedCheckboxes.length === 0) {
                showError('Please select at least one line to delete.');
                return;
            }
            
            if (!confirm(`Are you sure you want to delete ${selectedCheckboxes.length} selected lines?`)) {
                return;
            }
            
            let deletedCount = 0;
            // Convert to array to avoid live NodeList issues
            Array.from(selectedCheckboxes).forEach(checkbox => {
                const lineIndex = checkbox.getAttribute('data-line-index');
                const row = document.querySelector(`tr[data-line-index="${lineIndex}"]`);
                
                if (row) {
                    row.remove();
                    deletedCount++;
                }
            });
            
            // Renumber remaining lines
            renumberJournalLines();
            
            showSuccess(`${deletedCount} lines deleted successfully!`);
            updateBalance();
            updateSelectedLinesCount();
        }
        
        // Renumber journal lines after deletion
        function renumberJournalLines() {
            const journalRows = document.querySelectorAll('.journal-line');
            
            journalRows.forEach((row, index) => {
                const newLineIndex = index + 1;
                row.setAttribute('data-line-index', newLineIndex);
                
                // Update line number display
                const lineNumber = row.querySelector('.line-number');
                if (lineNumber) {
                    lineNumber.textContent = newLineIndex;
                }
                
                // Update checkbox data attribute
                const checkbox = row.querySelector('.line-selector');
                if (checkbox) {
                    checkbox.setAttribute('data-line-index', newLineIndex);
                }
            });
            
            // Update global line counter
            window.journalLineCounter = journalRows.length;
        }
        
        // 🎯 Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize balance calculation
            updateBalance();
            updateRemoveButtons();
            validateForm();
            
            // 🚀 Initialize auto-save system
            addAutoSaveListeners();
            addJournalLineAutoSaveListeners();
            
            // 🔍 Initialize advanced validation system (Phase 4.3.5)
            addAdvancedValidationListeners();
            
            // 🎯 Initialize Smart Autocomplete System (Phase 5.3)
            initializeSmartAutocomplete();
            
            // 🎯 Initialize Bulk Edit System (Phase 5.4)
            console.log('✅ Phase 5.4: Bulk Edit and Copy Features System initialized');
            
            // Handle company dropdown events
            const companyDropdown = document.querySelector('.navbar .form-select');
            if (companyDropdown) {
                // Reset styles on blur
                companyDropdown.addEventListener('blur', function() {
                    this.style.cssText = '';
                });
                
                // Reset styles on change
                companyDropdown.addEventListener('change', function() {
                    this.style.cssText = '';
                    this.blur();
                });
                
                // Reset styles on click outside
                document.addEventListener('click', function(e) {
                    if (!companyDropdown.contains(e.target)) {
                        companyDropdown.style.cssText = '';
                        companyDropdown.blur();
                    }
                });
            }

            // 🎨 Add hover effects to action buttons
            const actionButtons = document.querySelectorAll('.page-header button');
            actionButtons.forEach(button => {
                button.addEventListener('mouseenter', function() {
                    this.style.transform = 'translateY(-2px)';
                    this.style.boxShadow = '0 4px 12px rgba(0,0,0,0.15)';
                });
                
                button.addEventListener('mouseleave', function() {
                    this.style.transform = 'translateY(0)';
                    this.style.boxShadow = '';
                });
            });

            // 📝 Form validation
            const descriptionInput = document.getElementById('description');
                validateForm();
            });
            
            // Add validation listeners to basic fields
            document.getElementById('entry_date').addEventListener('change', validateForm);
            document.getElementById('description').addEventListener('input', validateForm);
            
            // 🔑 Phase 5.2: Complete Keyboard Shortcuts System
            document.addEventListener('keydown', function(e) {
                // 모달이 열려있을 때는 단축키 비활성화 (Escape 제외)
                const isModalOpen = document.querySelector('.modal.show') !== null;
                
                if (isModalOpen && e.key !== 'Escape') {
                    return;
                }
                
                // Escape: 활성 모달 닫기
                if (e.key === 'Escape') {
                    const activeModal = document.querySelector('.modal.show');
                    if (activeModal) {
                        const modal = bootstrap.Modal.getInstance(activeModal);
                        if (modal) {
                            modal.hide();
                        }
                    }
                    return;
                }
                
                // Ctrl 키 조합
                if (e.ctrlKey) {
                    switch(e.key) {
                        case 's': // Save
                            e.preventDefault();
                            if (!document.getElementById('save-btn').disabled) {
                                saveJournalEntry();
                            }
                            break;
                            
                        case 'r': // Reset
                            e.preventDefault();
                            resetForm();
                            break;
                            
                        case 'n': // New Line
                            e.preventDefault();
                            addJournalLine();
                            break;
                            
                        case 't': // Templates
                            e.preventDefault();
                            showTemplatesModal();
                            break;
                            
                        case 'h': // Recent entries (History)
                            e.preventDefault();
                            showRecentEntriesModal();
                            break;
                            
                        case '1': // Cash Transaction
                            e.preventDefault();
                            addQuickLine('cash');
                            break;
                            
                        case '2': // Bank Transaction
                            e.preventDefault();
                            addQuickLine('bank');
                            break;
                            
                        case '3': // Expense Entry
                            e.preventDefault();
                            addQuickLine('expense');
                            break;
                            
                        case '4': // Revenue Entry
                            e.preventDefault();
                            addQuickLine('revenue');
                            break;
                            
                        case 'b': // Bulk Edit Mode
                            e.preventDefault();
                            if (bulkEditMode) {
                                disableBulkEditMode();
                            } else {
                                enableBulkEditMode();
                            }
                            break;
                    }
                }
                
                // Tab 키: 스마트 네비게이션
                if (e.key === 'Tab' && !e.ctrlKey && !e.shiftKey) {
                    handleSmartTabNavigation(e);
                }
                
                // Enter 키: 상황별 액션
                if (e.key === 'Enter' && !e.ctrlKey && !e.shiftKey) {
                    handleSmartEnterAction(e);
                }
            });
            
            // 키보드 단축키 도움말 표시 함수
            function showKeyboardShortcuts() {
                const helpHTML = `
                    <div class="alert alert-info">
                        <h6><i class="bi bi-keyboard me-2"></i>Keyboard Shortcuts</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <small>
                                    <strong>General:</strong><br>
                                    <kbd>Ctrl</kbd> + <kbd>S</kbd> - Save Entry<br>
                                    <kbd>Ctrl</kbd> + <kbd>R</kbd> - Reset Form<br>
                                    <kbd>Ctrl</kbd> + <kbd>N</kbd> - Add Line<br>
                                    <kbd>Ctrl</kbd> + <kbd>T</kbd> - Templates<br>
                                    <kbd>Ctrl</kbd> + <kbd>H</kbd> - Recent Entries<br>
                                    <kbd>Esc</kbd> - Close Modal
                                </small>
                            </div>
                            <div class="col-md-6">
                                <small>
                                    <strong>Quick Actions:</strong><br>
                                    <kbd>Ctrl</kbd> + <kbd>1</kbd> - Cash Transaction<br>
                                    <kbd>Ctrl</kbd> + <kbd>2</kbd> - Bank Transaction<br>
                                    <kbd>Ctrl</kbd> + <kbd>3</kbd> - Expense Entry<br>
                                    <kbd>Ctrl</kbd> + <kbd>4</kbd> - Revenue Entry<br>
                                    <kbd>Ctrl</kbd> + <kbd>B</kbd> - Bulk Edit Mode<br>
                                    <kbd>Tab</kbd> - Smart Navigation<br>
                                    <kbd>Enter</kbd> - Smart Action
                                </small>
                            </div>
                        </div>
                    </div>
                `;
                
                // 기존 도움말 제거
                const existingHelp = document.getElementById('keyboard-help');
                if (existingHelp) {
                    existingHelp.remove();
                    return;
                }
                
                // 새 도움말 추가
                const helpDiv = document.createElement('div');
                helpDiv.id = 'keyboard-help';
                helpDiv.innerHTML = helpHTML;
                
                const container = document.querySelector('.page-container');
                container.insertBefore(helpDiv, container.firstChild);
                
                // 5초 후 자동 제거
                setTimeout(() => {
                    if (document.getElementById('keyboard-help')) {
                        document.getElementById('keyboard-help').remove();
                    }
                }, 8000);
            }
            
            // 스마트 Tab 네비게이션
            function handleSmartTabNavigation(e) {
                const activeElement = document.activeElement;
                
                // 입력 필드에서 Tab 시 다음 논리적 필드로 이동
                if (activeElement && activeElement.closest('.journal-line')) {
                    const currentLine = activeElement.closest('.journal-line');
                    const currentLineIndex = parseInt(currentLine.dataset.lineIndex);
                    
                    if (activeElement.classList.contains('account-select')) {
                        // 계정 → 설명
                        const descInput = currentLine.querySelector('.line-description');
                        if (descInput) {
                            e.preventDefault();
                            descInput.focus();
                        }
                    } else if (activeElement.classList.contains('line-description')) {
                        // 설명 → Debit
                        const debitInput = currentLine.querySelector('.debit-input');
                        if (debitInput) {
                            e.preventDefault();
                            debitInput.focus();
                        }
                    } else if (activeElement.classList.contains('debit-input') || activeElement.classList.contains('credit-input')) {
                        // 금액 → 다음 라인의 계정
                        const nextLine = document.querySelector(`[data-line-index="${currentLineIndex + 1}"]`);
                        if (nextLine) {
                            const nextAccountSelect = nextLine.querySelector('.account-select');
                            if (nextAccountSelect) {
                                e.preventDefault();
                                nextAccountSelect.focus();
                            }
                        } else {
                            // 마지막 라인이면 저장 버튼으로
                            const saveBtn = document.getElementById('save-btn');
                            if (saveBtn && !saveBtn.disabled) {
                                e.preventDefault();
                                saveBtn.focus();
                            }
                        }
                    }
                }
            }
            
            // 스마트 Enter 액션
            function handleSmartEnterAction(e) {
                const activeElement = document.activeElement;
                
                // 저장 버튼에 포커스가 있으면 저장
                if (activeElement && activeElement.id === 'save-btn' && !activeElement.disabled) {
                    e.preventDefault();
                    saveJournalEntry();
                    return;
                }
                
                // 마지막 라인의 금액 필드에서 Enter 시 새 라인 추가
                if (activeElement && (activeElement.classList.contains('debit-input') || activeElement.classList.contains('credit-input'))) {
                    const currentLine = activeElement.closest('.journal-line');
                    const allLines = document.querySelectorAll('.journal-line');
                    const isLastLine = currentLine === allLines[allLines.length - 1];
                    
                    if (isLastLine && (activeElement.value && parseFloat(activeElement.value) > 0)) {
                        e.preventDefault();
                        addJournalLine();
                        
                        // 새로 추가된 라인의 계정 선택으로 포커스 이동
                        setTimeout(() => {
                            const newLine = document.querySelectorAll('.journal-line')[allLines.length];
                            if (newLine) {
                                const accountSelect = newLine.querySelector('.account-select');
                                if (accountSelect) {
                                    accountSelect.focus();
                                }
                            }
                        }, 100);
                    }
                }
                
                // 계정 선택에서 Enter 시 설명으로 이동
                if (activeElement && activeElement.classList.contains('account-select') && activeElement.value) {
                    const descInput = activeElement.closest('.journal-line').querySelector('.line-description');
                    if (descInput) {
                        e.preventDefault();
                        descInput.focus();
                    }
                }
            }
            
            // F1 키로 키보드 단축키 도움말 표시
            document.addEventListener('keydown', function(e) {
                if (e.key === 'F1') {
                    e.preventDefault();
                    showKeyboardShortcuts();
                }
            });
        });
    </script>

    <!-- 📋 Templates Modal -->
    <div class="modal fade" id="templatesModal" tabindex="-1" aria-labelledby="templatesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="templatesModalLabel">
                        <i class="bi bi-file-earmark-text me-2"></i>Journal Entry Templates
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- 템플릿 목록 -->
                        <div class="col-md-4">
                            <div class="d-flex justify-content-between align-items-center mb-3">
                                <h6 class="mb-0">Saved Templates</h6>
                                <button class="btn btn-sm btn-outline-primary" onclick="saveCurrentAsTemplate()">
                                    <i class="bi bi-plus-circle me-1"></i>Save Current
                                </button>
                            </div>
                            <div id="templates-list" class="list-group">
                                <div class="text-center text-muted py-3">
                                    <i class="bi bi-file-earmark-text fs-1 opacity-50"></i>
                                    <p class="mt-2 mb-0">Loading templates...</p>
                                </div>
                            </div>
                        </div>
                        
                        <!-- 템플릿 미리보기 -->
                        <div class="col-md-8">
                            <h6 class="mb-3">Template Preview</h6>
                            <div id="template-preview" class="border rounded p-3 bg-light">
                                <div class="text-center text-muted py-5">
                                    <i class="bi bi-eye fs-1 opacity-50"></i>
                                    <p class="mt-2 mb-0">Select a template to preview</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-success" id="apply-template-btn" onclick="applySelectedTemplate()" disabled>
                        <i class="bi bi-check-circle me-1"></i>Apply Template
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Entries Modal -->
    <div class="modal fade" id="recentEntriesModal" tabindex="-1" aria-labelledby="recentEntriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recentEntriesModalLabel">
                        <i class="bi bi-clock-history me-2"></i>Recent Journal Entries
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterRecentEntries('7')">
                                Last 7 days
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterRecentEntries('30')">
                                Last 30 days
                            </button>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="filterRecentEntries('90')">
                                Last 90 days
                            </button>
                        </div>
                        <button type="button" class="btn btn-primary btn-sm" onclick="refreshRecentEntries()">
                            <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                        </button>
                    </div>
                    
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Store</th>
                                    <th>Description</th>
                                    <th>Lines</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recent-entries-tbody">
                                <tr>
                                    <td colspan="7" class="text-center">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                        <div class="mt-2">Loading recent entries...</div>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    
                    <div id="recent-entries-pagination" class="mt-3">
                        <!-- Pagination will be added here -->
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Phase 5.4: Bulk Edit Modal -->
    <div class="modal fade" id="bulkEditModal" tabindex="-1" aria-labelledby="bulkEditModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="bulkEditModalLabel">
                        <i class="bi bi-check2-all me-2"></i>Bulk Edit Journal Lines
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Selection Controls -->
                    <div class="border rounded p-3 mb-3">
                        <h6 class="mb-3"><i class="bi bi-ui-checks me-2"></i>Line Selection</h6>
                        <div class="row">
                            <div class="col-md-6">
                                <button class="btn btn-outline-primary btn-sm me-2" onclick="selectAllLines()">
                                    <i class="bi bi-check-all me-1"></i>Select All
                                </button>
                                <button class="btn btn-outline-secondary btn-sm" onclick="clearLineSelection()">
                                    <i class="bi bi-x-square me-1"></i>Clear All
                                </button>
                            </div>
                            <div class="col-md-6 text-end">
                                <span class="badge bg-primary" id="selected-lines-count">0 lines selected</span>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Bulk Actions -->
                    <div class="border rounded p-3 mb-3">
                        <h6 class="mb-3"><i class="bi bi-tools me-2"></i>Bulk Actions</h6>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">Set Account for Selected Lines</label>
                                <select class="form-select" id="bulk-account-select">
                                    <option value="">Choose Account...</option>
                                    <?php echo generateAccountOptions($accounts); ?>
                                </select>
                                <button class="btn btn-primary btn-sm mt-2" onclick="applyBulkAccount()">
                                    <i class="bi bi-arrow-right me-1"></i>Apply to Selected
                                </button>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Set Description for Selected Lines</label>
                                <input type="text" class="form-control" id="bulk-description-input" placeholder="Enter description...">
                                <button class="btn btn-primary btn-sm mt-2" onclick="applyBulkDescription()">
                                    <i class="bi bi-arrow-right me-1"></i>Apply to Selected
                                </button>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Copy & Delete Actions -->
                    <div class="border rounded p-3">
                        <h6 class="mb-3"><i class="bi bi-copy me-2"></i>Copy & Delete</h6>
                        <div class="d-flex gap-2">
                            <button class="btn btn-success btn-sm" onclick="copySelectedLines()">
                                <i class="bi bi-clipboard me-1"></i>Copy Selected Lines
                            </button>
                            <button class="btn btn-warning btn-sm" onclick="pasteLines()" id="paste-lines-btn" disabled>
                                <i class="bi bi-clipboard-check me-1"></i>Paste Lines
                            </button>
                            <button class="btn btn-danger btn-sm" onclick="deleteSelectedLines()">
                                <i class="bi bi-trash me-1"></i>Delete Selected
                            </button>
                        </div>
                        <div class="mt-2">
                            <small class="text-muted" id="clipboard-status">No lines in clipboard</small>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="enableBulkEditMode()">
                        <i class="bi bi-pencil-square me-1"></i>Enable Bulk Edit Mode
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Entries Modal -->
    <div class="modal fade" id="recentEntriesModal" tabindex="-1" aria-labelledby="recentEntriesModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-xl">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="recentEntriesModalLabel">
                        <i class="bi bi-clock-history me-2"></i>Recent Journal Entries
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th>Date</th>
                                    <th>Description</th>
                                    <th>Store</th>
                                    <th class="text-end">Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody id="recent-entries-list">
                                <tr>
                                    <td colspan="6" class="text-center">Loading...</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="loadRecentEntriesList()">
                        <i class="bi bi-arrow-clockwise me-1"></i>Refresh
                    </button>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="journal_line_functions.js"></script>
    <script src="advanced_search.js"></script>
    <script src="real_time_validation.js"></script>
    <script src="quick_actions_fix.js"></script>
    <script src="category_tag_handler.js"></script>
    <script src="debug_functions.js"></script>
    <script>
        // 🔐 Authentication Variables
        const userId = '<?= $user_id ?>';
        const companyId = '<?= $company_id ?>';
        const storeId = '<?= $store_id ?>' || null; // 페이지 스테이트에서 받은 store_id
        
        // Make variables available to external JS files
        window.userId = userId;
        window.companyId = companyId;
        window.storeId = storeId;
        
        // Global page state parameters for consistency
        const params = {
            user_id: userId,
            company_id: companyId,
            store_id: storeId
        };
        
        // Global state for user companies and stores (from page state)
        let userCompaniesData = null;
        
        // Load user companies and stores from page state (not API)
        // This uses the navigation state from the dashboard
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
                    updateCompanyDropdown();
                    updateStoreDropdown();
                    console.log('User companies loaded for Journal Entry:', userCompaniesData);
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
            
            console.log('Company dropdown updated for Journal Entry');
        }
        
        // Update store dropdown based on selected company
        function updateStoreDropdown() {
            const storeSelect = document.getElementById('store_id');
            if (!userCompaniesData || !userCompaniesData.companies) {
                return;
            }
            
            // Find selected company
            const selectedCompany = userCompaniesData.companies.find(company => company.company_id === params.company_id);
            
            if (!selectedCompany) {
                storeSelect.innerHTML = '<option value="">Select Store</option>';
                return;
            }
            
            // Store dropdown options
            let storeHTML = '<option value="">Select Store</option>';
            
            if (selectedCompany.stores && selectedCompany.stores.length > 0) {
                selectedCompany.stores.forEach(store => {
                    const isSelected = params.store_id === store.store_id ? 'selected' : '';
                    storeHTML += `<option value="${store.store_id}" ${isSelected}>${store.store_name}</option>`;
                });
            }
            
            storeSelect.innerHTML = storeHTML;
            console.log('Store dropdown updated for Journal Entry');
        }
        
        // Change company function - now uses navigation enhancement
        function changeCompany(companyId) {
            if (companyId && companyId !== params.company_id) {
                // Update navigation state for dynamic linking
                if (window.updateNavigationCompany) {
                    window.updateNavigationCompany(companyId);
                }
                
                // Update local params
                params.company_id = companyId;
                params.store_id = null; // Reset store when company changes
                
                // Update store dropdown for new company
                updateStoreDropdown();
                
                // Update URL
                const newUrl = `?user_id=${params.user_id}&company_id=${companyId}`;
                history.pushState(null, null, newUrl);
                
                console.log('Company changed in Journal Entry to:', companyId);
            }
        }
        
        // 🔍 Searchable Account Dropdown Functions
        let accountsData = []; // 전역 변수로 계정 데이터 저장
        let currentFocusedDropdown = null;
        
        // 계정 데이터 초기화
        function initializeAccountsData() {
            accountsData = <?php
                $jsAccountsData = [];
                foreach ($accounts as $account) {
                    $jsAccountsData[] = [
                        'id' => $account['account_id'],
                        'name' => $account['account_name'],
                        'type' => $account['account_type'],
                        'category_tag' => $account['category_tag'] ?? null
                    ];
                }
                echo json_encode($jsAccountsData);
            ?>;
            console.log('Accounts data initialized:', accountsData.length, 'accounts');
        }
        
        // 계정 필터링 함수
        function filterAccounts(input) {
            const query = input.value.toLowerCase();
            const container = input.closest('.account-dropdown-container');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            if (query.length === 0) {
                showAllAccounts(dropdownList);
            } else {
                const filteredAccounts = accountsData.filter(account => 
                    account.name.toLowerCase().includes(query)
                );
                showFilteredAccounts(dropdownList, filteredAccounts);
            }
            
            dropdownList.style.display = 'block';
        }
        
        // 모든 계정 표시
        function showAllAccounts(dropdownList) {
            const groupedAccounts = groupAccountsByType(accountsData);
            let html = '';
            
            Object.keys(groupedAccounts).forEach(type => {
                html += `<div class="account-dropdown-group">${type.charAt(0).toUpperCase() + type.slice(1)}</div>`;
                groupedAccounts[type].forEach(account => {
                    html += `<div class="account-dropdown-item" data-account-id="${account.id}" data-category-tag="${account.category_tag || ''}" onclick="selectAccount(this)">${account.name}</div>`;
                });
            });
            
            dropdownList.innerHTML = html;
        }
        
        // 필터된 계정 표시
        function showFilteredAccounts(dropdownList, filteredAccounts) {
            let html = '';
            
            if (filteredAccounts.length === 0) {
                html = '<div class="account-dropdown-no-results">No accounts found</div>';
            } else {
                filteredAccounts.forEach(account => {
                    html += `<div class="account-dropdown-item" data-account-id="${account.id}" onclick="selectAccount(this)">${account.name}</div>`;
                });
            }
            
            dropdownList.innerHTML = html;
        }
        
        // 계정 선택 함수
        function selectAccount(item) {
            const accountId = item.dataset.accountId;
            const accountName = item.textContent;
            const container = item.closest('.account-dropdown-container');
            const input = container.querySelector('.account-search-input');
            const hiddenInput = container.querySelector('.account-id-hidden');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            input.value = accountName;
            hiddenInput.value = accountId;
            dropdownList.style.display = 'none';
            
            // 계정 데이터에서 category_tag 찾기
            const accountData = accountsData.find(acc => acc.id === accountId);
            const categoryTag = accountData ? accountData.category_tag : null;
            
            // 💰 Category Tag 기반 UI 활성화
            if (typeof onAccountSelected === 'function') {
                const lineElement = container.closest('tr');
                onAccountSelected(accountId, lineElement, categoryTag);
            }
            
            // Trigger validation
            validateForm();
            
            console.log('Account selected:', accountName, accountId, 'Category Tag:', categoryTag);
        }
        
        // 드롭다운 표시
        function showAccountDropdown(input) {
            currentFocusedDropdown = input;
            const container = input.closest('.account-dropdown-container');
            const dropdownList = container.querySelector('.account-dropdown-list');
            
            if (input.value.length === 0) {
                showAllAccounts(dropdownList);
            } else {
                filterAccounts(input);
            }
            
            dropdownList.style.display = 'block';
        }
        
        // 드롭다운 숨기기
        function hideAccountDropdown(input) {
            setTimeout(() => {
                const container = input.closest('.account-dropdown-container');
                const dropdownList = container.querySelector('.account-dropdown-list');
                dropdownList.style.display = 'none';
                currentFocusedDropdown = null;
            }, 200);
        }
        
        // 계정 타입별 그룹화
        function groupAccountsByType(accounts) {
            const grouped = {};
            accounts.forEach(account => {
                if (!grouped[account.type]) {
                    grouped[account.type] = [];
                }
                grouped[account.type].push(account);
            });
            return grouped;
        }
        
        // 🔧 Change company function
        function changeCompany(companyId) {
            // Force reset dropdown style before navigation
            const dropdown = document.querySelector('.navbar .form-select');
            if (dropdown) {
                dropdown.blur();
                dropdown.style.cssText = '';
            }
            window.location.href = `../journal-entry/?user_id=<?= $user_id ?>&company_id=${companyId}`;
        }
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - Initializing Journal Entry...');
            
            // Load companies and stores first
            loadUserCompaniesAndStores();
            
            // Initialize accounts data
            initializeAccountsData();
            
            // 💰 Phase 5.2: Pre-load Cash Locations for Enhanced Modal
            loadCashLocations().then(() => {
                console.log('Cash locations pre-loaded successfully for enhanced modals');
            }).catch(error => {
                console.error('Failed to pre-load cash locations:', error);
            });
            
            // 🏢 Phase 5.3: Pre-load Counterparties for Enhanced Debt Modal
            loadCounterparties().then(() => {
                console.log('Counterparties pre-loaded successfully for enhanced debt modals');
            }).catch(error => {
                console.error('Failed to pre-load counterparties:', error);
            });
        });
        
        // 클릭 이벤트로 드롭다운 숨기기
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.account-dropdown-container')) {
                document.querySelectorAll('.account-dropdown-list').forEach(list => {
                    list.style.display = 'none';
                });
            }
        });
    </script>
    
    <!-- Navigation Enhancement Script -->
    <script src="../assets/js/navigation-enhancement.js"></script>

</html>
