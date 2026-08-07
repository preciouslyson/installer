<?php

namespace Mlangeni\Machinjiri\Installer\Stubs;

class Root 
{
    public static function sqliteEnvTemplate(): string { return <<<ENV
# Database Configuration (Sqlite) Default
# ------------------------------------------------
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite  
ENV;     
    }

    public static function mysqlEnvTemplate(): string { return <<<ENV
# Database Configuration (MYSQL, PostGres, etc)
# ------------------------------------------------
DB_CONNECTION=mysql
DB_HOST=host-name-here
DB_USERNAME=username-here
DB_PASSWORD=password-here
DB_DATABASE=database-name-here
DB_PORT=3306
DB_CHARSET=utf8
ENV;    
    }

    public static function artisanTemplate(): string { return <<<'PHP'
#!/usr/bin/env php

<?php
/**
 * Machinjiri Artisan CLI Entry Point
 *
 * This script serves as the entry point for the Machinjiri Artisan CLI,
 * bootstrapping the application and launching the terminal console.
 *
 * It loads the Composer autoloader and delegates execution to the
 * Terminal runner, which handles command parsing and dispatch.
 *
 * @publisher  Mlangeni Group
 * @author     Mlangeni Group
 * @copyright  (c) 2026 Mlangeni Group. All rights reserved.
 * @license    Proprietary – unauthorized use, reproduction, or distribution
 *             is strictly prohibited.
 *
 * @package    Machinjiri
 * @subpackage Core\Artisans\Terminal
 *
 * @see        \Mlangeni\Machinjiri\Core\Artisans\Terminal\Terminal
 */

require __DIR__ . '/vendor/autoload.php';

// Initialize and run the Machinjiri artisan console
(new \Mlangeni\Machinjiri\Core\Artisans\Terminal\Terminal())->run();
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
# =============================================================================
# Machinjiri Application .htaccess
#
# This file controls URL rewriting, directory access, and security for the
# Machinjiri application. It redirects all incoming requests to the public/
# directory, disables directory listing, and blocks access to sensitive files.
#
# @publisher  Mlangeni Group
# @author     Mlangeni Group
# @copyright  (c) 2026 Mlangeni Group. All rights reserved.
# @license    Proprietary – unauthorized use, reproduction, or distribution
#             is strictly prohibited.
#
# @package    Machinjiri
# =============================================================================

# -----------------------------------------------------------------------------
# URL Rewriting – Force all requests to go through the public/ directory
# -----------------------------------------------------------------------------
RewriteEngine On
RewriteCond %{REQUEST_URI} !^/public/
RewriteRule ^(.*)$ public/$1 [L]

# -----------------------------------------------------------------------------
# Security – Disable directory browsing
# -----------------------------------------------------------------------------
Options -Indexes

# -----------------------------------------------------------------------------
# Security – Block access to critical configuration and environment files
# -----------------------------------------------------------------------------
<FilesMatch "(\.env|composer\.json|composer\.lock|config\.php)">
    Require all denied
</FilesMatch>
HT;
    }
    
    public static function phpunitTemplate(): string { return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<!--
  =============================================================================
  Machinjiri PHPUnit Configuration
  =============================================================================

  This file defines the test suite configuration, environment variables,
  coverage settings, logging, and extensions for the Machinjiri application
  test runner.

  It is designed to run unit, feature, integration, and browser tests with
  optimal performance using in-memory SQLite, array caches, and synchronous
  queues. Coverage reports are generated in HTML, Clover, and plain text
  formats, with a minimum coverage threshold of 80%.

  @publisher  Mlangeni Group
  @author     Mlangeni Group
  @copyright  (c) 2026 Mlangeni Group. All rights reserved.
  @license    Proprietary – unauthorized use, reproduction, or distribution
              is strictly prohibited.

  @package    Machinjiri
  @see        https://phpunit.de/
  =============================================================================
-->

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

    <!--
      =========================================================================
      PHP Environment Variables for Testing
      =========================================================================
      These environment variables override application configuration when
      running tests. They ensure a clean, fast, and isolated test environment.
    -->
    <php>
        <!-- Application environment set to "testing" -->
        <env name="APP_ENV" value="testing"/>
        <!-- Enable code testing features (e.g., debug helpers) -->
        <env name="CODETEST_ENABLED" value="true"/>
        
        <!-- Use SQLite in-memory for maximum test speed -->
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        
        <!-- Cache and queue drivers use arrays to avoid external services -->
        <env name="CACHE_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
        
        <!-- Mail is logged to an array; no real emails are sent -->
        <env name="MAIL_MAILER" value="array"/>
        
        <!-- Session stored in array (stateless) -->
        <env name="SESSION_DRIVER" value="array"/>
        
        <!-- Application encryption key (must be 32 chars base64) – dummy for tests -->
        <env name="APP_KEY" value="base64:abcdefghijklmnopqrstuvwxyz1234567890="/>
        
        <!-- JWT authentication secret – dummy test key -->
        <env name="JWT_SECRET" value="test-jwt-secret-key-32-chars-long-here!"/>
        
        <!-- Minimum coverage threshold (percentage) for code coverage -->
        <env name="COVERAGE_MINIMUM" value="80"/>
    </php>

    <!--
      =========================================================================
      Test Suites
      =========================================================================
      Organises tests into logical groups. Each suite scans the corresponding
      directory for PHPUnit test classes.
    -->
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

    <!--
      =========================================================================
      Code Coverage Configuration
      =========================================================================
      Defines which source files to include in coverage reports, which to
      exclude, and the output formats and locations.
    -->
    <coverage cacheDirectory="build/cache">
        <include>
            <!-- Include all PHP files in the src directory -->
            <directory suffix=".php">src</directory>
        </include>
        <exclude>
            <!-- Exclude console and test-related classes -->
            <directory>src/Console</directory>
            <directory>src/Testing</directory>
            <directory>src/Artisans/Terminal</directory>
            <directory>src/Exceptions</directory>
            <!-- Exclude vendor and test directories -->
            <directory>vendor</directory>
            <directory>tests</directory>
        </exclude>
        <report>
            <!-- HTML coverage report with configurable colour thresholds -->
            <html outputDirectory="build/coverage" lUpperBound="35" highLowerBound="70"/>
            <!-- Clover XML coverage report -->
            <clover outputFile="build/logs/clover.xml"/>
            <!-- Plain text coverage summary, showing uncovered files -->
            <text outputFile="build/logs/coverage.txt" showUncoveredFiles="true"/>
        </report>
    </coverage>

    <!--
      =========================================================================
      Logging Configuration
      =========================================================================
      Produces JUnit XML test results for CI/CD integration.
    -->
    <logging>
        <junit outputFile="build/logs/junit.xml"/>
    </logging>

    <!--
      =========================================================================
      Extensions
      =========================================================================
      Load custom extensions – here we include Paratest for parallel test
      execution to speed up large test suites.
    -->
    <extensions>
        <extension class="ParaTest\WrapperRunner\WrapperRunner"/>
    </extensions>

</phpunit>
XML;
    }
}