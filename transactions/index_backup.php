<?php
/**
 * Financial Management System - Transaction History
 * Updated with modern design system
 */
require_once '../common/auth.php';
require_once '../common/functions.php';

// Authentication check
$auth = requireAuth();
$user_id = $auth['user_id'];
$company_id = $auth['company_id'];

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
    <meta name="locale" content="en-US">
    <meta http-equiv="Content-Language" content="en-US">
    <title>Transactions - Financial Management System</title>
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">
    <link href="../assets/css/modern-dropdown.css" rel="stylesheet">
    <style>
        :root {
            /* Colors from commondesign.md */
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

        /* Navigation */
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

        /* Dropdown active states */
        .navbar-nav .dropdown-toggle.active {
            color: #ffffff !important;
            background: rgba(37, 99, 235, 0.25) !important;
            border: 1px solid rgba(37, 99, 235, 0.4);
            border-radius: 8px;
            box-shadow: 0 0 15px rgba(37, 99, 235, 0.3);
        }

        /* Page Structure */
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

        /* Store Filter Pills */
        .store-filters {
            display: flex;
            gap: 0.5rem;
            flex-wrap: wrap;
            margin-bottom: 1.5rem;
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
            font-size: 0.875rem;
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
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        /* Advanced Filters */
        .advanced-filters {
            background: var(--light-bg);
            border-radius: 12px;
            padding: 1.5rem;
            border: 1px solid var(--border-color);
        }

        .filter-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1rem;
            margin-bottom: 1rem;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .filter-label {
            font-size: 0.875rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-control, .form-select {
            border: 2px solid var(--border-color);
            border-radius: 10px;
            padding: 0.75rem 1rem;
            font-size: 0.875rem;
            transition: all 0.3s ease;
        }

        .form-control:focus, .form-select:focus {
            border-color: var(--primary-color);
            box-shadow: 0 0 10px rgba(37, 99, 235, 0.2);
        }

        /* Date input placeholder styling */
        input[type="date"]::-webkit-input-placeholder {
            color: var(--text-muted);
        }
        
        input[type="date"]::-webkit-datetime-edit-text {
            color: var(--text-muted);
        }
        
        input[type="date"]::-webkit-datetime-edit-month-field {
            color: var(--text-muted);
        }
        
        input[type="date"]::-webkit-datetime-edit-day-field {
            color: var(--text-muted);
        }
        
        input[type="date"]::-webkit-datetime-edit-year-field {
            color: var(--text-muted);
        }

        /* Force English date format on date inputs */
        input[type="date"] {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            /* Force English locale for date inputs */
            -webkit-locale: "en-US";
            /* Additional browser-specific locale settings */
            --webkit-locale: "en-US";
            --moz-locale: "en-US";
            --ms-locale: "en-US";
        }
        
        /* Force English date picker in Chrome/Safari */
        input[type="date"]::-webkit-datetime-edit {
            color: var(--text-primary);
        }
        
        input[type="date"]::-webkit-datetime-edit-fields-wrapper {
            background: white;
        }
        
        input[type="date"]::-webkit-datetime-edit-text {
            color: var(--text-muted);
            padding: 0 0.25em;
        }
        
        input[type="date"]::-webkit-datetime-edit-month-field {
            color: var(--text-primary);
        }
        
        input[type="date"]::-webkit-datetime-edit-day-field {
            color: var(--text-primary);
        }
        
        input[type="date"]::-webkit-datetime-edit-year-field {
            color: var(--text-primary);
        }
        
        input[type="date"]::-webkit-calendar-picker-indicator {
            color: var(--primary-color);
            cursor: pointer;
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

        /* Custom dropdown arrow styling */
        .navbar .form-select {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
            background-repeat: no-repeat;
            background-position: right 0.75rem center;
            background-size: 18px 14px;
            padding-right: 2.8rem;
        }

        .navbar .form-select:focus {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
        }

        .navbar .form-select:hover {
            background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
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

        /* Add subtle pulsing animation to hint it's interactive */
        .navbar .form-select::before {
            content: '';
            position: absolute;
            top: -2px;
            left: -2px;
            right: -2px;
            bottom: -2px;
            border-radius: 10px;
            background: rgba(255, 255, 255, 0.1);
            z-index: -1;
            opacity: 0;
            transition: opacity 0.3s ease;
        }

        .navbar .form-select:hover::before {
            opacity: 1;
        }

        /* Action Buttons */
        .action-buttons {
            display: flex;
            gap: 1rem;
            align-items: center;
        }

        .btn-primary {
            background: var(--primary-color);
            border: 2px solid var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .btn-primary:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
        }

        .btn-outline-secondary {
            background: white;
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.75rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .btn-outline-secondary:hover {
            border-color: var(--text-secondary);
            color: var(--text-primary);
            transform: translateY(-1px);
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

        /* Results Section */
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

        /* Modern Table */
        .modern-table {
            width: 100%;
            border-collapse: collapse;
        }

        .modern-table th {
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

        .modern-table td {
            border: none;
            padding: 1rem;
            vertical-align: middle;
            border-bottom: 1px solid var(--border-color);
        }

        .modern-table tbody tr:hover {
            background-color: var(--hover-bg);
        }

        .modern-table tbody tr:last-child td {
            border-bottom: none;
        }

        /* Journal Entry Groups */
        .journal-header {
            background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
            font-weight: 600;
        }

        .journal-header td {
            border-bottom: 2px solid var(--border-color) !important;
        }

        .journal-info {
            display: flex;
            justify-content: space-between;
            align-items: center;
            width: 100%;
        }

        .journal-meta {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .journal-total {
            font-weight: 700;
            color: var(--primary-color);
        }

        /* Value Styling */
        .value-positive {
            color: var(--success-color);
            font-weight: 600;
        }

        .value-negative {
            color: var(--danger-color);
            font-weight: 600;
        }

        .value-neutral {
            color: var(--text-primary);
            font-weight: 600;
        }

        /* Badges */
        .badge-location {
            background: rgba(37, 99, 235, 0.1);
            color: var(--primary-color);
            padding: 0.25rem 0.75rem;
            border-radius: 20px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        .badge-store {
            background: rgba(100, 116, 139, 0.1);
            color: var(--text-secondary);
            padding: 0.25rem 0.75rem;
            border-radius: 6px;
            font-size: 0.875rem;
            font-weight: 500;
        }

        /* Icon Styling */
        .account-icon {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .icon-circle {
            width: 32px;
            height: 32px;
            border-radius: 8px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 0.875rem;
        }

        .icon-cash {
            background: rgba(5, 150, 105, 0.1);
            color: var(--success-color);
        }

        .icon-expense {
            background: rgba(220, 38, 38, 0.1);
            color: var(--danger-color);
        }

        .icon-debt {
            background: rgba(217, 119, 6, 0.1);
            color: var(--warning-color);
        }

        .icon-error {
            background: rgba(100, 116, 139, 0.1);
            color: var(--text-secondary);
        }

        /* Quick Select Buttons */
        .quick-select {
            display: flex;
            gap: 0.5rem;
            margin-bottom: 1rem;
        }

        .quick-btn {
            padding: 0.5rem 1rem;
            border: 2px solid var(--border-color);
            background: white;
            color: var(--text-secondary);
            border-radius: 8px;
            font-size: 0.875rem;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
        }

        .quick-btn:hover, .quick-btn.active {
            border-color: var(--primary-color);
            background: var(--primary-color);
            color: white;
        }

        /* Enhanced Pagination Controls */
        .pagination-controls-new {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: wrap;
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
            display: flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 40px;
            justify-content: center;
            font-size: 0.875rem;
        }

        .page-btn-new:hover:not(:disabled):not(.active) {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }

        .page-btn-new:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .page-btn-new.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
            cursor: default;
        }

        .page-ellipsis {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.5rem 0.25rem;
            display: flex;
            align-items: center;
        }

        /* Enhanced Pagination Controls - New Style */
        .pagination-controls-new {
            display: flex;
            align-items: center;
            gap: 0.25rem;
            flex-wrap: wrap;
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
            display: flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 40px;
            justify-content: center;
            font-size: 0.875rem;
        }

        .page-btn-new:hover:not(:disabled):not(.active) {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.2);
        }

        .page-btn-new:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .page-btn-new.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
            cursor: default;
        }

        .page-ellipsis {
            color: var(--text-muted);
            font-weight: 500;
            padding: 0.5rem 0.25rem;
            display: flex;
            align-items: center;
        }

        /* Hide original pagination controls */
        .pagination-controls {
            display: none !important;
        }

        /* Enhanced Pagination Controls */
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
            box-shadow: var(--shadow-sm);
        }

        .pagination-controls {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .page-btn {
            background: white;
            border: 2px solid var(--border-color);
            color: var(--text-secondary);
            padding: 0.5rem 0.75rem;
            border-radius: 8px;
            font-weight: 500;
            transition: all 0.3s ease;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.25rem;
            min-width: 40px;
            justify-content: center;
        }

        .page-btn:hover:not(:disabled) {
            border-color: var(--primary-color);
            color: var(--primary-color);
            transform: translateY(-1px);
        }

        .page-btn:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        .page-btn.active {
            background: var(--primary-color);
            border-color: var(--primary-color);
            color: white;
            box-shadow: 0 2px 8px rgba(37, 99, 235, 0.3);
        }

        .page-input-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            background: white;
            border: 2px solid var(--border-color);
            border-radius: 8px;
            padding: 0.25rem 0.5rem;
        }

        .page-input {
            border: none;
            outline: none;
            width: 50px;
            text-align: center;
            font-weight: 600;
            color: var(--text-primary);
        }

        .page-input-group:focus-within {
            border-color: var(--primary-color);
            box-shadow: 0 0 8px rgba(37, 99, 235, 0.2);
        }

        .page-separator {
            color: var(--text-muted);
            font-weight: 500;
        }

        /* Responsive Design */
        @media (max-width: 768px) {
            .page-container {
                padding: 1rem 0;
            }
            
            .filter-row {
                grid-template-columns: 1fr;
            }
            
            .action-buttons {
                flex-direction: column;
                align-items: stretch;
            }
            
            .results-header {
                flex-direction: column;
                align-items: flex-start;
                gap: 1rem;
            }
            
            .store-filters {
                justify-content: center;
            }
            
            .modern-table {
                font-size: 0.875rem;
            }

            .pagination-container {
                flex-direction: column;
                text-align: center;
                gap: 0.75rem;
            }

            .pagination-controls-new {
                flex-wrap: wrap;
                justify-content: center;
                gap: 0.125rem;
            }
            
            .page-btn-new {
                padding: 0.375rem 0.5rem;
                min-width: 36px;
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body lang="en-US" data-locale="en-US">
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
                        <a class="nav-link" href="../dashboard/"><i class="bi bi-speedometer2 me-1"></i>Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link active" href="../transactions/"><i class="bi bi-list-ul me-1"></i>Transactions</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="../journal-entry/"><i class="bi bi-journal-plus me-1"></i>Journal Entry</a>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-file-earmark-text me-1"></i>Financial Statements
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../balance-sheet/">Balance Sheet</a></li>
                            <li><a class="dropdown-item" href="../income-statement/">Income Statement</a></li>
                        </ul>
                    </li>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="bi bi-gear me-1"></i>Management
                        </a>
                        <ul class="dropdown-menu">
                            <li><a class="dropdown-item" href="../account-mapping/">Account Mapping</a></li>
                            <li><a class="dropdown-item" href="../debt-management/">Debt Management</a></li>
                            <li><a class="dropdown-item" href="../asset-management/">Asset Management</a></li>
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
                </div>
            </div>
        </div>
    </nav>

    <div class="container-fluid page-container">
        <!-- Page Header -->
        <div class="page-header">
            <h1 class="page-title">Transaction History</h1>
            <p class="page-subtitle">View and manage all financial transactions</p>
        </div>

        <!-- Store Filter -->
        <div class="filter-section">
            <div class="d-flex justify-content-between align-items-start mb-3">
                <h6 class="filter-label mb-0">Store Filter</h6>
                <a href="../journal-entry/?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>" class="btn btn-success">
                    <i class="bi bi-plus-circle me-2"></i>New Entry
                </a>
            </div>
            
            <div class="store-filters">
                <a href="?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>" 
                   class="store-filter-btn <?= !$filters['store_id'] ? 'active' : '' ?>">All Stores</a>
                <a href="?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>&store_id=HQ" 
                   class="store-filter-btn <?= $filters['store_id'] === 'HQ' ? 'active' : '' ?>">🏢 Headquarters</a>
                <?php foreach ($stores as $store): ?>
                <a href="?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>&store_id=<?= $store['store_id'] ?>" 
                   class="store-filter-btn <?= $filters['store_id'] == $store['store_id'] ? 'active' : '' ?>">
                    <?= h($store['store_name']) ?>
                </a>
                <?php endforeach; ?>
            </div>

            <!-- Advanced Filters -->
            <div class="advanced-filters">
                <form method="GET" action="" id="filterForm">
                    <input type="hidden" name="user_id" value="<?= $user_id ?>">
                    <input type="hidden" name="company_id" value="<?= $company_id ?>">
                    <input type="hidden" name="store_id" value="<?= $filters['store_id'] ?>">
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="filter-label">From Date</label>
                            <input type="date" class="form-control" name="date_from" value="<?= $filters['date_from'] ?>" placeholder="MM/DD/YYYY" lang="en-US">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">To Date</label>
                            <input type="date" class="form-control" name="date_to" value="<?= $filters['date_to'] ?>" placeholder="MM/DD/YYYY" lang="en-US">
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Quick Select</label>
                            <div class="quick-select">
                                <button type="button" class="quick-btn" onclick="setDateRange('today')">Today</button>
                                <button type="button" class="quick-btn" onclick="setDateRange('week')">This Week</button>
                                <button type="button" class="quick-btn" onclick="setDateRange('month')">This Month</button>
                            </div>
                        </div>
                    </div>
                    
                    <div class="filter-row">
                        <div class="filter-group">
                            <label class="filter-label">Account</label>
                            <select class="form-select" name="account_name" id="accountFilter">
                                <option value="">All Accounts</option>
                                <!-- Populated by JavaScript -->
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Created By</label>
                            <select class="form-select" name="created_by">
                                <option value="">All Users</option>
                                <!-- Populated by JavaScript -->
                            </select>
                        </div>
                        <div class="filter-group">
                            <label class="filter-label">Search</label>
                            <input type="text" class="form-control" name="keyword" placeholder="Search descriptions..." value="<?= h($filters['keyword']) ?>">
                        </div>
                    </div>

                    <div class="action-buttons">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search me-2"></i>Search
                        </button>
                        <a href="?user_id=<?= $user_id ?>&company_id=<?= $company_id ?>" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-clockwise me-2"></i>Reset
                        </a>
                        <button type="button" class="btn btn-outline-secondary" onclick="exportData()">
                            <i class="bi bi-download me-2"></i>Export
                        </button>
                    </div>
                    
                    <!-- 테스트 버튼들 -->
                    <div class="mb-3 mt-3">
                        <div class="alert alert-info" role="alert">
                            <strong>Debug Tools:</strong> Use these buttons to test the functionality
                        </div>
                        <button onclick="loadTransactions(1)" class="btn btn-warning me-2">
                            🔄 Load Transactions (Test)
                        </button>
                        <button onclick="console.log('Test button clicked')" class="btn btn-info me-2">
                            📝 Console Test
                        </button>
                        <button onclick="testAPI()" class="btn btn-success me-2">
                            🔌 Test API Direct
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Loading indicator -->
        <div id="loading" class="text-center my-5" style="display: none;">
            <div class="spinner-border text-primary" role="status">
                <span class="visually-hidden">Loading...</span>
            </div>
            <p class="mt-2">Loading transactions...</p>
        </div>

        <!-- Error alert -->
        <div id="error-alert" class="alert alert-danger" style="display: none;"></div>

        <!-- Results Section -->
        <div class="results-section" id="results-section" style="display: block;">
            <div class="results-header">
                <h5 class="results-title">Transactions</h5>
                <span class="results-count" id="results-count">Loading data...</span>
            </div>

            <table class="modern-table" id="transactions-table">
                <thead>
                    <tr>
                        <th>Date & Description</th>
                        <th>Account</th>
                        <th>Debit</th>
                        <th>Credit</th>
                        <th>Location/Party</th>
                        <th>Store</th>
                        <th>Created By</th>
                    </tr>
                </thead>
                <tbody id="transactions-tbody">
                    <tr>
                        <td colspan="7" class="text-center text-muted">Loading transactions...</td>
                    </tr>
                </tbody>
            </table>

            <!-- Enhanced Pagination Controls -->
            <div class="pagination-container" id="pagination-container" style="display: none;">
                <!-- Pagination content will be dynamically generated -->
            </div>
        </div>
    </div>

    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script src="pagination-enhanced.js"></script>
    <script>
        // Get URL parameters
        const urlParams = new URLSearchParams(window.location.search);
        const user_id = urlParams.get('user_id') || '<?= $user_id ?>';
        const company_id = urlParams.get('company_id') || '<?= $company_id ?>';
        
        // Pagination variables
        let currentPage = 1;
        let totalPages = 1;
        let totalEntries = 0;
        let currentLimit = 20; // Increased from 10 to 20
        
        // Initialize enhanced pagination
        function initializePagination() {
            console.log('Initializing enhanced pagination...');
            
            // Add page size selector
            const paginationContainer = document.getElementById('pagination-container');
            if (paginationContainer) {
                // This will be populated when updatePagination is called
            }
        }
        
        // Format currency
        function formatCurrency(amount) {
            if (!amount) return '-';
            return '₫' + new Intl.NumberFormat('vi-VN').format(Math.abs(amount));
        }

        // Format date
        function formatDate(dateString) {
            const date = new Date(dateString);
            return date.toLocaleDateString('en-US', { 
                year: 'numeric', 
                month: 'short', 
                day: 'numeric' 
            });
        }

        // Set date range with auto-reload
        function setDateRange(range) {
            const today = new Date();
            let fromDate, toDate;
            
            // Remove active class from all buttons
            document.querySelectorAll('.quick-btn').forEach(btn => btn.classList.remove('active'));
            
            switch(range) {
                case 'today':
                    fromDate = toDate = today.toISOString().split('T')[0];
                    event.target.classList.add('active');
                    break;
                case 'week':
                    const weekStart = new Date(today.setDate(today.getDate() - today.getDay()));
                    fromDate = weekStart.toISOString().split('T')[0];
                    toDate = new Date().toISOString().split('T')[0];
                    event.target.classList.add('active');
                    break;
                case 'month':
                    fromDate = new Date(today.getFullYear(), today.getMonth(), 1).toISOString().split('T')[0];
                    toDate = new Date().toISOString().split('T')[0];
                    event.target.classList.add('active');
                    break;
            }
            
            // Update form inputs
            document.querySelector('input[name="date_from"]').value = fromDate;
            document.querySelector('input[name="date_to"]').value = toDate;
            
            // Auto-reload with new date range
            console.log('Date range set:', {fromDate, toDate, range});
            currentPage = 1;
            loadTransactions(1);
        }

        // Load transactions with pagination
        async function loadTransactions(page = 1) {
            document.getElementById('loading').style.display = 'block';
            document.getElementById('results-section').style.display = 'none';
            document.getElementById('pagination-container').style.display = 'none';
            
            try {
                const formData = new FormData(document.getElementById('filterForm'));
                const params = new URLSearchParams(formData);
                
                // Add pagination parameters
                params.set('page', page);
                params.set('limit', currentLimit);
                
                const response = await fetch(`api.php?action=get_transactions&${params.toString()}`);
                const data = await response.json();
                
                if (data.success) {
                    displayTransactions(data.data);
                    updatePagination(data.pagination);
                    
                    // Update results count
                    const startEntry = ((page - 1) * currentLimit) + 1;
                    const endEntry = Math.min(page * currentLimit, data.pagination.total);
                    document.getElementById('results-count').textContent = 
                        `Showing ${startEntry}-${endEntry} of ${data.pagination.total} entries`;
                    
                    document.getElementById('results-section').style.display = 'block';
                    
                    // Show pagination if more than one page
                    if (data.pagination.totalPages > 1) {
                        document.getElementById('pagination-container').style.display = 'flex';
                    }
                } else {
                    throw new Error(data.message || 'Failed to load transactions');
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
                document.getElementById('error-alert').textContent = 'Failed to load transactions. Please try again.';
                document.getElementById('error-alert').style.display = 'block';
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }

        // Display transactions
        function displayTransactions(journalEntries) {
            const tbody = document.querySelector('#transactions-table tbody');
            tbody.innerHTML = '';
            
            if (journalEntries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No transactions found.</td></tr>';
                return;
            }
            
            journalEntries.forEach(entry => {
                // Journal Entry 헤더 행
                const headerRow = `
                    <tr class="journal-header">
                        <td colspan="7">
                            <div class="journal-info">
                                <div class="journal-meta">
                                    <strong>${formatDate(entry.entry_date)}</strong>
                                    ${entry.description ? `<span class="text-muted">${entry.description}</span>` : ''}
                                    <i class="bi bi-person-circle text-muted"></i>
                                    <span class="text-muted">${entry.created_by}</span>
                                </div>
                                <span class="journal-total">Total: ${formatCurrency(entry.total_debit)}</span>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += headerRow;
                
                // 각 라인 표시
                if (entry.lines && entry.lines.length > 0) {
                    entry.lines.forEach((line, index) => {
                        let accountIcon = 'icon-cash';
                        let iconType = 'bi-cash-coin';
                        
                        // Account type에 따른 아이콘 설정
                        if (line.account_name === 'Cash') {
                            accountIcon = 'icon-cash';
                            iconType = line.debit > 0 ? 'bi-bank' : 'bi-cash-coin';
                        } else if (line.account_name.includes('expense') || line.account_name.includes('Expense')) {
                            accountIcon = 'icon-expense';
                            iconType = 'bi-receipt';
                        } else if (line.account_name.includes('Payable') || line.account_name.includes('Receivable')) {
                            accountIcon = 'icon-debt';
                            iconType = 'bi-credit-card';
                        } else if (line.account_name === 'Error') {
                            accountIcon = 'icon-error';
                            iconType = 'bi-exclamation-triangle';
                        }
                        
                        // Location/Party 정보 결정
                        let locationPartyInfo = '';
                        if (line.account_name === 'Cash' && line.cash_location_name) {
                            locationPartyInfo = `<span class="badge-location"><i class="bi bi-geo-alt"></i> ${line.cash_location_name}</span>`;
                        } else if ((line.account_name === 'Notes Payable' || 
                                  line.account_name === 'Accounts Payable' || 
                                  line.account_name === 'Notes Receivable' ||
                                  line.account_name === 'Accounts Receivable') && 
                                 entry.counterparty_name) {
                            locationPartyInfo = `<span class="badge-location"><i class="bi bi-building"></i> ${entry.counterparty_name}</span>`;
                        }
                        
                        const lineRow = `
                            <tr class="journal-line">
                                <td>
                                    <div class="fw-bold">${formatDate(entry.entry_date)}</div>
                                    <div class="text-muted small">${line.description || ''}</div>
                                </td>
                                <td>
                                    <div class="account-icon">
                                        <div class="icon-circle ${accountIcon}">
                                            <i class="bi ${iconType}"></i>
                                        </div>
                                        ${line.account_name || ''}
                                    </div>
                                </td>
                                <td>${line.debit > 0 ? `<span class="value-negative">${formatCurrency(line.debit)}</span>` : '-'}</td>
                                <td>${line.credit > 0 ? `<span class="value-positive">${formatCurrency(line.credit)}</span>` : '-'}</td>
                                <td>${locationPartyInfo}</td>
                                <td><span class="badge-store">${line.store_name || entry.company_name || ''}</span></td>
                                <td>${entry.created_by}</td>
                            </tr>
                        `;
                        tbody.innerHTML += lineRow;
                    });
                }
            });
        }

        // Load accounts and users for filters
        async function loadFilterOptions() {
            try {
                // Load accounts
                const accountResponse = await fetch(`https://atkekzwgukdvucqntryo.supabase.co/rest/v1/accounts?order=account_name`, {
                    headers: {
                        'apikey': 'eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8',
                        'Authorization': 'Bearer eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8'
                    }
                });
                
                if (accountResponse.ok) {
                    const accounts = await accountResponse.json();
                    const select = document.getElementById('accountFilter');
                    const currentValue = '<?= $filters['account_name'] ?>';
                    
                    // Add common accounts first
                    const commonAccounts = ['Cash', 'Notes Payable', 'Notes Receivable', 'Accounts Payable', 'Accounts Receivable'];
                    commonAccounts.forEach(name => {
                        const option = document.createElement('option');
                        option.value = name;
                        option.textContent = name;
                        if (currentValue === name) option.selected = true;
                        select.appendChild(option);
                    });
                    
                    // Add separator
                    const separator = document.createElement('option');
                    separator.disabled = true;
                    separator.textContent = '──────────';
                    select.appendChild(separator);
                    
                    // Add all accounts
                    accounts.forEach(account => {
                        if (!commonAccounts.includes(account.account_name)) {
                            const option = document.createElement('option');
                            option.value = account.account_name;
                            option.textContent = account.account_name;
                            if (currentValue === account.account_name) option.selected = true;
                            select.appendChild(option);
                        }
                    });
                }
                
                // Add account filter change event
                const accountSelect = document.getElementById('accountFilter');
                if (accountSelect) {
                    accountSelect.addEventListener('change', function() {
                        console.log('Account filter changed:', this.value);
                        currentPage = 1;
                        loadTransactions(1);
                    });
                }
            } catch (error) {
                console.error('Failed to load filter options:', error);
            }
        }

        // Export data function
        async function exportData() {
            try {
                console.log('Starting export...');
                
                // Show loading state
                const exportBtn = event.target;
                const originalText = exportBtn.innerHTML;
                exportBtn.innerHTML = '<i class="bi bi-hourglass-split me-2"></i>Exporting...';
                exportBtn.disabled = true;
                
                // Get current filter parameters
                const formData = new FormData(document.getElementById('filterForm'));
                const params = new URLSearchParams(formData);
                
                // Remove pagination for export (get all data)
                params.delete('page');
                params.delete('limit');
                params.set('export', 'true');
                
                // Fetch all data for export
                const response = await fetch(`api.php?action=get_transactions&${params.toString()}`);
                const data = await response.json();
                
                if (data.success && data.data.length > 0) {
                    // Create CSV content
                    const csvContent = createCSVContent(data.data);
                    
                    // Download CSV file
                    const blob = new Blob([csvContent], { type: 'text/csv;charset=utf-8;' });
                    const link = document.createElement('a');
                    const url = URL.createObjectURL(blob);
                    link.setAttribute('href', url);
                    
                    // Create filename with current date
                    const now = new Date();
                    const dateStr = now.toISOString().split('T')[0];
                    link.setAttribute('download', `transactions_${dateStr}.csv`);
                    
                    // Trigger download
                    link.style.visibility = 'hidden';
                    document.body.appendChild(link);
                    link.click();
                    document.body.removeChild(link);
                    
                    console.log(`Exported ${data.data.length} transactions to CSV`);
                } else {
                    alert('No data to export with current filters.');
                }
                
            } catch (error) {
                console.error('Export failed:', error);
                alert('Export failed. Please try again.');
            } finally {
                // Restore button state
                const exportBtn = event.target;
                exportBtn.innerHTML = originalText;
                exportBtn.disabled = false;
            }
        }
        
        // Create CSV content from transaction data
        function createCSVContent(transactions) {
            const headers = [
                'Date',
                'Journal ID',
                'Description',
                'Account',
                'Debit',
                'Credit',
                'Store',
                'Location/Party',
                'Created By',
                'Entry Description'
            ];
            
            let csvContent = headers.join(',') + '\n';
            
            transactions.forEach(entry => {
                if (entry.lines && entry.lines.length > 0) {
                    entry.lines.forEach(line => {
                        const row = [
                            `"${entry.entry_date}"`,
                            `"${entry.journal_id}"`,
                            `"${(line.description || '').replace(/"/g, '""')}"`,
                            `"${(line.account_name || '').replace(/"/g, '""')}"`,
                            `"${line.debit || 0}"`,
                            `"${line.credit || 0}"`,
                            `"${(line.store_name || '').replace(/"/g, '""')}"`,
                            `"${(line.cash_location_name || line.counterparty_name || '').replace(/"/g, '""')}"`,
                            `"${(entry.created_by || '').replace(/"/g, '""')}"`,
                            `"${(entry.description || '').replace(/"/g, '""')}"`
                        ];
                        csvContent += row.join(',') + '\n';
                    });
                }
            });
            
            return csvContent;
        }

        // Change company
        function changeCompany(companyId) {
            // Force reset dropdown style before navigation
            const dropdown = document.querySelector('.navbar .form-select');
            if (dropdown) {
                dropdown.blur();
                dropdown.style.cssText = '';
            }
            window.location.href = `../transactions/?user_id=${user_id}&company_id=${companyId}`;
        }

        // Basic JavaScript test and initialization
        console.log('JavaScript loading...');
        
        // Test if page elements exist
        setTimeout(function() {
            console.log('Testing page elements...');
            console.log('results-section:', document.getElementById('results-section'));
            console.log('transactions-table:', document.getElementById('transactions-table'));
            console.log('filterForm:', document.getElementById('filterForm'));
        }, 100);
        
        // Immediate execution test
        setTimeout(function() {
            console.log('Immediate execution test...');
            if (typeof loadTransactions === 'function') {
                loadTransactions(1);
            }
        }, 200);
        
        // 스토어 필터 기능 구현
        function initializeStoreFilters() {
            console.log('Initializing store filters...');
            
            // 모든 스토어 필터 버튼에 이벤트 리스너 추가
            document.querySelectorAll('.store-filter-btn').forEach(btn => {
                btn.addEventListener('click', function(e) {
                    e.preventDefault();
                    
                    // 현재 활성화된 필터 제거
                    document.querySelectorAll('.store-filter-btn').forEach(b => b.classList.remove('active'));
                    
                    // 클릭된 버튼 활성화
                    this.classList.add('active');
                    
                    // URL에서 store_id 추출
                    const url = new URL(this.href);
                    const storeId = url.searchParams.get('store_id') || null;
                    
                    console.log('Store filter clicked:', storeId);
                    
                    // 폼의 store_id hidden 필드 업데이트
                    const storeIdInput = document.querySelector('input[name="store_id"]');
                    if (storeIdInput) {
                        storeIdInput.value = storeId || '';
                    }
                    
                    // 현재 페이지를 1로 리셋하고 새로운 필터로 로드
                    currentPage = 1;
                    loadTransactions(1, {store_id: storeId});
                });
            });
        }
        
        // Load transactions function with filter support
        async function loadTransactions(page = 1, additionalFilters = {}) {
            console.log('Loading transactions with filters...', {page, additionalFilters});
            document.getElementById('loading').style.display = 'block';
            document.getElementById('results-section').style.display = 'none';
            document.getElementById('pagination-container').style.display = 'none';
            
            try {
                // 폼 데이터 수집
                const formData = new FormData(document.getElementById('filterForm'));
                const params = new URLSearchParams(formData);
                
                // 추가 필터 적용
                Object.keys(additionalFilters).forEach(key => {
                    if (additionalFilters[key] !== null && additionalFilters[key] !== undefined) {
                        params.set(key, additionalFilters[key]);
                    }
                });
                
                // 페이지네이션 파라미터 추가
                params.set('page', page);
                params.set('limit', currentLimit);
                
                console.log('Request params:', params.toString());
                
                const response = await fetch(`api.php?action=get_transactions&${params.toString()}`);
                const data = await response.json();
                
                if (data.success) {
                    console.log('API Success:', data.data.length, 'transactions found');
                    displayTransactions(data.data);
                    updatePagination(data.pagination);
                    
                    // Update results count
                    const startEntry = ((page - 1) * currentLimit) + 1;
                    const endEntry = Math.min(page * currentLimit, data.pagination.total);
                    document.getElementById('results-count').textContent = 
                        `Showing ${startEntry}-${endEntry} of ${data.pagination.total} entries`;
                    
                    document.getElementById('results-section').style.display = 'block';
                    
                    // Show pagination if more than one page
                    if (data.pagination.totalPages > 1) {
                        document.getElementById('pagination-container').style.display = 'flex';
                    }
                } else {
                    throw new Error(data.message || 'Failed to load transactions');
                }
            } catch (error) {
                console.error('Error loading transactions:', error);
                document.getElementById('error-alert').textContent = 'Failed to load transactions. Please try again.';
                document.getElementById('error-alert').style.display = 'block';
            } finally {
                document.getElementById('loading').style.display = 'none';
            }
        }
        
        // Display transactions function - proper journal entry format
        function displayTransactions(journalEntries) {
            const tbody = document.querySelector('#transactions-table tbody');
            tbody.innerHTML = '';
            
            if (journalEntries.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted">No transactions found.</td></tr>';
                return;
            }
            
            journalEntries.forEach(entry => {
                // Journal Entry 헤더 행
                const headerRow = `
                    <tr class="journal-header">
                        <td colspan="7">
                            <div class="journal-info">
                                <div class="journal-meta">
                                    <strong>${formatDate(entry.entry_date)}</strong>
                                    ${entry.description ? `<span class="text-muted">- ${entry.description}</span>` : ''}
                                    <i class="bi bi-person-circle text-muted"></i>
                                    <span class="text-muted">${entry.created_by}</span>
                                </div>
                                <span class="journal-total">Total: ${formatCurrency(entry.total_debit)}</span>
                            </div>
                        </td>
                    </tr>
                `;
                tbody.innerHTML += headerRow;
                
                // 각 라인 표시
                if (entry.lines && entry.lines.length > 0) {
                    entry.lines.forEach((line, index) => {
                        let accountIcon = 'icon-cash';
                        let iconType = 'bi-cash-coin';
                        
                        // Account type에 따른 아이콘 설정
                        if (line.account_name === 'Cash') {
                            accountIcon = 'icon-cash';
                            iconType = 'bi-cash-coin';
                        } else if (line.account_name.includes('expense') || line.account_name.includes('Expense')) {
                            accountIcon = 'icon-expense';
                            iconType = 'bi-receipt';
                        } else if (line.account_name.includes('Payable') || line.account_name.includes('Receivable')) {
                            accountIcon = 'icon-debt';
                            iconType = 'bi-credit-card';
                        } else if (line.account_name.includes('revenue') || line.account_name.includes('Revenue')) {
                            accountIcon = 'icon-cash';
                            iconType = 'bi-graph-up';
                        } else if (line.account_name === 'Error') {
                            accountIcon = 'icon-error';
                            iconType = 'bi-exclamation-triangle';
                        }
                        
                        // Location/Party 정보 결정
                        let locationPartyInfo = '';
                        if (line.account_name === 'Cash' && line.cash_location_name) {
                            locationPartyInfo = `<span class="badge-location"><i class="bi bi-geo-alt"></i> ${line.cash_location_name}</span>`;
                        } else if ((line.account_name === 'Notes Payable' || 
                                  line.account_name === 'Accounts Payable' || 
                                  line.account_name === 'Notes Receivable' ||
                                  line.account_name === 'Accounts Receivable') && 
                                 entry.counterparty_name) {
                            locationPartyInfo = `<span class="badge-location"><i class="bi bi-building"></i> ${entry.counterparty_name}</span>`;
                        }
                        
                        // Debit/Credit 표시 - 올바른 로직
                        let debitDisplay = '-';
                        let creditDisplay = '-';
                        
                        if (line.debit && line.debit > 0) {
                            debitDisplay = `<span class="value-negative">${formatCurrency(line.debit)}</span>`;
                        }
                        if (line.credit && line.credit > 0) {
                            creditDisplay = `<span class="value-positive">${formatCurrency(line.credit)}</span>`;
                        }
                        
                        const lineRow = `
                            <tr class="journal-line">
                                <td>
                                    <div class="fw-bold">${formatDate(entry.entry_date)}</div>
                                    <div class="text-muted small">${line.description || ''}</div>
                                </td>
                                <td>
                                    <div class="account-icon">
                                        <div class="icon-circle ${accountIcon}">
                                            <i class="bi ${iconType}"></i>
                                        </div>
                                        ${line.account_name || ''}
                                    </div>
                                </td>
                                <td>${debitDisplay}</td>
                                <td>${creditDisplay}</td>
                                <td>${locationPartyInfo}</td>
                                <td><span class="badge-store">${line.store_name || entry.company_name || ''}</span></td>
                                <td>${entry.created_by}</td>
                            </tr>
                        `;
                        tbody.innerHTML += lineRow;
                    });
                }
            });
        }
        
        // URL 파라미터 기반으로 현재 활성 필터 설정
        function setActiveFilterFromURL() {
            const urlParams = new URLSearchParams(window.location.search);
            const currentStoreId = urlParams.get('store_id');
            
            // 모든 필터 버튼에서 active 클래스 제거
            document.querySelectorAll('.store-filter-btn').forEach(btn => {
                btn.classList.remove('active');
                
                // 현재 URL의 store_id와 매치되는 버튼 찾기
                const url = new URL(btn.href);
                const btnStoreId = url.searchParams.get('store_id');
                
                if ((!currentStoreId && !btnStoreId) || (currentStoreId && currentStoreId === btnStoreId)) {
                    btn.classList.add('active');
                }
            });
        }
        
        // Initialize when page loads
        function initializePage() {
            console.log('Initializing page with all features...');
            
            // 스토어 필터 초기화
            initializeStoreFilters();
            
            // 검색 및 필터 기능 초기화
            initializeSearchAndFilters();
            
            // 페이지네이션 초기화
            initializePagination();
            
            // URL 기반 활성 필터 설정
            setActiveFilterFromURL();
            
            // 필터 옵션 로드 (계정, 사용자 등)
            loadFilterOptions();
            
            // 초기 데이터 로드 - 강제 실행
            console.log('Starting initial data load...');
            setTimeout(function() {
                loadTransactions(1);
            }, 100);
        }
        
        // Multiple event listeners to ensure execution
        if (document.readyState === 'loading') {
            document.addEventListener('DOMContentLoaded', initializePage);
        } else {
            initializePage();
        }
        
        window.addEventListener('load', function() {
            console.log('Window loaded');
            // Backup execution - 강제 실행
            setTimeout(function() {
                console.log('Backup execution triggered');
                loadTransactions(1);
            }, 500);
            
            // Multiple backup attempts
            setTimeout(function() {
                if (!document.getElementById('results-section').style.display || document.getElementById('results-section').style.display === 'none') {
                    console.log('Second backup execution triggered');
                    loadTransactions(1);
                }
            }, 1500);
            
            // Set current month as default
            if (!document.querySelector('input[name="date_from"]').value) {
                setDateRange('month');
            }
            
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
                
                // Reset styles on escape key
                document.addEventListener('keydown', function(e) {
                    if (e.key === 'Escape') {
                        companyDropdown.style.cssText = '';
                        companyDropdown.blur();
                    }
                });
            }
        });
        
        // Final backup - ensure data loads
        setTimeout(function() {
            console.log('Final backup execution...');
            if (typeof loadTransactions === 'function') {
                loadTransactions(1);
            }
        }, 2000);
        
        // Force immediate execution after page load
        document.addEventListener('DOMContentLoaded', function() {
            console.log('DOM Content Loaded - forcing execution');
            setTimeout(function() {
                if (typeof loadTransactions === 'function') {
                    console.log('Forcing loadTransactions execution');
                    loadTransactions(1);
                } else {
                    console.log('loadTransactions function not found');
                }
            }, 100);
        });
        
        // Additional forced execution
        // Test API function
        async function testAPI() {
            console.log('Testing API directly...');
            try {
                const response = await fetch('api.php?action=get_transactions&user_id=0d2e61ad-e230-454e-8b90-efbe1c1a9268&company_id=ebd66ba7-fde7-4332-b6b5-0d8a7f615497&page=1&limit=10');
                const data = await response.json();
                console.log('API Response:', data);
                
                if (data.success) {
                    console.log('API Test SUCCESS - ' + data.data.length + ' transactions received');
                    
                    // Display the transactions using our proper function
                    displayTransactions(data.data);
                    
                    // Update results count
                    document.getElementById('results-count').textContent = 
                        `Showing ${data.data.length} of ${data.pagination.total} entries`;
                    
                    alert('API Test SUCCESS - ' + data.data.length + ' transactions received and displayed');
                } else {
                    console.log('API Test FAILED:', data.message);
                    alert('API Test FAILED: ' + data.message);
                }
            } catch (error) {
                console.error('API Test ERROR:', error);
                alert('API Test ERROR: ' + error.message);
            }
        }
        
        // 긴급 해결 방법 - 강제 실행
        setTimeout(function() {
            console.log('Emergency fix - Loading transactions directly');
            fetch('api.php?action=get_transactions&user_id=0d2e61ad-e230-454e-8b90-efbe1c1a9268&company_id=ebd66ba7-fde7-4332-b6b5-0d8a7f615497&store_id=6b436a0b-4ae6-49a0-86eb-3dc3bc71fa67&page=1&limit=10')
            .then(response => response.json())
            .then(data => {
                console.log('Emergency fix - API response:', data);
                if (data.success) {
                    const tbody = document.querySelector('#transactions-table tbody');
                    if (tbody) {
                        tbody.innerHTML = '';
                        data.data.forEach(entry => {
                            const debitAmount = entry.total_debit > 0 ? `₫${entry.total_debit.toLocaleString()}` : '-';
                            const creditAmount = entry.total_credit > 0 ? `₫${entry.total_credit.toLocaleString()}` : '-';
                            const storeName = entry.lines[0]?.store_name || 'N/A';
                            const description = entry.description || 'No description';
                            
                            const row = `<tr>
                                <td>
                                    <div class="fw-bold">${entry.entry_date}</div>
                                    <div class="text-muted small">${description}</div>
                                </td>
                                <td>Cash</td>
                                <td class="text-end">${debitAmount}</td>
                                <td class="text-end">${creditAmount}</td>
                                <td>-</td>
                                <td><span class="badge bg-light text-dark">${storeName}</span></td>
                                <td>${entry.created_by}</td>
                            </tr>`;
                            tbody.innerHTML += row;
                        });
                        
                        // 결과 카운트 업데이트
                        const resultsCount = document.getElementById('results-count');
                        if (resultsCount) {
                            resultsCount.textContent = `Showing ${data.data.length} transactions`;
                        }
                        
                        // 로딩 메시지 숨기기
                        const loadingMessage = document.querySelector('.results-section .text-muted');
                        if (loadingMessage) {
                            loadingMessage.textContent = `Total ${data.data.length} transactions found`;
                        }
                        
                        console.log('Emergency fix - SUCCESS! Data loaded into table');
                    } else {
                        console.error('Emergency fix - Table body not found');
                    }
                } else {
                    console.error('Emergency fix - API failed:', data.message);
                }
            })
            .catch(error => {
                console.error('Emergency fix - Error:', error);
            });
        }, 1000);
        
        window.onload = function() {
            console.log('Window onload - forcing execution');
            setTimeout(function() {
                if (typeof loadTransactions === 'function') {
                    console.log('Window onload - executing loadTransactions');
                    loadTransactions(1);
                }
            }, 200);
        };

        // updatePagination and generatePaginationHTML functions are now loaded from pagination-enhanced.js
        
        // Go to specific page
        function goToPage(page) {
            if (page < 1 || page > totalPages || page === currentPage) return;
            loadTransactions(page);
        }
        
        // Initialize search and filter functionality
        function initializeSearchAndFilters() {
            // Handle form submission (search)
            const filterForm = document.getElementById('filterForm');
            if (filterForm) {
                filterForm.addEventListener('submit', function(e) {
                    e.preventDefault();
                    console.log('Search form submitted');
                    currentPage = 1; // Reset to first page when filtering
                    loadTransactions(1);
                });
            }
            
            // Handle real-time search on keyword input
            const keywordInput = document.querySelector('input[name="keyword"]');
            if (keywordInput) {
                let searchTimeout;
                keywordInput.addEventListener('input', function() {
                    clearTimeout(searchTimeout);
                    searchTimeout = setTimeout(() => {
                        console.log('Keyword search:', this.value);
                        currentPage = 1;
                        loadTransactions(1);
                    }, 500); // 500ms delay for better UX
                });
            }
            
            // Handle date input changes
            const dateFromInput = document.querySelector('input[name="date_from"]');
            const dateToInput = document.querySelector('input[name="date_to"]');
            
            if (dateFromInput) {
                dateFromInput.addEventListener('change', function() {
                    console.log('Date from changed:', this.value);
                    // Remove active class from quick buttons when manually changing dates
                    document.querySelectorAll('.quick-btn').forEach(btn => btn.classList.remove('active'));
                    currentPage = 1;
                    loadTransactions(1);
                });
            }
            
            if (dateToInput) {
                dateToInput.addEventListener('change', function() {
                    console.log('Date to changed:', this.value);
                    // Remove active class from quick buttons when manually changing dates
                    document.querySelectorAll('.quick-btn').forEach(btn => btn.classList.remove('active'));
                    currentPage = 1;
                    loadTransactions(1);
                });
            }
            
            // Handle created_by filter
            const createdBySelect = document.querySelector('select[name="created_by"]');
            if (createdBySelect) {
                createdBySelect.addEventListener('change', function() {
                    console.log('Created by filter changed:', this.value);
                    currentPage = 1;
                    loadTransactions(1);
                });
            }
        }
    </script>
    
    <!-- Simple Direct Loader -->
    <script>
        // Remove simple loader - using the main displayTransactions function instead
    </script>
</body>
</html>