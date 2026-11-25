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
  `.yml` in an existing directory (no symbolic links, no `..` segments, no
  control characters) and does not read or write files with a stream wrapper
* `DataTargetCSV` writes only files with an allowed data file type (default:
  `csv`, `tsv`, `txt`, `xml`, `json`, `yaml`, `yml`) inside an allowed
  directory (default: `fileadmin/` and `typo3temp/` of the public directory and
  the `var` directory). Both lists can be configured. Names matching the
  `fileDenyPattern` of TYPO3, `EXT:` paths, `..` segments and stream wrappers
  are refused; symbolic links are resolved. This changes the behaviour of
  existing configurations which write to other directories or file types, see
  [DataTargetCSV](Documentation/Persistence/DataTargetCSV.md#written-files)
  ([#199](https://github.com/dwenzel/t3import_export/pull/199))
* `GenerateFileResource` and `GenerateUploadFile` copy only files whose names
  do not match the `fileDenyPattern` of TYPO3 or its default pattern; the
  target names of `WriteFile` and `MoveFile` are checked the same way. Server
  configuration files (e.g. `.htaccess`, `.user.ini`), names with a colon and
  names ending in a dot or space are refused
  ([#199](https://github.com/dwenzel/t3import_export/pull/199))

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
