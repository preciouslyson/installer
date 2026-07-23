<?php

namespace Mlangeni\Machinjiri\Installer;

class VersionManager {

    public const INSTALLER_VERSION = "1.2.9";
    public const RECOMMENDED_PHP_VERSION = "8.3.0";

    public static function installable (): array
    {
        return [
            'minimum' => '^2.1.6', // current machinjiri version
            'installable' => [
                '^2.1.6',
            ]
        ];
    }

    public static function canInstall(string $version): bool 
    {
        return in_array($version, self::installable());
    }
    
}