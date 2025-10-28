# Storage Setup Guide

## Issue: Images Not Loading on Production

If images are working locally but not on production, this is typically due to one of these reasons:

## 1. Storage Symlink Not Created

Laravel uses a symbolic link to make storage files publicly accessible. This link must be created on your production server.

### Solution:
Run the following command on your production server:

```bash
php artisan storage:link
```

This creates a symbolic link from `public/storage` to `storage/app/public`.

## 2. Wrong Environment Variables

Make sure your `.env` file has the correct settings:

```env
APP_URL=https://yourdomain.com
FILESYSTEM_DISK=public
```

For the frontend, set `VITE_REACT_APP_API_URL` to match your API endpoint:

```env
VITE_REACT_APP_API_URL=https://yourdomain.com/api
```

## 3. Web Server Configuration

### Apache
Make sure your `.htaccess` in the `public` directory allows following symbolic links.

### Nginx
Your nginx configuration should serve files directly:

```nginx
location /storage {
    alias /path/to/your/project/storage/app/public;
    try_files $uri $uri/ =404;
}
```

## 4. File Permissions

Ensure proper permissions on the storage directory:

```bash
chmod -R 775 storage
chmod -R 775 bootstrap/cache
chown -R www-data:www-data storage
chown -R www-data:www-data bootstrap/cache
```

## 5. Frontend URL Construction

The frontend now uses a reliable utility function (`storage-utils.js`) to construct storage URLs. 

This fixes the issue where URLs were being incorrectly constructed using `.replace('/api', '')`.

### Before (Problematic):
```javascript
`${import.meta.env.VITE_REACT_APP_API_URL?.replace('/api', '')}/storage/${photo}`
```

### After (Fixed):
```javascript
import { getStorageUrl } from '@/lib/storage-utils';
getStorageUrl(photo)
```

## Testing

After setup, verify by:
1. Upload a customer photo
2. Check the image loads in the customer list
3. Verify the image loads in the customer detail view

If images still don't load, check the browser console for errors and verify the actual URL being generated.

