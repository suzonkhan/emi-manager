# 🚀 Firebase Configuration - Production Deployment Guide

## Issue
Firebase credentials file not found on production server.

**Error:**
```
Firebase credentials file not found at: storage/app/firebase/ime-locker-app-credentials.json
```

## Root Cause
The `.env` file had a **relative path** that works differently between local and production environments.

## ✅ Solution

### Option 1: Use Relative Path (Recommended)
Your `.env` should use this format:
```env
FIREBASE_CREDENTIALS=storage/app/firebase/ime-locker-app-credentials.json
```

The code will automatically convert this to:
- **Local:** `C:\laragon\www\emi-manager\storage\app\firebase\ime-locker-app-credentials.json`
- **Production:** `/home/imelocker/data.imelocker.com/storage/app/firebase/ime-locker-app-credentials.json`

### Option 2: Use Absolute Path
If you prefer absolute paths, use:
```env
FIREBASE_CREDENTIALS=/home/imelocker/data.imelocker.com/storage/app/firebase/ime-locker-app-credentials.json
```

---

## 📋 Production Deployment Steps

### Step 1: Upload Firebase Credentials File

```bash
# SSH into your production server
ssh imelocker@server2

# Navigate to project directory
cd /home/imelocker/data.imelocker.com

# Create firebase directory if it doesn't exist
mkdir -p storage/app/firebase

# Upload the credentials file (use FTP or SCP)
# From your local machine:
scp storage/app/firebase/ime-locker-app-credentials.json imelocker@server2:/home/imelocker/data.imelocker.com/storage/app/firebase/

# OR create it manually on server:
nano storage/app/firebase/ime-locker-app-credentials.json
# Paste the JSON content and save (Ctrl+X, Y, Enter)
```

### Step 2: Set Correct File Permissions

```bash
# Set proper permissions
chmod 644 storage/app/firebase/ime-locker-app-credentials.json

# Verify file exists
ls -la storage/app/firebase/ime-locker-app-credentials.json

# Should show something like:
# -rw-r--r-- 1 imelocker imelocker 2345 Oct 16 15:30 ime-locker-app-credentials.json
```

### Step 3: Update Production .env

```bash
# Edit production .env
nano .env
```

**Update these lines:**
```env
# Firebase Configuration
FIREBASE_CREDENTIALS=storage/app/firebase/ime-locker-app-credentials.json
FIREBASE_PROJECT_ID=ime-locker-app
FIREBASE_DATABASE_URL=
FIREBASE_STORAGE_BUCKET=
```

**Save and exit** (Ctrl+X, Y, Enter)

### Step 4: Deploy Code Changes

```bash
# Pull latest code
git pull origin master

# Install/update dependencies (if needed)
composer install --no-dev --optimize-autoloader

# Clear all caches
php artisan config:clear
php artisan cache:clear
php artisan route:clear
php artisan view:clear

# Optimize for production
php artisan config:cache
php artisan route:cache
```

### Step 5: Verify Firebase Connection

```bash
# Test Firebase connection (create this test file if needed)
php -r "
require 'vendor/autoload.php';
\$app = require_once 'bootstrap/app.php';
\$kernel = \$app->make(Illuminate\Contracts\Console\Kernel::class);
\$kernel->bootstrap();
try {
    \$service = app(\App\Services\FirebaseService::class);
    echo '✅ Firebase connected successfully!\n';
} catch (\Exception \$e) {
    echo '❌ Error: ' . \$e->getMessage() . '\n';
}
"
```

---

## 🔍 Troubleshooting

### Error: File not found
**Check if file exists:**
```bash
ls -la storage/app/firebase/ime-locker-app-credentials.json
```

**If file doesn't exist:**
- Upload the file from local machine
- Or create it manually and paste JSON content

### Error: Permission denied
**Fix permissions:**
```bash
chmod 644 storage/app/firebase/ime-locker-app-credentials.json
chown imelocker:imelocker storage/app/firebase/ime-locker-app-credentials.json
```

### Error: Invalid credentials
**Verify JSON content:**
```bash
cat storage/app/firebase/ime-locker-app-credentials.json | python -m json.tool
```

Should show valid JSON with these fields:
- `project_id`
- `client_email`
- `private_key`
- `auth_uri`
- `token_uri`

### Error: Still getting "file not found"
**Debug the path:**
```bash
php artisan tinker

# In tinker:
config('firebase.credentials');
storage_path('app/firebase/ime-locker-app-credentials.json');
file_exists(storage_path('app/firebase/ime-locker-app-credentials.json'));
exit
```

---

## ✅ How the Path Resolution Works

The updated `FirebaseService` now intelligently handles paths:

1. **Reads from config:** `config('firebase.credentials')`
2. **Checks if path is relative:**
   - If starts with `/` or `C:` → Use as-is (absolute path)
   - If starts with `storage/` → Strip prefix and use `storage_path()`
   - Otherwise → Use `base_path()`
3. **Verifies file exists**
4. **Loads Firebase credentials**

### Example Path Transformations

| .env Value | Becomes (Production) |
|------------|---------------------|
| `storage/app/firebase/file.json` | `/home/imelocker/data.imelocker.com/storage/app/firebase/file.json` |
| `/absolute/path/file.json` | `/absolute/path/file.json` (unchanged) |
| `config/firebase.json` | `/home/imelocker/data.imelocker.com/config/firebase.json` |

---

## 📝 Production .env Template

```env
# Firebase Configuration
FIREBASE_CREDENTIALS=storage/app/firebase/ime-locker-app-credentials.json
FIREBASE_PROJECT_ID=ime-locker-app
FIREBASE_DATABASE_URL=
FIREBASE_STORAGE_BUCKET=
```

---

## 🎉 Success Indicators

After completing the steps, you should see:

1. ✅ No errors in Laravel logs
2. ✅ Device commands work via API
3. ✅ FCM messages sent successfully
4. ✅ Command logs created in database

Test with:
```bash
curl -X POST https://api.imelocker.com/api/devices/command/lock \
  -H "Authorization: Bearer YOUR_TOKEN" \
  -H "Content-Type: application/json" \
  -d '{"customer_id": 1}'
```

---

## 📞 Quick Reference

**File Location (Production):**
```
/home/imelocker/data.imelocker.com/storage/app/firebase/ime-locker-app-credentials.json
```

**Commands:**
```bash
# Upload file
scp local-file.json user@server:/path/to/file.json

# Check file
ls -la storage/app/firebase/

# Fix permissions
chmod 644 storage/app/firebase/*.json

# Clear cache
php artisan config:clear && php artisan cache:clear

# Test connection
php artisan tinker
>>> app(\App\Services\FirebaseService::class);
```
