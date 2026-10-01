<?php

return [
    'enabled' => env('SEARCH_HISTORY_ENABLED', false),
    'host' => env('SEARCH_REDIS_HOST', '127.0.0.1'),
    'port' => (int) env('SEARCH_REDIS_PORT', 6381),
    'password' => env('SEARCH_REDIS_PASSWORD'),
    'database' => (int) env('SEARCH_REDIS_DB', 0),
];
