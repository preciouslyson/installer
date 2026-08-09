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
use Mlangeni\Machinjiri\Installer\InstallationManager;
use Mlangeni\Machinjiri\Installer\Installer;
use Mlangeni\Machinjiri\Installer\InstallerLogger;
use Mlangeni\Machinjiri\Installer\InstallationSummary;
use Mlangeni\Machinjiri\Installer\StarterkitManager;
use Mlangeni\Machinjiri\Installer\VersionManager;


class InstallCommand extends Command
{
    protected static $defaultName = 'create';
    protected static $defaultDescription = 'Create a new Machinjiri application';

    private bool $bannerDisplayed = false;

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
            ->addOption('database', null, InputOption::VALUE_REQUIRED, 'Database to use')
            ->addOption('description', null, InputOption::VALUE_REQUIRED, 'Project description')
            ->addOption('company', null, InputOption::VALUE_REQUIRED, 'Company/Organization name')
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
            $projectName = $io->ask('Project Name', 'machinjiri-app', function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Project name cannot be empty.');
                }
                if (!preg_match('/^[a-zA-Z0-9._-]+$/', $value)) {
                    throw new \RuntimeException('Project name may only contain letters, numbers, dots, underscores, and hyphens.');
                }
                if (strlen($value) < 3) {
                    throw new \RuntimeException('Enter a valid project name');
                }
                return $value;
            });
        } elseif (!$projectName) {
            $projectName = 'machinjiri-app';
        }

        $description = $input->getOption('description');
        if (!$description && !$noInteraction) {
            $description = $io->ask('Description. Describe your project', '', function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Project description cannot be empty.');
                }
                return $value;
            });
        }

        $company = $input->getOption('company');
        if (!$company && !$noInteraction) {
            $company = $io->ask('Company/Organization name:', '', function ($value) {
                if (empty(trim($value))) {
                    throw new \RuntimeException('Company/Organization cannot be empty.');
                }
                return $value;
            });
        }

        $version = $input->getOption('m-version');
        if (!$version && !$noInteraction) {
            $installable = VersionManager::installable()['installable'];
            if (count($installable) > 1) {
                $userChoice = $io->choice("Select a Machinjiri version to install ", $installable, 0);
                if (!in_array($userChoice, $installable)) throw new \RuntimeException("Invalid version selected!");
                $version = $userChoice;
            } else {
                $version = VersionManager::installable()['minimum'];
            }
            
        }

        // Database
        $database = $input->getOption('database');
        if (!$database && !$noInteraction) {
            $database = $io->choice('What database would you prefer to use?', ['sqlite' => 'SQLite Database', 'mysql' => 'MYSQL Database'], 'sqlite');
        } elseif (!$database) {
            $database = 'sqlite';
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
        $logger = new InstallerLogger($targetDir, $io, $isVerbose);
        $manager = new InstallationManager($targetDir);

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
        $io->section('Installation Settings');
        $io->listing([
            "Project Name: <info>{$projectName}</info>",
            "Project Description: <info>{$description}</info>",
            "Company/Organization: <info>{$company}</info>",
            "Machinjiri version: <info>{$version}</info>",
            "Install dev dependencies: <info>" . ($installDev ? 'Yes' : 'No') . "</info>",
            "Starter kit: <info>{$starter}</info>",
            "Initialize Git: <info>" . ($initGit ? 'Yes' : 'No') . "</info>",
            "Composer cache: <info>" . ($preferCache ? 'Prefer Cache' : 'Default') . "</info>",
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
            'database' => $database,
            'description' => $description,
            'company' => $company,
        ];

        try {
            if ($isDryRun) {
                $io->warning('Running in DRY-RUN mode. No files will be created.');
                $io->newLine();
            }

            $io->writeln("\n<comment>Installing Machinjiri. Please wait..</comment>");
            $spinner = $this->createSpinner($output);

            $manager->registerStep('Install project', function () use ($io, $output, $projectName, $options, $spinner, $isDryRun, $isVerbose, $targetDir, $logger): void {
                $spinner->start();

                $installer = new Installer($io, $isVerbose);
                $installer->setProgressCallback(function ($step, $message) use ($spinner): void {
                    $spinner->setMessage($step . " - " . $message);
                });

                $logger->info('Starting project scaffolding');
                $installer->install($projectName, $options);
                $spinner->finish();
                $io->newLine(2);

                if ($isDryRun) {
                    $io->success("Dry-run completed successfully!");
                    $io->note("Run without --dry-run to create the project.");
                    return;
                }

                $logger->success('Project scaffolding completed');
            }, function () use ($targetDir, $logger): void {
                if (is_dir($targetDir)) {
                    InstallationManager::removeDirectory($targetDir);
                }
                $logger->warning('Rolled back partially created project files.');
            });

            $manager->executeAll();

            if ($isDryRun) {
                return Command::SUCCESS;
            }

            // Post-installation Git init
            if ($initGit) {
                $this->initializeGit($targetDir, $io);
            }

            $io->success($projectName . " created successfully");

            $summary = new InstallationSummary($targetDir, $projectName, $io);
            
            $nextSteps = $io->confirm('Take a little tour?', false);
            if ($nextSteps) {
                $summary->displayQuickStart();
                $summary->displayNextSteps();
                $summary->displayImportantNotes();
                $summary->displayResources();
                $summary->display();
            } else {
                $summary->display();
                $summary->displayQuickStart();
            }

            $logger->displaySummary();
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $manager->rollback();
            if (!$isDryRun && !$input->getOption('keep-on-error') && is_dir($targetDir)) {
                $io->warning("Installation failed. Removing partially created directory...");
                $this->removeDirectory($targetDir);
            }
            $logger->error($e->getMessage(), $e);
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

        // Installer version
        $io->writeln("Installer version: <info>" . VersionManager::INSTALLER_VERSION . "</info>");

        // PHP version
        if (version_compare(PHP_VERSION, VersionManager::RECOMMENDED_PHP_VERSION, '<')) {
            $io->error("Machinjiri requires PHP" . VersionManager::RECOMMENDED_PHP_VERSION . " or higher. You have " . PHP_VERSION);
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
        $bannerText = <<<ASCII
███╗   ███╗ █████╗  ██████╗██╗  ██╗██╗███╗   ██╗     ██╗██╗██████╗  ██╗
████╗ ████║██╔══██╗██╔════╝██║  ██║██║████╗  ██║     ██║██║██╔══██╗ ██║
██╔████╔██║███████║██║     ███████║██║██╔██╗ ██║     ██║██║██████╔╝ ██║
██║╚██╔╝██║██╔══██║██║     ██╔══██║██║██║╚██╗██║██   ██║██║██╔══██╗ ██║
██║ ╚═╝ ██║██║  ██║╚██████╗██║  ██║██║██║ ╚████║╚█████╔╝██║██║  ██║ ██║
╚═╝     ╚═╝╚═╝  ╚═╝ ╚═════╝╚═╝  ╚═╝╚═╝╚═╝  ╚═══╝ ╚════╝ ╚═╝╚═╝  ╚═╝ ╚═╝
ASCII;
        $io->writeln('');
        $io->writeln('<fg=cyan;>' . $bannerText . '</>');
        $io->writeln('');
        $io->writeln('<fg=white>The Cozy PHP Framework — where code meets comfort</>');
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
        $minimum = VersionManager::installable()['minimum'];
        if ($latest === null) {
            return $minimum;
        }

        $minimum = (int) str_replace('^', '', $minimum);

        if ($latest < $minimum) throw new \RuntimeException("Minimum installable version error");
        
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
            //$this->logger->warning("No stable version found, using latest: {$latest}");
            return $latest;
        }
        usort($stableVersions, 'version_compare');
        return end($stableVersions);
    }
}