<?php

namespace Preciouslyson\MachinjiriInstaller;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Preciouslyson\MachinjiriInstaller\StarterkitManager;

class Installer
{
    private SymfonyStyle $io;
    private ?string $composer;
    private string $projectName;
    private string $projectDir;
    private array $options;
    private $progressCallback = null;
    private ?ProjectValidator $validator = null;
    private ?InstallerLogger $logger = null;
    private ?InstallationManager $manager = null;
    const MIN_DISK_SPACE_MB = 100;
    
    public function __construct(SymfonyStyle $io, bool $verbose = false)
    {
        $this->io = $io;
        $this->composer = $this->findComposer();
    }

    public function install(string $projectName, array $options = []): void
    {
        $this->projectName = $projectName;
        $this->projectDir = getcwd() . DIRECTORY_SEPARATOR . $projectName;
        $this->options = $options;
        $this->validator = new ProjectValidator($this->io);
        $this->logger = new InstallerLogger($this->projectDir, $this->io, $options['verbose'] ?? false);

        try {
            $this->progress(1, 'Validating system requirements...');
            $this->checkRequirements();
            $this->progress(2, 'Validating project configuration...');
            $this->validateProject($projectName);
            $this->progress(3, 'Preparing project directory...');
            $this->prepareDirectory();
            $this->progress(4, 'Creating project structure...');
            $this->createProjectStructure();
            $this->progress(5, 'Creating project files...');
            $this->createFiles();
            $this->progress(6, 'Writing composer.json...');
            $this->writeComposerJson($projectName);
            $this->progress(7, 'Writing environment configuration...');
            $this->writeEnvironmentFile();
            $this->progress(8, 'Installing dependencies via Composer...');
            $this->runComposerInstall();
            $this->progress(9, 'Generating application key...');
            $this->generateAppKey();
            $this->progress(10, 'Validating installation...');
            $this->validateInstallation();
            
            $this->logger->success('Installation completed successfully');
        } catch (\Exception $e) {
            $this->logger->error('Installation failed', $e);
            throw $e;
        }
    }

    private function checkRequirements(): void
    {
        try {
            $this->validator->validatePhpVersion('8.0.0');
            $this->validator->validateExtensions(['json', 'mbstring', 'openssl']);
            
            if (!$this->composer) {
                throw new \RuntimeException('Composer is not installed or not found in PATH');
            }

            $this->logger->info('All system requirements validated');
        } catch (\Exception $e) {
            $this->logger->error('System requirement check failed', $e);
            throw $e;
        }
    }

    private function validateProject(string $projectName): void
    {
        try {
            $this->validator->validateProjectName($projectName);
            $this->validator->validateDiskSpace($this->projectDir, self::MIN_DISK_SPACE_MB);
            $this->validator->validateWritePermissions($this->projectDir);
            
            $this->logger->info("Project validation passed for: {$projectName}");
        } catch (\Exception $e) {
            $this->logger->error('Project validation failed', $e);
            throw $e;
        }
    }

    private function prepareDirectory(): void
    {
        if (is_dir($this->projectDir)) {
            if (!$this->options['force']) {
                if ($this->options['no-interaction'] ?? false) {
                    throw new \RuntimeException(
                        "Directory {$this->projectDir} already exists. Use --force to overwrite."
                    );
                }
            }
            $this->removeDirectory($this->projectDir);
        }

        if (!mkdir($this->projectDir, 0755, true)) {
            throw new \RuntimeException("Failed to create directory: {$this->projectDir}");
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? $this->removeDirectory($path) : unlink($path);
        }

        rmdir($dir);
    }

    private function createProjectStructure(): void
    {
        $directories = [
          'bootstrap',
          'public',
          'routes',
          'resources/views',
          'resources/views/layouts',
          'resources/views/partials',
          'database',
          'database/factories',
          'database/migrations',
          'database/seeders',
          'storage',
          'storage/app',
          'storage/store',
          'storage/framework',
          'storage/framework/queue',
          'storage/session',
          'storage/cache',
          'storage/logs',
          'storage/logs/reports',
          'storage/logs/events',
          'app',
          'app/Controllers',
          'app/Middleware',
          'app/Model',
          'app/Providers',
          'app/Queue/Drivers',
          'tests/Unit',
          'tests/Features',
          'config',
          'config/services'
        ];

        foreach ($directories as $directory) {
            $path = $this->projectDir . DIRECTORY_SEPARATOR . $directory;
            if (!is_dir($path)) {
                if ($this->options['dry-run'] ?? false) {
                    $this->logger->info("[DRY-RUN] Would create directory: {$directory}");
                } else {
                    mkdir($path, 0755, true);
                    $this->logger->info("Created directory: {$directory}");
                }
            }
        }
    }

