<?php

namespace Preciouslyson\MachinjiriInstaller;

use Symfony\Component\Console\Style\SymfonyStyle;

class InstallationSummary
{
    private string $projectDir;
    private string $projectName;
    private SymfonyStyle $io;
    private array $details = [];

    public function __construct(string $projectDir, string $projectName, SymfonyStyle $io)
    {
        $this->projectDir = $projectDir;
        $this->projectName = $projectName;
        $this->io = $io;
        $this->gatherDetails();
    }

    /**
     * Gather installation details
     * 
     * @return void
     */
    private function gatherDetails(): void
    {
        $this->details = [
            'Project Name' => $this->projectName,
            'Installation Path' => $this->projectDir,
            'PHP Version' => PHP_VERSION,
            'Installation Date' => date('Y-m-d H:i:s'),
        ];

        // Count created files and directories
        if (is_dir($this->projectDir)) {
            $this->details['Directories Created'] = $this->countDirectories($this->projectDir);
            $this->details['Files Created'] = $this->countFiles($this->projectDir);
        }
    }

    /**
     * Count directories recursively
     * 
     * @param string $dir
     * @return int
     */
    private function countDirectories(string $dir): int
    {
        $count = 0;
        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_dir($path)) {
                $count++;
                $count += $this->countDirectories($path);
            }
        }

        return $count;
    }

    /**
     * Count files recursively
     * 
     * @param string $dir
     * @return int
     */
    private function countFiles(string $dir): int
    {
        $count = 0;
        $items = array_diff(scandir($dir), ['.', '..']);

        foreach ($items as $item) {
            $path = $dir . DIRECTORY_SEPARATOR . $item;
            if (is_file($path)) {
                $count++;
            } elseif (is_dir($path)) {
                $count += $this->countFiles($path);
            }
        }

        return $count;
    }

    /**
     * Display the installation summary
     * 
     * @return void
     */
    public function display(): void
    {
        $this->io->section('Installation Summary');

        // Display details
        foreach ($this->details as $key => $value) {
            $this->io->writeln("<fg=cyan>{$key}:</> <fg=white>{$value}</>");
        }

        $this->io->newLine();
    }

    /**
     * Display quick start guide
     * 
     * @return void
     */
    public function displayQuickStart(): void
    {
        $this->io->section('Quick Start Guide');

        $this->io->writeln('1. Navigate to your project:');
        $this->io->writeln("   <fg=yellow>cd {$this->projectName}</>");

        $this->io->newLine();
        $this->io->writeln('2. Start the development server (if not already started:');
        $this->io->writeln('   <fg=yellow>php artisan server:start</>');

        $this->io->newLine();
        $this->io->writeln('3. Open your browser and visit:');
        $this->io->writeln('   <fg=yellow>http://localhost:3000</>');

        $this->io->newLine();
    }

    /**
     * Display important information about the installation
     * 
     * @return void
     */
    public function displayImportantNotes(): void
    {
        $this->io->section('Important Notes');

        $this->io->writeln('<fg=yellow>Security:</> The .env file has been created with restricted permissions. Keep it safe!');
        $this->io->writeln('<fg=yellow>Environment:</> Update .env file with your database and mail credentials.');
        $this->io->writeln('<fg=yellow>Dependencies:</> All Composer dependencies have been installed automatically.');
        $this->io->writeln('<fg=yellow>Migrations:</> Database migration files are in database/migrations/.');

        $this->io->newLine();
    }

    /**
     * Display recommended next steps
     * 
     * @return void
     */
    public function displayNextSteps(): void
    {
        $this->io->section('Next Steps');

        $steps = [
            'Review and update your .env file with database credentials',
            'Run database migrations: php artisan migrate',
            'Create your first controller: php artisan make:controller YourController',
            'Define routes in routes/web.php',
            'Build your application features',
            'Run tests: php artisan test',
        ];

        $this->io->listing($steps);
        $this->io->newLine();
    }

    /**
     * Display helpful resources
     * 
     * @return void
     */
    public function displayResources(): void
    {
        $this->io->section('Helpful Resources');

        $resources = [
            'Documentation' => 'https://github.com/preciouslyson/machinjiri#Introduction',
        ];

        foreach ($resources as $name => $url) {
            if (!empty($url)) {
                $this->io->writeln("<fg=cyan>{$name}:</> <fg=blue;href={$url}>{$url}</>");
            }
        }

        $this->io->newLine();
    }

    /**
     * Display complete installation report
     * 
     * @return void
     */
    public function displayComplete(): void
    {
        $this->display();
        $this->displayQuickStart();
        $this->displayImportantNotes();
        $this->displayNextSteps();
        $this->displayResources();
    }
}
