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

    'firebase' => [
        'project_id' => env('FIREBASE_PROJECT_ID'),
        'images_bucket' => env('FIREBASE_IMAGES_BUCKET'),
        'credentials' => env('FIREBASE_CREDENTIALS'),
        'images_public_url_base' => env('FIREBASE_IMAGES_PUBLIC_URL_BASE'),
        'inmueble_image_max_kb' => (int) env('INMUEBLE_IMAGE_MAX_KB', 10240),
        'documents_bucket' => env('FIREBASE_DOCUMENTS_BUCKET'),
        'document_max_kb' => (int) env('DOCUMENT_MAX_KB', 10240),
    ],

];
