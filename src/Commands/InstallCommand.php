<?php

namespace Mlangeni\Machinjiri\Installer\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Mlangeni\Machinjiri\Installer\Installer;
use Mlangeni\Machinjiri\Installer\InstallationSummary;
use Mlangeni\Machinjiri\Installer\StarterkitManager;

class InstallCommand extends Command
{
    protected static $defaultName = 'create';
    protected static $defaultDescription = 'Create a new Machinjiri application';

    private bool $bannerDisplayed = false;
    
    private ?string $resolvedFrameworkVersion = null;

    public function __construct()
    {
        parent::__construct(self::$defaultName);
        $this->setDescription(self::$defaultDescription);
        $this->setAliases(['new']);
    }

    protected function configure(): void
    {
        $this
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the project directory')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force installation even if the directory already exists')
            ->addOption('m-version', null, InputOption::VALUE_REQUIRED, 'Machinjiri version to install')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Install development dependencies')
            ->addOption('no-dev', null, InputOption::VALUE_NONE, 'Skip development dependencies')
            ->addOption('no-scripts', null, InputOption::VALUE_NONE, 'Skip Composer scripts')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Perform a dry-run without actually creating files')
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask any interactive questions')
            ->addOption('git', null, InputOption::VALUE_NONE, 'Initialize Git repository and make initial commit')
            ->addOption('starter', null, InputOption::VALUE_REQUIRED, 'Starter kit (default)')
            ->addOption('prefer-cache', null, InputOption::VALUE_NONE, 'Use Composer cache if available')
            ->addOption('keep-on-error', null, InputOption::VALUE_NONE, 'Do not delete partially created project on failure');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $isVerbose = $input->getOption('verbose');
        $noInteraction = $input->getOption('no-interaction');

        $this->displayBanner($io);

        if (!$this->checkEnvironment($io)) {
            return Command::FAILURE;
        }

