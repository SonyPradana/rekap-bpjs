<?php

declare(strict_types=1);

return [
    'client' => [
        'clients' => [
            [
                'base_uri'    => $_ENV['CLIENT_BASE_URI'] ?? 'http://127.0.0.1:3000',
                'http_errors' => false,
            ],
        ],
        'threshold' => (int) $_ENV['CLIENT_THRESHOLD'] ?? 3,
        'cooldown'  => (int) $_ENV['CLIENT_COOLDOWN'] ?? 60,
    ],
    'bpjs' => [
        'provider' => $_ENV['BPJS_PROVIDER'] ?? '',
    ],
    'cache' => [
        'driver' => $_ENV['CACHE_DRIVER'] ?? 'file',
        'ttl'    => (int) $_ENV['CACHE_TTL'] ?? 7_884_008,
    ],
];
