<?php

namespace Preciouslyson\MachinjiriInstaller;

use Symfony\Component\Console\Application;

class ComposerScripts
{
    public static function postAutoloadDump()
    {
        // Create bin directory and executable
        $binDir = __DIR__ . '/../../bin';
        if (!is_dir($binDir)) {
            mkdir($binDir, 0755, true);
        }

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
use Preciouslyson\MachinjiriInstaller\Commands\InstallCommand;

$application = new Application('Machinjiri Installer', '1.0.0');
$application->add(new InstallCommand());

$application->run();
PHP;

        file_put_contents($binDir . '/machinjiri', $cliContent);
        chmod($binDir . '/machinjiri', 0755);
    }
}