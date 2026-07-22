<?php

return [
    'privacy' => [
        'contact_email' => env('SPOTON_PRIVACY_EMAIL', 'privacy@spotonapp.cloud'),
        'location_retention_hours' => (int) env('SPOTON_LOCATION_RETENTION_HOURS', 24),
        'presence_retention_days' => (int) env('SPOTON_PRESENCE_RETENTION_DAYS', 30),
    ],
];
