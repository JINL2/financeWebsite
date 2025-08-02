# Income Statement V3 Clean

## Overview
Simplified Income Statement page that displays data from the `get_income_statement` RPC function.

## Features
- ✅ Clean, modern UI with Bootstrap 5
- ✅ Company and Store filtering from session storage
- ✅ Period selection (Monthly, Year-to-Date, 12 Month)
- ✅ Direct integration with `get_income_statement` RPC
- ✅ Real-time data display with proper formatting
- ✅ Responsive design for mobile devices

## RPC Integration
Uses the `get_income_statement` RPC function with parameters:
- `p_company_id` (UUID) - Required
- `p_start_date` (DATE) - Required  
- `p_end_date` (DATE) - Required
- `p_store_id` (UUID) - Optional (null for all stores)

## Data Structure
Displays sections returned by RPC:
- Revenue (Operating & Non-Operating)
- Cost of Goods Sold
- Gross Profit
- Expenses (Operating, Non-Operating, Tax)
- Operating Income
- Income Before Tax
- Other Comprehensive Income
- Net Income

## Files
- `index.php` - Main Income Statement page
- `README.md` - This documentation

## Testing
1. Navigate to the Income Statement page
2. Select filters (Company, Store, Period, View Type)
3. Click "Search Income Statement"
4. Verify data displays correctly with proper formatting

## Recent Changes
- Removed all unnecessary functions and complexity
- Simplified to focus only on displaying RPC results
- Clean, maintainable code structure
- Removed backup files and unused code
