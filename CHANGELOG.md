# Changelog

All notable changes to this extension are documented in this file. The
project follows [Semantic Versioning](https://semver.org/).

Each pull request adds its changes to the section `Unreleased`. Breaking
changes and deprecations are also described, with migration steps, in
[DEPRECATIONS.md](Documentation/DEPRECATIONS.md). See
[CONTRIBUTING.md](CONTRIBUTING.md#releases) for the release steps.

Changes up to version 3.2.0 are listed in [ChangeLog](ChangeLog).

## Unreleased

### Added

* Support for TYPO3 14.3
* The file sources and targets of `cpsit/import-export-core` can be used with
  TYPO3 paths (`EXT:`, relative to the public directory)
* Options `fileDirectories` and `allowedHosts` in
  `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']`
* `Resource\ResourceAccess` applies the rules for files and URLs to
  components that read or write files themselves
* `Service\TransferService::run($tasks)` runs tasks with the processor of
  the core; it replaces `Service\DataTransferProcessor`

### Changed

* Requires TYPO3 14.3 and `cpsit/import-export-core` ^0.5
* Tasks are run by the processor of the core; tasks, sets and components are
  created by the factories of the core
  ([#71](https://github.com/dwenzel/t3import_export/issues/71))
* Conditions (`disable`) and expressions are evaluated by the core
* Files are read and written only in allowed directories (by default
  `fileadmin/` and `var/import/`) and with allowed file types; URLs are
  loaded only from allowed hosts (by default none). This applies to all file
  components, including `ValidateXML`, `DataTargetXMLStream`,
  `GenerateFileResource` and `GenerateUploadFile`
* The backend modules use the module access `user`
* The file finishers use the resource API of TYPO3 14
* `DataTargetFileStream` and `DataTargetXMLStream` write their temporary files
  to `var/transient/t3import_export/` instead of `typo3temp/` below the public
  directory
* TCA: `searchFields` is replaced by the field option `searchable`; the
  status of queue items is no longer searched in the backend
* The backend modules redirect to the list with an error message for a task
  or set that is not configured

### Deprecated

* `Persistence\DataSourceCSV`, `DataSourceXML`, `DataTargetCSV`,
  `DataSourceDummy` and `DataTargetDummy`; use the classes of the core
* `Domain\Model\DataStream` and `DataStreamInterface`; use the data stream of
  the core
* `DataTargetFileStream::TEMP_DIRECTORY`

### Removed

* Support for TYPO3 13; it is maintained on the branch `support/typo3-v13`
* Rendering content with TypoScript: pre-processor `RenderContent`, content
  objects in `disable` and in `DataSourceDB` queries
  ([#75](https://github.com/dwenzel/t3import_export/issues/75))
* The pre-processors that the core replaces (`AddArrays`, `Clean`,
  `ConcatenateFields`, `ImplodeArray`, `MapFields`, `MapFieldValues`,
  `RemoveFields`, `SetFieldValue`, `StringToTime`, `UnsetEmptyFields`)
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* Pre-processor `EvaluateExpressions`, `Expression\ExpressionEvaluator` and
  `Expression\ExpressionEvaluatorInterface`; use the classes of the core
* `Service\DataTransferProcessor`; use `Service\TransferService`
* `Domain\Model\TransferTask`, `TransferSet`, `Dto\TaskDemand` and
  `Dto\DemandInterface`; use the models of the core
* `Domain\Factory\TransferTaskFactory`, `Factory\FactoryFactory`,
  `ComponentFactoryMap`, `AbstractFactory`, `FactoryInterface`,
  `FactoryMapInterface`, the component factories, `DataSourceFactory` and
  `DataTargetFactory`; use the factories of the core
* `ext_tables.php`
* Tasks and sets in TypoScript (`module.tx_t3importexport.settings`),
  `ext_typoscript_setup.typoscript`, the static template and the TypoScript
  examples; use YAML. The command `t3import-export:convert-typoscript`
  converts existing configurations
  ([#75](https://github.com/dwenzel/t3import_export/issues/75))

### Fixed

* `LookUpDB` failed on TYPO3 14 because `Persistence\Query\QueryFacade`
  called the removed `QueryBuilder::execute()`
  ([#165](https://github.com/dwenzel/t3import_export/issues/165))

### Security

* `LookUpDB`: values from records are quoted in the `IN` constraint
* Symbolic links are resolved before the allowed directories are checked; a
  link inside an allowed directory that points outside of it is rejected
  (`Resource\Typo3PathResolver`)
* ValidateXML: schemas cannot include files or URLs outside the allowed
  locations
