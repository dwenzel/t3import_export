# Changelog

All notable changes to this extension are documented in this file. The
project follows [Semantic Versioning](https://semver.org/).

This file covers the branch `support/typo3-v13` (version 3.x, TYPO3 13.4).
Each pull request adds its changes to the section `Unreleased`.

Changes up to version 3.2.0 are listed in [ChangeLog](ChangeLog).

## Unreleased

## 3.3.1 - 2026-11-25

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
  ([#199](https://github.com/dwenzel/t3import_export/pull/199)). Names
  containing `?` or `#` are refused as well
  ([#202](https://github.com/dwenzel/t3import_export/pull/202)). These rules
  also apply to `file` of `DataTargetCSV` (exception code 1795400103).
* `GenerateFileResource` and `GenerateUploadFile` check the source of the
  files they copy
  ([#202](https://github.com/dwenzel/t3import_export/pull/202)). Existing
  configurations which copy files from other directories or from URLs need
  configuration, see
  [GenerateFileResource](Documentation/Components/PreProcessors/GENERATE_FILE.md#source-files):
  * Local files are read only inside allowed directories (default:
    `fileadmin/` of the public directory and `import/` of the `var`
    directory). A list in
    `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileSourceDirectories']`
    replaces the default list, it does not extend it. `..` segments, control
    characters and stream wrappers are refused; symbolic links are resolved.
  * `EXT:` paths are allowed only as `sourcePath` of the configuration; the
    file must be below that directory inside the extension. A value of a
    record which is an `EXT:` path is refused.
  * URLs are loaded only with http(s) from hosts listed in
    `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']`
    (default: none, so URLs are refused until hosts are listed). An entry
    `host` allows only the default port of the scheme (80, 443), an entry
    `host:port` only that port; `*.example.org` allows subdomains. `*`
    allows every host and port, including internal addresses. Host names are
    not resolved or checked against IP ranges, so do not list names which
    resolve to internal addresses.
  * Redirects are followed by the extension, at most 5, each checked like the
    URL; no redirect from https to http.
  * If `sourcePath` is a URL, the value of a record cannot change its scheme,
    host or port. The path of a URL `sourcePath` is not a boundary.
  * A source which is not allowed throws an exception (codes 1795400201 to
    1795400204) and stops the task, possibly after earlier records were
    imported.
  * The target name is the last segment of the path (for URLs without query
    and fragment); names with `?` or `#` and `.svgz` files are refused. SVG
    files (by extension or MIME type of the content) are sanitized with the
    SVG sanitizer of TYPO3; the MIME type detection is a best effort. HTML,
    XHTML and XML files are not sanitized.
  * Target file names containing `?` or `#` are now refused (exception code
    1795400103), like names with a colon and names ending in a dot or space.
    This affects `file` of `DataTargetCSV`, `target.name` of `WriteFile` and
    `MoveFile`, and the names of local source files of `GenerateFileResource`
    and `GenerateUploadFile` (e.g. `report#1.csv`, `a?b.csv`). Rename such
    files or targets before upgrading.

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
