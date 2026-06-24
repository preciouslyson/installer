<?php

namespace Mlangeni\Machinjiri\App\Providers;

use Mlangeni\Machinjiri\Core\Providers\ServiceProvider;
use Mlangeni\Machinjiri\Core\Http\HttpRequest;
use Mlangeni\Machinjiri\Core\Http\HttpResponse;
use Mlangeni\Machinjiri\Core\Authentication\Session;
use Mlangeni\Machinjiri\Core\Authentication\Cookie;
use Mlangeni\Machinjiri\Core\Authentication\AuthManager;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;
use Mlangeni\Machinjiri\Core\Artisans\Events\EventListener;
use Mlangeni\Machinjiri\Core\Debug\Debugger;
use Mlangeni\Machinjiri\Core\FileSystem\FileSystemManager;
use Mlangeni\Machinjiri\Core\FileSystem\Adapters\LocalAdapter;
use Mlangeni\Machinjiri\Core\FileSystem\Adapters\FtpAdapter;
use Mlangeni\Machinjiri\Core\Artisans\Caching\CacheManager;
use Mlangeni\Machinjiri\Core\Transport\Mail\MailManager;
use Mlangeni\Machinjiri\Core\Security\Tokens\CSRFToken;
use Mlangeni\Machinjiri\Core\Routing\RoutingConfig;
use Mlangeni\Machinjiri\Core\Security\Hashing\Hasher;
use Mlangeni\Machinjiri\Core\Security\Encryption\Bangwe;
use Mlangeni\Machinjiri\Core\Authentication\ThirdParty\ThirdPartyAuth;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register core application services
     */
    public function register(): void
    {
        // Register HTTP request/response as singletons
        $this->singleton(HttpRequest::class, function($app) {
            return HttpRequest::createFromGlobals();
        });

        $this->singleton(HttpResponse::class, function($app) {
            return new HttpResponse();
        });

        // Register authentication services
        $this->singleton(Session::class);
        $this->singleton(Cookie::class);

        $this->singleton(AuthManager::class, function ($app) {
            $config = $app->configurations['auth'] ?? false;
            if (!$config) throw new MachinjiriException("App Service Error: auth config not found");
            return new AuthManager($app, $config);
        });
        
        // Register Debugger
        $this->app->singleton(Debugger::class, function ($app) {
            return new Debugger($app);
        });
        
        $this->app->singleton(LocalAdapter::class, function ($app) {
            return new LocalAdapter($app->configurations['filesystem']['disks']['local']['root']);
        });
        
        $this->app->singleton(FtpAdapter::class, function ($app) {
            return new FtpAdapter($app->configurations['filesystem']['disks']['ftp']);
        });
        
        $this->app->singleton(FileSystemManager::class, function ($app) {
            return new FileSystemManager($app->configurations['filesystem']);
        });
        
        $this->app->singleton(CacheManager::class, function ($app) {
            return new CacheManager($app->configurations['cache']);
        });
        
        $this->app->singleton(MailManager::class, function ($app) {
          $logger = new Logger('mailer-transport', Logger::DEBUG, true);
          return new MailManager($app, null,$logger, new EventListener($logger), null, $app->resolve('queue.dispatcher'));
        });

        // Register EventListener service
        $this->bind(EventListener::class, function($app) {
            return new EventListener(new Logger(env('APP_NAME') ?? 'machinjiri', Logger::DEBUG, true));
        });
        
        $this->bind(Logger::class, function($app) {
            return new Logger(env('APP_NAME') ?? 'machinjiri', Logger::DEBUG);
        });
        
        $this->app->singleton(CSRFToken::class, function ($app) {
            return new CSRFToken($app->resolve(Session::class), $app->resolve(Cookie::class), env("CSRF_TOKEN_NAME", "csrf_token"));
        });

        $this->singleton(RoutingConfig::class, function ($app) {
            $config = $this->app->config . 'routing.php';
            return is_file($config) ? require $config : null;
        });

        $this->singleton(Hasher::class, function ($app) {
            return new Hasher();
        });

        $this->singleton(Bangwe::class, function ($app) {
            return new Bangwe($app);
        });

        $this->singleton(ThirdPartyAuth::class, function ($app) {
            return new ThirdPartyAuth($app->configurations['oauth']);
        });

        $this->singleton('ldap.manager', function ($app) {
            return new  \Mlangeni\Machinjiri\Core\Components\LDAP\Manager($app->configurations['ldap']);
        });

        // Register aliases for easier access
        $this->aliasMany([
            'request' => HttpRequest::class,
            'response' => HttpResponse::class,
            'auth.session' => Session::class,
            'auth.cookie' => Cookie::class,
            'auth.thirdparty' => ThirdPartyAuth::class,
            'debugger' => Debugger::class,
            'events' => EventListener::class,
            'fs.adapter.local' => LocalAdapter::class,
            'fs.adapter.ftp' => FtpAdapter::class,
            'fs.manager' => FileSystemManager::class,
            'cache.manager' => CacheManager::class,
            'mail.manager' => MailManager::class,
            'logger' => Logger::class,
            'routing.config' => RoutingConfig::class,
            'auth.manager' => AuthManager::class,
            'security.hasher' => Hasher::class,
            'security.bangwe' => Bangwe::class,
        ]);

    }

    /**
     * Bootstrap application services
     */
    public function boot(): void
    {
        // Load application configuration
        $configDir = $this->app->config;
        if (is_dir($configDir)) {
            $this->mergeConfigFrom($configDir . 'app.php', 'app');
            $this->mergeConfigFrom($configDir . 'filesystem.php', 'filesystem');
            $this->mergeConfigFrom($configDir . 'database.php', 'database');
            $this->mergeConfigFrom($configDir . 'cache.php', 'cache');
            $this->mergeConfigFrom($configDir . 'mail.php', 'mail');
            $this->mergeConfigFrom($configDir . 'queue.php', 'queue');
            $this->mergeConfigFrom($configDir . 'auth.php', 'auth');
            $this->mergeConfigFrom($configDir . 'oauth.php', 'oauth');
            $this->mergeConfigFrom($configDir . 'ldap.php', 'ldap');
        }

    }

    public function provides(): array
    {
        return array_merge(
            array_keys($this->bindings),
            array_keys($this->singletons),
            array_keys($this->aliases)
        );
    }
}