# CORS Configuration Fix for Production

## Issue
Frontend (https://www.imelocker.com) was unable to access the API (https://api.imelocker.com) due to CORS policy blocking the requests.

**Error:**
```
Access to fetch at 'https://api.imelocker.com/api/auth/login' from origin 'https://www.imelocker.com' 
has been blocked by CORS policy: Response to preflight request doesn't pass access control check: 
No 'Access-Control-Allow-Origin' header is present on the requested resource.
```

## Solution Applied

### 1. Created CORS Configuration File
**File:** `config/cors.php`

```php
<?php

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    
    'allowed_methods' => ['*'],
    
    'allowed_origins' => [
        'https://www.imelocker.com',
        'https://imelocker.com',
        'http://localhost:5173',
        'http://localhost:3000',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:3000',
    ],
    
    'allowed_origins_patterns' => [],
    
    'allowed_headers' => ['*'],
    
    'exposed_headers' => [],
    
    'max_age' => 0,
    
    'supports_credentials' => true,
];
```

### 2. Updated Bootstrap Configuration
**File:** `bootstrap/app.php`

Added CORS middleware to API routes:

```php
->withMiddleware(function (Middleware $middleware): void {
    $middleware->api(prepend: [
        \Illuminate\Http\Middleware\HandleCors::class,
    ]);
})
```

### 3. Updated Sanctum Configuration
**File:** `config/sanctum.php`

Added production domains to stateful domains:

```php
'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
    '%s%s%s',
    'localhost,localhost:3000,localhost:5173,127.0.0.1,127.0.0.1:8000,127.0.0.1:5173,::1,',
    'imelocker.com,www.imelocker.com,',
    Sanctum::currentApplicationUrlWithPort(),
))),
```

## Environment Configuration

### Required .env Variables

Add these to your production `.env` file:

```env
# CORS & Sanctum Configuration
SANCTUM_STATEFUL_DOMAINS=imelocker.com,www.imelocker.com,localhost,localhost:5173,127.0.0.1,127.0.0.1:5173

# Session Domain (for cookies to work across subdomains)
SESSION_DOMAIN=.imelocker.com

# Application URLs
APP_URL=https://api.imelocker.com
FRONTEND_URL=https://www.imelocker.com

# For local development, use:
# SESSION_DOMAIN=localhost
# APP_URL=http://localhost:8000
# FRONTEND_URL=http://localhost:5173
```

### Important Notes

1. **SESSION_DOMAIN with leading dot:** 
   - `.imelocker.com` allows cookies to work on both `api.imelocker.com` and `www.imelocker.com`
   - Without the dot, cookies won't be shared across subdomains

2. **SANCTUM_STATEFUL_DOMAINS:**
   - Must include both `imelocker.com` and `www.imelocker.com`
   - Should NOT include the protocol (`http://` or `https://`)

3. **supports_credentials: true:**
   - Allows cookies and authentication headers to be sent with requests
   - Required for Sanctum token authentication

## Testing

### 1. Clear Laravel Cache
```bash
php artisan config:clear
php artisan cache:clear
php artisan route:clear
```

### 2. Test CORS Headers
```bash
curl -H "Origin: https://www.imelocker.com" \
     -H "Access-Control-Request-Method: POST" \
     -H "Access-Control-Request-Headers: Content-Type, Authorization" \
     -X OPTIONS \
     --verbose \
     https://api.imelocker.com/api/auth/login
```

**Expected Response Headers:**
```
Access-Control-Allow-Origin: https://www.imelocker.com
Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS
Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With
Access-Control-Allow-Credentials: true
```

### 3. Test Frontend Login
1. Open https://www.imelocker.com
2. Try to login
3. Check browser console - should NOT see CORS errors
4. Check Network tab - API requests should succeed

## Allowed Origins

### Production
- ✅ `https://www.imelocker.com` - Main frontend
- ✅ `https://imelocker.com` - Without www redirect
- ✅ `https://api.imelocker.com` - API domain (automatically via Sanctum)

### Development
- ✅ `http://localhost:5173` - Vite dev server
- ✅ `http://localhost:3000` - Alternative dev port
- ✅ `http://127.0.0.1:5173` - IPv4 localhost
- ✅ `http://127.0.0.1:3000` - IPv4 localhost alt

## Common Issues & Solutions

### Issue 1: "Credentials flag is 'true', but 'Access-Control-Allow-Credentials' header is ''"
**Solution:** Ensure `supports_credentials: true` in `config/cors.php`

### Issue 2: Cookies not being sent with requests
**Solution:** 
- Set `SESSION_DOMAIN=.imelocker.com` (with leading dot)
- Ensure frontend uses `credentials: 'include'` in fetch/axios

### Issue 3: Still getting CORS errors after changes
**Solution:**
```bash
php artisan config:clear
php artisan cache:clear
php artisan optimize:clear
# Restart PHP-FPM or web server
```

### Issue 4: Preflight OPTIONS requests failing
**Solution:** Ensure web server (Nginx/Apache) doesn't block OPTIONS requests

## Nginx Configuration (if applicable)

Add this to your Nginx server block:

```nginx
# Handle preflight requests
if ($request_method = 'OPTIONS') {
    add_header 'Access-Control-Allow-Origin' '$http_origin' always;
    add_header 'Access-Control-Allow-Methods' 'GET, POST, PUT, PATCH, DELETE, OPTIONS' always;
    add_header 'Access-Control-Allow-Headers' 'Content-Type, Authorization, X-Requested-With, Accept' always;
    add_header 'Access-Control-Allow-Credentials' 'true' always;
    add_header 'Access-Control-Max-Age' 1728000;
    add_header 'Content-Type' 'text/plain charset=UTF-8';
    add_header 'Content-Length' 0;
    return 204;
}
```

## Apache Configuration (if applicable)

Add this to your `.htaccess` or Apache config:

```apache
<IfModule mod_headers.c>
    SetEnvIf Origin "https://www\.imelocker\.com$" ORIGIN_ALLOWED=$0
    SetEnvIf Origin "https://imelocker\.com$" ORIGIN_ALLOWED=$0
    
    Header always set Access-Control-Allow-Origin "%{ORIGIN_ALLOWED}e" env=ORIGIN_ALLOWED
    Header always set Access-Control-Allow-Methods "GET, POST, PUT, PATCH, DELETE, OPTIONS"
    Header always set Access-Control-Allow-Headers "Content-Type, Authorization, X-Requested-With, Accept"
    Header always set Access-Control-Allow-Credentials "true"
    
    # Handle OPTIONS preflight
    RewriteEngine On
    RewriteCond %{REQUEST_METHOD} OPTIONS
    RewriteRule ^(.*)$ $1 [R=204,L]
</IfModule>
```

## Security Considerations

1. **Never use `'*'` for allowed_origins in production** - Always specify exact domains
2. **Keep supports_credentials: true** - Required for authenticated requests
3. **Use HTTPS in production** - Mixed content (HTTP/HTTPS) will be blocked by browsers
4. **Validate frontend origin** - Laravel automatically validates against allowed_origins

## Verification Checklist

After deploying:

- [ ] Config cache cleared: `php artisan config:clear`
- [ ] .env file updated with correct domains
- [ ] Web server restarted (PHP-FPM/Apache/Nginx)
- [ ] Browser console shows no CORS errors
- [ ] Login works from frontend
- [ ] API requests succeed with proper headers
- [ ] Cookies are being set and sent
- [ ] OPTIONS preflight requests return 200/204

## Related Files Modified

1. ✅ `config/cors.php` - Created
2. ✅ `bootstrap/app.php` - Added CORS middleware
3. ✅ `config/sanctum.php` - Added production domains
4. 📝 `.env` - Needs manual update with production values

## Date
October 10, 2025

## Next Steps

1. **Update Production .env:**
   ```bash
   nano /path/to/.env
   # Add the environment variables mentioned above
   ```

2. **Clear Caches:**
   ```bash
   php artisan config:clear
   php artisan cache:clear
   php artisan optimize:clear
   ```

3. **Restart Services:**
   ```bash
   # For PHP-FPM
   sudo systemctl restart php8.3-fpm
   
   # For Nginx
   sudo systemctl restart nginx
   
   # For Apache
   sudo systemctl restart apache2
   ```

4. **Test the Application:**
   - Open https://www.imelocker.com
   - Test login functionality
   - Verify no CORS errors in console
