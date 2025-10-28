# Production Deployment Notes

## Issue Fixed: Storage 404 Errors

### Problem
Images returning 404 errors because the root `.htaccess` was excluding `/storage/` from redirecting to `public/`.

### Solution
Updated root `.htaccess` to properly handle storage requests.

## Files to Upload to Production

1. **Root `.htaccess`** - Replace the existing one
2. **Ensure storage symlink exists:**
   ```bash
   ssh into production
   cd /path/to/your/project
   php artisan storage:link
   ```

## Complete Deployment Checklist

```bash
# 1. Upload files (including the new .htaccess)

# 2. SSH into production
ssh user@apiimelocker.com

# 3. Navigate to project
cd /var/www/html/your-project  # adjust path

# 4. Remove old symlink if it exists
rm public/storage

# 5. Create new symlink
php artisan storage:link

# 6. Fix permissions
chmod -R 775 storage/app/public
chmod -R 775 public/storage

# 7. Clear cache
php artisan config:clear
php artisan cache:clear
php artisan route:clear

# 8. Verify
ls -la public/storage
```

## Test After Deployment

Visit:
```
https://apiimelocker.com/storage/photos/users/user_1761620836_69003364992b4.png
```

Should display the image instead of 404.

## Verification

Run the diagnostic:
```bash
php storage-check.php
```

Or access via browser:
```
https://apiimelocker.com/storage-check.php
```

