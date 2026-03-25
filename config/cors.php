<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Paths must match the request for HandleCors to add headers. Default
    | includes api/* and Sanctum CSRF cookie route.
    |
    | When supports_credentials is true, allowed_origins cannot be "*" — use
    | CORS_ALLOWED_ORIGINS (comma-separated) with every dev/prod front origin.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => array_values(array_filter(array_map(
        'trim',
        explode(',', (string) env('CORS_ALLOWED_ORIGINS', implode(',', [
            'http://localhost:3000',
            'http://127.0.0.1:3000',
            'http://localhost:3001',
            'https://mkit.com.br',
            'https://www.mkit.com.br',
            'https://dev.mkit.com.br',
        ])))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => (bool) env('CORS_SUPPORTS_CREDENTIALS', true),

];
