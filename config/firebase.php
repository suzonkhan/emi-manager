<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Firebase Service Account Credentials
    |--------------------------------------------------------------------------
    |
    | Path to the Firebase service account JSON file.
    | Store the JSON file securely and reference it here.
    | If not provided, will use individual credentials below.
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
    | Firebase Client Email
    |--------------------------------------------------------------------------
    |
    | Service account client email
    |
    */

    'client_email' => env('FIREBASE_CLIENT_EMAIL'),

    /*
    |--------------------------------------------------------------------------
    | Firebase Private Key
    |--------------------------------------------------------------------------
    |
    | Service account private key (replace \n with \\n in .env)
    |
    */

    'private_key' => env('FIREBASE_PRIVATE_KEY'),

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
