# Development Guide

This document provides guidelines and information for developers contributing to the t3import_export extension.

## Code Quality Tools

The extension uses various code quality tools to maintain high code standards. These tools can be executed via Composer scripts.

### Available Commands

```bash
# Fix code quality issues
composer fix              # Run all fixers
composer fix:composer     # Fix composer.json normalization
composer fix:editorconfig # Fix EditorConfig violations
composer fix:php          # Fix PHP coding style issues

# Check for code quality issues
composer lint             # Run all linters
composer lint:composer    # Check composer.json formatting
composer lint:editorconfig # Check EditorConfig compliance
composer lint:php         # Check PHP coding style
composer lint:typoscript  # Check TypoScript files

# Static code analysis
composer sca              # Run all static analyzers
composer sca:php          # Run PHPStan analysis

# TYPO3 Rector migration
composer migration        # Run all migrations
composer migration:rector # Run TYPO3 Rector

# Testing
composer test             # Run all tests
composer test:unit        # Run unit tests
composer test:coverage    # Generate code coverage report
```

## Testing

### Running Tests

Unit tests can be executed with:

```bash
composer test:unit
```

### Code Coverage

To generate a code coverage report:

```bash
composer test:coverage
```

The code coverage setup has been updated to use a centralized configuration file (`phpunit.xml` in the root directory). This change ensures consistent test execution across different environments.

**Important changes:**
- The test:coverage command now uses `phpunit.xml` instead of `Tests/Build/UnitTests.xml`
- Xdebug mode is explicitly set with `XDEBUG_MODE=coverage` to ensure proper coverage reporting
- Coverage reports are generated in `.Build/log/coverage/`

## GitHub Workflows

The extension includes CI workflows for:
- Running tests with different PHP and TYPO3 versions
- Code quality checks (PHP-CS-Fixer, PHPStan)
- Composer validation
- TYPO3 compatibility checks

All workflows use the same Composer scripts that are available locally, ensuring consistent behavior between local development and CI environments.

## PHP and TYPO3 Requirements

- PHP 8.3 or 8.4
- TYPO3 13.4

The extension is designed to work with the latest PHP and TYPO3 versions for optimal security and performance.

## Coding Standards

The extension follows the [TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html). These standards are enforced through PHP-CS-Fixer, PHPStan, and EditorConfig.

## Contributing

When contributing to this extension, please:

1. Create a feature branch from the `develop` branch
2. Run all tests and quality checks before submitting
3. Update documentation when introducing new features or changes
4. Make sure code coverage remains high
5. Follow the TYPO3 coding standards