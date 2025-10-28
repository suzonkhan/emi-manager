#!/bin/bash

# Storage Fix Script for Production
# Run this on your production server

echo "========================================="
echo "Laravel Storage Fix Script"
echo "========================================="
echo ""

# Colors
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

# Get the project directory
PROJECT_DIR="$( cd "$( dirname "${BASH_SOURCE[0]}" )" && pwd )"
cd "$PROJECT_DIR"

echo "Project directory: $PROJECT_DIR"
echo ""

# Step 1: Check if storage/app/public exists
echo -e "${YELLOW}Step 1: Checking storage directory...${NC}"
if [ -d "storage/app/public" ]; then
    echo -e "${GREEN}✓ storage/app/public exists${NC}"
    ls -la storage/app/public/photos/users/ 2>/dev/null | head -5 || echo "No user photos found"
else
    echo -e "${RED}✗ storage/app/public does not exist${NC}"
    exit 1
fi
echo ""

# Step 2: Remove old symlink if it exists
echo -e "${YELLOW}Step 2: Removing old symlink...${NC}"
if [ -L "public/storage" ]; then
    echo "Removing existing symlink..."
    rm public/storage
    echo -e "${GREEN}✓ Old symlink removed${NC}"
elif [ -d "public/storage" ]; then
    echo -e "${RED}WARNING: public/storage is a directory, not a symlink!${NC}"
    echo "Removing directory..."
    rm -rf public/storage
    echo -e "${GREEN}✓ Directory removed${NC}"
else
    echo -e "${GREEN}✓ No symlink found${NC}"
fi
echo ""

# Step 3: Create new symlink
echo -e "${YELLOW}Step 3: Creating storage symlink...${NC}"
php artisan storage:link
if [ $? -eq 0 ]; then
    echo -e "${GREEN}✓ Symlink created successfully${NC}"
else
    echo -e "${RED}✗ Failed to create symlink${NC}"
    exit 1
fi
echo ""

# Step 4: Verify symlink
echo -e "${YELLOW}Step 4: Verifying symlink...${NC}"
if [ -L "public/storage" ]; then
    echo -e "${GREEN}✓ Symlink exists${NC}"
    echo "Symlink target: $(readlink -f public/storage)"
else
    echo -e "${RED}✗ Symlink verification failed${NC}"
    exit 1
fi
echo ""

# Step 5: Fix permissions
echo -e "${YELLOW}Step 5: Fixing permissions...${NC}"
if [ -w "storage/app/public" ]; then
    chmod -R 775 storage/app/public
    chmod -R 775 public/storage 2>/dev/null || true
    
    # Try to set ownership (may require sudo)
    if command -v www-data &> /dev/null; then
        chown -R www-data:www-data storage/app/public || echo "Could not change ownership (may need sudo)"
    fi
    echo -e "${GREEN}✓ Permissions updated${NC}"
else
    echo -e "${YELLOW}⚠ Cannot write to storage directory${NC}"
fi
echo ""

# Step 6: Test file access
echo -e "${YELLOW}Step 6: Testing file access...${NC}"
FIRST_FILE=$(ls storage/app/public/photos/users/ 2>/dev/null | head -1)
if [ -n "$FIRST_FILE" ]; then
    if [ -f "public/storage/photos/users/$FIRST_FILE" ]; then
        echo -e "${GREEN}✓ Test file accessible via symlink${NC}"
        echo "   File: photos/users/$FIRST_FILE"
        echo "   Size: $(du -h "public/storage/photos/users/$FIRST_FILE" | cut -f1)"
    else
        echo -e "${REDrag file:$FIRST_FILE"
    fi
else
    echo -e "${YELLOW}⚠ No test files found${NC}"
fi
echo ""

# Step 7: Clear cache
echo -e "${YELLOW}Step 7: Clearing caches...${NC}"
php artisan cache:clear
php artisan config:clear
php artisan route:clear
echo -e "${GREEN}✓ Cache cleared${NC}"
echo ""

echo "========================================="
echo -e "${GREEN}✓ Setup Complete!${NC}"
echo "========================================="
echo ""
echo "Test your images at:"
echo "https://yourdomain.com/storage/photos/users/user_xxx.png"
echo ""
echo "If still getting 404, check:"
echo "1. Apache/Nginx configuration allows following symlinks"
echo "2. APP_URL in .env is correct"
echo "3. Web server has read permissions on storage/app/public"
echo ""

