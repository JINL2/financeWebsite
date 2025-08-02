# Dashboard Design Specification

## 📋 **Overall Layout Structure**

### **Page Layout Hierarchy**
```
1. Navigation Bar (Fixed Top) - with Active Page Indicator
2. Page Header Section
3. Filter Section (Store + Month)
4. Balance Sheet Overview (3-column grid)
5. Monthly Performance (2-column grid)
6. Net Income Highlight (Centered, Large)
7. Quick Actions (4-button grid)
8. Recent Journal Entries (Full-width table)
```

### **Container Structure**
- **Main Container**: `container-fluid` with 2rem top/bottom padding
- **Grid System**: Bootstrap 5 responsive grid
- **Spacing**: Consistent 2rem margin between major sections

## 🧭 **Navigation Bar with Active Page Indicator**

### **Active Page Design Pattern**
```css
/* Navigation Bar - Dark gradient with active states */
.navbar {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    box-shadow: var(--shadow-md);
    padding: 1rem 0;
}

/* Navigation Links */
.navbar-nav .nav-link {
    color: rgba(255, 255, 255, 0.8);
    font-weight: 500;
    padding: 0.75rem 1rem;
    margin: 0 0.25rem;
    border-radius: 8px;
    transition: all 0.3s ease;
    position: relative;
}

/* Default hover state */
.navbar-nav .nav-link:hover {
    color: rgba(255, 255, 255, 1);
    background-color: rgba(255, 255, 255, 0.1);
    transform: translateY(-1px);
}

/* Active page indicator */
.navbar-nav .nav-link.active {
    color: #ffffff;
    background: rgba(37, 99, 235, 0.2);
    border: 1px solid rgba(37, 99, 235, 0.3);
    box-shadow: 0 0 20px rgba(37, 99, 235, 0.2);
}

/* Active page indicator with bottom accent */
.navbar-nav .nav-link.active::after {
    content: '';
    position: absolute;
    bottom: -8px;
    left: 50%;
    transform: translateX(-50%);
    width: 80%;
    height: 3px;
    background: var(--primary-color);
    border-radius: 2px;
}

/* Brand logo area */
.navbar-brand {
    color: #ffffff !important;
    font-weight: 700;
    font-size: 1.2rem;
}

.navbar-brand:hover {
    color: #e2e8f0 !important;
}
```

### **Navigation HTML Structure**
```html
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <!-- Brand/Logo -->
        <a class="navbar-brand" href="dashboard/">
            <i class="bi bi-graph-up me-2"></i>
            Financial Management
        </a>
        
        <!-- Navigation Links -->
        <div class="navbar-nav">
            <!-- Dashboard - ACTIVE when on this page -->
            <a class="nav-link active" href="dashboard/">
                <i class="bi bi-speedometer2 me-1"></i>
                Dashboard
            </a>
            
            <!-- Other navigation items -->
            <a class="nav-link" href="transactions/">
                <i class="bi bi-list-ul me-1"></i>
                Transactions
            </a>
            
            <a class="nav-link" href="journal-entry/">
                <i class="bi bi-plus-circle me-1"></i>
                Journal Entry
            </a>
            
            <a class="nav-link" href="balance-sheet/">
                <i class="bi bi-bar-chart me-1"></i>
                Financial Statements
            </a>
            
            <!-- Dropdown for Management -->
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-gear me-1"></i>
                    Management
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="accounts/">Account Management</a></li>
                    <li><a class="dropdown-item" href="counterparties/">Counterparties</a></li>
                    <li><a class="dropdown-item" href="assets/">Asset Management</a></li>
                </ul>
            </div>
        </div>
        
        <!-- User Section -->
        <div class="navbar-nav ms-auto">
            <div class="nav-item dropdown">
                <a class="nav-link dropdown-toggle" href="#" role="button" data-bs-toggle="dropdown">
                    <i class="bi bi-person-circle me-1"></i>
                    User
                </a>
                <ul class="dropdown-menu">
                    <li><a class="dropdown-item" href="#">Profile Settings</a></li>
                    <li><a class="dropdown-item" href="#">Logout</a></li>
                </ul>
            </div>
        </div>
    </div>
</nav>
```