        $projectName = $input->getArgument('name');
        if (!$projectName && !$noInteraction) {
            $projectName = $io->ask('Project name', 'machinjiri-app', function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Project name cannot be empty.');
                }
                if (!preg_match('/^[a-zA-Z0-9._-]+$/', $value)) {
                    throw new \RuntimeException('Project name may only contain letters, numbers, dots, underscores, and hyphens.');
                }
                return $value;
            });
        } elseif (!$projectName) {
            $projectName = 'machinjiri-app';
        }

        $version = $input->getOption('m-version');
        if (!$version && !$noInteraction) {
            $version = $io->ask('Machinjiri version (leave empty for latest)', '*');
            if (empty($version) || $version === '*') {
                $version = $this->resolveFrameworkVersion('*');
            }
        } elseif (!$version) {
            $version = $this->resolveFrameworkVersion('*');
        }

        // Dev/No-dev
        $installDev = null;
        if ($input->getOption('dev')) {
            $installDev = true;
        } elseif ($input->getOption('no-dev')) {
            $installDev = false;
        } elseif (!$noInteraction) {
            $choice = $io->choice('Install development dependencies?', ['dev' => 'Yes (recommended for development)', 'no-dev' => 'No (production only)'], 'dev');
            $installDev = ($choice === 'dev');
        } else {
            $installDev = true;
        }

        // Starter kit
        $starter = $input->getOption('starter');
        if (!$starter && !$noInteraction) {
            $starter = $io->choice('Select a starter kit', StarterkitManager::startKits(), 'default');
        } elseif (!$starter) {
            $starter = 'default';
        }

        // Git initialization
        $initGit = $input->getOption('git');
        if (!$initGit && !$noInteraction && !$isDryRun) {
            $initGit = $io->confirm('Initialize a Git repository and make initial commit?', false);
        }

        // Prefer Composer cache
        $preferCache = $input->getOption('prefer-cache');

        $targetDir = getcwd() . DIRECTORY_SEPARATOR . $projectName;

        // Handle existing directory
        if (!$input->getOption('force') && !$isDryRun && is_dir($targetDir)) {
            if (!$noInteraction) {
                $overwrite = $io->confirm("Directory '$projectName' already exists. Overwrite?", false);
                if (!$overwrite) {
                    $io->error('Installation aborted by user.');
                    return Command::FAILURE;
                }
                $input->setOption('force', true);
            } else {
                $io->error("Directory '$projectName' already exists. Use --force to overwrite.");
                return Command::FAILURE;
            }
        }

        // Installation summary
        $io->section('Installation settings');
        $io->listing([
            "Project name: <info>{$projectName}</info>",
            "Machinjiri version: <info>{$version}</info>",
            "Development dependencies: <info>" . ($installDev ? 'Yes' : 'No') . "</info>",
            "Starter kit: <info>{$starter}</info>",
            "Git init: <info>" . ($initGit ? 'Yes' : 'No') . "</info>",
            "Composer cache: <info>" . ($preferCache ? 'Prefer cache' : 'Default') . "</info>",
            "Target directory: <info>{$targetDir}</info>",
        ]);

        if (!$noInteraction && !$isDryRun) {
            if (!$io->confirm('Proceed with installation?', true)) {
                return Command::SUCCESS;
            }
        }

        // Prepare options for Installer
        $options = [
            'force' => $input->getOption('force'),
            'version' => $version,
            'no-interaction' => $noInteraction,
            'dev' => $installDev,
            'no-dev' => !$installDev,
            'no-scripts' => $input->getOption('no-scripts'),
            'dry-run' => $isDryRun,
            'verbose' => $isVerbose,
            'starter' => $starter,
            'prefer-cache' => $preferCache,
        ];

        try {
            if ($isDryRun) {
                $io->warning('Running in DRY-RUN mode. No files will be created.');
                $io->newLine();
            }

            $io->writeln("\n<comment>Installing Machinjiri...</comment>");
            $spinner = $this->createSpinner($output);
            $spinner->start();

            $installer = new Installer($io, $isVerbose);
            
            $installer->setProgressCallback(function ($step, $message) use ($spinner) {
                $spinner->setMessage($step . " - " . $message);
            });

            $installer->install($projectName, $options);

            $spinner->finish();
            $io->newLine(2);

            if ($isDryRun) {
                $io->success("Dry-run completed successfully!");
                $io->note("Run without --dry-run to create the project.");
                return Command::SUCCESS;
            }

            // Post-installation Git init
            if ($initGit) {
                $this->initializeGit($targetDir, $io);
            }

            // Post-installation wizard
            $this->runPostInstallWizard($targetDir, $io, $noInteraction);

            $io->success("Machinjiri installed successfully!");

            $summary = new InstallationSummary($targetDir, $projectName, $io);
            $summary->displayComplete();

            return Command::SUCCESS;
        } catch (\Exception $e) {
            if (!$isDryRun && !$input->getOption('keep-on-error') && is_dir($targetDir)) {
                $io->warning("Installation failed. Removing partially created directory...");
                $this->removeDirectory($targetDir);
            }
            $io->error($e->getMessage());
            if ($isVerbose) {
                $io->note("Full stack trace:");
                $io->writeln($e->getTraceAsString());
            }
            return Command::FAILURE;
        }
    }

    private function checkEnvironment(SymfonyStyle $io): bool
    {
        $io->section('Environment check');

        // PHP version
        if (version_compare(PHP_VERSION, '8.2.0', '<')) {
            $io->error("Machinjiri requires PHP 8.2.0 or higher. You have " . PHP_VERSION);
            return false;
        }
        $io->writeln("PHP version: <info>" . PHP_VERSION . "</info>");

        // Required extensions
        $required = ['json', 'mbstring', 'zip', 'openssl', 'pdo', 'tokenizer', 'ctype'];
        $missing = [];
        foreach ($required as $ext) {
            if (!extension_loaded($ext)) {
                $missing[] = $ext;
            }
        }
        if (!empty($missing)) {
            $io->error("Missing required PHP extensions: " . implode(', ', $missing));
            return false;
        }
        $io->writeln("Required extensions: <info>all present</info>");

        $process = new Process(['composer', '--version']);
        $process->run();
        if (!$process->isSuccessful()) {
            $io->error("Composer is not available. Please install Composer first.");
            return false;
        }
        $io->writeln("Composer: <info>" . trim($process->getOutput()) . "</info>");

        $io->newLine();
        return true;
    }

    private function createSpinner(OutputInterface $output): ProgressBar
    {
        $spinner = new ProgressBar($output);
        $spinner->setFormat(' [%bar%] %message%');
        $spinner->setBarCharacter('<fg=green>⚬</>');
        $spinner->setEmptyBarCharacter(' ');
        $spinner->setProgressCharacter('➤');
        $spinner->setMessage('Starting...');
        $spinner->start();
        return $spinner;
    }

    private function initializeGit(string $targetDir, SymfonyStyle $io): void
    {
        $io->writeln("\n<comment>Initializing Git repository...</comment>");
        $process = new Process(['git', 'init'], $targetDir);
        $process->run();
        if (!$process->isSuccessful()) {
            $io->warning("Git init failed: " . $process->getErrorOutput());
            return;
        }
        // Create initial .gitignore if not present
        $gitignore = $targetDir . '/.gitignore';
        if (!file_exists($gitignore)) {
            file_put_contents($gitignore, "/vendor\n.env\n.DS_Store\n");
        }
        $process = new Process(['git', 'add', '.'], $targetDir);
        $process->run();
        $process = new Process(['git', 'commit', '-m', 'Initial commit from Machinjiri installer'], $targetDir);
        $process->run();
        $io->success("Git repository initialized with initial commit.");
    }

    private function runPostInstallWizard(string $targetDir, SymfonyStyle $io, bool $noInteraction): void
    {
        if ($noInteraction) {
            return;
        }
    
        $io->section('Post-installation setup');
        $wantsSetup = $io->confirm('Would you like to perform some post-installation setup?', true);
        if (!$wantsSetup) {
            return;
        }
        if ($io->confirm('Run database migrations?', false)) {
            $artisan = $targetDir . '/artisan';
            if (file_exists($artisan)) {
                $process = new Process(['php', $artisan, 'migration:migrate'], $targetDir);
                $process->setTimeout(300);
                $process->run(function ($type, $buffer) use ($io) {
                    $io->write($buffer);
                });
                if ($process->isSuccessful()) {
                    $io->success("Migrations completed.");
                } else {
                    $io->error("Migrations failed: " . $process->getErrorOutput());
                }
            } else {
                $io->warning("Artisan not found. Skipping migrations.");
            }
        }
    
        if ($io->confirm('Start the built-in PHP development server now?', false)) {
            $io->writeln("Starting server with <comment>php artisan server:start</comment>");
            $io->writeln("Press Ctrl+C to stop the server.");
            $process = new Process(['php', 'artisan', 'server:start'], $targetDir);
            $process->setTty(true);
            $process->run();
        }
    }

    private function removeDirectory(string $dir): void
    {
        if (!is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            $file->isDir() ? rmdir($file->getRealPath()) : unlink($file->getRealPath());
        }
        rmdir($dir);
    }

    private function displayBanner($io): void
    {
        if ($this->bannerDisplayed) {
            return;
        }
        $this->bannerDisplayed = true;
        $bigM = <<<ASCII
   ███╗   ███╗ █████╗  ██████╗██╗  ██╗██╗███╗   ██╗     ██╗██╗██████╗ 
   ████╗ ████║██╔══██╗██╔════╝██║  ██║██║████╗  ██║     ██║██║██╔══██╗
   ██╔████╔██║███████║██║     ███████║██║██╔██╗ ██║     ██║██║██████╔╝
   ██║╚██╔╝██║██╔══██║██║     ██╔══██║██║██║╚██╗██║██   ██║██║██╔══██╗
   ██║ ╚═╝ ██║██║  ██║╚██████╗██║  ██║██║██║ ╚████║╚█████╔╝██║██║  ██║
   ╚═╝     ╚═╝╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚═╝╚═╝  ╚═══╝ ╚════╝ ╚═╝╚═╝  ╚═╝
ASCII;
        $io->writeln('');
        $io->writeln('<fg=cyan;options=bold>' . $bigM . '</>');
        $io->writeln('');
        $io->writeln('     <fg=white>The Cozy PHP Framework — where code meets comfort</>');
        $io->writeln('');
    }
    
    private function resolveFrameworkVersion(string $requestedVersion): string
    {
        // If it's not a wildcard, return as is
        if ($requestedVersion !== '*' && strtolower($requestedVersion) !== 'latest') {
            return $requestedVersion;
        }
        
        // Fetch latest stable version from Packagist
        $latest = $this->fetchLatestFrameworkVersion();
        if ($latest === null) {
            return '*';
        }
        
        return '^' . $latest;
    }
    
    private function fetchLatestFrameworkVersion(): ?string
    {
        $url = 'https://packagist.org/packages/machinjiri/framework.json';
        $json = @file_get_contents($url);
      
        if ($json === false) {
            return null;
        }
        $data = json_decode($json, true);
        if (!isset($data['package']['name']) || $data['package']['name'] !== "machinjiri/framework") {
            return null;
        }
        $versions = array_keys($data['package']['versions']);
        $stableVersions = array_filter($versions, function ($version) {
            return preg_match('/^\d+\.\d+\.\d+$/', $version);
        });
        
        if (empty($stableVersions)) {
            usort($versions, 'version_compare');
            $latest = end($versions);
            $this->logger->warning("No stable version found, using latest: {$latest}");
            return $latest;
        }
        usort($stableVersions, 'version_compare');
        return end($stableVersions);
    }
}