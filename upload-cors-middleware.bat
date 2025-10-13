@echo off
echo ============================================================
echo   CORS Middleware Solution - Upload to Production Server
echo ============================================================
echo.

set SERVER=server2
set USER=imelocker
set REMOTE_PATH=/home/imelocker/data.imelocker.com
set LOCAL_PATH=c:\laragon\www\emi-manager

echo [1/5] Uploading CORS Middleware...
scp "%LOCAL_PATH%\app\Http\Middleware\HandleCorsHeaders.php" %USER%@%SERVER%:%REMOTE_PATH%/app/Http/Middleware/HandleCorsHeaders.php
if %errorlevel% neq 0 (
    echo ERROR: Failed to upload middleware
    pause
    exit /b 1
)
echo     SUCCESS: Middleware uploaded
echo.

echo [2/5] Uploading Bootstrap Config...
scp "%LOCAL_PATH%\bootstrap\app.php" %USER%@%SERVER%:%REMOTE_PATH%/bootstrap/app.php
if %errorlevel% neq 0 (
    echo ERROR: Failed to upload bootstrap/app.php
    pause
    exit /b 1
)
echo     SUCCESS: Bootstrap config uploaded
echo.

echo [3/5] Uploading CORS Config...
scp "%LOCAL_PATH%\config\cors.php" %USER%@%SERVER%:%REMOTE_PATH%/config/cors.php
if %errorlevel% neq 0 (
    echo ERROR: Failed to upload config/cors.php
    pause
    exit /b 1
)
echo     SUCCESS: CORS config uploaded
echo.

echo [4/5] Uploading .htaccess files...
scp "%LOCAL_PATH%\.htaccess" %USER%@%SERVER%:%REMOTE_PATH%/.htaccess
scp "%LOCAL_PATH%\public\.htaccess" %USER%@%SERVER%:%REMOTE_PATH%/public/.htaccess
echo     SUCCESS: .htaccess files uploaded
echo.

echo [5/5] Uploading documentation...
scp "%LOCAL_PATH%\CORS_MIDDLEWARE_SOLUTION.md" %USER%@%SERVER%:%REMOTE_PATH%/CORS_MIDDLEWARE_SOLUTION.md
echo     SUCCESS: Documentation uploaded
echo.

echo ============================================================
echo   Upload Complete!
echo ============================================================
echo.
echo CRITICAL NEXT STEPS:
echo.
echo 1. SSH to server:
echo    ssh %USER%@%SERVER%
echo.
echo 2. Edit .env file:
echo    cd %REMOTE_PATH%
echo    nano .env
echo.
echo 3. Add this line (if not present):
echo    CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com
echo.
echo 4. Clear caches:
echo    php artisan config:clear
echo    php artisan config:cache
echo    php artisan optimize
echo.
echo 5. Restart PHP-FPM in cPanel:
echo    - Go to cPanel -^> MultiPHP Manager
echo    - Toggle PHP version for data.imelocker.com
echo.
echo 6. Test:
echo    curl -I https://www.imelocker.com/api/reports/dealers
echo.
echo ============================================================
pause
