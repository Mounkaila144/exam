<?php

return [
    'subject' => env('VAPID_SUBJECT', 'mailto:admin@examguard.local'),
    'public_key' => env('VAPID_PUBLIC_KEY'),
    'private_key' => env('VAPID_PRIVATE_KEY'),

    'ttl' => 60 * 60 * 12,
    'urgency' => 'high',
];
