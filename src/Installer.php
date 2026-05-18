<?php

namespace Preciouslyson\MachinjiriInstaller;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Installer
{
    private SymfonyStyle $io;
    private ?string $composer;
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
                
                if (!$this->io->confirm("Directory '{$this->projectDir}' already exists. Overwrite?", false)) {
                    throw new \RuntimeException('Installation cancelled.');
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
          'storage/session',
          'storage/cache',
          'storage/logs',
          'app',
          'app/Controllers',
          'app/Middleware',
          'app/Model',
          'app/Providers',
          'app/Queue/Drivers',
          'tests/Unit',
          'tests/Features',
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
# Application Configuration
APP_NAME="MachinjiriApp"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:3000
APP_KEY=
APP_CIPHER=aes-256-gcm

# Database Configuration
DB_CONNECTION=sqlite # or mysql
DB_DATABASE=database/database.sqlite
DB_FOREIGN_KEYS=true

# For Mysql Databases
#DB_HOST=host-server
#DB_USERNAME=your-username
#DB_PASSWORD=your-password
#DB_DATABASE=database-name

# Cache Configuration
CACHE_DRIVER=file
CACHE_PREFIX=machinjiri_cache

# Session Configuration
SESSION_DRIVER=file
SESSION_LIFETIME=120
SESSION_COOKIE=machinjiri_session
SESSION_DOMAIN=null
SESSION_SECURE_COOKIE=false

# Queue Configuration
QUEUE_CONNECTION=sync
QUEUE_FAILED_DRIVER=database

# Mail Configuration
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_ENCRYPTION=tls
MAIL_USERNAME=
MAIL_PASSWORD=
MAIL_FROM_ADDRESS=hello@example.com
MAIL_FROM_NAME=Example

# Logging Configuration
LOG_CHANNEL=stack
LOG_LEVEL=debug

# Asset Configuration
ASSET_URL=null

# View Configuration
VIEW_COMPILED_PATH=storage/framework/views

# JWT
JWT_SECRET=your-super-secret-jwt-key-here
JWT_ALGO=HS256
JWT_EXPIRATION=3600
JWT_ISSUER=your-app-name
JWT_AUDIENCE=your-app-audience
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
        $write($this->projectDir . '/routes/web.php', $this->webRouteTemplate());
        $write($this->projectDir . '/app/Controllers/HomeController.php', $this->homeControllerTemplate());
        $write($this->projectDir . '/resources/views/welcome.view.php', $this->welcomeTemplate());

        $write($this->projectDir . '/.gitignore', $this->gitIgnoreTemplate());

        $write($this->projectDir . '/artisan', $this->artisanTemplate(), 0755);
        @chmod($this->projectDir . '/artisan', 0755);

        $write($this->projectDir . '/.htaccess', $this->rootHtaccess());
        $write($this->projectDir . '/public/.htaccess', $this->publicHtaccess());
        
        $write($this->projectDir . '/config/providers.php', $this->providersTemplate());
        $write($this->projectDir . '/config/app.php', $this->appConfigTemplate());
        $write($this->projectDir . '/config/appserviceprovider.php', $this->AppServiceProviderFileTemplate());
        $write($this->projectDir . '/app/Providers/AppServiceProvider.php', $this->AppServiceProviderTemplate());
        $write($this->projectDir . '/config/databaseserviceprovider.php', $this->DatabaseServiceProviderFileTemplate());
        $write($this->projectDir . '/app/Providers/DatabaseServiceProvider.php', $this->DatabaseServiceProviderTemplate());

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
use Mlangeni\Machinjiri\Core\Http\HttpResponse;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;

// Load helper functions
require_once CWD . '/helpers.php';

/**
 * Read APP_DEBUG from environment (supports `.env` or server env)
 * Default to true (development) if not explicitly set to false.
 */
\$debug = filter_var(getenv('APP_DEBUG') ?: true, FILTER_VALIDATE_BOOLEAN);

/**
 * Instantiating the Machinjiri Framework
 */
\$machinjiri = Machinjiri::App(CWD, \$debug);
/**
 * Start App Entry Logger
 */
\$log = new Logger('app_main');
PHP;
    }

