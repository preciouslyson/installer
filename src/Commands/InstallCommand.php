<?php

namespace Preciouslyson\MachinjiriInstaller\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Helper\ProgressBar;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Preciouslyson\MachinjiriInstaller\Installer;
use Preciouslyson\MachinjiriInstaller\InstallationSummary;

class InstallCommand extends Command
{
    protected static $defaultName = 'new';
    protected static $defaultDescription = 'Create a new Machinjiri application';

    public function __construct()
    {
        parent::__construct(self::$defaultName);
        $this->setDescription(self::$defaultDescription);
    }

    protected function configure(): void
    {
        $this
            ->setName(self::$defaultName)
            ->setDescription(self::$defaultDescription)
            ->addArgument('name', InputArgument::OPTIONAL, 'Name of the project directory', 'machinjiri-app')
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force installation even if the directory already exists')
            ->addOption('m-version', null, InputOption::VALUE_REQUIRED, 'Machinjiri version to install', '*')
            ->addOption('dev', null, InputOption::VALUE_NONE, 'Install development dependencies')
            ->addOption('no-dev', null, InputOption::VALUE_NONE, 'Skip development dependencies')
            ->addOption('no-scripts', null, InputOption::VALUE_NONE, 'Skip Composer scripts')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Perform a dry-run without actually creating files');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $projectName = $input->getArgument('name');
        $isDryRun = $input->getOption('dry-run');
        $isVerbose = $input->getOption('verbose');
        
        $options = [
            'force' => $input->getOption('force'),
            'version' => $input->getOption('m-version'),
            'no-interaction' => $input->getOption('no-interaction'),
            'dev' => $input->getOption('dev'),
            'no-dev' => $input->getOption('no-dev'),
            'no-scripts' => $input->getOption('no-scripts'),
            'dry-run' => $isDryRun,
            'verbose' => $isVerbose,
        ];

        try {
            if ($isDryRun) {
                $io->warning('Running in DRY-RUN mode. No files will be created.');
                $io->newLine();
            }
            
            $progressBar = new ProgressBar($output, 11); // 11 total steps
            $progressBar->setFormat(' %current%/%max% [%bar%] %percent:3s%% %message%');
            $progressBar->setMessage('Starting installation...');
            $progressBar->start();
            
            $installer = new Installer($io, $isVerbose);
            
            $installer->setProgressCallback(function($step, $message) use ($progressBar) {
                $progressBar->setMessage($message);
                $progressBar->advance();
            });
            
            $installer->install($projectName, $options);

            $progressBar->finish();
            
            $output->writeln("\n");
            
            if ($isDryRun) {
                $io->success("Dry-run completed successfully!");
                $io->note("Run without --dry-run to create the project.");
            } else {
                $io->success("Machinjiri installed successfully!");
                
                // Display installation summary
                $summary = new InstallationSummary(
                    getcwd() . DIRECTORY_SEPARATOR . $projectName,
                    $projectName,
                    $io
                );
                $summary->displayComplete();
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            
            if (isset($progressBar)) {
                $progressBar->clear();
            }
            $io->error($e->getMessage());
            
            if ($isVerbose) {
                $io->note("Full stack trace:");
                $io->writeln($e->getTraceAsString());
            }
            
            return Command::FAILURE;
            
        }
    }
}