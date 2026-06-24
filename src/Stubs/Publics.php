<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class Publics
{
    public static function cssTemplate(): string { return <<<'CSS'
/* Define your custom css rules here ... */
CSS;
    }

    public static function jsTemplate(): string { return <<<'JS'
/* Write your javascript rules here ... */
JS;
    }
    
    public static function publicIndexTemplate(): string { return <<<PHP
<?php
/*
 * Public Entry Point
 * @Author: Precious Lyson
 * This file serves as the front controller for all HTTP requests.
 * It bootstraps the application and handles incoming requests.
 * Make sure to keep this file secure and do not expose sensitive information.
 */
require __DIR__ . '/../bootstrap/app.php';
/**
 * Initialize Machinjiri Framework
 */
\$machinjiri->init();
/* Handle the incoming request and send the response */
PHP;
    }

    public static function publicHtaccess(): string { return <<<'HT'
RewriteEngine On
RewriteBase /

RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]

<IfModule mod_headers.c>
    Header always set X-Content-Type-Options nosniff
    Header always set X-Frame-Options DENY
</IfModule>
HT;
    }
}