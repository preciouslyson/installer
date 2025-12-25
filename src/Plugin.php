<?php

namespace Preciouslyson\MachinjiriInstaller;

use Composer\Composer;
use Composer\IO\IOInterface;
use Composer\Plugin\PluginInterface;
use Composer\EventDispatcher\EventSubscriberInterface;
use Composer\Script\Event;
use Composer\Plugin\Capable;

class Plugin implements PluginInterface, EventSubscriberInterface, Capable
{
    public function activate(Composer $composer, IOInterface $io)
    {
        // Plugin activation
    }

    public function deactivate(Composer $composer, IOInterface $io)
    {
        // Plugin deactivation
    }

    public function uninstall(Composer $composer, IOInterface $io)
    {
        // Plugin uninstallation
    }

    public function getCapabilities()
    {
        return [
            'Composer\Plugin\Capability\CommandProvider' => 'Preciouslyson\MachinjiriInstaller\CommandProvider',
        ];
    }

    public static function getSubscribedEvents()
    {
        return [
            'post-autoload-dump' => 'onPostAutoloadDump',
        ];
    }

    public static function onPostAutoloadDump(Event $event)
    {
        // Optional: post-autoload-dump actions
    }
}