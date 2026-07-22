<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class ServiceProviders {

    public static function DatabaseServiceProviderTemplate () { return <<<'PHP'
<?php

namespace Mlangeni\Machinjiri\App\Providers;

use Mlangeni\Machinjiri\Core\Providers\ServiceProvider;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;
use Mlangeni\Machinjiri\Core\Database\Seeder\SeederManager;
use Mlangeni\Machinjiri\Core\Database\Factory\FactoryManager;
use Mlangeni\Machinjiri\Core\Database\Migrations\MigrationHandler;
use Mlangeni\Machinjiri\Core\Database\Migrations\MigrationCreator;
use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;
use Mlangeni\Machinjiri\Core\Database\Caching\PrefetchManager;
use Mlangeni\Machinjiri\Core\Artisans\Caching\CacheManager;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register core application services
     */
    public function register(): void
    {
        $this->singleton('db.kernel.connection', function($app) {
          $config = $app->getConfigurations()['database'];
          DatabaseConnection::setConfig($config);
          DatabaseConnection::setPath($app->database);
          return DatabaseConnection::getInstance();
        });
        
        $this->singleton(MigrationCreator::class, function ($app) {
          return new MigrationCreator();
        });
        
        $this->singleton(MigrationHandler::class, function ($app) {
          return new MigrationHandler();
        });
        
        $this->singleton(SeederManager::class, function ($app) {
          return new SeederManager($app);
        });
        
        $this->singleton(FactoryManager::class, function ($app) {
          return new FactoryManager($app);
        });
        
        $this->aliasMany([
          'db.migration.creator' => MigrationCreator::class,
          'db.migration.handler' => MigrationHandler::class,
          'db.seeder.manager' => SeederManager::class,
          'db.factory.manager' => FactoryManager::class,
        ]);
    }
    
    public function boot(): void
    {
      try {
        $this->prefetchDatabase();
      } catch (MachinjiriException $machinjiriException) {
        $machinjiriException->show();
      }
    }
    
    /**
     * Prefetch database queries to cache.
     * @return void
     */
    public function prefetchDatabase(): void
    {
        $prefetchEnabled = filter_var(env('DB_PREFETCH') ?: 'false', FILTER_VALIDATE_BOOLEAN);
        if ($prefetchEnabled) {
          $prefetchFile = $this->app->database . "cache-prefetch-db.php";
          if (!is_dir($this->app->database) || !is_file($prefetchFile)) {
            throw new MachinjiriException("Unable to find Prefetch Database file. [cache-prefetch-db.php]");
          }
          
          $warmers = require $prefetchFile;
          
          if (!is_array($warmers)) {
              throw new MachinjiriException("Prefetch file must return an array of callbacks in {$prefetchFile}");
          }
          
          if (!$this->bound(CacheManager::class)) {
            throw new MachinjiriException('CacheManager not bound – cannot prefetch database queries');
          }
          
          $cacheManager = $this->resolve(CacheManager::class);
          $prefetchManager = new PrefetchManager($cacheManager);
          $logger = new Logger("db-prefetch-provider");
          
          foreach ($warmers as $name => $callback) {
            if (!is_callable($callback)) {
                $logger->warning("Prefetch warmer {$name} is not callable, skipping");
                continue;
            }
      
            try {
              $callback($prefetchManager);
              $logger->info("Database prefetch warmer executed \n table => {warmer}", ['warmer' => $name]);
            } catch (MachinjiriException $e) {
              throw new MachinjiriException(" Failed to Warmer on table '{$name}' due to: " . $e->getMessage());
            }
          }
        }
    }
    /**
     * Get the services provided by the provider
     */
    public function provides(): array
    {
        return array_merge(
            array_keys($this->bindings),
            array_keys($this->singletons),
            array_keys($this->aliases)
        );
    }
}
PHP;
    }

    public static function QueueServiceProviderTemplate() { return <<<'PHP'
<?php

namespace Mlangeni\Machinjiri\App\Providers;

use Mlangeni\Machinjiri\Core\Providers\ServiceProvider;
use Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException;
use Mlangeni\Machinjiri\Core\Artisans\Contracts\QueueInterface;
use Mlangeni\Machinjiri\Core\Artisans\Contracts\BaseWorker;
use Mlangeni\Machinjiri\Core\Artisans\Contracts\BaseJobDispatcher;
use Mlangeni\Machinjiri\App\Queue\Drivers\DatabaseQueue;
use Mlangeni\Machinjiri\App\Queue\Drivers\FileQueue;
use Mlangeni\Machinjiri\App\Queue\Drivers\MemoryQueue;
use Mlangeni\Machinjiri\App\Queue\Drivers\RedisQueue;
use Mlangeni\Machinjiri\App\Queue\Drivers\SyncQueue;

class QueueServiceProvider extends ServiceProvider
{
    /**
     * Register QueueService Services
     */
    public function register(): void
    {
        // Register queue bindings
        $this->bind('queue', function($app) {
            $config = $app->getConfigurations()['queue'] ?? [];
            $driver = $config['default'] ?? getenv('QUEUE_DRIVER');
            return $this->createQueueDriver($driver, $config);
        });
        
        // Register queue worker
        $this->singleton('queue.worker', function($app) {
            $queue = $app->resolve('queue');
            $processor = $app->resolve('queue.processor');
            return new BaseWorker($app, $queue, $processor);
        });
        
        // Register job processor
        $this->singleton('queue.processor', function($app) {
            return new class($app) extends \Mlangeni\Machinjiri\Core\Artisans\Contracts\BaseJobProcessor {};
        });
        
        // Register job dispatcher
        $this->singleton('queue.dispatcher', function($app) {
            $queue = $app->resolve('queue');
            return new BaseJobDispatcher($app, $queue);
        });
    }

    /**
     * Bootstrap services
     */
    public function boot(): void
    {
        // Load queue configuration
        $this->mergeConfigFrom($this->app->config . 'queue.php', 'queue');
        
        // Create jobs table if using database driver
        $this->createJobsTableIfNeeded();
        
    }

    /**
     * Create queue driver instance
     */
    protected function createQueueDriver(string $driver, array $config): QueueInterface
    {
        $driverConfig = $config['drivers'][$driver] ?? [];
        
        switch ($driver) {
            case 'database':
                return new DatabaseQueue($this->app, $driver, $driverConfig);
            case 'redis':
                return new RedisQueue($this->app, $driver, $driverConfig);
            case 'file':
                return new FileQueue($this->app, $driver, $driverConfig);
            case 'memory':
                return new MemoryQueue($this->app, $driver, $driverConfig);
            case 'sync':
                return new SyncQueue($this->app, $driver, $driverConfig);
            default:
                // Try to load custom driver
                $driverClass = "Mlangeni\\Machinjiri\\App\\Queue\\Drivers\\" . ucfirst($driver) . 'Queue';
                if (class_exists($driverClass)) {
                    return new $driverClass($this->app, $driver, $driverConfig);
                }
                
                throw new MachinjiriException("Queue driver not found: {$driver}. Try running php artisan queue:init");
        }
    }

    /**
     * Create jobs table if needed
     */
    protected function createJobsTableIfNeeded(): void
    {
        $config = $this->getConfigurations()['queue'] ?? [];
        $driver = $config['default'] ?? 'sync';
        
        if ($driver === 'database') {
            $table = $config['drivers']['database']['table'] ?? 'jobs';
            
            $query = new \Mlangeni\Machinjiri\Core\Database\QueryBuilder('');
            $sql = $query->createTable($table, [
                'id' => $query->id()->primary()->autoincrement(),
                'queue' => $query->string('queue', 255)->notNull(),
                'payload' => $query->text('payload'),
                'attempts' => $query->integer('attempts')->default(0),
                'reserved_at' => $query->integer('reserved_at')->default(0),
                'available_at' => $query->integer('available_at')->notNull(),
                'created_at' => $query->integer('created_at')->notNull(),
            ], ['if_not_exists' => true])->compileCreateTable();
            
            \Mlangeni\Machinjiri\Core\Database\DatabaseConnection::executeQuery($sql);
        }
    }
}
PHP;
  }
    
    public static function AppServiceProviderTemplate () { return <<<'PHP'
<?php

/**
 * Application Service Provider
 *
 * This service provider is responsible for registering and bootstrapping
 * core application services. It binds interfaces to concrete implementations,
 * registers singleton instances, sets up configuration, and provides aliases
 * for easier access via the service container.
 *
 * @package Mlangeni\Machinjiri\App\Providers
 */

namespace Mlangeni\Machinjiri\App\Providers;

use Mlangeni\Machinjiri\Core\Providers\ServiceProvider;
use Mlangeni\Machinjiri\Core\Http\HttpRequest;
use Mlangeni\Machinjiri\Core\Http\HttpResponse;
use Mlangeni\Machinjiri\Core\Authentication\Session;
use Mlangeni\Machinjiri\Core\Authentication\Cookie;
use Mlangeni\Machinjiri\Core\Authentication\AuthManager;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;
use Mlangeni\Machinjiri\Core\Artisans\Logging\LoggerFactory;
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

// Note: The class below references MachinjiriException, but it is not imported.
// This is likely a framework exception that should be available via a use statement
// or from the global namespace. For now, the code remains as-is.

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register core application services.
     *
     * This method is called during the service container's registration phase.
     * All bindings and singletons are defined here. Services like HTTP handling,
     * authentication, logging, filesystem, caching, mailing, and security are
     * registered with the container.
     *
     * @return void
     */
    public function register(): void
    {
        // -------------------- HTTP Request/Response --------------------
        // Register the HTTP request as a singleton, created from global PHP superglobals.
        $this->singleton(HttpRequest::class, function($app) {
            return HttpRequest::createFromGlobals();
        });

        // Register the HTTP response as a singleton for output manipulation.
        $this->singleton(HttpResponse::class, function($app) {
            return new HttpResponse();
        });

        // -------------------- Authentication Services --------------------
        // Session and Cookie handlers are singletons for state management.
        $this->singleton(Session::class);
        $this->singleton(Cookie::class);

        // AuthManager handles authentication logic; requires configuration.
        $this->singleton(AuthManager::class, function ($app) {
            $config = $app->configurations['auth'] ?? false;
            // Ensure authentication configuration exists; otherwise throw an exception.
            if (!$config) throw new MachinjiriException("App Service Error: auth config not found");
            return new AuthManager($app, $config);
        });

        // -------------------- Debugging --------------------
        // Debugger is registered as a singleton for centralized debugging.
        $this->app->singleton(Debugger::class, function ($app) {
            return new Debugger($app);
        });

        // -------------------- Filesystem Adapters --------------------
        // LocalAdapter uses the root path from the filesystem configuration.
        $this->app->singleton(LocalAdapter::class, function ($app) {
            return new LocalAdapter($app->configurations['filesystem']['disks']['local']['root']);
        });

        // FtpAdapter uses the FTP configuration from the filesystem settings.
        $this->app->singleton(FtpAdapter::class, function ($app) {
            return new FtpAdapter($app->configurations['filesystem']['disks']['ftp']);
        });

        // FileSystemManager manages multiple disks; passed the full filesystem config.
        $this->app->singleton(FileSystemManager::class, function ($app) {
            return new FileSystemManager($app->configurations['filesystem']);
        });

        // -------------------- Caching --------------------
        // CacheManager handles caching strategies using the cache configuration.
        $this->app->singleton(CacheManager::class, function ($app) {
            return new CacheManager($app->configurations['cache']);
        });

        // -------------------- Mail Transport --------------------
        // MailManager is responsible for sending emails; it creates its own logger
        // and event listener internally, and uses a queue dispatcher if available.
        $this->app->singleton(MailManager::class, function ($app) {
            $log = "mailer-transport";
            return new MailManager(
                $app,
                null,
                new Logger('mailer-transport', Logger::DEBUG, false, '', 'system'),
                new EventListener(
                    new Logger('mailer-transport', Logger::DEBUG, true, '', 'system')
                ),
                null,
                $app->resolve('queue.dispatcher')
            );
        });

        // -------------------- Event System --------------------
        // EventListener is bound as a regular binding (not singleton) to allow
        // fresh instances with a dedicated logger.
        $this->bind(EventListener::class, function($app) {
            return new EventListener(new Logger(env('APP_NAME') ?? 'machinjiri', Logger::DEBUG, true));
        });

        // -------------------- Logging --------------------
        // Logger binding uses the application name from environment, with DEBUG level.
        $this->bind(Logger::class, function($app) {
            return new Logger(env('APP_NAME') ?? 'machinjiri', Logger::DEBUG);
        });

        // -------------------- CSRF Protection --------------------
        // CSRFToken singleton requires Session, Cookie, and a token name from env.
        $this->app->singleton(CSRFToken::class, function ($app) {
            return new CSRFToken(
                $app->resolve(Session::class),
                $app->resolve(Cookie::class),
                env("CSRF_TOKEN_NAME", "csrf_token")
            );
        });

        // -------------------- Routing Configuration --------------------
        // RoutingConfig loads the routing.php configuration file if it exists.
        $this->singleton(RoutingConfig::class, function ($app) {
            $config = $this->app->config . 'routing.php';
            return is_file($config) ? require $config : null;
        });

        // -------------------- Security Services --------------------
        // Hasher provides hashing utilities (bcrypt, etc.) as a singleton.
        $this->singleton(Hasher::class, function ($app) {
            return new Hasher();
        });

        // Bangwe is the encryption service, requiring the application instance.
        $this->singleton(Bangwe::class, function ($app) {
            return new Bangwe($app);
        });

        // -------------------- Third-Party Authentication --------------------
        // ThirdPartyAuth uses the OAuth configuration from the application config.
        $this->singleton(ThirdPartyAuth::class, function ($app) {
            return new ThirdPartyAuth($app->configurations['oauth']);
        });

        // -------------------- LDAP Manager --------------------
        // Custom LDAP manager registered with a string alias, using LDAP config.
        $this->singleton('ldap.manager', function ($app) {
            return new \Mlangeni\Machinjiri\Core\Components\LDAP\Manager($app->configurations['ldap']);
        });

        // -------------------- Aliases for Convenience --------------------
        // Provide shorter names for common services to simplify dependency resolution.
        $this->aliasMany([
            'request'           => HttpRequest::class,
            'response'          => HttpResponse::class,
            'auth.session'      => Session::class,
            'auth.cookie'       => Cookie::class,
            'auth.thirdparty'   => ThirdPartyAuth::class,
            'debugger'          => Debugger::class,
            'events'            => EventListener::class,
            'fs.adapter.local'  => LocalAdapter::class,
            'fs.adapter.ftp'    => FtpAdapter::class,
            'fs.manager'        => FileSystemManager::class,
            'cache.manager'     => CacheManager::class,
            'mail.manager'      => MailManager::class,
            'logger'            => Logger::class,
            'routing.config'    => RoutingConfig::class,
            'auth.manager'      => AuthManager::class,
            'security.hasher'   => Hasher::class,
            'security.bangwe'   => Bangwe::class,
        ]);
    }

    /**
     * Bootstrap application services.
     *
     * This method is called after all service providers have been registered.
     * It loads the various configuration files from the config directory and merges
     * them into the application's configuration repository.
     *
     * @return void
     */
    public function boot(): void
    {
        // Get the configuration directory path from the application.
        $configDir = $this->app->config;

        // If the config directory exists, load each configuration file.
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
            $this->mergeConfigFrom($configDir . 'logger.php', 'logger');
        }
    }

    /**
     * Get the services provided by this provider.
     *
     * This method returns an array of service names (abstracts or aliases)
     * that this provider registers. It is used by the container to optimize
     * deferred service loading.
     *
     * @return array
     */
    public function provides(): array
    {
        // Combine all bindings, singletons, and aliases into a single list.
        return array_merge(
            array_keys($this->bindings),
            array_keys($this->singletons),
            array_keys($this->aliases)
        );
    }
}
PHP;
    }
    
}