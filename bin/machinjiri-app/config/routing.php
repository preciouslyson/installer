<?php

use Mlangeni\Machinjiri\Core\Routing\RoutingConfig;

return new RoutingConfig(
    cacheFile: __DIR__ . '/../storage/cache/routes.cache',
    errorsDir: __DIR__ . '/../resources/views/errors',
    controllersNamespace: 'Mlangeni\\Machinjiri\\App\\Controllers',
    rateLimiters: [
        'api' => ['max_requests' => 100, 'period' => 60],
        'login' => ['max_requests' => 5, 'period' => 300],
    ],
    viewsBasePath: __DIR__ . '/../resources/views',
    viewsCachePath: __DIR__ . '/../storage/cache/views',
    enableCsrf: true,
    corsDefaults: [
        'allowed_origins' => ['https://example.com'],
        'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE'],
        'allowed_headers' => ['Content-Type', 'X-Requested-With'],
        'max_age' => 3600,
    ]
);