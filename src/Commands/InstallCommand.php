<?php

namespace Preciouslyson\MachinjiriInstaller\Commands;

use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Process\Process;
use Preciouslyson\MachinjiriInstaller\Installer;

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
            ->addOption('no-interaction', 'n', InputOption::VALUE_NONE, 'Do not ask any interactive question');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $projectName = $input->getArgument('name');
        $options = [
            'force' => $input->getOption('force'),
            'version' => $input->getOption('m-version'),
            'no-interaction' => $input->getOption('no-interaction'),
            'dev' => $input->getOption('dev'),
            'no-dev' => $input->getOption('no-dev'),
            'no-scripts' => $input->getOption('no-scripts'),
        ];

        try {
            $installer = new Installer($io);
            $installer->install($projectName, $options);
            
            $io->success("Machinjiri application installed successfully!");
            $io->text("Next steps:");
            $io->listing([
                "cd $projectName",
                "php artisan server:start",
                "Visit http://localhost:3000 in your browser",
            ]);
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}