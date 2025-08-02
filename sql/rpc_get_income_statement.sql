-- RPC Function for Income Statement
-- This function does NOT modify any table structure
-- It only reads data and categorizes accounts based on their names

-- Drop existing function if exists
DROP FUNCTION IF EXISTS rpc_get_income_statement(DATE, UUID, UUID);

-- Create the income statement RPC function
CREATE OR REPLACE FUNCTION rpc_get_income_statement(
    period DATE,
    company_id UUID,
    store_id UUID DEFAULT NULL
)
RETURNS TABLE (
    period TEXT,
    statement_detail_category TEXT,
    account_name TEXT,
    amount NUMERIC,
    is_subtotal BOOLEAN,
    sort_order INT
) AS $$
BEGIN
    RETURN QUERY
    WITH raw_data AS (
        -- Get all income statement transactions for the period
        SELECT 
            a.account_id,
            a.account_name,
            CASE 
                -- Revenue accounts
                WHEN a.account_type = 'income' AND a.account_name IN ('Sales revenue', 'Other revenue', 'Tour Incentive', '매출', 'Service revenue', 'Product sales') THEN 'sales_revenue'
                -- Cost of Goods Sold
                WHEN a.account_name IN ('COGS', 'COGS - Online', 'Cost of Goods Sold', '매출원가', 'Cost of Sales', 'Product Cost') THEN 'cogs'
                -- Tax
                WHEN a.account_name ILIKE '%tax%' AND a.account_name IN ('Income tax expense', 'Corporate tax', '법인세', 'Tax expense') THEN 'tax'
                -- Other Comprehensive Income
                WHEN a.account_name IN ('Foreign Exchange Profit and Loss', 'Foreign currency translation profit and loss', 'Long Term Debt Revaluation gain & loss', 'Error', 'others', '외환손익', '외화환산손익', '기타포괄손익') THEN 'comprehensive_income'
                -- Default to operating expense
                ELSE 'operating_expense'
            END as statement_detail_category,
            COALESCE(SUM(jl.credit - jl.debit), 0) as amount
        FROM journal_entries je
        INNER JOIN journal_lines jl ON je.journal_id = jl.journal_id
        INNER JOIN accounts a ON jl.account_id = a.account_id
        WHERE je.company_id = rpc_get_income_statement.company_id
            AND EXTRACT(YEAR FROM je.entry_date) = EXTRACT(YEAR FROM rpc_get_income_statement.period)
            AND EXTRACT(MONTH FROM je.entry_date) = EXTRACT(MONTH FROM rpc_get_income_statement.period)
            AND a.account_type IN ('income', 'expense')
            AND (rpc_get_income_statement.store_id IS NULL 
                OR jl.store_id = rpc_get_income_statement.store_id 
                OR (jl.store_id IS NULL AND rpc_get_income_statement.store_id = '00000000-0000-0000-0000-000000000000'))
        GROUP BY a.account_id, a.account_name, a.account_type
        HAVING COALESCE(SUM(jl.credit - jl.debit), 0) != 0
    ),
    -- Add subtotals
    subtotals AS (
        SELECT 
            statement_detail_category,
            SUM(amount) as category_total
        FROM raw_data
        GROUP BY statement_detail_category
    )
    -- Combine account rows and subtotal rows
    SELECT 
        TO_CHAR(rpc_get_income_statement.period, 'YYYY/MM') as period,
        rd.statement_detail_category,
        rd.account_name,
        rd.amount,
        FALSE as is_subtotal,
        CASE rd.statement_detail_category
            WHEN 'sales_revenue' THEN 100
            WHEN 'cogs' THEN 200
            WHEN 'operating_expense' THEN 300
            WHEN 'tax' THEN 400
            WHEN 'comprehensive_income' THEN 500
            ELSE 600
        END as sort_order
    FROM raw_data rd
    
    UNION ALL
    
    -- Subtotal rows
    SELECT 
        TO_CHAR(rpc_get_income_statement.period, 'YYYY/MM') as period,
        st.statement_detail_category,
        CASE st.statement_detail_category
            WHEN 'sales_revenue' THEN 'Total Revenue'
            WHEN 'cogs' THEN 'Total Cost'
            WHEN 'operating_expense' THEN 'Total Operating Expenses'
            WHEN 'tax' THEN 'Total Tax'
            WHEN 'comprehensive_income' THEN 'Other Comprehensive Income'
            ELSE 'Total ' || st.statement_detail_category
        END as account_name,
        st.category_total as amount,
        TRUE as is_subtotal,
        CASE st.statement_detail_category
            WHEN 'sales_revenue' THEN 199
            WHEN 'cogs' THEN 299
            WHEN 'operating_expense' THEN 399
            WHEN 'tax' THEN 499
            WHEN 'comprehensive_income' THEN 599
            ELSE 699
        END as sort_order
    FROM subtotals st
    
    ORDER BY sort_order, is_subtotal, account_name;
    
END;
$$ LANGUAGE plpgsql;

-- Grant execute permission
GRANT EXECUTE ON FUNCTION rpc_get_income_statement(DATE, UUID, UUID) TO authenticated;
GRANT EXECUTE ON FUNCTION rpc_get_income_statement(DATE, UUID, UUID) TO service_role;

-- Add comment for documentation
COMMENT ON FUNCTION rpc_get_income_statement(DATE, UUID, UUID) IS 
'Returns income statement data for a given period with subtotals.
Categories are determined by account names, not by a separate column.
Categories: sales_revenue, cogs, operating_expense, tax, comprehensive_income.
Each category includes individual accounts and a subtotal row.';
