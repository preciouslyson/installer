<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class ConfigFiles {

    public static function fileSystemConfigurationTemplate() { return <<<'PHP'
<?php
/**
 * File System Configuration
 */
return [
    // default disk
    'default' => env('FILE_SYSTEM_DEFAULT_DRIVER', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => env('FILE_SYSTEM_ROOT') ?: __DIR__ . '/../storage/app',
        ],
        'ftp' => [
            'driver'   => 'ftp',
            'host'     => env('FILE_SYSTEM_FTP_HOST', 'ftp.example.com'),
            'username' => env('FILE_SYSTEM_FTP_USER', 'user'),
            'password' => env('FILE_SYSTEM_FTP_PASSWORD', 'secret'),
            'root'     => env('FILE_SYSTEM_FTP_ROOT', '/public_html/uploads'),
            'port'     => env('FILE_SYSTEM_FTP_PORT', 21),
            'ssl'      => env('FILE_SYSTEM_FTP_SSL', false),
            'passive'  => env('FILE_SYSTEM_FTP_PASSIVE', true),
            'timeout'  => env('FILE_SYSTEM_FTP_TIMEOUT', 90),
        ],
    ],
];
PHP;
  }

    public static function routingConfigFileTemplate () { return <<<'PHP'
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
PHP;
  }

    public static function queueConfigFileTemplate() { return <<<'PHP'
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Default Queue Driver
    |--------------------------------------------------------------------------
    |
    | This option controls the default queue driver that will be used
    | by the application. You may change this to any supported driver.
    |
    | Supported: "sync", "database", "redis", "file", "memory"
    |
    */
    'default' => env('QUEUE_DRIVER', 'sync'),
    
    /*
    |--------------------------------------------------------------------------
    | Queue Connections
    |--------------------------------------------------------------------------
    |
    | Here you may configure the connection options for each queue driver
    | that is supported by the application.
    |
    */
    'drivers' => [
        'sync' => [
            'driver' => 'sync',
        ],
        
        'database' => [
            'driver' => 'database',
            'table' => 'jobs',
            'queue' => 'default',
            'retry_after' => 90,
            'failed_table' => 'failed_jobs',
        ],
        
        'redis' => [
            'driver' => 'redis',
            'connection' => 'default',
            'queue' => 'default',
            'retry_after' => 90,
            'block_for' => null,
        ],
        
        'file' => [
            'driver' => 'file',
            'path' => __DIR__ . '/../storage/queue',
            'retry_after' => 90,
        ],
        
        'memory' => [
            'driver' => 'memory',
            'retry_after' => 90,
        ],
    ],
    
    /*
    |--------------------------------------------------------------------------
    | Failed Queue Jobs
    |--------------------------------------------------------------------------
    |
    | These options configure the behavior of failed queue job logging.
    |
    */
    'failed' => [
        'driver' => 'database',
        'database' => env('DB_CONNECTION', 'mysql'),
        'table' => 'failed_jobs',
    ],
];
PHP;
  }

    public static function cacheConfigTemplate() { return <<<'PHP'
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
PHP;
  }

    public static function databaseConfigTemplate() { return <<<'PHP'
<?php

/*
  |--------------------------------------------------------------------------
  | Database Configuration
  |--------------------------------------------------------------------------
  |
  | Here you may specify which database connection the application should
  | use. The default is SQLite, but other connections are available.
  |
  */
return [
  'default' => env('DB_CONNECTION', 'sqlite'),
  'prefetch' => env('DB_PREFETCH', false),
  'connections' => [
    'sqlite' => [
        'driver' => 'sqlite',
        'database' => env('DB_DATABASE', 'database/database.sqlite'),
        'prefix' => '',
        'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
    ],
  ]
];
PHP;
  }

    public static function mailConfigTemplate () { return <<<'PHP'
<?php

return [
  'default' => env('MAIL_MAILER', 'smtp'),
  'mailers' => [
    'smtp' => [
      'transport' => 'smtp',
      'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
      'port' => env('MAIL_PORT', 2525),
      'encryption' => env('MAIL_ENCRYPTION', 'tls'),
      'username' => env('MAIL_USERNAME'),
      'password' => env('MAIL_PASSWORD'),
      'timeout' => null,
      'auth_mode' => null,
    ],
      // Add other mailers as needed
  ],
  'from' => [
      'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
      'name' => env('MAIL_FROM_NAME', 'Example'),
  ]
];
PHP;
  }

    public static function providersTemplate () {return <<<'PHP'
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Service Providers Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file registers Service Providers you create
    | for your application.
    |
    | You can modify these values to suit your application needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Configuration Section
    |--------------------------------------------------------------------------
    |
    | Add your Service Providers here.
    | AppServiceProvider And DatabaseServiceProvider is for the Core do not ommit
    | Add Custom Service Providers below AppServiceProvider
    */

    'providers' => [
        \Mlangeni\Machinjiri\App\Providers\AppServiceProvider::class,
        \Mlangeni\Machinjiri\App\Providers\DatabaseServiceProvider::class,
        \Mlangeni\Machinjiri\App\Providers\QueueServiceProvider::class,
    ],
    
    /**
     * Define Service Providers that will be loaded only when needed to
     * improve app performance
     */
    'deffered' => [
        
    ],
];
PHP;
    }

    public static function appConfigTemplate() { return <<<'PHP'
<?php
/**
 * Application Configuration
 *
 * This file contains the main configuration for the Machinjiri framework.
 * Environment variables are loaded and used to configure the application.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'name' => env('APP_NAME', 'Machinjiri App'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */
    'env' => env('APP_ENV', 'development'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */
    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */
    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => 'en',
    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */
    'key' => env('APP_KEY', ''),
    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the session driver that should be used by the
    | application. The default is "file", but other drivers are available.
    |
    */
    'session' => [
        'driver' => env('SESSION_DRIVER', 'file'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => __DIR__ . '/../storage/session',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => env(
            'SESSION_COOKIE',
            'machinjiri_session'
        ),
        'path' => __DIR__ . '/../storage/session',
        'domain' => env('SESSION_DOMAIN'),
        'secure' => env('SESSION_SECURE_COOKIE', false),
        'http_only' => true,
        'same_site' => 'lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Global middleware that runs on every request.
    |
    */
    'middleware' => [
        'global' => [
        ],
        'web' => [
        ],
        'api' => [
        ],
    ],
    'encryption_key' => env('APP_KEY', ''),
    'encryption_cipher' => env('APP_CIPHER', 'aes-256-gcm'),
    'jwt_secret' => env('JWT_SECRET', ''),
    'jwt_algo' => env('JWT_ALGO', 'HS256'),
    'jwt_expiration' => env('JWT_EXPIRATION', 3600),
    'jwt_issuer' => env('JWT_ISSUER', 'machinjiri'),
    'jwt_audience' => env('JWT_AUDIENCE', 'machinjiri_api'),
];
PHP;
  }
}