    private function publicIndexTemplate(): string { return <<<PHP
<?php
/*
 * Public Entry Point
 * This file serves as the front controller for all HTTP requests.
 * It bootstraps the application and handles incoming requests.
 * Make sure to keep this file secure and do not expose sensitive information.
 */
require __DIR__ . '/../bootstrap/app.php';

try {
    /* Initialise and run the application (includes request handling & response sending) */
    \$machinjiri->init();
} catch (Throwable \$e) {
    \$log->error(\$e->getMessage(), [
      'file' => \$e->getFile(),
      'line' => \$e->getLine(),
    ]);
    /* Send a generic 500 error page in production, detailed error in debug mode */
    \$isDebug = defined('APP_DEBUG') ? APP_DEBUG : false;
    if (\$isDebug) {
      (new HttpResponse())
      ->setStatusCode(500)
      ->setBody('<h1>Application Error</h1><pre>' . htmlspecialchars((string) \$e) . '</pre>')
      ->send();
    } else {
      (new HttpResponse())
      ->setStatusCode(500)
      ->setBody('<h1>Server Error</h1><p>Something went wrong. Please try again later.</p>')
      ->send();
    }
    exit(1);
}
/* Handle the incoming request and send the response */
PHP;
    }

    private function webRouteTemplate(): string { return <<<'PHP'
<?php

use Mlangeni\Machinjiri\Core\Routing\Router;

/**
 * Web Routes
 * Define your web routes here.
 * You can create additional route files as needed.
 * Remember to keep your routes organized and manageable.
 */

/* Example Route */
Router::get('/', 'HomeController@index', 'welcome');




/* Dispatch the router to handle the incoming request */
Router::dispatch();

PHP;
    }

    private function homeControllerTemplate(): string { return <<<PHP
<?php

/**
 * Home Controller
 * Handles requests to the home page.
 */
namespace Mlangeni\\Machinjiri\\App\\Controllers;

use Mlangeni\\Machinjiri\\Core\\Views\\View;

// HomeController class
class HomeController
{
    public function index(): void
    {
        // Render the welcome view
        View::make('welcome')->display();
    }
}
PHP;
    }

    private function welcomeTemplate(): string { 
      $version = ($this->getTemplateData()['version'] === '*') ? 'Latest' : $this->getTemplateData()['version'];
      $date = $this->getTemplateData()['date'];
      $appName = $this->getTemplateData()['project_name'];

    return <<<HTML
<?php use Mlangeni\\Machinjiri\\Core\\Views\\View; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover">
    <title>Machinjiri - Your Cozy Dev Space</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #FCF7F0;
            font-family: 'Inter', system-ui, -apple-system, 'Segoe UI', 'Poppins', 'Roboto', sans-serif;
            color: #2E2C2A;
            line-height: 1.5;
            min-height: 100vh;
            padding: 2rem 1.5rem;
            position: relative;
        }

