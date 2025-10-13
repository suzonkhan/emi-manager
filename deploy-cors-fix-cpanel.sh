#!/bin/bash

# =============================================================================
# Laravel CORS Fix - cPanel/Shared Hosting Version
# =============================================================================
# Run this script on cPanel servers (no systemctl access)
#
# Usage: bash deploy-cors-fix-cpanel.sh
# =============================================================================

echo "🚀 Starting Laravel CORS Fix for cPanel..."
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in a Laravel directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: artisan file not found. Please run from Laravel root directory.${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Step 1: Checking .env configuration...${NC}"

# Check for CORS_ALLOWED_ORIGINS
if grep -q "^CORS_ALLOWED_ORIGINS=" .env; then
    CORS_VALUE=$(grep "^CORS_ALLOWED_ORIGINS=" .env | cut -d '=' -f2-)
    echo -e "${GREEN}✓ CORS_ALLOWED_ORIGINS found: ${CORS_VALUE}${NC}"
else
    echo -e "${RED}❌ CORS_ALLOWED_ORIGINS not found in .env${NC}"
    echo ""
    echo -e "${YELLOW}Please add this to your .env file:${NC}"
    echo "CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com"
    echo ""
    read -p "Press Enter after you've added it to continue, or Ctrl+C to exit..."
fi

# Check for SESSION_DOMAIN
if grep -q "^SESSION_DOMAIN=" .env; then
    SESSION_VALUE=$(grep "^SESSION_DOMAIN=" .env | cut -d '=' -f2-)
    echo -e "${GREEN}✓ SESSION_DOMAIN found: ${SESSION_VALUE}${NC}"
else
    echo -e "${YELLOW}⚠ SESSION_DOMAIN not found. Recommended: SESSION_DOMAIN=.imelocker.com${NC}"
fi

echo ""
echo -e "${YELLOW}🧹 Step 2: Clearing all Laravel caches...${NC}"

# Suppress PHP warnings from cPanel's php.ini
export PHP_INI_SCAN_DIR=""

php artisan config:clear 2>/dev/null && echo -e "${GREEN}✓ Config cache cleared${NC}"
php artisan cache:clear 2>/dev/null && echo -e "${GREEN}✓ Application cache cleared${NC}"
php artisan route:clear 2>/dev/null && echo -e "${GREEN}✓ Route cache cleared${NC}"
php artisan view:clear 2>/dev/null && echo -e "${GREEN}✓ View cache cleared${NC}"
php artisan optimize:clear 2>/dev/null && echo -e "${GREEN}✓ Optimized files cleared${NC}"

echo ""
echo -e "${YELLOW}⚡ Step 3: Rebuilding optimized cache...${NC}"

php artisan config:cache 2>/dev/null && echo -e "${GREEN}✓ Config cached${NC}"
php artisan route:cache 2>/dev/null && echo -e "${GREEN}✓ Routes cached${NC}"
php artisan optimize 2>/dev/null && echo -e "${GREEN}✓ Application optimized${NC}"

echo ""
echo -e "${YELLOW}🔄 Step 4: Restarting PHP-FPM (cPanel method)...${NC}"

# Method 1: Try to find and restart PHP-FPM process
if command -v killall &> /dev/null; then
    killall -USR2 lsphp 2>/dev/null && echo -e "${GREEN}✓ PHP processes reloaded${NC}" || echo -e "${YELLOW}⚠ Could not reload PHP processes${NC}"
else
    echo -e "${YELLOW}⚠ killall command not available${NC}"
fi

# Method 2: Touch .user.ini to trigger PHP-FPM reload
if [ -f ".user.ini" ]; then
    touch .user.ini && echo -e "${GREEN}✓ Touched .user.ini (PHP-FPM will reload automatically)${NC}"
fi

echo ""
echo -e "${YELLOW}📝 IMPORTANT: Restart PHP-FPM via cPanel${NC}"
echo "1. Log into cPanel"
echo "2. Go to 'MultiPHP Manager' or 'Select PHP Version'"
echo "3. Toggle PHP version (switch and switch back) OR click 'Save'"
echo "4. This will restart PHP-FPM and load new .env settings"
echo ""
read -p "Press Enter after you've restarted PHP-FPM in cPanel to continue testing..."

echo ""
echo -e "${YELLOW}🧪 Step 5: Testing CORS configuration...${NC}"
echo ""

# Get URLs from .env
APP_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d '=' -f2-)
FRONTEND_URL=$(grep "^FRONTEND_URL=" .env 2>/dev/null | cut -d '=' -f2-)

if [ -z "$APP_URL" ]; then
    APP_URL="https://www.imelocker.com"
fi

if [ -z "$FRONTEND_URL" ]; then
    FRONTEND_URL="https://imelocker.com"
fi

echo "Testing: ${APP_URL}/api/dashboard/stats"
echo "From origin: ${FRONTEND_URL}"
echo ""

# Test CORS with timeout
RESPONSE=$(curl -s -m 10 -I -X OPTIONS "${APP_URL}/api/dashboard/stats" \
    -H "Origin: ${FRONTEND_URL}" \
    -H "Access-Control-Request-Method: GET" 2>&1)

if echo "$RESPONSE" | grep -qi "access-control-allow-origin"; then
    echo -e "${GREEN}✅ CORS headers are present!${NC}"
    echo ""
    echo "$RESPONSE" | grep -i "access-control"
    echo ""
    echo -e "${GREEN}🎉 Success! CORS is configured correctly.${NC}"
else
    echo -e "${YELLOW}⚠️ CORS headers not detected yet${NC}"
    echo ""
    echo -e "${YELLOW}Possible reasons:${NC}"
    echo "1. PHP-FPM not restarted yet (restart in cPanel)"
    echo "2. .env file not updated with CORS_ALLOWED_ORIGINS"
    echo "3. Config cache not cleared (run: php artisan config:clear)"
    echo "4. API route not accessible (check web server config)"
    echo ""
    echo "Response received:"
    echo "$RESPONSE"
fi

echo ""
echo -e "${YELLOW}📝 Final Checklist:${NC}"
echo "[ ] .env has CORS_ALLOWED_ORIGINS"
echo "[ ] .env has SESSION_DOMAIN=.imelocker.com"
echo "[ ] Ran php artisan config:cache"
echo "[ ] Restarted PHP-FPM via cPanel"
echo "[ ] Tested in browser (no CORS errors)"
echo "[ ] API calls working from https://imelocker.com"
echo ""

echo -e "${YELLOW}📊 Additional Commands:${NC}"
echo ""
echo "# View Laravel logs:"
echo "tail -f storage/logs/laravel.log"
echo ""
echo "# Test CORS manually:"
echo "curl -I -X OPTIONS ${APP_URL}/api/dashboard/stats \\"
echo "  -H \"Origin: ${FRONTEND_URL}\""
echo ""
echo "# Check .env CORS config:"
echo "grep CORS .env"
echo ""

echo -e "${GREEN}✅ Script complete!${NC}"
echo ""
echo -e "${YELLOW}If CORS errors persist:${NC}"
echo "1. Wait 5 minutes for cPanel's PHP-FPM auto-reload"
echo "2. Or restart Apache via cPanel: 'Apache Status' → 'Restart'"
echo "3. Clear browser cache and hard refresh (Ctrl+Shift+R)"
echo ""
echo -e "${GREEN}Done! 🎉${NC}"
