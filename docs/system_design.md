# Finance Management System Design Document

## 1. System Overview

### 1.1 Purpose
- Financial management system that can be easily used by users without financial expertise
- Detailed filtering by company/store/user/account
- View and analyze past transaction history

### 1.2 Core Features
1. Financial statement viewing (Balance Sheet, Income Statement)
2. Transaction history viewing (with detailed filtering)
3. Simple journal entry
4. Dashboard with financial overview

## 2. Required Functionality

### 2.1 Dashboard
#### Display Requirements:
- **Financial Summary Cards**
  - Total Assets
  - Total Liabilities
  - This Month Income
  - This Month Expense
  - Net Income/Loss
  - Currency display based on company settings

- **Recent Transactions**
  - Date, Store, Account, Amount
  - Debit/Credit indication
  - Cash location display
  - Counterparty information
  - Created by user name

- **Quick Actions**
  - Add Transaction button
  - View Full Transaction List
  - Financial Reports access

### 2.2 Transaction Management
#### Display Requirements:
- **Transaction List View**
  - Date-based grouping
  - Account name and type
  - Amount with debit/credit differentiation
  - Store location
  - User who created entry
  - Description/memo field
  - Cash location (if cash transaction)
  - Counterparty (if debt transaction)

- **Filtering Options**
  - By Company
  - By Store
  - By User
  - By Account
  - By Date Range
  - By Keyword search
  - By Transaction Type

### 2.3 Journal Entry
#### Input Requirements:
- **Simple Entry Mode**
  - Step-by-step guided entry
  - Income vs Expense selection
  - Category selection (Cash Sales, Card Sales, Salary, Rent, etc.)
  - Amount input
  - Date selection
  - Store selection
  - Optional fields based on transaction type

- **Advanced Entry Mode**
  - Multiple line items
  - Custom account selection
  - Debit/Credit manual entry
  - Counterparty selection
  - Cash location selection
  - Fixed asset information
  - Debt information

### 2.4 Financial Statements
#### Balance Sheet Display:
- **Asset Section**
  - Current Assets (Cash, Receivables)
  - Fixed Assets
  - Total Assets

- **Liability Section**
  - Current Liabilities
  - Long-term Liabilities
  - Total Liabilities

- **Equity Section**
  - Capital
  - Retained Earnings
  - Total Equity

#### Income Statement Display:
- **Revenue Section**
  - Sales Revenue by category
  - Other Income
  - Total Revenue

- **Expense Section**
  - Cost of Goods Sold
  - Operating Expenses by category
  - Total Expenses

- **Net Income Calculation**
  - Gross Profit
  - Operating Income
  - Net Income before Tax
  - Net Income

### 2.5 Special Features

#### Cash Management
- **Display Requirements:**
  - Cash location list with balances
  - Location types (cash drawer, bank, vault, digital wallet)
  - Journal balance vs Actual balance comparison
  - Difference highlighting

#### Debt Management
- **Display Requirements:**
  - Total receivables and payables summary
  - Counterparty-wise breakdown
  - Due date tracking
  - Overdue highlighting
  - Interest rate display
  - Payment history

#### Fixed Asset Management
- **Display Requirements:**
  - Asset list with acquisition details
  - Depreciation progress bars
  - Book value calculation
  - Monthly depreciation amounts
  - Asset lifecycle visualization

#### Account Mapping
- **Display Requirements:**
  - Counterparty to account mapping setup
  - Direction setting (receivable/payable)
  - Linked company account mapping
  - Easy configuration interface

## 3. User Interface Requirements

### 3.1 Layout Structure
- **Navigation Menu**
  - Dashboard
  - Transactions
  - Journal Entry
  - Balance Sheet
  - Income Statement
  - Account Mapping
  - Debt Management
  - Asset Management

- **Header Section**
  - Company selector
  - Store filter
  - User information
  - Date range selector

### 3.2 Visual Design Elements
- **Color Coding**
  - Green for income/debit
  - Red for expense/credit
  - Yellow/Orange for warnings
  - Blue for informational

- **Icons Usage**
  - 💵 for income
  - 💸 for expense
  - 📍 for location
  - 🏢 for company/counterparty
  - 📊 for reports
  - ⚠️ for alerts

- **Responsive Design**
  - Mobile-friendly layout
  - Touch-optimized controls
  - Collapsible menus
  - Swipe gestures for navigation

### 3.3 Data Visualization
- **Charts and Graphs**
  - Monthly income/expense trends
  - Category-wise expense breakdown
  - Cash flow visualization
  - Asset depreciation curves

- **Progress Indicators**
  - Loading states
  - Progress bars for depreciation
  - Percentage indicators

## 4. Filtering System

### 4.1 Filter Types
- **Company Filter**: Select specific company
- **Store Filter**: All stores or specific store
- **User Filter**: Transactions by specific user
- **Account Filter**: Specific account or account type
- **Date Filter**: Custom date range
- **Keyword Search**: Search in descriptions

### 4.2 Filter Persistence
- Remember last used filters
- Quick filter presets
- Clear all filters option

## 5. Data Display Formats

### 5.1 Currency Display
- Support multiple currencies (KRW, USD, VND, EUR, JPY)
- Proper formatting with thousand separators
- Currency symbol placement

### 5.2 Date Display
- Consistent date format (YYYY-MM-DD)
- Relative date display (Today, Yesterday)
- Date grouping in lists

### 5.3 Number Formatting
- Thousand separators
- Decimal places for percentages
- Negative numbers in red or with minus sign

## 6. Validation Requirements

### 6.1 Journal Entry Validation
- Debit must equal Credit
- Required fields validation
- Date within accounting period
- Positive amount values only
- Account existence check

### 6.2 Business Rules
- Cash location restrictions by store
- Counterparty required for debt transactions
- Asset information required for fixed asset purchases
- Interest rate and due date for debt transactions

## 7. Security Considerations

### 7.1 Access Control
- User authentication required
- Company-level access restrictions
- Store-level permissions
- Read/Write permissions

### 7.2 Data Protection
- No sensitive data in URLs
- Secure API endpoints
- Input sanitization
- XSS prevention

## 8. Performance Requirements

### 8.1 Loading Times
- Dashboard load under 2 seconds
- Transaction list pagination
- Lazy loading for large datasets
- Caching frequently accessed data

### 8.2 Scalability
- Handle thousands of transactions
- Multiple concurrent users
- Efficient database queries
- Optimized API calls

## 9. Error Handling

### 9.1 User-Friendly Messages
- Clear error descriptions
- Suggested actions
- Contact support option
- Error logging

### 9.2 Data Integrity
- Transaction rollback on errors
- Validation before submission
- Duplicate prevention
- Audit trail maintenance

## 10. Future Enhancements

### 10.1 Planned Features
- Excel import/export
- Automated bank reconciliation
- Multi-language support
- Advanced reporting
- Mobile app
- Email notifications
- Approval workflows
- Budget management
- Forecast analysis
- API for third-party integration