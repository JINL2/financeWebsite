# Transactions Page Design Specification

## 📋 **Overall Layout Structure**

### **Page Layout Hierarchy**
```
1. Navigation Bar (Fixed Top) - with Active Page Indicator
2. Page Header Section
3. Store Filter Section with New Entry Button
4. Advanced Filters Section
5. Results Section with Modern Table
```

### **Container Structure**
- **Main Container**: `container` with 2rem top/bottom padding
- **Grid System**: Bootstrap 5 responsive grid
- **Spacing**: Consistent 2rem margin between major sections

## 🧭 **Navigation Bar with Active Page Indicator**

### **✅ WORKING EXAMPLE: Navigation Company Dropdown**

**Status**: ✅ **CORRECTLY IMPLEMENTED** - This page serves as the reference implementation

**Why It Works**: 
- Custom CSS overrides Bootstrap's default form-select styles
- Specific navbar selectors prevent conflicts with page content
- Proper z-index and positioning ensures clean dropdown appearance
- Uses `!important` declarations to override Bootstrap defaults

**Working CSS** (Reference implementation):
```css
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

/* Custom dropdown arrow styling */
.navbar .form-select {
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16'%3e%3cpath fill='none' stroke='%23ffffff' stroke-linecap='round' stroke-linejoin='round' stroke-width='2.5' d='M2 5l6 6 6-6'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right 0.75rem center;
    background-size: 18px 14px;
    padding-right: 2.8rem;
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
```

**Key Success Factors**:
1. **Specific Selectors**: `.navbar .form-select` targets only navbar dropdowns
2. **Important Declarations**: `!important` ensures Bootstrap overrides
3. **Custom Arrow**: SVG arrow prevents browser default arrows
4. **Proper States**: Hover, focus, and active states all defined
5. **No Conflicts**: Doesn't affect other form elements on the page

**Testing Results**:
- ✅ Single dropdown arrow visible
- ✅ Proper hover effects
- ✅ Clean focus states
- ✅ No interference with page content forms
- ✅ Matches design system colors

**📝 Copy This Implementation**: Use this exact CSS in other pages to ensure consistency
```css
/* Navigation Bar - Dark gradient with active states */
.navbar {
    background: linear-gradient(135deg, #1e293b 0%, #334155 100%);
    box-shadow: var(--shadow-md);
    padding: 1rem 0;
}

/* Active page indicator for Transactions */
.navbar-nav .nav-link.active {
    color: #ffffff !important;
    background: rgba(37, 99, 235, 0.25) !important;
    border: 1px solid rgba(37, 99, 235, 0.4);
    border-radius: 8px;
    box-shadow: 0 0 15px rgba(37, 99, 235, 0.3);
}
```

## 🎨 **Visual Design Elements**

### **Page Header Design**
```css
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
```

### **Filter Section Design**
```css
.filter-section {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.5rem;
    margin-bottom: 2rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
}
```

## 🔘 **Store Filter Pills**

### **Store Filter Button Design**
```css
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
```

### **Store Filter Layout**
- **Display**: Flex wrap with 0.5rem gap
- **Responsive**: Centers on mobile devices
- **Active State**: Blue background with shadow
- **Hover Effect**: Blue border with lift animation

## ✅ **New Entry Button**

### **Positioned in Store Filter Header**
```css
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
```

