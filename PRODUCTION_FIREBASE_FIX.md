# Production Firebase Configuration Fix

## Problem
Firebase service was failing with: "Could not map type `Kreait\Firebase\ServiceAccount`"

## Solution
Configure Firebase using **direct environment variables** instead of JSON file.

---

## Step-by-Step Instructions

### 1. **Update Production .env File**

SSH into your server:
```bash
ssh imelocker@server2
cd /home/imelocker/data.imelocker.com
```

Edit the `.env` file:
```bash
nano .env
```

**REMOVE or COMMENT OUT these lines:**
```env
# FIREBASE_CREDENTIALS=/var/www/yourapp/storage/app/firebase/ime-locker-app-credentials.json
```

**ADD these Firebase configuration lines:**
```env
# Firebase Direct Credentials
FIREBASE_PROJECT_ID=ime-locker-app
FIREBASE_CLIENT_EMAIL=firebase-adminsdk-fbsvc@ime-locker-app.iam.gserviceaccount.com
FIREBASE_PRIVATE_KEY="-----BEGIN PRIVATE KEY-----\nMIIEvAIBADANBgkqhkiG9w0BAQEFAASCBKYwggSiAgEAAoIBAQDWeFx81K3JMF1K\n/d8sLe8PhUxiXFdAfBeYrfsBu1aHnNG1dJtKVXVb65zSbCnHmO8U+YR2Prjo27rh\nYMWd4EUiyIYLyLEl5/Z5PfD+JMGvprbnvXnM+RBsUL3M16IdPi178pUSpae3C9BP\n98B2gOqipK9fk+YhBCdXQ/948daMi7AeT1uTlCKCKUbQvaV3ajn6iapsci1j1XR4\nOONxz70TQXuko45Kr2IY3TS1vMBW3pTnhkAPo7SGGfvhTcGs8WH69UwhP6aOytDV\nV01haxtnhbVIYxK3/Wb5AbFmgbx4LbxDlhFBN8Sy6j56FCcA9ddzuSAniZU0gTqi\nWvZQWPuRAgMBAAECggEAB5G96eXzsDsVv9TP8JY6brjMplgN+Eeo6YmSZ1A+UJD3\nUckoxenYN4NMSJzqqJG8NmhMBYRXMFi48sVglB7bMcwT9wyBPA5Aw6ys+btAXruq\njulACDzuGHSNDlK59QBByLMaEcto7Ovs0TW0RDpujYj0a5IjuByWat0KgjE+2jzv\nTcRO1FJmYQuDgDp0tc6p6tvuY2xc9lZrBGzjXVGtMYZgaC65e+T68fOAQs2HZN4n\n/IvnwqHY6n4b0XxWo3VhLqZ7GT9uB1nXkudUJWSI8yp2ICa0b+jSfxP7DHEA0ty1\nT+Ie67ktok6mkf2YkQpg+vg1vLRb3Qz5wA17VCXJ8QKBgQD2bYouvWcjFAth8rlv\nXXIJMQ/z3HcD++Z+E2J1BO70kjLfDlr96Fw1EFXSU3cnw+fsuuQfXVD0CPhrpmEr\n5TtfcEWwXiBNhXmBwljEMe7aEhfi2x82JHnLXAamcbrJutYl8i8PS0vvIOTDT3Te\n31oZPU9YjpaOtcZKzGYSv3M+XQKBgQDezQlJixw1K7isVsWS7orCfjsKo5lHbt02\n79xSEZBDVdRKn3SKVsNPrvC6HlaeZWnjwJ9v/mcqs+oUNEQeRP9RrHr0ANKd5yHW\nBQ8+LRXzOkRlVyXpyikjbVYBuR2UkR9X1GXsLJXocVb0Se0G+1HOqkXXbxokjek4\n/cZ46aYWxQKBgFJsEOvRLc3txmDcbmlxN9MbNdq6wpPyjQVeNnAtVj83Jwy0IHsM\nXMriy9GtWQ1T2R6049gZvhnhZjWbUKT95v3k72ouEV/cZOehuU7l5J3Lr3GRGL9j\nM9lwzkidgXw3oajPeC4FYUB6IAmzacOhsOEAQKm+B110Lv6Vnw5mOoWBAoGAacyx\nuVOuzGz7oBMAdVqDWAJ7ZPz1H5+8uobCd9JRUDhkvB7mR38V9jPbqnYXqdX8p6Nj\n0tnbAcM3x+pd4oXfPFiMdmwhl8wXHDuA1oSwZLTpn7n3jgJq8KDN87mFG3SijqGU\n2Mb/VMblhNHKFcOoQFxJGBlxL6SX+HFCG07QiQUCgYB5NPO8p7fPi6ID0LP8OLXj\nNl3oEOZoR0YbpKa00F6ctyAFT5ky8kQJd2p3/PoSnQFTBLKcBx3j2hdujtB5Iz1N\njvSjrCl8u4l4ds4Rw+RXNOuK2jtXE4HDScmIQVw8zE2TBkUGwNWW7M5nOWkA0LVr\n2zG+Oup8+ZAutM8USWJ6rQ==\n-----END PRIVATE KEY-----\n"
```

