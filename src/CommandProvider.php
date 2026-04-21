<?php

namespace Preciouslyson\MachinjiriInstaller;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Preciouslyson\MachinjiriInstaller\Commands\InstallCommand;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return [
            new InstallCommand(),
        ];
    }
}