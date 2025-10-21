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
    | Firebase Credentials JSON (Alternative to file path)
    |--------------------------------------------------------------------------
    |
    | You can provide Firebase credentials as JSON string in environment
    | This is recommended for production to avoid file permission issues
    |
    */

    'credentials_json' => env('FIREBASE_CREDENTIALS_JSON'),

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
