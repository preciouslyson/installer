<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class ConfigFiles {

    public static function smsConfigurationTemplate() {return <<<'PHP'
<?php

/**
 * SMS Transport Configuration
 * 
 * This configuration file defines the settings for SMS transport drivers,
 including retry policies, circuit breakers, rate limiting, and default transports.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default SMS Transport
    |--------------------------------------------------------------------------
    |
    | This option controls the default SMS transport that will be used by the
    | SMSManager when no specific transport is specified.
    |
    */
    'default' => env('SMS_DEFAULT_DRIVER', 'africastalking'),

    /*
    |--------------------------------------------------------------------------
    | Asynchronous Sending
    |--------------------------------------------------------------------------
    |
    | When set to true, SMS messages will be queued for asynchronous delivery.
    | This improves response times but requires a queue worker to be running.
    |
    */
    'async' => env('SMS_ASYNC', false),

    /*
    |--------------------------------------------------------------------------
    | Transport Configurations
    |--------------------------------------------------------------------------
    |
    | Here you may configure each SMS transport driver. Each driver should have
    | a unique key and contain the necessary credentials and settings for the
    | specific SMS provider.
    |
    | Available drivers: africastalking
    |
    */
    'transports' => [
        'africastalking' => [
            'driver' => 'africastalking',
            
            // Provider-specific configuration
            'api_key' => env('AFRICASTALKING_API_KEY', ''),
            'username' => env('AFRICASTALKING_USERNAME', ''),
            'from' => env('AFRICASTALKING_FROM', ''),
            'sandbox' => env('AFRICASTALKING_SANDBOX', true),
            
            // Custom transport class (optional - overrides default)
            'transportClass' => Mlangeni\Machinjiri\Core\Transport\SMS\Transports\AfricasTalkingTransport::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Retry Policy
    |--------------------------------------------------------------------------
    |
    | These settings control how the system retries failed SMS deliveries.
    | The retry policy uses exponential backoff with jitter to prevent
    | overwhelming the provider during outages.
    |
    */
    'retry' => [
        'max_attempts' => env('SMS_RETRY_MAX_ATTEMPTS', 3),
        'base_delay_ms' => env('SMS_RETRY_BASE_DELAY', 1000),
        'backoff_factor' => env('SMS_RETRY_BACKOFF_FACTOR', 2.0),
        'jitter_factor' => env('SMS_RETRY_JITTER_FACTOR', 0.1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Rate Limiting
    |--------------------------------------------------------------------------
    |
    | Rate limiting prevents exceeding provider limits by controlling
    | the number of SMS messages sent per time window.
    |
    */
    'rate_limit' => [
        // Maximum number of SMS messages per time window
        'max_messages' => env('SMS_RATE_LIMIT_MAX', 10),
        
        // Time window in minutes
        'time_window_minutes' => env('SMS_RATE_LIMIT_WINDOW', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Circuit Breaker
    |--------------------------------------------------------------------------
    |
    | The circuit breaker prevents sending SMS when the provider is
    | experiencing issues. It tracks failures and opens the circuit
    | after a certain threshold is reached.
    |
    */
    'circuit_breaker' => [
        'failure_threshold' => env('SMS_CIRCUIT_FAILURE_THRESHOLD', 5),
        'timeout_seconds' => env('SMS_CIRCUIT_TIMEOUT', 60),
        'half_open_attempts' => env('SMS_CIRCUIT_HALF_OPEN_ATTEMPTS', 1),
    ],

    /*
    |--------------------------------------------------------------------------
    | Idempotency
    |--------------------------------------------------------------------------
    |
    | Idempotency prevents duplicate SMS messages by storing processed
    | message hashes. The TTL defines how long to remember processed messages.
    |
    */
    'idempotency' => [
        'ttl_seconds' => env('SMS_IDEMPOTENCY_TTL', 3600),
        'cache_key_prefix' => 'sms_idempotent_',
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging
    |--------------------------------------------------------------------------
    |
    | Configure which events to log and at what level.
    |
    */
    'logging' => [
        'enabled' => env('SMS_LOGGING_ENABLED', true),
        'channel' => env('SMS_LOGGING_CHANNEL', 'daily'),
        'level' => env('SMS_LOGGING_LEVEL', 'info'),
        'log_success' => true,
        'log_failure' => true,
        'log_sensitive_data' => false, // Don't log phone numbers or message content
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Settings for queue-based SMS sending.
    |
    */
    'queue' => [
        'connection' => env('SMS_QUEUE_CONNECTION', 'default'),
        'queue' => env('SMS_QUEUE_NAME', 'sms'),
        'retry_after' => env('SMS_QUEUE_RETRY_AFTER', 90),
        'max_attempts' => env('SMS_QUEUE_MAX_ATTEMPTS', 3),
    ],

    /*
    |--------------------------------------------------------------------------
    | Transport-Specific Defaults
    |--------------------------------------------------------------------------
    |
    | Default values that apply to all transports unless overridden.
    |
    */
    'defaults' => [
        'from' => env('SMS_DEFAULT_FROM', ''),
        'timeout_seconds' => env('SMS_DEFAULT_TIMEOUT', 30),
        'connect_timeout_seconds' => env('SMS_DEFAULT_CONNECT_TIMEOUT', 5),
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Configuration
    |--------------------------------------------------------------------------
    |
    | If the primary transport fails, you can specify a fallback transport.
    | The system will attempt the fallback before reporting a failure.
    |
    */
    'fallback' => [
        'enabled' => env('SMS_FALLBACK_ENABLED', false),
        'driver' => env('SMS_FALLBACK_DRIVER', null),
    ],
];
PHP;
    }

    public static function redisConfigurationTemplate() { return <<<'PHP'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default Redis Connection
    |--------------------------------------------------------------------------
    |
    | The default connection to use when no specific connection is requested.
    |
    */
    'default' => 'default',
    /*
    |--------------------------------------------------------------------------
    | Redis Connections
    |--------------------------------------------------------------------------
    |
    | Each connection can be configured with its own set of parameters.
    | You can define multiple connections for different LDAP servers.
    |
    */
    'connections' => [
        'default' => [
            'host'               => env('REDIS_HOST', '127.0.0.1'),
            'port'               => env('REDIS_PORT', 679),
            'database'           => env('REDIS_DATABASE', 0),
            'password'           => env('REDIS_PASSWORD', null),
            'timeout'            => env('REDIS_TIMEOUT', 2.5),
            'read_write_timeout' => env('REDIS_READ_WRITE_TIMEOUT', 2.5),
            'retry_interval'     => env('REDIS_RETRY_INTERVAL', 100),
            'prefix'             => env('REDIS_PREFIX', ''),
            'serialize'          => env('REDIS_SERIALIZE', true),
        ],
        // you can define additional connections here
    ],
];
PHP;
    }

    public static function authConfigurationTemplate() { return <<<'PHP'
<?php

/**
 * Configuration file for Authentication Manager (AuthManager).
 * 
 * This file defines the settings for Authentication
 * 
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Default Authentication Guard - (session, token, jwt) default => session
    |--------------------------------------------------------------------------
    |
    | The default guard to use for authentication.
    |
    */
    'default' => 'session',
    /*
    |--------------------------------------------------------------------------
    | Authentication Guards Configuration
    |--------------------------------------------------------------------------
    |
    | Make sure you properly configure your guard if set to default above
    | or will throw an error
    |
    */
    'guards' => [
        'session' => [
            /*
            |--------------------------------------------------------------------------
            | Session Guard
            |--------------------------------------------------------------------------
            | 
            */
            'driver' => 'session',
            /*
            |--------------------------------------------------------------------------
            | SessionGuard provider configuration
            |--------------------------------------------------------------------------
            | The provider is the core for the authentication guard
            | The provider has (database, ldap, oauth) set your provider driver for 
            | your session guard
            | The model is the Model used for crud operations and authentication
            |
            */
            'provider' => [
                /* Can use database, ldap and OAuth */
                'driver' => 'database',
                /* Define your custom model */
                'model' => \Mlangeni\Machinjiri\Facade\Authentication\Models\User::class,
            ],
            /*
            |--------------------------------------------------------------------------
            | Remember Token Expiration
            |--------------------------------------------------------------------------
            | Set expiration time in days. Default is 7
            */
            'remember_expiration' => 7 * 24 * 60 * 60,
        ],
        // add guards here.. (eg token, jwt)
    ],
];
PHP;
    }
    
    public static function OAuthConfigurationTemplate() { return <<<'PHP'
<?php

/**
 * Configuration file for Third-Party Authentication (ThirdPartyAuth).
 * 
 * This file defines the settings for OAuth providers (Google, GitHub, Facebook, etc.)
 * and how user data is managed locally.
 * 
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Redirect URI
    |--------------------------------------------------------------------------
    |
    | The URI where the OAuth provider will redirect after authentication.
    | This should match the callback URL registered with each provider.
    | Typically, you set this to your application's URL + '/auth/callback'.
    |
    */
    'redirect_uri' => env('APP_URL', 'http://localhost:3000') . '/auth/callback',

    /*
    |--------------------------------------------------------------------------
    | Session Key Prefix
    |--------------------------------------------------------------------------
    |
    | Prefix used for session keys when storing temporary authentication data.
    |
    */
    'session_key_prefix' => 'thirdparty_auth_',

    /*
    |--------------------------------------------------------------------------
    | Database Tables
    |--------------------------------------------------------------------------
    |
    | Names of the tables used to store user accounts, provider connections,
    | and OAuth tokens. You can customize these to match your database schema.
    |
    */
    'user_table'       => 'users',
    'provider_table'   => 'user_providers',
    'token_table'      => 'user_tokens',

    /*
    |--------------------------------------------------------------------------
    | User Management
    |--------------------------------------------------------------------------
    |
    | - auto_create_users: Automatically create a local user account if none
    |   exists for the authenticated provider user.
    | - auto_sync_profile: Update local user data (name, avatar) from the
    |   provider on each login.
    | - default_role: The role assigned to newly created users.
    |
    */
    'auto_create_users'   => true,
    'auto_sync_profile'   => true,
    'default_role'        => 'user',

    /*
    |--------------------------------------------------------------------------
    | OAuth Scopes
    |--------------------------------------------------------------------------
    |
    | Define the permission scopes requested from each provider.
    | These are merged with the default scopes defined in the class.
    | You can override per provider by specifying a custom array.
    |
    */
    'scopes' => [
        'google'     => ['email', 'profile'],
        'github'     => ['user:email'],
        'facebook'   => ['email', 'public_profile'],
        'twitter'    => ['users.read', 'tweet.read'],
        'yahoo'      => ['profile', 'email'],
        'linkedin'   => ['r_liteprofile', 'r_emailaddress'],
        'microsoft'  => ['User.Read', 'email'],
        'instagram'  => ['user_profile', 'user_media'],
        'gitlab'     => ['read_user'],
        'bitbucket'  => ['account', 'email'],
        'amazon'     => ['profile'],
        'slack'      => ['users:read', 'users:read.email'],
    ],

    /*
    |--------------------------------------------------------------------------
    | OAuth Endpoints
    |--------------------------------------------------------------------------
    |
    | The authorization, token, and revoke endpoints for each provider.
    | Usually you do not need to change these, but you can override any
    | endpoint here if a provider changes its API.
    |
    | The class provides default endpoints; you only need to specify
    | the ones you want to override.
    |
    */
    'endpoints' => [
        // Example override (uncomment and modify as needed):
        // 'google' => [
        //     'authorization' => 'https://accounts.google.com/o/oauth2/auth',
        //     'token'         => 'https://oauth2.googleapis.com/token',
        //     'revoke'        => 'https://oauth2.googleapis.com/revoke',
        // ],
        'github' => [
            'authorization' => 'https://github.com/login/oauth/authorize',
            'token' => 'https://github.com/login/oauth/access_token',
            'revoke' => null
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | User Info Endpoints
    |--------------------------------------------------------------------------
    |
    | The API endpoints used to fetch user profile data after obtaining
    | an access token. You can override any endpoint here.
    |
    */
    'user_info_endpoints' => [
        // Example override:
        // 'facebook' => 'https://graph.facebook.com/v12.0/me?fields=id,name,email,picture.type(large)',
    ],

    /*
    |--------------------------------------------------------------------------
    | Provider Credentials (Environment Variables)
    |--------------------------------------------------------------------------
    |
    | For each provider you wish to enable, you must set the following
    | environment variables (in your .env file or system environment):
    |
    | GOOGLE_CLIENT_ID, GOOGLE_CLIENT_SECRET
    | GITHUB_CLIENT_ID, GITHUB_CLIENT_SECRET
    | FACEBOOK_CLIENT_ID, FACEBOOK_CLIENT_SECRET
    | TWITTER_CLIENT_ID, TWITTER_CLIENT_SECRET
    | YAHOO_CLIENT_ID, YAHOO_CLIENT_SECRET
    | LINKEDIN_CLIENT_ID, LINKEDIN_CLIENT_SECRET
    | MICROSOFT_CLIENT_ID, MICROSOFT_CLIENT_SECRET
    | INSTAGRAM_CLIENT_ID, INSTAGRAM_CLIENT_SECRET
    | GITLAB_CLIENT_ID, GITLAB_CLIENT_SECRET
    | BITBUCKET_CLIENT_ID, BITBUCKET_CLIENT_SECRET
    | AMAZON_CLIENT_ID, AMAZON_CLIENT_SECRET
    | SLACK_CLIENT_ID, SLACK_CLIENT_SECRET
    |
    | Only providers with both ID and secret set will be enabled.
    |
    */
];
PHP;
    }
    
    public static function ldapConfigurationTemplate() { return <<<'PHP'
<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default LDAP Connection
    |--------------------------------------------------------------------------
    |
    | The default connection to use when no specific connection is requested.
    |
    */
    'default' => env('LDAP_DEFAULT', 'primary'),

    /*
    |--------------------------------------------------------------------------
    | LDAP Connections
    |--------------------------------------------------------------------------
    |
    | Each connection can be configured with its own set of parameters.
    | You can define multiple connections for different LDAP servers.
    |
    */
    'connections' => [

        'primary' => [
            /*
            | The LDAP server host(s). You can specify multiple for failover.
            | The first reachable host will be used.
            */
            'hosts' => explode(',', env('LDAP_HOSTS', 'ldap.forumsys.com')),

            /*
            | LDAP port (389 for standard, 636 for LDAPS)
            */
            'port' => env('LDAP_PORT', 389),

            /*
            | Base DN for searches (e.g., dc=example,dc=com)
            */
            'base_dn' => env('LDAP_BASE_DN', 'dc=example,dc=com'),

            /*
            | Use SSL (ldaps:// protocol). If true, port usually 636.
            */
            'use_ssl' => env('LDAP_SSL', false),

            /*
            | Use TLS (StartTLS) after connecting.
            | If both use_ssl and use_tls are true, use_ssl takes precedence.
            */
            'use_tls' => env('LDAP_TLS', false),

            /*
            | Automatically bind with the service account after connecting.
            | If set to false, you must call bind() manually before queries.
            */
            'auto_bind' => env('LDAP_AUTO_BIND', true),

            /*
            | Service account credentials for binding.
            | If empty, anonymous bind will be attempted.
            */
            'username' => env('LDAP_USERNAME', 'cn=read-only-admin,dc=example,dc=com'),
            'password' => env('LDAP_PASSWORD', 'password'),

            /*
            | LDAP options (see ldap_set_option)
            | Common options:
            |   "LDAP_OPT_PROTOCOL_VERSION" => 3
            |   "LDAP_OPT_REFERRALS"        => 0
            |   "LDAP_OPT_NETWORK_TIMEOUT"  => 5  (seconds)
            */
            'options' => [
                "LDAP_OPT_PROTOCOL_VERSION" => 3,
                "LDAP_OPT_REFERRALS"        => 0,
                "LDAP_OPT_NETWORK_TIMEOUT"  => env('LDAP_NETWORK_TIMEOUT', 5),
            ],

            /*
            | Connection timeout in seconds.
            | This is used in the ldap_connect call via network timeout option above.
            */
            'timeout' => env('LDAP_TIMEOUT', 5),

            /*
            | Additional configuration for the LDAP User Provider
            | (if you are using this component for authentication)
            */
            'provider' => [
                /*
                | The attribute used as the local username.
                | Usually 'uid' or 'cn' or 'sAMAccountName' for Active Directory.
                */
                'username_attribute' => env('LDAP_USERNAME_ATTR', 'uid'),

                /*
                | Fields to search when looking up a user by credentials.
                | These will be tried in order until a match is found.
                */
                'search_fields' => explode(',', env('LDAP_SEARCH_FIELDS', 'uid,mail')),

                /*
                | Mapping of local model attributes to LDAP attributes.
                | The local attribute is the key, the LDAP attribute is the value.
                */
                'sync_attributes' => [
                    'name'  => 'cn',
                    'email' => 'mail',
                    'phone' => 'telephoneNumber',
                ],

                /*
                | Cache TTL in seconds for LDAP user lookups.
                | Set to 0 to disable caching.
                */
                'cache_ttl' => env('LDAP_CACHE_TTL', 300),
            ],
        ],

        /*
        | An example Active Directory connection
        */
        'ad' => [
            'hosts' => explode(',', env('AD_HOSTS', 'dc1.example.com,dc2.example.com')),
            'port' => env('AD_PORT', 389),
            'base_dn' => env('AD_BASE_DN', 'dc=example,dc=com'),
            'use_ssl' => env('AD_SSL', false),
            'use_tls' => env('AD_TLS', false),
            'auto_bind' => env('AD_AUTO_BIND', true),
            'username' => env('AD_USERNAME', 'cn=Administrator,cn=Users,dc=example,dc=com'),
            'password' => env('AD_PASSWORD', 'secret'),
            'options' => [
                "LDAP_OPT_PROTOCOL_VERSION" => 3,
                "LDAP_OPT_REFERRALS"        => 0,
                "LDAP_OPT_NETWORK_TIMEOUT"  => 5,
            ],
            'provider' => [
                'username_attribute' => 'sAMAccountName',
                'search_fields' => ['sAMAccountName', 'mail', 'userPrincipalName'],
                'sync_attributes' => [
                    'name'  => 'cn',
                    'email' => 'mail',
                    'phone' => 'telephoneNumber',
                ],
                'cache_ttl' => 600,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Caching & Logging Settings
    |--------------------------------------------------------------------------
    */
    'cache' => [
        /*
        | Whether to enable caching for LDAP queries by default.
        | Individual queries can override this.
        */
        'enabled' => env('LDAP_CACHE_ENABLED', true),

        /*
        | Default cache TTL in seconds.
        */
        'ttl' => env('LDAP_CACHE_TTL_DEFAULT', 300),
    ],

    /*
    | Logging configuration
    | (uses the framework's Logger)
    */
    'logging' => [
        /*
        | Log level for LDAP operations: debug, info, warning, error, etc.
        */
        'level' => env('LDAP_LOG_LEVEL', 'info'),

        /*
        | Log channel to use (if using a multi-channel logger).
        */
        'channel' => env('LDAP_LOG_CHANNEL', 'ldap'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Advanced Options
    |--------------------------------------------------------------------------
    */
    'advanced' => [
        /*
        | Enable stampede protection for cache (prevents cache stampedes).
        | Requires CacheManager with stampede protection.
        */
        'stampede_protection' => env('LDAP_STAMPEDE_PROTECTION', true),

        /*
        | Circuit breaker settings (if integrated with a circuit breaker).
        */
        'circuit_breaker' => [
            'enabled' => env('LDAP_CIRCUIT_BREAKER', false),
            'failures_threshold' => 5,
            'timeout' => 30,
        ],
    ],
];
PHP;
    }

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
            'root'   => env('FILE_SYSTEM_ROOT') ?: storage_path('uploads'),
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
    cacheFile: storage_path('cache/routes.cache'),
    errorsDir: resource_path('views/errors'),
    controllersNamespace: 'App\\Controllers',
    rateLimiters: [
        'api' => ['max_requests' => 100, 'period' => 60],
        'login' => ['max_requests' => 5, 'period' => 300],
    ],
    viewsBasePath: resource_path('views'),
    viewsCachePath: storage_path('cache/views/'),
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
            'path' => storage_path('queue'),
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
          'path' =>  env('CACHE_LOCAL_STORAGE') ?: storage_path('cache'),
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

    public static function databaseConfigTemplate(string $database) { 
        return match (strtolower($database)) {
            "sqlite" => <<<'PHP'
<?php

/*
  |--------------------------------------------------------------------------
  | SQLite Database Configuration
  |--------------------------------------------------------------------------
  */
return [
  'default' => 'sqlite',
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
PHP,
            "mysql" => <<<'PHP'
<?php

/*
  |--------------------------------------------------------------------------
  | MYSQL Database Configuration
  |--------------------------------------------------------------------------
  */
return [
  'default' => 'mysql',
  'prefetch' => env('DB_PREFETCH', false),
  'connections' => [
    'mysql' => [
        'driver' => 'mysql',
        'host' => env('DB_HOST', null),
        'username' => env('DB_USERNAME', null),
        'password' => env('DB_PASSWORD', null),
        'database' => env('DB_DATABASE', null),
        'port' => env('DB_PORT', 3306),
        'charset' => env('DB_CHARSET', 'utf8'),
    ],
  ]
];
PHP,   
        };

  }

    public static function mailConfigTemplate () { return <<<'PHP'
<?php

return [
  'default' => env('MAIL_DRIVER', 'phpmailer'),
  'transports' => [
    'phpmailer' => [
      'transport' => 'phpmailer',
      'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
      'port' => env('MAIL_PORT', 2525),
      'encryption' => env('MAIL_ENCRYPTION', 'tls'),
      'username' => env('MAIL_USERNAME'),
      'password' => env('MAIL_PASSWORD'),
      'timeout' => null,
      'auth_mode' => null,
      'debug' => false, //debug mode for smtp
      'from_email' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
    ],
      // Add other transports as needed
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
    | Application Maintenance Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in meintenance mode, a 503 service unavailable page will be displayed
    |
    */
    'maintenance' => env('APP_MAINTENANCE', false),

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
    'url' => env('APP_URL', 'http://localhost:3000'),

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
        'files' => storage_path('session'),
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => env(
            'SESSION_COOKIE',
            'machinjiri_session'
        ),
        'path' => env('APP_URL', 'http://localhost:3000'),
        'domain' => env('SESSION_DOMAIN'),
        'secure' => env('SESSION_SECURE_COOKIE', false),
        'http_only' => true,
        'same_site' => 'lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | JSON Web Tokens Configuration
    |--------------------------------------------------------------------------
    |
    */
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