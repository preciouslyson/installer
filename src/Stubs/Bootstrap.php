<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class Bootstrap {
    
    public static function bootstrapTemplate(): string { return <<<PHP
<?php
/*
 * Application Bootstrapper
 * This file initializes the Machinjiri application.
 * It sets up the environment, loads configurations, and prepares the application for handling requests.
 */

/* Enable strict typing*/
declare(strict_types=1);

/* Start session management */
if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

/* Define base and current working directory constants */
@define('BASE', dirname(__DIR__) . DIRECTORY_SEPARATOR);
@define('BOOTSTRAP', __DIR__);

/* Autoload dependencies using Composer */
\$composerAutoload = BASE . 'vendor/autoload.php';
if (!is_file(\$composerAutoload)) {
    die('Composer autoloader not found. Run `composer install`.');
}
require \$composerAutoload;

/* Import Application  */
use Mlangeni\Machinjiri\Core\Machinjiri as App;
use Mlangeni\Machinjiri\Core\Container;
use Mlangeni\Machinjiri\Core\Artisans\Helpers\HelperLoader;

// load app helper methods
HelperLoader::getHelperMethods();

// Check if application is being accessed from Web or Artisan CLI Interface
if (PHP_SAPI === "cli" || defined('STDIN')) {
    return new Container(BOOTSTRAP);
}

return App::App(BOOTSTRAP);
PHP;
                                                       }
    
}