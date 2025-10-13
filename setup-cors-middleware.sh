#!/bin/bash

# ============================================================================
# CORS Middleware Solution - Server-Side Setup Script
# ============================================================================
# Run this script AFTER uploading files via upload-cors-middleware.bat
# ============================================================================

echo "🔧 CORS Middleware Setup - Final Configuration"
echo "============================================================"
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m'

# Check Laravel directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: Not in Laravel directory${NC}"
    exit 1
fi

echo -e "${BLUE}Step 1: Verify Uploaded Files${NC}"
echo "============================================================"
echo ""

# Check middleware
if [ -f "app/Http/Middleware/HandleCorsHeaders.php" ]; then
    echo -e "${GREEN}✓ CORS Middleware found${NC}"
else
    echo -e "${RED}❌ HandleCorsHeaders.php not found${NC}"
    echo "Please upload: app/Http/Middleware/HandleCorsHeaders.php"
    exit 1
fi

# Check bootstrap
if [ -f "bootstrap/app.php" ]; then
    if grep -q "HandleCorsHeaders" bootstrap/app.php; then
        echo -e "${GREEN}✓ Middleware registered in bootstrap/app.php${NC}"
    else
        echo -e "${YELLOW}⚠ Middleware not registered${NC}"
    fi
else
    echo -e "${RED}❌ bootstrap/app.php not found${NC}"
    exit 1
fi

# Check config
if [ -f "config/cors.php" ]; then
    echo -e "${GREEN}✓ CORS config found${NC}"
else
    echo -e "${YELLOW}⚠ config/cors.php not found${NC}"
fi

echo ""
echo -e "${BLUE}Step 2: Configure .env${NC}"
echo "============================================================"
echo ""

if [ ! -f ".env" ]; then
    echo -e "${RED}❌ .env file not found${NC}"
    exit 1
fi

# Check CORS_ALLOWED_ORIGINS
if grep -q "^CORS_ALLOWED_ORIGINS=" .env; then
    CORS_VALUE=$(grep "^CORS_ALLOWED_ORIGINS=" .env | cut -d '=' -f2-)
    echo -e "${GREEN}✓ CORS_ALLOWED_ORIGINS found${NC}"
    echo "  Current value: $CORS_VALUE"
    
    if [[ ! "$CORS_VALUE" =~ "imelocker.com" ]]; then
        echo -e "${YELLOW}⚠ Value doesn't include imelocker.com domains${NC}"
        read -p "Update to include both domains? (y/n): " UPDATE
        if [ "$UPDATE" = "y" ]; then
            sed -i.bak 's/^CORS_ALLOWED_ORIGINS=.*/CORS_ALLOWED_ORIGINS=https:\/\/www.imelocker.com,https:\/\/imelocker.com/' .env
            echo -e "${GREEN}✓ Updated CORS_ALLOWED_ORIGINS${NC}"
        fi
    fi
else
    echo -e "${RED}❌ CORS_ALLOWED_ORIGINS not found${NC}"
    echo ""
    read -p "Add CORS_ALLOWED_ORIGINS to .env? (y/n): " ADD
    
    if [ "$ADD" = "y" ]; then
        echo "" >> .env
        echo "# CORS Configuration" >> .env
        echo "CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com" >> .env
        echo -e "${GREEN}✓ Added CORS_ALLOWED_ORIGINS to .env${NC}"
    else
        echo -e "${RED}Cannot proceed without CORS_ALLOWED_ORIGINS${NC}"
        exit 1
    fi
fi

echo ""
echo -e "${BLUE}Step 3: Clear All Caches${NC}"
echo "============================================================"
echo ""

php artisan config:clear 2>&1 | grep -q "cleared" && echo -e "${GREEN}✓ Config cache cleared${NC}"
php artisan cache:clear 2>&1 | grep -q "cleared" && echo -e "${GREEN}✓ Application cache cleared${NC}"
php artisan route:clear 2>&1 | grep -q "cleared" && echo -e "${GREEN}✓ Route cache cleared${NC}"
php artisan view:clear 2>&1 | grep -q "cleared" && echo -e "${GREEN}✓ View cache cleared${NC}"
php artisan optimize:clear 2>&1 | grep -q "Clearing" && echo -e "${GREEN}✓ Optimized cache cleared${NC}"

echo ""
echo -e "${BLUE}Step 4: Rebuild Cache${NC}"
echo "============================================================"
echo ""

