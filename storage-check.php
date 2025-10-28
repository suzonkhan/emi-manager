<?php
/**
 * Storage Diagnostic Script
 * Run this directly: php storage-check.php
 * Or via browser: http://yourdomain.com/storage-check.php
 */

echo "<h2>Laravel Storage Diagnostic</h2>";
echo "<pre>";

// Check 1: Storage directory exists
echo "1. Checking storage/app/public directory...\n";
if (is_dir(__DIR__ . '/storage/app/public')) {
    echo "   ✓ Directory exists\n";
} else {
    echo "   ✗ Directory NOT FOUND\n";
}

// Check 2: Photos directory
echo "\n2. Checking photos directory...\n";
if (is_dir(__DIR__ . '/storage/app/public/photos')) {
    echo "   ✓ Photos directory exists\n";
    $userPhotos = glob(__DIR__ . '/storage/app/public/photos/users/*');
    $customerPhotos = glob(__DIR__ . '/storage/app/public/photos/customers/*');
    echo "   - User photos: " . count($userPhotos) . "\n";
    echo "   - Customer photos: " . count($customerPhotos) . "\n";
    
    if (count($userPhotos) > 0) {
        echo "   - First user photo: " . basename($userPhotos[0]) . "\n";
    }
} else {
    echo "   ✗ Photos directory NOT FOUND\n";
}

// Check 3: Public/storage symlink
echo "\n3. Checking public/storage symlink...\n";
$storageSymlink = __DIR__ . '/public/storage';
if (is_link($storageSymlink)) {
    echo "   ✓ Symlink exists\n";
    echo "   - Points to: " . readlink($storageSymlink) . "\n";
    echo "   - Resolved: " . realpath($storageSymlink) . "\n";
} elseif (is_dir($storageSymlink)) {
    echo "   ⚠ WARNING: public/storage is a directory, NOT a symlink!\n";
    echo "   - This is the problem! You need to delete this directory and create a symlink.\n";
} else {
    echo "   ✗ Symlink NOT FOUND\n";
    echo "   - Run: php artisan storage:link\n";
}

// Check 4: Symlink accessibility
echo "\n4. Testing symlink accessibility...\n";
if (is_link($storageSymlink)) {
    $testFile = __DIR__ . '/public/storage/photos';
    if (is_dir($testFile)) {
        echo "   ✓ Symlink works - can access photos directory\n";
        $files = glob($testFile . '/users/*');
        if (count($files) > 0) {
            $firstFile = basename($files[0]);
            echo "   - Sample file accessible: photos/users/$firstFile\n";
        }
    } else {
        echo "   ✗ Symlink NOT working - cannot access photos\n";
    }
}

// Check 5: Permissions
echo "\n5. Checking permissions...\n";
$storagePath = __DIR__ . '/storage/app/public';
if (file_exists($storagePath)) {
    echo "   - Storage directory: " . substr(sprintf('%o', fileperms($storagePath)), -4) . "\n";
    if (is_readable($storagePath)) {
        echo "   ✓ Storage is readable\n";
    } else {
        echo "   ✗ Storage is NOT readable\n";
    }
}

// Check 6: Test URL
echo "\n6. Test URLs:\n";
echo "   Your APP_URL: " . (getenv('APP_URL') ?: 'Not set in environment') . "\n";
if (count($userPhotos) > 0) {
    $filename = basename($userPhotos[0]);
    $appUrl = getenv('APP_URL') ?: 'http://localhost';
    echo "   Test image URL: $appUrl/storage/photos/users/$filename\n";
}

// Check 7: Environment check
echo "\n7. Environment variables:\n";
echo "   APP_URL: " . ($_ENV['APP_URL'] ?? 'Not set') . "\n";
echo "   FILESYSTEM_DISK: " . ($_ENV['FILESYSTEM_DISK'] ?? 'Not set') . "\n";

echo "\n";
echo "=================================\n";
echo "Diagnostic Complete\n";
echo "=================================\n";
echo "</pre>";

