# Quick Setup Guide

This guide will help you get the Financial Management System up and running quickly.

## 🚀 Quick Start (5 minutes)

### Step 1: Clone and Configure
```bash
git clone https://github.com/yourusername/financial-management-system.git
cd financial-management-system
cp .env.example .env
```

### Step 2: Update Environment Variables
Edit `.env` file with your Supabase credentials:
```bash
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_ANON_KEY=your-supabase-anonymous-key
DB_PASSWORD=your-database-password
```

### Step 3: Set up Web Server
Point your web server to the project directory and access via browser.

### Step 4: Login
Use your Supabase Auth credentials to log in.

## 🔧 Environment Variables Setup

### Required Variables
```bash
# In your .env file or server environment
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_ANON_KEY=eyJ...your-key-here
SUPABASE_SERVICE_KEY=eyJ...your-service-key-here
DB_PASSWORD=your-secure-password
```

### Optional Test Variables
```bash
TEST_MODE=false
ALLOWED_TEST_USERS=user-uuid-1,user-uuid-2
ALLOWED_TEST_COMPANIES=company-uuid-1,company-uuid-2
```

## 🗄️ Database Setup

### 1. Create Supabase Project
1. Go to [supabase.com](https://supabase.com)
2. Create new project
3. Note your URL and API keys

### 2. Required Tables
Your Supabase project should have these tables:
- `users`
- `companies` 
- `stores`
- `accounts`
- `journal_entries`
- `journal_lines`
- `cash_locations`
- `counterparties`

### 3. RPC Functions
Ensure these RPC functions are available:
- `get_user_companies_and_stores`
- `insert_journal_with_everything`
- `get_balance_sheet`
- `get_income_statement`

## 🌐 Web Server Configuration

### Apache (.htaccess already included)
Ensure your Apache has:
- `mod_rewrite` enabled
- `AllowOverride All` in virtual host

### Nginx
Add this to your server block:
```nginx
location / {
    try_files $uri $uri/ /index.php?$query_string;
}
```

### PHP Requirements
- PHP 7.4+
- cURL extension
- PDO PostgreSQL extension
- JSON extension

## 🧪 Testing the Setup

### 1. Check Configuration
Visit: `http://yoursite.com/`
- Should redirect to login page

### 2. Test Database Connection
- Login should work with valid Supabase Auth credentials
- Dashboard should load financial data

### 3. Test Core Features
- ✅ Dashboard loads
- ✅ Transactions page works
- ✅ Journal entry form appears
- ✅ Balance sheet generates

## 🚨 Troubleshooting

### Common Issues

**"Database connection failed"**
```bash
# Check environment variables
echo $SUPABASE_URL
echo $SUPABASE_ANON_KEY
# Verify in .env file or system environment
```

**"Page not found" errors**
```bash
# Check web server configuration
# Ensure mod_rewrite is enabled (Apache)
# Check file permissions (755 for directories, 644 for files)
```

**JavaScript errors**
```bash
# Check browser console
# Verify all CSS/JS files are loading
# Check for PHP errors in server logs
```

**Login fails**
```bash
# Verify Supabase Auth is configured
# Check user exists in users table
# Confirm API keys are correct
```

### Getting Help

1. Check the main [README.md](README.md) for detailed documentation
2. Verify all environment variables are set correctly
3. Check server error logs for PHP errors
4. Use browser developer tools to check for JavaScript errors

## 📁 File Structure

```
financial-management-system/
├── .env.example                 # Environment variables template
├── .gitignore                  # Git ignore rules
├── README.md                   # Main documentation
├── SETUP.md                    # This file
├── index.php                   # Main entry point
├── common/
│   ├── config.example.php      # Configuration template
│   ├── config.php              # Configuration (created from env vars)
│   ├── db.php                  # Database connection
│   ├── auth.php                # Authentication
│   └── functions.php           # Common functions
├── dashboard/                  # Dashboard module
├── transactions/              # Transaction history
├── journal-entry/            # Journal entry form
├── balance-sheet/            # Balance sheet reports
├── income-statement/         # Income statement reports
├── login/                    # Login system
├── assets/                   # CSS, JS, images
└── docs/                     # Additional documentation
```

## ✅ Deployment Checklist

Before going live:

- [ ] Set `TEST_MODE=false`
- [ ] Use strong, unique passwords
- [ ] Enable HTTPS/SSL
- [ ] Set up regular database backups
- [ ] Configure error logging
- [ ] Test all functionality
- [ ] Set up monitoring/alerts

---

Need help? Check the full [README.md](README.md) or create an issue on GitHub.
