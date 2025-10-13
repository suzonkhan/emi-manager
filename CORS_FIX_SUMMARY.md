# 🚨 CORS Issue Fix Summary

## The Problem

```
Access to fetch at 'https://www.imelocker.com/api/reports/generate' 
from origin 'https://imelocker.com' has been blocked by CORS policy
```

**Why this happens:**
- Frontend: `https://imelocker.com`
- Backend API: `https://www.imelocker.com`
- These are different origins → Browser blocks for security

---

## ✅ What We Fixed (Local)

### 1. Backend Configuration
**File**: `config/cors.php`
- ✅ Made CORS origins configurable via `.env`
- ✅ Supports both `imelocker.com` and `www.imelocker.com`

### 2. Frontend Configuration  
**File**: `emi-manager-frontend/.env`
- ✅ Changed from `http://127.0.0.1:8000/api`
- ✅ To `https://www.imelocker.com/api`
- ✅ Rebuilt with `npm run build`

### 3. Documentation Created
- ✅ `PRODUCTION_DEPLOYMENT.md` - Complete deployment guide
- ✅ `deploy-cors-fix.sh` - Automated deployment script
- ✅ `README.md` - Updated with production issues section

---

## 🎯 What You Need to Do on Production Server

### Step 1: Update Production `.env` File

SSH to your server and edit Laravel's `.env` file:

```bash
cd /var/www/your-laravel-backend
nano .env
```

**Add these lines** (or update if they exist):

```bash
# CORS Configuration
CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com

# Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=imelocker.com,www.imelocker.com

# Session Configuration
SESSION_DOMAIN=.imelocker.com
SESSION_DRIVER=database
SESSION_LIFETIME=120
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

# URLs
APP_URL=https://www.imelocker.com
FRONTEND_URL=https://imelocker.com
```

Save and exit (`Ctrl+X`, then `Y`, then `Enter`).

---

### Step 2: Clear Laravel Caches

```bash
cd /var/www/your-laravel-backend

php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan optimize:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan optimize
```

---

### Step 3: Restart Services

```bash
# Restart PHP-FPM (adjust version if needed)
sudo systemctl restart php8.3-fpm

# Restart Nginx
sudo systemctl restart nginx

# OR if using Apache:
# sudo systemctl restart apache2
```

---

### Step 4: Upload New Frontend Build

1. On your local machine, the frontend is already built at:
   ```
   c:\laragon\www\emi-manager-frontend\dist\
   ```

2. Upload this `dist/` folder to your production server's frontend location

3. If using Nginx, ensure it points to the `dist/` folder:
   ```nginx
   server {
       listen 443 ssl http2;
       server_name imelocker.com www.imelocker.com;
       
       root /var/www/frontend/dist;
       index index.html;
       
       location / {
           try_files $uri $uri/ /index.html;
       }
   }
   ```

---

### Step 5: Verify the Fix

**Option A: Using Browser**
1. Open `https://imelocker.com`
2. Open DevTools (F12) → Network tab
3. Try any API call (like loading dashboard)
4. Check response headers - should see `Access-Control-Allow-Origin: https://imelocker.com`

**Option B: Using Command Line**
```bash
curl -I -X OPTIONS https://www.imelocker.com/api/reports/generate \
  -H "Origin: https://imelocker.com" \
  -H "Access-Control-Request-Method: POST"
```

**Expected response headers:**
```
Access-Control-Allow-Origin: https://imelocker.com
Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS
Access-Control-Allow-Headers: *
```

---

## 🚀 Automated Deployment (Recommended)

Instead of manual steps 2-3, you can use the automated script:

1. Upload `deploy-cors-fix.sh` to your Laravel backend directory
2. Run it:
```bash
cd /var/www/your-laravel-backend
bash deploy-cors-fix.sh
```

The script will:
- ✅ Check your `.env` configuration
- ✅ Clear all caches
- ✅ Rebuild optimized files
- ✅ Restart services
- ✅ Test CORS configuration
- ✅ Show results

---

## 🔍 Troubleshooting

### If CORS error persists:

1. **Check `.env` file syntax**
   - No spaces around `=`
   - Comma-separated values, no spaces
   - Correct: `CORS_ALLOWED_ORIGINS=https://a.com,https://b.com`
   - Wrong: `CORS_ALLOWED_ORIGINS = https://a.com, https://b.com`

2. **Verify config was cleared**
   ```bash
   php artisan config:clear
   php artisan config:cache
   ```

3. **Check Laravel logs**
   ```bash
   tail -f storage/logs/laravel.log
   ```

4. **Check web server logs**
   ```bash
   # Nginx
   tail -f /var/log/nginx/error.log
   
   # Apache
   tail -f /var/log/apache2/error.log
   ```

5. **Verify PHP-FPM restarted**
   ```bash
   sudo systemctl status php8.3-fpm
   ```

6. **Check if Cloudflare/proxy is stripping headers**
   - If using Cloudflare, check SSL/TLS settings
   - Ensure "Proxy status" is enabled (orange cloud)

---

## 📋 Checklist

Before marking as complete, verify:

- [ ] Production `.env` has `CORS_ALLOWED_ORIGINS`
- [ ] Production `.env` has `SANCTUM_STATEFUL_DOMAINS`
- [ ] Production `.env` has `SESSION_DOMAIN=.imelocker.com`
- [ ] Ran `php artisan config:clear` on production
- [ ] Ran `php artisan config:cache` on production  
- [ ] Restarted PHP-FPM service
- [ ] Restarted Nginx/Apache service
- [ ] Uploaded new frontend `dist/` folder
- [ ] Tested in browser - no CORS errors
- [ ] Checked Network tab - CORS headers present
- [ ] All API calls working (dashboard, reports, etc.)

---

## 📞 Need Help?

If issues persist after all steps:

1. Share the output of:
   ```bash
   curl -v -X OPTIONS https://www.imelocker.com/api/reports/generate \
     -H "Origin: https://imelocker.com"
   ```

2. Check Laravel logs:
   ```bash
   tail -n 50 storage/logs/laravel.log
   ```

3. Verify both domains resolve to same server:
   ```bash
   ping imelocker.com
   ping www.imelocker.com
   ```

---

**Last Updated**: October 14, 2025  
**Status**: Ready for Production Deployment
