<?php

$googleCallbackPath = env('APP_ENV') === 'production'
    ? '/v1/auth/google/callback'
    : '/api/auth/google/callback';
$googleRedirect = env('GOOGLE_REDIRECT');

return [
    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'resend' => [
        'key' => env('RESEND_KEY'),
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

    'google' => [
        'client_id' => env('GOOGLE_CLIENT_ID'),
        'client_secret' => env('GOOGLE_CLIENT_SECRET'),
        'callback_path' => $googleCallbackPath,
        'redirect' => $googleRedirect ?: rtrim(env('APP_URL', 'http://localhost:8000'), '/').$googleCallbackPath,
    ],

    'amis' => [
        'base_url' => env('AMIS_BASE_URL', 'http://localhost:8001'),
        'origin' => env('AMIS_ORIGIN') ?: env('APP_URL', 'http://localhost:8000'),
        'connect_timeout_seconds' => (int) env('AMIS_CONNECT_TIMEOUT_SECONDS', 2),
        'timeout_seconds' => (int) env('AMIS_TIMEOUT_SECONDS', 4),
    ],
];
