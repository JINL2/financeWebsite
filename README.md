# Financial Management System

A comprehensive web-based financial management system built with PHP and Supabase, featuring real-time data processing, multi-company support, and modern UI design.

![License](https://img.shields.io/badge/license-MIT-blue.svg)
![PHP](https://img.shields.io/badge/PHP-7.4%2B-blue.svg)
![Supabase](https://img.shields.io/badge/Database-Supabase-green.svg)

## 🚀 Features

- **💼 Multi-Company Support**: Switch between different companies seamlessly
- **📊 Dashboard**: Financial overview with real-time metrics and charts
- **📝 Journal Entries**: Double-entry bookkeeping with automated validation
- **💰 Transaction Management**: Comprehensive transaction history and filtering
- **📈 Financial Reports**: Balance Sheet and Income Statement generation
- **🏦 Cash Management**: Track cash locations and balances across multiple currencies
- **👥 Employee Management**: Payroll and salary data management
- **🔐 Secure Authentication**: Supabase Auth integration with role-based access
- **📱 Responsive Design**: Modern UI with Bootstrap 5 and mobile support
- **🌍 Multi-Currency**: Support for multiple currencies with exchange rates

## 🏗️ Architecture

- **Backend**: PHP 7.4+ with Supabase integration
- **Database**: PostgreSQL via Supabase
- **Frontend**: Bootstrap 5, Vanilla JavaScript
- **API**: RESTful APIs with RPC function calls
- **Authentication**: Supabase Auth integration

## 📋 Prerequisites

- PHP 7.4 or higher
- Web server (Apache/Nginx)
- Supabase account and project
- Modern web browser

## ⚙️ Installation

### 1. Clone the Repository
```bash
git clone https://github.com/yourusername/financial-management-system.git
cd financial-management-system/luxapp/finance
```

### 2. Configuration Setup

#### Environment Variables Setup (Required)
```bash
# Copy environment template
cp .env.example .env

# Edit .env file with your Supabase credentials
nano .env
```

**Important**: You must set up your own Supabase project and update the `.env` file with your credentials:

1. Create a new Supabase project at [supabase.com](https://supabase.com)
2. Get your project URL and API keys from the project settings
3. Update the `.env` file with your actual credentials:
   ```
   SUPABASE_URL=https://your-project-id.supabase.co
   SUPABASE_ANON_KEY=your-actual-anon-key
   ```

#### Option B: Using Configuration File
```bash
# Copy configuration template
cp common/config.example.php common/config.php

# Edit config.php with your credentials  
nano common/config.php
```

### 3. Supabase Setup

1. Create a new Supabase project at [supabase.com](https://supabase.com)
2. Note your project URL and API keys
3. Set up your database schema (see `sql/` directory for schema files)
4. Configure Row Level Security (RLS) policies as needed

### 4. Environment Variables

Set the following environment variables:

```bash
# Supabase Configuration
export SUPABASE_URL="https://your-project-id.supabase.co"
export SUPABASE_ANON_KEY="your-supabase-anonymous-key"
export SUPABASE_SERVICE_KEY="your-supabase-service-key"

# Database Configuration
export DB_HOST="db.your-project-id.supabase.co"
export DB_PASSWORD="your-database-password"

# Optional: Test Configuration
export TEST_MODE="false"
export ALLOWED_TEST_USERS="your-test-user-ids"
export ALLOWED_TEST_COMPANIES="your-test-company-ids"
```

### 5. Web Server Configuration

#### Apache
Ensure your Apache configuration allows `.htaccess` files and has `mod_rewrite` enabled.

#### Nginx
Configure your virtual host to point to the project directory.

### 6. Access the Application

Navigate to your configured domain or `http://localhost/your-project-path/`

## 🔧 Configuration

### Environment Variables Reference

| Variable | Description | Required |
|----------|-------------|----------|
| `SUPABASE_URL` | Your Supabase project URL | Yes |
| `SUPABASE_ANON_KEY` | Supabase anonymous key | Yes |
| `SUPABASE_SERVICE_KEY` | Supabase service role key | Optional |
| `DB_HOST` | Database host | Optional |
| `DB_PASSWORD` | Database password | Optional |
| `TEST_MODE` | Enable test mode | No |

### Database Schema

The system requires the following main tables:
- `users` - User accounts
- `companies` - Company information
- `stores` - Store/branch locations
- `accounts` - Chart of accounts
- `journal_entries` - Journal entry headers
- `journal_lines` - Journal entry line items
- `cash_locations` - Cash management locations
- `counterparties` - Business partners/customers/vendors

## 📱 Usage

### Basic Workflow

1. **Login**: Authenticate with your credentials
2. **Select Company**: Choose the company to work with
3. **Dashboard**: View financial overview and recent transactions
4. **Journal Entry**: Record financial transactions using double-entry bookkeeping
5. **Reports**: Generate balance sheets and income statements
6. **Transaction History**: Review and filter historical transactions

### Key Features

#### Multi-Company Support
Switch between different companies using the dropdown in the navigation bar.

#### Real-time Updates
All financial data updates in real-time across all connected sessions.

#### Advanced Filtering
Filter transactions by date range, store, account, or search keywords.

#### Automated Validation
Built-in validation ensures accounting principles are followed (debits = credits).

## 🛡️ Security Features

- Environment variable configuration for sensitive data
- SQL injection prevention with parameterized queries
- XSS protection with proper output escaping
- Authentication via Supabase Auth
- Role-based access control

## 🔗 API Endpoints

### Authentication
- `POST /login/api.php` - User authentication

### Dashboard  
- `GET /dashboard/api.php?action=get_summary` - Financial summary
- `GET /dashboard/api.php?action=get_recent_transactions` - Recent transactions

### Transactions
- `GET /transactions/api.php?action=get_transactions` - Transaction history

### Journal Entry
- `POST /journal-entry/save_journal_entry.php` - Save journal entry

### Financial Statements
- `GET /balance-sheet/api.php` - Balance sheet data
- `GET /income-statement/api.php` - Income statement data

## 🧪 Testing

### Test Data Setup
Use the provided environment variables to set up test data:

```bash
export TEST_MODE="true"
export ALLOWED_TEST_USERS="test-user-1,test-user-2"
export ALLOWED_TEST_COMPANIES="test-company-1,test-company-2"
```

### Manual Testing
1. Access the login page
2. Use test credentials (if configured)
3. Navigate through different modules
4. Test transaction entry and report generation

## 🚀 Deployment

### Production Checklist

- [ ] Set `TEST_MODE=false`
- [ ] Configure production environment variables
- [ ] Set up HTTPS/SSL
- [ ] Configure proper error logging
- [ ] Set up database backups
- [ ] Configure web server security headers
- [ ] Test all functionality in production environment

### Recommended Hosting

- **VPS/Dedicated Server** with PHP 7.4+ support
- **Shared Hosting** with PHP and database access
- **Cloud Platforms**: AWS, Google Cloud, DigitalOcean
- **Supabase** for database and authentication

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## 📄 License

This project is licensed under the MIT License - see the [LICENSE](LICENSE) file for details.

## 🆘 Support

### Common Issues

**Q: Page shows "Database connection failed"**
A: Check your environment variables and ensure Supabase credentials are correct.

**Q: Login fails with valid credentials**
A: Verify Supabase Auth is properly configured and user exists in the users table.

**Q: Transactions not loading**
A: Check browser console for JavaScript errors and verify API endpoints are accessible.

### Getting Help

- Check the documentation in the `docs/` directory
- Review error logs in your web server
- Ensure all environment variables are properly set
- Verify database connectivity

### Contact

For support and questions:
- Create an issue on GitHub
- Check existing documentation
- Review the troubleshooting guide

---

**Built with ❤️ using PHP, Supabase, and modern web technologies**