### **🚨 CRITICAL FIX: Navigation Company Dropdown**

**Problem Identified**: Multiple dropdown arrows appearing in company selector

**Root Cause**: 
- `modern-dropdown.css` conflicts with Bootstrap's default form-select styling
- Browser shows multiple arrows when CSS specificity is incorrect
- Standard form-select styles don't work properly in dark navbar

**Solution Applied**: 
1. **Remove modern-dropdown.css**: Don't include it in dashboard pages
2. **Add Custom CSS**: Use specific navbar dropdown styling (see commondesign.md)
3. **Override Bootstrap**: Use `!important` to ensure proper styling

**Required CSS** (Must be included in every dashboard page):
```css
/* CRITICAL: Navigation dropdown fix */
.navbar .form-select {
    /* This CSS must be included exactly as shown in commondesign.md */
    border: 2px solid rgba(255, 255, 255, 0.3) !important;
    background: rgba(255, 255, 255, 0.1) !important;
    color: white !important;
    /* ... full CSS from commondesign.md ... */
}
```

**HTML Structure**:
```html
<div class="company-dropdown-container">
    <span class="company-dropdown-label">
        <i class="bi bi-building me-1"></i>Company:
    </span>
    <select class="form-select form-select-sm d-inline-block w-auto" 
            onchange="changeCompany(this.value)" 
            title="Select Company">
        <?php foreach ($companies as $comp): ?>
        <option value="<?= $comp['company_id'] ?>" 
                <?= $comp['company_id'] == $company_id ? 'selected' : '' ?>>
            <?= h($comp['company_name']) ?>
        </option>
        <?php endforeach; ?>
    </select>
</div>
```

**Testing Checklist**:
- [ ] Only one dropdown arrow visible
- [ ] Dropdown matches transactions page exactly
- [ ] Hover effects work properly
- [ ] Focus states are consistent
- [ ] No CSS conflicts with form inputs in page content

**⚠️ IMPORTANT**: This fix must be applied to ALL pages with navigation
```css
/* Company selector in navbar */
.company-selector {
    background: rgba(255, 255, 255, 0.1);
    border: 1px solid rgba(255, 255, 255, 0.2);
    border-radius: 8px;
    color: #ffffff;
    padding: 0.5rem 1rem;
    font-size: 0.9rem;
}

.company-selector:focus {
    background: rgba(255, 255, 255, 0.15);
    border-color: var(--primary-color);
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.3);
    color: #ffffff;
}
```

## 🎨 **Visual Design Elements**

### **Card Design Pattern**
```css
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
```

### **Icon + Content Pattern**
```html
<div class="financial-card-header">
    <div class="financial-card-icon [icon-class]">
        <i class="bi [bootstrap-icon]"></i>
    </div>
    <div>
        <h6 class="financial-card-title">TITLE</h6>
    </div>
</div>
<h3 class="financial-card-value [value-class]">VALUE</h3>
```

### **Color-Coded Icons**
- **Assets**: Light blue background (`rgba(6, 182, 212, 0.1)`) + `bi-building`
- **Liabilities**: Orange background (`rgba(217, 119, 6, 0.1)`) + `bi-credit-card`
- **Equity**: Brown background (`rgba(139, 69, 19, 0.1)`) + `bi-pie-chart`
- **Revenue**: Green background (`rgba(5, 150, 105, 0.1)`) + `bi-arrow-up-circle`
- **Expense**: Red background (`rgba(220, 38, 38, 0.1)`) + `bi-arrow-down-circle`

## 📊 **Section-Specific Design**

