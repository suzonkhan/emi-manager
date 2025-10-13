#!/bin/bash

# =============================================================================
# Laravel CORS Fix - Production Deployment Script
# =============================================================================
# Run this script on your production server after updating .env file
#
# Usage: bash deploy-cors-fix.sh
# =============================================================================

echo "🚀 Starting Laravel CORS Configuration Fix..."
echo ""

# Colors for output
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Check if we're in a Laravel directory
if [ ! -f "artisan" ]; then
    echo -e "${RED}❌ Error: artisan file not found. Please run this script from Laravel root directory.${NC}"
    exit 1
fi

echo -e "${YELLOW}📋 Step 1: Checking .env file...${NC}"
if [ ! -f ".env" ]; then
    echo -e "${RED}❌ Error: .env file not found!${NC}"
    exit 1
fi

# Check for required variables
echo -e "${YELLOW}🔍 Checking CORS configuration...${NC}"
if grep -q "CORS_ALLOWED_ORIGINS" .env; then
    echo -e "${GREEN}✓ CORS_ALLOWED_ORIGINS found${NC}"
else
    echo -e "${YELLOW}⚠ CORS_ALLOWED_ORIGINS not found in .env${NC}"
    echo -e "${YELLOW}Add this line to your .env file:${NC}"
    echo "CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com"
fi

if grep -q "SANCTUM_STATEFUL_DOMAINS" .env; then
    echo -e "${GREEN}✓ SANCTUM_STATEFUL_DOMAINS found${NC}"
else
    echo -e "${YELLOW}⚠ SANCTUM_STATEFUL_DOMAINS not found in .env${NC}"
    echo -e "${YELLOW}Add this line to your .env file:${NC}"
    echo "SANCTUM_STATEFUL_DOMAINS=imelocker.com,www.imelocker.com"
fi

echo ""
echo -e "${YELLOW}🧹 Step 2: Clearing all Laravel caches...${NC}"
php artisan config:clear && echo -e "${GREEN}✓ Config cache cleared${NC}"
php artisan cache:clear && echo -e "${GREEN}✓ Application cache cleared${NC}"
php artisan route:clear && echo -e "${GREEN}✓ Route cache cleared${NC}"
php artisan view:clear && echo -e "${GREEN}✓ View cache cleared${NC}"
php artisan optimize:clear && echo -e "${GREEN}✓ Optimized files cleared${NC}"

echo ""
echo -e "${YELLOW}⚡ Step 3: Rebuilding optimized cache...${NC}"
php artisan config:cache && echo -e "${GREEN}✓ Config cached${NC}"
php artisan route:cache && echo -e "${GREEN}✓ Routes cached${NC}"
php artisan optimize && echo -e "${GREEN}✓ Application optimized${NC}"

echo ""
echo -e "${YELLOW}🔄 Step 4: Restarting services...${NC}"

# Detect PHP version
PHP_VERSION=$(php -r "echo PHP_MAJOR_VERSION.'.'.PHP_MINOR_VERSION;")
echo "Detected PHP version: $PHP_VERSION"

# Try to restart PHP-FPM
if systemctl is-active --quiet php${PHP_VERSION}-fpm; then
    sudo systemctl restart php${PHP_VERSION}-fpm && echo -e "${GREEN}✓ PHP-FPM restarted${NC}"
elif systemctl is-active --quiet php-fpm; then
    sudo systemctl restart php-fpm && echo -e "${GREEN}✓ PHP-FPM restarted${NC}"
else
    echo -e "${YELLOW}⚠ PHP-FPM service not found or no sudo access${NC}"
fi

# Try to restart Nginx
if systemctl is-active --quiet nginx; then
    sudo systemctl restart nginx && echo -e "${GREEN}✓ Nginx restarted${NC}"
elif systemctl is-active --quiet apache2; then
    sudo systemctl restart apache2 && echo -e "${GREEN}✓ Apache restarted${NC}"
else
    echo -e "${YELLOW}⚠ Web server service not found or no sudo access${NC}"
fi

echo ""
echo -e "${GREEN}✅ Deployment complete!${NC}"
echo ""
echo -e "${YELLOW}🧪 Testing CORS configuration...${NC}"
echo ""

# Test CORS
BACKEND_URL=$(grep APP_URL .env | cut -d '=' -f2)
FRONTEND_URL=$(grep FRONTEND_URL .env | cut -d '=' -f2)

if [ -n "$BACKEND_URL" ] && [ -n "$FRONTEND_URL" ]; then
    echo "Testing: $BACKEND_URL/api/dashboard/stats"
    echo "From origin: $FRONTEND_URL"
    echo ""
    
    RESPONSE=$(curl -s -I -X OPTIONS "${BACKEND_URL}/api/dashboard/stats" \
        -H "Origin: ${FRONTEND_URL}" \
        -H "Access-Control-Request-Method: GET" 2>&1)
    
    if echo "$RESPONSE" | grep -q "Access-Control-Allow-Origin"; then
        echo -e "${GREEN}✅ CORS headers are present!${NC}"
        echo "$RESPONSE" | grep "Access-Control"
    else
        echo -e "${RED}❌ CORS headers not found in response${NC}"
        echo ""
        echo -e "${YELLOW}Troubleshooting steps:${NC}"
        echo "1. Check if .env has CORS_ALLOWED_ORIGINS"
        echo "2. Verify config/cors.php exists"
        echo "3. Check Laravel logs: tail -f storage/logs/laravel.log"
        echo "4. Verify web server configuration (Nginx/Apache)"
    fi
else
    echo -e "${YELLOW}⚠ Could not determine BACKEND_URL or FRONTEND_URL from .env${NC}"
    echo "Manual test command:"
    echo "curl -I -X OPTIONS https://www.imelocker.com/api/dashboard/stats -H \"Origin: https://imelocker.com\""
fi

echo ""
echo -e "${YELLOW}📝 Next steps:${NC}"
echo "1. Test the application in your browser"
echo "2. Check browser console for CORS errors"
echo "3. Monitor: tail -f storage/logs/laravel.log"
echo ""
echo -e "${GREEN}Done! 🎉${NC}"
