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
    'terms' => [
        'version' => env('SPOTON_TERMS_VERSION', '2026-09-10'),
        'contact_email' => env('SPOTON_TERMS_EMAIL')
            ?: env('SPOTON_PRIVACY_EMAIL')
            ?: 'privacy@spotonapp.cloud',
    ],
    'community_locations' => [
        'daily_limit' => max(1, (int) env('SPOTON_LOCATION_CREATE_DAILY_LIMIT', 3)),
        'max_distance_meters' => max(1, (int) env('SPOTON_LOCATION_CREATE_MAX_DISTANCE_METERS', 1000)),
        'duplicate_radius_meters' => max(1, (int) env('SPOTON_LOCATION_DUPLICATE_RADIUS_METERS', 150)),
        'position_max_age_minutes' => max(1, (int) env('SPOTON_LOCATION_POSITION_MAX_AGE_MINUTES', 10)),
        'max_accuracy_meters' => max(1, (int) env('SPOTON_LOCATION_MAX_ACCURACY_METERS', 100)),
        'default_radius_meters' => max(1, (int) env('SPOTON_LOCATION_DEFAULT_RADIUS_METERS', 100)),
    ],
    'smart_location' => [
        'provider' => env('SPOTON_PLACE_PROVIDER', 'none'),
        'google_api_key' => env('GOOGLE_PLACES_API_KEY'),
        'nearby_radius_meters' => max(25, (int) env('SPOTON_LOCATION_NEARBY_RADIUS_METERS', 150)),
        'provider_radius_meters' => max(25, (int) env('SPOTON_LOCATION_PROVIDER_RADIUS_METERS', 150)),
        'candidate_limit' => min(5, max(1, (int) env('SPOTON_LOCATION_CANDIDATE_LIMIT', 5))),
        'cache_ttl_seconds' => max(60, (int) env('SPOTON_LOCATION_LOOKUP_CACHE_TTL_SECONDS', 86400)),
        'provider_timeout_seconds' => min(10, max(2, (int) env('SPOTON_LOCATION_PROVIDER_TIMEOUT_SECONDS', 5))),
        'lookup_per_minute' => max(1, (int) env('SPOTON_LOCATION_LOOKUP_PER_MINUTE', 6)),
    ],
    'share_video' => [
        'enabled' => (bool) env('SPOTON_SHARE_VIDEO_ENABLED', false),
        'disk' => env('SPOTON_SHARE_VIDEO_DISK', 'local'),
        'directory' => env('SPOTON_SHARE_VIDEO_DIRECTORY', 'share-videos'),
        'queue' => env('SPOTON_SHARE_VIDEO_QUEUE', 'media'),
        'daily_limit' => max(1, (int) env('SPOTON_SHARE_VIDEO_DAILY_LIMIT', 3)),
        'beta_user_emails' => array_values(array_filter(array_map(
            'trim',
            explode(',', (string) env('SPOTON_SHARE_VIDEO_BETA_EMAILS', '')),
        ))),
        'timeout' => max(10, (int) env('SPOTON_SHARE_VIDEO_TIMEOUT', 60)),
        'template_version' => env('SPOTON_SHARE_VIDEO_TEMPLATE_VERSION', 'v1'),
        'ffmpeg_binary' => env('SPOTON_SHARE_VIDEO_FFMPEG_BINARY', 'ffmpeg'),
        'font_path' => env('SPOTON_SHARE_VIDEO_FONT_PATH', '/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf'),
        'font_bold_path' => env('SPOTON_SHARE_VIDEO_FONT_BOLD_PATH', '/usr/share/fonts/truetype/dejavu/DejaVuSans-Bold.ttf'),
        'width' => 720,
        'height' => 1280,
        'fps' => 25,
    ],
    'social_card' => [
        'disk' => env('SPOTON_SOCIAL_CARD_DISK', 'public'),
        'directory' => env('SPOTON_SOCIAL_CARD_DIRECTORY', 'share-cards'),
    ],
    'public_cache_ttl_seconds' => max(30, (int) env('SPOTON_PUBLIC_CACHE_TTL_SECONDS', 300)),
    'child_safety' => [
        'contact_email' => env('SPOTON_CHILD_SAFETY_EMAIL')
            ?: env('SPOTON_PRIVACY_EMAIL')
            ?: 'privacy@spotonapp.cloud',
    ],
];
