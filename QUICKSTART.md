# Quick Start Guide

Get the Financial Management System up and running in 5 minutes!

## 🚀 One-Command Setup

```bash
# Clone and setup
git clone https://github.com/yourusername/financial-management-system.git
cd financial-management-system/luxapp/finance
chmod +x setup.sh
./setup.sh
```

## 🔧 Manual Setup

### 1. Prerequisites
- PHP 7.4+ with extensions: `curl`, `pdo`, `json`
- Web server (Apache/Nginx)
- Supabase account

### 2. Quick Configuration
```bash
# Copy environment template
cp .env.example .env

# Edit with your Supabase credentials
nano .env
```

### 3. Required .env Values
```bash
SUPABASE_URL=https://your-project-id.supabase.co
SUPABASE_ANON_KEY=your-anon-key-here
```

### 4. Access Your System
Point your web server to this directory and visit:
```
http://localhost/your-directory/
```

## 🎯 First Login

The system uses Supabase Auth. You'll need to:
1. Create a user account in your Supabase Auth dashboard
2. Add user data to the `users` table
3. Link user to companies via `user_companies` table

## 📚 Need Help?

- **Full Guide**: [README.md](README.md)
- **Security**: [SECURITY.md](SECURITY.md)
- **Demo Setup**: [DEMO.md](DEMO.md)

## 🐛 Troubleshooting

**Common Issues:**
- `Database connection failed` → Check .env file
- `Page not found` → Enable URL rewriting
- `JavaScript errors` → Check browser console

**Still stuck?** Check the full [README.md](README.md) for detailed troubleshooting.
