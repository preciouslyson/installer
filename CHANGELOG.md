# Changelog

## 1.3.0 - 2026-07-22

### Highlights

This release focuses on improving the installer experience, clarity, and reliability for new Machinjiri project setups.

### Added

- A refreshed CLI banner with a cleaner Machinjiri presentation
- Integration of the installer logging system for better progress and error reporting
- Installation step management with rollback support for safer setup failures
- A post-install summary with quick-start guidance and next steps
- Expanded documentation and usage examples for common install scenarios

### Improved

- More polished command output and onboarding flow
- Better support for dry-run, verbose, and non-interactive installs
- Clearer project creation messaging and install guidance

### Requirements

- PHP 8.3 or newer
- Composer 2.x
- Required extensions: json, mbstring, openssl, zip, pdo, tokenizer, and ctype

### Upgrade Notes

To install or update to this version:

```bash
composer global require machinjiri/installer
```

Or, if you are using it as a project dependency:

```bash
composer require --dev machinjiri/installer
```
