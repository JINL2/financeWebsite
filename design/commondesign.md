# Financial Management System - Common Design System

## 🎨 **Color Palette**

### **Primary Brand Colors**
```css
:root {
    --primary-color: #2563eb;      /* Blue - Primary actions, links */
    --secondary-color: #64748b;    /* Slate Gray - Secondary text */
    --accent-color: #0891b2;       /* Cyan - Highlights, info */
}
```

### **Status & Semantic Colors**
```css
:root {
    --success-color: #059669;      /* Green - Revenue, positive values, success states */
    --danger-color: #dc2626;       /* Red - Expenses, negative values, errors */
    --warning-color: #d97706;      /* Orange - Warnings, liabilities */
    --info-color: #0891b2;         /* Cyan - Information, neutral highlights */
}
```

### **Background & Surface Colors**
```css
:root {
    --light-bg: #f8fafc;          /* Main page background */
    --card-bg: #ffffff;           /* Card and component backgrounds */
    --border-color: #e2e8f0;      /* Borders, dividers */
    --hover-bg: rgba(37, 99, 235, 0.05); /* Hover states */
}
```

### **Text Colors**
```css
:root {
    --text-primary: #1e293b;      /* Main text, headings */
    --text-secondary: #64748b;    /* Secondary text, labels */
    --text-muted: #94a3b8;        /* Metadata, timestamps */
}
```

### **Shadow System**
```css
:root {
    --shadow-sm: 0 1px 2px 0 rgba(0, 0, 0, 0.05);
    --shadow-md: 0 4px 6px -1px rgba(0, 0, 0, 0.1);
    --shadow-lg: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
}
```

## 🔤 **Typography System**

### **Font Family**
```css
body {
    font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
}
```

### **Font Scale & Usage**
```css
/* Page Titles */
.page-title {
    font-size: 2rem;           /* 32px */
    font-weight: 700;
    line-height: 1.2;
}

/* Section Headers */
.section-title {
    font-size: 1.25rem;        /* 20px */
    font-weight: 700;
    line-height: 1.3;
}

/* Card Titles */
.card-title {
    font-size: 0.875rem;       /* 14px */
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

/* Large Values (Financial Data) */
.value-large {
    font-size: 1.75rem;        /* 28px */
    font-weight: 700;
    line-height: 1.2;
}

/* Extra Large Values (Net Income) */
.value-xl {
    font-size: 3rem;           /* 48px */
    font-weight: 800;
    line-height: 1;
}

/* Body Text */
.body-text {
    font-size: 1rem;           /* 16px */
    font-weight: 400;
    line-height: 1.5;
}

/* Small Text */
.text-small {
    font-size: 0.875rem;       /* 14px */
    font-weight: 400;
    line-height: 1.4;
}
```

## 🧩 **Component Library**

### **Card Component**
```css
.base-card {
    background: var(--card-bg);
    border-radius: 16px;
    padding: 1.5rem;
    box-shadow: var(--shadow-sm);
    border: 1px solid var(--border-color);
    transition: all 0.3s ease;
}

.base-card:hover {
    transform: translateY(-2px);
    box-shadow: var(--shadow-md);
}
```

### **Navigation Dropdown (Company Selector)**

⚠️ **Critical Issue & Solution**: The navigation dropdown requires specific CSS to prevent multiple arrow displays

**Problem**: Modern browsers and Bootstrap show multiple dropdown arrows when using standard form-select classes in navbar

**Solution**: Override default form-select styles with custom navbar-specific CSS

```css
/* REQUIRED: Navigation Company Dropdown Styling */
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
    
    /* Custom arrow - prevents multiple arrows */
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

/* Reset when not focused */
.navbar .form-select:not(:focus-visible) {
    border-color: rgba(255, 255, 255, 0.3) !important;
    background: rgba(255, 255, 255, 0.1) !important;
    color: white !important;
    box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1) !important;
    transform: none !important;
}

.navbar .form-select option {
    color: var(--text-primary);
    background: white;
    padding: 0.75rem;
}

/* Container styling */
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

**HTML Structure**:
```html
<div class="company-dropdown-container">
    <span class="company-dropdown-label">
        <i class="bi bi-building me-1"></i>Company:
    </span>
    <select class="form-select form-select-sm d-inline-block w-auto" 
            onchange="changeCompany(this.value)" 
            title="Select Company">
        <!-- Options here -->
    </select>
