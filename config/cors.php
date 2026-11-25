<?php

$origenesLocales = implode(',', [
    'http://localhost:4200',
    'http://127.0.0.1:4200',
    'http://localhost:4201',
    'http://127.0.0.1:4201',
    'http://localhost:4202',
    'http://127.0.0.1:4202',
    'http://10.0.2.2:4202',
]);

$origenes = env(
    'CORS_ALLOWED_ORIGINS',
    env('APP_ENV') === 'local' ? $origenesLocales : ''
);

return [
    'paths' => ['api/*', 'sanctum/csrf-cookie'],
    'allowed_methods' => ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'],
    'allowed_origins' => array_values(array_filter(
        array_map('trim', explode(',', (string) $origenes))
    )),
    'allowed_origins_patterns' => [],
    'allowed_headers' => [
        'Accept',
        'Authorization',
        'Content-Type',
        'X-Requested-With',
        'Precognition',
        'Precognition-Validate-Only',
    ],
    'exposed_headers' => [
        'Precognition',
        'Precognition-Success',
    ],
    'max_age' => 0,
    'supports_credentials' => false,
];
