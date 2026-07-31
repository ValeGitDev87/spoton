<?php

return [
    'app_links' => [
        'android_package' => env('SPOTON_ANDROID_PACKAGE', 'it.spotonapp.app'),
        'android_sha256_cert_fingerprints' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SPOTON_ANDROID_SHA256_CERT_FINGERPRINTS', '')),
        ))),
        'apple_bundle_identifier' => env('SPOTON_APPLE_BUNDLE_IDENTIFIER', 'it.spotonapp.app'),
        'apple_team_id' => env('SPOTON_APPLE_TEAM_ID'),
    ],
    'privacy' => [
        'contact_email' => env('SPOTON_PRIVACY_EMAIL', 'privacy@spotonapp.cloud'),
        'location_retention_hours' => (int) env('SPOTON_LOCATION_RETENTION_HOURS', 24),
        'presence_retention_days' => (int) env('SPOTON_PRESENCE_RETENTION_DAYS', 30),
    ],
];
