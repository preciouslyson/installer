<?php

namespace Mlangeni\Machinjiri\Installer;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;
use \RuntimeException;
use Mlangeni\Machinjiri\Installer\StarterkitManager;
use Mlangeni\Machinjiri\Installer\Stubs\ConfigFiles;
use Mlangeni\Machinjiri\Installer\Stubs\Database;
use Mlangeni\Machinjiri\Installer\Stubs\Root;
use Mlangeni\Machinjiri\Installer\Stubs\Resources;
use Mlangeni\Machinjiri\Installer\Stubs\Publics;
use Mlangeni\Machinjiri\Installer\Stubs\Bootstrap;

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
            $this->writeComposerJson($projectName, $options['description']);
            $this->progress(7, 'Writing environment configuration...');
            $this->writeEnvironmentFile();
            $this->progress(8, 'Installing dependencies via Composer...');
            $this->runComposerInstall();
            $this->progress(9, 'Generating application key...');
            $this->generateAppKey();
            $this->progress(10, 'Generating database config...');
            $this->generateDatabaseConfig();
            $this->progress(11, 'Validating installation...');
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
            $this->validator->validatePhpVersion('8.2.0');
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
                if (($this->options['no-interaction'] ?? false)) {
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
          'public/src',
          'public/src/css',
          'public/src/js',
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
          'storage/uploads',
          'storage/framework',
          'storage/framework/queue',
          'storage/session',
          'storage/cache',
          'storage/logs',
          'app',
          'app/Controllers',
          'app/Middleware',
          'app/Models',
          'app/Providers',
          'app/Queue/Drivers',
          'tests/Unit',
          'tests/Features',
          'config',
          'config/services',
          'config/core',
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

    private function writeComposerJson(string $projectName, string $description): void
    {
        $version = $this->options['version'] ?? '*';
        
        $vendor = strtolower(str_replace([' ', '-', '_', '.'], '', $this->options['company'] ?? 'machinjiri'));
        $packageName = strtolower(str_replace(' ', '-', $projectName));
        
        $composerJson = [
            'name' => $vendor . '/' . $packageName,
            'description' => $description,
            'type' => 'project',
            'require' => [
                'php' => '^' . VersionManager::RECOMMENDED_PHP_VERSION,
                'machinjiri/framework' => $version,
            ],
            'require-dev' => [
                'phpunit/phpunit' => '^10.0',
            ],
            'autoload' => [
                'psr-4' => [
                    'App\\' => 'app/',
                    'Mlangeni\\Machinjiri\\Database\\' => "database/",
                ]
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
APP_SUPPORT_EMAIL=admin@{$this->projectName}.com

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

DB_PLACEHOLDER=null

# -------------------------------------------------
# Cache Configuration                             |
# -------------------------------------------------
CACHE_DRIVER=file
CACHE_PREFIX={$this->projectName}
CACHE_DEFAULT_TTL=300
CACHE_LOCAL_STORAGE=

# -------------------------------------------------
# Session Configuration                           |
# -------------------------------------------------
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_COOKIE={$this->projectName}_session
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false

# ------------------------------------------------
# Queue Configuration                            |
# ------------------------------------------------
QUEUE_DRIVER=sync
QUEUE_FAILED_DRIVER=database
QUEUE_WORKER_MAX_MEMORY=256
QUEUE_WORKER_MAX_JOBS=1000

# Supervisor monitor settings
QUEUE_WORKER_CHECK_INTERVAL=5
QUEUE_WORKER_HEARTBEAT_TTL=15
QUEUE_WORKER_GRACE_PERIOD=10
QUEUE_WORKER_STOP_ON_EMPTY=false
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
MAIL_USERNAME=someone@{$this->projectName}.com
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=no-reply@{$this->projectName}.com
MAIL_FROM_NAME={$this->projectName}

# -------------------------------------------------
# Redis Server Configuration                      |
# -------------------------------------------------
REDIS_HOST=127.0.0.1
REDIS_PORT=6379
REDIS_PASSWORD=null
REDIS_DATABASE=0
REDIS_TIMEOUT=5
REDIS_READ_WRITE_TIMEOUT=2.5
REDIS_RETRY_INTERVAL=100
REDIS_PREFIX=
REDIS_SERIALIZE=true

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
JWT_SECRET=null
JWT_ALGO=HS256
JWT_EXPIRATION=3600
JWT_ISSUER={$this->projectName}
JWT_AUDIENCE={$this->projectName}-audience

# ------------------------------------------------
# File System Configuration                      |
# ------------------------------------------------
FILE_SYSTEM_DEFAULT_DRIVER=local
FILE_SYSTEM_ROOT=

# ftp connection
FILE_SYSTEM_FTP_HOST=ftp.example.com
FILE_SYSTEM_FTP_USER=your-username-here
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

    private function generateDatabaseConfig(): void
    {
        $envPath = $this->projectDir . '/.env';

        $config = match ($this->options['database']) {
            "sqlite" => Root::sqliteEnvTemplate(),
            "mysql" => Root::mysqlEnvTemplate(),
        };
        
        if (file_exists($envPath)) {
            $envContent = file_get_contents($envPath);
            $envContent = preg_replace('/DB_PLACEHOLDER=.*/', $config, $envContent);
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
        
        /* Starter kit Installation */
        (new StarterkitManager($this->projectDir))
        ->install($this->options['starter'], $write, $this->getTemplateData());
        
        /* Configuration files */
        $write($this->projectDir . '/config/services/providers.php', ConfigFiles::providersTemplate());
        $write($this->getCoreConfigDir() . '/app.php', ConfigFiles::appConfigTemplate());
        $write($this->getCoreConfigDir() . '/mail.php', ConfigFiles::mailConfigTemplate());
        $write($this->getCoreConfigDir() . '/database.php', ConfigFiles::databaseConfigTemplate($this->options['database']));
        $write($this->getCoreConfigDir() . '/cache.php', ConfigFiles::cacheConfigTemplate());
        $write($this->getCoreConfigDir() . '/queue.php', ConfigFiles::queueConfigFileTemplate());
        $write($this->getCoreConfigDir() . '/routing.php', ConfigFiles::routingConfigFileTemplate());
        $write($this->getCoreConfigDir() . '/filesystem.php', ConfigFiles::fileSystemConfigurationTemplate());
        $write($this->getCoreConfigDir() . '/auth.php', ConfigFiles::authConfigurationTemplate());
        $write($this->getCoreConfigDir() . '/oauth.php', ConfigFiles::OAuthConfigurationTemplate());
        $write($this->getCoreConfigDir() . '/ldap.php', ConfigFiles::ldapConfigurationTemplate());
        $write($this->getCoreConfigDir() . '/redis.php', ConfigFiles::redisConfigurationTemplate());
        $write($this->getCoreConfigDir() . '/sms.php', ConfigFiles::smsConfigurationTemplate());
        
        /* Database Files */
        $write($this->projectDir . '/database/cache-prefetch-db.php', Database::dbCachePrefetchTemplate());
        
        /* App Bootstrap Files */
        $write($this->projectDir . '/bootstrap/app.php', Bootstrap::bootstrapTemplate(), 0444);
        
        /* Root Config Files */
        $write($this->projectDir . '/phpunit.xml', Root::phpunitTemplate());
        $write($this->projectDir . '/.htaccess', Root::rootHtaccess());
        $write($this->projectDir . '/.gitignore', Root::gitIgnoreTemplate());
        $write($this->projectDir . '/artisan', Root::artisanTemplate(), 0755);
        @chmod($this->projectDir . '/artisan', 0755);
        
        /* Resources files */
        $write($this->projectDir . '/resources/views/errors/404.view.php', Resources::notFoundViewTemplate());
        $write($this->projectDir . '/resources/views/layouts/error.layout.php', Resources::notFoundLayoutTemplate());
        
        /* Public Entry Files */
        $write($this->projectDir . '/public/index.php', Publics::publicIndexTemplate(), 0444);
        
        $write($this->projectDir . '/public/.htaccess', Publics::publicHtaccess());
        if ($this->options['starter'] === "default") {
            $write($this->projectDir . '/public/src/js/app.js', Publics::jsTemplate());
            $write($this->projectDir . '/public/src/css/app.css', Publics::cssTemplate());
        }
    }

    private function getCoreConfigDir(): string 
    {
        return $this->projectDir . "/config/core";
    }
  
}