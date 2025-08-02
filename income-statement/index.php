<?php
/**
 * Financial Management System - Income Statement V3 Clean
 * Simplified version focused on displaying get_income_statement RPC results
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

$user = getCurrentUser($user_id);

// Date parameters
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('n'));

// Validate date ranges
$currentYear = (int)date('Y');
if ($year < 2020 || $year > $currentYear + 1) {
    $year = $currentYear;
}
if ($month < 1 || $month > 12) {
    $month = (int)date('n');
}

$months = [
    1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
    5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
    9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Income Statement - Financial Statements</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    
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
    <style>
        :root {
            /* Professional Color Palette */
            --primary-bg: #ffffff;
            --secondary-bg: #f8f9fa;
            --tertiary-bg: #e9ecef;
            --border-color: #dee2e6;
            --text-primary: #212529;
            --text-secondary: #6c757d;
            --text-muted: #adb5bd;
            
            /* Financial Colors */
            --positive-color: #22c55e;
            --negative-color: #ef4444;
            --neutral-color: #64748b;
            --highlight-color: #3b82f6;
            
            /* Typography */
            --font-primary: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', 'Courier New', monospace;
            
            /* Spacing */
            --spacing-xs: 0.25rem;
            --spacing-sm: 0.5rem;
            --spacing-md: 1rem;
            --spacing-lg: 1.5rem;
            --spacing-xl: 2rem;

            /* Legacy variables for compatibility */
            --hover-bg: rgba(37, 99, 235, 0.05);
            --primary-color: #2563eb;
            --success-color: #059669;
            --danger-color: #dc2626;
            --warning-color: #d97706;
            --card-bg: #ffffff;
            --light-bg: #f8fafc;
            --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
            --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
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
            color: #ffffff !important;
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

        .navbar .form-select {
            border: 2px solid rgba(255, 255, 255, 0.3) !important;
            background: rgba(255, 255, 255, 0.1) !important;
            color: white !important;
            padding: 0.5rem 1rem;
            font-size: 0.875rem;
            min-width: 220px;
            border-radius: 8px !important;
        }

        .navbar .form-select option {
            color: var(--text-primary);
            background: white;
        }

        .company-dropdown-container {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .company-dropdown-label {
            color: rgba(255, 255, 255, 0.8);
            font-size: 0.875rem;
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
            box-shadow: var(--shadow-sm);
        }

        .filter-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: visible;
            position: relative;
            z-index: 1;
            transition: all 0.3s ease;
        }

        .filter-card:hover {
            box-shadow: var(--shadow-lg);
            transform: translateY(-2px);
        }

        .filter-header {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            padding: 1.25rem 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-title {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            display: flex;
            align-items: center;
        }

        .filter-content {
            padding: 1.5rem;
        }

        /* Form Controls */
        .form-control, .form-select:not(.navbar .form-select) {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
            background: white;
        }

        .form-control:focus, .form-select:not(.navbar .form-select):focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.2);
        }

        /* Quick Period Buttons */
        .quick-period-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
        }

        .quick-period-buttons .btn {
            font-size: 0.75rem;
            padding: 0.375rem 0.75rem;
            border-radius: 6px;
            font-weight: 500;
            transition: all 0.2s ease;
        }

        .quick-period-buttons .btn:hover {
            transform: translateY(-1px);
            box-shadow: var(--shadow-sm);
        }

        /* Search Button */
        .btn-search {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }

        /* Balance Sheet Style Cards */
        .balance-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            margin-bottom: 2rem;
        }

        .balance-card:hover {
            transform: translateY(-3px);
            box-shadow: 0 12px 30px rgba(0, 0, 0, 0.1);
        }

        /* Card Headers */
        .card-header-revenue {
            background: var(--gradient-success);
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header-expenses {
            background: var(--gradient-warning);
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header-primary {
            background: var(--gradient-primary);
            color: white;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
        }

        .card-header-revenue::before,
        .card-header-expenses::before,
        .card-header-primary::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20px;
            width: 100px;
            height: 100px;
            background: rgba(255, 255, 255, 0.1);
            border-radius: 50%;
            animation: float 6s ease-in-out infinite;
        }

        @keyframes float {
            0%, 100% { transform: translateY(0px) rotate(0deg); }
            50% { transform: translateY(-20px) rotate(180deg); }
        }

        .card-title {
            font-size: 1.25rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
        }

        .card-subtitle {
            font-size: 0.875rem;
            opacity: 0.9;
            margin-bottom: 1rem;
        }

        .card-total {
            font-size: 2rem;
            font-weight: 900;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .card-content {
            padding: 1.5rem;
        }

        .account-category {
            margin-bottom: 1.5rem;
        }

        .category-header {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .account-list {
            space-y: 0.5rem;
        }

        .account-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.75rem 1rem;
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 8px;
            transition: all 0.2s ease;
            margin-bottom: 0.5rem;
        }

        .account-item:hover {
            background: var(--hover-bg);
            transform: translateX(4px);
            border-color: var(--primary-color);
        }

        .account-name {
            font-weight: 500;
            color: var(--text-primary);
        }

        .account-amount {
            font-family: 'Monaco', 'Menlo', monospace;
            font-weight: 600;
            color: var(--text-secondary);
        }

        .category-total {
            font-weight: 700;
            color: var(--text-primary);
            text-align: right;
            padding: 0.75rem 1rem;
            background: rgba(37, 99, 235, 0.05);
            border-radius: 8px;
            margin-top: 0.5rem;
        }

        .amount-positive {
            color: var(--success-color);
        }

        .amount-negative {
            color: var(--danger-color);
        }

        /* KPI Overview Section */
        .kpi-overview-section {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .kpi-card {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 2rem;
            border-radius: 16px;
            text-align: center;
            font-size: 1.5rem;
            font-weight: 700;
            transition: all 0.3s ease;
            box-shadow: var(--shadow-md);
        }

        .kpi-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-lg);
        }

        .kpi-card.revenue { 
            background: var(--gradient-success); 
        }
        
        .kpi-card.expenses { 
            background: var(--gradient-warning); 
        }
        
        .kpi-card.net-income.profit { 
            background: var(--gradient-primary); 
        }
        
        .kpi-card.net-income.loss {
            background: linear-gradient(135deg, #dc2626 0%, #ef4444 100%);
        }

        .kpi-icon {
            font-size: 2rem;
            margin-bottom: 0.5rem;
        }

        .kpi-label {
            font-size: 1rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            opacity: 0.9;
        }

        .kpi-amount {
            font-size: 2rem;
            font-weight: 900;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        /* Waterfall Flow Section */
        .waterfall-section {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 1rem;
            margin-bottom: 3rem;
            overflow-x: auto;
            padding: 1rem 0;
        }

        .waterfall-item {
            min-width: 120px;
            padding: 1rem;
            background: white;
            border-radius: 12px;
            box-shadow: var(--shadow-md);
            text-align: center;
            font-weight: 600;
            border: 2px solid var(--border-color);
            transition: all 0.3s ease;
        }

        .waterfall-item:hover {
            transform: translateY(-3px);
            box-shadow: var(--shadow-lg);
            border-color: var(--primary-color);
        }

        .waterfall-item.revenue {
            border-color: var(--success-color);
            background: linear-gradient(135deg, #f0fdf4 0%, #dcfce7 100%);
        }

        .waterfall-item.cogs {
            border-color: var(--warning-color);
            background: linear-gradient(135deg, #fefce8 0%, #fef3c7 100%);
        }

        .waterfall-item.gross {
            border-color: var(--success-color);
            background: linear-gradient(135deg, #f0f9ff 0%, #dbeafe 100%);
        }

        .waterfall-item.opex {
            border-color: var(--warning-color);
            background: linear-gradient(135deg, #fdf2f8 0%, #fce7f3 100%);
        }

        .waterfall-item.net {
            border-color: var(--primary-color);
            background: linear-gradient(135deg, #ede9fe 0%, #ddd6fe 100%);
        }

        .waterfall-arrow {
            font-size: 2rem;
            font-weight: bold;
            color: var(--primary-color);
            user-select: none;
        }

        .waterfall-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            margin-bottom: 0.5rem;
        }

        .waterfall-amount {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            font-family: 'Monaco', 'Menlo', monospace;
        }

        /* Detailed Breakdown */
        .detailed-breakdown {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 2rem;
            margin-bottom: 2rem;
        }

        .breakdown-title {
            font-size: 1.125rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid var(--border-color);
            display: flex;
            align-items: center;
        }

        .breakdown-category {
            background: white;
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1rem;
            box-shadow: var(--shadow-sm);
            border: 1px solid var(--border-color);
        }

        .category-name {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
            margin-bottom: 1rem;
            display: flex;
            align-items: center;
        }

        .category-accounts {
            margin-bottom: 1rem;
        }

        .account-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 0.5rem 0;
            border-bottom: 1px solid #f1f5f9;
        }

        .account-row:last-child {
            border-bottom: none;
        }

        .account-name {
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .account-amount {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .category-subtotal {
            font-weight: 700;
            color: var(--primary-color);
            text-align: right;
            padding-top: 0.5rem;
            border-top: 2px solid var(--border-color);
            font-family: 'Monaco', 'Menlo', monospace;
        }

        /* Net Income Summary - Updated */
        .net-income-summary {
            display: none; /* Hide old summary, use new KPI instead */
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .kpi-overview-section {
                grid-template-columns: 1fr;
            }
            
            .detailed-breakdown {
                grid-template-columns: 1fr;
            }
            
            .waterfall-section {
                flex-direction: column;
                gap: 0.5rem;
            }
            
            .waterfall-arrow {
                transform: rotate(90deg);
                font-size: 1.5rem;
            }
            
            .kpi-amount {
                font-size: 1.5rem;
            }
        }

        .empty-state {
            text-align: center;
            padding: 4rem 2rem;
            color: var(--text-secondary);
            background: var(--card-bg);
            border-radius: 16px;
            margin: 2rem 0;
            box-shadow: var(--shadow-sm);
            border: 2px dashed var(--border-color);
        }

        .empty-icon {
            font-size: 3rem;
            opacity: 0.3;
            margin-bottom: 1rem;
            color: var(--text-muted);
        }

        .loading-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(255,255,255,0.8);
            display: none;
            justify-content: center;
            align-items: center;
            z-index: 9999;
        }

        /* Executive Summary Panel */
        .executive-summary-panel {
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .summary-header {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
            display: flex;
            justify-content: space-between;
            align-items: center;
            background: var(--secondary-bg);
        }

        .summary-header h3 {
            margin: 0;
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
            font-family: var(--font-primary);
        }

        .period-indicator {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-family: var(--font-mono);
            background: var(--tertiary-bg);
            padding: 0.25rem 0.5rem;
            border-radius: 3px;
        }

        /* Financial Metrics Grid */
        .financial-metrics-grid {
            padding: 0;
        }

        .metric-row {
            display: grid;
            grid-template-columns: 2fr 1fr 1fr;
            padding: var(--spacing-sm) var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
            align-items: center;
            transition: background-color 0.15s ease;
        }

        .metric-row:hover {
            background: var(--secondary-bg);
        }

        .metric-row.highlight {
            background: var(--secondary-bg);
            font-weight: 600;
        }

        .metric-label {
            font-size: 0.875rem;
            color: var(--text-primary);
            font-family: var(--font-primary);
        }

        .metric-value {
            font-family: var(--font-mono);
            font-size: 1.1rem;
            font-weight: 700;
            text-align: right;
        }

        .metric-value.positive {
            color: #059669;
        }

        .metric-value.negative {
            color: #dc2626;
        }

        .metric-change,
        .metric-margin {
            font-family: var(--font-mono);
            font-size: 0.9rem;
            font-weight: 600;
            text-align: right;
            color: #4f46e5;
        }

        /* Professional Waterfall */
        .waterfall-analysis {
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: var(--spacing-lg);
        }

        .section-header {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
            background: var(--secondary-bg);
        }

        .section-header h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .waterfall-container {
            display: flex;
            align-items: flex-end;
            padding: var(--spacing-lg);
            gap: var(--spacing-md);
            min-height: 200px;
        }

        .waterfall-step {
            flex: 1;
            display: flex;
            flex-direction: column;
            align-items: center;
            position: relative;
        }

        .step-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            margin-bottom: var(--spacing-sm);
            text-align: center;
            font-weight: 500;
        }

        .step-bar {
            width: 100%;
            min-height: 40px;
            border: 1px solid var(--border-color);
            border-radius: 2px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }

        .step-bar.positive {
            background: linear-gradient(180deg, 
                rgba(34, 197, 94, 0.1) 0%, 
                rgba(34, 197, 94, 0.05) 100%);
            border-color: var(--positive-color);
        }

        .step-bar.negative {
            background: linear-gradient(180deg, 
                rgba(239, 68, 68, 0.1) 0%, 
                rgba(239, 68, 68, 0.05) 100%);
            border-color: var(--negative-color);
        }

        .step-value {
            font-family: var(--font-mono);
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .waterfall-connector {
            font-size: 1.25rem;
            color: var(--text-muted);
            font-weight: 600;
            align-self: center;
        }

        /* Professional Data Tables */
        .financial-data-table {
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            overflow: hidden;
        }

        .table-header {
            background: var(--secondary-bg);
            padding: var(--spacing-sm) var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .table-row {
            display: grid;
            grid-template-columns: 2fr 1fr;
            padding: var(--spacing-sm) var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
            align-items: center;
        }

        .table-row:last-child {
            border-bottom: none;
        }

        .table-row:hover {
            background: var(--secondary-bg);
        }

        .account-name {
            font-size: 0.95rem;
            font-weight: 500;
            color: #374151;
        }

        .account-amount {
            font-family: var(--font-mono);
            font-size: 1rem;
            font-weight: 600;
            text-align: right;
            color: #1f2937;
        }

        /* Financial Ratios Panel */
        .financial-ratios-panel {
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: var(--spacing-lg);
        }

        .ratios-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 0;
        }

        .ratio-item {
            padding: var(--spacing-md);
            border-right: 1px solid var(--border-color);
            border-bottom: 1px solid var(--border-color);
            text-align: center;
        }

        .ratio-item:nth-child(4n) {
            border-right: none;
        }

        .ratio-label {
            font-size: 0.75rem;
            color: var(--text-secondary);
            display: block;
            margin-bottom: 0.25rem;
        }

        .ratio-value {
            font-family: var(--font-mono);
            font-size: 1.2rem;
            font-weight: 700;
            color: #1f2937;
        }

        /* Financial Breakdown Section */
        .financial-breakdown-section {
            margin-bottom: var(--spacing-lg);
        }

        .breakdown-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: var(--spacing-lg);
        }

        /* Professional Income Statement Styles */
        .professional-income-statement {
            background: var(--primary-bg);
            border: 1px solid var(--border-color);
            border-radius: 4px;
            margin-bottom: var(--spacing-lg);
            box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        }

        .statement-header {
            padding: var(--spacing-md);
            border-bottom: 1px solid var(--border-color);
            background: var(--secondary-bg);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .statement-header h3 {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 600;
            color: var(--text-primary);
            font-family: var(--font-primary);
        }

        .statement-content {
            padding: 0;
        }

        .statement-section {
            border-bottom: 1px solid var(--border-color);
        }

        .statement-section:last-child {
            border-bottom: none;
        }

        .section-title {
            font-size: 1rem;
            font-weight: 700;
            color: white;
            padding: var(--spacing-sm) var(--spacing-md);
            margin: 0;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            cursor: default;
        }

        .section-title:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        }

        .section-title.revenue {
            background: linear-gradient(135deg, #3b82f6 0%, #1d4ed8 100%);
            color: white;
        }

        .section-title.cogs {
            background: linear-gradient(135deg, #64748b 0%, #475569 100%);
            color: white;
        }

        .section-title.expenses {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
        }

        .section-title.other {
            background: linear-gradient(135deg, #8b5cf6 0%, #7c3aed 100%);
            color: white;
        }

        .line-item {
            display: grid;
            grid-template-columns: 2fr 1fr;
            padding: var(--spacing-sm) var(--spacing-md);
            border-bottom: 1px solid #f1f5f9;
            align-items: center;
            transition: all 0.2s ease;
        }

        .line-item:hover {
            background: linear-gradient(135deg, #f8fafc 0%, #f1f5f9 100%) !important;
            transform: translateX(4px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
        }

        .line-item.subtotal {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            font-weight: 600;
            border-top: 1px solid #cbd5e1;
            border-left: 4px solid #3b82f6;
            border-radius: 0 4px 4px 0;
        }

        .line-item.total {
            background: linear-gradient(135deg, #eff6ff 0%, #dbeafe 100%);
            font-weight: 700;
            border: 2px solid #3b82f6;
            border-left: 6px solid #3b82f6;
            border-radius: 4px;
            box-shadow: 0 2px 8px rgba(59, 130, 246, 0.15);
        }

        .line-item.final-total {
            background: linear-gradient(135deg, #fff7ed 0%, #fed7aa 100%);
            font-weight: 800;
            font-size: 1.1rem;
            border: 3px solid #f97316;
            border-left: 8px solid #f97316;
            border-radius: 6px;
            box-shadow: 0 4px 16px rgba(249, 115, 22, 0.25);
        }

        .line-item-label {
            font-size: 0.95rem;
            color: var(--text-primary);
            font-family: var(--font-primary);
        }

        .line-item-amount {
            font-family: var(--font-mono);
            font-size: 1rem;
            font-weight: 600;
            text-align: right;
            color: var(--text-primary);
        }

        .line-item-amount.positive {
            color: #1d4ed8;
            font-weight: 700;
        }

        .line-item-amount.negative {
            color: #dc2626;
            font-weight: 700;
        }

        .line-item-amount.neutral {
            color: #64748b;
            font-style: italic;
        }

        .account-detail {
            padding-left: var(--spacing-md);
            font-size: 0.875rem;
            color: var(--text-secondary);
            background: rgba(248, 250, 252, 0.6);
            border-left: 3px solid #e2e8f0;
            border-radius: 0 6px 6px 0;
            margin: 2px 0;
        }

        .indent-1 { 
            padding-left: 1rem; 
            color: #1e293b;
            font-weight: 600;
        }
        
        .indent-2 { 
            padding-left: 2rem; 
            color: #475569;
            font-size: 0.9rem;
        }
        
        .indent-3 { 
            padding-left: 3rem; 
            color: #64748b;
            font-size: 0.85rem;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .financial-metrics-grid {
                grid-template-columns: 1fr;
            }
            
            .metric-row {
                grid-template-columns: 1fr;
                gap: var(--spacing-xs);
            }
            
            .waterfall-container {
                flex-direction: column;
                align-items: stretch;
            }
            
            .waterfall-step {
                flex-direction: row;
                justify-content: space-between;
                padding: var(--spacing-sm);
                background: var(--secondary-bg);
                border-radius: 4px;
            }
            
            .step-bar {
                width: auto;
                padding: var(--spacing-xs) var(--spacing-sm);
            }

            .ratios-grid {
                grid-template-columns: repeat(2, 1fr);
            }

            .breakdown-grid {
                grid-template-columns: 1fr;
            }

            .line-item {
                grid-template-columns: 1fr;
                gap: var(--spacing-xs);
            }

            .line-item-amount {
                text-align: left;
            }
        }
    </style>
</head>
<body>
    <!-- Loading Overlay -->
    <div class="loading-overlay" id="loadingOverlay">
        <div class="spinner-border text-primary" role="status">
            <span class="visually-hidden">Loading...</span>
        </div>
    </div>

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
                            <li><a class="dropdown-item" href="../balance-sheet/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">Balance Sheet</a></li>
                            <li><a class="dropdown-item active" href="../income-statement/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>">Income Statement</a></li>
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
            <h1 class="mb-2">
                <i class="bi bi-file-earmark-bar-graph me-2"></i>
                Income Statement
            </h1>
            <p class="mb-0 text-muted">Revenue and expense analysis for financial period</p>
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
                    <!-- Period Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-calendar-event me-1"></i>Period
                        </label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="startDateSelect" 
                                   value="<?= sprintf('%04d-%02d-01', $year, $month) ?>">
                            <span class="input-group-text">to</span>
                            <input type="date" class="form-control" id="endDateSelect" 
                                   value="<?= sprintf('%04d-%02d-%02d', $year, $month, date('t', mktime(0, 0, 0, $month, 1, $year))) ?>">
                        </div>
                        
                        <!-- Quick Period Selection Buttons -->
                        <div class="quick-period-buttons mt-2">
                            <button type="button" class="btn btn-outline-primary btn-sm" id="thisMonthBtn">This Month</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" id="lastMonthBtn">Last Month</button>
                            <button type="button" class="btn btn-outline-info btn-sm" id="thisYearBtn">This Year</button>
                        </div>
                    </div>
                    
                    <!-- Store Filter -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-building me-1"></i>Store Filter
                        </label>
                        <select class="form-select" id="storeSelect">
                            <option value="">All Stores</option>
                            <option value="00000000-0000-0000-0000-000000000000">Headquarters Only</option>
                        </select>
                    </div>
                    
                    <!-- Report View -->
                    <div class="col-md-3">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-bar-chart-line me-1"></i>Report View
                        </label>
                        <select class="form-select" id="viewTypeSelect">
                            <option value="monthly">Monthly</option>
                            <option value="12_month">12 Month</option>
                        </select>
                    </div>
                    
                    <!-- Search Button -->
                    <div class="col-md-3">
                        <label class="form-label">&nbsp;</label>
                        <button type="button" class="btn btn-primary btn-search w-100" id="searchButton" onclick="executeSearch()">
                            <i class="bi bi-search me-2"></i>Search Income Statement
                        </button>
                    </div>
                </div>
                

            </div>
        </div>

        <!-- Empty State -->
        <div class="empty-state" id="emptyState">
            <div class="empty-icon">
                <i class="bi bi-graph-up"></i>
            </div>
            <h5>Please Set Filters</h5>
            <p>Select your filters and click Search to load Income Statement data.</p>
            <button class="btn btn-primary" onclick="resetFilters()">
                <i class="bi bi-arrow-clockwise me-2"></i>Reset Filters
            </button>
        </div>

        <!-- Income Statement Content -->
        <div id="incomeStatementContent" style="display: none;">
            <!-- Executive Summary Panel -->
            <div class="executive-summary-panel">
                <div class="summary-header">
                    <h3>Financial Performance Summary</h3>
                    <div class="period-indicator" id="periodIndicator">Loading...</div>
                </div>
                
                <div class="financial-metrics-grid">
                    <div class="metric-row">
                        <span class="metric-label">Total Revenue</span>
                        <span class="metric-value positive" id="executiveRevenue">₫0</span>
                        <span class="metric-change" id="revenueChange">+0.0%</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Gross Profit</span>
                        <span class="metric-value positive" id="executiveGrossProfit">₫0</span>
                        <span class="metric-margin" id="grossMargin">100.0%</span>
                    </div>
                    <div class="metric-row">
                        <span class="metric-label">Operating Income</span>
                        <span class="metric-value positive" id="executiveOperatingIncome">₫0</span>
                        <span class="metric-margin" id="operatingMargin">100.0%</span>
                    </div>
                    <div class="metric-row highlight">
                        <span class="metric-label">Net Income</span>
                        <span class="metric-value positive" id="executiveNetIncome">₫0</span>
                        <span class="metric-margin" id="netMargin">100.0%</span>
                    </div>
                </div>
            </div>

            <!-- Professional Income Statement -->
            <div class="professional-income-statement">
                <div class="statement-header">
                    <h3>Income Statement</h3>
                    <div class="period-indicator" id="statementPeriod">For the Period Ended</div>
                </div>
                
                <div class="statement-content" id="statementContent">
                    <!-- This will be populated by JavaScript with professional income statement structure -->
                </div>
            </div>

            <!-- Financial Ratios Panel -->
            <div class="financial-ratios-panel">
                <div class="section-header">
                    <h4>Key Financial Ratios</h4>
                </div>
                
                <div class="ratios-grid">
                    <div class="ratio-item">
                        <span class="ratio-label">Gross Margin</span>
                        <span class="ratio-value" id="ratioGrossMargin">100.0%</span>
                    </div>
                    <div class="ratio-item">
                        <span class="ratio-label">Operating Margin</span>
                        <span class="ratio-value" id="ratioOperatingMargin">100.0%</span>
                    </div>
                    <div class="ratio-item">
                        <span class="ratio-label">Net Margin</span>
                        <span class="ratio-value" id="ratioNetMargin">100.0%</span>
                    </div>
                    <div class="ratio-item">
                        <span class="ratio-label">EBITDA Margin</span>
                        <span class="ratio-value" id="ratioEbitdaMargin">100.0%</span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Global variables
        let selectedCompanyId = '<?= $company_id ?>';
        let selectedStoreId = null;
        let currentViewType = 'monthly';
        
        // Initialize page
        document.addEventListener('DOMContentLoaded', function() {
            console.log('🚀 Income Statement V3 Clean - Initializing...');
            
            // Wait for navigation enhancement to load, then update dropdowns
            setTimeout(() => {
                updateCompanyDropdown();
                updateStoreDropdown();
            }, 1000);
            
            setupEventListeners();
            updatePeriodFilterState();
            
            console.log('✅ Initialization completed');
        });
        
        // Company change function
        function changeCompany(companyId) {
            if (companyId && companyId !== selectedCompanyId) {
                selectedCompanyId = companyId;
                
                // Update URL
                const urlParams = new URLSearchParams(window.location.search);
                urlParams.set('company_id', companyId);
                window.location.href = window.location.pathname + '?' + urlParams.toString();
            }
        }
        
        // Update company dropdown
        function updateCompanyDropdown() {
            const select = document.getElementById('companySelect');
            
            // Try to get data from NavigationState or session storage
            let userData = null;
            if (window.NavigationState && window.NavigationState.userCompaniesData) {
                userData = window.NavigationState.userCompaniesData;
            } else {
                const sessionData = sessionStorage.getItem('userCompaniesData');
                if (sessionData) {
                    userData = JSON.parse(sessionData);
                }
            }
            
            if (userData && userData.companies) {
                select.innerHTML = '';
                userData.companies.forEach(company => {
                    const option = document.createElement('option');
                    option.value = company.company_id;
                    option.textContent = company.company_name;
                    option.selected = company.company_id === selectedCompanyId;
                    select.appendChild(option);
                });
                console.log('🏢 Company dropdown updated with', userData.companies.length, 'companies');
            } else {
                select.innerHTML = `<option value="${selectedCompanyId}" selected>Current Company</option>`;
            }
        }
        
        // Update store dropdown
        function updateStoreDropdown() {
            const select = document.getElementById('storeSelect');
            
            // Try to get data from NavigationState or session storage
            let userData = null;
            if (window.NavigationState && window.NavigationState.userCompaniesData) {
                userData = window.NavigationState.userCompaniesData;
            } else {
                const sessionData = sessionStorage.getItem('userCompaniesData');
                if (sessionData) {
                    userData = JSON.parse(sessionData);
                }
            }
            
            if (userData && userData.companies) {
                const company = userData.companies.find(c => c.company_id === selectedCompanyId);
                if (company && company.stores) {
                    // Clear existing options except default ones
                    const defaultOptions = Array.from(select.children);
                    defaultOptions.forEach(option => {
                        if (option.value !== '' && option.value !== '00000000-0000-0000-0000-000000000000') {
                            option.remove();
                        }
                    });
                    
                    // Add individual stores
                    company.stores.forEach(store => {
                        if (store.store_id !== '00000000-0000-0000-0000-000000000000') {
                            const option = document.createElement('option');
                            option.value = store.store_id;
                            option.textContent = store.store_name;
                            select.appendChild(option);
                        }
                    });
                    
                    console.log('🏪 Store dropdown updated with', company.stores.length, 'stores');
                }
            }
        }
        
        // Setup event listeners
        function setupEventListeners() {
            // Store change
            document.getElementById('storeSelect').addEventListener('change', function() {
                selectedStoreId = this.value || null;
                console.log('🏪 Store changed to:', selectedStoreId);
            });
            
            // View type change
            document.getElementById('viewTypeSelect').addEventListener('change', function() {
                currentViewType = this.value;
                console.log('📊 View type changed to:', currentViewType);
                updatePeriodFilterState();
            });
            
            // Quick period buttons
            document.getElementById('thisMonthBtn').addEventListener('click', function() {
                setQuickPeriod('thisMonth');
            });
            
            document.getElementById('lastMonthBtn').addEventListener('click', function() {
                setQuickPeriod('lastMonth');
            });
            
            document.getElementById('thisYearBtn').addEventListener('click', function() {
                setQuickPeriod('thisYear');
            });
        }
        
        // Update period filter state based on view type
        function updatePeriodFilterState() {
            const isMonthlyView = currentViewType === 'monthly';
            const startDateSelect = document.getElementById('startDateSelect');
            const endDateSelect = document.getElementById('endDateSelect');
            const quickPeriodButtons = document.querySelectorAll('.quick-period-buttons .btn');
            
            if (isMonthlyView) {
                // Enable period controls for monthly view
                startDateSelect.disabled = false;
                endDateSelect.disabled = false;
                quickPeriodButtons.forEach(btn => btn.disabled = false);
                
                // Add visual styling
                startDateSelect.style.opacity = '1';
                endDateSelect.style.opacity = '1';
                quickPeriodButtons.forEach(btn => {
                    btn.style.opacity = '1';
                    btn.style.pointerEvents = 'auto';
                });
                
                console.log('📅 Period filter enabled for monthly view');
            } else {
                // Disable period controls for 12-month view
                startDateSelect.disabled = true;
                endDateSelect.disabled = true;
                quickPeriodButtons.forEach(btn => btn.disabled = true);
                
                // Add visual styling to show disabled state
                startDateSelect.style.opacity = '0.5';
                endDateSelect.style.opacity = '0.5';
                quickPeriodButtons.forEach(btn => {
                    btn.style.opacity = '0.5';
                    btn.style.pointerEvents = 'none';
                });
                
                // Set default year range for 12-month view
                const currentYear = new Date().getFullYear();
                startDateSelect.value = `${currentYear}-01-01`;
                endDateSelect.value = `${currentYear}-12-31`;
                
                console.log('📅 Period filter disabled for 12-month view, set to full year:', currentYear);
            }
        }
        
        // Set quick period
        function setQuickPeriod(period) {
            // Only allow quick period selection in monthly view
            if (currentViewType !== 'monthly') {
                console.log('🚫 Quick period not available in 12-month view');
                return;
            }
            
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth() + 1; // JavaScript months are 0-based
            const startDateSelect = document.getElementById('startDateSelect');
            const endDateSelect = document.getElementById('endDateSelect');
            
            switch(period) {
                case 'thisMonth':
                    const thisMonthStart = `${currentYear}-${currentMonth.toString().padStart(2, '0')}-01`;
                    const thisMonthEnd = `${currentYear}-${currentMonth.toString().padStart(2, '0')}-${new Date(currentYear, currentMonth, 0).getDate().toString().padStart(2, '0')}`;
                    startDateSelect.value = thisMonthStart;
                    endDateSelect.value = thisMonthEnd;
                    document.getElementById('viewTypeSelect').value = 'monthly';
                    currentViewType = 'monthly';
                    break;
                case 'lastMonth':
                    const lastMonth = currentMonth === 1 ? 12 : currentMonth - 1;
                    const lastMonthYear = currentMonth === 1 ? currentYear - 1 : currentYear;
                    const lastMonthStart = `${lastMonthYear}-${lastMonth.toString().padStart(2, '0')}-01`;
                    const lastMonthEnd = `${lastMonthYear}-${lastMonth.toString().padStart(2, '0')}-${new Date(lastMonthYear, lastMonth, 0).getDate().toString().padStart(2, '0')}`;
                    startDateSelect.value = lastMonthStart;
                    endDateSelect.value = lastMonthEnd;
                    document.getElementById('viewTypeSelect').value = 'monthly';
                    currentViewType = 'monthly';
                    break;
                case 'thisYear':
                    const yearStart = `${currentYear}-01-01`;
                    const yearEnd = `${currentYear}-12-31`;
                    startDateSelect.value = yearStart;
                    endDateSelect.value = yearEnd;
                    // Don't change view type when This Year is clicked
                    break;
            }
            
            console.log('📅 Quick period set to:', period, '- Start:', startDateSelect.value, 'End:', endDateSelect.value);
        }
        
        // Reset filters
        function resetFilters() {
            const now = new Date();
            const currentYear = now.getFullYear();
            const currentMonth = now.getMonth() + 1;
            
            const startDate = `${currentYear}-${currentMonth.toString().padStart(2, '0')}-01`;
            const endDate = `${currentYear}-${currentMonth.toString().padStart(2, '0')}-${new Date(currentYear, currentMonth, 0).getDate().toString().padStart(2, '0')}`;
            
            document.getElementById('startDateSelect').value = startDate;
            document.getElementById('endDateSelect').value = endDate;
            document.getElementById('storeSelect').value = '';
            document.getElementById('viewTypeSelect').value = 'monthly';
            
            selectedStoreId = null;
            currentViewType = 'monthly';
            
            console.log('🔄 Filters reset to defaults');
        }
        
        // Execute search
        async function executeSearch() {
            console.log('🔍 Execute Search clicked!');
            
            // Show loading
            const loadingOverlay = document.getElementById('loadingOverlay');
            const searchButton = document.getElementById('searchButton');
            const originalText = searchButton.innerHTML;
            
            loadingOverlay.style.display = 'flex';
            searchButton.disabled = true;
            searchButton.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Searching...';
            
            try {
                // Get filter values
                const startDate = document.getElementById('startDateSelect').value;
                const endDate = document.getElementById('endDateSelect').value;
                const storeValue = document.getElementById('storeSelect').value;
                const viewType = document.getElementById('viewTypeSelect').value;
                
                // Validate date inputs
                if (!startDate || !endDate) {
                    throw new Error('Please select both start and end dates');
                }
                
                if (new Date(startDate) > new Date(endDate)) {
                    throw new Error('Start date cannot be after end date');
                }
                
                // Choose API action based on view type
                let apiAction;
                if (viewType === '12_month') {
                    apiAction = 'get_income_statement_monthly';
                } else {
                    apiAction = 'get_income_statement_v2';
                }
                
                // Prepare RPC parameters
                const rpcParams = {
                    action: apiAction,
                    p_company_id: selectedCompanyId,
                    p_start_date: startDate,
                    p_end_date: endDate
                };
                
                if (storeValue && storeValue !== '') {
                    rpcParams.p_store_id = storeValue;
                }
                
                console.log('📊 Search Parameters:', rpcParams);
                
                // Call API
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/x-www-form-urlencoded',
                    },
                    body: new URLSearchParams(rpcParams)
                });
                
                const result = await response.json();
                console.log('📊 API Response:', result);
                
                // Display results based on view type
                if (viewType === '12_month') {
                    if (result && typeof result === 'object') {
                        display12MonthIncomeStatement(result);
                    } else {
                        throw new Error('No monthly data returned or invalid response format');
                    }
                } else {
                    if (Array.isArray(result) && result.length > 0) {
                        displayIncomeStatement(result);
                    } else {
                        throw new Error('No data returned or invalid response format');
                    }
                }
                
            } catch (error) {
                console.error('❌ Error:', error);
                showError('Failed to load Income Statement: ' + error.message);
            } finally {
                // Hide loading
                loadingOverlay.style.display = 'none';
                searchButton.disabled = false;
                searchButton.innerHTML = originalText;
            }
        }
        
        // Display income statement
        function displayIncomeStatement(data) {
            console.log('🎨 Displaying Income Statement data:', data);
            
            // Hide empty state, show content
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('incomeStatementContent').style.display = 'block';
            
            // Show regular panels for monthly view
            document.querySelector('.executive-summary-panel').style.display = 'block';
            document.querySelector('.financial-ratios-panel').style.display = 'block';
            
            // Process data with professional structure
            const processedData = processFinancialData(data);
            
            // Update professional dashboard
            updateProfessionalDashboard(processedData);
            
            // Update professional income statement
            updateProfessionalIncomeStatement(data);
            
            console.log('✅ Professional Income Statement displayed successfully');
        }
        
        // Create section card - Balance Sheet Style
        function createSectionCard(section) {
            const card = document.createElement('div');
            card.className = 'balance-card';
            
            const isRevenue = section.section.toLowerCase().includes('revenue');
            const isExpense = section.section.toLowerCase().includes('expense') || 
                             section.section.toLowerCase().includes('cost');
            
            let headerClass = 'card-header-primary';
            if (isRevenue) headerClass = 'card-header-revenue';
            else if (isExpense) headerClass = 'card-header-expenses';
            
            let icon = 'calculator';
            if (isRevenue) icon = 'graph-up';
            else if (isExpense) icon = 'graph-down';
            
            let subtitle = 'Financial data for the period';
            if (isRevenue) subtitle = 'Income generated for the period';
            else if (isExpense) subtitle = 'Costs incurred for the period';
            
            let html = `
                <div class="${headerClass}">
                    <h5 class="card-title">
                        <i class="bi bi-${icon}"></i>
                        ${section.section}
                    </h5>
                    <p class="card-subtitle">${subtitle}</p>
                    <div class="card-total">${formatCurrency(section.section_total)}</div>
                </div>
                <div class="card-content">
            `;
            
            // Add subcategories if they exist
            if (section.subcategories && section.subcategories.length > 0) {
                section.subcategories.forEach(subcategory => {
                    if (subcategory.accounts && subcategory.accounts.length > 0) {
                        html += `
                            <div class="account-category">
                                <h6 class="category-header">
                                    <i class="bi bi-${isRevenue ? 'cash-coin' : isExpense ? 'credit-card' : 'folder'} me-2"></i>
                                    ${subcategory.subcategory}
                                </h6>
                                <div class="account-list">
                        `;
                        
                        subcategory.accounts.forEach(account => {
                            html += `
                                <div class="account-item">
                                    <span class="account-name">${account.account_name}</span>
                                    <span class="account-amount">${formatCurrency(account.net_amount)}</span>
                                </div>
                            `;
                        });
                        
                        if (subcategory.subcategory_total !== 0) {
                            html += `
                                <div class="category-total">
                                    Subtotal: <span>${formatCurrency(subcategory.subcategory_total)}</span>
                                </div>
                            `;
                        }
                        
                        html += `
                                </div>
                            </div>
                        `;
                    }
                });
            }
            
            html += '</div>';
            card.innerHTML = html;
            
            return card;
        }
        
        // Professional data processing
        function processFinancialData(data) {
            const processed = {
                summary: {
                    revenue: 0,
                    cogs: 0,
                    grossProfit: 0,
                    operatingExpenses: 0,
                    operatingIncome: 0,
                    netIncome: 0
                },
                ratios: {
                    grossMargin: 0,
                    operatingMargin: 0,
                    netMargin: 0,
                    ebitdaMargin: 0
                },
                sections: {},
                trends: {}
            };
            
            // Process sections and calculate key metrics
            data.forEach(section => {
                const sectionName = section.section.toLowerCase();
                processed.sections[sectionName] = section;
                
                // Calculate key metrics
                if (sectionName.includes('revenue')) {
                    processed.summary.revenue = section.section_total;
                } else if (sectionName.includes('cost of goods')) {
                    processed.summary.cogs = Math.abs(section.section_total);
                } else if (sectionName.includes('gross profit')) {
                    processed.summary.grossProfit = section.section_total;
                } else if (sectionName.includes('expenses')) {
                    processed.summary.operatingExpenses = Math.abs(section.section_total);
                } else if (sectionName.includes('operating income')) {
                    processed.summary.operatingIncome = section.section_total;
                } else if (sectionName.includes('net income') && !sectionName.includes('excluding')) {
                    processed.summary.netIncome = section.section_total;
                }
                
                // Extract actual margin values from get_income_statement_v2
                if (sectionName === 'gross margin') {
                    // Margin values come as percentage values (e.g., 100 means 100%)
                    processed.ratios.grossMargin = section.section_total || 0;
                } else if (sectionName === 'operating margin') {
                    processed.ratios.operatingMargin = section.section_total || 0;
                } else if (sectionName === 'net margin') {
                    processed.ratios.netMargin = section.section_total || 0;
                } else if (sectionName === 'ebitda') {
                    // For EBITDA margin, calculate percentage if we have both EBITDA and revenue
                    const ebitdaValue = section.section_total || 0;
                    if (processed.summary.revenue > 0) {
                        processed.ratios.ebitdaMargin = (ebitdaValue / processed.summary.revenue) * 100;
                    }
                }
            });
            
            // Calculate derived metrics if not provided
            if (processed.summary.grossProfit === 0) {
                processed.summary.grossProfit = processed.summary.revenue - processed.summary.cogs;
            }
            if (processed.summary.operatingIncome === 0) {
                processed.summary.operatingIncome = processed.summary.grossProfit - processed.summary.operatingExpenses;
            }
            if (processed.summary.netIncome === 0) {
                processed.summary.netIncome = processed.summary.operatingIncome;
            }
            
            // If margin ratios are still 0, calculate them as percentages
            if (processed.summary.revenue > 0) {
                if (processed.ratios.grossMargin === 0) {
                    processed.ratios.grossMargin = (processed.summary.grossProfit / processed.summary.revenue) * 100;
                }
                if (processed.ratios.operatingMargin === 0) {
                    processed.ratios.operatingMargin = (processed.summary.operatingIncome / processed.summary.revenue) * 100;
                }
                if (processed.ratios.netMargin === 0) {
                    processed.ratios.netMargin = (processed.summary.netIncome / processed.summary.revenue) * 100;
                }
                // Only calculate EBITDA margin if it wasn't already set from data
                if (processed.ratios.ebitdaMargin === 0) {
                    processed.ratios.ebitdaMargin = processed.ratios.operatingMargin; // Simplified for now
                }
            }
            
            return processed;
        }
        
        // Update professional dashboard
        function updateProfessionalDashboard(data) {
            updateExecutiveSummary(data.summary);
            updateFinancialRatios(data.ratios);
            updatePeriodIndicator();
        }
        
        // Executive summary update
        function updateExecutiveSummary(summary) {
            // Update metric values with professional formatting
            document.getElementById('executiveRevenue').textContent = formatProfessionalCurrency(summary.revenue);
            document.getElementById('executiveRevenue').className = `metric-value ${summary.revenue >= 0 ? 'positive' : 'negative'}`;
            
            document.getElementById('executiveGrossProfit').textContent = formatProfessionalCurrency(summary.grossProfit);
            document.getElementById('executiveGrossProfit').className = `metric-value ${summary.grossProfit >= 0 ? 'positive' : 'negative'}`;
            
            document.getElementById('executiveOperatingIncome').textContent = formatProfessionalCurrency(summary.operatingIncome);
            document.getElementById('executiveOperatingIncome').className = `metric-value ${summary.operatingIncome >= 0 ? 'positive' : 'negative'}`;
            
            document.getElementById('executiveNetIncome').textContent = formatProfessionalCurrency(summary.netIncome);
            document.getElementById('executiveNetIncome').className = `metric-value ${summary.netIncome >= 0 ? 'positive' : 'negative'}`;
            
            // Update margins
            const grossMarginPercent = summary.revenue > 0 ? ((summary.grossProfit / summary.revenue) * 100).toFixed(1) : '0.0';
            const operatingMarginPercent = summary.revenue > 0 ? ((summary.operatingIncome / summary.revenue) * 100).toFixed(1) : '0.0';
            const netMarginPercent = summary.revenue > 0 ? ((summary.netIncome / summary.revenue) * 100).toFixed(1) : '0.0';
            
            document.getElementById('grossMargin').textContent = `${grossMarginPercent}%`;
            document.getElementById('operatingMargin').textContent = `${operatingMarginPercent}%`;
            document.getElementById('netMargin').textContent = `${netMarginPercent}%`;
            
            // Update change indicators (placeholder for now)
            document.getElementById('revenueChange').textContent = '+0.0%';
        }
        

        
        // Update financial ratios
        function updateFinancialRatios(ratios) {
            document.getElementById('ratioGrossMargin').textContent = `${ratios.grossMargin.toFixed(1)}%`;
            document.getElementById('ratioOperatingMargin').textContent = `${ratios.operatingMargin.toFixed(1)}%`;
            document.getElementById('ratioNetMargin').textContent = `${ratios.netMargin.toFixed(1)}%`;
            document.getElementById('ratioEbitdaMargin').textContent = `${ratios.ebitdaMargin.toFixed(1)}%`;
        }
        
        // Update professional income statement
        function updateProfessionalIncomeStatement(data) {
            const statementContent = document.getElementById('statementContent');
            let html = '';
            
            // Sort data by section order for proper income statement flow
            const sortedData = [...data].sort((a, b) => {
                const order = {
                    'revenue': 1,
                    'cost of goods sold': 2,
                    'gross profit': 3,
                    'expenses': 4,
                    'operating income': 5,
                    'income before tax': 6,
                    'other comprehensive income': 7,
                    'net income': 8
                };
                return (order[a.section.toLowerCase()] || 99) - (order[b.section.toLowerCase()] || 99);
            });
            
            // Filter out margin sections that should only appear in Key Financial Ratios
            const filteredData = sortedData.filter(section => {
                const sectionLower = section.section.toLowerCase();
                // Exclude margin sections and EBITDA sections from main statement
                return !sectionLower.includes('gross margin') && 
                       !sectionLower.includes('operating margin') && 
                       !sectionLower.includes('net margin') && 
                       !sectionLower.includes('ebitda') && 
                       !sectionLower.includes('net income (excluding error)');
            });
            
            filteredData.forEach(section => {
                const sectionLower = section.section.toLowerCase();
                const isTotal = sectionLower.includes('profit') || sectionLower.includes('income');
                const isFinalTotal = sectionLower === 'net income';
                
                // Determine section color class
                let sectionColorClass = '';
                if (sectionLower.includes('revenue')) {
                    sectionColorClass = 'revenue';
                } else if (sectionLower.includes('cost of goods')) {
                    sectionColorClass = 'cogs';
                } else if (sectionLower.includes('expense')) {
                    sectionColorClass = 'expenses';
                } else if (sectionLower.includes('comprehensive')) {
                    sectionColorClass = 'other';
                }
                
                // Section container
                html += `<div class="statement-section">`;
                
                // Main section line
                if (section.subcategories && section.subcategories.length > 0) {
                    // Section with subcategories
                    html += `<h4 class="section-title ${sectionColorClass}">${section.section}</h4>`;
                    
                    section.subcategories.forEach(subcategory => {
                        if (subcategory.accounts && subcategory.accounts.length > 0) {
                            // Subcategory header
                            html += `
                                <div class="line-item">
                                    <div class="line-item-label indent-1"><strong>${subcategory.subcategory}</strong></div>
                                    <div class="line-item-amount"></div>
                                </div>
                            `;
                            
                            // Individual accounts
                            subcategory.accounts.forEach(account => {
                                const isNegative = account.net_amount < 0;
                                const amountClass = account.net_amount === 0 ? 'neutral' : (isNegative ? 'negative' : 'positive');
                                html += `
                                    <div class="line-item">
                                        <div class="line-item-label indent-2">${account.account_name}</div>
                                        <div class="line-item-amount ${amountClass}">
                                            ${formatProfessionalCurrency(account.net_amount)}
                                        </div>
                                    </div>
                                `;
                            });
                            
                            // Subcategory total
                            const isNegative = subcategory.subcategory_total < 0;
                            const amountClass = subcategory.subcategory_total === 0 ? 'neutral' : (isNegative ? 'negative' : 'positive');
                            html += `
                                <div class="line-item subtotal">
                                    <div class="line-item-label indent-1">Total ${subcategory.subcategory}</div>
                                    <div class="line-item-amount ${amountClass}">
                                        ${formatProfessionalCurrency(subcategory.subcategory_total)}
                                    </div>
                                </div>
                            `;
                        }
                    });
                    
                    // Section total
                    const isNegative = section.section_total < 0;
                    const amountClass = section.section_total === 0 ? 'neutral' : (isNegative ? 'negative' : 'positive');
                    const totalClass = isFinalTotal ? 'final-total' : isTotal ? 'total' : 'subtotal';
                    html += `
                        <div class="line-item ${totalClass}">
                            <div class="line-item-label"><strong>Total ${section.section}</strong></div>
                            <div class="line-item-amount ${amountClass}">
                                ${formatProfessionalCurrency(section.section_total)}
                            </div>
                        </div>
                    `;
                } else {
                    // Simple section (calculated totals)
                    const isNegative = section.section_total < 0;
                    const amountClass = section.section_total === 0 ? 'neutral' : (isNegative ? 'negative' : 'positive');
                    const totalClass = isFinalTotal ? 'final-total' : isTotal ? 'total' : 'subtotal';
                    
                    // Add section header for empty sections with special styling
                    if (section.section_total === 0 && (sectionLower.includes('cost of goods') || sectionLower.includes('expense') || sectionLower.includes('comprehensive'))) {
                        html += `<h4 class="section-title ${sectionColorClass}">${section.section}</h4>`;
                        html += `
                            <div class="line-item">
                                <div class="line-item-label indent-1">No data for this period</div>
                                <div class="line-item-amount neutral">₫0</div>
                            </div>
                        `;
                    } else {
                        html += `
                            <div class="line-item ${totalClass}">
                                <div class="line-item-label"><strong>${section.section}</strong></div>
                                <div class="line-item-amount ${amountClass}">
                                    ${formatProfessionalCurrency(section.section_total)}
                                </div>
                            </div>
                        `;
                    }
                }
                
                html += `</div>`;
            });
            
            statementContent.innerHTML = html;
        }
        
        // Update period indicator
        function updatePeriodIndicator() {
            const startDate = document.getElementById('startDateSelect').value;
            const endDate = document.getElementById('endDateSelect').value;
            
            if (startDate && endDate) {
                const start = new Date(startDate);
                const end = new Date(endDate);
                
                const startFormatted = start.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
                const endFormatted = end.toLocaleDateString('en-US', { 
                    year: 'numeric', 
                    month: 'short', 
                    day: 'numeric' 
                });
                
                document.getElementById('periodIndicator').textContent = `${startFormatted} - ${endFormatted}`;
            } else {
                document.getElementById('periodIndicator').textContent = 'Custom Period';
            }
        }
        
        // Render section breakdown
        function renderSectionBreakdown(section) {
            if (!section.subcategories || section.subcategories.length === 0) {
                return '<p class="text-muted">No detailed data available</p>';
            }
            
            let html = '';
            section.subcategories.forEach(subcategory => {
                if (subcategory.accounts && subcategory.accounts.length > 0) {
                    html += `
                        <div class="breakdown-category">
                            <h6 class="category-name">${subcategory.subcategory}</h6>
                            <div class="category-accounts">
                    `;
                    
                    subcategory.accounts.forEach(account => {
                        html += `
                            <div class="account-row">
                                <span class="account-name">${account.account_name}</span>
                                <span class="account-amount">${formatCurrency(account.net_amount)}</span>
                            </div>
                        `;
                    });
                    
                    html += `
                            </div>
                            <div class="category-subtotal">
                                Subtotal: ${formatCurrency(subcategory.subcategory_total)}
                            </div>
                        </div>
                    `;
                }
            });
            
            return html;
        }
        
        // Update summary - Balance Sheet Style (for compatibility)
        function updateSummary(revenue, expenses, netIncome) {
            document.getElementById('summaryRevenue').textContent = formatCurrency(revenue);
            document.getElementById('summaryExpenses').textContent = formatCurrency(expenses);
            document.getElementById('summaryNetIncome').textContent = formatCurrency(netIncome);
            
            // Update net income card header class based on profit/loss
            const netIncomeHeader = document.getElementById('netIncomeHeader');
            if (netIncome >= 0) {
                netIncomeHeader.className = 'summary-card-header net-income profit';
            } else {
                netIncomeHeader.className = 'summary-card-header net-income loss';
            }
        }
        
        // Professional currency formatting
        function formatProfessionalCurrency(amount) {
            const absAmount = Math.abs(amount || 0);
            const formatted = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(absAmount);
            
            // Financial industry standard: parentheses for negatives
            if (amount < 0) {
                return `(₫${formatted})`;
            }
            return `₫${formatted}`;
        }
        
        // Legacy currency formatting for compatibility
        function formatCurrency(amount) {
            return formatProfessionalCurrency(amount);
        }
        
        // Display 12 Month Income Statement
        function display12MonthIncomeStatement(data) {
            console.log('📊 Displaying 12 Month Income Statement:', data);
            
            // Hide empty state, show content
            document.getElementById('emptyState').style.display = 'none';
            document.getElementById('incomeStatementContent').style.display = 'block';
            
            // Update period indicator
            updatePeriodIndicator();
            
            // Hide regular executive summary and financial ratios for 12-month view
            document.querySelector('.executive-summary-panel').style.display = 'none';
            document.querySelector('.financial-ratios-panel').style.display = 'none';
            
            // Create 12-month Excel-style table
            create12MonthExcelTable(data);
            
            console.log('✅ 12 Month Income Statement displayed successfully');
        }
        
        // Update executive summary for monthly view
        function updateExecutiveSummaryMonthly(yearlyTotals, yearlyMargins) {
            const revenue = yearlyTotals.total_revenue || 0;
            const grossProfit = yearlyTotals.gross_profit || 0;
            const operatingIncome = yearlyTotals.operating_income || 0;
            const netIncome = yearlyTotals.net_income || 0;
            
            document.getElementById('executiveRevenue').textContent = formatProfessionalCurrency(revenue);
            document.getElementById('executiveRevenue').className = `metric-value ${revenue >= 0 ? 'positive' : 'negative'}`;
            
            document.getElementById('executiveGrossProfit').textContent = formatProfessionalCurrency(grossProfit);
            document.getElementById('executiveGrossProfit').className = `metric-value ${grossProfit >= 0 ? 'positive' : 'negative'}`;
            
            document.getElementById('executiveOperatingIncome').textContent = formatProfessionalCurrency(operatingIncome);
            document.getElementById('executiveOperatingIncome').className = `metric-value ${operatingIncome >= 0 ? 'positive' : 'negative'}`;
            
            document.getElementById('executiveNetIncome').textContent = formatProfessionalCurrency(netIncome);
            document.getElementById('executiveNetIncome').className = `metric-value ${netIncome >= 0 ? 'positive' : 'negative'}`;
            
            // Update margins
            if (yearlyMargins) {
                document.getElementById('grossMargin').textContent = `${yearlyMargins.gross_profit_margin || 0}%`;
                document.getElementById('operatingMargin').textContent = `${yearlyMargins.operating_profit_margin || 0}%`;
                document.getElementById('netMargin').textContent = `${yearlyMargins.net_profit_margin || 0}%`;
            }
            
            // Update change indicators (placeholder for now)
            document.getElementById('revenueChange').textContent = '+0.0%';
        }
        
        // Create 12-month Excel-style table
        function create12MonthExcelTable(data) {
            const statementContent = document.getElementById('statementContent');
            
            // Generate 12 months for the year
            const startDate = new Date(document.getElementById('startDateSelect').value);
            const year = startDate.getFullYear();
            
            const months = [];
            const monthNames = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            for (let i = 1; i <= 12; i++) {
                const monthStr = i.toString().padStart(2, '0');
                months.push({
                    key: `${year}-${monthStr}`,
                    display: `${year}/${monthStr}`,
                    shortDisplay: `<div style="font-weight: 700; margin-bottom: 2px;">${year}</div><div style="font-size: 10px; color: #666;">${monthNames[i-1]}</div>`
                });
            }
            
            let html = `
                <style>
                    .excel-table {
                        width: 100%;
                        border-collapse: collapse;
                        font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
                        font-size: 11px;
                        background: white;
                        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
                        margin: 20px 0;
                    }
                    
                    .excel-table th,
                    .excel-table td {
                        border: 1px solid #d0d7de;
                        padding: 6px 8px;
                        text-align: left;
                        white-space: nowrap;
                    }
                    
                    .excel-table th {
                        background: linear-gradient(180deg, #f6f8fa 0%, #e9ecef 100%);
                        font-weight: 600;
                        color: #24292f;
                        border-bottom: 2px solid #d0d7de;
                        font-size: 11px;
                        line-height: 1.2;
                        vertical-align: middle;
                        text-align: center;
                        padding: 8px 6px;
                    }
                    
                    .excel-table .category-header {
                        background: linear-gradient(180deg, #0969da 0%, #0550ae 100%);
                        color: white;
                        font-weight: 700;
                        font-size: 12px;
                        text-align: center;
                    }
                    
                    .excel-table .category-header.revenue {
                        background: linear-gradient(180deg, #1f883d 0%, #1a7f37 100%);
                    }
                    
                    .excel-table .category-header.cost {
                        background: linear-gradient(180deg, #64748b 0%, #475569 100%);
                    }
                    
                    .excel-table .category-header.expenses {
                        background: linear-gradient(180deg, #bf8700 0%, #9a6700 100%);
                    }
                    
                    .excel-table .category-header.other {
                        background: linear-gradient(180deg, #8b5cf6 0%, #7c3aed 100%);
                    }
                    
                    .excel-table .account-name {
                        padding-left: 20px;
                        color: #24292f;
                        font-weight: 500;
                        min-width: 200px;
                    }
                    
                    .excel-table .amount {
                        text-align: right;
                        font-family: 'Consolas', 'Monaco', monospace;
                        font-weight: 600;
                        min-width: 80px;
                    }
                    
                    .excel-table .amount.positive {
                        color: #1f883d;
                    }
                    
                    .excel-table .amount.negative {
                        color: #d1242f;
                    }
                    
                    .excel-table .amount.zero {
                        color: #656d76;
                    }
                    
                    .excel-table .total-row {
                        background: #f6f8fa;
                        font-weight: 700;
                        border-top: 2px solid #d0d7de;
                    }
                    
                    .excel-table .subtotal-row {
                        background: #f1f3f4;
                        font-weight: 600;
                        border-top: 1px solid #d0d7de;
                    }
                    
                    .excel-table .grand-total {
                        background: linear-gradient(180deg, #fff8dc 0%, #ffeaa7 100%);
                        font-weight: 800;
                        font-size: 12px;
                        border-top: 3px solid #d1242f;
                        border-bottom: 3px solid #d1242f;
                    }
                    
                    .table-container {
                        overflow-x: auto;
                        margin: 20px 0;
                        border-radius: 8px;
                        border: 1px solid #d0d7de;
                        /* 세로 스크롤 제거 - 하나의 스크롤바만 사용 */
                    }
                    
                    .first-column {
                        position: sticky;
                        left: 0;
                        background: white;
                        z-index: 10;
                        border-right: 2px solid #d0d7de !important;
                    }
                    
                    .total-column {
                        position: sticky;
                        right: 0;
                        background: #e6f3ff;
                        z-index: 9;
                        border-left: 2px solid #d0d7de !important;
                        font-weight: 800;
                    }
                </style>
                
                <div class="table-container">
                    <table class="excel-table">
                        <thead>
                            <tr>
                                <th class="first-column" style="width: 250px;">Account</th>
            `;
            
            // Add month header columns
            months.forEach(month => {
                html += `<th class="amount">${month.shortDisplay}</th>`;
            });
            
            html += `
                                <th class="amount total-column">Total</th>
                            </tr>
                        </thead>
                        <tbody>
            `;
            
            // Process data and build comprehensive income statement
            const processedData = processMonthlyDataComplete(data, months);
            
            // Revenue Section
            if (processedData.revenue && processedData.revenue.accounts.length > 0) {
                html += createExcelCategorySection('Revenue', 'revenue', processedData.revenue, months);
                
                // Operating Revenue subsection
                const operatingRevenue = processedData.revenue.accounts.filter(acc => 
                    ['Online Sales(VN)', 'Online Sales(KR)', 'Online Sales(Others)', 'Sales revenue'].includes(acc.name));
                if (operatingRevenue.length > 0) {
                    html += createExcelSubsectionHeader('Operating Revenue', months);
                    operatingRevenue.forEach(account => {
                        html += createExcelAccountRow(account, months);
                    });
                    html += createExcelSubtotalRow('Total Operating Revenue', operatingRevenue, months);
                }
                
                // Non-Operating Revenue subsection
                const nonOperatingRevenue = processedData.revenue.accounts.filter(acc => 
                    ['Other revenue'].includes(acc.name));
                if (nonOperatingRevenue.length > 0) {
                    html += createExcelSubsectionHeader('Non-Operating Revenue', months);
                    nonOperatingRevenue.forEach(account => {
                        html += createExcelAccountRow(account, months);
                    });
                    html += createExcelSubtotalRow('Total Non-Operating Revenue', nonOperatingRevenue, months);
                }
                
                html += createExcelCalculatedRow('Total Revenue', processedData.totalRevenue, months, 'total-row');
            }
            
            // Cost of Goods Sold Section
            if (processedData.cost && processedData.cost.accounts.length > 0) {
                html += createExcelCategorySection('Cost of Goods Sold', 'cost', processedData.cost, months);
                
                // Direct Costs subsection
                html += createExcelSubsectionHeader('Direct Costs', months);
                processedData.cost.accounts.forEach(account => {
                    html += createExcelAccountRow(account, months);
                });
                html += createExcelSubtotalRow('Total Direct Costs', processedData.cost.accounts, months);
                html += createExcelCalculatedRow('Total Cost of Goods Sold', processedData.totalCost, months, 'total-row');
            }
            
            // Gross Profit Row
            html += createExcelCalculatedRow('Gross Profit', processedData.grossProfit, months, 'subtotal-row');
            
            // Expenses Section
            if (processedData.expenses && processedData.expenses.accounts.length > 0) {
                html += createExcelCategorySection('Expenses', 'expenses', processedData.expenses, months);
                
                // Operating Expenses subsection
                const operatingExpenses = processedData.expenses.accounts.filter(acc => 
                    !['Income tax expense', 'Impairment loss', 'Interest Expenses'].includes(acc.name));
                if (operatingExpenses.length > 0) {
                    html += createExcelSubsectionHeader('Operating Expenses', months);
                    operatingExpenses.forEach(account => {
                        html += createExcelAccountRow(account, months);
                    });
                    html += createExcelSubtotalRow('Total Operating Expenses', operatingExpenses, months);
                }
                
                // Non-Operating Expenses subsection
                const nonOperatingExpenses = processedData.expenses.accounts.filter(acc => 
                    ['Impairment loss', 'Interest Expenses'].includes(acc.name));
                if (nonOperatingExpenses.length > 0) {
                    html += createExcelSubsectionHeader('Non-Operating Expenses', months);
                    nonOperatingExpenses.forEach(account => {
                        html += createExcelAccountRow(account, months);
                    });
                    html += createExcelSubtotalRow('Total Non-Operating Expenses', nonOperatingExpenses, months);
                }
                
                // Tax Expenses subsection
                const taxExpenses = processedData.expenses.accounts.filter(acc => 
                    ['Income tax expense'].includes(acc.name));
                if (taxExpenses.length > 0) {
                    html += createExcelSubsectionHeader('Tax Expenses', months);
                    taxExpenses.forEach(account => {
                        html += createExcelAccountRow(account, months);
                    });
                    html += createExcelSubtotalRow('Total Tax Expenses', taxExpenses, months);
                }
                
                html += createExcelCalculatedRow('Total Expenses', processedData.totalExpenses, months, 'total-row');
            }
            
            // Operating Income Row
            html += createExcelCalculatedRow('Operating Income', processedData.operatingIncome, months, 'subtotal-row');
            
            // Income Before Tax Row
            html += createExcelCalculatedRow('Income Before Tax', processedData.operatingIncome, months, 'subtotal-row');
            
            // Other Comprehensive Income Section
            if (processedData.otherComprehensive && processedData.otherComprehensive.accounts.length > 0) {
                html += createExcelCategorySection('Other Comprehensive Income', 'other', processedData.otherComprehensive, months);
                
                html += createExcelSubsectionHeader('Comprehensive Income Items', months);
                processedData.otherComprehensive.accounts.forEach(account => {
                    html += createExcelAccountRow(account, months);
                });
                html += createExcelSubtotalRow('Total Comprehensive Income Items', processedData.otherComprehensive.accounts, months);
                html += createExcelCalculatedRow('Total Other Comprehensive Income', processedData.totalOtherComprehensive, months, 'total-row');
            }
            
            // Final Net Income Row
            html += createExcelCalculatedRow('Net Income', processedData.netIncome, months, 'grand-total');
            
            html += `
                        </tbody>
                    </table>
                </div>
            `;
            
            statementContent.innerHTML = html;
        }
        
        // Process monthly data for Excel table - Complete version
        function processMonthlyDataComplete(data, months) {
            const processed = {
                revenue: { accounts: [], totals: {} },
                cost: { accounts: [], totals: {} },
                expenses: { accounts: [], totals: {} },
                totalRevenue: {},
                totalCost: {},
                grossProfit: {},
                totalExpenses: {},
                operatingIncome: {},
                incomeTax: {},
                netIncome: {},
                otherComprehensive: { accounts: [], totals: {} },
                totalOtherComprehensive: {}
            };
            
            // Initialize totals for each month
            months.forEach(month => {
                processed.revenue.totals[month.key] = 0;
                processed.cost.totals[month.key] = 0;
                processed.expenses.totals[month.key] = 0;
                processed.totalRevenue[month.key] = 0;
                processed.totalCost[month.key] = 0;
                processed.grossProfit[month.key] = 0;
                processed.totalExpenses[month.key] = 0;
                processed.operatingIncome[month.key] = 0;
                processed.incomeTax[month.key] = 0;
                processed.netIncome[month.key] = 0;
                processed.otherComprehensive.totals[month.key] = 0;
                processed.totalOtherComprehensive[month.key] = 0;
            });
            
            console.log('📊 Processing RPC monthly data:', data);
            
            // Process sections from get_income_statement_monthly RPC
            if (data.sections && Array.isArray(data.sections)) {
                data.sections.forEach(section => {
                    console.log('Processing section:', section.section, section);
                    
                    const sectionLower = section.section.toLowerCase();
                    
                    // Process subcategories
                    if (section.subcategories && Array.isArray(section.subcategories)) {
                        section.subcategories.forEach(subcategory => {
                            if (subcategory.accounts && Array.isArray(subcategory.accounts)) {
                                subcategory.accounts.forEach(account => {
                                    const accountData = {
                                        name: account.account_name,
                                        monthlyData: account.monthly_amounts || {},
                                        yearlyTotal: account.total || 0,
                                        calculation_method: account.calculation_method
                                    };
                                    
                                    // Categorize based on section type
                                    if (sectionLower.includes('revenue')) {
                                        processed.revenue.accounts.push(accountData);
                                        months.forEach(month => {
                                            const amount = accountData.monthlyData[month.key] || 0;
                                            processed.revenue.totals[month.key] += amount;
                                            processed.totalRevenue[month.key] += amount;
                                        });
                                    } else if (sectionLower.includes('cost of goods')) {
                                        processed.cost.accounts.push(accountData);
                                        months.forEach(month => {
                                            const amount = accountData.monthlyData[month.key] || 0;
                                            processed.cost.totals[month.key] += amount;
                                            processed.totalCost[month.key] += amount;
                                        });
                                    } else if (sectionLower.includes('expenses')) {
                                        processed.expenses.accounts.push(accountData);
                                        months.forEach(month => {
                                            const amount = accountData.monthlyData[month.key] || 0;
                                            processed.expenses.totals[month.key] += amount;
                                            processed.totalExpenses[month.key] += amount;
                                        });
                                    } else if (sectionLower.includes('other comprehensive')) {
                                        processed.otherComprehensive.accounts.push(accountData);
                                        months.forEach(month => {
                                            const amount = accountData.monthlyData[month.key] || 0;
                                            processed.otherComprehensive.totals[month.key] += amount;
                                            processed.totalOtherComprehensive[month.key] += amount;
                                        });
                                    }
                                });
                            }
                        });
                    }
                    
                    // Process calculated sections (Gross Profit, Net Income, etc.)
                    if (section.section_type === 'calculated' && section.section_monthly_totals) {
                        if (sectionLower.includes('gross profit')) {
                            months.forEach(month => {
                                processed.grossProfit[month.key] = section.section_monthly_totals[month.key] || 0;
                            });
                        } else if (sectionLower.includes('net income') && !sectionLower.includes('before')) {
                            months.forEach(month => {
                                processed.netIncome[month.key] = section.section_monthly_totals[month.key] || 0;
                            });
                        } else if (sectionLower.includes('income before tax') || sectionLower.includes('net income before')) {
                            months.forEach(month => {
                                processed.operatingIncome[month.key] = section.section_monthly_totals[month.key] || 0;
                            });
                        } else if (sectionLower.includes('income tax expense') && section.section_monthly_totals) {
                            months.forEach(month => {
                                processed.incomeTax[month.key] = section.section_monthly_totals[month.key] || 0;
                            });
                        }
                    }
                });
            }
            
            // Calculate missing metrics from available data
            months.forEach(month => {
                // If gross profit wasn't calculated, derive it
                if (processed.grossProfit[month.key] === 0) {
                    processed.grossProfit[month.key] = processed.totalRevenue[month.key] - processed.totalCost[month.key];
                }
                
                // If operating income wasn't calculated, derive it
                if (processed.operatingIncome[month.key] === 0) {
                    processed.operatingIncome[month.key] = processed.grossProfit[month.key] - processed.totalExpenses[month.key];
                }
                
                // If net income wasn't calculated, derive it
                if (processed.netIncome[month.key] === 0) {
                    processed.netIncome[month.key] = processed.operatingIncome[month.key] - processed.incomeTax[month.key] + processed.totalOtherComprehensive[month.key];
                }
            });
            
            console.log('📊 Processed data structure:', processed);
            return processed;
        }
        
        // Create Excel category section
        function createExcelCategorySection(categoryName, categoryClass, categoryData, months) {
            let html = `
                <tr>
                    <td class="category-header ${categoryClass} first-column" colspan="${months.length + 2}">${categoryName}</td>
                </tr>
            `;
            return html;
        }
        
        // Create Excel subsection header
        function createExcelSubsectionHeader(subsectionName, months) {
            return `
                <tr>
                    <td class="account-name first-column" style="font-weight: 700; background: #f1f3f4; padding-left: 10px;"><strong>${subsectionName}</strong></td>
                    ${months.map(() => '<td class="amount" style="background: #f1f3f4;"></td>').join('')}
                    <td class="amount total-column" style="background: #f1f3f4;"></td>
                </tr>
            `;
        }
        
        // Create Excel account row
        function createExcelAccountRow(account, months) {
            let html = `
                <tr>
                    <td class="account-name first-column">${account.name}</td>
            `;
            
            months.forEach(month => {
                const amount = account.monthlyData[month.key] || 0;
                const amountClass = amount > 0 ? 'positive' : amount < 0 ? 'negative' : 'zero';
                html += `<td class="amount ${amountClass}">${formatExcelCurrency(amount)}</td>`;
            });
            
            // Yearly total
            const yearlyClass = account.yearlyTotal > 0 ? 'positive' : account.yearlyTotal < 0 ? 'negative' : 'zero';
            html += `<td class="amount ${yearlyClass} total-column">${formatExcelCurrency(account.yearlyTotal)}</td>`;
            html += `</tr>`;
            
            return html;
        }
        
        // Create Excel subtotal row
        function createExcelSubtotalRow(label, accounts, months) {
            let html = `
                <tr class="subtotal-row">
                    <td class="first-column" style="font-weight: 600; padding-left: 10px;">${label}</td>
            `;
            
            let yearlyTotal = 0;
            months.forEach(month => {
                let monthTotal = 0;
                accounts.forEach(account => {
                    monthTotal += account.monthlyData[month.key] || 0;
                });
                yearlyTotal += monthTotal;
                const amountClass = monthTotal > 0 ? 'positive' : monthTotal < 0 ? 'negative' : 'zero';
                html += `<td class="amount ${amountClass}" style="font-weight: 600;">${formatExcelCurrency(monthTotal)}</td>`;
            });
            
            const yearlyClass = yearlyTotal > 0 ? 'positive' : yearlyTotal < 0 ? 'negative' : 'zero';
            html += `<td class="amount ${yearlyClass} total-column" style="font-weight: 700;">${formatExcelCurrency(yearlyTotal)}</td>`;
            html += `</tr>`;
            
            return html;
        }
        
        // Create Excel calculated row (Total Revenue, Gross Profit, etc.)
        function createExcelCalculatedRow(label, monthlyData, months, rowClass) {
            let html = `
                <tr class="${rowClass}">
                    <td class="first-column" style="font-weight: 700;">${label}</td>
            `;
            
            let yearlyTotal = 0;
            months.forEach(month => {
                const amount = monthlyData[month.key] || 0;
                yearlyTotal += amount;
                const amountClass = amount > 0 ? 'positive' : amount < 0 ? 'negative' : 'zero';
                html += `<td class="amount ${amountClass}" style="font-weight: 700;">${formatExcelCurrency(amount)}</td>`;
            });
            
            const yearlyClass = yearlyTotal > 0 ? 'positive' : yearlyTotal < 0 ? 'negative' : 'zero';
            html += `<td class="amount ${yearlyClass} total-column" style="font-weight: 800;">${formatExcelCurrency(yearlyTotal)}</td>`;
            html += `</tr>`;
            
            return html;
        }
        
        // Format currency for Excel display
        function formatExcelCurrency(amount) {
            if (amount === 0) return '₫0';
            
            const absAmount = Math.abs(amount);
            const formatted = new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 0,
                maximumFractionDigits: 0
            }).format(absAmount);
            
            return amount < 0 ? `(₫${formatted})` : `₫${formatted}`;
        }
        
        // Show error
        function showError(message) {
            const emptyState = document.getElementById('emptyState');
            emptyState.innerHTML = `
                <div class="empty-icon">
                    <i class="bi bi-exclamation-triangle text-danger"></i>
                </div>
                <h5 class="text-danger">Error Loading Data</h5>
                <p class="text-danger">${message}</p>
                <button class="btn btn-outline-primary" onclick="location.reload()">
                    <i class="bi bi-arrow-clockwise me-2"></i>Try Again
                </button>
            `;
            emptyState.style.display = 'block';
            document.getElementById('incomeStatementContent').style.display = 'none';
        }
    </script>
    
    <!-- Company Change Function -->
    <script>
        /**
         * Company 변경 함수 - Balance Sheet 스타일과 동일
         */
        function changeCompany(companyId) {
            if (!companyId) {
                console.log('No company selected');
                return;
            }
            
            if (companyId === COMPANY_ID) {
                console.log('Same company selected, no change needed');
                return;
            }
            
            console.log('🔄 Changing company to:', companyId);
            
            // 세션 스토리지에서 사용자 데이터 가져오기
            try {
                const storedData = sessionStorage.getItem('userCompaniesData');
                if (storedData) {
                    const userData = JSON.parse(storedData);
                    
                    // 선택된 회사 정보 찾기
                    const selectedCompany = userData.companies?.find(c => c.company_id === companyId);
                    if (selectedCompany) {
                        console.log('✅ Company found:', selectedCompany.company_name);
                        
                        // URL 파라미터 업데이트
                        const newUrl = `${window.location.pathname}?user_id=${USER_ID}&company_id=${companyId}`;
                        console.log('🌐 Navigating to:', newUrl);
                        
                        // 페이지 이동
                        window.location.href = newUrl;
                    } else {
                        console.error('Company not found in user data');
                    }
                } else {
                    console.error('No user data found in sessionStorage');
                    // 직접 이동 (백업)
                    const newUrl = `${window.location.pathname}?user_id=${USER_ID}&company_id=${companyId}`;
                    window.location.href = newUrl;
                }
            } catch (error) {
                console.error('Error accessing sessionStorage:', error);
                // 직접 이동 (백업)
                const newUrl = `${window.location.pathname}?user_id=${USER_ID}&company_id=${companyId}`;
                window.location.href = newUrl;
            }
        }
        
        /**
         * 페이지 로드 시 회사 드롭다운 초기화
         */
        function initializeCompanyDropdown() {
            console.log('🏢 Initializing company dropdown...');
            
            try {
                const storedData = sessionStorage.getItem('userCompaniesData');
                if (storedData) {
                    const userData = JSON.parse(storedData);
                    if (userData.companies && Array.isArray(userData.companies)) {
                        populateCompanyDropdown(userData.companies);
                        console.log('✅ Company dropdown populated from sessionStorage');
                        return;
                    }
                }
            } catch (error) {
                console.error('Error loading companies from sessionStorage:', error);
            }
            
            // 백업: NavigationState에서 시도
            if (typeof window.NavigationState !== 'undefined' && 
                window.NavigationState.userCompaniesData && 
                window.NavigationState.userCompaniesData.companies) {
                populateCompanyDropdown(window.NavigationState.userCompaniesData.companies);
                console.log('✅ Company dropdown populated from NavigationState');
                return;
            }
            
            // 마지막 백업: 잠시 후 다시 시도
            setTimeout(initializeCompanyDropdown, 1000);
        }
        
        /**
         * Company 드롭다운 채우기
         */
        function populateCompanyDropdown(companies) {
            const companySelect = document.getElementById('companySelect');
            if (!companySelect || !companies || !Array.isArray(companies)) {
                console.error('Cannot populate company dropdown: missing elements or data');
                return;
            }
            
            // 기존 옵션 제거
            companySelect.innerHTML = '';
            
            // 회사 옵션 추가
            companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.company_id;
                option.textContent = company.company_name;
                option.selected = company.company_id === COMPANY_ID;
                companySelect.appendChild(option);
            });
            
            console.log(`🏢 Company dropdown populated with ${companies.length} companies`);
        }
        
        // 페이지 로드 시 초기화
        document.addEventListener('DOMContentLoaded', function() {
            console.log('📋 Income Statement page loaded, initializing...');
            
            // 회사 드롭다운 초기화
            setTimeout(initializeCompanyDropdown, 500);
            
            // 사용자 정보 업데이트
            setTimeout(() => {
                try {
                    const storedData = sessionStorage.getItem('userCompaniesData');
                    if (storedData) {
                        const userData = JSON.parse(storedData);
                        if (userData.user && userData.user.full_name) {
                            document.getElementById('userInfo').innerHTML = 
                                `<i class="bi bi-person-circle me-1"></i> ${userData.user.full_name}`;
                        }
                    }
                } catch (error) {
                    console.error('Error updating user info:', error);
                }
            }, 500);
        });
    </script>
    
    <!-- Navigation Enhancement Script -->
    <script src="../assets/js/navigation-enhancement.js"></script>
    
    <!-- Auto-update Store Dropdown -->
    <script>
        // Auto-update store dropdown after navigation loads
        setTimeout(() => {
            console.log('🔧 Auto-updating store dropdown...');
            updateStoreDropdown();
        }, 1500);
    </script>
</body>
</html>