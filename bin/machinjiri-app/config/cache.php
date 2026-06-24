<?php
/**
 * Cache Configuration
 */
return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => [
        'redis' => [
          'driver' => 'redis',
          'host' => env('REDIS_HOST', '127.0.0.1')
        ],
        'array' => [
          'driver' => 'array', 
          'max_items' => 500,
          'eviction' => 'lru'
        ],
        'file' => [
          'driver' => 'file',
          'path' =>  env('CACHE_LOCAL_STORAGE') ?: __DIR__ . '/../storage/',
          'max_files' => 5000,
          'file_perm' => 0644,
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'machinjiri_cache'),
    'default_ttl' => env('CACHE_DEFAULT_TTL', 300),
    'stampede_protection' => true
];