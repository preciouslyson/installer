Machinjiri Installer

A professional Composer plugin and CLI tool for creating new Machinjiri PHP framework projects quickly and efficiently.

Installation

Method 1: Global Installation (Recommended)

Install the installer globally for creating projects anywhere:

```bash
composer global require machinjiri/installer
```

Method 2: Project Dependency

Add to your existing project's composer.json:

```json
{
    "require-dev": {
        "preciouslyson/installer": "^1.2.1"
    }
}
```

Then run:

```bash
composer require-dev machinjiri/installer
```

Quick Start

After global installation, create a new Machinjiri project:

```bash
machinjiri create
```

Available Commands

machinjiri create

Create a new Machinjiri application.

```bash

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
machinjiri create
```

Project Structure Created

The installer creates a complete Machinjiri application structure:

```
your-project/
├── app/
│   ├── Controllers/          # Application controllers
│   ├── Jobs/                 # Application Jobs
│   ├── Middleware/           # Custom middleware classes
│   ├── Queue/                # Queue Drivers
│   │     └── Drivers/        # Driver Files (Database, Sync, Redis)
│   ├── Models/               # Data models
│   ├── Providers/            # Service providers
│   └── Exceptions/           # Custom exceptions
├── bootstrap/
│   ├── app.php/              # Application bootstrap file
│   └── helpers.php           # Helper functions (API)
├── config/
│   ├── app.php               # Application configuration
│   ├── database.php          # Database configuration
│   ├── mail.php              # Mail service configuration
│   ├── auth.php              # Authentication configuration
│   └── providers.php         # Service providers list
├── database/
│   ├── migrations/           # Database migration files
│   ├── seeders/              # Database seeder classes
│   └── factories/            # Model factory classes
├── resources/
│   └── views/                # View templates
│       ├── layouts/          # Layout templates
│       ├── partials/         # Reusable view fragments
│       └── pages/            # Page-specific views
├── routes/
│   └── web.php               # Web application routes
├── storage/
│   ├── cache/                # Cached data files
│   ├── cookies/              # Cookie storage
│   ├── logs/                 # Application logs
│   └── sessions/             # Session files
├── public/
│   ├── src/
│   │   ├── css/              # Stylesheets
│   │   ├── js/               # JavaScript files
│   │   └── images/           # Image assets
│   ├── .htaccess             # .htaccess config file
│   └── index.php             # Application entry point
├── vendor/                   # Composer dependencies
├── .env                      # Environment configuration
├── artisan                   # Console application
├── composer.json             # Project dependencies
└── phpunit.xml               # PHPUnit configuration
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
# -------------------------------------------------
#   Application configurations                    |
# -------------------------------------------------
APP_NAME="MachinjiriApp"
APP_ENV=local
APP_DEBUG=true
APP_URL=http://localhost:3000
APP_KEY=
APP_CIPHER=aes-256-gcm

# ------------------------------------------------
# Database Configuration (Sqlite)                |
# ------------------------------------------------
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
DB_FOREIGN_KEYS=true
```

Post-Installation Steps

After successful installation:

1. Navigate to your project:
   ```bash
   cd myapp
   ```
2. Start the development server:
   ```bash
   php artisan server:start
   ```
3. Visit the welcome page:
   Open http://localhost:3000 in your browser
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
php artisan server:start

# Run tests
php vendor/bin/phpunit

# Create controller
php artisan make:controller UserController

# Create model with migration
php artisan make:model User -m

# Run database migrations
php artisan migrate

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
  - export PATH="$PATH:$HOME/.config/vendor/bin:$PATH"
  - machinjiri new ${CI_PROJECT_NAME} --no-interaction --force --no-dev
  - cd ${CI_PROJECT_NAME}
  - composer install --no-dev --no-interaction

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

Happy coding with Machinjiri Framework!