        /* cozy background texture */
        body::before {
            content: "";
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-image: radial-gradient(#E8DCCC 1px, transparent 1px);
            background-size: 28px 28px;
            opacity: 0.3;
            pointer-events: none;
            z-index: 0;
        }

        .cozy-container {
            max-width: 1280px;
            margin: 0 auto;
            position: relative;
            z-index: 2;
        }

        /* header section – soft and glowing */
        .welcome-header {
            text-align: center;
            margin-bottom: 2.5rem;
            animation: fadeSlideUp 0.6s ease-out;
        }

        .cozy-badge {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #FFF3E6;
            padding: 0.5rem 1.2rem;
            border-radius: 60px;
            font-size: 0.85rem;
            font-weight: 500;
            color: #C97E5A;
            border: 1px solid #FFE2CC;
            margin-bottom: 1.2rem;
            backdrop-filter: blur(2px);
        }

        .cozy-badge i {
            font-size: 1rem;
        }

        .welcome-header h1 {
            font-size: 3.2rem;
            font-weight: 700;
            background: linear-gradient(135deg, #E68A5E, #C4633A);
            background-clip: text;
            -webkit-background-clip: text;
            color: transparent;
            letter-spacing: -0.02em;
            margin-bottom: 0.75rem;
            display: inline-flex;
            align-items: center;
            gap: 12px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .welcome-header h1 i {
            background: none;
            color: #E68A5E;
            font-size: 2.8rem;
        }

        .tagline {
            font-size: 1.2rem;
            color: #6B5E53;
            max-width: 580px;
            margin: 0 auto;
            background: rgba(255, 245, 235, 0.7);
            padding: 0.6rem 1.4rem;
            border-radius: 48px;
            backdrop-filter: blur(4px);
        }

        /* cozy card style */
        .card {
            background: #FFFFFFDD;
            backdrop-filter: blur(2px);
            border-radius: 32px;
            padding: 1.8rem;
            box-shadow: 0 12px 28px -8px rgba(0, 0, 0, 0.05), 0 2px 4px rgba(0, 0, 0, 0.02);
            transition: all 0.25s ease;
            border: 1px solid #F2E5D8;
        }

        .card:hover {
            transform: translateY(-3px);
            box-shadow: 0 20px 32px -12px rgba(90, 50, 25, 0.12);
            border-color: #FADDC7;
        }

        /* grid layout */
        .grid-2cols {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.8rem;
            margin-bottom: 2.5rem;
        }

        .detail-list {
            list-style: none;
            margin-top: 0.8rem;
        }

        .detail-list li {
            margin-bottom: 0.7rem;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .detail-list li i {
            color: #E68A5E;
            width: 22px;
            font-size: 1rem;
        }

        .status-chip {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: #F0EEE9;
            padding: 0.35rem 1rem;
            border-radius: 60px;
            font-size: 0.8rem;
            font-weight: 500;
            color: #926B4B;
            margin-top: 1rem;
            border: 1px solid #E9DDD0;
        }

        /* quick actions grid */
        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(190px, 1fr));
            gap: 1.5rem;
            margin: 2rem 0 2rem;
        }

        .action-item {
            background: #FFFFFF;
            border-radius: 28px;
            padding: 1.5rem 1rem;
            text-align: center;
            transition: all 0.2s;
            border: 1px solid #F1E3D6;
            box-shadow: 0 6px 12px -6px rgba(0, 0, 0, 0.03);
        }

        .action-item:hover {
            background: #FFF9F3;
            border-color: #EECEB6;
            transform: scale(0.98);
        }

        .action-icon {
            background: #FCE9DE;
            width: 64px;
            height: 64px;
            border-radius: 36px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 1rem;
            font-size: 1.8rem;
            color: #E68A5E;
        }

        .action-item h3 {
            font-size: 1.25rem;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: #3E332A;
        }

        .action-item p {
            font-size: 0.85rem;
            color: #7C6B5C;
            margin-bottom: 1.2rem;
        }

        .cozy-btn {
            background: #E68A5E;
            border: none;
            padding: 0.6rem 1.2rem;
            border-radius: 60px;
            font-weight: 600;
            font-size: 0.85rem;
            color: white;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            transition: all 0.2s;
            text-decoration: none;
            justify-content: center;
        }

        .cozy-btn.outline {
            background: transparent;
            border: 1px solid #E6CFBC;
            color: #A57253;
        }

        .cozy-btn.outline:hover {
            background: #F8EDE3;
            border-color: #E68A5E;
            color: #C4633A;
        }

        .cozy-btn:hover {
            background: #CD7350;
            transform: translateY(-2px);
        }

        /* steps area */
        .steps-wrapper {
            background: #FFF9F3;
            border-radius: 32px;
            padding: 2rem;
            margin: 1.5rem 0;
            border: 1px solid #F7E8DB;
        }

        .steps-title {
            font-size: 1.7rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 1.8rem;
            color: #3E332A;
        }

        .steps-container {
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }

        .step-row {
            display: flex;
            align-items: center;
            gap: 1rem;
            background: white;
            padding: 1rem 1.4rem;
            border-radius: 28px;
            box-shadow: 0 2px 6px rgba(0,0,0,0.02);
            border: 1px solid #F3E4D5;
            transition: all 0.2s;
        }

        .step-number {
            background: #FCE5D8;
            width: 42px;
            height: 42px;
            display: flex;
            align-items: center;
            justify-content: center;
            border-radius: 30px;
            font-weight: 800;
            font-size: 1.2rem;
            color: #E68A5E;
        }

        .step-content {
            flex: 1;
        }

        .step-content h4 {
            font-size: 1.1rem;
            font-weight: 600;
            margin-bottom: 4px;
        }

        .step-content p {
            font-size: 0.9rem;
            color: #6F5E4E;
        }

        .step-content code {
            background: #F5EDE4;
            padding: 0.2rem 0.6rem;
            border-radius: 24px;
            font-family: 'SF Mono', 'Fira Code', monospace;
            font-size: 0.8rem;
            color: #C4633A;
        }

        .footer-cozy {
            margin-top: 2rem;
            text-align: center;
            font-size: 0.85rem;
            color: #A48E78;
            border-top: 1px solid #F0E0D2;
            padding-top: 2rem;
            display: flex;
            flex-wrap: wrap;
            justify-content: space-between;
            align-items: center;
            gap: 1rem;
        }

        .footer-links a {
            color: #B47C5A;
            text-decoration: none;
            margin: 0 0.7rem;
            transition: color 0.2s;
        }

        .footer-links a:hover {
            color: #E68A5E;
            text-decoration: underline;
        }

        @keyframes fadeSlideUp {
            from {
                opacity: 0;
                transform: translateY(18px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 680px) {
            body { padding: 1.2rem; }
            .welcome-header h1 { font-size: 2.2rem; }
            .tagline { font-size: 1rem; }
            .step-row { flex-direction: column; align-items: flex-start; }
            .footer-cozy { flex-direction: column; text-align: center; }
        }
    </style>
</head>
<body>
<div class="cozy-container">
    <div class="welcome-header">
        <div class="cozy-badge">
            <i class="fas fa-mug-hot"></i> <span>fresh install · ready to create</span> <i class="fas fa-heart" style="color: #E68A5E;"></i>
        </div>
        <h1>
            <i class="fas fa-feather-alt"></i> 
            You're all set!
        </h1>
        <div class="tagline">Machinjiri is installed — cozy, fast, and waiting for your ideas.
        </div>
    </div>

    <!-- main content grid: system details + components -->
    <div class="grid-2cols">
        <div class="card">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1.2rem;">
                <i class="fas fa-rocket" style="font-size: 1.8rem; color: #E68A5E;"></i>
                <h2 style="font-size: 1.5rem; font-weight: 600;">App snapshot</h2>
            </div>
            <div>
                <p><strong>Name:</strong> Machinjiri • <span style="background:#F7EFE7; padding:2px 8px; border-radius:20px;">$appName</span></p>
                <p><strong>Release:</strong> $version <i class="fas fa-check-circle" style="color:#7AA77B;"></i></p>
                <p><strong>Born on:</strong> 2026-05-18 <i class="fas fa-calendar-alt" style="color:#B58E6B;"></i></p>
                <div class="status-chip">
                    <i class="fas fa-shield-alt"></i> Verified & cozy-ready
                </div>
            </div>
        </div>

        <div class="card">
            <div style="display: flex; align-items: center; gap: 12px; margin-bottom: 1rem;">
                <i class="fas fa-puzzle-piece" style="font-size: 1.6rem; color: #E68A5E;"></i>
                <h2 style="font-size: 1.5rem; font-weight: 600;">Components installed</h2>
            </div>
            <ul class="detail-list">
                <li><i class="fas fa-cubes"></i> Core Framework + CLI</li>
                <li><i class="fas fa-database"></i> Database ORM (Eloquent-like)</li>
                <li><i class="fas fa-id-card"></i> Auth & Session system</li>
                <li><i class="fas fa-cloud-sun"></i> API Toolkit & Middleware</li>
                <li><i class="fas fa-vial"></i> Testing Suite ready</li>
                <li><i class="fas fa-laptop-code"></i> Dev server + hot reload</li>
            </ul>
        </div>
    </div>

    <!-- Quick actions - catchy & cozy -->
    <div class="action-grid">
        <div class="action-item">
            <div class="action-icon"><i class="fas fa-book-open"></i></div>
            <h3>Read the docs</h3>
            <p>Cozy tutorials, API references, and best practices.</p>
            <a href="https://github.com/preciouslyson/machinjiri" target="_blank" class="cozy-btn outline">Explore docs →</a>
        </div>
        <div class="action-item">
            <div class="action-icon"><i class="fas fa-terminal"></i></div>
            <h3>CLI power</h3>
            <p>Generate models, controllers, and migrations.</p>
            <a href="https://github.com/preciouslyson/machinjiri#Console" class="cozy-btn outline">php artisan list</a>
        </div>
        <div class="action-item">
            <div class="action-icon"><i class="fas fa-users"></i></div>
            <h3>Community</h3>
            <p>Join discord, share your cozy creations.</p>
            <a href="#" class="cozy-btn outline">Join the hub →</a>
        </div>
        <div class="action-item">
            <div class="action-icon"><i class="fas fa-mug-saucer"></i></div>
            <h3>First app</h3>
            <p>Build a "Hello, cozy world" in 2 minutes.</p>
            <a href="#" class="cozy-btn outline">Start building</a>
        </div>
    </div>

    <!-- Next steps - cozy roadmap -->
    <div class="steps-wrapper">
        <div class="steps-title">
            <i class="fas fa-map-signs"></i> your next cozy steps
        </div>
        <div class="steps-container">
            <div class="step-row">
                <div class="step-number">1</div>
                <div class="step-content">
                    <h4>Run the dev server</h4>
                    <p>In your project root, type <code>php artisan server:start</code> and visit <code>http://localhost:3000</code></p>
                </div>
            </div>
            <div class="step-row">
                <div class="step-number">2</div>
                <div class="step-content">
                    <h4>Create your first route</h4>
                    <p>Open <code>routes/web.php</code> and add: <code>Route::get('/welcome', 'HomeController@index', 'welcome');</code></p>
                </div>
            </div>
            <div class="step-row">
                <div class="step-number">3</div>
                <div class="step-content">
                    <h4>Explore the starter kit</h4>
                    <p>Check <code>/resources/views</code> and customize your cozy layout.</p>
                </div>
            </div>
            <div class="step-row">
                <div class="step-number">4</div>
                <div class="step-content">
                    <h4>Configure .env</h4>
                    <p>Set database, app URL and enjoy full framework magic.</p>
                </div>
            </div>
        </div>
        <div style="margin-top: 1.5rem; text-align: center;">
            <a href="https://github.com/preciouslyson/machinjiri#Introduction" class="cozy-btn"><i class="fas fa-graduation-cap"></i> full getting started guide</a>
        </div>
    </div>

    <!-- footer cozy love -->
    <div class="footer-cozy">
        <div>
            <i class="fas fa-heart" style="color: #E68A5E;"></i> Machinjiri — where code meets comfort
        </div>
        <div class="footer-links">
            <a href="https://github.com/preciouslyson/machinjiri"><i class="fab fa-github"></i> GitHub</a>
            <a href="https://github.com/preciouslyson/machinjiri/support"><i class="fas fa-life-ring"></i> Support</a>
            <a href="https://github.com/preciouslyson/machinjiri/feedback"><i class="fas fa-comment-dots"></i> Feedback</a>
        </div>
        <div>
            &copy; 2024 - $date
        </div>
    </div>
</div>
</body>
</html>
HTML;
    }
    
    private function gitIgnoreTemplate(): string { return <<<GIT
/vendor/
/node_modules/
.env
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
            <html outputDirectory="build/coverage" lowUpperBound="35" highLowerBound="70"/>
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
    
    public function AppServiceProviderTemplate () {return <<<'PHP'
<?php

namespace Mlangeni\Machinjiri\App\Providers;

use Mlangeni\Machinjiri\Core\Providers\ServiceProvider;
use Mlangeni\Machinjiri\Core\Http\HttpRequest;
use Mlangeni\Machinjiri\Core\Http\HttpResponse;
use Mlangeni\Machinjiri\Core\Authentication\Session;
use Mlangeni\Machinjiri\Core\Authentication\Cookie;
use Mlangeni\Machinjiri\Core\Database\DatabaseConnection;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;
use Mlangeni\Machinjiri\Core\Artisans\Events\EventListener;
use Mlangeni\Machinjiri\Core\Debug\Debugger;

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

        // Register database connection
        $this->singleton('db.connection', function($app) {
            $config = $app->getConfigurations()['database'];
            DatabaseConnection::setConfig($config);
            DatabaseConnection::setPath($app->database);
            return DatabaseConnection::getInstance();
        });
        
        // Register Debugger
        $this->app->singleton(Debugger::class, function ($app) {
            return new Debugger($app);
        });

        // Register EventListener service
        $this->bind('events', function($app) {
            $logger = new Logger('events');
            return new EventListener($logger);
        });

        // Register aliases for easier access
        $this->aliasMany([
            'request' => HttpRequest::class,
            'response' => HttpResponse::class,
            'session' => Session::class,
            'cookie' => Cookie::class,
            'db' => 'db.connection',
            'debugger' => Debugger::class,
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
            $this->mergeConfigFrom($configDir . 'database.php', 'database');
        }

        // Load application routes
        $routesDir = $this->app->routes;
        if (is_dir($routesDir)) {
            $this->loadRoutesFrom($routesDir . 'web.php');
        }

        // Trigger app booted event
        $this->triggerEvent('app.booted');
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
use Mlangeni\Machinjiri\Core\Database\Seeder\SeederManager;
use Mlangeni\Machinjiri\Core\Database\Factory\FactoryManager;
use Mlangeni\Machinjiri\Core\Database\Migrations\MigrationHandler;
use Mlangeni\Machinjiri\Core\Database\Migrations\MigrationCreator;
use Mlangeni\Machinjiri\Core\Database\QueryBuilder;
use Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;

class DatabaseServiceProvider extends ServiceProvider
{
    /**
     * Register core application services
     */
    public function register(): void
    {
        $this->singleton('migration.creator', function ($app) {
          return new MigrationCreator();
        });
        
        $this->singleton('migration.manager', function ($app) {
          return new MigrationHandler();
        });
        
        $this->singleton('db.seeder', function ($app) {
          return new SeederManager($app);
        });
        
        $this->singleton('db.factory', function ($app) {
          return new FactoryManager($app);
        });
        
    }
    
    public function boot(): void
    {}
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
  
  protected function appConfigTemplate () { return <<<'PHP'
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
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */
    'providers' => [
        
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */
    'aliases' => [
    
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify which database connection the application should
    | use. The default is SQLite, but other connections are available.
    |
    */
    'database' => [
        'default' => env('DB_CONNECTION', 'sqlite'),
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => env('DB_DATABASE', env('DB_DATABASE')),
                'prefix' => '',
                'foreign_key_constraints' => env('DB_FOREIGN_KEYS', true),
            ],
            // Add other database connections as needed
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the cache store that should be used by the
    | application. The default is "file", but other stores are available.
    |
    */
    'cache' => [
        'default' => env('CACHE_DRIVER', 'file'),
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => BASE . 'storage/cache',
            ],
            // Add other cache stores as needed
        ],
        'prefix' => env('CACHE_PREFIX', 'machinjiri_cache'),
    ],
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
        'files' => BASE . '/storage/session',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => env(
            'SESSION_COOKIE',
            'machinjiri_session'
        ),
        'path' => '/',
        'domain' => env('SESSION_DOMAIN'),
        'secure' => env('SESSION_SECURE_COOKIE', false),
        'http_only' => true,
        'same_site' => 'lax',
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may specify the mail settings for the application.
    |
    */
    'mail' => [
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
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Service Provider Configuration
    |--------------------------------------------------------------------------
    |
    | Configuration for service provider behavior.
    |
    */
    'provider' => [
        'deferred' => [
            // List deferred service providers here
        ],
        'eager' => [
            // List eager-loaded service providers here
        ],
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
  
  protected function AppServiceProviderFileTemplate () { return <<<'PHP'
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Appserviceprovider Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file manages the settings for the Appserviceprovider
    | component of your application.
    |
    | You can modify these values to suit your application needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Configuration Section
    |--------------------------------------------------------------------------
    |
    | Add your configuration values here. You can organize them into
    | logical sections as needed for your application.
    |
    */

];
PHP;
  }
  
  protected function DatabaseServiceProviderFileTemplate () { return <<<'PHP'
<?php
return [
    /*
    |--------------------------------------------------------------------------
    | DatabaseServiceProvider Configuration
    |--------------------------------------------------------------------------
    |
    | This configuration file manages the settings for the DatabaseServiceProvider
    | component of your application.
    |
    | You can modify these values to suit your application needs.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Configuration Section
    |--------------------------------------------------------------------------
    |
    | Add your configuration values here. You can organize them into
    | logical sections as needed for your application.
    |
    */

];
PHP;
  }
  
  private function helpersFileTemplate () {<<<'PHP'
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
        $value = $_ENV[$key] ?? $_SERVER[$key] ?? env($key);
        
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
PHP;
  }
}