# Development Guide

This document provides guidelines and information for developers contributing to the
Import & Export extension.

## Code Quality Tools

The extension uses various code quality tools to maintain high code standards. These
tools can be executed via Composer scripts.

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

# Static code analysis
composer sca              # Run all static analyzers
composer sca:php          # Run PHPStan analysis

# TYPO3 Rector migration
composer migration        # Run all migrations
composer migration:rector # Run TYPO3 Rector

# Testing
composer test             # Run unit and functional tests
composer test:unit        # Run unit tests
composer test:functional  # Run functional tests
composer test:coverage    # Generate code coverage report
```

## Testing

### Running Tests

Unit tests can be executed with:

```bash
composer test:unit
```

Functional tests need a database. Without a database server, SQLite can be
used:

```bash
typo3DatabaseDriver=pdo_sqlite composer test:functional
```

In the DDEV environment
[t3import-export-bundle](https://github.com/dwenzel/t3import-export-bundle),
`ddev ext test:unit` runs the unit tests of the checked out branch.

### Code Coverage

To generate a code coverage report:

```bash
composer test:coverage
```

- Unit tests use `phpunit.unit.xml`, functional tests `phpunit.functional.xml`
- `XDEBUG_MODE=coverage` is set by the scripts
- The coverage of both is merged with `phpcov`; the reports are written to
  `.build/log/coverage/` (HTML in `html/_merged`, `clover.xml`)

## GitHub Workflows

The extension includes CI workflows for:
- Running tests with different PHP and TYPO3 versions
- Code quality checks (PHP-CS-Fixer, PHPStan)
- Composer validation
- TYPO3 compatibility checks

All workflows use the same Composer scripts that are available locally, ensuring
consistent behavior between local development and CI environments.

## PHP and TYPO3 Requirements

- PHP 8.3 or 8.4
- TYPO3 14.3

The extension is designed to work with the latest PHP and TYPO3 versions for optimal
security and performance.

## Coding Standards

The extension follows the
[TYPO3 Coding Guidelines](https://docs.typo3.org/m/typo3/reference-coreapi/main/en-us/CodingGuidelines/Index.html).
These standards are enforced through PHP-CS-Fixer, PHPStan, and EditorConfig.

## Contributing

The branching model, the workflow for pull requests and the release steps are
described in [CONTRIBUTING.md](../CONTRIBUTING.md). In short:

1. Create a feature branch from `main`, or from `support/typo3-v{major}` if the
   change only concerns an older TYPO3 version. Name it after the issue, e.g.
   `feature/152-changelog-and-release-steps`.
2. Make the change with tests, and update the documentation.
3. Add an entry to the section `Unreleased` of
   [CHANGELOG.md](../CHANGELOG.md). Describe breaking changes and deprecations
   in [DEPRECATIONS.md](DEPRECATIONS.md).
4. Run the tests and `composer lint` and `composer sca`.
5. Open a pull request into the branch you started from.