    private function writeComposerJson(string $projectName): void
    {
        $version = $this->options['version'] ?? '*';
        
        $vendor = 'machinjiri';
        $packageName = strtolower(str_replace(' ', '-', $projectName));
        
        $composerJson = [
            'name' => $vendor . '/' . $packageName,
            'description' => 'A Machinjiri Framework application',
            'type' => 'project',
            'require' => [
                'php' => '^8.0',
                'machinjiri/framework' => $version,
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^10.0',
            ],
            'autoload' => [
                'psr-4' => [
                    'Mlangeni\\Machinjiri\\App\\' => 'app/',
                    'Mlangeni\\Machinjiri\\Database\\' => "database/",
                ],
                'files' => [
                    'bootstrap/helpers.php',
                ],
            ],
            'autoload-dev' => [
                'psr-4' => [
                    'Mlangeni\\Machinjiri\\Tests\\' => 'tests/',
                ],
            ],
            'scripts' => [
                'test' => 'phpunit'
            ],
            'minimum-stability' => 'dev',
            'prefer-stable' => true,
        ];
    
        file_put_contents(
            $this->projectDir . '/composer.json',
            json_encode($composerJson, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
    
    private function validateInstallation(): bool
    {
        $requiredFiles = [
          'bootstrap/app.php',
          'public/index.php',
          '.env',
          'composer.json'
        ];
        
        foreach ($requiredFiles as $file) {
            if (!file_exists($this->projectDir . '/' . $file)) {
                throw new \RuntimeException("Required file missing: {$file}");
            }
        }
        return true;
    }

    public function setProgressCallback(callable $callback)
    {
        $this->progressCallback = $callback;
    }

    protected function progress($step, $message)
    {
        if ($this->progressCallback) {
            call_user_func($this->progressCallback, $step, $message);
        }
    }

    private function writeEnvironmentFile(): void
    {
        $envContent = <<<ENV
# -------------------------------------------------
#   Application configurations                    |
# -------------------------------------------------
APP_NAME="{$this->projectName}"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:3000
APP_KEY=null
APP_CIPHER=aes-256-gcm
APP_VERSION=1.0.0
APP_SUPPORT_EMAIL=admin@example.com

# ------------------------------------------------
# Client Site Forgery (CSRF Token Name)          |
# ------------------------------------------------
CSRF_TOKEN_NAME=token-name-here

# ------------------------------------------------
# Error Reporting - to support email             |
# ------------------------------------------------
REPORT_ERRORS=false

# ------------------------------------------------
# Database Configuration                         |
# ------------------------------------------------
DB_FOREIGN_KEYS=true
DB_PREFETCH=true
# Database Configuration (Sqlite) Default
# ------------------------------------------------
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

# Database Configuration (MYSQL, PostGres, etc)
# ------------------------------------------------
# DB_CONNECTION=mysql
# DB_HOST=127.0.0.1
# DB_USERNAME=root
# DB_PASSWORD=
# DB_DATABASE=week
# DB_PORT=3306

# -------------------------------------------------
# Cache Configuration                             |
# -------------------------------------------------
CACHE_DRIVER=redis
CACHE_PREFIX=machinjiri_cache
CACHE_DEFAULT_TTL=300
CACHE_LOCAL_STORAGE=

# -------------------------------------------------
# Session Configuration                           |
# -------------------------------------------------
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_COOKIE=machinjiri_session
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false

# ------------------------------------------------
# Queue Configuration                            |
# ------------------------------------------------
QUEUE_DRIVER=sync
QUEUE_FAILED_DRIVER=database
# Worker limits
# MB - worker restarts when exceeded
QUEUE_WORKER_MAX_MEMORY=256
# jobs - worker restarts after processing this many
QUEUE_WORKER_MAX_JOBS=1000

# Supervisor monitor settings
QUEUE_WORKER_CHECK_INTERVAL=5
# seconds after which a worker is considered dead
QUEUE_WORKER_HEARTBEAT_TTL=15
# seconds to wait before SIGKILL during shutdown
QUEUE_WORKER_GRACE_PERIOD=10
# whether worker exits when queue is empty
QUEUE_WORKER_STOP_ON_EMPTY=false
# Heartbeat interval inside the worker (implemented in BaseWorker)
QUEUE_WORKER_HEARTBEAT_INTERVAL=60

# ------------------------------------------------
# Mailer Configuration                           |
# ------------------------------------------------
MAIL_DRIVER=phpmailer
MAIL_DEBUG=0
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_ENCRYPTION=tls
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=your-email-address
MAIL_FROM_NAME=sanity-fm

# -------------------------------------------------
# Redis Server Configuration                      |
# -------------------------------------------------
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DATABASE=0

# -------------------------------------------------
# Logging Configuration                           |
# -------------------------------------------------
LOG_CHANNEL=stack
LOG_LEVEL=debug

# ------------------------------------------------
# Asset Configuration                            |
# ------------------------------------------------
ASSET_URL=http://localhost:3000

# -------------------------------------------------
# View Configuration                              |
# -------------------------------------------------
VIEW_COMPILED_PATH=storage/framework/views

# ------------------------------------------------
# Bangwe Encryption Configuration                |
# ------------------------------------------------
JWT_SECRET={APP_KEY}
JWT_ALGO=HS256
JWT_EXPIRATION=3600
JWT_ISSUER=sanity-fm
JWT_AUDIENCE=sanity-fm

# ------------------------------------------------
# File System Configuration                      |
# ------------------------------------------------
FILE_SYSTEM_DEFAULT_DRIVER=local
FILE_SYSTEM_ROOT=

# ftp connection
FILE_SYSTEM_FTP_HOST=ftp.example.com
FILE_SYSTEM_FTP_USER=your-username
FILE_SYSTEM_FTP_PASSWORD=secret
FILE_SYSTEM_FTP_ROOT=public_html/uploads
FILE_SYSTEM_FTP_PORT=21
FILE_SYSTEM_FTP_SSL=null
FILE_SYSTEM_FTP_PASSIVE=null
FILE_SYSTEM_FTP_TIMEOUT=90
ENV;

        $envPath = $this->projectDir . '/.env';
        file_put_contents($envPath, $envContent);
        
        // Set secure permissions on .env file (readable by owner only)
        @chmod($envPath, 0600);
        
        $this->logger->info('.env file created with secure permissions');
    }

    private function runComposerInstall(): void
    {
        $args = [
            $this->composer,
            'install',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
            '--quiet'
        ];

        if ($this->options['no-dev'] ?? false) {
            $args[] = '--no-dev';
        }

        if ($this->options['no-scripts'] ?? false) {
            $args[] = '--no-scripts';
        }

        $process = new Process($args, $this->projectDir);
        $process->setTimeout(300);

        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->io->text($buffer);
            } else {
                $this->io->write($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }
    }

    private function generateAppKey(): void
    {
        $key = 'base64:' . base64_encode(random_bytes(32));
        $envPath = $this->projectDir . '/.env';
        
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/APP_KEY=.*/', "APP_KEY={$key}", $envContent);
            file_put_contents($envPath, $envContent);
        }
    }
    
    private function getTemplateData(): array
    {
      return [
        'version' => $this->options['version'] ?? 'latest',
        'project_name' => basename($this->projectDir),
        'date' => date('Y-m-d'),
      ];
    }

    private function findComposer(): ?string
    {
        if (file_exists(getcwd() . '/composer.phar')) {
            return '"' . PHP_BINARY . '" composer.phar';
        }

        if (file_exists(getcwd() . '/composer')) {
            return '"' . PHP_BINARY . '" composer';
        }

        $process = new Process(['which', 'composer']);
        $process->run();

        if ($process->isSuccessful()) {
            return trim($process->getOutput());
        }

        return 'composer';
    }
    
    private function createFiles(): void
    {
        $write = function (string $path, string $content, int $mode = 0644) {
            if (file_exists($path) && !$this->options['force']) {
                return;
            }
            $dir = dirname($path);
            if (!is_dir($dir) && !mkdir($dir, 0777, true)) {
                throw new RuntimeException("Failed to create directory for file: {$dir}");
            }
            if (file_put_contents($path, $content) === false) {
                throw new RuntimeException("Failed to write file: {$path}");
            }
            @chmod($path, $mode);
        };

        $write($this->projectDir . '/bootstrap/app.php', $this->bootstrapTemplate());
        $write($this->projectDir . '/bootstrap/helpers.php', $this->helpersFileTemplate());
        $write($this->projectDir . '/public/index.php', $this->publicIndexTemplate());
        // create startkit files 
        (new StarterkitManager($this->projectDir))
        ->install($this->options['starter'], $write, $this->getTemplateData());

        $write($this->projectDir . '/.gitignore', $this->gitIgnoreTemplate());

        $write($this->projectDir . '/artisan', $this->artisanTemplate(), 0755);
        @chmod($this->projectDir . '/artisan', 0755);

        $write($this->projectDir . '/.htaccess', $this->rootHtaccess());
        $write($this->projectDir . '/public/.htaccess', $this->publicHtaccess());
        
        $write($this->projectDir . '/config/providers.php', $this->providersTemplate());
        $write($this->projectDir . '/config/app.php', $this->appConfigTemplate());
        $write($this->projectDir . '/config/mail.php', $this->mailConfigTemplate());
        $write($this->projectDir . '/config/database.php', $this->databaseConfigTemplate());
        $write($this->projectDir . '/config/cache.php', $this->cacheConfigTemplate());
        $write($this->projectDir . '/bootstrap/artisan.php', $this->artisanBootstrapTemplate());
        $write($this->projectDir . '/config/filesystem.php', $this->fileSystemConfigurationTemplate());
        $write($this->projectDir . '/app/Providers/AppServiceProvider.php', $this->AppServiceProviderTemplate());
        $write($this->projectDir . '/app/Providers/DatabaseServiceProvider.php', $this->DatabaseServiceProviderTemplate());
        $write($this->projectDir . '/database/cache-prefetch-db.php', $this->dbCachePrefetchTemplate());
        $write($this->projectDir . '/phpunit.xml', $this->phpunitTemplate());
    }
    
    /* ---------- Helpers & templates ---------- */
    
    private function bootstrapTemplate(): string { return <<<PHP
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
\$composerAutoload = BASE . 'vendor/autoload.php';
if (!is_file(\$composerAutoload)) {
    die('Composer autoloader not found. Run `composer install`.');
}
require \$composerAutoload;

/* Import necessary classes */
use Mlangeni\Machinjiri\Core\Machinjiri;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;

// Load helper functions
require_once CWD . '/helpers.php';

/**
 * Instantiating the Machinjiri Framework
 */
\$machinjiri = Machinjiri::App(CWD);
/**
 * Start App Entry Logger
 */
PHP;
    }

    private function publicIndexTemplate(): string { return <<<PHP
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
    
    private function gitIgnoreTemplate(): string { return <<<GIT
/vendor/
/node_modules/
/.env
/storage/*
!important/storage/.gitignore
/.idea
/.vscode
/.DS_Store
GIT;
    }

    private function artisanTemplate(): string { return <<<'PHP'
#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';
use Mlangeni\Machinjiri\Core\Artisans\Terminal\Terminal;
// init Machinjiri artisan console
(new Terminal())->run();
PHP;
    }

    private function rootHtaccess(): string { return <<<'HT'
# Redirect everything to public
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L]

Options -Indexes
<FilesMatch "(\.env|composer\.json|composer\.lock|config\.php)">
    Require all denied
</FilesMatch>
HT;
    }

    private function publicHtaccess(): string { return <<<'HT'
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

    private function phpunitTemplate(): string { return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    colors="true"
    cacheResult="true"
    executionOrder="random"
    stopOnFailure="false"
    processIsolation="false"
    >

    <php>
        <!-- Testing environment -->
        <env name="APP_ENV" value="testing"/>
        <env name="CODETEST_ENABLED" value="true"/>
        
        <!-- Database: use in-memory SQLite for speed -->
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        
        <!-- Cache & Queue: use array driver -->
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        
        <!-- Mail: log to array (no real sending) -->
        <env name="MAIL_MAILER" value="array"/>
        
        <!-- Session: array driver for tests -->
        <env name="SESSION_DRIVER" value="array"/>
        
        <!-- Encryption key (must be 32 chars base64) -->
        <env name="APP_KEY" value="base64:abcdefghijklmnopqrstuvwxyz1234567890="/>
        
        <!-- JWT secret -->
        <env name="JWT_SECRET" value="test-jwt-secret-key-32-chars-long-here!"/>
        
        <!-- Coverage threshold -->
        <env name="COVERAGE_MINIMUM" value="80"/>
    </php>

    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Features</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Browser">
            <directory>tests/Browser</directory>
        </testsuite>
    </testsuites>

    <coverage cacheDirectory="build/cache">
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/Console</directory>
            <directory>src/Testing</directory>
            <directory>src/Artisans/Terminal</directory>
            <directory>src/Exceptions</directory>
            <directory>vendor</directory>
            <directory>tests</directory>
        </exclude>
        <report>
            <html outputDirectory="build/coverage" lUpperBound="35" highLowerBound="70"/>
            <clover outputFile="build/logs/clover.xml"/>
            <text outputFile="build/logs/coverage.txt" showUncoveredFiles="true"/>
        </report>
    </coverage>

    <logging>
        <junit outputFile="build/logs/junit.xml"/>
    </logging>

    <extensions>
        <!-- Paratest for parallel execution -->
        <extension class="ParaTest\WrapperRunner\WrapperRunner"/>
    </extensions>
</phpunit>
XML;
    }
    
    public function AppServiceProviderTemplate () { return <<<'PHP'
<?php

namespace Mlangeni\Machinjiri\App\Providers;

use Mlangeni\Machinjiri\Core\Providers\ServiceProvider;
use Mlangeni\Machinjiri\Core\Http\HttpRequest;
use Mlangeni\Machinjiri\Core\Http\HttpResponse;
use Mlangeni\Machinjiri\Core\Authentication\Session;
use Mlangeni\Machinjiri\Core\Authentication\Cookie;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;
use Mlangeni\Machinjiri\Core\Artisans\Events\EventListener;
use Mlangeni\Machinjiri\Core\Debug\Debugger;
use Mlangeni\Machinjiri\Core\FileSystem\FileSystemManager;
use Mlangeni\Machinjiri\Core\FileSystem\Adapters\LocalAdapter;
use Mlangeni\Machinjiri\Core\FileSystem\Adapters\FtpAdapter;
use Mlangeni\Machinjiri\Core\Artisans\Caching\CacheManager;
use Mlangeni\Machinjiri\Core\Transport\Mail\MailManager;
use Mlangeni\Machinjiri\Core\Security\Tokens\CSRFToken;

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
          return new MailManager($app, null, $logger, new EventListener($logger), null, $app->resolve('queue.dispatcher'));
        });

        // Register EventListener service
        $this->bind('events', function($app) {
            return new EventListener(new Logger('machinjiri', Logger::DEBUG, true));
        });
        
        $this->bind(Logger::class, function($app) {
            return new Logger('machinjiri', Logger::DEBUG);
        });
        
        $this->app->singleton(CSRFToken::class, function ($app) {
            return new CSRFToken($app->resolve(Session::class), $app->resolve(Cookie::class), env("CSRF_TOKEN_NAME", "csrf_token"));
        });

        // Register aliases for easier access
        $this->aliasMany([
            'request' => HttpRequest::class,
            'response' => HttpResponse::class,
            'session' => Session::class,
            'cookie' => Cookie::class,
            'debugger' => Debugger::class,
            'fs.adapter.local' => LocalAdapter::class,
            'fs.adapter.ftp' => FtpAdapter::class,
            'fs.manager' => FileSystemManager::class,
            'cache.manager' => CacheManager::class,
            'mail.manager' => MailManager::class,
            'logger' => Logger::class,
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
        }

        // Load application routes
        $routesDir = $this->app->routes;
        if (is_dir($routesDir)) {
            $this->loadRoutesFrom($routesDir . 'web.php');
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
PHP;
    }
    
    public function DatabaseServiceProviderTemplate () { return <<<'PHP'
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
    
    protected function providersTemplate () {return <<<'PHP'
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Service Providers Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file registers Service Providers you create
    | for your application.
    |
    | You can modify these values to suit your application needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Configuration Section
    |--------------------------------------------------------------------------
    |
    | Add your Service Providers here.
    | AppServiceProvider And DatabaseServiceProvider is for the Core do not ommit
    | Add Custom Service Providers below AppServiceProvider
    */

    'providers' => [
        \Mlangeni\Machinjiri\App\Providers\AppServiceProvider::class,
        \Mlangeni\Machinjiri\App\Providers\DatabaseServiceProvider::class,
    ],
    
    /**
     * Define Service Providers that will be loaded only when needed to
     * improve app performance
     */
    'deffered' => [
        
    ],
];
PHP;
    }
  
  protected function appConfigTemplate() { return <<<'PHP'
<?php
/**
 * Application Configuration
 *
 * This file contains the main configuration for the Machinjiri framework.
 * Environment variables are loaded and used to configure the application.
 */

return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'name' => env('APP_NAME', 'Machinjiri App'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */
    'env' => env('APP_ENV', 'development'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */
    'debug' => env('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */
    'url' => env('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */
    'timezone' => 'UTC',

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => 'en',
    'fallback_locale' => 'en',

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the Illuminate encrypter service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */
    'key' => env('APP_KEY', ''),
    'cipher' => 'AES-256-CBC',

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the session driver that should be used by the
    | application. The default is "file", but other drivers are available.
    |
    */
    'session' => [
        'driver' => env('SESSION_DRIVER', 'file'),
        'lifetime' => env('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => __DIR__ . '/../storage/session',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => env(
            'SESSION_COOKIE',
            'machinjiri_session'
        ),
        'path' => __DIR__ . '/../storage/session',
        'domain' => env('SESSION_DOMAIN'),
        'secure' => env('SESSION_SECURE_COOKIE', false),
        'http_only' => true,
        'same_site' => 'lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware Configuration
    |--------------------------------------------------------------------------
    |
    | Global middleware that runs on every request.
    |
    */
    'middleware' => [
        'global' => [
        ],
        'web' => [
        ],
        'api' => [
        ],
    ],
    'encryption_key' => env('APP_KEY', ''),
    'encryption_cipher' => env('APP_CIPHER', 'aes-256-gcm'),
    'jwt_secret' => env('JWT_SECRET', ''),
    'jwt_algo' => env('JWT_ALGO', 'HS256'),
    'jwt_expiration' => env('JWT_EXPIRATION', 3600),
    'jwt_issuer' => env('JWT_ISSUER', 'machinjiri'),
    'jwt_audience' => env('JWT_AUDIENCE', 'machinjiri_api'),
];
PHP;
  }
  
  private function helpersFileTemplate () { return <<<'PHP'
<?php

/**
 * Global Helper Functions for Machinjiri Framework
 * 
 * This file provides global helper functions for easy access to application services.
 */

use Mlangeni\Machinjiri\Core\Container;
use Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;
use Mlangeni\Machinjiri\Core\Date\DateTimeHandler;
use Mlangeni\Machinjiri\Core\Debug\Dumper;
use Mlangeni\Machinjiri\Integrations\Vite\Vite;
use Mlangeni\Machinjiri\Core\Debug\Debugger;
use Mlangeni\Machinjiri\Core\Views\View;
use Mlangeni\Machinjiri\Core\Routing\Router;
use Mlangeni\Machinjiri\Core\Http\HttpClient;
use Mlangeni\Machinjiri\Core\Http\HttpRequest;
use Mlangeni\Machinjiri\Core\Http\HttpResponse;
use Mlangeni\Machinjiri\Core\Authentication\Session;
use Mlangeni\Machinjiri\Core\Authentication\Cookie;

if (!function_exists('app')) {
    /**
     * Get the application container instance or resolve a service
     *
     * @param string|null $abstract Service name to resolve
     * @return Container|mixed
     */
    function app(?string $abstract = null)
    {
        static $container = null;
        
        // If container is not set, try to get it from global registry
        if ($container === null) {
            // Try to get container from global registry if available
            if (isset($GLOBALS['__machinjiri_container'])) {
                $container = $GLOBALS['__machinjiri_container'];
            }
        }
        
        // If we have a container and asked for a specific service, resolve it
        if ($container !== null && $abstract !== null) {
            return $container->make($abstract);
        }
        
        return $container;
    }
}

if (!function_exists('resolve')) {
    /**
     * Resolve a service from the container
     *
     * @param string $abstract Service name to resolve
     * @param array $parameters Parameters to pass
     * @return mixed
     */
    function resolve(string $abstract, array $parameters = [])
    {
        $container = app();
        
        if ($container === null) {
            throw new MachinjiriException(
                "Application container not initialized. Call app() first.",
                30104
            );
        }
        
        if (method_exists($container, 'make')) {
            return $container->make($abstract, $parameters);
        }
        
        if (method_exists($container, 'resolve')) {
            return $container->resolve($abstract, $parameters);
        }
        
        throw new MachinjiriException(
            "Container does not have a resolve method for: {$abstract}",
            30105
        );
    }
}

if (!function_exists('singleton')) {
    /**
     * Register a singleton in the container
     *
     * @param string $abstract
     * @param mixed $concrete
     * @return void
     */
    function singleton(string $abstract, $concrete = null)
    {
        $container = app();
        
        if ($container === null) {
            throw new MachinjiriException(
                "Application container not initialized.",
                30104
            );
        }
        
        if (method_exists($container, 'singleton')) {
            $container->singleton($abstract, $concrete);
        } elseif (method_exists($container, 'bind')) {
            $container->bind($abstract, $concrete, true);
        }
    }
}

if (!function_exists('bind')) {
    /**
     * Register a binding in the container
     *
     * @param string $abstract
     * @param mixed $concrete
     * @param bool $shared
     * @return void
     */
    function bind(string $abstract, $concrete = null, bool $shared = false)
    {
        $container = app();
        
        if ($container === null) {
            throw new MachinjiriException(
                "Application container not initialized.",
                30104
            );
        }
        
        if (method_exists($container, 'bind')) {
            $container->bind($abstract, $concrete, $shared);
        }
    }
}

if (!function_exists('alias')) {
    /**
     * Register an alias in the container
     *
     * @param string $abstract
     * @param string $alias
     * @return void
     */
    function alias(string $abstract, string $alias)
    {
        $container = app();
        
        if ($container === null) {
            throw new MachinjiriException(
                "Application container not initialized.",
                30104
            );
        }
        
        if (method_exists($container, 'alias')) {
            $container->alias($abstract, $alias);
        }
    }
}

if (!function_exists('service')) {
    /**
     * Get a service provider instance
     *
     * @param string $providerClass
     * @return \Mlangeni\Machinjiri\Core\Providers\ServiceProvider|null
     */
    function service(string $providerClass)
    {
        $container = app();
        
        if ($container === null) {
            return null;
        }
        
        // Try to resolve provider from container
        try {
            return $container->make($providerClass);
        } catch (Exception $e) {
            // Provider not in container, create new instance
            return new $providerClass($container);
        }
    }
}

if (!function_exists('config')) {
    /**
     * Get or set configuration values
     *
     * @param string|array|null $key
     * @param mixed $default
     * @return mixed
     */
    function config($key = null, $default = null)
    {
        $container = app();
        
        if ($container === null) {
            return $default;
        }
        
        // Check if configurations property exists
        if (!property_exists($container, 'configurations') || !isset($container->configurations)) {
            return $default;
        }
        
        if ($key === null) {
            return $container->configurations;
        }
        
        if (is_array($key)) {
            // Set configuration
            foreach ($key as $configKey => $value) {
                $container->configurations[$configKey] = $value;
            }
            return true;
        }
        
        // Get configuration using dot notation
        $keys = explode('.', $key);
        $config = $container->configurations;
        
        foreach ($keys as $segment) {
            if (is_array($config) && isset($config[$segment])) {
                $config = $config[$segment];
            } else {
                return $default;
            }
        }
        
        return $config;
    }
}

if (!function_exists('config_path')) {
    /**
     * Get the full path to a configuration file
     *
     * @param string $filename Configuration filename (with or without .php extension)
     * @return string Full path to the configuration file
     */
    function config_path(string $filename = ''): string
    {
        // Get base path from container or global
        $basePath = defined('BASE') ? BASE : getcwd();
        
        // Ensure filename has .php extension if not present
        if (!empty($filename) && !str_ends_with($filename, '.php')) {
            $filename .= '.php';
        }
        
        $configDir = $basePath . DIRECTORY_SEPARATOR . 'config';
        
        return empty($filename) ? $configDir : $configDir . DIRECTORY_SEPARATOR . $filename;
    }
}

if (!function_exists('event')) {
    /**
     * Trigger an event or register an event listener
     *
     * @param string $event
     * @param mixed $data
     * @return void|mixed
     */
    function event(string $event, $data = null)
    {
        $container = app();
        
        if ($container === null) {
            return;
        }
        
        static $eventListener = null;
        static $servicesRegistered = false;
        
        // Auto-register event services if not already registered
        if (!$servicesRegistered) {
            // Register Logger if not already bound
            if (!$container->bound(Logger::class)) {
                $container->singleton(Logger::class, function($c) {
                    return new Logger();
                });
            }
            
            // Register EventListener if not already bound
            if (!$container->bound(\Mlangeni\Machinjiri\Core\Artisans\Events\EventListener::class)) {
                $container->singleton(\Mlangeni\Machinjiri\Core\Artisans\Events\EventListener::class, function($c) {
                    $logger = $c->make(Logger::class);
                    return new \Mlangeni\Machinjiri\Core\Artisans\Events\EventListener($logger);
                });
            }
            
            $servicesRegistered = true;
        }
        
        if ($eventListener === null) {
            try {
                $eventListener = $container->make(\Mlangeni\Machinjiri\Core\Artisans\Events\EventListener::class);
            } catch (Exception $e) {
                error_log("Failed to create EventListener: " . $e->getMessage());
                return;
            }
        }
        
        $numArgs = func_num_args();
        
        // If only one parameter (just $event), trigger the event without data
        if ($numArgs === 1) {
            return $eventListener->trigger($event);
        }
        
        // If two parameters
        if ($numArgs === 2) {
            // If second parameter is callable, register listener
            if (is_callable($data)) {
                return $eventListener->on($event, $data);
            }
            
            // Otherwise, trigger event with data
            return $eventListener->trigger($event, $data);
        }
        
        return;
    }
}

if (!function_exists('service_provider')) {
    /**
     * Check if a service provider is registered
     *
     * @param string $providerClass
     * @return bool
     */
    function service_provider(string $providerClass): bool
    {
        $container = app();
        
        if ($container === null) {
            return false;
        }
        
        // Check if provider loader exists
        if (!property_exists($container, 'providerLoader') || !isset($container->providerLoader)) {
            return false;
        }
        
        $loader = $container->providerLoader;
        
        if (method_exists($loader, 'getRegisteredProviders')) {
            $providers = $loader->getRegisteredProviders();
            return in_array($providerClass, $providers);
        }
        
        return false;
    }
}

if (!function_exists('boot_service_provider')) {
    /**
     * Boot a specific service provider
     *
     * @param string $providerClass
     * @return bool
     */
    function boot_service_provider(string $providerClass): bool
    {
        $container = app();
        
        if ($container === null) {
            return false;
        }
        
        // Check if provider loader exists
        if (!property_exists($container, 'providerLoader') || !isset($container->providerLoader)) {
            return false;
        }
        
        $loader = $container->providerLoader;
        
        if (method_exists($loader, 'bootProvider')) {
            try {
                $provider = $container->make($providerClass);
                $loader->bootProvider($provider);
                return true;
            } catch (Exception $e) {
                return false;
            }
        }
        
        return false;
    }
}

if (!function_exists('env')) {
    /**
     * Get environment variable value
     *
     * @param string $key Environment variable name
     * @param mixed $default Default value if not found
     * @return mixed
     */
    function env(string $key, $default = null)
    {
        // Fallback to $_ENV or $_SERVER
        $value = $_ENV[$key] ?? $_SERVER[$key];
        
        return $value !== false ? $value : $default;
    }
}

if (!function_exists('storage_path')) {
    /**
     * Get the full path to the storage directory or a subdirectory
     *
     * @param string $path Subdirectory or file path within storage
     * @return string Full path
     */
    function storage_path(string $path = ''): string
    {
        // Get base path from container, constant, or current directory
        $basePath = defined('BASE') ? BASE : getcwd();
        
        $storageDir = $basePath . DIRECTORY_SEPARATOR . 'storage';
        
        if (empty($path)) {
            return $storageDir;
        }
        
        // Ensure path doesn't start with directory separator
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        
        return $storageDir . DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('now')) {
    /**
     * Get current DateTimeHandler instance
     *
     * @param string|null $timezone Timezone string (default: UTC or from config)
     * @return DateTimeHandler
     */
    function now(?string $timezone = null): DateTimeHandler
    {
        // Get timezone from parameter, config, or default to UTC
        $tz = $timezone ?? config('app.timezone', 'UTC');
        
        return new DateTimeHandler('now', $tz);
    }
}

if (!function_exists('base_path')) {
    /**
     * Get the base path of the application
     *
     * @param string $path Subdirectory or file path within base
     * @return string Full path
     */
    function base_path(string $path = ''): string
    {
        $basePath = defined('BASE') ? BASE : getcwd();
        
        if (empty($path)) {
            return $basePath;
        }
        
        // Ensure path doesn't start with directory separator
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        
        return $basePath . DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('app_path')) {
    /**
     * Get the path to the application directory
     *
     * @param string $path Subdirectory or file path within app
     * @return string Full path
     */
    function app_path(string $path = ''): string
    {
        $appDir = base_path('app');
        
        if (empty($path)) {
            return $appDir;
        }
        
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        
        return $appDir . DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('public_path')) {
    /**
     * Get the path to the public directory
     *
     * @param string $path Subdirectory or file path within public
     * @return string Full path
     */
    function public_path(string $path = ''): string
    {
        $publicDir = base_path('public');
        
        if (empty($path)) {
            return $publicDir;
        }
        
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        
        return $publicDir . DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('resource_path')) {
    /**
     * Get the path to the resources directory
     *
     * @param string $path Subdirectory or file path within resources
     * @return string Full path
     */
    function resource_path(string $path = ''): string
    {
        $resourceDir = base_path('resources');
        
        if (empty($path)) {
            return $resourceDir;
        }
        
        $path = ltrim($path, DIRECTORY_SEPARATOR);
        
        return $resourceDir . DIRECTORY_SEPARATOR . $path;
    }
}

if (!function_exists('dump')) {
    /**
     * Dump the given variables and continue execution.
     *
     * @param mixed ...$args
     * @return void
     */
    function dump(...$args): void
    {
        Dumper::dump(...$args);
    }
}

if (!function_exists('dd')) {
    /**
     * Dump the given variables and end the script.
     *
     * @param mixed ...$args
     * @return never
     */
    function dd(...$args): never
    {
        Dumper::dd(...$args);
    }
}

if (!function_exists('vite')) {
    /**
     * Get the Vite instance or generate tags for given entries.
     *
     * @param string|array|null $entries
     * @return Vite|string
     */
    function vite($entries = null)
    {
        /** @var Vite $vite */
        $vite = Container::getInstance()->make(Vite::class);

        if ($entries === null) {
            return $vite;
        }

        return $vite->entry($entries)->tags();
    }
}

if (!function_exists('vite_asset')) {
    /**
     * Get the URL for a Vite asset.
     *
     * @param string $path
     * @return string
     */
    function vite_asset(string $path): string
    {
        return Container::getInstance()->make(Vite::class)->asset($path);
    }
}

if (!function_exists('debugger')) {
    /**
     * Get the Debugger instance.
     *
     * @return Debugger
     */
    function debugger(): Debugger
    {
        return Debugger::getInstance();
    }
}

if (!function_exists('debug')) {
    /**
     * Log a debug message.
     *
     * @param mixed $message
     * @param array $context
     * @return void
     */
    function debug($message, array $context = []): void
    {
        debugger()->log($message, 'debug', $context);
    }
}

if (!function_exists('measure')) {
    /**
     * Measure execution time of a callable.
     *
     * @param callable $callback
     * @param string $label
     * @return mixed
     */
    function measure(callable $callback, string $label = 'anonymous')
    {
        return debugger()->measure($callback, $label);
    }
}
if (!function_exists('view')) {
    /**
     * Render a view and return its content.
     *
     * @param string $view
     * @param array $data
     * @return string
     */
    function view(string $view, array $data = []): void
    {
        View::make($view, $data)->display();
    }
}

if (!function_exists('display_view')) {
    /**
     * Render and directly output a view.
     *
     * @param string $view
     * @param array $data
     */
    function display_view(string $view, array $data = []): void
    {
        View::make($view, $data)->display();
    }
}

if (!function_exists('asset')) {
    /**
     * Generate a versioned asset URL.
     *
     * @param string $path
     * @return string
     * @throws \Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException
     */
    function asset(string $path): string
    {
        return View::asset($path);
    }
}

if (!function_exists('style')) {
    /**
     * Generate a <link> tag for a CSS asset.
     *
     * @param string $path
     * @param array $attributes
     * @return string
     */
    function style(string $path, array $attributes = []): string
    {
        return View::style($path, $attributes);
    }
}

if (!function_exists('script')) {
    /**
     * Generate a <script> tag for a JavaScript asset.
     *
     * @param string $path
     * @param array $attributes
     * @return string
     */
    function script(string $path, array $attributes = []): string
    {
        return View::script($path, $attributes);
    }
}

if (!function_exists('load_resource')) {
    /**
     * Legacy resource loader (loads all CSS/JS files recursively or a single file).
     *
     * @param string $type 'css' or 'js'
     * @param string $path Optional specific file path
     * @return string
     */
    function load_resource(string $type, string $path = ""): string
    {
        return View::loadResource($type, $path);
    }
}

if (!function_exists('share')) {
    /**
     * Share data across all views.
     *
     * @param string|array $key
     * @param mixed|null $value
     */
    function share($key, $value = null): void
    {
        View::share($key, $value);
    }
}

// ---- Template tag helpers (for use inside view files) ----

if (!function_exists('section')) {
    /**
     * Start or set a named section.
     *
     * @param string $name
     * @param string|null $content
     * @throws \Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException
     */
    function section(string $name, ?string $content = null): void
    {
        View::section($name, $content);
    }
}

if (!function_exists('endSection')) {
    /**
     * End the currently open section.
     *
     * @throws \Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException
     */
    function endSection(): void
    {
        View::endSection();
    }
}

if (!function_exists('view_yield')) {
    /**
     * Output the content of a section.
     *
     * @param string $name
     */
    function view_yield(string $name): void
    {
        View::yield($name);
    }
}

if (!function_exists('extend')) {
    /**
     * Specify a layout to extend.
     *
     * @param string $layout
     * @throws \Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException
     */
    function extend(string $layout): void
    {
        View::extend($layout);
    }
}

if (!function_exists('include_view')) {
    /**
     * Include a partial view (shortcut for View::include).
     *
     * @param string $view
     * @param array $data
     * @throws \Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException
     */
    function include_view(string $view, array $data = []): void
    {
        View::include($view, $data);
    }
}

if (!function_exists('hasSection')) {
    /**
     * Check if a section exists.
     *
     * @param string $name
     * @return bool
     */
    function hasSection(string $name): bool
    {
        return View::hasSection($name);
    }
}

if (!function_exists('getSection')) {
    /**
     * Get the content of a section (without outputting).
     *
     * @param string $name
     * @return string
     */
    function getSection(string $name): string
    {
        return View::getSection($name);
    }
}

if (!function_exists('parent')) {
    /**
     * Output the parent section content (when using inheritance).
     *
     * @param string $name
     */
    function parent(string $name): void
    {
        View::parent($name);
    }
}
// Router API Functions
if (!function_exists('route_get')) {
    /**
     * Register a GET route.
     *
     * @param string $pattern
     * @param mixed $handler
     * @param string|null $name
     * @param array $options
     * @return Router
     */
    function route_get(string $pattern, mixed $handler, ?string $name = null, array $options = []): Router
    {
        return Router::get($pattern, $handler, $name, $options);
    }
}

if (!function_exists('route_post')) {
    /**
     * Register a POST route.
     *
     * @param string $pattern
     * @param mixed $handler
     * @param string|null $name
     * @param array $options
     * @return Router
     */
    function route_post(string $pattern, mixed $handler, ?string $name = null, array $options = []): Router
    {
        return Router::post($pattern, $handler, $name, $options);
    }
}

if (!function_exists('route_put')) {
    /**
     * Register a PUT route.
     *
     * @param string $pattern
     * @param mixed $handler
     * @param string|null $name
     * @param array $options
     * @return Router
     */
    function route_put(string $pattern, mixed $handler, ?string $name = null, array $options = []): Router
    {
        return Router::put($pattern, $handler, $name, $options);
    }
}

if (!function_exists('route_delete')) {
    /**
     * Register a DELETE route.
     *
     * @param string $pattern
     * @param mixed $handler
     * @param string|null $name
     * @param array $options
     * @return Router
     */
    function route_delete(string $pattern, mixed $handler, ?string $name = null, array $options = []): Router
    {
        return Router::delete($pattern, $handler, $name, $options);
    }
}

if (!function_exists('route_any')) {
    /**
     * Register a route that responds to any HTTP method.
     *
     * @param string $pattern
     * @param mixed $handler
     * @param string|null $name
     * @param array $options
     * @return Router
     */
    function route_any(string $pattern, mixed $handler, ?string $name = null, array $options = []): Router
    {
        return Router::any($pattern, $handler, $name, $options);
    }
}

if (!function_exists('route_ajax')) {
    /**
     * Register an AJAX-only route (responds only to XMLHttpRequest).
     *
     * @param string $pattern
     * @param mixed $handler
     * @param string|null $name
     * @param array $options
     * @return Router
     */
    function route_ajax(string $pattern, mixed $handler, ?string $name = null, array $options = []): Router
    {
        return Router::ajax($pattern, $handler, $name, $options);
    }
}

if (!function_exists('route_traditional')) {
    /**
     * Register a traditional (non-AJAX) route.
     *
     * @param string $pattern
     * @param mixed $handler
     * @param string|null $name
     * @param array $options
     * @return Router
     */
    function route_traditional(string $pattern, mixed $handler, ?string $name = null, array $options = []): Router
    {
        return Router::traditional($pattern, $handler, $name, $options);
    }
}

if (!function_exists('route_group')) {
    /**
     * Create a route group with shared attributes (prefix, middleware, etc.).
     *
     * @param array $attributes
     * @param callable $callback
     * @return Router
     */
    function route_group(array $attributes, callable $callback): Router
    {
        return Router::group($attributes, $callback);
    }
}

if (!function_exists('route_middleware')) {
    /**
     * Apply middleware to a route or group.
     *
     * @param mixed $middleware
     * @param callable|null $callback
     * @return Router
     */
    function route_middleware(mixed $middleware, ?callable $callback = null): Router
    {
        return Router::middleware($middleware, $callback);
    }
}

if (!function_exists('route_cors')) {
    /**
     * Apply CORS configuration to a route or group.
     *
     * @param array $config
     * @param callable|null $callback
     * @return Router
     */
    function route_cors(array $config = [], ?callable $callback = null): Router
    {
        if ($callback !== null) {
            return Router::cors($config, $callback);
        }
        return Router::cors($config);
    }
}

if (!function_exists('base_url')) {
    /**
     * Get the base URL of the application (protocol + host + base path).
     *
     * @return string
     */
    function base_url(): string
    {
        return Router::baseUrl();
    }
}

if (!function_exists('route_url')) {
    /**
     * Generate a URL for a named route.
     *
     * @param string $name
     * @param array $params
     * @return string
     * @throws \Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException
     */
    function route_url(string $name, array $params = []): string
    {
        return Router::route($name, $params);
    }
}

if (!function_exists('route_absolute')) {
    /**
     * Generate an absolute URL (including protocol and host) for a named route.
     *
     * @param string $name
     * @param array $params
     * @return string
     */
    function route_absolute(string $name, array $params = []): string
    {
        return Router::absoluteRoute($name, $params);
    }
}

if (!function_exists('dispatch_routes')) {
    /**
     * Dispatch the current request to the matching route.
     *
     * @return void
     */
    function dispatch_routes(): void
    {
        Router::dispatch();
    }
}

if (!function_exists('http_client')) {
    /**
     * Create a new HttpClient instance.
     *
     * @param string $baseUrl
     * @param Session|null $session
     * @param Cookie|null $cookie
     * @return HttpClient
     */
    function http_client(string $baseUrl = '', ?Session $session = null, ?Cookie $cookie = null): HttpClient
    {
        return new HttpClient($baseUrl, $session, $cookie);
    }
}

if (!function_exists('http_get')) {
    /**
     * Execute a GET request.
     *
     * @param string $url
     * @param array $queryParams
     * @param array $options Additional cURL options (e.g., headers, timeout)
     * @return array Response data (see HttpClient::execute)
     */
    function http_get(string $url, array $queryParams = [], array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->get($url, $queryParams);
    }
}

if (!function_exists('http_post')) {
    /**
     * Execute a POST request.
     *
     * @param string $url
     * @param array $data
     * @param bool $isJson Whether to send as JSON (default true)
     * @param array $options Additional cURL options
     * @return array
     */
    function http_post(string $url, array $data = [], bool $isJson = true, array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->post($url, $data, $isJson);
    }
}

if (!function_exists('http_put')) {
    /**
     * Execute a PUT request.
     *
     * @param string $url
     * @param array $data
     * @param bool $isJson
     * @param array $options
     * @return array
     */
    function http_put(string $url, array $data = [], bool $isJson = true, array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->put($url, $data, $isJson);
    }
}

if (!function_exists('http_patch')) {
    /**
     * Execute a PATCH request.
     *
     * @param string $url
     * @param array $data
     * @param bool $isJson
     * @param array $options
     * @return array
     */
    function http_patch(string $url, array $data = [], bool $isJson = true, array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->patch($url, $data, $isJson);
    }
}

if (!function_exists('http_delete')) {
    /**
     * Execute a DELETE request.
     *
     * @param string $url
     * @param array $data Optional JSON body
     * @param array $options
     * @return array
     */
    function http_delete(string $url, array $data = [], array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->delete($url, $data);
    }
}

if (!function_exists('http_head')) {
    /**
     * Execute a HEAD request.
     *
     * @param string $url
     * @param array $options
     * @return array
     */
    function http_head(string $url, array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->head($url);
    }
}

if (!function_exists('http_options')) {
    /**
     * Execute an OPTIONS request.
     *
     * @param string $url
     * @param array $options
     * @return array
     */
    function http_options(string $url, array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->options($url);
    }
}

if (!function_exists('http_upload_file')) {
    /**
     * Upload a file via multipart/form-data.
     *
     * @param string $url
     * @param string $fieldName
     * @param string $filePath
     * @param array $additionalData
     * @param array $options
     * @return array
     * @throws MachinjiriException
     */
    function http_upload_file(string $url, string $fieldName, string $filePath, array $additionalData = [], array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->uploadFile($url, $fieldName, $filePath, $additionalData);
    }
}

if (!function_exists('http_download_file')) {
    /**
     * Download a file from a URL and save it locally.
     *
     * @param string $url
     * @param string $savePath
     * @param array $options
     * @return array
     * @throws MachinjiriException
     */
    function http_download_file(string $url, string $savePath, array $options = []): array
    {
        $client = http_client();
        apply_options($client, $options);
        return $client->downloadFile($url, $savePath);
    }
}

if (!function_exists('http_multi_request')) {
    /**
     * Execute multiple requests concurrently.
     *
     * @param array $requests Array where each element has 'url' and optionally 'options' (cURL options for that request)
     * @param array $globalOptions Options to apply to all requests (e.g., timeout)
     * @return array Results keyed by original request keys
     */
    function http_multi_request(array $requests, array $globalOptions = []): array
    {
        $client = http_client();
        apply_options($client, $globalOptions);
        
        // Transform simplified requests into the format expected by multiRequest
        $multiRequests = [];
        foreach ($requests as $key => $req) {
            $multiRequests[$key] = [
                'options' => array_merge(
                    $globalOptions,
                    $req['options'] ?? [],
                    [CURLOPT_URL => $req['url']]
                )
            ];
        }
        return $client->multiRequest($multiRequests);
    }
}

if (!function_exists('apply_options')) {
    /**
     * Apply common configuration options to an HttpClient instance.
     *
     * @param HttpClient $client
     * @param array $options Supported keys:
     *   - headers: array
     *   - timeout: int
     *   - max_redirects: int
     *   - user_agent: string
     *   - referer: string
     *   - basic_auth: [username, password]
     *   - bearer_token: string
     *   - proxy: [proxy, port?, username?, password?]
     *   - ssl: [verify_peer, verify_host, cert_path, key_path]
     *   - retry: [max_retries, retry_delay]
     *   - compress: bool
     *   - cookies: bool|string (true = use memory, string = file path)
     *   - cookie_pairs: array (name => value)
     *   - custom_method: string (GET, POST, etc.)
     *   - capture_headers: bool
     */
    function apply_options(HttpClient $client, array $options): void
    {
        if (isset($options['headers'])) {
            $client->setHeaders($options['headers']);
        }
        if (isset($options['timeout'])) {
            $client->setTimeout($options['timeout']);
        }
        if (isset($options['max_redirects'])) {
            $client->setMaxRedirects($options['max_redirects']);
        }
        if (isset($options['user_agent'])) {
            $client->setUserAgent($options['user_agent']);
        }
        if (isset($options['referer'])) {
            $client->setReferer($options['referer']);
        }
        if (isset($options['basic_auth']) && is_array($options['basic_auth']) && count($options['basic_auth']) >= 2) {
            $client->setBasicAuth($options['basic_auth'][0], $options['basic_auth'][1]);
        }
        if (isset($options['bearer_token'])) {
            $client->setBearerToken($options['bearer_token']);
        }
        if (isset($options['proxy'])) {
            $proxy = $options['proxy'];
            if (is_array($proxy)) {
                $client->setProxy($proxy[0], $proxy[1] ?? null, $proxy[2] ?? null, $proxy[3] ?? null);
            } else {
                $client->setProxy($proxy);
            }
        }
        if (isset($options['ssl']) && is_array($options['ssl'])) {
            $ssl = $options['ssl'];
            $client->setSslOptions(
                $ssl['verify_peer'] ?? true,
                $ssl['verify_host'] ?? 2,
                $ssl['cert_path'] ?? null,
                $ssl['key_path'] ?? null
            );
        }
        if (isset($options['retry']) && is_array($options['retry'])) {
            $client->setRetryOptions($options['retry']['max_retries'] ?? 3, $options['retry']['retry_delay'] ?? 1000);
        }
        if (isset($options['compress']) && $options['compress'] === true) {
            $client->enableCompression();
        }
        if (isset($options['cookies'])) {
            if (is_string($options['cookies'])) {
                $client->enableCookies($options['cookies']);
            } elseif ($options['cookies'] === true) {
                $client->enableCookies();
            }
        }
        if (isset($options['cookie_pairs']) && is_array($options['cookie_pairs'])) {
            foreach ($options['cookie_pairs'] as $name => $value) {
                $client->setCookie($name, $value);
            }
        }
        if (isset($options['custom_method'])) {
            $client->setCustomRequest($options['custom_method']);
        }
        if (isset($options['capture_headers']) && $options['capture_headers'] === true) {
            $client->withHeaderCapture();
        }
    }
}

if (!function_exists('http_api')) {
    /**
     * Execute a POST/GET/PUT/PATCH/DELETE request in one function.
     *
     * @param string $url
     * @param string $method
     * @param array $data
     * @param array $headers
     * @return HttpResponse
     */
    function http_api(string $url, ?string $method = null, $data = null, array $headers = []): HttpResponse
    {
        $request = HttpRequest::createFromGlobals();
        return $request->api($url, $method, $data, $headers);
    }
}
PHP;
  }
  
  private function mailConfigTemplate () { return <<<'PHP'
<?php

return [
  'default' => env('MAIL_MAILER', 'smtp'),
  'mailers' => [
    'smtp' => [
      'transport' => 'smtp',
      'host' => env('MAIL_HOST', 'smtp.mailtrap.io'),
      'port' => env('MAIL_PORT', 2525),
      'encryption' => env('MAIL_ENCRYPTION', 'tls'),
      'username' => env('MAIL_USERNAME'),
      'password' => env('MAIL_PASSWORD'),
      'timeout' => null,
      'auth_mode' => null,
    ],
      // Add other mailers as needed
  ],
  'from' => [
      'address' => env('MAIL_FROM_ADDRESS', 'hello@example.com'),
      'name' => env('MAIL_FROM_NAME', 'Example'),
  ]
];
PHP;
  }
  
  private function artisanBootstrapTemplate() { return <<<'PHP'
<?php

use Mlangeni\Machinjiri\Core\Container;
use Mlangeni\Machinjiri\App\Providers\AppServiceProvider;
use Mlangeni\Machinjiri\App\Providers\QueueServiceProvider;

define('BASE', dirname(__DIR__) . DIRECTORY_SEPARATOR);
$appRoot = BASE;

if (!is_dir($appRoot)) {
    die("Invalid app base path: {$appRoot}\n");
}

$debug = filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN);

$app = new Container($appRoot, $debug, true);

$app->initialize();

$providers = [
    AppServiceProvider::class,
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
PHP;
  }
  
  private function fileSystemConfigurationTemplate() { return <<<'PHP'
<?php
/**
 * File System Configuration
 */
return [
    // default disk
    'default' => env('FILE_SYSTEM_DEFAULT_DRIVER', 'local'),
    'disks' => [
        'local' => [
            'driver' => 'local',
            'root'   => env('FILE_SYSTEM_ROOT') ?: __DIR__ . '/../storage/app',
        ],
        'ftp' => [
            'driver'   => 'ftp',
            'host'     => env('FILE_SYSTEM_FTP_HOST', 'ftp.example.com'),
            'username' => env('FILE_SYSTEM_FTP_USER', 'user'),
            'password' => env('FILE_SYSTEM_FTP_PASSWORD', 'secret'),
            'root'     => env('FILE_SYSTEM_FTP_ROOT', '/public_html/uploads'),
            'port'     => env('FILE_SYSTEM_FTP_PORT', 21),
            'ssl'      => env('FILE_SYSTEM_FTP_SSL', false),
            'passive'  => env('FILE_SYSTEM_FTP_PASSIVE', true),
            'timeout'  => env('FILE_SYSTEM_FTP_TIMEOUT', 90),
        ],
    ],
];
PHP;
  }
  
  private function dbCachePrefetchTemplate() { return <<<'PHP'
<?php

use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Database\Caching\PrefetchManager;

return [
    /**
     * An example warmer to warm the users table as it will be 
     * frequently used by application
     */
    /*"users" => function (PrefetchManager $manager) {
        // Register a custom warmer that caches the most used user query
        $manager->registerWarmer('users:active', function ($cache) {
            // Build your query (example using QueryBuilder)
            $result = (new QueryBuilder("users"))
                      ->select(['*'])->get();
            $key = 'dbq:' . md5('SELECT * FROM users WHERE active = ?') . ':' . md5('1');
            $cache->tags(['users'])->set($key, $result, 4);
        });
        $manager->warm('users:active');
    },*/
];
PHP;
  }
  
  private function cacheConfigTemplate() { return <<<'PHP'
<?php
/**
 * Cache Configuration
 */
return [
    'default' => env('CACHE_DRIVER', 'file'),
    'stores' => [
        'redis' => [
          'driver' => 'redis',
          'host' => env('REDIS_HOST', '127.0.0.1')
        ],
        'array' => [
          'driver' => 'array', 
          'max_items' => 500,
          'eviction' => 'lru'
        ],
        'file' => [
          'driver' => 'file',
          'path' =>  env('CACHE_LOCAL_STORAGE') ?: __DIR__ . '/../storage/',
          'max_files' => 5000,
          'file_perm' => 0644,
        ],
    ],
    'prefix' => env('CACHE_PREFIX', 'machinjiri_cache'),
    'default_ttl' => env('CACHE_DEFAULT_TTL', 300),
    'stampede_protection' => true
];
PHP;
  }
  
  private function databaseConfigTemplate() { return <<<'PHP'
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
PHP;
  }
}