-- Financial Management System Database Schema
-- This file contains the basic database structure needed for the system

-- Enable required extensions
CREATE EXTENSION IF NOT EXISTS "uuid-ossp";

-- Companies table
CREATE TABLE IF NOT EXISTS companies (
    company_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    company_name VARCHAR(255),
    company_code VARCHAR(100),
    owner_id UUID,
    base_currency_id UUID,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT false,
    deleted_at TIMESTAMP
);

-- Users table
CREATE TABLE IF NOT EXISTS users (
    user_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    first_name VARCHAR(255),
    last_name VARCHAR(255),
    email VARCHAR(255) UNIQUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT false,
    deleted_at TIMESTAMP
);

-- Stores table
CREATE TABLE IF NOT EXISTS stores (
    store_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    store_name VARCHAR(255),
    store_code VARCHAR(100),
    company_id UUID REFERENCES companies(company_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT false,
    deleted_at TIMESTAMP
);

-- User-Company relationships
CREATE TABLE IF NOT EXISTS user_companies (
    user_company_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES users(user_id),
    company_id UUID REFERENCES companies(company_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT false
);

-- User-Store relationships
CREATE TABLE IF NOT EXISTS user_stores (
    user_store_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    user_id UUID REFERENCES users(user_id),
    store_id UUID REFERENCES stores(store_id),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    is_deleted BOOLEAN DEFAULT false
);

-- Accounts (Chart of Accounts)
CREATE TABLE IF NOT EXISTS accounts (
    account_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    account_name TEXT NOT NULL,
    account_type TEXT NOT NULL CHECK (account_type IN ('asset', 'liability', 'equity', 'income', 'expense')),
    expense_nature TEXT CHECK (expense_nature IN ('fixed', 'variable')),
    category_tag TEXT,
    description TEXT,
    created_at TIMESTAMP DEFAULT now(),
    updated_at TIMESTAMP DEFAULT now()
);

-- Currency types
CREATE TABLE IF NOT EXISTS currency_types (
    currency_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    currency_code TEXT NOT NULL UNIQUE,
    currency_name TEXT,
    symbol TEXT,
    created_at TIMESTAMP DEFAULT now()
);

-- Journal entries
CREATE TABLE IF NOT EXISTS journal_entries (
    journal_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    company_id UUID NOT NULL REFERENCES companies(company_id),
    store_id UUID REFERENCES stores(store_id),
    entry_date DATE NOT NULL,
    description TEXT,
    reference_number TEXT,
    created_by UUID REFERENCES users(user_id),
    created_at TIMESTAMP DEFAULT now(),
    updated_at TIMESTAMP DEFAULT now()
);

-- Journal lines
CREATE TABLE IF NOT EXISTS journal_lines (
    line_id UUID DEFAULT gen_random_uuid() PRIMARY KEY,
    journal_id UUID NOT NULL REFERENCES journal_entries(journal_id),
    account_id UUID NOT NULL REFERENCES accounts(account_id),
    debit_amount DECIMAL(15,2) DEFAULT 0,
    credit_amount DECIMAL(15,2) DEFAULT 0,
    description TEXT,
    created_at TIMESTAMP DEFAULT now()
);

-- Insert default currency
INSERT INTO currency_types (currency_code, currency_name, symbol) 
VALUES ('USD', 'US Dollar', '$')
ON CONFLICT (currency_code) DO NOTHING;

-- Insert basic accounts
INSERT INTO accounts (account_name, account_type) VALUES
('Cash', 'asset'),
('Bank Account', 'asset'),
('Accounts Receivable', 'asset'),
('Accounts Payable', 'liability'),
('Sales Revenue', 'income'),
('Operating Expenses', 'expense')
ON CONFLICT DO NOTHING;

-- RPC function to get user companies and stores
CREATE OR REPLACE FUNCTION get_user_companies_and_stores(p_user_id UUID)
RETURNS JSON AS $$
DECLARE
    result JSON;
BEGIN
    SELECT json_build_object(
        'user_id', p_user_id,
        'companies', COALESCE(
            json_agg(
                json_build_object(
                    'company_id', c.company_id,
                    'company_name', c.company_name,
                    'stores', COALESCE(stores_data.stores, '[]'::json)
                )
            ) FILTER (WHERE c.company_id IS NOT NULL),
            '[]'::json
        )
    ) INTO result
    FROM user_companies uc
    JOIN companies c ON c.company_id = uc.company_id
    LEFT JOIN (
        SELECT 
            s.company_id,
            json_agg(
                json_build_object(
                    'store_id', s.store_id,
                    'store_name', s.store_name
                )
            ) as stores
        FROM stores s
        JOIN user_stores us ON us.store_id = s.store_id
        WHERE us.user_id = p_user_id AND s.is_deleted = false
        GROUP BY s.company_id
    ) stores_data ON stores_data.company_id = c.company_id
    WHERE uc.user_id = p_user_id 
      AND uc.is_deleted = false 
      AND c.is_deleted = false;
    
    RETURN result;
END;
$$ LANGUAGE plpgsql;