### **Button Placement**
- **Location**: Top right of Store Filter section
- **Color**: Success green (#059669)
- **Icon**: Plus circle icon
- **Action**: Links to Journal Entry page

## 🎛️ **Advanced Filters Section**

### **Filter Container Design**
```css
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
```

### **Form Elements Styling**
```css
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
```

### **Quick Select Buttons**
```css
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
```

## 📊 **Modern Table Design**

### **Table Structure**
```css
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
```

### **Journal Entry Grouping**
```css
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

.journal-total {
    font-weight: 700;
    color: var(--primary-color);
}
```

## 🎨 **Account Icon System**

### **Icon Design Pattern**
```css
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
```

### **Account Type Colors**
- **Cash**: Green background (#059669) - `icon-cash`
  - **Bank**: `bi-bank` icon
  - **Cashier**: `bi-cash-coin` icon
- **Expenses**: Red background (#dc2626) - `icon-expense`
  - **Icon**: `bi-receipt`
- **Debt/Receivables**: Orange background (#d97706) - `icon-debt`
  - **Icon**: `bi-credit-card`
- **Error**: Gray background (#64748b) - `icon-error`
  - **Icon**: `bi-exclamation-triangle`

## 💰 **Value Display System**

### **Financial Value Colors**
```css
.value-positive {
    color: var(--success-color);   /* #059669 - Green for Credits */
    font-weight: 600;
}

.value-negative {
    color: var(--danger-color);    /* #dc2626 - Red for Debits */
    font-weight: 600;
}

.value-neutral {
    color: var(--text-primary);    /* #1e293b - Dark for Neutral */
    font-weight: 600;
}
```

### **Color Logic**
- **🟢 Green (Credits)**: Money coming in, positive cash flow
- **🔴 Red (Debits)**: Money going out, expenses
- **⚫ Dark (Neutral)**: Error accounts, neutral transactions

## 🏷️ **Badge System**

### **Location/Party Badges**
```css
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
```

### **Badge Usage**
- **Location Badge**: Cash locations, banks (blue)
- **Store Badge**: Store names, company info (gray)
- **Icons**: Geo-alt for locations, building for parties

## 🔍 **Action Buttons**

### **Primary Actions**
```css
.btn-primary {
    background: var(--primary-color);     /* #2563eb - Blue */
    border: 2px solid var(--primary-color);
    color: white;
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
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
```

### **Button Functions**
- **🔍 Search**: Primary blue button
- **🔄 Reset**: Secondary outline button
- **📥 Export**: Secondary outline button
- **➕ New Entry**: Success green button (prominent)

## 📱 **Responsive Design**

### **Mobile Adaptations**
```css
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
    
    .store-filters {
        justify-content: center;
    }
    
    .modern-table {
        font-size: 0.875rem;
    }
}
```

### **Mobile Features**
- **Stacked filters**: Single column layout
- **Centered store pills**: Better mobile navigation
- **Full-width actions**: Touch-friendly buttons
- **Smaller text**: Optimized table text size

## 🎯 **User Experience Flow**

### **Primary User Actions**
1. **Filter by Store**: Click store pills for quick filtering
2. **Set Date Range**: Use quick select or date inputs
3. **Search**: Filter by account, user, or keyword
4. **Add Transaction**: Prominent green "New Entry" button
5. **Export Data**: Download filtered results

### **Visual Hierarchy**
1. **Page Title**: Largest, dark text
2. **Store Filters**: Prominent pills with active states
3. **New Entry Button**: Green, attention-grabbing
4. **Table Headers**: Uppercase, muted
5. **Transaction Data**: Organized, color-coded

## 🔧 **Implementation Guidelines**

### **Required CSS Variables**
```css
:root {
    --primary-color: #2563eb;
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
}
```

### **JavaScript Features**
- **Dynamic loading**: AJAX-based transaction loading
- **Real-time filtering**: Form-based search
- **Date range shortcuts**: Today/Week/Month buttons
- **Account autocomplete**: Dropdown population
- **Export functionality**: Data download feature

### **API Integration**
- **GET transactions**: Filter-based data retrieval
- **Account list**: Dynamic dropdown options
- **Currency formatting**: Vietnamese Dong display
- **Date formatting**: Localized date display

## ✅ **Quality Checklist**

### **Visual Consistency**
- [ ] Uses commondesign.md color palette
- [ ] Consistent with Dashboard page styling
- [ ] Active navigation state properly shown
- [ ] Proper spacing using design system values

### **User Experience**
- [ ] New Entry button is prominent and accessible
- [ ] Store filtering is intuitive and clear
- [ ] Table data is well-organized and readable
- [ ] Mobile responsive design works properly

### **Financial Data**
- [ ] Currency formatting is consistent (₫)
- [ ] Debit/Credit colors follow accounting conventions
- [ ] Journal entries are properly grouped
- [ ] Account icons match account types

---
**Design Status**: ✅ Complete and Implemented
**Last Updated**: 2025-07-15
**Compatible with**: Bootstrap 5, Modern browsers
**Mobile-First**: Responsive design implemented
**Color System**: Follows commondesign.md specifications