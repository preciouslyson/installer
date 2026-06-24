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