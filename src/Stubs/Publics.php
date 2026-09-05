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
/**
 * Machinjiri Web Application Entry Point
 *
 * This script serves as the front controller for all web requests.
 * It boots the application by loading the bootstrap file and then
 * initialises the Machinjiri instance to handle the incoming request
 * and generate the appropriate response.
 *
 * All web requests are routed through this file, which is located
 * in the public/ directory to keep the application root secure.
 *
 * @publisher  Mlangeni Group
 * @author     Mlangeni Group
 * @copyright  (c) 2026 Mlangeni Group. All rights reserved.
 * @license    Proprietary – unauthorized use, reproduction, or distribution
 *             is strictly prohibited.
 */

// Load the application bootstrap – this creates the \$machinjiri instance
\$app = require __DIR__ . '/../bootstrap/app.php';

// render all web requests
\$app->initWeb();
PHP;
    }

    public static function publicHtaccess(): string { return <<<'HT'
# =============================================================================
# Machinjiri Web Application Public .htaccess
#
# This file configures URL rewriting and security headers for the public/
# directory. It routes all non-file/non-directory requests through the
# front controller (index.php) and sets secure HTTP headers to protect
# against common web vulnerabilities.
#
# @publisher  Mlangeni Group
# @author     Mlangeni Group
# @copyright  (c) 2026 Mlangeni Group. All rights reserved.
# @license    Proprietary – unauthorized use, reproduction, or distribution
#             is strictly prohibited.
#
# @package    Machinjiri
# @see        public/index.php
# =============================================================================

# -----------------------------------------------------------------------------
# URL Rewriting – Route all requests to index.php unless file/dir exists
# -----------------------------------------------------------------------------
RewriteEngine On
RewriteBase /

# If the requested resource is not an existing directory or file,
# rewrite the URL to index.php (the front controller)
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^ index.php [L]

# -----------------------------------------------------------------------------
# Security Headers – Prevent MIME sniffing and clickjacking
# -----------------------------------------------------------------------------
<IfModule mod_headers.c>
    # Prevent browsers from interpreting files as a different MIME type
    Header always set X-Content-Type-Options nosniff
    # Prevent the page from being displayed in a frame (clickjacking protection)
    Header always set X-Frame-Options DENY
</IfModule>
HT;
    }
}