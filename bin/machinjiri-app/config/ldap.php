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
            |   LDAP_OPT_PROTOCOL_VERSION => 3
            |   LDAP_OPT_REFERRALS        => 0
            |   LDAP_OPT_NETWORK_TIMEOUT  => 5  (seconds)
            */
            'options' => [
                LDAP_OPT_PROTOCOL_VERSION => 3,
                LDAP_OPT_REFERRALS        => 0,
                LDAP_OPT_NETWORK_TIMEOUT  => env('LDAP_NETWORK_TIMEOUT', 5),
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