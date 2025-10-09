# CORS Fix - Deployment Checklist

## Pre-Deployment (Already Completed ✅)
- [x] Created `config/cors.php` with allowed origins
- [x] Updated `bootstrap/app.php` with CORS middleware
- [x] Updated `config/sanctum.php` with production domains
- [x] Cleared local config cache
- [x] Created documentation

## Production Deployment

### Step 1: Update Code
```bash
cd /path/to/emi-manager
git pull origin master
```

### Step 2: Update .env File
```bash
nano .env
```

Add/update these lines:
```env
SANCTUM_STATEFUL_DOMAINS=imelocker.com,www.imelocker.com
SESSION_DOMAIN=.imelocker.com
APP_URL=https://api.imelocker.com
FRONTEND_URL=https://www.imelocker.com
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax
```

### Step 3: Clear All Caches
```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
php artisan view:clear
php artisan route:clear
```

### Step 4: Restart Services
```bash
# PHP-FPM
sudo systemctl restart php8.3-fpm

# Nginx
sudo systemctl restart nginx

# Or Apache (if using Apache)
# sudo systemctl restart apache2
```

### Step 5: Verify Configuration
```bash
# Check if config is loaded correctly
php artisan tinker
>>> config('cors.allowed_origins');
# Should show: ['https://www.imelocker.com', 'https://imelocker.com', ...]

>>> config('sanctum.stateful');
# Should include: 'imelocker.com', 'www.imelocker.com'
```

### Step 6: Test CORS
```bash
# Test OPTIONS preflight
curl -H "Origin: https://www.imelocker.com" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: Content-Type, Authorization" \
     -X OPTIONS \
     --verbose \
     https://api.imelocker.com/api/auth/login

# Expected: 200/204 response with CORS headers
```

### Step 7: Test Frontend
- [ ] Open https://www.imelocker.com
- [ ] Open browser DevTools (F12)
- [ ] Go to Console tab
- [ ] Try to login
- [ ] Verify NO CORS errors appear
- [ ] Check Network tab - API requests should have CORS headers
- [ ] Verify login succeeds

## Verification Checklist

### Backend API
- [ ] `config/cors.php` file exists
- [ ] `bootstrap/app.php` has CORS middleware
- [ ] `config/sanctum.php` has production domains
- [ ] `.env` has correct SANCTUM_STATEFUL_DOMAINS
- [ ] `.env` has correct SESSION_DOMAIN (with leading dot)
- [ ] All caches cleared
- [ ] PHP-FPM restarted
- [ ] Nginx/Apache restarted

### Network Responses
Check these headers in browser DevTools Network tab:

- [ ] `Access-Control-Allow-Origin: https://www.imelocker.com`
- [ ] `Access-Control-Allow-Credentials: true`
- [ ] `Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS`
- [ ] `Access-Control-Allow-Headers: ...` (includes Content-Type, Authorization)

### Frontend Application
- [ ] Login page loads without errors
- [ ] Can submit login form
- [ ] No CORS errors in console
- [ ] API requests succeed
- [ ] Cookies are set (check Application > Cookies in DevTools)
- [ ] Authentication token received
- [ ] Can access protected routes

## Rollback Plan (If Needed)

If CORS is causing issues:

```bash
# 1. Remove CORS middleware from bootstrap/app.php
# 2. Restore old sanctum.php if needed
# 3. Clear caches again
php artisan config:clear
php artisan cache:clear

# 4. Restart services
sudo systemctl restart php8.3-fpm
sudo systemctl restart nginx
```

## Troubleshooting

### Issue: Still getting CORS errors
**Solution:**
```bash
# Clear ALL caches
php artisan optimize:clear
php artisan config:clear
php artisan cache:clear

# Restart PHP-FPM
sudo systemctl restart php8.3-fpm

# Clear browser cache or try incognito mode
```

### Issue: Cookies not being set
**Check:**
- [ ] SESSION_DOMAIN=.imelocker.com (with leading dot)
- [ ] SESSION_SECURE_COOKIE=true
- [ ] Frontend using HTTPS
- [ ] API using HTTPS

### Issue: 419 CSRF Token Mismatch
**Check:**
- [ ] SANCTUM_STATEFUL_DOMAINS includes your frontend domain
- [ ] Frontend calls `/sanctum/csrf-cookie` before login
- [ ] Cookies are being sent with requests

## Support Contacts

If issues persist:
1. Check logs: `/var/log/nginx/error.log`
2. Check PHP logs: `/var/log/php8.3-fpm.log`
3. Check Laravel logs: `storage/logs/laravel.log`

## Completion Sign-off

Deployed by: ________________
Date: ________________
Time: ________________
Verified by: ________________

Notes:
_____________________________________________
_____________________________________________
_____________________________________________
