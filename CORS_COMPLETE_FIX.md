# 🚨 CORS Issue Not Fixed - Complete Solution

## Current Status
❌ CORS headers still not present after running deploy script  
❌ `.htaccess` files missing CORS headers  
⚠️ PHP-FPM may not be restarted properly on cPanel

---

## 🎯 Root Causes

### 1. Missing `.htaccess` CORS Headers
Laravel's CORS middleware runs in PHP, but Apache needs to pass through OPTIONS requests first.

### 2. `.env` Missing CORS Configuration
The application can't read CORS settings if they're not in `.env`.

### 3. PHP-FPM Not Restarted
Config changes don't take effect until PHP-FPM restarts.

---

## ✅ Complete Fix (Step by Step)

### Step 1: Update `.htaccess` Files

You need TWO `.htaccess` files:

#### A. Root `.htaccess` (at `/home/imelocker/data.imelocker.com/.htaccess`)

Create or replace with:

```apache
<IfModule mod_rewrite.c>
    RewriteEngine On
 
    # Redirect all requests to the public folder
    RewriteRule ^(.*)$ public/$1 [L]
</IfModule>

# CORS Headers - Allow cross-origin requests
<IfModule mod_headers.c>
    # Handle preflight OPTIONS requests
    Header always set Access-Control-Allow-Origin "*"
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept, Origin, X-XSRF-TOKEN"
    Header always set Access-Control-Expose-Headers "Authorization"
    Header always set Access-Control-Allow-Credentials "true"
    Header always set Access-Control-Max-Age "3600"
</IfModule>
```

#### B. Public `.htaccess` (at `/home/imelocker/data.imelocker.com/public/.htaccess`)

Replace with:

```apache
<IfModule mod_rewrite.c>
    <IfModule mod_negotiation.c>
        Options -MultiViews -Indexes
    </IfModule>

    RewriteEngine On

    # Handle Authorization Header
    RewriteCond %{HTTP:Authorization} .
    RewriteRule .* - [E=HTTP_AUTHORIZATION:%{HTTP:Authorization}]

    # Handle X-XSRF-Token Header
    RewriteCond %{HTTP:x-xsrf-token} .
    RewriteRule .* - [E=HTTP_X_XSRF_TOKEN:%{HTTP:X-XSRF-Token}]

    # Redirect Trailing Slashes If Not A Folder...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_URI} (.+)/$
    RewriteRule ^ %1 [L,R=301]

    # Send Requests To Front Controller...
    RewriteCond %{REQUEST_FILENAME} !-d
    RewriteCond %{REQUEST_FILENAME} !-f
    RewriteRule ^ index.php [L]
</IfModule>

# CORS Headers - Critical for cross-origin API requests
<IfModule mod_headers.c>
    # Set CORS headers for all responses
    SetEnvIf Origin "^https?://(www\.)?(imelocker\.com)$" AccessControlAllowOrigin=$0
    Header always set Access-Control-Allow-Origin %{AccessControlAllowOrigin}e env=AccessControlAllowOrigin
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept, Origin, X-XSRF-TOKEN, X-CSRF-TOKEN"
    Header always set Access-Control-Expose-Headers "Authorization"
    Header always set Access-Control-Allow-Credentials "true"
    Header always set Access-Control-Max-Age "86400"
    
    # Handle preflight OPTIONS requests
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=204,L]
</IfModule>

# Security Headers
<IfModule mod_headers.c>
    Header always set X-Frame-Options "SAMEORIGIN"
    Header always set X-Content-Type-Options "nosniff"
    Header always set X-XSS-Protection "1; mode=block"
</IfModule>
```

---

### Step 2: Update `.env` File

SSH to server and edit:

```bash
cd /home/imelocker/data.imelocker.com
nano .env
```

**Add these lines** (if not already present):

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

# Force HTTPS
APP_FORCE_HTTPS=true
```

Save and exit (`Ctrl+X`, `Y`, `Enter`).

---

### Step 3: Clear Laravel Caches

```bash
cd /home/imelocker/data.imelocker.com

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Rebuild cache
php artisan config:cache
php artisan route:cache
php artisan optimize
```

---

### Step 4: Restart PHP-FPM (cPanel Methods)

Choose ONE method:

#### Method A: cPanel Interface (Most Reliable)
1. Login to cPanel
2. Go to **"MultiPHP Manager"**
3. Find `data.imelocker.com`
4. Switch PHP version (e.g., from 8.3 to 8.2, then back to 8.3)
5. This forces PHP-FPM restart

#### Method B: Restart Apache via cPanel
1. Login to cPanel
2. Go to **"Restart Services"** or search for **"Apache"**
3. Click **"Restart Apache"**

#### Method C: Terminal (if available)
```bash
# Reload Apache
/scripts/restartsrv_httpd

# Or kill PHP processes to force reload
killall -USR2 lsphp
```

#### Method D: Wait 5-10 Minutes
- cPanel auto-reloads PHP-FPM after detecting changes
- Not recommended if urgent

---

### Step 5: Verify `.htaccess` is Active

Test if `mod_headers` is enabled:

```bash
# Check if CORS headers are returned
curl -I https://www.imelocker.com/api/dashboard/stats

