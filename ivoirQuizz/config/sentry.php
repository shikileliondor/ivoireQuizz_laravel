<?php

return [
    'dsn' => env('SENTRY_LARAVEL_DSN', env('SENTRY_DSN')),
    'release' => env('SENTRY_RELEASE'),
    'environment' => env('SENTRY_ENVIRONMENT', env('APP_ENV', 'production')),
    'sample_rate' => (float) env('SENTRY_SAMPLE_RATE', 1.0),
    'traces_sample_rate' => env('SENTRY_TRACES_SAMPLE_RATE') !== null ? (float) env('SENTRY_TRACES_SAMPLE_RATE') : null,
    'profiles_sample_rate' => env('SENTRY_PROFILES_SAMPLE_RATE') !== null ? (float) env('SENTRY_PROFILES_SAMPLE_RATE') : null,
    'send_default_pii' => (bool) env('SENTRY_SEND_DEFAULT_PII', false),
    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'sql_queries' => true,
        'sql_bindings' => false,
        'queue_info' => true,
        'command_info' => true,
        'http_client_requests' => true,
    ],
];