### **1. Page Header with Breadcrumb**
```css
.page-header {
    background: linear-gradient(135deg, #f8fafc 0%, #e2e8f0 100%);
    border-radius: 16px;
    padding: 1.5rem 2rem;
    margin-bottom: 2rem;
    border-left: 4px solid var(--primary-color);
}

.page-title {
    font-size: 1.75rem;
    font-weight: 700;
    color: var(--text-primary);
    margin-bottom: 0.5rem;
}

.page-subtitle {
    color: var(--text-secondary);
    font-size: 1rem;
}

/* Breadcrumb enhancement */
.breadcrumb-current {
    color: var(--primary-color);
    font-weight: 600;
}
```

### **2. Filter Section**
```css
/* Light card design with internal grid */
.filter-section {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    border: 1px solid var(--border-color);
}

/* Store filter pills */
.store-filter-btn {
    padding: 0.5rem 1rem;
    border: 2px solid var(--border-color);
    border-radius: 10px;
    transition: all 0.3s ease;
    background: transparent;
    color: var(--text-secondary);
}

.store-filter-btn.active {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.3);
}

.store-filter-btn:hover {
    border-color: var(--primary-color);
    color: var(--primary-color);
}
```

### **3. Balance Sheet Overview**
- **3-column responsive grid** (`col-lg-4`)
- **Equal height cards** with hover effects
- **Icon + title + large value** pattern
- **Neutral coloring** for balance sheet items

### **4. Monthly Performance**
- **2-column responsive grid** (`col-lg-6`)
- **Green for revenue**, **red for expense**
- **Same card pattern** as balance sheet

### **5. Net Income Highlight**
```css
.net-income-section {
    background: linear-gradient(135deg, #f1f5f9 0%, #e2e8f0 100%);
    border-radius: 20px;
    padding: 2rem;
    text-align: center;
    margin: 2rem 0;
    border: 2px solid var(--border-color);
}

.net-income-label {
    font-size: 1rem;
    color: var(--text-secondary);
    margin-bottom: 0.5rem;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.net-income-value {
    font-size: 3rem;
    font-weight: 800;
    line-height: 1;
    margin-bottom: 0.5rem;
}

.net-income-status {
    font-size: 0.9rem;
    color: var(--text-secondary);
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.5rem;
}
```

### **6. Quick Actions**
```css
/* 4-column responsive grid */
.action-buttons {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 1rem;
    margin: 2rem 0;
}

/* Button design with hover effects */
.action-btn {
    padding: 1rem 1.5rem;
    border: 2px solid var(--border-color);
    border-radius: 12px;
    transition: all 0.3s ease;
    background: var(--card-bg);
    text-align: center;
    text-decoration: none;
    color: var(--text-primary);
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 0.5rem;
}

.action-btn:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
    text-decoration: none;
}

/* Color-coded borders and hover states */
.action-btn.btn-income:hover {
    background: var(--success-color);
    border-color: var(--success-color);
    color: white;
}

.action-btn.btn-expense:hover {
    background: var(--danger-color);
    border-color: var(--danger-color);
    color: white;
}

.action-btn.btn-transfer:hover {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.action-btn.btn-view:hover {
    background: var(--info-color);
    border-color: var(--info-color);
    color: white;
}
```

### **7. Recent Journal Entries**
```css
/* Modern table design */
.transaction-table {
    border-radius: 12px;
    overflow: hidden;
    background: var(--card-bg);
    border: 1px solid var(--border-color);
}

.transaction-table th {
    background: var(--light-bg);
    border: none;
    font-weight: 600;
    color: var(--text-primary);
    padding: 1rem;
}

.transaction-table td {
    border: none;
    padding: 1rem;
    vertical-align: middle;
}

.transaction-table tbody tr {
    border-bottom: 1px solid var(--border-color);
}

.transaction-table tbody tr:hover {
    background-color: rgba(37, 99, 235, 0.05);
}

.transaction-table tbody tr:last-child {
    border-bottom: none;
}
```

