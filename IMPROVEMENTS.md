# Machinjiri Installer Improvements

## Overview
The installer has been significantly enhanced with improved error handling, validation, security, and user experience features.

## New Features

### 1. **ProjectValidator Class** (`ProjectValidator.php`)
Comprehensive validation system for project setup:
- **Project name validation**: Ensures valid naming conventions
- **Disk space checking**: Verifies sufficient space before installation
- **Permission validation**: Checks write permissions in target directory
- **PHP version validation**: Ensures minimum PHP 8.0
- **Extension validation**: Verifies required PHP extensions

### 2. **InstallationManager Class** (`InstallationManager.php`)
Manages installation steps with rollback capability:
- Register installation steps with execute and rollback callbacks
- Execute steps sequentially or individually
- Automatic rollback on failure
- Step tracking for recovery

### 3. **InstallerLogger Class** (`InstallerLogger.php`)
Comprehensive logging system:
- Multiple log levels: INFO, SUCCESS, WARNING, ERROR
- Timestamped log entries
- Optional verbose output
- Log file generation (`.installation.log`)
- Exception tracking with stack traces

### 4. **InstallationSummary Class** (`InstallationSummary.php`)
Post-installation reporting:
- Installation summary with details
- Quick start guide
- Important security notes
- Recommended next steps
- Helpful resources and documentation links

## Enhanced Features

### Security Improvements
- **.env file permissions**: Now set to `0600` (readable by owner only)
- **Validation checks**: Early detection of configuration issues
- **Error logging**: Sensitive operations are logged for debugging

### Error Handling
- **Enhanced error messages**: More descriptive and actionable error text
- **Logging on failure**: All failures are logged with full context
- **Stack traces**: Available in verbose mode for troubleshooting
- **Rollback capability**: Automatic cleanup if installation fails

### User Experience
- **Dry-run mode**: Preview what will be installed without creating files
- **Verbose mode**: Detailed output for debugging
- **Progress tracking**: Clear progress indication with step descriptions
- **Installation summary**: Comprehensive post-installation report
- **Quick start guide**: Immediate next steps after installation

## New Command Options

```bash
# Dry-run without creating files
machinjiri new my-app --dry-run

# Verbose output for debugging
machinjiri new my-app --verbose

# Both options together
machinjiri new my-app --dry-run --verbose

# Force overwrite existing directory
machinjiri new my-app --force

# Skip development dependencies
machinjiri new my-app --no-dev

# Specify Machinjiri version
machinjiri new my-app --m-version=2.0
```

## Installation Steps

The installer now performs these steps in order:

1. **Validate system requirements** - PHP version, extensions, Composer
2. **Validate project configuration** - Project name, disk space, permissions
3. **Prepare project directory** - Create or validate directory
4. **Create project structure** - Set up directories
5. **Create project files** - Bootstrap, routes, controllers, views
6. **Copy migrations** - Database migration files
7. **Write composer.json** - Project dependencies configuration
8. **Write environment configuration** - .env file with secure permissions
9. **Install dependencies** - Composer install
10. **Generate application key** - Secure app key
11. **Validate installation** - Verify all required files exist

## Usage Examples

### Basic Installation
```bash
machinjiri new my-awesome-app
```

### With Verbose Output
```bash
machinjiri new my-awesome-app -v
```

### Dry-Run to Preview
```bash
machinjiri new my-awesome-app --dry-run
```

### Force Overwrite
```bash
machinjiri new my-awesome-app --force
```

### With Specific Version
```bash
machinjiri new my-awesome-app --m-version=2.0
```

## Installation Log

After installation, a log file is created at `.installation.log` within the project directory containing:
- Timestamp for each operation
- Operation type (INFO, SUCCESS, WARNING, ERROR)
- Detailed messages
- Exception stack traces (if errors occurred)

## Configuration

The installer respects the following environment variables and options:

| Option | Default | Description |
|--------|---------|-------------|
| `--force` | false | Overwrite existing directory |
| `--m-version` | * | Specific Machinjiri version |
| `--dev` | false | Include dev dependencies |
| `--no-dev` | false | Skip dev dependencies |
| `--no-scripts` | false | Skip Composer scripts |
| `--dry-run` | false | Preview without creating files |
| `--verbose` | false | Detailed output |
| `--no-interaction` | false | Non-interactive mode |

## Error Handling

The installer now provides:
- Clear error messages with suggestions
- Automatic logging of all errors
- Detailed stack traces in verbose mode
- Project validation before installation begins
- Graceful failure with cleanup

## Security Notes

1. **Environment File**: The `.env` file is created with `0600` permissions (owner-read/write only)
2. **Sensitive Data**: Database passwords and API keys should be configured in `.env` after installation
3. **Git Exclusion**: `.env` files are ignored in `.gitignore`
4. **Permissions**: Proper directory permissions are set (0755 for directories, 0644 for files)

## Troubleshooting

If installation fails:

1. Check the installation log: `.installation.log`
2. Run with verbose mode: `machinjiri new app --verbose`
3. Verify system requirements are met
4. Check disk space availability
5. Ensure directory permissions are writable

## Development

To extend the installer:

1. Create custom validation in `ProjectValidator`
2. Add installation steps using `InstallationManager`
3. Log important operations using `InstallerLogger`
4. Display custom summaries with `InstallationSummary`

---

**Last Updated**: January 28, 2026  
**Installer Version**: 2.2.1
