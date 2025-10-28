# Storage 404 Fix Guide

## 🔥 QUICK FIX (Copy and run on your production server)

```bash
cd /path/to/your/project

# 1. Remove old symlink
rm public/storage

# 2. Create new symlink
php artisan storage:link

# 3. Fix permissions
chmod -R 775 storage/app/public
chmod -R 775 public/storage

# 4. Clear cache
php artisan config:clear
php artisan cache:clear

# 5. Test
ls -la public/storage/photos/users/
```

If you get "Permission denied", use sudo:
```bash
sudo php artisan storage:link
```

## Problem
Images are stored in `storage/app/public/photos` but return 404 when accessed via `/storage/photos/...`

## Root Causes
1. **Symlink not created on production server**
2. **Symlink broken or incorrect permissions**
3. **Web server not configured to follow symlinks**
4. **Files are in the wrong storage location**

## Solutions

### Solution 1: Recreate the Storage Symlink

```bash
# Remove the old symlink if it exists
rm public/storage

# Create the symlink
php artisan storage:link
```

### Solution 2: Check File Permissions

```bash
# Ensure proper permissions
chmod -R 775 storage/app/public
chmod -R 775 public/storage
chown -R www-data:www-data storage/app/public
chown -R www-data:www-data public/storage
```

### Solution 3: Configure Apache to Follow Symlinks

Edit your `.htaccess` file in the `public` directory:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
    
    # Allow symlinks
    Options +FollowSymLinks
    
    # Existing rules...
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteRule ^ index.php [L]
</IfModule>
```

Or better, add this to your Apache VirtualHost configuration:

```apache
<Directory /path/to/your/project/public>
    Options FollowSymLinks
    AllowOverride All
    Require all granted
</Directory>
```

### Solution 4: Configure Nginx (if using Nginx)

Add this to your Nginx server block:

```nginx
location /storage {
    alias /path/to/your/project/storage/app/public;
    try_files $uri $uri/ =404;
}
```

### Solution 5: Verify the Storage Link

```bash
# Check if symlink exists
ls -la public/ | grep storage

# Should show:
# storage -> /absolute/path/to/storage/app/public
```

### Solution 6: Force Recreate on Production

If the symlink exists but doesn't work:

```bash
cd /path/to/your/project

# Remove old link
rm -f public/storage

# Create new link
php artisan storage:link

# Verify
ls -la public/storage
```

### Solution 7: Check Your APP_URL in .env

Make sure your production `.env` has the correct URL:

```env
APP_URL=https://apiimelocker.com
```

### Solution 8: Test the Link

After creating the symlink, test it:

```bash
# On server
cd public/storage/photos/users
ls -la
```

You should see your uploaded images.

## Debugging Steps

1. **Check if files exist:**
   ```bash
   ls -la storage/app/public/photos/users/
   ```

2. **Check if symlink exists:**
   ```bash
   ls -la public/ | grep storage
   ```

3. **Check if symlink points to correct location:**
   ```bash
   readlink -f public/storage
   ```

4. **Test accessing a file directly:**
   ```bash
   curl https://apiimelocker.com/storage/photos/users/user_xxx.png
   ```

5. **Check web server error logs:**
   ```bash
   tail -f /var/log/apache2/error.log
   # or
   tail -f /var/log/nginx/error.log
   ```

## Quick Fix Commands (Run these on production)

```bash
cd /path/to/your/project

# Recreate symlink
php artisan storage:link

# Fix permissions
chmod -R 775 storage/app/public
chown -R www-data:www-data storage/app/public

# Clear cache
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Test
php artisan tinker
# Then run:
Storage::disk('public')->files('photos/users');
```