**Important Notes:**
- The private key MUST be wrapped in double quotes
- Keep all the `\n` characters exactly as shown
- Don't add extra spaces or line breaks inside the key

Save and exit (Ctrl+X, then Y, then Enter)

---

### 2. **Deploy Updated Code**

Pull the latest code changes:
```bash
cd /home/imelocker/data.imelocker.com
git pull origin master
```

---

### 3. **Clear All Caches**

Run these commands:
```bash
php artisan config:clear
php artisan cache:clear
php artisan view:clear
php artisan route:clear
```

---

### 4. **Run Composer**

This should now work without errors:
```bash
composer dump-autoload
```

---

### 5. **Verify Firebase Configuration**

Check if Firebase is properly configured:
```bash
php artisan config:show firebase
```

Expected output should show:
```
firebase
  credentials ........ null (or path to JSON if you use file)
  project_id ......... ime-locker-app
  client_email ....... firebase-adminsdk-fbsvc@ime-locker-app.iam.gserviceaccount.com
  private_key ........ -----BEGIN PRIVATE KEY----- (truncated)
```

---

### 6. **Test the Application**

Try to access your API:
```bash
curl https://api.imelocker.com/api/health
```

Or test PDF generation from your frontend application.

---

## Alternative: Using JSON File (If Direct Credentials Don't Work)

If for some reason the direct credentials approach doesn't work, you can use the JSON file approach:

1. Create the directory:
```bash
mkdir -p /home/imelocker/data.imelocker.com/storage/app/firebase
```

2. Create the credentials file:
```bash
nano /home/imelocker/data.imelocker.com/storage/app/firebase/ime-locker-app-credentials.json
```

3. Paste your entire JSON content and save.

4. Update `.env`:
```env
FIREBASE_CREDENTIALS=/home/imelocker/data.imelocker.com/storage/app/firebase/ime-locker-app-credentials.json
# FIREBASE_PROJECT_ID=ime-locker-app
# FIREBASE_CLIENT_EMAIL=...
# FIREBASE_PRIVATE_KEY="..."
```

5. Clear cache and test:
```bash
php artisan config:clear
php artisan config:show firebase
```

---

## Troubleshooting

### Issue: Still getting "Could not map type" error

**Solution:** Make sure the `.env` file has been updated correctly. Check:
```bash
cat .env | grep FIREBASE
```

You should see all three Firebase variables with values.

### Issue: Private key format error

**Solution:** The private key must:
- Be wrapped in double quotes (not single quotes)
- Contain `\n` characters (not actual line breaks)
- Include the full `-----BEGIN PRIVATE KEY-----` header and `-----END PRIVATE KEY-----` footer

### Issue: Permission denied

**Solution:** Check file permissions:
```bash
chmod 644 .env
chown imelocker:imelocker .env
```

---

## Summary

✅ **What We Fixed:**
1. Modified `FirebaseService.php` to support both JSON file and direct env credentials
2. Updated `config/firebase.php` to include client_email and private_key
3. Created production `.env` configuration with direct Firebase credentials
4. Now Firebase will work without needing to upload JSON files to the server

✅ **What You Need to Do:**
1. Pull the latest code: `git pull origin master`
2. Update production `.env` with the Firebase credentials provided above
3. Clear caches: `php artisan config:clear && php artisan cache:clear`
4. Test: `php artisan config:show firebase`

---

**After completing these steps, your PDF generation and device commands should work perfectly! 🎉**