# Should show:
# access-control-allow-origin: https://imelocker.com (or similar)
```

If NO headers appear:

1. Check Apache error log:
```bash
tail -f /home/imelocker/logs/error_log
```

2. Verify `mod_headers` is enabled (contact hosting support if needed)

3. Check `.htaccess` syntax:
```bash
apachectl configtest
```

---

### Step 6: Test CORS

#### Test A: Command Line
```bash
curl -I -X OPTIONS https://www.imelocker.com/api/dashboard/stats \
  -H "Origin: https://imelocker.com" \
  -H "Access-Control-Request-Method: GET"
```

**Expected output:**
```
HTTP/2 200
access-control-allow-origin: https://imelocker.com
access-control-allow-methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
access-control-allow-headers: Content-Type, Authorization, X-Requested-With, Accept, Origin, X-XSRF-TOKEN, X-CSRF-TOKEN
access-control-allow-credentials: true
```

#### Test B: Browser
1. Open `https://imelocker.com`
2. Open DevTools (F12) → **Network** tab
3. Try loading dashboard
4. Click any API request
5. Check **Response Headers** for `access-control-allow-origin`

#### Test C: Check Specific Request
In browser console:
```javascript
fetch('https://www.imelocker.com/api/dashboard/stats', {
  method: 'GET',
  headers: {
    'Accept': 'application/json',
    'Authorization': 'Bearer YOUR_TOKEN'
  }
})
.then(r => r.json())
.then(data => console.log('Success:', data))
.catch(err => console.error('Error:', err));
```

---

## 🔧 Troubleshooting

### Issue 1: "CORS headers not found"

**Cause**: `.htaccess` not being read OR `mod_headers` not enabled

**Solution**:
```bash
# Check if .htaccess exists and has correct permissions
ls -la /home/imelocker/data.imelocker.com/.htaccess
ls -la /home/imelocker/data.imelocker.com/public/.htaccess

# Should show: -rw-r--r--
# Fix permissions if needed:
chmod 644 /home/imelocker/data.imelocker.com/.htaccess
chmod 644 /home/imelocker/data.imelocker.com/public/.htaccess
```

**Contact hosting support to enable `mod_headers`:**
```
Please enable Apache mod_headers module for my account
```

---

### Issue 2: "Still getting CORS error after all steps"

**Cause**: Cloudflare or other proxy stripping headers

**Solution**:
1. If using Cloudflare:
   - Go to Cloudflare Dashboard
   - **SSL/TLS** → Set to **Full** or **Full (Strict)**
   - **Rules** → Check if any are blocking headers
   
2. Check if domain has proxy/CDN:
```bash
dig www.imelocker.com
# If shows Cloudflare IPs, headers might be stripped
```

3. Bypass test (temporary):
   - Add to `/etc/hosts`: `SERVER_IP www.imelocker.com`
   - Test directly to server

---

### Issue 3: "403 Forbidden on .htaccess"

**Cause**: `AllowOverride` not enabled in Apache config

**Solution**:
Contact hosting support:
```
Please enable AllowOverride All for my Laravel application 
at /home/imelocker/data.imelocker.com
```

---

### Issue 4: "PHP warning in php.ini"

**Cause**: cPanel's PHP configuration has syntax error (non-critical)

**Solution**: Ignore this warning - it doesn't affect functionality. Laravel commands still work.

---

## 📋 Final Checklist

Before testing:

- [ ] Root `.htaccess` created with CORS headers
- [ ] Public `.htaccess` updated with CORS headers
- [ ] `.env` has `CORS_ALLOWED_ORIGINS`
- [ ] `.env` has `SESSION_DOMAIN=.imelocker.com`
- [ ] Ran `php artisan config:clear`
- [ ] Ran `php artisan config:cache`
- [ ] Restarted PHP-FPM via cPanel
- [ ] OR restarted Apache
- [ ] OR waited 5-10 minutes
- [ ] Tested with curl (shows CORS headers)
- [ ] Tested in browser (no CORS errors)
- [ ] API calls working from https://imelocker.com

---

## 🚀 Quick Commands Summary

```bash
# 1. Upload new .htaccess files (from local)
scp .htaccess imelocker@server2:/home/imelocker/data.imelocker.com/
scp public/.htaccess imelocker@server2:/home/imelocker/data.imelocker.com/public/

# 2. SSH and edit .env
cd /home/imelocker/data.imelocker.com
nano .env
# Add: CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com

# 3. Clear caches
php artisan config:clear && php artisan config:cache

# 4. Test
curl -I https://www.imelocker.com/api/dashboard/stats

# 5. If headers not showing, restart Apache via cPanel
```

---

## 📞 If Still Not Working

1. **Check Apache error log**:
```bash
tail -f ~/logs/error_log
```

2. **Verify mod_headers is enabled**:
```bash
php -m | grep headers
```

3. **Contact cPanel support** with this message:
```
Hi, I need help enabling mod_headers for my Laravel application.
Domain: data.imelocker.com
Issue: CORS headers not being set by .htaccess
Please enable mod_headers and ensure AllowOverride All is set.
```

4. **Alternative: Use Nginx** (if Apache doesn't support mod_headers on your plan)

---

**Last Updated**: October 14, 2025  
**Status**: Ready for Deployment  
**Priority**: HIGH - CORS blocking all API requests
