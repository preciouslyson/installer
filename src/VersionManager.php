<?php

namespace Mlangeni\Machinjiri\Installer;

class VersionManager {

    public const INSTALLER_VERSION = "1.3.4";
    public const RECOMMENDED_PHP_VERSION = "8.3.0";

    public static function installable (): array
    {
        return [
            'minimum' => '^2.2.1', // minimum installable machinjiri version
            'installable' => [
                '^2.2.0',
            ]
        ];
    }

    public static function canInstall(string $version): bool 
    {
        return in_array($version, self::installable());
    }
    
}