<?php

use Mlangeni\Machinjiri\Core\Container;
use Mlangeni\Machinjiri\App\Providers\AppServiceProvider;
use Mlangeni\Machinjiri\App\Providers\DatabaseServiceProvider;
use Mlangeni\Machinjiri\App\Providers\QueueServiceProvider;

$appRoot = dirname(__DIR__) . DIRECTORY_SEPARATOR;

if (!is_dir($appRoot)) {
    die("Invalid app base path: {$appRoot}\n");
}

$debug = filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN);

$app = new Container($appRoot, $debug, true);

$app->initialize();

$providers = [
    AppServiceProvider::class,
    DatabaseServiceProvider::class,
    QueueServiceProvider::class,
];

foreach ($providers as $providerClass) {
    $provider = new $providerClass($app);
    $provider->register();
}

foreach ($providers as $providerClass) {
    $provider = new $providerClass($app);
    $provider->boot();
}

Container::setInstance($app);

if (!function_exists('app')) {
    function app($abstract = null) {
        $container = Container::getInstance();
        return $abstract ? $container->make($abstract) : $container;
    }
}

return $app;