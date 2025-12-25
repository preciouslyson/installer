Machinjiri Installer

A professional Composer plugin and CLI tool for creating new Machinjiri PHP framework projects quickly and efficiently.

Overview

This Composer plugin automates the setup of new Machinjiri applications with a complete directory structure, configuration files, and dependencies. It provides a modern CLI experience with Symfony Console and can be installed globally for easy project creation.

Features

· ✅ Composer Plugin - Proper Composer integration with autoloading
· ✅ Multiple Commands - machinjiri:install and new commands
· ✅ Global Installation - Install once, use anywhere
· ✅ Version Control - Specify Machinjiri framework version
· ✅ Force Overwrite - Overwrite existing directories
· ✅ Interactive/Non-interactive - Flexible for CI/CD
· ✅ Beautiful Output - Colored console output with progress indicators
· ✅ Complete Structure - All necessary directories and files
· ✅ Automatic Dependencies - Composer packages with auto-installation
· ✅ Secure Configuration - Auto-generated APP_KEY and file permissions
· ✅ Modern Welcome Page - Informative installation complete page

Installation

Method 1: Global Installation (Recommended)

Install the installer globally for creating projects anywhere:

```bash
composer global require preciouslyson/installer
```

Method 2: Project Dependency

Add to your existing project's composer.json:

```json
{
    "require-dev": {
        "preciouslyson/installer": "^1.1.3"
    }
}
```

Then run:

```bash
composer require-dev preciouslyson/installer
```

Quick Start

After global installation, create a new Machinjiri project:

```bash
machinjiri new myapp
```

Or using the longer command:

```bash
machinjiri:install myapp
```

Available Commands

machinjiri new

Create a new Machinjiri application.

```bash
Usage:
  new [options] [--] [<name>]

Arguments:
  name                           Name of the project directory

Options:
  -f, --force                    Force installation even if the directory already exists
      --m-version[=VERSION]      Machinjiri version to install [default: "*"]
      --dev                      Install development dependencies
      --no-dev                   Skip development dependencies
      --no-scripts               Skip Composer scripts
  -n, --no-interaction           Do not ask any interactive question
  -h, --help                     Display help for the command
  -q, --quiet                    Do not output any message
  -V, --version                  Display this application version
      --ansi                     Force ANSI output
      --no-ansi                  Disable ANSI output
  -v|vv|vvv, --verbose           Increase the verbosity of messages
```

Usage Examples

Basic Installation

```bash
# Create app in default directory (machinjiri-app/)
machinjiri new

# Create app with custom name
machinjiri new my-awesome-app

# Create app with specific version
machinjiri new blog --version="^1.2"

# Force overwrite existing directory
machinjiri new api --force
```

Development Setup

```bash
# Create with development dependencies
machinjiri new project --dev

# Create without development dependencies (production)
machinjiri new project --no-dev

# Non-interactive mode for CI/CD
machinjiri new app --no-interaction --force
```

Advanced Usage

```bash
# Specify version and force overwrite
machinjiri:install ecommerce --version="^1.3" --force

# Skip Composer scripts
machinjiri:install microservice --no-scripts

# Verbose output for debugging
machinjiri:install api -vvv
```

Project Structure Created

The installer creates a complete Machinjiri application structure:

```
your-project/
├── app/
│   ├── Controllers/
│   ├── Middleware/
│   ├── Models/
│   └── Providers/
├── bootstrap/
│   ├── app.php
│   └── helpers.php
├── config/
│   ├── app.php
│   ├── providers.php
│   ├── appserviceprovider.php
│   └── databaseserviceprovider.php
├── database/
│   ├── seeders/
│   └── factories/
├── public/
│   ├── index.php
│   └── .htaccess
├── resources/
│   └── views/
│       ├── layouts/
│       ├── partials/
│       └── welcome.mg.php
├── routes/
│   └── web.php
├── storage/
│   ├── session/
│   ├── cache/
│   └── logs/
├── tests/
│   └── Unit/
├── .env
├── .gitignore
├── .htaccess
├── artisan
├── composer.json
├── phpunit.xml
└── README.md
```

Key Features

1. Smart Requirements Check

The installer automatically checks:

· PHP version (≥ 8.0)
· Required extensions (json, mbstring, openssl)
· Composer availability
· Directory permissions

2. Secure Configuration

· Auto-generated 32-byte APP_KEY
· Secure file permissions (storage: 0775, .env: 0600)
· .htaccess security headers
· Protected configuration files

3. Modern CLI Experience

· Colored output
· Progress indicators
· Clear error messages
· Interactive prompts
· Verbose debugging mode

