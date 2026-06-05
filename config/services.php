<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'postmark' => [
        'key' => env('POSTMARK_API_KEY'),
    ],

    'resend' => [
        'key' => env('RESEND_API_KEY'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

    'slack' => [
        'notifications' => [
            'bot_user_oauth_token' => env('SLACK_BOT_USER_OAUTH_TOKEN'),
            'channel' => env('SLACK_BOT_USER_DEFAULT_CHANNEL'),
        ],
    ],

    // Khalti (Nepal) e-payment — sandbox by default. Leave secret null to use
    // the built-in "mock" gateway that auto-completes locally for development.
    // Get a FREE test secret key at https://test-admin.khalti.com (sandbox).
    'khalti' => [
        'secret' => env('KHALTI_SECRET_KEY'),
        'base_url' => env('KHALTI_BASE_URL', 'https://a.khalti.com/api/v2'),
    ],

    // TMDB (themoviedb.org) — free API for fetching real movie posters.
    // Get a free key at https://www.themoviedb.org (Settings -> API).
    'tmdb' => [
        'key' => env('TMDB_API_KEY'),
        'image_base' => env('TMDB_IMAGE_BASE', 'https://image.tmdb.org/t/p/w500'),
    ],

    // Google Sign-In: the OAuth **Web client ID**. When set, the API verifies that
    // a submitted Google ID token's `aud` claim matches this id (prevents tokens
    // minted for other apps being accepted at POST /api/v1/auth/google).
    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
    ],

    // eSewa (Nepal) ePay v2. Defaults to the PUBLIC sandbox test merchant
    // ("EPAYTEST") — no signup required, works out of the box for testing.
    // Sandbox test login: eSewa ID 9806800001 / password Nepal@123 / MPIN 1122.
    'esewa' => [
        'product_code' => env('ESEWA_PRODUCT_CODE', 'EPAYTEST'),
        'secret' => env('ESEWA_SECRET', '8gBm/:&EnhH.1/q'),
        'form_url' => env('ESEWA_FORM_URL', 'https://rc-epay.esewa.com.np/api/epay/main/v2/form'),
        'status_url' => env('ESEWA_STATUS_URL', 'https://rc.esewa.com.np/api/epay/transaction/status/'),
    ],

    // Firebase Cloud Messaging (push notifications, HTTP v1 API).
    // Download a service-account key from Firebase console → Project settings →
    // Service accounts → "Generate new private key", and point FIREBASE_CREDENTIALS
    // at it. project_id is read from that file (override with FIREBASE_PROJECT_ID).
    'firebase' => [
        // `?:` so an empty env value still falls back to the default path.
        'credentials' => env('FIREBASE_CREDENTIALS') ?: storage_path('app/firebase/service-account.json'),
        'project_id' => env('FIREBASE_PROJECT_ID'),
    ],

];
