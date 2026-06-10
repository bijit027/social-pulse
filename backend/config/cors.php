<?php
return [
    'paths'               => ['api/*'],
    'allowed_methods'     => ['*'],
    'allowed_origins'     => [
        'http://localhost:5173',
        'http://localhost:5174',
        'http://localhost:5175',
        'http://127.0.0.1:5173',
        'http://127.0.0.1:5174',
        'http://localhost:9001',
        'https://social-pulse-730bi47hv-bijit-deb-s-projects.vercel.app',
        'https://social-pulse-ebon.vercel.app',
        'https://support.test',
    'http://support.test',
        'https://*.s6-tastewp.com',
    ],
    'allowed_origins_patterns' => [],
    'allowed_headers'     => ['*'],
    'exposed_headers'     => [],
    'max_age'             => 0,
    'supports_credentials' => true,
];