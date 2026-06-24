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