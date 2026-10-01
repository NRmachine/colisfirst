<?php

return [
    'enabled' => env('STRIPE_ENABLED', false),
    'secret' => env('STRIPE_SECRET'),
    'webhook_secret' => env('STRIPE_WEBHOOK_SECRET'),
    'live' => env('STRIPE_LIVE', false),
    'api_version' => '2026-08-26.dahlia',
    'integration_identifier' => 'colisfirst_checkout_qmvxatpk',
];
