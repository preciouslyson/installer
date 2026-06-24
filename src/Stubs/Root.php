<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class Root 
{

    public static function artisanTemplate(): string { return <<<'PHP'
#!/usr/bin/env php
<?php
require __DIR__ . '/vendor/autoload.php';
use Mlangeni\Machinjiri\Core\Artisans\Terminal\Terminal;
// init Machinjiri artisan console
(new Terminal())->run();
PHP;
    }

    public static function gitIgnoreTemplate(): string { return <<<GIT
/vendor/
/node_modules/
/.env
/storage/*
!important/storage/.gitignore
/.idea
/.vscode
/.DS_Store
GIT;
    }

    public static function rootHtaccess(): string { return <<<'HT'
# Redirect everything to public
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L]

Options -Indexes
<FilesMatch "(\.env|composer\.json|composer\.lock|config\.php)">
    Require all denied
</FilesMatch>
HT;
    }
    
    public static function phpunitTemplate(): string { return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit 
    xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
    xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
    bootstrap="tests/bootstrap.php"
    colors="true"
    cacheResult="true"
    executionOrder="random"
    stopOnFailure="false"
    processIsolation="false"
    >

    <php>
        <!-- Testing environment -->
        <env name="APP_ENV" value="testing"/>
        <env name="CODETEST_ENABLED" value="true"/>
        
        <!-- Database: use in-memory SQLite for speed -->
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        
        <!-- Cache & Queue: use array driver -->
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        
        <!-- Mail: log to array (no real sending) -->
        <env name="MAIL_MAILER" value="array"/>
        
        <!-- Session: array driver for tests -->
        <env name="SESSION_DRIVER" value="array"/>
        
        <!-- Encryption key (must be 32 chars base64) -->
        <env name="APP_KEY" value="base64:abcdefghijklmnopqrstuvwxyz1234567890="/>
        
        <!-- JWT secret -->
        <env name="JWT_SECRET" value="test-jwt-secret-key-32-chars-long-here!"/>
        
        <!-- Coverage threshold -->
        <env name="COVERAGE_MINIMUM" value="80"/>
    </php>

    <testsuites>
        <testsuite name="Unit">
            <directory>tests/Unit</directory>
        </testsuite>
        <testsuite name="Feature">
            <directory>tests/Features</directory>
        </testsuite>
        <testsuite name="Integration">
            <directory>tests/Integration</directory>
        </testsuite>
        <testsuite name="Browser">
            <directory>tests/Browser</directory>
        </testsuite>
    </testsuites>

    <coverage cacheDirectory="build/cache">
        <include>
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <directory>src/Console</directory>
            <directory>src/Testing</directory>
            <directory>src/Artisans/Terminal</directory>
            <directory>src/Exceptions</directory>
            <directory>vendor</directory>
            <directory>tests</directory>
        </exclude>
        <report>
            <html outputDirectory="build/coverage" lUpperBound="35" highLowerBound="70"/>
            <clover outputFile="build/logs/clover.xml"/>
            <text outputFile="build/logs/coverage.txt" showUncoveredFiles="true"/>
        </report>
    </coverage>

    <logging>
        <junit outputFile="build/logs/junit.xml"/>
    </logging>

    <extensions>
        <!-- Paratest for parallel execution -->
        <extension class="ParaTest\WrapperRunner\WrapperRunner"/>
    </extensions>
</phpunit>
XML;
    }
}