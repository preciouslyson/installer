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