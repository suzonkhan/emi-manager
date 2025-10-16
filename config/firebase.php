<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | Store the JSON file securely and reference it here.
    |
    */

    'credentials' => env('FIREBASE_CREDENTIALS'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Project ID
    |--------------------------------------------------------------------------
    |
    | Your Firebase project ID
    |
    */

    'project_id' => env('FIREBASE_PROJECT_ID', 'ime-locker-app'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Database URL
    |--------------------------------------------------------------------------
    |
    | Your Firebase Realtime Database URL (optional)
    |
    */

    'database_url' => env('FIREBASE_DATABASE_URL'),

    /*
    |--------------------------------------------------------------------------
    | Storage Bucket
    |--------------------------------------------------------------------------
    |
    | Your Firebase Storage bucket (optional)
    |
    */

    'storage_bucket' => env('FIREBASE_STORAGE_BUCKET'),
];
