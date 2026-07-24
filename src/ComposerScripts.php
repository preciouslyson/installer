<?php

namespace Mlangeni\Machinjiri\Installer;

use Symfony\Component\Console\Application;
use Mlangeni\Machinjiri\Installer\VersionManager;

class ComposerScripts
{
    public static function postAutoloadDump()
    {
        // Create bin directory and executable
        $binDir = __DIR__ . '/../../bin';
        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

        $version = VersionManager::INSTALLER_VERSION;

        // Create the CLI executable
        $cliContent = <<<'PHP'
#!/usr/bin/env php
<?php
if (file_exists(__DIR__.'/../../../autoload.php')) {
    require __DIR__.'/../../../autoload.php';
} else {
    require __DIR__.'/../vendor/autoload.php';
}
use Symfony\Component\Console\Application;
use Mlangeni\Machinjiri\Installer\Commands\InstallCommand;
$terminal = new Application('Machinjiri Installer', $version);
$terminal->add(new InstallCommand());
$terminal->run();
PHP;
        file_put_contents($binDir . '/machinjiri', $cliContent);
        chmod($binDir . '/machinjiri', 0755);
    }
}
