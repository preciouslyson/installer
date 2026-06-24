<?php

namespace Mlangeni\Machinjiri\Installer;

/**
 * Manages installation steps and provides rollback capability
 */
class InstallationManager
{
    private array $steps = [];
    private array $executedSteps = [];
    private string $projectDir;

    public function __construct(string $projectDir)
    {
        $this->projectDir = $projectDir;
    }

    /**
     * Register an installation step
     * 
     * @param string $name
     * @param callable $execute
     * @param callable|null $rollback
     * @return $this
     */
    public function registerStep(string $name, callable $execute, ?callable $rollback = null): self
    {
        $this->steps[$name] = [
            'execute' => $execute,
            'rollback' => $rollback,
            'executed' => false,
        ];

        return $this;
    }

    /**
     * Execute a specific step
     * 
     * @param string $name
     * @return bool
     * @throws \RuntimeException
     */
    public function executeStep(string $name): bool
    {
        if (!isset($this->steps[$name])) {
            throw new \RuntimeException("Installation step not found: {$name}");
        }

        $step = $this->steps[$name];

        try {
            call_user_func($step['execute']);
            $this->steps[$name]['executed'] = true;
            $this->executedSteps[] = $name;
            return true;
        } catch (\Exception $e) {
            throw new \RuntimeException("Installation step '{$name}' failed: " . $e->getMessage(), 0, $e);
        }
    }

    /**
     * Execute all registered steps in order
     * 
     * @return bool
     * @throws \RuntimeException
     */
    public function executeAll(): bool
    {
        foreach (array_keys($this->steps) as $stepName) {
            try {
                $this->executeStep($stepName);
            } catch (\Exception $e) {
                $this->rollback();
                throw $e;
            }
        }

        return true;
    }

    /**
     * Rollback all executed steps in reverse order
     * 
     * @return bool
     */
    public function rollback(): bool
    {
        $stepsToRollback = array_reverse($this->executedSteps);

        foreach ($stepsToRollback as $stepName) {
            $step = $this->steps[$stepName];

            if ($step['rollback']) {
                try {
                    call_user_func($step['rollback']);
                } catch (\Exception $e) {
                    // Log rollback errors but don't stop the process
                    error_log("Rollback error for step '{$stepName}': " . $e->getMessage());
                }
            }
        }

        return true;
    }

    /**
     * Get list of executed steps
     * 
     * @return array
     */
    public function getExecutedSteps(): array
    {
        return $this->executedSteps;
    }

    /**
     * Get total number of steps
     * 
     * @return int
     */
    public function getTotalSteps(): int
    {
        return count($this->steps);
    }

    /**
     * Remove a directory recursively
     * 
     * @param string $dir
     * @return bool
     */
    public static function removeDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . DIRECTORY_SEPARATOR . $file;
            is_dir($path) ? self::removeDirectory($path) : @unlink($path);
        }

        return @rmdir($dir);
    }

    /**
     * Create a rollback handler for directory creation
     * 
     * @param string $dir
     * @return callable
     */
    public static function createDirectoryRollback(string $dir): callable
    {
        return function () use ($dir) {
            self::removeDirectory($dir);
        };
    }

    /**
     * Create a rollback handler for file creation
     * 
     * @param string $file
     * @return callable
     */
    public static function createFileRollback(string $file): callable
    {
        return function () use ($file) {
            if (file_exists($file)) {
                @unlink($file);
            }
        };
    }
}
