# Supabase Finance System Guide

## 🚨 CRITICAL RULES FOR AI - MUST READ

### ABSOLUTE RESTRICTIONS

1. **NEVER MODIFY EXISTING TABLES**
   - DO NOT rename tables
   - DO NOT alter table structures
   - DO NOT drop or recreate tables
   - Creating NEW tables is allowed

2. **NO RLS (Row Level Security)**
   - RLS has been completely removed from all tables
   - DO NOT enable RLS on any table
   - DO NOT create any RLS policies
   - DO NOT suggest or implement RLS in any form

3. **MODIFICATION RULES**
   - ✅ ALLOWED: Create NEW functions
   - ✅ ALLOWED: Create NEW tables
   - ✅ ALLOWED: Create NEW views
   - ❌ FORBIDDEN: Modify EXISTING functions without explicit permission
   - ❌ FORBIDDEN: Modify EXISTING tables without explicit permission
   - ❌ FORBIDDEN: Modify EXISTING views without explicit permission

4. **PERMISSION REQUIRED**
   - If you need to modify anything that already exists, you MUST:
     1. Ask for permission first
     2. Explain what you want to change and why
     3. Wait for explicit approval
     4. Only proceed if approved

### Example Permission Request
```
"I need to modify the existing function to add a new parameter. 
Reason: [explain why]
Changes: [list specific changes]
May I proceed with this modification?"
```

## 🔑 Supabase Project Information
- **Project URL**: https://atkekzwgukdvucqntryo.supabase.co
- **Project ID**: atkekzwgukdvucqntryo
- **ANON KEY**: `eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9.eyJpc3MiOiJzdXBhYmFzZSIsInJlZiI6ImF0a2VrendndWtkdnVjcW50cnlvIiwicm9sZSI6ImFub24iLCJpYXQiOjE3NDI4OTQwMjIsImV4cCI6MjA1ODQ3MDAyMn0.G4WqAmLvQSqYEfMWIpFOAZOYtnT0kxCxj8dVGhuUYO8`

## 🛠️ General Guidelines for Supabase Usage

### Data Query Principles
- Always filter by company_id for security
- Use proper parameter encoding (eq., gte., lte.)
- Date format must be YYYY-MM-DD
- Order format: column.asc or column.desc

### API Call Requirements
- All API calls require user_id and company_id
- store_id is optional but recommended for filtering
- Always check HTTP status codes
- Handle errors gracefully

### Development Best Practices
- Use parameter binding to prevent SQL injection
- Validate all user inputs
- Check permissions before data access
- Log errors for debugging

## 🤖 AI Supabase MCP Usage Guidelines

### Available MCP Functions
- `supabase:list_tables` - List all tables
- `supabase:execute_sql` - Execute SQL queries
- `supabase:apply_migration` - Create NEW functions/tables (NOT for modifying existing ones)
- `supabase:list_projects` - List projects
- `supabase:get_project` - Get project details
- `supabase:deploy_edge_function` - Deploy Edge Functions
- `supabase:search_docs` - Search Supabase documentation

### Remember the Rules
1. NO RLS - it's completely disabled
2. NO table modifications without permission
3. NO existing function modifications without permission
4. Create NEW things freely, but ASK before modifying

## 📋 General Testing Principles

### Testing Approach
- Test with real data in production environment
- Use actual user IDs and company IDs
- Verify all API responses
- Check for edge cases

### Common Issues to Check
- Empty data responses
- Permission errors
- Invalid parameters
- Network connectivity

### Important Notes
- RLS is completely disabled - no 403 errors from RLS
- All tables are accessible with proper authentication
- If you encounter access issues, it's NOT due to RLS