## 📱 **Responsive Design Rules**

### **Mobile Breakpoints** (`@media (max-width: 768px)`)
```css
/* Navigation adjustments */
.navbar-nav {
    margin-top: 1rem;
}

.navbar-nav .nav-link {
    margin: 0.25rem 0;
}

/* Center filter elements */
.store-filters { 
    justify-content: center; 
    flex-wrap: wrap;
    gap: 0.5rem;
}

.month-selector { 
    justify-content: center; 
    margin-top: 1rem; 
}

/* Reduce font sizes */
.financial-card-value { font-size: 1.5rem; }
.net-income-value { font-size: 2.25rem; }
.page-title { font-size: 1.5rem; }

/* Stack quick actions vertically */
.action-buttons { 
    grid-template-columns: 1fr; 
    gap: 0.75rem;
}

/* Table responsive scrolling */
.table-responsive {
    border-radius: 12px;
}
```

## 🎯 **Key Design Principles**

### **1. Information Hierarchy**
- **Largest**: Net Income (3rem font)
- **Large**: Card values (1.75rem font)
- **Medium**: Section titles (1.25rem font)
- **Small**: Metadata and labels (0.875rem font)

### **2. Color Psychology**
- **Green**: Positive values (revenue, assets growth)
- **Red**: Negative values (expenses, losses)
- **Blue**: Neutral/informational (primary actions)
- **Gray**: Supporting information

### **3. Spacing Consistency**
- **Section margins**: 2rem
- **Card padding**: 1.5rem
- **Button gaps**: 1rem
- **Icon margins**: 0.75rem - 1rem

### **4. Interactive Elements**
- **Hover animations**: `transform: translateY(-2px)`
- **Transition timing**: `all 0.3s ease`
- **Shadow elevation**: sm → md on hover
- **Color transitions**: border/background changes

### **5. Active Page Indication**
- **Visual cues**: background, border, glow effect
- **Bottom accent**: blue line under active nav item
- **Consistent across all pages**: same active class pattern

## 🔧 **Implementation Guidelines for AI**

### **Navigation Active State Logic**
```php
// PHP logic to determine active page
$current_page = basename($_SERVER['PHP_SELF'], '.php');
$nav_items = [
    'index' => 'Dashboard',
    'transactions' => 'Transactions', 
    'journal-entry' => 'Journal Entry',
    'balance-sheet' => 'Financial Statements'
];

// Apply 'active' class to current page
foreach ($nav_items as $page => $title) {
    $active_class = ($current_page === $page) ? 'active' : '';
    echo "<a class='nav-link $active_class' href='$page/'>$title</a>";
}
```

### **When creating similar pages:**
1. **Use same card structure** for all metric displays
2. **Apply color coding** consistently (green=positive, red=negative)
3. **Maintain spacing rhythm** (2rem sections, 1.5rem cards)
4. **Include hover effects** on all interactive elements
5. **Use Bootstrap icons** with colored backgrounds
6. **Implement responsive breakpoints** for mobile
7. **Add active state** to navigation for current page

### **Copy-paste patterns:**
- Navigation bar: Copy complete navbar structure with active states
- Financial cards: Copy `.financial-card` structure
- Action buttons: Copy `.action-btn` with appropriate color class
- Tables: Copy `.transaction-table` styling
- Filters: Copy `.filter-section` layout

### **Required CSS variables:**
- Must include all `--primary-color`, `--success-color`, etc.
- Must include shadow and spacing variables
- Must include consistent border-radius values (8px, 10px, 12px, 16px, 20px)

---
**Design Status**: ✅ Complete with Active Page Indicators
**Last Updated**: 2025-07-15
**Compatible with**: Bootstrap 5, Modern browsers
**Mobile-First**: Responsive design implemented
**Navigation**: Active page indication system included