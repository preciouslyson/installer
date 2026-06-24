<?php

namespace Mlangeni\Machinjiri\Installer;

use Composer\Plugin\Capability\CommandProvider as CommandProviderCapability;
use Mlangeni\Machinjiri\Installer\Commands\InstallCommand;

class CommandProvider implements CommandProviderCapability
{
    public function getCommands()
    {
        return [
            new InstallCommand(),
        ];
    }
}