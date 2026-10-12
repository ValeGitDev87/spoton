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

    'spoton_backup' => [
        'path' => env('SPOTON_BACKUP_PATH', '/var/backups/spotonapp'),
        'command' => env('SPOTON_BACKUP_COMMAND', '/usr/local/sbin/spotonapp-backup.sh'),
    ],

    'spoton_audio' => [
        'disk' => env('SPOTON_AUDIO_DISK', 'public'),
        'directory' => env('SPOTON_AUDIO_DIRECTORY', 'post-audios'),
    ],

    'spoton_auth' => [
        'email_verification_expire_minutes' => (int) env('SPOTON_EMAIL_VERIFICATION_EXPIRE_MINUTES', 60),
        'password_reset_expire_minutes' => (int) env('SPOTON_PASSWORD_RESET_EXPIRE_MINUTES', 30),
        'password_reset_url' => env('SPOTON_PASSWORD_RESET_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/reset-password'),
        'email_verified_url' => env('SPOTON_EMAIL_VERIFIED_URL', rtrim((string) env('APP_URL', 'http://localhost'), '/').'/email-verified'),
    ],

    'push' => [
        'driver' => env('PUSH_DRIVER', 'log'),
    ],

    'expo' => [
        'push_endpoint' => env('EXPO_PUSH_ENDPOINT', 'https://exp.host/--/api/v2/push/send'),
        'receipts_endpoint' => env('EXPO_PUSH_RECEIPTS_ENDPOINT', 'https://exp.host/--/api/v2/push/getReceipts'),
        'access_token' => env('EXPO_ACCESS_TOKEN'),
    ],

];
