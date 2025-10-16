# DomPDF Production Server Fix

## Problem
PDF generation is failing on the production server with:
```
Class "Barryvdh\DomPDF\Facade\Pdf" not found
```

This happens because the `barryvdh/laravel-dompdf` package is not installed on the production server.

## Solution

### Step 1: SSH into Your Production Server
```bash
ssh your-username@your-server.com
cd /home/imelocker/data.imelocker.com
```

### Step 2: Install Composer Dependencies
Run this command to install all missing packages:
```bash
composer install --no-dev --optimize-autoloader
```

**Explanation:**
- `--no-dev` = Don't install development packages (like debugbar, telescope)
- `--optimize-autoloader` = Optimize the autoloader for better performance

### Step 3: Clear Laravel Cache
After installing, clear all caches:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

### Step 4: Verify Installation
Check if the package is installed:
```bash
composer show barryvdh/laravel-dompdf
```

You should see version information if it's installed correctly.

### Step 5: Test PDF Generation
Try downloading a PDF report from your frontend. It should work now!

---

## Alternative: If composer install doesn't work

If you get permission errors or composer doesn't work, you can:

### Option A: Install Locally and Upload
1. On your local machine (Windows):
   ```powershell
   cd c:\laragon\www\emi-manager
   composer install --no-dev --optimize-autoloader
   ```

2. Upload the entire `vendor` folder to your server using FTP/SFTP

3. Upload the `composer.lock` file as well

### Option B: Check composer.lock
Make sure `composer.lock` exists on your server and matches your local version.

---

## Why This Happened

The package is in `composer.json` (requirement list) but wasn't actually installed on the server. This usually happens when:

1. You ran `composer install` locally but didn't push `vendor` folder (which is correct - vendor should be in .gitignore)
2. You didn't run `composer install` on the production server after deployment
3. The production server's `composer install` failed silently

## Prevention for Future Deployments

Always run these commands after deploying code to production:
```bash
composer install --no-dev --optimize-autoloader
php artisan config:clear
php artisan cache:clear
php artisan migrate --force
php artisan optimize
```

Consider using a deployment script or CI/CD pipeline to automate this.

---

## Troubleshooting

### If you still get errors after installing:

1. **Check PHP Extensions**
   DomPDF requires these PHP extensions:
   ```bash
   php -m | grep -E 'dom|mbstring|gd'
   ```
   
   If missing, install them:
   ```bash
   # For Ubuntu/Debian
   sudo apt-get install php-xml php-mbstring php-gd
   
   # For CentOS/RHEL
   sudo yum install php-xml php-mbstring php-gd
   ```

2. **Check File Permissions**
   Make sure Laravel can write to storage:
   ```bash
   chmod -R 775 storage bootstrap/cache
   chown -R www-data:www-data storage bootstrap/cache
   ```

3. **Check Memory Limit**
   PDF generation can be memory-intensive. Check your `php.ini`:
   ```ini
   memory_limit = 256M
   ```

4. **Enable Error Logging**
   Check Laravel logs:
   ```bash
   tail -f storage/logs/laravel.log
   ```

---

## Database Errors (Bonus Fix)

I also noticed these errors in your logs:

### 1. Database Connection Issue (October 9, 2025)
```
Access denied for user 'imelocker_dev'@'localhost'
```
**Fix:** Update your production `.env` file with correct database credentials:
```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_production_db_name
DB_USERNAME=your_production_db_user
DB_PASSWORD=your_production_db_password
```

### 2. Missing Column 'plain_password' (October 12, 2025)
```
Column not found: 1054 Unknown column 'plain_password'
```
**Fix:** Run migrations on production server:
```bash
php artisan migrate --force
```

### 3. PDO Class Not Found (October 13, 2025)
```
Class "PDO" not found
```
**Fix:** Install PHP PDO extension:
```bash
# Ubuntu/Debian
sudo apt-get install php-mysql php-pdo

# CentOS/RHEL
sudo yum install php-mysqlnd

# Then restart your web server
sudo systemctl restart apache2  # or nginx
```

---

## Quick Commands Checklist for Production

After any deployment, run these in order:

```bash
# 1. Install dependencies
composer install --no-dev --optimize-autoloader

# 2. Run migrations
php artisan migrate --force

# 3. Clear all caches
php artisan optimize:clear

# 4. Optimize for production
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart queue workers (if using queues)
php artisan queue:restart
```

---

## Contact Info
If you continue to have issues, check:
- Server PHP version: `php -v` (should be 8.2 or higher)
- Server Laravel version: `php artisan --version`
- Installed packages: `composer show`
