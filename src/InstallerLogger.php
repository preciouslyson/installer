<?php

namespace Mlangeni\Machinjiri\Installer;

use Symfony\Component\Console\Style\SymfonyStyle;

class InstallerLogger
{
    private string $logFile;
    private SymfonyStyle $io;
    private array $logs = [];
    private bool $verbose;

    public function __construct(string $projectDir, SymfonyStyle $io, bool $verbose = false)
    {
        $this->logFile = $projectDir . DIRECTORY_SEPARATOR . '.installation.log';
        $this->io = $io;
        $this->verbose = $verbose;
    }

    /**
     * Log an info message
     * 
     * @param string $message
     * @return void
     */
    public function info(string $message): void
    {
        $this->log('INFO', $message);
        if ($this->verbose) {
            $this->io->writeln("<fg=blue>ℹ</> {$message}");
        }
    }

    /**
     * Log a success message
     * 
     * @param string $message
     * @return void
     */
    public function success(string $message): void
    {
        $this->log('SUCCESS', $message);
        if ($this->verbose) {
            $this->io->writeln("<fg=green>✓</> {$message}");
        }
    }

    /**
     * Log a warning message
     * 
     * @param string $message
     * @return void
     */
    public function warning(string $message): void
    {
        $this->log('WARNING', $message);
        $this->io->writeln("<fg=yellow>⚠</> {$message}");
    }

    /**
     * Log an error message
     * 
     * @param string $message
     * @param \Throwable|null $exception
     * @return void
     */
    public function error(string $message, ?\Throwable $exception = null): void
    {
        $this->log('ERROR', $message);
        $this->io->writeln("<fg=red>✗</> {$message}");

        if ($exception) {
            $this->logException($exception);
            if ($this->verbose) {
                $this->io->writeln("<fg=red>{$exception->getMessage()}</>");
            }
        }
    }

    /**
     * Log an exception
     * 
     * @param \Throwable $exception
     * @return void
     */
    public function logException(\Throwable $exception): void
    {
        $message = sprintf(
            "[%s] %s:%d - %s\nStacktrace:\n%s",
            get_class($exception),
            $exception->getFile(),
            $exception->getLine(),
            $exception->getMessage(),
            $exception->getTraceAsString()
        );

        $this->log('EXCEPTION', $message);
    }

    /**
     * Internal log method
     * 
     * @param string $level
     * @param string $message
     * @return void
     */
    private function log(string $level, string $message): void
    {
        $timestamp = date('Y-m-d H:i:s');
        $logEntry = "[{$timestamp}] [{$level}] {$message}";

        $this->logs[] = $logEntry;

        if (file_exists(dirname($this->logFile)) || mkdir(dirname($this->logFile), 0755, true)) {
            file_put_contents(
                $this->logFile,
                $logEntry . PHP_EOL,
                FILE_APPEND | LOCK_EX
            );
        }
    }

    /**
     * Get log file path
     * 
     * @return string
     */
    public function getLogFile(): string
    {
        return $this->logFile;
    }

    /**
     * Get all logged entries
     * 
     * @return array
     */
    public function getLogs(): array
    {
        return $this->logs;
    }

    /**
     * Clear logs
     * 
     * @return void
     */
    public function clear(): void
    {
        $this->logs = [];
        if (file_exists($this->logFile)) {
            @unlink($this->logFile);
        }
    }

    /**
     * Display installation summary with log file location
     * 
     * @return void
     */
    public function displaySummary(): void
    {
        if (file_exists($this->logFile)) {
            $this->io->writeln('');
            $this->io->note("Installation log saved to: {$this->logFile}");
        }
    }
}
