#!/bin/bash

# Financial Management System - Quick Setup Script
# 재무관리 시스템 빠른 설치 스크립트

echo "🚀 Financial Management System Setup"
echo "==================================="
echo ""

# Check if .env file exists
if [ ! -f ".env" ]; then
    echo "📄 Creating .env file from template..."
    if [ -f ".env.example" ]; then
        cp .env.example .env
        echo "✅ .env file created successfully"
        echo "⚠️  IMPORTANT: Please edit .env file with your Supabase credentials"
        echo ""
    else
        echo "❌ .env.example file not found"
        exit 1
    fi
else
    echo "✅ .env file already exists"
fi

# Check PHP version
echo "🔍 Checking PHP version..."
if command -v php &> /dev/null; then
    php_version=$(php -r "echo PHP_VERSION;")
    echo "   PHP Version: $php_version"
    
    # Check if PHP version is 7.4 or higher
    php_version_check=$(php -r "echo version_compare(PHP_VERSION, '7.4.0', '>=') ? 'OK' : 'FAIL';")
    if [ "$php_version_check" = "OK" ]; then
        echo "✅ PHP version is compatible"
    else
        echo "⚠️  PHP 7.4+ recommended (current: $php_version)"
    fi
else
    echo "❌ PHP not found. Please install PHP 7.4 or higher"
fi
echo ""

# Check for required PHP extensions
echo "🔍 Checking PHP extensions..."
required_extensions=("curl" "pdo" "json")
missing_extensions=()

for ext in "${required_extensions[@]}"; do
    if php -m | grep -q "^$ext$"; then
        echo "✅ $ext extension loaded"
    else
        echo "❌ $ext extension missing"
        missing_extensions+=("$ext")
    fi
done

if [ ${#missing_extensions[@]} -eq 0 ]; then
    echo "✅ All required PHP extensions are available"
else
    echo "⚠️  Missing extensions: ${missing_extensions[*]}"
    echo "   Please install missing PHP extensions"
fi
echo ""

# Check web server configuration
echo "🌐 Web Server Setup:"
echo "   Make sure your web server is configured to:"
echo "   - Point document root to this directory"
echo "   - Enable .htaccess files (Apache)"
echo "   - Enable URL rewriting"
echo ""

# Final instructions
echo "📋 Next Steps:"
echo "   1. Edit .env file with your Supabase credentials:"
echo "      nano .env"
echo ""
echo "   2. Set up your Supabase project:"
echo "      - Create account at https://supabase.com"
echo "      - Create new project"
echo "      - Get API keys from Settings > API"
echo "      - Import database schema if needed"
echo ""
echo "   3. Configure your web server to point to this directory"
echo ""
echo "   4. Visit your domain to access the system"
echo ""
echo "🔧 For troubleshooting:"
echo "   - Check PHP error logs"
echo "   - Verify .env file configuration"
echo "   - Ensure all PHP extensions are installed"
echo "   - Check browser developer console for JavaScript errors"
echo ""
echo "📚 Documentation:"
echo "   - README.md - Full installation guide"
echo "   - SETUP.md - Quick setup guide"
echo "   - SECURITY.md - Security guidelines"
echo ""
echo "🎉 Setup script completed!"
