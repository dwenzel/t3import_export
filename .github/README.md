# GitHub Actions for t3import_export

This directory contains GitHub Actions workflows for automated testing and quality assurance.

## Available Workflows

### 1. Tests (`tests.yml`)
Runs unit tests with different PHP and TYPO3 versions.
- Tests PHP 8.2, 8.3, and 8.4
- Tests TYPO3 v12.4 and v13.4
- Generates code coverage reports

### 2. Code Quality (`code-quality.yml`)
Runs code quality checks:
- PHP-CS-Fixer: Ensures code style compliance
- PHPStan: Performs static code analysis

### 3. Composer Validation (`composer-validate.yml`)
Validates the composer.json file:
- Checks syntax and dependencies
- Ensures composer.json is normalized

### 4. TYPO3 Compatibility (`typo3-compatibility.yml`)
Verifies compatibility with different TYPO3 versions:
- Tests with TYPO3 v12.4 and v13.4
- Uses TYPO3 Rector to check for deprecations

## Status Badges

You can add these status badges to your README.md:

```markdown
[![Tests](https://github.com/dwenzel/t3import_export/actions/workflows/tests.yml/badge.svg)](https://github.com/dwenzel/t3import_export/actions/workflows/tests.yml)
[![Code Quality](https://github.com/dwenzel/t3import_export/actions/workflows/code-quality.yml/badge.svg)](https://github.com/dwenzel/t3import_export/actions/workflows/code-quality.yml)
[![Composer Validation](https://github.com/dwenzel/t3import_export/actions/workflows/composer-validate.yml/badge.svg)](https://github.com/dwenzel/t3import_export/actions/workflows/composer-validate.yml)
[![TYPO3 Compatibility](https://github.com/dwenzel/t3import_export/actions/workflows/typo3-compatibility.yml/badge.svg)](https://github.com/dwenzel/t3import_export/actions/workflows/typo3-compatibility.yml)
```