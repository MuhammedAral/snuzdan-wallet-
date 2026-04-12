<?php

/**
 * Sentry Configuration for Snuzdan.
 *
 * To enable Sentry:
 * 1. composer require sentry/sentry-laravel
 * 2. Set SENTRY_LARAVEL_DSN in your .env file
 * 3. php artisan vendor:publish --provider="Sentry\Laravel\ServiceProvider"
 *
 * This file serves as a reference configuration.
 */

return [
    'dsn' => env('SENTRY_LARAVEL_DSN'),

    // Performance Monitoring
    'traces_sample_rate' => (float) env('SENTRY_TRACES_SAMPLE_RATE', 1.0),

    // Profiling (requires sentry/sentry-laravel 4.x+)
    'profiles_sample_rate' => (float) env('SENTRY_PROFILES_SAMPLE_RATE', 0.0),

    // Send default PII (user email etc.)
    'send_default_pii' => true,

    // Environment
    'environment' => env('APP_ENV', 'production'),

    // Release
    'release' => env('SENTRY_RELEASE'),

    // Breadcrumbs
    'breadcrumbs' => [
        'logs' => true,
        'cache' => true,
        'sql_queries' => true,
        'sql_bindings' => true,
        'queue_info' => true,
        'command_info' => true,
    ],
];
