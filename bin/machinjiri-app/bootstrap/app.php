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
    session_start();
}

/* Define base and current working directory constants */
define('BASE', dirname(__DIR__) . DIRECTORY_SEPARATOR);
define('CWD', __DIR__);

/* Autoload dependencies using Composer */
$composerAutoload = BASE . 'vendor/autoload.php';
if (!is_file($composerAutoload)) {
    die('Composer autoloader not found. Run `composer install`.');
}
require $composerAutoload;

/* Import necessary classes */
use Mlangeni\Machinjiri\Core\Machinjiri;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;

// Load helper functions
require_once CWD . '/helpers.php';

/**
 * Instantiating the Machinjiri Framework
 */
$machinjiri = Machinjiri::App(CWD);
/**
 * Start App Entry Logger
 */