# Production Deployment Guide

## CORS Issue Fix

### Issue
```
Access to fetch at 'https://www.imelocker.com/api/reports/generate' from origin 'https://imelocker.com' 
has been blocked by CORS policy: Response to preflight request doesn't pass access control check: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

### Root Cause
Laravel's config cache on the production server is stale, or the production `.env` file is missing required CORS/Sanctum variables.

---

## Step-by-Step Fix

### 1. Update Production `.env` File

Add these variables to your **production server's** `.env` file (located at the Laravel backend root):

```bash
# CORS Configuration
CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com

# Sanctum Stateful Domains (no protocol, comma-separated)
SANCTUM_STATEFUL_DOMAINS=imelocker.com,www.imelocker.com

# Session Configuration (with leading dot for subdomain support)
SESSION_DOMAIN=.imelocker.com
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# Application URLs
APP_URL=https://www.imelocker.com
FRONTEND_URL=https://imelocker.com
```

### 2. Clear All Laravel Caches on Production Server

SSH into your production server and run:

```bash
cd /path/to/your/laravel/backend

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear
php artisan optimize:clear

# Rebuild optimized cache
php artisan config:cache
php artisan route:cache
php artisan optimize
```

### 3. Restart PHP-FPM and Web Server

```bash
# For PHP-FPM (adjust version if needed)
sudo systemctl restart php8.3-fpm

# For Nginx
sudo systemctl restart nginx

# OR for Apache
sudo systemctl restart apache2
```

### 4. Verify CORS Headers

Test if CORS headers are now present:

```bash
curl -I -X OPTIONS https://www.imelocker.com/api/reports/generate \
  -H "Origin: https://imelocker.com" \
  -H "Access-Control-Request-Method: POST"
```

**Expected response should include:**
```
Access-Control-Allow-Origin: https://imelocker.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: *
```

---

## Alternative Solutions

### Option A: Use Single Domain (Recommended)

Redirect one domain to the other to avoid CORS entirely:

**In Nginx:**
```nginx
server {
    listen 443 ssl http2;
    server_name imelocker.com;
    
    # Redirect to www version
    return 301 https://www.imelocker.com$request_uri;
}
```

**Then update frontend `.env`:**
```bash
VITE_REACT_APP_API_URL=https://www.imelocker.com/api
```

### Option B: Wildcard CORS (Less Secure)

If you control both domains, you can use pattern matching:

```php
// config/cors.php
'allowed_origins_patterns' => ['/^https:\/\/(www\.)?imelocker\.com$/'],
```

---

## Frontend Build & Deploy

After backend fixes, ensure frontend is built with correct API URL:

```bash
cd c:\laragon\www\emi-manager-frontend

# Update .env
echo "VITE_REACT_APP_API_URL=https://www.imelocker.com/api" > .env

# Rebuild
npm run build

# Upload dist/ folder to production server
```

---

## Verification Checklist

- [ ] Production `.env` has CORS_ALLOWED_ORIGINS
- [ ] Production `.env` has SANCTUM_STATEFUL_DOMAINS
- [ ] Production `.env` has SESSION_DOMAIN with leading dot
- [ ] Ran `php artisan config:clear` on production
- [ ] Ran `php artisan config:cache` on production
- [ ] Restarted PHP-FPM
- [ ] Restarted web server (Nginx/Apache)
- [ ] Frontend built with HTTPS API URL
- [ ] Uploaded new frontend dist/ folder
- [ ] Tested in browser (check Network tab for CORS headers)

---

## Common Mistakes

1. **Config cache not cleared** - Laravel caches config, must clear it
2. **PHP-FPM not restarted** - Changes won't take effect without restart
3. **Frontend .env not updated** - Still using HTTP or localhost
4. **Mismatched domains** - Frontend on `imelocker.com`, API on `www.imelocker.com`
5. **SESSION_DOMAIN missing leading dot** - Should be `.imelocker.com` not `imelocker.com`

---

## Testing Commands

### Test from command line:
```bash
# Test CORS preflight
curl -X OPTIONS https://www.imelocker.com/api/reports/generate \
  -H "Origin: https://imelocker.com" \
  -H "Access-Control-Request-Method: POST" \
  -v

# Test actual request
curl -X GET https://www.imelocker.com/api/dashboard/stats \
  -H "Origin: https://imelocker.com" \
  -H "Accept: application/json" \
  -v
```

### Check in browser:
1. Open https://imelocker.com
2. Open DevTools (F12) → Network tab
3. Try an API request
4. Check response headers for `Access-Control-Allow-Origin`

---

## Need Help?

If the issue persists after following all steps:

1. Check Laravel logs: `storage/logs/laravel.log`
2. Check web server error logs
3. Verify `.env` file syntax (no spaces around `=`)
4. Ensure both domains point to the same server
5. Check if cloudflare/proxy is stripping CORS headers