php artisan config:cache 2>&1 | grep -q "cached" && echo -e "${GREEN}✓ Config cached${NC}"
php artisan route:cache 2>&1 | grep -q "cached" && echo -e "${GREEN}✓ Routes cached${NC}"
php artisan optimize 2>&1 | grep -q "Caching" && echo -e "${GREEN}✓ Application optimized${NC}"

echo ""
echo -e "${BLUE}Step 5: Verify Middleware Registration${NC}"
echo "============================================================"
echo ""

echo "Checking API routes middleware..."
php artisan route:list --path=api --columns=uri,middleware | head -20

echo ""
echo -e "${BLUE}Step 6: Test CORS Headers${NC}"
echo "============================================================"
echo ""

APP_URL=$(grep "^APP_URL=" .env 2>/dev/null | cut -d '=' -f2- | tr -d ' ')
if [ -z "$APP_URL" ]; then
    APP_URL="https://www.imelocker.com"
fi

echo "Testing: ${APP_URL}/api/reports/dealers"
echo "Origin: https://imelocker.com"
echo ""

RESPONSE=$(curl -s -m 10 -I "${APP_URL}/api/reports/dealers" \
    -H "Origin: https://imelocker.com" \
    -H "Accept: application/json" 2>&1)

if echo "$RESPONSE" | grep -qi "access-control-allow-origin"; then
    echo -e "${GREEN}✅ SUCCESS! CORS headers are present!${NC}"
    echo ""
    echo "Response headers:"
    echo "$RESPONSE" | grep -i "access-control"
    echo ""
    echo -e "${GREEN}🎉 CORS is now configured correctly!${NC}"
else
    echo -e "${YELLOW}⚠️ CORS headers not detected in response${NC}"
    echo ""
    echo "Full response:"
    echo "$RESPONSE"
    echo ""
    echo -e "${YELLOW}You MUST restart PHP-FPM for changes to take effect!${NC}"
fi

echo ""
echo -e "${BLUE}============================================================${NC}"
echo -e "${BLUE}  CRITICAL: Restart PHP-FPM${NC}"
echo -e "${BLUE}============================================================${NC}"
echo ""
echo -e "${YELLOW}Changes will NOT take effect until PHP-FPM restarts!${NC}"
echo ""
echo "Restart via cPanel:"
echo "  1. Login to cPanel"
echo "  2. Go to 'MultiPHP Manager'"
echo "  3. Select domain: data.imelocker.com"
echo "  4. Toggle PHP version (8.3 -> 8.2 -> 8.3)"
echo "  5. Or go to 'Select PHP Version' -> Click 'Save'"
echo ""
echo "OR wait 5-10 minutes for automatic reload"
echo ""

read -p "Press Enter after restarting PHP-FPM to re-test..."

echo ""
echo "Re-testing CORS..."
RESPONSE2=$(curl -s -m 10 -I "${APP_URL}/api/reports/dealers" \
    -H "Origin: https://imelocker.com" 2>&1)

if echo "$RESPONSE2" | grep -qi "access-control-allow-origin"; then
    echo -e "${GREEN}✅ VERIFIED! CORS is working!${NC}"
    echo ""
    echo "$RESPONSE2" | grep -i "access-control"
else
    echo -e "${RED}❌ Still not working${NC}"
    echo ""
    echo "Troubleshooting steps:"
    echo "1. Check Laravel logs: tail -f storage/logs/laravel.log"
    echo "2. Verify .env has CORS_ALLOWED_ORIGINS"
    echo "3. Run: php artisan config:show cors"
    echo "4. Check if domain is correct in .env"
    echo "5. Contact hosting support about PHP-FPM restart"
fi

echo ""
echo -e "${BLUE}============================================================${NC}"
echo -e "${BLUE}  Final Verification${NC}"
echo -e "${BLUE}============================================================${NC}"
echo ""
echo "Test in browser:"
echo "1. Open: https://imelocker.com"
echo "2. Open DevTools (F12) -> Network tab"
echo "3. Try accessing Reports page"
echo "4. Check if CORS error is gone"
echo ""
echo "If still having issues:"
echo "  - View browser console for errors"
echo "  - Check Network tab response headers"
echo "  - Look for 'access-control-allow-origin' header"
echo ""
echo -e "${GREEN}Setup complete! 🎉${NC}"
