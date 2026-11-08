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
* Publish to TER (`publish.yml`): on version tags (`X.Y.Z`) or started
  manually for an existing tag; checks the tag against `ext_emconf.php` and
  uploads the version with [tailor](https://github.com/TYPO3/tailor). Needs the
  repository secret `TYPO3_API_TOKEN`; without it, the job only reports that
  nothing was published.

## Triggers

* pushes to `main`, `feature/**` and `support/**`
* all pull requests

## Required checks

Pull requests into `main` can only be merged when the jobs of these workflows
pass (ruleset for `main`). The support branches have their own workflows, see
[#31](https://github.com/dwenzel/t3import_export/issues/31).

The same checks run locally with `composer lint`, `composer sca` and
`composer test:unit` (in the DDEV bundle: `ddev ext <script>`).
