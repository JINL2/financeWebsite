<?php
/**
 * Cash Control System
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
    <title>Cash Control - Financial Management System</title>
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
            padding: 2rem 1rem;
            max-width: 1400px;
            margin: 0 auto;
        }

        /* Page Header Styles */
        .page-header {
            background: var(--card-bg);
            border-radius: 16px;
            padding: 2rem;
            margin-bottom: 2rem;
            border-left: 4px solid var(--primary-color);
            box-shadow: var(--shadow-md);
            position: relative;
            overflow: hidden;
        }

        .page-header::before {
            content: '';
            position: absolute;
            top: 0;
            right: 0;
            width: 100px;
            height: 100px;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border-radius: 50%;
            transform: translate(50%, -50%);
            opacity: 0.1;
        }

        .page-title {
            font-size: 2rem;
            font-weight: 700;
            color: var(--text-primary);
            margin-bottom: 0.5rem;
            position: relative;
            z-index: 1;
        }

        .page-subtitle {
            color: var(--text-secondary);
            font-size: 1rem;
            margin: 0;
            position: relative;
            z-index: 1;
        }

        /* Filter Section Styles */
        .filter-card {
            background: var(--card-bg);
            border: 1px solid var(--border-color);
            border-radius: 16px;
            box-shadow: var(--shadow-sm);
            overflow: hidden;
        }

        .filter-header {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1rem 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .filter-title {
            margin: 0;
            font-weight: 600;
            color: var(--text-primary);
            font-size: 1rem;
        }

        .filter-content {
            padding: 1.5rem;
        }

        .form-label {
            color: var(--text-secondary);
            font-size: 0.875rem;
            margin-bottom: 0.5rem;
        }

        .form-select {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.2);
        }

        .btn-search {
            padding: 0.75rem 1.5rem;
            font-weight: 600;
        }

        .btn-primary {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            border: none;
        }

        .btn-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 20px rgba(37, 99, 235, 0.3);
        }

        /* Cash Control Content Styles */
        .cash-control-content {
            margin-top: 2rem;
        }

        /* Combined Balance Card Styles */
        .combined-balance-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
        }

        .combined-balance-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        /* Combined Header Styles */
        .combined-header {
            display: grid;
            grid-template-columns: 1fr 1fr;
        }

        .balance-header {
            padding: 1.5rem;
            color: white;
        }

        .expected-header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
        }

        .actual-header {
            background: linear-gradient(135deg, #92400e 0%, #78350f 100%);
        }

        .balance-header .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .balance-header .card-subtitle {
            margin: 0.5rem 0 0 0;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
        }

        /* Combined Body Styles */
        .combined-body {
            padding: 0;
        }

        /* Combined Location List Styles */
        .location-list-combined {
            display: flex;
            flex-direction: column;
        }

        .combined-location-item {
            display: grid;
            grid-template-columns: 1fr 1fr;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            min-height: 100px;
        }

        .combined-location-item:hover {
            background: var(--hover-bg);
        }

        .combined-location-item:last-child {
            border-bottom: none;
        }

        .balance-side, .actual-side {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
        }

        .actual-side {
            border-left: 1px solid var(--border-color);
        }

        /* Cash Ending Section */
        .cash-ending-section {
            padding: 2rem 1.5rem 1rem 1.5rem;
            text-align: center;
            border-top: 1px solid var(--border-color);
            background: var(--light-bg);
        }

        .cash-ending-btn {
            font-size: 0.875rem;
            padding: 0.75rem 2rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
        }

        .cash-ending-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        .balance-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
            transition: all 0.3s ease;
            height: 100%;
        }

        .balance-card:hover {
            transform: translateY(-2px);
            box-shadow: var(--shadow-lg);
        }

        .balance-card .card-header {
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .expected-balance .card-header {
            background: linear-gradient(135deg, #0891b2 0%, #0e7490 100%);
            color: white;
        }

        .actual-balance .card-header {
            background: linear-gradient(135deg, #92400e 0%, #78350f 100%);
            color: white;
        }

        .balance-card .card-title {
            margin: 0;
            font-size: 1.25rem;
            font-weight: 700;
            color: white;
            display: flex;
            align-items: center;
        }

        .balance-card .card-subtitle {
            margin: 0.5rem 0 0 0;
            font-size: 0.875rem;
            color: rgba(255, 255, 255, 0.9);
        }

        .balance-card .card-body {
            padding: 0;
        }

        /* Location List Styles */
        .location-list {
            display: flex;
            flex-direction: column;
        }

        .location-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
            transition: all 0.3s ease;
            min-height: 100px; /* Fixed height for consistent alignment */
        }

        .location-item:hover {
            background: var(--hover-bg);
        }

        .location-item:last-child {
            border-bottom: none;
        }

        .location-info {
            display: flex;
            align-items: center;
            gap: 1rem;
            flex: 1;
            min-height: 48px; /* Ensure consistent height */
        }

        .location-icon {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: white;
            flex-shrink: 0;
        }

        .location-icon.cashier {
            background: linear-gradient(135deg, #10b981 0%, #059669 100%);
        }

        .location-icon.bank {
            background: linear-gradient(135deg, #3b82f6 0%, #2563eb 100%);
        }

        .location-icon.vault {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
        }

        .location-details {
            flex: 1;
            display: flex;
            flex-direction: column;
            justify-content: center;
            min-height: 48px; /* Match icon height */
        }

        .location-name {
            margin: 0;
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .location-description {
            margin: 0.25rem 0 0 0;
            font-size: 0.875rem;
            color: var(--text-secondary);
        }

        .location-amount {
            display: flex;
            flex-direction: column;
            align-items: flex-end;
            justify-content: center;
            gap: 0.5rem;
            text-align: right;
            min-height: 48px; /* Match other elements */
        }

        .location-amount .amount {
            font-size: 1.25rem;
            font-weight: 700;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .expected .amount {
            color: #0891b2;
        }

        .actual .amount {
            color: #059669;
        }

        .not-updated .amount {
            color: var(--text-muted);
        }
        
        .no-exchange-rate .amount {
            color: var(--warning-color);
            font-style: italic;
        }

        .location-amount .amount-label {
            font-size: 0.75rem;
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-muted);
        }

        .cash-ending-btn {
            font-size: 0.875rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-weight: 600;
            transition: all 0.2s ease;
            min-width: 120px;
        }

        .cash-ending-btn:hover {
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
        }

        /* Total Section */
        .total-section {
            background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
            padding: 1.5rem;
            border-top: 1px solid var(--border-color);
        }

        .total-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .total-label {
            font-size: 1rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .total-amount {
            font-size: 1.5rem;
            font-weight: 700;
            font-family: 'Monaco', 'Menlo', monospace;
            color: var(--primary-color);
        }

        /* Variance Analysis */
        .variance-card {
            background: var(--card-bg);
            border-radius: 16px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-color);
            overflow: hidden;
        }

        .variance-card .card-header {
            background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%);
            padding: 1.5rem;
            border-bottom: 1px solid var(--border-color);
        }

        .variance-card .card-body {
            padding: 1.5rem;
        }

        .variance-grid {
            display: flex;
            flex-direction: column;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .variance-item {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 1rem;
            background: var(--light-bg);
            border-radius: 10px;
            border: 1px solid var(--border-color);
        }

        .variance-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex: 1;
        }

        .variance-actions {
            display: flex;
            gap: 0.5rem;
            align-items: center;
        }

        .variance-info .location-name {
            font-weight: 600;
            color: var(--text-primary);
        }

        .variance-amount {
            font-weight: 700;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .variance-item.positive .variance-amount {
            color: var(--success-color);
        }

        .variance-item.negative .variance-amount {
            color: var(--danger-color);
        }

        .variance-bar {
            width: 100px;
            height: 8px;
            background: #e2e8f0;
            border-radius: 4px;
            overflow: hidden;
        }

        .variance-fill {
            height: 100%;
            transition: width 0.6s ease;
        }

        .variance-fill.positive {
            background: linear-gradient(90deg, var(--success-color), #10b981);
        }

        .variance-fill.negative {
            background: linear-gradient(90deg, var(--danger-color), #f87171);
        }

        .variance-percentage {
            font-size: 0.875rem;
            font-weight: 600;
            min-width: 60px;
            text-align: right;
        }

        .variance-item.positive .variance-percentage {
            color: var(--success-color);
        }

        .variance-item.negative .variance-percentage {
            color: var(--danger-color);
        }
        
        /* Warning styles for missing exchange rates */
        .location-amount.no-exchange-rate {
            border-left: 3px solid var(--warning-color);
            padding-left: 12px;
        }
        
        .location-amount.no-exchange-rate .amount {
            font-size: 0.9rem;
        }

        .total-variance {
            padding-top: 1rem;
            border-top: 2px solid var(--border-color);
        }

        .variance-summary {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 1rem;
            background: var(--card-bg);
            border-radius: 10px;
            border: 2px solid;
        }

        .variance-summary.positive {
            border-color: var(--success-color);
            background: rgba(5, 150, 105, 0.05);
        }

        .variance-summary.negative {
            border-color: var(--danger-color);
            background: rgba(220, 38, 38, 0.05);
        }

        .summary-label {
            font-size: 1.125rem;
            font-weight: 600;
            color: var(--text-primary);
        }

        .summary-amount {
            font-size: 1.5rem;
            font-weight: 700;
            font-family: 'Monaco', 'Menlo', monospace;
        }

        .variance-summary.positive .summary-amount {
            color: var(--success-color);
        }

        .variance-summary.negative .summary-amount {
            color: var(--danger-color);
        }

        .summary-percentage {
            font-size: 1rem;
            font-weight: 600;
            margin-left: 0.5rem;
        }

        /* Disabled button styles */
        .variance-actions .btn:disabled {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: #ffffff !important;
            opacity: 0.7 !important;
            cursor: not-allowed !important;
        }

        .variance-actions .btn:disabled:hover {
            background-color: #6c757d !important;
            border-color: #6c757d !important;
            color: #ffffff !important;
            transform: none !important;
            box-shadow: none !important;
        }

        /* Modal Styles */
        .modal-content {
            border-radius: 16px;
            border: none;
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.25);
        }

        .modal-header {
            background: linear-gradient(135deg, var(--primary-color) 0%, #1d4ed8 100%);
            color: white;
            border-bottom: none;
            border-radius: 16px 16px 0 0;
            padding: 1.5rem;
        }

        .modal-title {
            font-weight: 700;
            display: flex;
            align-items: center;
        }

        .btn-close {
            filter: invert(1);
        }

        .modal-body {
            padding: 2rem;
        }

        /* Store Selection Styles */
        .store-selection-section {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }
        
        .selected-store-info {
            text-align: center;
        }
        
        .store-dropdown-section .form-select {
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .store-dropdown-section .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.2);
        }
        
        /* Cash Location Selection Styles */
        .cash-location-section {
            background: #f0f9ff;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #bae6fd;
        }
        
        /* Cash Entry Section Styles */
        .cash-entry-section {
            background: #f8fafc;
            border-radius: 10px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .currency-section {
            background: white;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #e2e8f0;
        }
        
        .currency-title {
            color: var(--primary-color);
            font-weight: 600;
            margin-bottom: 1rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid #e2e8f0;
        }
        
        .denominations-grid {
            display: flex;
            flex-direction: column;
            gap: 0.75rem;
        }
        
        .denomination-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            padding: 0.75rem;
            background: #f8fafc;
            border-radius: 6px;
            border: 1px solid #e2e8f0;
        }
        
        .denomination-label {
            font-weight: 600;
            font-family: 'Monaco', 'Menlo', monospace;
            color: var(--text-primary);
            min-width: 120px;
            font-size: 0.95rem;
        }
        
        .denomination-input {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            flex: 1;
        }
        
        .denomination-input .form-control {
            max-width: 120px;
            text-align: center;
        }
        
        /* Hide number input spinners (up/down arrows) */
        .denomination-input .form-control::-webkit-outer-spin-button,
        .denomination-input .form-control::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        
        .denomination-input .form-control[type=number] {
            -moz-appearance: textfield;
        }
        
        .denomination-input .input-label {
            font-size: 0.875rem;
            color: var(--text-secondary);
            font-weight: 500;
        }
        
        /* Vault Direction Section Styles */
        .vault-direction-section {
            background: #fff3cd;
            border-radius: 8px;
            padding: 1rem;
            border: 1px solid #ffeaa7;
        }
        
        .vault-direction-section .btn-group {
            border-radius: 8px;
            overflow: hidden;
        }
        
        .vault-direction-section .btn {
            padding: 0.75rem 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .vault-direction-section .btn-check:checked + .btn-outline-success {
            background-color: var(--success-color);
            border-color: var(--success-color);
            color: white;
        }
        
        .vault-direction-section .btn-check:checked + .btn-outline-danger {
            background-color: var(--danger-color);
            border-color: var(--danger-color);
            color: white;
        }
        
        .bank-entry-content {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            border: 1px solid #e2e8f0;
        }
        
        .amount-input-section .input-group {
            max-width: 300px;
        }
        
        .cash-location-section .form-select {
            border: 2px solid #0ea5e9;
            border-radius: 8px;
            padding: 0.75rem;
            transition: all 0.3s ease;
        }
        
        .cash-location-section .form-select:focus {
            border-color: #0284c7;
            box-shadow: 0 0 10px rgba(14, 165, 233, 0.3);
        }

        .current-info {
            background: var(--light-bg);
            border-radius: 10px;
            padding: 1rem;
            border: 1px solid var(--border-color);
        }

        .info-item {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 0.5rem;
        }

        .info-item:last-child {
            margin-bottom: 0;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-secondary);
        }

        .info-value {
            font-weight: 700;
            font-family: 'Monaco', 'Menlo', monospace;
            color: var(--text-primary);
        }

        .input-section .form-label {
            color: var(--text-primary);
            font-size: 1rem;
        }

        .input-group-text {
            background: var(--light-bg);
            border-color: var(--border-color);
            font-weight: 600;
            color: var(--text-primary);
        }

        .form-control:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.2);
        }

        .variance-preview .alert {
            border-radius: 10px;
            border: none;
        }

        .modal-footer {
            padding: 1.5rem 2rem;
            border-top: 1px solid var(--border-color);
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .location-item {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .location-amount {
                align-items: flex-start;
                width: 100%;
            }
            
            .variance-item {
                flex-direction: column;
                gap: 0.75rem;
            }
            
            .variance-info {
                width: 100%;
            }
            
            .variance-bar {
                width: 100%;
            }
            
            .variance-summary {
                flex-direction: column;
                gap: 0.5rem;
                text-align: center;
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
            <h1 class="page-title">Cash Control</h1>
            <p class="page-subtitle">Manage and monitor cash positions across all locations</p>
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
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Company</label>
                        <select class="form-select" id="companyFilter">
                            <option value="">Select Company</option>
                        </select>
                    </div>
                    
                    <!-- Store Filter -->
                    <div class="col-md-6">
                        <label class="form-label fw-semibold">Store Filter</label>
                        <select class="form-select" id="storeFilter">
                            <option value="">All Stores</option>
                        </select>
                    </div>
                </div>
                
                <div class="row mt-3">
                    <div class="col-md-6">
                        <!-- Search Button -->
                        <button class="btn btn-primary btn-search" id="searchBtn">
                            <i class="bi bi-search me-2"></i> Search
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Control Content -->
        <div class="cash-control-content" id="cashControlContent" style="display: none;">
            <!-- Single Combined Card -->
            <div class="combined-balance-card">
                <!-- Header Row -->
                <div class="combined-header">
                    <div class="balance-header expected-header">
                        <h5 class="card-title">
                            <i class="bi bi-calculator me-2"></i>
                            Balance
                        </h5>
                        <p class="card-subtitle">Based on Balance Sheet</p>
                    </div>
                    <div class="balance-header actual-header">
                        <h5 class="card-title">
                            <i class="bi bi-clipboard-check me-2"></i>
                            Actual Cash
                        </h5>
                        <p class="card-subtitle">Cash ending input for today</p>
                    </div>
                </div>
                
                <!-- Content Body -->
                <div class="combined-body">
                    <div class="location-list-combined" id="locationListCombined">
                        <!-- Dynamic content will be inserted here -->
                    </div>
                    
                    <!-- Cash Ending Button -->
                    <div class="cash-ending-section">
                        <button class="btn btn-primary cash-ending-btn" onclick="openCashEndingModal('all', 'All Locations')">
                            <i class="bi bi-pencil-square me-2"></i>
                            Cash Ending
                        </button>
                    </div>
                </div>
            </div>

            <!-- Variance Analysis -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="variance-card">
                        <div class="card-header">
                            <h5 class="card-title">
                                <i class="bi bi-graph-up-arrow me-2"></i>
                                Difference
                            </h5>
                            <p class="card-subtitle">Difference Between Balance & Actual</p>
                        </div>
                        <div class="card-body">
                            <div class="variance-grid">
                                <div class="variance-item positive">
                                    <div class="variance-info">
                                        <span class="location-name">Cashier</span>
                                        <span class="variance-amount">-₫50,000</span>
                                    </div>
                                    <div class="variance-bar">
                                        <div class="variance-fill negative" style="width: 15%;"></div>
                                    </div>
                                    <span class="variance-percentage">-2.0%</span>
                                </div>
                                
                                <div class="variance-item positive">
                                    <div class="variance-info">
                                        <span class="location-name">Bank Account</span>
                                        <span class="variance-amount">+₫70,000</span>
                                    </div>
                                    <div class="variance-bar">
                                        <div class="variance-fill positive" style="width: 25%;"></div>
                                    </div>
                                    <span class="variance-percentage">+0.4%</span>
                                </div>
                                
                                <div class="variance-item negative">
                                    <div class="variance-info">
                                        <span class="location-name">Vault</span>
                                        <span class="variance-amount">-₫5,000,000</span>
                                    </div>
                                    <div class="variance-bar">
                                        <div class="variance-fill negative" style="width: 100%;"></div>
                                    </div>
                                    <span class="variance-percentage">-100%</span>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Cash Ending Modal -->
        <div class="modal fade" id="cashEndingModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title">
                            <i class="bi bi-cash-coin me-2"></i>
                            Cash Ending
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body">
                        <!-- Store Selection Section -->
                        <div class="store-selection-section mb-4">
                            <!-- Will be populated dynamically based on filter selection -->
                            <div id="storeSelectionContent">
                                <!-- Content will be updated dynamically -->
                            </div>
                        </div>
                        
                        <!-- Other content will be added in next steps -->
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancel
                        </button>
                        <button type="button" class="btn btn-primary" id="saveCashEndingBtn">
                            <i class="bi bi-check-circle me-1"></i>
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Make Error Confirmation Modal -->
        <div class="modal fade" id="makeErrorConfirmModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header bg-danger text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            Make Error
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-exclamation-triangle-fill text-warning" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="mb-3">Are you sure you want to make an error entry for:</h6>
                        <div class="alert alert-light border">
                            <strong id="errorLocationName">Location Name</strong><br>
                            <span class="text-muted">Variance: </span>
                            <span id="errorVarianceAmount" class="fw-bold">₫0</span>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancel
                        </button>
                        <button type="button" class="btn btn-danger" id="confirmMakeErrorBtn">
                            <i class="bi bi-check-circle me-1"></i>
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Foreign Currency Translation Confirmation Modal -->
        <div class="modal fade" id="foreignCurrencyTranslationModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header bg-info text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-currency-exchange me-2"></i>
                            Foreign Currency Translation
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-currency-exchange text-info" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="mb-3">Are you sure you want to make a foreign currency translation entry for:</h6>
                        <div class="alert alert-light border">
                            <strong id="foreignCurrencyLocationName">Location Name</strong><br>
                            <span class="text-muted">Variance: </span>
                            <span id="foreignCurrencyVarianceAmount" class="fw-bold">₫0</span>
                        </div>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                            <i class="bi bi-x-circle me-1"></i>
                            Cancel
                        </button>
                        <button type="button" class="btn btn-info" id="confirmForeignCurrencyTranslationBtn">
                            <i class="bi bi-check-circle me-1"></i>
                            OK
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Success Modal -->
        <div class="modal fade" id="successModal" tabindex="-1">
            <div class="modal-dialog modal-dialog-centered modal-sm">
                <div class="modal-content">
                    <div class="modal-header bg-success text-white">
                        <h5 class="modal-title">
                            <i class="bi bi-check-circle me-2"></i>
                            Success
                        </h5>
                        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
                    </div>
                    <div class="modal-body text-center">
                        <div class="mb-3">
                            <i class="bi bi-check-circle-fill text-success" style="font-size: 3rem;"></i>
                        </div>
                        <h6 class="mb-3" id="successMessage">Operation completed successfully!</h6>
                    </div>
                    <div class="modal-footer justify-content-center">
                        <button type="button" class="btn btn-success" data-bs-dismiss="modal">
                            <i class="bi bi-check-circle me-1"></i>
                            OK
                        </button>
                    </div>
                </div>
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
        let currentCurrencySymbol = '₫'; // Default symbol, will be updated dynamically

        // Load cash locations for selected store from Supabase
        async function loadCashLocationsForStore(companyId, storeId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_cash_locations_for_store');
                formData.append('company_id', companyId);
                
                // Handle HeadQuarter case (store_id = null)
                if (storeId === 'null' || storeId === null) {
                    formData.append('store_id', '');
                    formData.append('is_headquarters', 'true');
                } else {
                    formData.append('store_id', storeId);
                }
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    return data.data || [];
                } else {
                    console.error('Failed to load cash locations:', data.error || 'Unknown error');
                    return [];
                }
            } catch (error) {
                console.error('Error loading cash locations:', error);
                return [];
            }
        }
        async function loadCurrencySymbol(companyId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_company_currency_symbol');
                formData.append('company_id', companyId);
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success && data.symbol) {
                    currentCurrencySymbol = data.symbol;
                    console.log('Currency symbol loaded:', currentCurrencySymbol);
                } else {
                    console.warn('Failed to load currency symbol, using default:', data.error || 'Unknown error');
                    currentCurrencySymbol = '₫'; // Fallback to VND
                }
            } catch (error) {
                console.error('Error loading currency symbol:', error);
                currentCurrencySymbol = '₫'; // Fallback to VND
            }
        }
        
        // Load balance amounts for cash locations
        async function loadBalanceAmounts(companyId, storeId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_cash_balance_amounts');
                formData.append('company_id', companyId);
                if (storeId && storeId !== '') {
                    formData.append('store_id', storeId);
                }
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    // Return the structured data with balance and actual
                    return data.data || { balance: [], actual: [] };
                } else {
                    console.error('Failed to load balance amounts:', data.error || 'Unknown error');
                    return { balance: [], actual: [] };
                }
            } catch (error) {
                console.error('Error loading balance amounts:', error);
                return { balance: [], actual: [] };
            }
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
            const filterSelect = document.getElementById('companyFilter');
            
            if (!userCompaniesData || !userCompaniesData.companies) {
                select.innerHTML = '<option value="">No companies available</option>';
                filterSelect.innerHTML = '<option value="">No companies available</option>';
                return;
            }
            
            // Update navbar dropdown
            select.innerHTML = '';
            userCompaniesData.companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.company_id;
                option.textContent = company.company_name;
                option.selected = company.company_id === params.company_id;
                select.appendChild(option);
            });
            
            // Update filter dropdown
            filterSelect.innerHTML = '';
            userCompaniesData.companies.forEach(company => {
                const option = document.createElement('option');
                option.value = company.company_id;
                option.textContent = company.company_name;
                option.selected = company.company_id === params.company_id;
                filterSelect.appendChild(option);
            });
            
            // Update store filter based on selected company
            updateStoreFilter(params.company_id);
            
            // NavigationState에 데이터 저장 (다른 페이지에서 사용)
            if (window.NavigationState && window.NavigationState.setUserData) {
                window.NavigationState.setUserData(userCompaniesData);
            }
        }
        
        // Update store filter based on selected company
        function updateStoreFilter(companyId) {
            const storeFilterSelect = document.getElementById('storeFilter');
            if (!userCompaniesData || !userCompaniesData.companies) {
                storeFilterSelect.innerHTML = '<option value="">All Stores</option>';
                return;
            }
            
            // 선택된 회사 찾기
            const selectedCompany = userCompaniesData.companies.find(company => company.company_id === companyId);
            
            if (!selectedCompany) {
                storeFilterSelect.innerHTML = '<option value="">All Stores</option>';
                return;
            }
            
            // Store Filter 옵션 생성
            storeFilterSelect.innerHTML = '<option value="">All Stores</option>';
            
            if (selectedCompany.stores && selectedCompany.stores.length > 0) {
                selectedCompany.stores.forEach(store => {
                    const option = document.createElement('option');
                    option.value = store.store_id;
                    option.textContent = store.store_name;
                    option.selected = params.store_id === store.store_id;
                    storeFilterSelect.appendChild(option);
                });
            }
        }
        
        // Change company
        function changeCompany(companyId) {
            if (companyId && companyId !== params.company_id) {
                // 네비게이션 상태 업데이트
                if (window.updateNavigationCompany) {
                    window.updateNavigationCompany(companyId);
                }
                
                // 파라미터 업데이트
                params.company_id = companyId;
                params.store_id = null; // 회사 변경 시 스토어 초기화
                
                // URL 업데이트
                const newUrl = `?user_id=${params.user_id}&company_id=${companyId}`;
                history.pushState(null, null, newUrl);
                
                // 페이지 새로고침
                window.location.reload();
                
                console.log('Company changed to:', companyId);
            }
        }

        // Load data on page load
        document.addEventListener('DOMContentLoaded', function() {
            loadUserCompaniesAndStores(); // Load companies
            
            // Company filter change event
            document.getElementById('companyFilter').addEventListener('change', function() {
                const selectedCompanyId = this.value;
                if (selectedCompanyId) {
                    updateStoreFilter(selectedCompanyId);
                }
            });
            
            // Search button click event
            document.getElementById('searchBtn').addEventListener('click', function() {
                const selectedCompanyId = document.getElementById('companyFilter').value;
                const selectedStoreId = document.getElementById('storeFilter').value;
                
                console.log('Search with filters:', {
                    company_id: selectedCompanyId,
                    store_id: selectedStoreId
                });
                
                // Load cash locations and show content
                loadCashLocations(selectedCompanyId, selectedStoreId);
            });
            
            // Cash Ending button click events will be attached dynamically after loading locations
        });
        
        // Show success modal
        function showSuccessModal(message) {
            document.getElementById('successMessage').textContent = message;
            const modal = new bootstrap.Modal(document.getElementById('successModal'));
            modal.show();
        }
        
        // Update store selection content based on current filter
        function updateStoreSelectionContent() {
            const storeSelectionContent = document.getElementById('storeSelectionContent');
            const selectedCompanyId = document.getElementById('companyFilter').value;
            const selectedStoreId = document.getElementById('storeFilter').value;
            
            if (!selectedCompanyId) {
                storeSelectionContent.innerHTML = '<div class="alert alert-warning">Please select a company first</div>';
                return;
            }
            
            // Find the selected company data
            const selectedCompany = userCompaniesData?.companies?.find(company => company.company_id === selectedCompanyId);
            
            if (!selectedCompany) {
                storeSelectionContent.innerHTML = '<div class="alert alert-warning">Company data not found</div>';
                return;
            }
            
            if (selectedStoreId && selectedStoreId !== '') {
                // Specific store is selected - show selected store info and load cash locations
                const selectedStore = selectedCompany.stores?.find(store => store.store_id === selectedStoreId);
                const storeName = selectedStore ? selectedStore.store_name : 'Unknown Store';
                
                storeSelectionContent.innerHTML = `
                    <div class="selected-store-info mb-3">
                        <div class="d-flex align-items-center">
                            <strong class="me-2">Selected Store:</strong>
                            <span class="badge bg-primary fs-6">${escapeHtml(storeName)}</span>
                        </div>
                    </div>
                    
                    <!-- Cash Location Selection -->
                    <div class="cash-location-section">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-geo-alt me-1"></i>
                            Select Cash Location:
                        </label>
                        <select class="form-select" id="modalCashLocationSelect" onchange="handleCashLocationSelection()">
                            <option value="">Loading cash locations...</option>
                        </select>
                        <div class="form-text">Choose the cash location for this entry</div>
                    </div>
                    
                    <!-- Cash Entry Section (initially hidden) -->
                    <div class="cash-entry-section mt-4" id="cashEntrySection" style="display: none;">
                        <!-- Content will be populated dynamically based on location type -->
                    </div>
                `;
                
                // Auto-load cash locations for the selected store
                loadCashLocationsForSelectedStore(selectedCompanyId, selectedStoreId);
            } else {
                // All stores selected - show store dropdown
                let storeOptions = '<option value="">Select a store...</option>';
                
                // Always add HeadQuarter as the first option
                storeOptions += '<option value="null">HeadQuarter</option>';
                
                if (selectedCompany.stores && selectedCompany.stores.length > 0) {
                    selectedCompany.stores.forEach(store => {
                        storeOptions += `<option value="${store.store_id}">${escapeHtml(store.store_name)}</option>`;
                    });
                }
                
                storeSelectionContent.innerHTML = `
                    <div class="store-dropdown-section">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-shop me-1"></i>
                            Select Store:
                        </label>
                        <select class="form-select" id="modalStoreSelect" onchange="handleStoreSelection()">
                            ${storeOptions}
                        </select>
                        <div class="form-text">Choose the store for this cash ending entry</div>
                    </div>
                    
                    <!-- Cash Location Selection (initially hidden) -->
                    <div class="cash-location-section mt-3" id="cashLocationSection" style="display: none;">
                        <label class="form-label fw-semibold">
                            <i class="bi bi-geo-alt me-1"></i>
                            Select Cash Location:
                        </label>
                        <select class="form-select" id="modalCashLocationSelect" onchange="handleCashLocationSelection()">
                            <option value="">Loading...</option>
                        </select>
                        <div class="form-text">Choose the cash location for this entry</div>
                        </div>
                        
                        <!-- Cash Entry Section (initially hidden) -->
                        <div class="cash-entry-section mt-4" id="cashEntrySection" style="display: none;">
                        <!-- Content will be populated dynamically based on location type -->
                        </div>
                `;
            }
        }
        
        // Handle store selection in modal
        async function handleStoreSelection() {
            const storeSelect = document.getElementById('modalStoreSelect');
            const cashLocationSection = document.getElementById('cashLocationSection');
            const cashLocationSelect = document.getElementById('modalCashLocationSelect');
            
            const selectedStoreId = storeSelect.value;
            
            if (!selectedStoreId || selectedStoreId === '') {
                // No store selected, hide cash location section
                cashLocationSection.style.display = 'none';
                return;
            }
            
            // Show cash location section and set loading state
            cashLocationSection.style.display = 'block';
            cashLocationSelect.innerHTML = '<option value="">Loading cash locations...</option>';
            
            try {
                // Get selected company ID
                const selectedCompanyId = document.getElementById('companyFilter').value;
                
                console.log('Loading cash locations for:', {
                    companyId: selectedCompanyId,
                    storeId: selectedStoreId
                });
                
                // Load cash locations for the selected store
                const cashLocations = await loadCashLocationsForStore(selectedCompanyId, selectedStoreId);
                
                console.log('Cash locations loaded:', cashLocations);
                
                // Populate cash location dropdown
                let locationOptions = '<option value="">Select a cash location...</option>';
                
                if (cashLocations && cashLocations.length > 0) {
                    cashLocations.forEach(location => {
                        // Store both cash_location_id and location_type for later use
                        locationOptions += `<option value="${location.cash_location_id}" data-location-type="${location.location_type || ''}">${escapeHtml(location.location_name)}</option>`;
                    });
                } else {
                    locationOptions = '<option value="">No cash locations found</option>';
                }
                
                cashLocationSelect.innerHTML = locationOptions;
                
            } catch (error) {
                console.error('Error loading cash locations:', error);
                cashLocationSelect.innerHTML = '<option value="">Error loading locations</option>';
            }
        }
        
        // Load cash locations for pre-selected store (when store filter is already selected)
        async function loadCashLocationsForSelectedStore(companyId, storeId) {
            const cashLocationSelect = document.getElementById('modalCashLocationSelect');
            
            if (!cashLocationSelect) {
                console.error('Cash location select element not found');
                return;
            }
            
            try {
                console.log('Loading cash locations for pre-selected store:', {
                    companyId: companyId,
                    storeId: storeId
                });
                
                // Load cash locations for the selected store
                const cashLocations = await loadCashLocationsForStore(companyId, storeId);
                
                console.log('Cash locations loaded for pre-selected store:', cashLocations);
                
                // Populate cash location dropdown
                let locationOptions = '<option value="">Select a cash location...</option>';
                
                if (cashLocations && cashLocations.length > 0) {
                    cashLocations.forEach(location => {
                        // Store both cash_location_id and location_type for later use
                        locationOptions += `<option value="${location.cash_location_id}" data-location-type="${location.location_type || ''}">${escapeHtml(location.location_name)}</option>`;
                    });
                } else {
                    locationOptions = '<option value="">No cash locations found</option>';
                }
                
                cashLocationSelect.innerHTML = locationOptions;
                
            } catch (error) {
                console.error('Error loading cash locations for pre-selected store:', error);
                cashLocationSelect.innerHTML = '<option value="">Error loading locations</option>';
            }
        }
        
        // Handle cash location selection in modal
        async function handleCashLocationSelection() {
            const cashLocationSelect = document.getElementById('modalCashLocationSelect');
            const cashEntrySection = document.getElementById('cashEntrySection');
            
            const selectedLocationId = cashLocationSelect.value;
            const selectedOption = cashLocationSelect.selectedOptions[0];
            const locationType = selectedOption ? selectedOption.getAttribute('data-location-type') : null;
            
            if (!selectedLocationId || selectedLocationId === '') {
                // No location selected, hide cash entry section
                cashEntrySection.style.display = 'none';
                return;
            }
            
            // Show loading state
            cashEntrySection.style.display = 'block';
            cashEntrySection.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading...</div>';
            
            try {
                const selectedCompanyId = document.getElementById('companyFilter').value;
                
                console.log('Selected location:', {
                    locationId: selectedLocationId,
                    locationType: locationType,
                    companyId: selectedCompanyId
                });
                
                // Determine UI type based on location type
                if (locationType === 'cash' || locationType === 'vault') {
                    // Load currencies and denominations for cash/vault types
                    await renderCashVaultEntry(selectedCompanyId, selectedLocationId, locationType);
                } else {
                    // Default to bank-style entry for bank and other types
                    await renderBankEntry(selectedLocationId, locationType);
                }
                
            } catch (error) {
                console.error('Error handling cash location selection:', error);
                cashEntrySection.innerHTML = '<div class="alert alert-danger">Error loading entry form. Please try again.</div>';
            }
        }
        
        // Render cash/vault entry form with currency denominations
        async function renderCashVaultEntry(companyId, locationId, locationType) {
            const cashEntrySection = document.getElementById('cashEntrySection');
            
            try {
                // Load company currencies and denominations
                const currencyData = await loadCompanyCurrenciesAndDenominations(companyId);
                
                let entryHtml = `
                    <div class="cash-entry-content">
                        <h6 class="mb-3">
                            <i class="bi bi-cash-stack me-2"></i>
                            Cash Count Entry
                        </h6>
                `;
                
                // Add +/- buttons for vault type
                if (locationType === 'vault') {
                    entryHtml += `
                        <div class="vault-direction-section mb-4">
                            <label class="form-label fw-semibold">
                                <i class="bi bi-arrow-up-down me-1"></i>
                                Transaction Direction:
                            </label>
                            <div class="btn-group w-100" role="group" id="vaultDirectionButtons">
                                <input type="radio" class="btn-check" name="vaultDirection" id="vaultDebit" value="debit" autocomplete="off">
                                <label class="btn btn-outline-success" for="vaultDebit">
                                    <i class="bi bi-plus-circle me-2"></i>
                                    Add (+)
                                </label>
                                
                                <input type="radio" class="btn-check" name="vaultDirection" id="vaultCredit" value="credit" autocomplete="off">
                                <label class="btn btn-outline-danger" for="vaultCredit">
                                    <i class="bi bi-dash-circle me-2"></i>
                                    Remove (-)
                                </label>
                            </div>
                            <div class="form-text">Choose whether you are adding to or removing from the vault</div>
                        </div>
                    `;
                }
                
                if (currencyData.length === 0) {
                    entryHtml += `
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle me-2"></i>
                            No currencies configured for this company. Please set up currencies first.
                        </div>
                    `;
                } else {
                    // Group by currency
                    currencyData.forEach(currency => {
                        entryHtml += `
                            <div class="currency-section mb-4">
                                <h6 class="currency-title">
                                    <i class="bi bi-currency-exchange me-2"></i>
                                    ${escapeHtml(currency.currency_name)} (${escapeHtml(currency.currency_code)})
                                </h6>
                        `;
                        
                        if (currency.denominations.length === 0) {
                            entryHtml += `
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>
                                    Please set up denominations for ${escapeHtml(currency.currency_name)}.
                                </div>
                            `;
                        } else {
                            // Sort denominations by value (largest first)
                            const sortedDenominations = currency.denominations.sort((a, b) => parseFloat(b.value) - parseFloat(a.value));
                            
                            entryHtml += '<div class="denominations-grid">';
                            
                            sortedDenominations.forEach(denomination => {
                                const formattedValue = parseFloat(denomination.value).toLocaleString();
                                entryHtml += `
                                    <div class="denomination-row">
                                        <div class="denomination-label">
                                            ${currency.symbol}${formattedValue}
                                        </div>
                                        <div class="denomination-input">
                                            <input type="number" 
                                                   class="form-control" 
                                                   id="denom_${denomination.denomination_id}" 
                                                   min="0" 
                                                   placeholder=""
                                                   data-currency-id="${currency.currency_id}"
                                                   data-denomination-value="${denomination.value}">
                                            <div class="input-label">pieces</div>
                                        </div>
                                    </div>
                                `;
                            });
                            
                            entryHtml += '</div>'; // End denominations-grid
                        }
                        
                        entryHtml += '</div>'; // End currency-section
                    });
                }
                
                entryHtml += '</div>'; // End cash-entry-content
                
                cashEntrySection.innerHTML = entryHtml;
                
            } catch (error) {
                console.error('Error rendering cash/vault entry:', error);
                cashEntrySection.innerHTML = '<div class="alert alert-danger">Error loading cash entry form. Please try again.</div>';
            }
        }
        
        // Render bank entry form with currency dropdown and amount input
        async function renderBankEntry(locationId, locationType) {
            const cashEntrySection = document.getElementById('cashEntrySection');
            
            // Show loading state
            cashEntrySection.innerHTML = '<div class="text-center p-3"><div class="spinner-border spinner-border-sm" role="status"><span class="visually-hidden">Loading...</span></div> Loading currencies...</div>';
            
            try {
                const selectedCompanyId = document.getElementById('companyFilter').value;
                
                // Load currencies for the company
                const currencies = await loadCompanyCurrencies(selectedCompanyId);
                
                let currencyOptions = '<option value="">Select Currency...</option>';
                if (currencies && currencies.length > 0) {
                    currencies.forEach(currency => {
                        currencyOptions += `<option value="${currency.currency_id}">${currency.currency_code}</option>`;
                    });
                } else {
                    currencyOptions = '<option value="">No currencies available</option>';
                }
                
                const entryHtml = `
                    <div class="bank-entry-content">
                        <h6 class="mb-3">
                            <i class="bi bi-bank me-2"></i>
                            Amount Entry
                        </h6>
                        
                        <!-- Currency Selection -->
                        <div class="currency-selection-section mb-3">
                            <label class="form-label fw-semibold">Select Currency:</label>
                            <select class="form-select" id="bankCurrencySelect">
                                ${currencyOptions}
                            </select>
                            <div class="form-text">Choose the currency for this entry</div>
                        </div>
                        
                        <!-- Amount Input -->
                        <div class="amount-input-section">
                            <label class="form-label fw-semibold">Enter Amount:</label>
                            <div class="input-group">
                                <span class="input-group-text">₫</span>
                                <input type="number" 
                                       class="form-control" 
                                       id="bankAmount" 
                                       min="0" 
                                       placeholder="0">
                            </div>
                            <div class="form-text">Enter the total amount for this location</div>
                        </div>
                    </div>
                `;
                
                cashEntrySection.innerHTML = entryHtml;
                
            } catch (error) {
                console.error('Error loading currencies for bank entry:', error);
                cashEntrySection.innerHTML = '<div class="alert alert-danger">Error loading currencies. Please try again.</div>';
            }
        }
        
        // Load company currencies
        async function loadCompanyCurrencies(companyId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_company_currencies');
                formData.append('company_id', companyId);
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    return data.data || [];
                } else {
                    console.error('Failed to load company currencies:', data.error);
                    return [];
                }
                
            } catch (error) {
                console.error('Error loading company currencies:', error);
                return [];
            }
        }
        
        // Load company currencies and their denominations
        async function loadCompanyCurrenciesAndDenominations(companyId) {
            try {
                const formData = new FormData();
                formData.append('action', 'get_company_currencies_with_denominations');
                formData.append('company_id', companyId);
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    return data.data || [];
                } else {
                    console.error('Failed to load currencies and denominations:', data.error);
                    return [];
                }
                
            } catch (error) {
                console.error('Error loading currencies and denominations:', error);
                return [];
            }
        }
        
        // Open Cash Ending Modal
        function openCashEndingModal(locationId, locationName) {
            const modal = new bootstrap.Modal(document.getElementById('cashEndingModal'));
            
            // Store location ID for later use
            document.getElementById('cashEndingModal').setAttribute('data-location-id', locationId);
            
            // Update store selection content based on current filter
            updateStoreSelectionContent();
            
            modal.show();
        }
        
        // Open Make Error Confirmation Modal
        function openMakeErrorConfirmModal(locationId, locationName, varianceAmount, storeId, varianceValue) {
            const modal = new bootstrap.Modal(document.getElementById('makeErrorConfirmModal'));
            
            // Set location information in modal
            document.getElementById('errorLocationName').textContent = locationName || 'Unknown Location';
            document.getElementById('errorVarianceAmount').textContent = varianceAmount || '₫0';
            
            // Store location data for later use
            const modalElement = document.getElementById('makeErrorConfirmModal');
            modalElement.setAttribute('data-location-id', locationId);
            modalElement.setAttribute('data-location-name', locationName);
            modalElement.setAttribute('data-store-id', storeId);
            modalElement.setAttribute('data-variance-value', varianceValue);
            
            modal.show();
        }
        
        // Open Foreign Currency Translation Confirmation Modal
        function openForeignCurrencyTranslationModal(locationId, locationName, varianceAmount, storeId, varianceValue) {
            const modal = new bootstrap.Modal(document.getElementById('foreignCurrencyTranslationModal'));
            
            // Set location information in modal
            document.getElementById('foreignCurrencyLocationName').textContent = locationName || 'Unknown Location';
            document.getElementById('foreignCurrencyVarianceAmount').textContent = varianceAmount || '₫0';
            
            // Store location data for later use
            const modalElement = document.getElementById('foreignCurrencyTranslationModal');
            modalElement.setAttribute('data-location-id', locationId);
            modalElement.setAttribute('data-location-name', locationName);
            modalElement.setAttribute('data-store-id', storeId);
            modalElement.setAttribute('data-variance-value', varianceValue);
            
            modal.show();
        }
        
        // Load cash locations from API
        async function loadCashLocations(companyId, storeId) {
            try {
                // Show loading state for combined view
                const combinedContainer = document.getElementById('locationListCombined');
                combinedContainer.innerHTML = '<div class="text-center p-4"><div class="spinner-border" role="status"><span class="visually-hidden">Loading...</span></div></div>';
                
                // Load currency symbol first
                await loadCurrencySymbol(companyId);
                
                // Load balance and actual amounts
                const balanceAndActualData = await loadBalanceAmounts(companyId, storeId);
                
                // Make API call for cash locations
                const formData = new FormData();
                formData.append('action', 'get_cash_locations');
                formData.append('company_id', companyId);
                if (storeId && storeId !== '') {
                    formData.append('store_id', storeId);
                }
                
                const response = await fetch('../common/cash_control_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const data = await response.json();
                
                if (data.success) {
                    renderCashLocations(data.data, balanceAndActualData);
                    // Show cash control content
                    document.getElementById('cashControlContent').style.display = 'block';
                } else {
                    throw new Error(data.error || 'Failed to load cash locations');
                }
                
            } catch (error) {
                console.error('Error loading cash locations:', error);
                alert('Failed to load cash locations: ' + error.message);
            }
        }
        
        // Render cash locations in combined view
        function renderCashLocations(locations, balanceAndActualData = { balance: [], actual: [] }) {
            const combinedContainer = document.getElementById('locationListCombined');
            
            if (locations.length === 0) {
                combinedContainer.innerHTML = '<div class="text-center p-4 text-muted">No cash locations found</div>';
                return;
            }
            
            // Create balance amount lookup
            const balanceLookup = {};
            if (balanceAndActualData.balance) {
                balanceAndActualData.balance.forEach(item => {
                    balanceLookup[item.cash_location_id] = item.balance;
                });
            }
            
            // Create actual amount lookup
            const actualLookup = {};
            if (balanceAndActualData.actual) {
                balanceAndActualData.actual.forEach(item => {
                    actualLookup[item.cash_location_id] = {
                        actual_amount: item.actual_amount,
                        last_updated: item.last_updated,
                        updated_by: item.updated_by
                    };
                });
            }
            
            // Render combined location items
            let combinedHtml = '';
            locations.forEach(location => {
                combinedHtml += renderCombinedLocationItem(location, balanceLookup[location.cash_location_id] || 0, actualLookup[location.cash_location_id] || null);
            });
            combinedContainer.innerHTML = combinedHtml;
            
            // Render difference section
            renderDifferenceSection(locations, balanceAndActualData);
        }
        
        // Render combined location item with both balance and actual columns
        function renderCombinedLocationItem(location, balance = 0, actualInfo = null) {
            // Format balance amount
            const formattedBalance = currentCurrencySymbol + balance.toLocaleString('vi-VN');
            
            // Process actual data
            let actualAmount = 0;
            let originalAmount = 0;
            let currencyId = null;
            let lastUpdated = null;
            let isUpdated = false;
            let hasExchangeRate = true;
            
            if (actualInfo) {
                actualAmount = actualInfo.actual_amount;
                originalAmount = actualInfo.original_amount || 0;
                currencyId = actualInfo.currency_id;
                lastUpdated = actualInfo.last_updated;
                isUpdated = originalAmount > 0 || lastUpdated !== null;
                hasExchangeRate = actualAmount !== null;
            }
            
            // Format actual amount
            const formattedActual = hasExchangeRate ? 
                currentCurrencySymbol + (actualAmount || 0).toLocaleString('vi-VN') : 
                'Exchange rate unavailable';
            
            let description = 'Not updated today';
            if (isUpdated) {
                if (!hasExchangeRate && originalAmount > 0) {
                    description = `Original: ${originalAmount.toLocaleString('vi-VN')} (Exchange rate needed)`;
                } else if (lastUpdated) {
                    description = `Last updated: ${formatDateTimeEnglish(lastUpdated)}`;
                } else {
                    description = 'Updated today';
                }
            }
            
            const amountLabel = isUpdated ? (hasExchangeRate ? 'Actual' : 'No Rate') : 'Not Set';
            const actualCssClass = isUpdated ? 
                (hasExchangeRate ? 'actual' : 'actual no-exchange-rate') : 
                'actual not-updated';
            
            return `
                <div class="combined-location-item">
                    <!-- Balance Side -->
                    <div class="balance-side">
                        <div class="location-info">
                            <div class="location-icon ${location.css_class}">
                                <i class="bi ${location.bootstrap_icon}"></i>
                            </div>
                            <div class="location-details">
                                <h6 class="location-name">${escapeHtml(location.location_name)}</h6>
                                <p class="location-description">From Balance Sheet</p>
                            </div>
                        </div>
                        <div class="location-amount expected">
                            <span class="amount">${formattedBalance}</span>
                        </div>
                    </div>
                    
                    <!-- Actual Side -->
                    <div class="actual-side">
                        <div class="location-info">
                            <div class="location-icon ${location.css_class}">
                                <i class="bi ${location.bootstrap_icon}"></i>
                            </div>
                            <div class="location-details">
                                <h6 class="location-name">${escapeHtml(location.location_name)}</h6>
                                <p class="location-description">${description}</p>
                            </div>
                        </div>
                        <div class="location-amount ${actualCssClass}">
                            <span class="amount">${formattedActual}</span>
                            <span class="amount-label">${amountLabel}</span>
                        </div>
                    </div>
                </div>
            `;
        }
        
        // Render individual location item for balance section (kept for backward compatibility)
        function renderBalanceLocationItem(location, balance = 0) {
            // Format the balance amount with currency symbol (preserve sign)
            const formattedAmount = currentCurrencySymbol + balance.toLocaleString('vi-VN');
            
            return `
                <div class="location-item">
                    <div class="location-info">
                        <div class="location-icon ${location.css_class}">
                            <i class="bi ${location.bootstrap_icon}"></i>
                        </div>
                        <div class="location-details">
                            <h6 class="location-name">${escapeHtml(location.location_name)}</h6>
                            <p class="location-description">From Balance Sheet</p>
                        </div>
                    </div>
                    <div class="location-amount expected">
                        <span class="amount">${formattedAmount}</span>
                    </div>
                </div>
            `;
        }
        
        // Render individual location item for actual section
        function renderActualLocationItem(location, actualData = null) {
            let actualAmount = 0;
            let originalAmount = 0;
            let currencyId = null;
            let lastUpdated = null;
            let isUpdated = false;
            let hasExchangeRate = true;
            
            // Find actual data for this location
            if (actualData) {
                const actualInfo = actualData.find(item => item.cash_location_id === location.cash_location_id);
                if (actualInfo) {
                    actualAmount = actualInfo.actual_amount;
                    originalAmount = actualInfo.original_amount || 0;
                    currencyId = actualInfo.currency_id;
                    lastUpdated = actualInfo.last_updated;
                    isUpdated = originalAmount > 0 || lastUpdated !== null;
                    hasExchangeRate = actualAmount !== null;
                }
            }
            
            // Format amounts
            const formattedAmount = hasExchangeRate ? 
                currentCurrencySymbol + (actualAmount || 0).toLocaleString('vi-VN') : 
                'Exchange rate unavailable';
            
            let description = 'Not updated today';
            if (isUpdated) {
                if (!hasExchangeRate && originalAmount > 0) {
                    description = `Original: ${originalAmount.toLocaleString('vi-VN')} (Exchange rate needed)`;
                } else if (lastUpdated) {
                    description = `Last updated: ${formatDateTimeEnglish(lastUpdated)}`;
                } else {
                    description = 'Updated today';
                }
            }
            
            const amountLabel = isUpdated ? (hasExchangeRate ? 'Actual' : 'No Rate') : 'Not Set';
            const cssClass = isUpdated ? 
                (hasExchangeRate ? 'actual' : 'actual no-exchange-rate') : 
                'actual not-updated';
            
            return `
                <div class="location-item">
                    <div class="location-info">
                        <div class="location-icon ${location.css_class}">
                            <i class="bi ${location.bootstrap_icon}"></i>
                        </div>
                        <div class="location-details">
                            <h6 class="location-name">${escapeHtml(location.location_name)}</h6>
                            <p class="location-description">${description}</p>
                        </div>
                    </div>
                    <div class="location-amount ${cssClass}">
                        <span class="amount">${formattedAmount}</span>
                        <span class="amount-label">${amountLabel}</span>
                    </div>
                </div>
            `;
        }
        
        // Escape HTML to prevent XSS
        function escapeHtml(text) {
            const map = {
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            };
            return text.replace(/[&<>"']/g, function(m) { return map[m]; });
        }
        
        // Format date time in English with AM/PM
        function formatDateTimeEnglish(dateTimeString) {
            const date = new Date(dateTimeString);
            const options = {
                year: 'numeric',
                month: 'short',
                day: 'numeric',
                hour: 'numeric',
                minute: '2-digit',
                hour12: true,
                timeZone: 'Asia/Seoul'
            };
            return date.toLocaleString('en-US', options);
        }
        
        // Render difference section with dynamic data
        function renderDifferenceSection(locations, balanceAndActualData) {
            const varianceGrid = document.querySelector('.variance-grid');
            if (!varianceGrid) return;
            
            // Create balance lookup
            const balanceLookup = {};
            if (balanceAndActualData.balance) {
                balanceAndActualData.balance.forEach(item => {
                    balanceLookup[item.cash_location_id] = item.balance || 0;
                });
            }
            
            // Create actual lookup
            const actualLookup = {};
            if (balanceAndActualData.actual) {
                balanceAndActualData.actual.forEach(item => {
                    actualLookup[item.cash_location_id] = item.actual_amount || 0;
                });
            }
            
            let varianceHtml = '';
            
            locations.forEach(location => {
                const balance = balanceLookup[location.cash_location_id] || 0;
                const actual = actualLookup[location.cash_location_id] || 0;
                const variance = actual - balance;
                
                // Skip if both balance and actual are 0
                if (balance === 0 && actual === 0) return;
                
                const isPositive = variance >= 0;
                const varianceClass = isPositive ? 'positive' : 'negative';
                
                // Format amounts
                const formattedVariance = (isPositive ? '+' : '') + currentCurrencySymbol + Math.abs(variance).toLocaleString('vi-VN');
                
                varianceHtml += `
                    <div class="variance-item ${varianceClass}">
                        <div class="variance-info">
                            <span class="location-name">${escapeHtml(location.location_name)}</span>
                            <span class="variance-amount">${formattedVariance}</span>
                        </div>
                        <div class="variance-actions">
                            <button class="btn btn-sm btn-outline-danger me-2" onclick="openMakeErrorConfirmModal('${location.cash_location_id}', '${escapeHtml(location.location_name)}', '${formattedVariance}', '${location.store_id}', ${variance})" ${variance === 0 ? 'disabled' : ''}>
                                <i class="bi bi-exclamation-triangle me-1"></i>
                                Make Error
                            </button>
                            <button class="btn btn-sm btn-outline-info" onclick="openForeignCurrencyTranslationModal('${location.cash_location_id}', '${escapeHtml(location.location_name)}', '${formattedVariance}', '${location.store_id}', ${variance})" ${variance === 0 ? 'disabled' : ''}>
                                <i class="bi bi-currency-exchange me-1"></i>
                                Foreign Currency Translation
                            </button>
                        </div>
                    </div>
                `;
            });
            
            varianceGrid.innerHTML = varianceHtml;
        }
        
        // Attach event listeners to cash ending buttons (no longer needed as using onclick)
        function attachCashEndingEventListeners() {
            // No longer needed as we use inline onclick for the single button
        }
        
        // Execute Make Error journal entry
        async function executeMakeError(locationId, locationName, storeId, varianceValue) {
            try {
                console.log('Executing Make Error for:', {
                    locationId,
                    locationName,
                    storeId,
                    varianceValue
                });
                
                const isPositive = varianceValue >= 0;
                const absoluteAmount = Math.abs(varianceValue);
                
                // Account IDs
                const CASH_ACCOUNT_ID = 'd4a7a16e-45a1-47fe-992b-ff807c8673f0';
                const ERROR_ACCOUNT_ID = 'a45fac5d-010c-4b1b-92e9-ddcf8f3222bf';
                
                // Prepare journal lines based on variance sign
                const lines = [
                    {
                        "account_id": CASH_ACCOUNT_ID,
                        "description": `Make Error - ${locationName} - Cash adjustment`,
                        "debit": isPositive ? absoluteAmount : 0,
                        "credit": isPositive ? 0 : absoluteAmount,
                        "cash": {
                            "cash_location_id": locationId
                        }
                    },
                    {
                        "account_id": ERROR_ACCOUNT_ID,
                        "description": `Make Error - ${locationName} - Error adjustment`,
                        "debit": isPositive ? 0 : absoluteAmount,
                        "credit": isPositive ? absoluteAmount : 0
                    }
                ];
                
                // Prepare RPC parameters
                const rpcParams = {
                    p_base_amount: absoluteAmount,
                    p_company_id: params.company_id,
                    p_created_by: params.user_id,
                    p_description: `Make Error - ${locationName}`,
                    p_entry_date: getLocalDateTime(),
                    p_lines: lines,
                    p_counterparty_id: null,
                    p_if_cash_location_id: locationId,
                    p_store_id: storeId
                };
                
                console.log('RPC Parameters:', rpcParams);
                
                // Call Supabase RPC function
                const formData = new FormData();
                formData.append('action', 'call_rpc');
                formData.append('function_name', 'insert_journal_with_everything');
                formData.append('parameters', JSON.stringify(rpcParams));
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('Make Error executed successfully:', result);
                    showSuccessModal(`Make Error completed successfully for ${locationName}!`);
                    
                    // Refresh the data
                    const selectedCompanyId = document.getElementById('companyFilter').value;
                    const selectedStoreId = document.getElementById('storeFilter').value;
                    loadCashLocations(selectedCompanyId, selectedStoreId);
                } else {
                    throw new Error(result.error || 'Unknown error occurred');
                }
                
            } catch (error) {
                console.error('Error executing Make Error:', error);
                alert(`Failed to execute Make Error: ${error.message}`);
            }
        }
        
        // Execute Foreign Currency Translation journal entry
        async function executeForeignCurrencyTranslation(locationId, locationName, storeId, varianceValue) {
            try {
                console.log('Executing Foreign Currency Translation for:', {
                    locationId,
                    locationName,
                    storeId,
                    varianceValue
                });
                
                const isPositive = varianceValue >= 0;
                const absoluteAmount = Math.abs(varianceValue);
                
                // Account IDs
                const CASH_ACCOUNT_ID = 'd4a7a16e-45a1-47fe-992b-ff807c8673f0';
                const FOREIGN_CURRENCY_TRANSLATION_ACCOUNT_ID = '80b311db-f548-46e3-9854-67c5ff6766e8';
                
                // Prepare journal lines based on variance sign
                const lines = [
                    {
                        "account_id": CASH_ACCOUNT_ID,
                        "description": `Foreign Currency Translation - ${locationName} - Cash adjustment`,
                        "debit": isPositive ? absoluteAmount : 0,
                        "credit": isPositive ? 0 : absoluteAmount,
                        "cash": {
                            "cash_location_id": locationId
                        }
                    },
                    {
                        "account_id": FOREIGN_CURRENCY_TRANSLATION_ACCOUNT_ID,
                        "description": `Foreign Currency Translation - ${locationName} - Translation adjustment`,
                        "debit": isPositive ? 0 : absoluteAmount,
                        "credit": isPositive ? absoluteAmount : 0
                    }
                ];
                
                // Prepare RPC parameters
                const rpcParams = {
                    p_base_amount: absoluteAmount,
                    p_company_id: params.company_id,
                    p_created_by: params.user_id,
                    p_description: `Foreign Currency Translation - ${locationName}`,
                    p_entry_date: getLocalDateTime(),
                    p_lines: lines,
                    p_counterparty_id: null,
                    p_if_cash_location_id: locationId,
                    p_store_id: storeId
                };
                
                console.log('RPC Parameters:', rpcParams);
                
                // Call Supabase RPC function
                const formData = new FormData();
                formData.append('action', 'call_rpc');
                formData.append('function_name', 'insert_journal_with_everything');
                formData.append('parameters', JSON.stringify(rpcParams));
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('Foreign Currency Translation executed successfully:', result);
                    showSuccessModal(`Foreign Currency Translation completed successfully for ${locationName}!`);
                    
                    // Refresh the data
                    const selectedCompanyId = document.getElementById('companyFilter').value;
                    const selectedStoreId = document.getElementById('storeFilter').value;
                    loadCashLocations(selectedCompanyId, selectedStoreId);
                } else {
                    throw new Error(result.error || 'Unknown error occurred');
                }
                
            } catch (error) {
                console.error('Error executing Foreign Currency Translation:', error);
                alert(`Failed to execute Foreign Currency Translation: ${error.message}`);
            }
        }
        
        // Format currency
        function formatCurrency(amount) {
            return '₫' + amount.toLocaleString('vi-VN');
        }
        
        // Get local datetime in PostgreSQL format
        function getLocalDateTime() {
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');
            const seconds = String(now.getSeconds()).padStart(2, '0');
            const milliseconds = String(now.getMilliseconds()).padStart(3, '0');
            
            return `${year}-${month}-${day} ${hours}:${minutes}:${seconds}.${milliseconds}`;
        }
        
        // Save Cash Ending function
        async function saveCashEnding() {
            try {
                // Get selected cash location information
                const cashLocationSelect = document.getElementById('modalCashLocationSelect');
                if (!cashLocationSelect || !cashLocationSelect.value) {
                    alert('Please select a cash location first.');
                    return;
                }
                
                const selectedLocationId = cashLocationSelect.value;
                const selectedOption = cashLocationSelect.selectedOptions[0];
                const locationType = selectedOption ? selectedOption.getAttribute('data-location-type') : null;
                
                console.log('Save Cash Ending for:', {
                    locationId: selectedLocationId,
                    locationType: locationType
                });
                
                // Handle vault type
                if (locationType === 'vault') {
                    await saveVaultCashEnding(selectedLocationId);
                    return;
                }
                
                // Handle bank type
                if (locationType === 'bank') {
                    await saveBankCashEnding(selectedLocationId);
                    return;
                }
                
                // Only process if location type is 'cash' (existing logic)
                if (locationType !== 'cash') {
                    alert('Cash ending is currently only supported for cash, vault, and bank locations.');
                    return;
                }
                
                // Get store information
                let selectedStoreId = null;
                const storeFilter = document.getElementById('storeFilter');
                const modalStoreSelect = document.getElementById('modalStoreSelect');
                
                if (storeFilter && storeFilter.value) {
                    // Store was pre-selected in filter
                    selectedStoreId = storeFilter.value;
                } else if (modalStoreSelect && modalStoreSelect.value) {
                    // Store was selected in modal
                    selectedStoreId = modalStoreSelect.value;
                }
                
                // Handle null case for HeadQuarter
                if (selectedStoreId === 'null') {
                    selectedStoreId = null;
                }
                
                console.log('Store ID:', selectedStoreId);
                
                // Collect denomination data from cash entry form
                const currenciesData = collectCashEntryData();
                
                if (currenciesData.length === 0) {
                    alert('Please enter at least one denomination quantity.');
                    return;
                }
                
                console.log('Currencies data collected:', currenciesData);
                
                // Prepare RPC parameters
                const rpcParams = {
                    p_company_id: params.company_id,
                    p_currencies: currenciesData,
                    p_location_id: selectedLocationId,
                    p_record_date: getTodayDate(),
                    p_store_id: selectedStoreId || '',
                    p_created_by: params.user_id,
                    p_created_at: getLocalDateTime()
                };
                
                console.log('Calling insert_cashier_amount_lines with params:', rpcParams);
                
                // Show loading state
                const saveBtn = document.getElementById('saveCashEndingBtn');
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';
                saveBtn.disabled = true;
                
                // Call Supabase RPC function
                const formData = new FormData();
                formData.append('action', 'call_rpc');
                formData.append('function_name', 'insert_cashier_amount_lines');
                formData.append('parameters', JSON.stringify(rpcParams));
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const result = await response.json();
                
                if (result.success) {
                    console.log('Cash ending saved successfully:', result);
                    
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('cashEndingModal'));
                    modal.hide();
                    
                    // Show success message
                    showSuccessModal('Cash ending saved successfully!');
                    
                    // Refresh the data
                    const selectedCompanyId = document.getElementById('companyFilter').value;
                    const filterStoreId = document.getElementById('storeFilter').value;
                    loadCashLocations(selectedCompanyId, filterStoreId);
                } else {
                    throw new Error(result.error || 'Unknown error occurred');
                }
                
            } catch (error) {
                console.error('Error saving cash ending:', error);
                alert(`Failed to save cash ending: ${error.message}`);
            } finally {
                // Restore button state
                const saveBtn = document.getElementById('saveCashEndingBtn');
                saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> OK';
                saveBtn.disabled = false;
            }
        }
        
        // Save vault cash ending using vault_amount_insert RPC
        async function saveVaultCashEnding(locationId) {
            try {
                // Check vault direction selection
                const vaultDirectionRadios = document.querySelectorAll('input[name="vaultDirection"]');
                const selectedDirection = Array.from(vaultDirectionRadios).find(radio => radio.checked);
                
                if (!selectedDirection) {
                    alert('Please select transaction direction (Add or Remove) for vault entry.');
                    return;
                }
                
                const isDebit = selectedDirection.value === 'debit';
                const isCredit = selectedDirection.value === 'credit';
                
                // Collect denomination data
                const vaultAmountLines = [];
                const denominationInputs = document.querySelectorAll('[id^="denom_"]');
                
                console.log('Found denomination inputs:', denominationInputs.length);
                
                denominationInputs.forEach(input => {
                    const quantity = parseInt(input.value);
                    
                    // Only skip if value is empty, null, undefined, or NaN
                    // Allow 0 values to be sent
                    if (isNaN(quantity) || input.value === '' || input.value === null || input.value === undefined) {
                        return;
                    }
                    
                    const denominationId = input.id.replace('denom_', '');
                    console.log('Processing denomination:', {
                        inputId: input.id,
                        denominationId: denominationId,
                        quantity: quantity,
                        currencyId: input.getAttribute('data-currency-id')
                    });
                    
                    vaultAmountLines.push({
                        denomination_id: denominationId,
                        quantity: quantity
                    });
                });
                
                if (vaultAmountLines.length === 0) {
                    alert('Please enter at least one denomination value.');
                    return;
                }
                
                // Get selected store ID (from modal or filter)
                let storeId = null;
                const modalStoreSelect = document.getElementById('modalStoreSelect');
                if (modalStoreSelect && modalStoreSelect.value && modalStoreSelect.value !== '') {
                    storeId = modalStoreSelect.value === 'null' ? null : modalStoreSelect.value;
                } else {
                    const filterStoreId = document.getElementById('storeFilter').value;
                    storeId = filterStoreId && filterStoreId !== '' ? filterStoreId : null;
                }
                
                // Get company ID
                const companyId = document.getElementById('companyFilter').value;
                
                // Get first currency ID from denominations (assuming single currency for now)
                const firstInput = document.querySelector('[id^="denom_"]');
                const currencyId = firstInput ? firstInput.getAttribute('data-currency-id') : null;
                
                if (!currencyId) {
                    alert('Currency information not found. Please try again.');
                    return;
                }
                
                // Get user's local time
                const now = new Date();
                const userCreatedAt = getLocalDateTime();
                const userRecordDate = getTodayDate(); // YYYY-MM-DD format
                
                // Prepare RPC parameters
                const rpcParams = {
                    p_location_id: locationId,
                    p_company_id: companyId,
                    p_created_at: userCreatedAt,
                    p_created_by: params.user_id,
                    p_credit: isCredit,
                    p_currency_id: currencyId,
                    p_debit: isDebit,
                    p_record_date: userRecordDate,
                    p_store_id: storeId,
                    p_vault_amount_line_json: vaultAmountLines
                };
                
                console.log('Calling vault_amount_insert with params:', rpcParams);
                console.log('Vault amount lines JSON:', JSON.stringify(vaultAmountLines));
                
                // Show loading state
                const saveBtn = document.getElementById('saveCashEndingBtn');
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';
                saveBtn.disabled = true;
                
                // Call Supabase RPC function
                const formData = new FormData();
                formData.append('action', 'call_rpc');
                formData.append('function_name', 'vault_amount_insert');
                formData.append('parameters', JSON.stringify(rpcParams));
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse response as JSON:', responseText);
                    throw new Error('Invalid response format from server');
                }
                
                console.log('Parsed result:', result);
                
                if (result.success) {
                    console.log('Vault cash ending saved successfully:', result);
                    
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('cashEndingModal'));
                    modal.hide();
                    
                    // Show success message
                    showSuccessModal('Vault cash ending saved successfully!');
                    
                    // Refresh the data
                    const selectedCompanyId = document.getElementById('companyFilter').value;
                    const filterStoreId = document.getElementById('storeFilter').value;
                    loadCashLocations(selectedCompanyId, filterStoreId);
                } else {
                    throw new Error(result.error || 'Failed to save vault cash ending');
                }
                
            } catch (error) {
                console.error('Error saving vault cash ending:', error);
                alert(`Failed to save vault cash ending: ${error.message}`);
            } finally {
                // Restore button state
                const saveBtn = document.getElementById('saveCashEndingBtn');
                saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> OK';
                saveBtn.disabled = false;
            }
        }
        
        // Save bank cash ending function
        async function saveBankCashEnding(locationId) {
            try {
                // Get selected currency
                const bankCurrencySelect = document.getElementById('bankCurrencySelect');
                if (!bankCurrencySelect || !bankCurrencySelect.value) {
                    alert('Please select a currency.');
                    return;
                }
                
                // Get entered amount
                const bankAmountInput = document.getElementById('bankAmount');
                if (!bankAmountInput || !bankAmountInput.value || parseFloat(bankAmountInput.value) <= 0) {
                    alert('Please enter a valid amount.');
                    return;
                }
                
                const currencyId = bankCurrencySelect.value;
                const totalAmount = parseFloat(bankAmountInput.value);
                
                // Get selected store ID (from modal or filter)
                let storeId = null;
                const modalStoreSelect = document.getElementById('modalStoreSelect');
                if (modalStoreSelect && modalStoreSelect.value && modalStoreSelect.value !== '') {
                    storeId = modalStoreSelect.value === 'null' ? null : modalStoreSelect.value;
                } else {
                    const filterStoreId = document.getElementById('storeFilter').value;
                    storeId = filterStoreId && filterStoreId !== '' ? filterStoreId : null;
                }
                
                // Get company ID
                const companyId = document.getElementById('companyFilter').value;
                
                // Get current date and time in user's local timezone
                const now = new Date();
                const year = now.getFullYear();
                const month = String(now.getMonth() + 1).padStart(2, '0');
                const day = String(now.getDate()).padStart(2, '0');
                
                const recordDate = `${year}-${month}-${day}`; // YYYY-MM-DD format in local time
                const createdAt = getLocalDateTime(); // Use existing function for local time
                
                // Prepare RPC parameters for bank_amount_insert_v2
                const rpcParams = {
                    p_company_id: companyId,
                    p_store_id: storeId,
                    p_record_date: recordDate,
                    p_location_id: locationId,
                    p_currency_id: currencyId,
                    p_total_amount: totalAmount,
                    p_created_by: params.user_id,
                    p_created_at: createdAt
                };
                
                console.log('Calling bank_amount_insert_v2 with params:', rpcParams);
                
                // Show loading state
                const saveBtn = document.getElementById('saveCashEndingBtn');
                const originalText = saveBtn.innerHTML;
                saveBtn.innerHTML = '<i class="bi bi-hourglass-split me-1"></i> Saving...';
                saveBtn.disabled = true;
                
                // Call Supabase RPC function
                const formData = new FormData();
                formData.append('action', 'call_rpc');
                formData.append('function_name', 'bank_amount_insert_v2');
                formData.append('parameters', JSON.stringify(rpcParams));
                
                const response = await fetch('../common/supabase_api.php', {
                    method: 'POST',
                    body: formData
                });
                
                const responseText = await response.text();
                console.log('Raw response:', responseText);
                
                let result;
                try {
                    result = JSON.parse(responseText);
                } catch (parseError) {
                    console.error('Failed to parse response as JSON:', responseText);
                    throw new Error('Invalid response format from server');
                }
                
                console.log('Parsed result:', result);
                
                if (result.success) {
                    console.log('Bank cash ending saved successfully:', result);
                    
                    // Close the modal
                    const modal = bootstrap.Modal.getInstance(document.getElementById('cashEndingModal'));
                    modal.hide();
                    
                    // Show success message
                    showSuccessModal('Bank cash ending saved successfully!');
                    
                    // Refresh the data
                    const selectedCompanyId = document.getElementById('companyFilter').value;
                    const filterStoreId = document.getElementById('storeFilter').value;
                    loadCashLocations(selectedCompanyId, filterStoreId);
                } else {
                    throw new Error(result.error || 'Failed to save bank cash ending');
                }
                
            } catch (error) {
                console.error('Error saving bank cash ending:', error);
                alert(`Failed to save bank cash ending: ${error.message}`);
            } finally {
                // Restore button state
                const saveBtn = document.getElementById('saveCashEndingBtn');
                saveBtn.innerHTML = '<i class="bi bi-check-circle me-1"></i> OK';
                saveBtn.disabled = false;
            }
        }
        
        // Collect cash entry data from denomination inputs
        function collectCashEntryData() {
            const currenciesData = [];
            const currencyGroups = {};
            
            // Find all denomination inputs
            const denominationInputs = document.querySelectorAll('[id^="denom_"]');
            
            denominationInputs.forEach(input => {
                const quantity = parseInt(input.value) || 0;
                
                // Skip if quantity is 0 or empty
                if (quantity <= 0) {
                    return;
                }
                
                const currencyId = input.getAttribute('data-currency-id');
                const denominationId = input.id.replace('denom_', '');
                
                if (!currencyId || !denominationId) {
                    console.warn('Missing currency or denomination ID for input:', input.id);
                    return;
                }
                
                // Group by currency
                if (!currencyGroups[currencyId]) {
                    currencyGroups[currencyId] = {
                        currency_id: currencyId,
                        denominations: []
                    };
                }
                
                currencyGroups[currencyId].denominations.push({
                    denomination_id: denominationId,
                    quantity: quantity
                });
            });
            
            // Convert to array format
            Object.values(currencyGroups).forEach(currencyGroup => {
                if (currencyGroup.denominations.length > 0) {
                    currenciesData.push(currencyGroup);
                }
            });
            
            return currenciesData;
        }
        
        // Get today's date in YYYY-MM-DD format
        function getTodayDate() {
            const today = new Date();
            const year = today.getFullYear();
            const month = String(today.getMonth() + 1).padStart(2, '0');
            const day = String(today.getDate()).padStart(2, '0');
            return `${year}-${month}-${day}`;
        }
        
        // Input change event for variance preview and Make Error confirmation handler
        document.addEventListener('DOMContentLoaded', function() {
            // Save Cash Ending button event listener
            const saveCashEndingBtn = document.getElementById('saveCashEndingBtn');
            if (saveCashEndingBtn) {
                saveCashEndingBtn.addEventListener('click', saveCashEnding);
            }
            
            const actualAmountInput = document.getElementById('actualAmountInput');
            if (actualAmountInput) {
                actualAmountInput.addEventListener('input', function() {
                    const actualAmount = parseFloat(this.value) || 0;
                    const expectedAmount = 2500000; // Sample - replace with actual
                    
                    if (actualAmount > 0) {
                        const variance = actualAmount - expectedAmount;
                        
                        document.getElementById('previewExpected').textContent = formatCurrency(expectedAmount);
                        document.getElementById('previewActual').textContent = formatCurrency(actualAmount);
                        document.getElementById('previewVariance').textContent = formatCurrency(variance);
                        
                        const varianceEl = document.getElementById('previewVariance');
                        varianceEl.className = variance >= 0 ? 'variance-amount text-success' : 'variance-amount text-danger';
                        
                        document.getElementById('variancePreview').style.display = 'block';
                    } else {
                        document.getElementById('variancePreview').style.display = 'none';
                    }
                });
            }
            
            // Make Error confirmation button handler
            const confirmMakeErrorBtn = document.getElementById('confirmMakeErrorBtn');
            if (confirmMakeErrorBtn) {
                confirmMakeErrorBtn.addEventListener('click', async function() {
                    const modalElement = document.getElementById('makeErrorConfirmModal');
                    const locationId = modalElement.getAttribute('data-location-id');
                    const locationName = modalElement.getAttribute('data-location-name');
                    const storeId = modalElement.getAttribute('data-store-id');
                    const varianceValue = parseFloat(modalElement.getAttribute('data-variance-value'));
                    
                    console.log('Make Error confirmed for:', {
                        locationId,
                        locationName,
                        storeId,
                        varianceValue
                    });
                    
                    // Close the modal first
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    modal.hide();
                    
                    // Execute Make Error
                    await executeMakeError(locationId, locationName, storeId, varianceValue);
                });
            }
            
            // Foreign Currency Translation confirmation button handler
            const confirmForeignCurrencyTranslationBtn = document.getElementById('confirmForeignCurrencyTranslationBtn');
            if (confirmForeignCurrencyTranslationBtn) {
                confirmForeignCurrencyTranslationBtn.addEventListener('click', async function() {
                    const modalElement = document.getElementById('foreignCurrencyTranslationModal');
                    const locationId = modalElement.getAttribute('data-location-id');
                    const locationName = modalElement.getAttribute('data-location-name');
                    const storeId = modalElement.getAttribute('data-store-id');
                    const varianceValue = parseFloat(modalElement.getAttribute('data-variance-value'));
                    
                    console.log('Foreign Currency Translation confirmed for:', {
                        locationId,
                        locationName,
                        storeId,
                        varianceValue
                    });
                    
                    // Close the modal first
                    const modal = bootstrap.Modal.getInstance(modalElement);
                    modal.hide();
                    
                    // Execute Foreign Currency Translation
                    await executeForeignCurrencyTranslation(locationId, locationName, storeId, varianceValue);
                });
            }
        });
    </script>
    
    <!-- Navigation Enhancement Script -->
    <script src="../assets/js/navigation-enhancement.js"></script>
</body>
</html>
