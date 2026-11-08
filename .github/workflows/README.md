# GitHub Actions workflows

This file lives in `.github/workflows/`, not in `.github/`: GitHub shows a
`README.md` in `.github/` on the repository start page instead of the root
`README.md`.

## Workflows

* Tests (`tests.yml`): unit tests with TYPO3 13.4 and PHP 8.3 and 8.4,
  coverage report
* Code Quality (`code-quality.yml`): PHP-CS-Fixer, PHPStan
* Composer Validation (`composer-validate.yml`): `composer validate`,
  normalized `composer.json`
* TYPO3 Compatibility (`typo3-compatibility.yml`): TYPO3 Rector (dry run) with
  TYPO3 13.4 and PHP 8.3 and 8.4

## Triggers

The workflows still name the former branches `master` and `develop`. Updating
the triggers and making the jobs required checks for `main` is tracked in
[#31](https://github.com/dwenzel/t3import_export/issues/31).

The same checks run locally with `composer lint`, `composer sca` and
`composer test:unit` (in the DDEV bundle: `ddev ext <script>`).
