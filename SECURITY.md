# Security Guidelines

## 🔐 Important Security Information

### Critical Files - NEVER COMMIT TO GIT:
- `.env` - Contains all sensitive credentials
- `common/config.php` - Auto-generated from environment variables
- `test-db.php` - Database connection testing script

### Safe for GitHub:
- `.env.example` - Template file with placeholder values
- All other project files

## 🛡️ Setup Security Checklist

### Before Going Live:
1. **Environment Variables**:
   - [ ] Set up your own Supabase project
   - [ ] Update `.env` with your actual credentials
   - [ ] Never commit `.env` file to Git

2. **Database Security**:
   - [ ] Configure Row Level Security (RLS) in Supabase
   - [ ] Set up proper user authentication
   - [ ] Limit API access with proper permissions

3. **Server Security**:
   - [ ] Use HTTPS in production
   - [ ] Set up proper file permissions (644 for files, 755 for directories)
   - [ ] Configure error logging (don't display errors to users)

4. **API Security**:
   - [ ] Validate all user inputs
   - [ ] Use parameterized queries (already implemented)
   - [ ] Implement rate limiting if needed

## 🚨 Emergency Response

If you accidentally commit sensitive information:
1. Immediately revoke the exposed API keys in Supabase
2. Generate new API keys
3. Update your `.env` file with new credentials
4. Use `git filter-branch` or BFG Repo-Cleaner to remove sensitive data from Git history

## 📞 Support

For security-related questions, please create a private issue or contact the maintainers directly.
