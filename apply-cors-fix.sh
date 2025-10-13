#!/bin/bash

# ============================================================================
# CORS Fix - Apply .htaccess Changes and Configure Environment
# ============================================================================
# This script applies the complete CORS fix on the server
# Run this AFTER uploading the .htaccess files
# ============================================================================

echo "🚀 Starting CORS Complete Fix..."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Check if we're in Laravel directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: artisan not found. Run from Laravel root directory.${NC}"
    exit 1
fi

echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Step 1: Verify .htaccess Files${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

# Check root .htaccess
if [ -f ".htaccess" ]; then
    echo -e "${GREEN}✓ Root .htaccess exists${NC}"
    if grep -q "Access-Control-Allow-Origin" .htaccess; then
        echo -e "${GREEN}✓ Root .htaccess has CORS headers${NC}"
    else
        echo -e "${YELLOW}⚠ Root .htaccess missing CORS headers${NC}"
        echo "  Please upload the new .htaccess file"
    fi
else
    echo -e "${RED}❌ Root .htaccess not found${NC}"
    echo "  Please upload .htaccess to Laravel root directory"
fi

# Check public .htaccess
if [ -f "public/.htaccess" ]; then
    echo -e "${GREEN}✓ Public .htaccess exists${NC}"
    if grep -q "Access-Control-Allow-Origin" public/.htaccess; then
        echo -e "${GREEN}✓ Public .htaccess has CORS headers${NC}"
    else
        echo -e "${YELLOW}⚠ Public .htaccess missing CORS headers${NC}"
        echo "  Please upload the new public/.htaccess file"
    fi
else
    echo -e "${RED}❌ Public .htaccess not found${NC}"
fi

echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Step 2: Check .env Configuration${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

if [ ! -f ".env" ]; then
    echo -e "${RED}❌ .env file not found!${NC}"
    exit 1
fi

# Check CORS configuration
if grep -q "^CORS_ALLOWED_ORIGINS=" .env; then
    CORS_VALUE=$(grep "^CORS_ALLOWED_ORIGINS=" .env | cut -d '=' -f2-)
    echo -e "${GREEN}✓ CORS_ALLOWED_ORIGINS found${NC}"
    echo "  Value: $CORS_VALUE"
else
    echo -e "${RED}❌ CORS_ALLOWED_ORIGINS not found in .env${NC}"
    echo ""
    echo -e "${YELLOW}Add this to your .env file:${NC}"
    echo "CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com"
    echo ""
    read -p "Do you want me to add it automatically? (y/n): " ADD_CORS
    
    if [ "$ADD_CORS" = "y" ] || [ "$ADD_CORS" = "Y" ]; then
        echo "" >> .env
        echo "# CORS Configuration" >> .env
        echo "CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com" >> .env
        echo -e "${GREEN}✓ CORS_ALLOWED_ORIGINS added to .env${NC}"
    else
        echo -e "${YELLOW}Please add it manually and run this script again.${NC}"
        exit 1
    fi
fi

# Check SESSION_DOMAIN
if grep -q "^SESSION_DOMAIN=" .env; then
    SESSION_VALUE=$(grep "^SESSION_DOMAIN=" .env | cut -d '=' -f2-)
    echo -e "${GREEN}✓ SESSION_DOMAIN found: $SESSION_VALUE${NC}"
else
    echo -e "${YELLOW}⚠ SESSION_DOMAIN not found${NC}"
    read -p "Do you want to add SESSION_DOMAIN=.imelocker.com? (y/n): " ADD_SESSION
    
    if [ "$ADD_SESSION" = "y" ] || [ "$ADD_SESSION" = "Y" ]; then
        echo "SESSION_DOMAIN=.imelocker.com" >> .env
        echo "SESSION_SECURE_COOKIE=true" >> .env
        echo -e "${GREEN}✓ SESSION_DOMAIN added${NC}"
    fi
fi

# Check APP_URL
if grep -q "^APP_URL=" .env; then
    APP_URL_VALUE=$(grep "^APP_URL=" .env | cut -d '=' -f2-)
    echo -e "${GREEN}✓ APP_URL found: $APP_URL_VALUE${NC}"
else
    echo -e "${YELLOW}⚠ APP_URL not found${NC}"
fi

echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Step 3: Clear All Laravel Caches${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

php artisan config:clear 2>&1 | grep -v "syntax error" && echo -e "${GREEN}✓ Config cache cleared${NC}"
php artisan cache:clear 2>&1 | grep -v "syntax error" && echo -e "${GREEN}✓ Application cache cleared${NC}"
php artisan route:clear 2>&1 | grep -v "syntax error" && echo -e "${GREEN}✓ Route cache cleared${NC}"
php artisan view:clear 2>&1 | grep -v "syntax error" && echo -e "${GREEN}✓ View cache cleared${NC}"

echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Step 4: Rebuild Optimized Cache${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

php artisan config:cache 2>&1 | grep -v "syntax error" && echo -e "${GREEN}✓ Config cached${NC}"
php artisan route:cache 2>&1 | grep -v "syntax error" && echo -e "${GREEN}✓ Routes cached${NC}"
php artisan optimize 2>&1 | grep -v "syntax error" && echo -e "${GREEN}✓ Application optimized${NC}"

echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Step 5: Set Correct Permissions${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

chmod 644 .htaccess 2>/dev/null && echo -e "${GREEN}✓ Root .htaccess permissions set (644)${NC}"
chmod 644 public/.htaccess 2>/dev/null && echo -e "${GREEN}✓ Public .htaccess permissions set (644)${NC}"
chmod -R 775 storage 2>/dev/null && echo -e "${GREEN}✓ Storage permissions set (775)${NC}"
chmod -R 775 bootstrap/cache 2>/dev/null && echo -e "${GREEN}✓ Bootstrap cache permissions set (775)${NC}"

echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Step 6: Test CORS Configuration${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""

APP_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d '=' -f2-)
FRONTEND_URL=$(grep "^FRONTEND_URL=" .env 2>/dev/null | cut -d '=' -f2-)

if [ -z "$APP_URL" ]; then
    APP_URL="https://www.imelocker.com"
fi

if [ -z "$FRONTEND_URL" ]; then
    FRONTEND_URL="https://imelocker.com"
fi

echo "Testing CORS from:"
echo "  Backend: ${APP_URL}/api/dashboard/stats"
echo "  Origin: ${FRONTEND_URL}"
echo ""

RESPONSE=$(curl -s -m 10 -I "${APP_URL}/api/dashboard/stats" 2>&1)

if echo "$RESPONSE" | grep -qi "access-control-allow"; then
    echo -e "${GREEN}✅ CORS headers detected!${NC}"
    echo ""
    echo "$RESPONSE" | grep -i "access-control"
else
    echo -e "${YELLOW}⚠️ CORS headers not detected yet${NC}"
    echo ""
    echo "Response received:"
    echo "$RESPONSE" | head -10
fi

echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  IMPORTANT: Restart PHP-FPM${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""
echo -e "${YELLOW}To complete the fix, you MUST restart PHP-FPM:${NC}"
echo ""
echo "Method 1 (cPanel):"
echo "  1. Login to cPanel"
echo "  2. Go to 'MultiPHP Manager'"
echo "  3. Toggle PHP version for this domain"
echo "  4. Or go to 'Select PHP Version' and click 'Save'"
echo ""
echo "Method 2 (Command line - if available):"
echo "  killall -USR2 lsphp"
echo "  /scripts/restartsrv_httpd"
echo ""
echo "Method 3 (Wait):"
echo "  Wait 5-10 minutes for automatic PHP-FPM reload"
echo ""

echo -e "${GREEN}✅ Configuration applied!${NC}"
echo ""
echo -e "${BLUE}================================================${NC}"
echo -e "${BLUE}  Next Steps${NC}"
echo -e "${BLUE}================================================${NC}"
echo ""
echo "1. Restart PHP-FPM via cPanel (see above)"
echo "2. Test in browser: ${FRONTEND_URL}"
echo "3. Check browser console (F12) for CORS errors"
echo "4. Verify API calls work"
echo ""
echo "If CORS errors persist after restart:"
echo "  - Check Apache error log: tail -f ~/logs/error_log"
echo "  - Verify mod_headers enabled (contact support)"
echo "  - Check Cloudflare settings (if using)"
echo ""
echo -e "${GREEN}Done! 🎉${NC}"
