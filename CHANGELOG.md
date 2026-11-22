# Changelog

All notable changes to this extension are documented in this file. The
project follows [Semantic Versioning](https://semver.org/).

This file covers the branch `support/typo3-v13` (version 3.x, TYPO3 13.4).
Each pull request adds its changes to the section `Unreleased`.

Changes up to version 3.2.0 are listed in [ChangeLog](ChangeLog).

## Unreleased

### Fixed

* The conflict modes of the finishers `WriteFile` and `MoveFile` are applied
  as named. Before, TYPO3 did not recognise the mode strings and fell back to
  `cancel`. This changes the behaviour of existing configurations if a target
  file exists: `changeName` (default of `WriteFile`) and `renameNewFile`
  (default of `MoveFile`) now rename the new file, and `overrideExistingFile`
  now replaces the existing file, instead of cancelling. Set
  `conflictMode = cancel` to keep the previous behaviour
  ([#124](https://github.com/dwenzel/t3import_export/issues/124))
