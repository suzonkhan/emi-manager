@echo off
echo ================================================
echo   CORS Fix - Upload .htaccess Files to Server
echo ================================================
echo.

REM Configuration
set SERVER=server2
set USER=imelocker
set REMOTE_PATH=/home/imelocker/data.imelocker.com
set LOCAL_PATH=c:\laragon\www\emi-manager

echo [1/4] Uploading root .htaccess...
scp "%LOCAL_PATH%\.htaccess" %USER%@%SERVER%:%REMOTE_PATH%/.htaccess
if %errorlevel% neq 0 (
    echo ERROR: Failed to upload root .htaccess
    pause
    exit /b 1
)
echo     SUCCESS: Root .htaccess uploaded
echo.

echo [2/4] Uploading public/.htaccess...
scp "%LOCAL_PATH%\public\.htaccess" %USER%@%SERVER%:%REMOTE_PATH%/public/.htaccess
if %errorlevel% neq 0 (
    echo ERROR: Failed to upload public/.htaccess
    pause
    exit /b 1
)
echo     SUCCESS: Public .htaccess uploaded
echo.

echo [3/4] Uploading CORS fix documentation...
scp "%LOCAL_PATH%\CORS_COMPLETE_FIX.md" %USER%@%SERVER%:%REMOTE_PATH%/CORS_COMPLETE_FIX.md
echo     SUCCESS: Documentation uploaded
echo.

echo [4/4] Setting correct permissions...
ssh %USER%@%SERVER% "cd %REMOTE_PATH% && chmod 644 .htaccess public/.htaccess"
echo     SUCCESS: Permissions set to 644
echo.

echo ================================================
echo   Upload Complete!
echo ================================================
echo.
echo Next steps:
echo 1. SSH to server: ssh %USER%@%SERVER%
echo 2. Edit .env file: nano %REMOTE_PATH%/.env
echo 3. Add: CORS_ALLOWED_ORIGINS=https://www.imelocker.com,https://imelocker.com
echo 4. Clear cache: php artisan config:clear ^&^& php artisan config:cache
echo 5. Restart PHP-FPM via cPanel
echo.
echo Or run the automated script:
echo    bash %REMOTE_PATH%/deploy-cors-fix-cpanel.sh
echo.
pause