</div>
```

**⚠️ DO NOT USE**: `modern-dropdown.css` - it conflicts with navbar styling

**✅ ALWAYS**: Include the complete CSS above in every page with navigation
```css
/* Base Button */
.btn-base {
    padding: 0.75rem 1.5rem;
    border-radius: 10px;
    font-weight: 600;
    transition: all 0.3s ease;
    border: 2px solid;
    text-decoration: none;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Primary Button */
.btn-primary {
    background: var(--primary-color);
    border-color: var(--primary-color);
    color: white;
}

.btn-primary:hover {
    background: #1d4ed8;
    transform: translateY(-1px);
}

/* Success Button (Income) */
.btn-success {
    background: white;
    border-color: var(--success-color);
    color: var(--success-color);
}

.btn-success:hover {
    background: var(--success-color);
    color: white;
}

/* Danger Button (Expense) */
.btn-danger {
    background: white;
    border-color: var(--danger-color);
    color: var(--danger-color);
}

.btn-danger:hover {
    background: var(--danger-color);
    color: white;
}
```

### **Icon System**
```css
/* Icon Container */
.icon-container {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
}

/* Icon Color Variants */
.icon-primary { 
    background: rgba(37, 99, 235, 0.1); 
    color: var(--primary-color); 
}

.icon-success { 
    background: rgba(5, 150, 105, 0.1); 
    color: var(--success-color); 
}

.icon-danger { 
    background: rgba(220, 38, 38, 0.1); 
    color: var(--danger-color); 
}

.icon-warning { 
    background: rgba(217, 119, 6, 0.1); 
    color: var(--warning-color); 
}

.icon-info { 
    background: rgba(8, 145, 178, 0.1); 
    color: var(--info-color); 
}
```

### **Table System**
```css
.modern-table {
    border-radius: 12px;
    overflow: hidden;
    box-shadow: var(--shadow-sm);
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
```

### **Badge System**
```css
.badge-base {
    padding: 0.25rem 0.75rem;
    border-radius: 20px;
    font-size: 0.875rem;
    font-weight: 500;
}

.badge-location {
    background: rgba(37, 99, 235, 0.1);
    color: var(--primary-color);
}

.badge-store {
    background: rgba(100, 116, 139, 0.1);
    color: var(--text-secondary);
    border-radius: 6px;
}

.badge-positive {
    background: rgba(5, 150, 105, 0.1);
    color: var(--success-color);
}

.badge-negative {
    background: rgba(220, 38, 38, 0.1);
    color: var(--danger-color);
}
```

## 📐 **Layout & Spacing System**

### **Spacing Scale**
```css
:root {
    --space-xs: 0.25rem;    /* 4px */
    --space-sm: 0.5rem;     /* 8px */
    --space-md: 1rem;       /* 16px */
    --space-lg: 1.5rem;     /* 24px */
    --space-xl: 2rem;       /* 32px */
    --space-2xl: 3rem;      /* 48px */
}

/* Usage Guidelines:
   - Section margins: var(--space-xl)
   - Card padding: var(--space-lg)
   - Button gaps: var(--space-md)
   - Icon margins: var(--space-sm) to var(--space-md)
*/
```

### **Border Radius Scale**
```css
:root {
    --radius-sm: 6px;       /* Small elements, badges */
    --radius-md: 10px;      /* Buttons, inputs */
    --radius-lg: 12px;      /* Tables, smaller cards */
    --radius-xl: 16px;      /* Main cards */
    --radius-2xl: 20px;     /* Hero sections */
}
```

### **Grid System Guidelines**
- **Use Bootstrap 5 grid system**
- **Container**: `container-fluid` for full-width layouts
- **Responsive columns**: `col-lg-*` for desktop, stacks on mobile
- **Equal height**: Use `h-100` class for card grids

## 💰 **Financial Data Display Rules**

### **Currency Format**
```css
/* Always use Vietnamese Dong symbol */
.currency::before {
    content: "₫";
}

/* Number formatting: ₫1,234,567,890 */
```

### **Value Color Coding**
```css
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
```

### **Financial Card Pattern**
```html
<!-- Standard Financial Card Structure -->
<div class="base-card">
    <div class="d-flex align-items-center mb-3">
        <div class="icon-container icon-[variant]">
            <i class="bi bi-[icon-name]"></i>
        </div>
        <div class="ms-3">
            <h6 class="card-title mb-0">[TITLE]</h6>
        </div>
    </div>
    <h3 class="value-large value-[positive|negative|neutral]">₫[VALUE]</h3>
</div>
```

## 📱 **Responsive Design Rules**

### **Breakpoints**
```css
/* Mobile First Approach */
@media (min-width: 576px) { /* sm */ }
@media (min-width: 768px) { /* md */ }
@media (min-width: 992px) { /* lg */ }
@media (min-width: 1200px) { /* xl */ }
```

### **Mobile Adaptations**
```css
@media (max-width: 768px) {
    /* Reduce font sizes */
    .value-large { font-size: 1.5rem; }
    .value-xl { font-size: 2.25rem; }
    
    /* Stack layouts */
    .desktop-grid { 
        display: block; 
    }
    
    /* Center filters */
    .filter-buttons {
        justify-content: center;
        flex-wrap: wrap;
    }
    
    /* Full-width action buttons */
    .action-grid {
        grid-template-columns: 1fr;
    }
}
```

## 🔧 **Implementation Guidelines**

### **CSS Variable Usage**
- **Always use CSS variables** for colors and spacing
- **Define custom properties** at component level when needed
- **Maintain consistency** across all pages

### **Class Naming Convention**
```css
/* Component-based naming */
.financial-card { }           /* Component */
.financial-card__header { }   /* Element */
.financial-card--large { }    /* Modifier */

/* Utility classes */
.text-positive { }            /* Color utility */
.shadow-hover { }             /* Effect utility */
.space-y-lg { }              /* Spacing utility */
```

### **Required Dependencies**
```html
<!-- Bootstrap 5 CSS & JS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">

<!-- Bootstrap Icons -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.0/font/bootstrap-icons.min.css" rel="stylesheet">

<!-- JavaScript -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
```

## ✅ **Design Consistency Checklist**

### **For every new page, ensure:**
- [ ] CSS variables are defined and used
- [ ] Color coding follows financial rules (green=positive, red=negative)
- [ ] Spacing uses consistent scale (--space-* variables)
- [ ] Cards use base-card structure with hover effects
- [ ] Icons have colored backgrounds using icon-container pattern
- [ ] Tables use modern-table styling
- [ ] Buttons follow btn-base pattern with appropriate variants
- [ ] Mobile responsive breakpoints are implemented
- [ ] Typography scale is followed consistently
- [ ] Shadows and border-radius use defined variables

---
**Design System Version**: 1.0
**Last Updated**: 2025-07-15
**Framework**: Bootstrap 5
**Browser Support**: Modern browsers (Chrome, Firefox, Safari, Edge)
**Accessibility**: WCAG 2.1 AA compliant color contrasts
