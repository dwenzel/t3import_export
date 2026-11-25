# Changelog

All notable changes to this extension are documented in this file. The
project follows [Semantic Versioning](https://semver.org/).

This file covers the branch `support/typo3-v13` (version 3.x, TYPO3 13.4).
Each pull request adds its changes to the section `Unreleased`.

Changes up to version 3.2.0 are listed in [ChangeLog](ChangeLog).

## Unreleased

### Security

* The command `t3import-export:convert-typoscript` is not offered by the
  scheduler any more; it writes only files with the extension `.yaml` or
  `.yml` and does not read or write files with a stream wrapper

## 3.3.0 - 2026-11-22

### Security

* `LookUpDB`: values from records are quoted in the `IN` constraint
  ([#161](https://github.com/dwenzel/t3import_export/pull/161))
* `ValidateXML`: included schemas (`xs:include`, `xs:import`, `xs:redefine`,
  `xs:override`) are loaded only from `xsd` files below the directory of the
  main schema; URLs and other locations are denied. External entities and
  external DTDs are not loaded
  ([#167](https://github.com/dwenzel/t3import_export/pull/167))

### Fixed

* The finisher `ValidateXML` validates the document. Before, it failed before
  validating ("Cannot access parser properties before loading data"). This
  changes the behaviour of existing configurations: without
  `config.schema.file`, the document is validated against its DTD, so a
  document without `DOCTYPE` or with an external DTD is reported as invalid;
  with a schema, the schema validation counts. A missing or empty schema file
  is logged as error 1508914547. See
  [ValidateXML](Documentation/Components/Finishers/ValidateXML.md)
  ([#167](https://github.com/dwenzel/t3import_export/pull/167))
* The conflict modes of the finishers `WriteFile` and `MoveFile` are applied
  as named. Before, TYPO3 did not recognise `changeName`, `renameNewFile` and
  `overrideExistingFile` and fell back to `cancel`. This changes the behaviour
  of existing configurations if a target file exists: `changeName` (default of
  `WriteFile`) and `renameNewFile` (default of `MoveFile`) now rename the new
  file, and `overrideExistingFile` now replaces the existing file, instead of
  cancelling. Set `conflictMode = cancel` to keep the previous behaviour
  ([#124](https://github.com/dwenzel/t3import_export/issues/124))
