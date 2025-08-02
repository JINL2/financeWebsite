# Demo Setup Guide

## 🎬 For Demo Purposes Only

This guide is for users who want to quickly demo the system without setting up their own Supabase project.

### ⚠️ Important Warning
The demo configuration uses a shared database. **Do NOT use this for production or store real sensitive data.**

### Quick Demo Setup

1. Create a demo `.env` file:
```bash
cp .env.example .env.demo
```

2. Contact the project maintainer for demo credentials, or set up your own Supabase project:
   - Sign up at [supabase.com](https://supabase.com)
   - Create a new project
   - Import the database schema from `sql/` directory
   - Get your project URL and API keys

3. Update your `.env` file with demo credentials:
```bash
# Demo Configuration - NOT FOR PRODUCTION
SUPABASE_URL=your-demo-project-url
SUPABASE_ANON_KEY=your-demo-anon-key
SUPABASE_SERVICE_KEY=your-demo-service-key
```

### Demo Features Available:
- ✅ Login/Authentication
- ✅ Dashboard Overview
- ✅ Transaction Management
- ✅ Journal Entry Creation
- ✅ Financial Reports
- ✅ Multi-company Support

### Demo Limitations:
- 🚫 Data may be reset periodically
- 🚫 Limited to demo dataset
- 🚫 No email notifications
- 🚫 Not suitable for production use

### Production Setup:
For production use, always set up your own Supabase project and database.