4. Complete Application Setup

· Composer.json with proper autoloading
· Environment configuration
· Service providers
· Database configuration
· Testing setup (PHPUnit)
· Development server ready

Environment Configuration

The installer generates a .env file with sensible defaults:

```env
APP_NAME="Machinjiri App"
APP_ENV=local
APP_KEY=[auto-generated-32-byte-key]
APP_DEBUG=true
APP_URL=http://localhost

LOG_CHANNEL=stack
LOG_LEVEL=debug

DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite

CACHE_DRIVER=file
SESSION_DRIVER=file
QUEUE_CONNECTION=sync
```

Post-Installation Steps

After successful installation:

1. Navigate to your project:
   ```bash
   cd myapp
   ```
2. Start the development server:
   ```bash
   php artisan serve
   ```
3. Visit the welcome page:
   Open http://localhost:8000 in your browser
4. Set up database:
   ```bash
   # For SQLite (default)
   touch database/database.sqlite
   
   # For MySQL/PostgreSQL, update .env file
   ```
5. Run migrations:
   ```bash
   php artisan migrate
   ```

Development Commands

Once your project is created:

```bash
# Start development server
php artisan serve

# Run tests
php vendor/bin/phpunit

# Create controller
php artisan make:controller UserController

# Create model with migration
php artisan make:model User -m

# Run database migrations
php artisan migrate

# Generate app key (if needed)
php artisan key:generate
```

Troubleshooting

"Command not found" after global installation

Ensure Composer's global bin directory is in your PATH:

```bash
# Linux/macOS
export PATH="$PATH:$HOME/.composer/vendor/bin"

# Or add to your shell profile
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> ~/.bashrc
echo 'export PATH="$PATH:$HOME/.composer/vendor/bin"' >> ~/.zshrc

# Windows (Powershell)
[Environment]::SetEnvironmentVariable("Path", $env:Path + ";$env:APPDATA\Composer\vendor\bin", "User")
```

"PHP extension missing"

Install required extensions:

```bash
# Ubuntu/Debian
sudo apt-get install php8.0-json php8.0-mbstring php8.0-openssl

# CentOS/RHEL
sudo yum install php-json php-mbstring php-openssl

# macOS with Homebrew
brew install php@8.0
brew services start php@8.0
```

"Permission denied"

```bash
# Check directory permissions
ls -la /path/to/project

# Fix permissions (Linux/macOS)
sudo chown -R $USER:$USER /path/to/project
sudo chmod -R 755 /path/to/project/storage
```

"Composer install failed"

```bash
# Check Composer version
composer --version

# Clear Composer cache
composer clear-cache

# Try with verbose output
machinjiri new app -vvv
```

For CI/CD Pipelines

The installer supports non-interactive mode for automation:

```bash
# GitLab CI example
before_script:
  - composer global require preciouslyson/machinjiri-installer
  - export PATH="$PATH:$HOME/.composer/vendor/bin"
  - machinjiri new ${CI_PROJECT_NAME} --no-interaction --force --no-dev
  - cd ${CI_PROJECT_NAME}
  - composer install --no-dev --no-interaction

# GitHub Actions example
jobs:
  install:
    runs-on: ubuntu-latest
    steps:
      - uses: actions/checkout@v3
      - name: Install PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: '8.0'
          extensions: json, mbstring, openssl
      - name: Install Composer
        run: |
          curl -sS https://getcomposer.org/installer | php
          sudo mv composer.phar /usr/local/bin/composer
      - name: Install Machinjiri
        run: |
          composer global require preciouslyson/machinjiri-installer
          echo "$HOME/.composer/vendor/bin" >> $GITHUB_PATH
          machinjiri new myapp --no-interaction --force
```

Security Best Practices

1. Generated APP_KEY is 32 bytes using random_bytes()
2. .env file permissions are set to 0600
3. Storage directories have appropriate permissions
4. .htaccess files include security headers
5. Configuration files are protected from web access
6. Composer dependencies are installed with --prefer-dist

Development

To contribute to the installer:

```bash
# Clone the repository
git clone https://github.com/preciouslyson/machinjiri-installer.git
cd machinjiri-installer

# Install dependencies
composer install

# Run tests
composer test

# Test the installer locally
php bin/machinjiri new test-app
```

License

This installer is part of the Machinjiri framework ecosystem and is released under the MIT License.

Support

· Documentation: Machinjiri Documentation
· Issues: GitHub Issues
· Email: precious.lyson@gmail.com

---

Happy coding with Machinjiri! 🚀