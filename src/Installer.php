<?php

namespace Preciouslyson\MachinjiriInstaller;

use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class Installer
{
    private $io;
    private $composer;
    private $projectDir;
    private $options;
    
    const DOCS_URL = ""; // coming soon
    const FORUMS_URL = ""; // coming soon
    
    public function __construct(SymfonyStyle $io)
    {
        $this->io = $io;
        $this->composer = $this->findComposer();
    }

    public function install(string $projectName, array $options = []): void
    {
        $this->projectDir = getcwd() . DIRECTORY_SEPARATOR . $projectName;
        $this->options = $options;

        $this->checkRequirements();
        $this->prepareDirectory();
        $this->createProjectStructure();
        $this->createFiles();
        $this->writeComposerJson($projectName);
        $this->writeEnvironmentFile();
        $this->runComposerInstall();
        $this->generateAppKey();
        $this->validateInstallation();
    }

    private function checkRequirements(): void
    {
        $requirements = [
            'PHP Version' => [
                'required' => '8.0.0',
                'current' => PHP_VERSION,
                'check' => version_compare(PHP_VERSION, '8.0.0', '>='),
            ],
            'JSON Extension' => [
                'required' => 'enabled',
                'current' => extension_loaded('json') ? 'enabled' : 'disabled',
                'check' => extension_loaded('json'),
            ],
            'MBString Extension' => [
                'required' => 'enabled',
                'current' => extension_loaded('mbstring') ? 'enabled' : 'disabled',
                'check' => extension_loaded('mbstring'),
            ],
            'OpenSSL Extension' => [
                'required' => 'enabled',
                'current' => extension_loaded('openssl') ? 'enabled' : 'disabled',
                'check' => extension_loaded('openssl'),
            ],
            'Composer' => [
                'required' => 'installed',
                'current' => $this->composer ? 'found' : 'not found',
                'check' => $this->composer !== null,
            ],
        ];

        $failed = [];
        foreach ($requirements as $name => $requirement) {
            if (!$requirement['check']) {
                $failed[] = "$name ({$requirement['current']})";
            }
        }

        if (!empty($failed)) {
            throw new \RuntimeException(
                "System requirements not met:\n" . implode("\n", $failed)
            );
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
        $this->io->section('Creating project structure...');

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
        ];

        foreach ($directories as $directory) {
            $path = $this->projectDir . DIRECTORY_SEPARATOR . $directory;
            if (!is_dir($path)) {
                mkdir($path, 0755, true);
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
                'preciouslyson/machinjiri' => $version,
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

    private function writeEnvironmentFile(): void
    {
        $envContent = <<<ENV
# Application Configuration
APP_NAME="MachinjiriApp"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost
APP_KEY=
APP_CIPHER=aes-256-gcm

# Database Configuration
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
DB_FOREIGN_KEYS=true

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

        file_put_contents($this->projectDir . '/.env', $envContent);
    }

    private function runComposerInstall(): void
    {
        $this->io->section('Installing dependencies...');

        $args = [
            $this->composer,
            'install',
            '--no-interaction',
            '--prefer-dist',
            '--optimize-autoloader',
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
        $this->io->section('Generating application key...');

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
        'docs_url' => self::DOCS_URL,
        'forums_url' => self::FORUMS_URL,
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
        $write($this->projectDir . '/resources/views/welcome.mg.php', $this->welcomeTemplate());

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

declare(strict_types=1);
@session_start();

define('BASE', __DIR__ . '/../');
define('CWD', __DIR__);

require BASE . 'vendor/autoload.php';

use Mlangeni\Machinjiri\Core\Machinjiri;
use Mlangeni\Machinjiri\Core\Container;
use Mlangeni\Machinjiri\Core\ProviderLoader;

// Load helper functions
require_once CWD . '/helpers.php';

/**
 * Initialize the application
 *
 * @param array \$config Application configuration
 * @return Container
 */
function init_app(array \$config = []): Container
{
    // Create container
    \$container = new Container();
    
    // Set configuration
    foreach (\$config as \$key => \$value) {
        \$container->configurations[\$key] = \$value;
    }
    
    // Set the global instance
    Container::setInstance(\$container);
    
    // Create and set provider loader
    \$providerLoader = new ProviderLoader(\$container);
    \$container->providerLoader = \$providerLoader;
    
    // Register providers
    \$providerLoader->register();
    
    \$providerLoader->boot();
    
    return \$container;
}

/**
 * Get the application container (alias for app())
 *
 * @return Container|null
 */
function container(): ?Container
{
    return app();
}


\$machinjiri = Machinjiri::App(CWD, true); // True for Development and False for Production

PHP;
    }

    private function publicIndexTemplate(): string { return <<<PHP
<?php
require __DIR__ . '/../bootstrap/app.php';

\$machinjiri->init();
PHP;
    }

    private function webRouteTemplate(): string { return <<<'PHP'
<?php
use Mlangeni\Machinjiri\Core\Routing\Router;

$router = Router::getInstance();
$router->get('/', 'HomeController@index');
$router->dispatch();
PHP;
    }

    private function homeControllerTemplate(): string { return <<<PHP
<?php

namespace Mlangeni\\Machinjiri\\App\\Controllers;

use Mlangeni\\Machinjiri\\Core\\Views\\View;

class HomeController
{
    public function index(): void
    {
        View::make('welcome')->display();
    }
}
PHP;
    }

    private function welcomeTemplate(): string { 
      $version = $this->getTemplateData()['version'];
      $docsUrl = $this->getTemplateData()['docs_url'];
      $docsForumUrl = $this->getTemplateData()['forums_url'];
      $date = $this->getTemplateData()['date'];
      $appName = $this->getTemplateData()['project_name'];

    return <<<HTML
<?php use Mlangeni\\Machinjiri\\Core\\Views\\View; ?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Machiniri - Welcome</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root {
            --primary: #6f42c1;
            --secondary: #20c997;
            --light: #f8f9fa;
            --dark: #212529;
            --success: #28a745;
            --info: #17a2b8;
            --border-radius: 10px;
            --box-shadow: 0 6px 15px rgba(0, 0, 0, 0.08);
            --transition: all 0.3s ease;
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        body {
            background-color: #f9fafc;
            color: #333;
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 10% 20%, rgba(111, 66, 193, 0.05) 0%, transparent 20%),
                radial-gradient(circle at 90% 80%, rgba(32, 201, 151, 0.05) 0%, transparent 20%);
        }

        .success-container {
            max-width: 1000px;
            width: 100%;
            margin: 0 auto;
        }

        .success-header {
            background: linear-gradient(135deg, var(--primary) 0%, #5a32a3 100%);
            padding: 40px;
            border-radius: var(--border-radius) var(--border-radius) 0 0;
            text-align: center;
            position: relative;
            overflow: hidden;
            box-shadow: var(--box-shadow);
        }

        .success-header::before {
            content: "";
            position: absolute;
            top: -50%;
            left: -50%;
            width: 200%;
            height: 200%;
            background: radial-gradient(circle, rgba(255,255,255,0.2) 1px, transparent 1px);
            background-size: 20px 20px;
            opacity: 0.3;
            animation: float 20s linear infinite;
        }

        .success-icon {
            font-size: 5rem;
            color: white;
            margin-bottom: 20px;
            position: relative;
            z-index: 1;
            animation: pulse 2s infinite;
        }

        .success-title {
            font-size: 3rem;
            font-weight: 800;
            margin-bottom: 10px;
            color: white;
            position: relative;
            z-index: 1;
        }

        .success-subtitle {
            font-size: 1.2rem;
            color: rgba(255, 255, 255, 0.9);
            max-width: 600px;
            margin: 0 auto;
            position: relative;
            z-index: 1;
        }

        .success-content {
            background-color: white;
            border-radius: 0 0 var(--border-radius) var(--border-radius);
            overflow: hidden;
            box-shadow: var(--box-shadow);
            padding: 40px;
        }

        .installation-details {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }

        .detail-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            border-left: 5px solid var(--secondary);
            transition: var(--transition);
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
            border: 1px solid #eaeaea;
        }

        .detail-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 12px 20px rgba(0, 0, 0, 0.08);
        }

        .detail-header {
            display: flex;
            align-items: center;
            margin-bottom: 15px;
            color: var(--primary);
        }

        .detail-header i {
            font-size: 1.5rem;
            margin-right: 12px;
        }

        .detail-header h3 {
            font-size: 1.3rem;
        }

        .detail-content {
            font-size: 1.1rem;
            color: #555;
        }

        .detail-content ul {
            list-style-type: none;
            margin-top: 10px;
        }

        .detail-content li {
            margin-bottom: 8px;
            padding-left: 25px;
            position: relative;
        }

        .detail-content li::before {
            content: "✓";
            position: absolute;
            left: 0;
            color: var(--secondary);
            font-weight: bold;
        }

        .status-badge {
            display: inline-block;
            background-color: rgba(32, 201, 151, 0.15);
            color: var(--secondary);
            padding: 5px 15px;
            border-radius: 50px;
            font-weight: 600;
            font-size: 0.9rem;
            margin-top: 10px;
            border: 1px solid rgba(32, 201, 151, 0.3);
        }

        .quick-actions {
            background-color: rgba(111, 66, 193, 0.08);
            border-radius: var(--border-radius);
            padding: 30px;
            margin-bottom: 40px;
            border: 1px solid rgba(111, 66, 193, 0.2);
        }

        .quick-actions h2 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary);
            font-size: 1.8rem;
        }

        .action-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .action-card {
            background-color: white;
            border-radius: var(--border-radius);
            padding: 25px;
            text-align: center;
            transition: var(--transition);
            border: 1px solid #eaeaea;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .action-card:hover {
            border-color: var(--primary);
            transform: translateY(-3px);
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.08);
        }

        .action-icon {
            font-size: 2.5rem;
            color: var(--primary);
            margin-bottom: 15px;
            height: 70px;
            width: 70px;
            display: flex;
            align-items: center;
            justify-content: center;
            background-color: rgba(111, 66, 193, 0.1);
            border-radius: 50%;
        }

        .action-card h3 {
            margin-bottom: 10px;
            font-size: 1.3rem;
            color: #333;
        }

        .action-card p {
            font-size: 0.95rem;
            color: #666;
            margin-bottom: 20px;
            flex-grow: 1;
        }

        .action-btn {
            display: inline-block;
            background-color: var(--primary);
            color: white;
            padding: 10px 20px;
            border-radius: 50px;
            text-decoration: none;
            font-weight: 600;
            transition: var(--transition);
            width: 100%;
            text-align: center;
        }

        .action-btn:hover {
            background-color: #5a32a3;
            transform: translateY(-2px);
            box-shadow: 0 5px 12px rgba(0, 0, 0, 0.15);
        }

        .action-btn.secondary {
            background-color: var(--secondary);
            color: white;
        }

        .action-btn.secondary:hover {
            background-color: #1aa67d;
        }

        .next-steps {
            margin-top: 40px;
            padding-top: 30px;
            border-top: 1px solid #eaeaea;
        }

        .next-steps h2 {
            text-align: center;
            margin-bottom: 25px;
            color: var(--primary);
            font-size: 1.8rem;
        }

        .steps-container {
            display: flex;
            flex-direction: column;
            gap: 20px;
            max-width: 800px;
            margin: 0 auto;
        }

        .step {
            display: flex;
            align-items: flex-start;
            background-color: white;
            border-radius: var(--border-radius);
            padding: 20px;
            border-left: 5px solid var(--primary);
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.05);
            border: 1px solid #f0f0f0;
        }

        .step-number {
            background-color: var(--primary);
            color: white;
            width: 40px;
            height: 40px;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            font-size: 1.2rem;
            margin-right: 20px;
            flex-shrink: 0;
        }

        .step-content h3 {
            margin-bottom: 8px;
            color: #333;
        }

        .step-content p {
            color: #555;
        }

        .step-content code {
            background-color: #f5f5f5;
            padding: 3px 8px;
            border-radius: 4px;
            font-family: 'Courier New', monospace;
            font-size: 0.9rem;
            color: var(--primary);
            margin-top: 5px;
            display: inline-block;
            border: 1px solid #e0e0e0;
        }

        .footer-note {
            text-align: center;
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eaeaea;
            font-size: 0.9rem;
            color: #666;
        }

        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        @keyframes float {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }

        @media (max-width: 768px) {
            .success-header {
                padding: 30px 20px;
            }
            
            .success-title {
                font-size: 2.2rem;
            }
            
            .success-subtitle {
                font-size: 1rem;
            }
            
            .success-content {
                padding: 25px;
            }
            
            .installation-details {
                grid-template-columns: 1fr;
            }
            
            .action-grid {
                grid-template-columns: 1fr;
            }
            
            .step {
                flex-direction: column;
            }
            
            .step-number {
                margin-right: 0;
                margin-bottom: 15px;
            }
        }

        /* Additional light mode specific styles */
        .detail-content strong {
            color: #333;
        }

        a {
            color: var(--primary);
            text-decoration: none;
        }

        a:hover {
            text-decoration: underline;
        }

        .action-card .action-icon {
            box-shadow: 0 4px 10px rgba(111, 66, 193, 0.15);
        }

        .detail-card .detail-header {
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }
    </style>
</head>
<body>
    <div class="success-container">
        <div class="success-header">
            <div class="success-icon">
                <i class="fas fa-check-circle"></i>
            </div>
            <h1 class="success-title">Installation Complete!</h1>
            <p class="success-subtitle">
                Machinjiri has been successfully installed and configured. 
                You're now ready to start building amazing applications.
            </p>
            
        </div>
        
        <div class="success-content">
            <div class="installation-details">
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-code"></i>
                        <h3>Application Details</h3>
                    </div>
                    <div class="detail-content">
                        <p><strong>Name:</strong> $appName; </p>
                        <p><strong>Version:</strong> $version</p>
                        <p><strong>Release:</strong> Stable (LTS)</p>
                        <p><strong>Date:</strong> $date</p>
                        <div class="status-badge">
                            <i class="fas fa-check"></i> Verified & Authenticated
                        </div>
                    </div>
                </div>
                
                <div class="detail-card">
                    <div class="detail-header">
                        <i class="fas fa-cogs"></i>
                        <h3>Components Installed</h3>
                    </div>
                    <div class="detail-content">
                        <ul>
                            <li>Core Framework</li>
                            <li>Database ORM</li>
                            <li>Authentication System</li>
                            <li>API Toolkit</li>
                            <li>CLI Tools</li>
                            <li>Development Server</li>
                            <li>Testing Suite</li>
                        </ul>
                    </div>
                </div>
            </div>
            
            <div class="next-steps">
                <h2>Next Steps</h2>
                <div class="steps-container">
                    
                    <div class="step">
                        <div class="step-number">1</div>
                        <div class="step-content">
                            <h3>Explore Examples</h3>
                            <p>Check out sample projects and tutorials in the documentation to learn faster. The examples cover common use cases:</p>
                            <a href="$docsUrl" style="color: var(--primary); font-weight: 600;">View Examples →</a>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="footer-note">
                <p>
                    <i class="fas fa-info-circle"></i> 
                    Having issues? Visit our <a href="$docsUrl" style="color: var(--primary); font-weight: 600;">troubleshooting guide</a> or 
                    <a href="$docsForumUrl" style="color: var(--primary); font-weight: 600;">community forums</a> for help.
                </p>
                <p style="margin-top: 10px;">
                    Machinjiri $version •  
                    <i class="fas fa-heart" style="color: #e74c3c;"></i> Thank you for choosing Machinjiri
                </p><a href="$docsUrl" target="_blank" class="action-btn secondary">
                            Open Docs
                        </a>
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

$application = new Terminal();
$application->run();
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
<phpunit colors="true" bootstrap="vendor/autoload.php">
  <testsuites>
    <testsuite name="Unit Tests">
      <directory>tests/Unit</directory>
    </testsuite>
  </testsuites>
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

        // Register aliases for easier access
        $this->aliasMany([
            'request' => HttpRequest::class,
            'response' => HttpResponse::class,
            'session' => Session::class,
            'cookie' => Cookie::class,
            'db' => 'db.connection',
        ]);

        // Register event listeners
        $this->listenMany([
            'app.booted' => function() {
                // Log application boot
            },
            'db.connected' => function($driver) {
                // Log database connection
            },
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

        // Register core middleware
        $this->registerMiddleware([
            'session' => \Mlangeni\Machinjiri\App\Http\Middleware\StartSession::class,
            'auth' => \Mlangeni\Machinjiri\App\Http\Middleware\Authenticate::class,
            'csrf' => \Mlangeni\Machinjiri\App\Http\Middleware\VerifyCsrfToken::class,
        ]);

        // Load application routes
        $routesDir = $this->app->routes;
        if (is_dir($routesDir)) {
            $this->loadRoutesFrom($routesDir . 'web.php');
            $this->loadRoutesFrom($routesDir . 'api.php');
        }

        // Trigger app booted event
        $this->triggerEvent('app.booted');
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
    {
        /**
         * Run Migrations first before seeding the database and running factories
        */
        try {
          $this->migrate();
        } catch (MachinjiriException $e) {
          (new Logger('db-migration'))->critical($e->getMessage());
        }
        
        try {
          $result = $this->runFactories();
          foreach ($result as $key => $factory) {
            if (isset($factory['status']) && $factory['status'] == 'error') {
              (new Logger('db-factory'))->warning(
                "Unable to run factory <" . $key . "> due to: " .
                $factory['error']);
            }
          }
        } catch (MachinjiriException $e) {
          (new Logger('db-factory'))->critical($e->getMessage());
        }
        
        try {
          $seed = $this->seedDatabase();
          foreach ($seed as $key => $seeder) {
            if (isset($seeder['status']) && $seeder['status'] == 'error') {
              (new Logger('db-seeder'))->warning(
                "Unable to run seeder <" . $seeder['seeder'] . "> due to: " .
                $seeder['error']);
            }
          }
        } catch (MachinjiriException $e) {
          (new Logger('db-seeder'))->critical($e->getMessage());
        }
        
    }
    
    public function migrate (): void 
    {
      $handler = $this->resolve('migration.manager');
      $handler->migrate();
    }


    /**
     * Run seeders
     */
    public function seedDatabase(array $seeders = [], bool $inTransaction = false): array
    {
        try {
            $seederManager = new SeederManager($this->app);
            
            if ($inTransaction) {
                return $seederManager->runAllInTransaction();
            } elseif (count($seeders) == 0) {
                return $seederManager->runAll();
            } else {
                $results = [];
                foreach ($seeders as $seeder) {
                    $results[] = $seederManager->run($seeder);
                }
                return $results;
            }
        } catch (\Exception $e) {
            throw new MachinjiriException(
                "Failed to seed database: " . $e->getMessage(),
                500,
                $e
            );
        }
    }

    /**
     * Run factories
     */
    public function runFactories(array $definitions = []): array
    {
        try {
            $factoryManager = $this->resolve('db.factory');
            
            if (empty($definitions)) {
                // Run all factories once
                $allModels = $factoryManager->list();
                $definitions = [];
                foreach ($allModels as $factory) {
                    $definitions[$factory['model']] = 1; // Run each factory once
                }
            }
            
            return $factoryManager->runMultiple($definitions);
        } catch (\Exception $e) {
            throw new MachinjiriException(
                "Failed to run factories: " . $e->getMessage(),
                500,
                $e
            );
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
    'name' => getenv('APP_NAME', 'Machinjiri App'),

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
    'env' => getenv('APP_ENV', 'production'),

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
    'debug' => getenv('APP_DEBUG', false),

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
    'url' => getenv('APP_URL', 'http://localhost'),

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
    'key' => getenv('APP_KEY', ''),
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
        'default' => getenv('DB_CONNECTION', 'sqlite'),
        'connections' => [
            'sqlite' => [
                'driver' => 'sqlite',
                'database' => getenv('DB_DATABASE', getenv('DB_DATABASE')),
                'prefix' => '',
                'foreign_key_constraints' => getenv('DB_FOREIGN_KEYS', true),
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
        'default' => getenv('CACHE_DRIVER', 'file'),
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => BASE . 'storage/cache',
            ],
            // Add other cache stores as needed
        ],
        'prefix' => getenv('CACHE_PREFIX', 'machinjiri_cache'),
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
        'driver' => getenv('SESSION_DRIVER', 'file'),
        'lifetime' => getenv('SESSION_LIFETIME', 120),
        'expire_on_close' => false,
        'encrypt' => false,
        'files' => BASE . '/storage/session',
        'connection' => null,
        'table' => 'sessions',
        'store' => null,
        'lottery' => [2, 100],
        'cookie' => getenv(
            'SESSION_COOKIE',
            'machinjiri_session'
        ),
        'path' => '/',
        'domain' => getenv('SESSION_DOMAIN'),
        'secure' => getenv('SESSION_SECURE_COOKIE', false),
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
        'default' => getenv('MAIL_MAILER', 'smtp'),
        'mailers' => [
            'smtp' => [
                'transport' => 'smtp',
                'host' => getenv('MAIL_HOST', 'smtp.mailtrap.io'),
                'port' => getenv('MAIL_PORT', 2525),
                'encryption' => getenv('MAIL_ENCRYPTION', 'tls'),
                'username' => getenv('MAIL_USERNAME'),
                'password' => getenv('MAIL_PASSWORD'),
                'timeout' => null,
                'auth_mode' => null,
            ],
            // Add other mailers as needed
        ],
        'from' => [
            'address' => getenv('MAIL_FROM_ADDRESS', 'hello@example.com'),
            'name' => getenv('MAIL_FROM_NAME', 'Example'),
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
    'encryption_key' => getenv('APP_KEY', ''),
    'encryption_cipher' => getenv('APP_CIPHER', 'aes-256-gcm'),
    'jwt_secret' => getenv('JWT_SECRET', ''),
    'jwt_algo' => getenv('JWT_ALGO', 'HS256'),
    'jwt_expiration' => getenv('JWT_EXPIRATION', 3600),
    'jwt_issuer' => getenv('JWT_ISSUER', 'machinjiri'),
    'jwt_audience' => getenv('JWT_AUDIENCE', 'machinjiri_api'),
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
  
  private function helpersFileTemplate () {return <<<'PHP'
<?php
/**
 * Global Helper Functions for Machinjiri Framework
 * 
 * This file provides global helper functions for easy access to application services.
 */

use Mlangeni\Machinjiri\Core\Container;
use Mlangeni\Machinjiri\Core\Exceptions\MachinjiriException;
use Mlangeni\Machinjiri\Core\Artisans\Logging\Logger;

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
PHP;
  }
    
}