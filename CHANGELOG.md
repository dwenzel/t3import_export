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
* Command `t3import-export:export-task` (alias `export:task`) runs a single
  export task; it accepts the option `--yaml-config`
  ([#63](https://github.com/dwenzel/t3import_export/issues/63))
* `Dbal\Typo3ConnectionProvider` provides the connections of TYPO3 to the
  DBAL components of the core (`DataSourceDBAL`, `DataTargetDBAL`,
  `RelationListPreProcessor`), which can be used in tasks; `default` is the
  TYPO3 connection `Default`
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* Table `tx_t3importexport_queue_item` for the queue of the core and
  `Dbal\QueueRepositoryFactory`, which creates the queue repository of the
  core on it; the repository is registered as `QueueRepositoryInterface`
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* Upgrade wizard `t3importExport_queueItemMigration`, which copies the items
  of the queue table `tx_t3importexport_domain_model_queueitem` into the
  queue table of the core; items of all statuses are copied, items in
  processing become new, and the wizard can be run again. Run it before
  running tasks with 4.0
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* Commands `t3import-export:queue:status` (alias `queue:status`), which shows
  the number of queue items by status and lists items, and
  `t3import-export:queue:release` (alias `queue:release`), which sets items
  in processing back to new; they replace the list of queue items in the
  backend ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* `DataSourceQueue` accepts the option `limit`
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* `DataSourceDB` accepts the options of `DataSourceDBAL` of the core
  (`connection`, `fields` as list, `where` as map, `filter`, `orderBy` as
  map, `cursorField`, `pageSize`, `timezone`)
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* `DataTargetDB` accepts the options `connection`, `key` and `idField` of
  `DataTargetDBAL` of the core
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* Table `tx_t3importexport_id_map` for the ID map of the core and
  `Dbal\IdMapFactory`, which creates the ID map of the core on it; the ID map
  is registered as `IdMapInterface`. Tasks can use the option `idMap` and the
  components `TargetIdPreProcessor`, `ResolveReferencePreProcessor` and
  `UnchangedRecordFilter` of the core, e.g. to update the records of earlier
  runs and skip unchanged records
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* `Component\DisableableTrait` implements `disable` for components that do
  not extend `AbstractComponent`
* The XML components of the core (`ValidateXMLPreProcessor`,
  `XMLMapperPreProcessor`, `ValidateXMLFinisher`) can be used in tasks; they
  load schemas and files with the rules of the extension (`EXT:` paths,
  `fileDirectories`, `allowedHosts`)
  ([#154](https://github.com/dwenzel/t3import_export/issues/154))

### Changed

* `t3import-export:import-task` returns 1 instead of 0 for an invalid task
  configuration and 2 right away for an empty task identifier
* Requires TYPO3 14.3 and `cpsit/import-export-core` ^0.7. Components of a
  task run in the order of their integer keys (core
  [#81](https://github.com/CPS-IT/import-export-core/issues/81)); see
  [DEPRECATIONS.md](Documentation/DEPRECATIONS.md#changes-of-cpsitimport-export-core-06)
* If a task fails, the processor of the core tells the target before the
  source, and `DataSourceQueue` marks only items that are still claimed by
  the run as finished or failed (core 0.7); see
  [DEPRECATIONS.md](Documentation/DEPRECATIONS.md#changes-of-cpsitimport-export-core-07)
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
* The export module lists export sets with label and description, like the
  import module ([#62](https://github.com/dwenzel/t3import_export/issues/62))
* `LookUpDB` without `singleRow` no longer sets a target field that was not
  in the record to `[]` for an empty result; the field stays unset
  ([#180](https://github.com/dwenzel/t3import_export/issues/180))
* `LookUpDB` with `keepOrder` does not sort the rows if `groupBy` is set
  ([#180](https://github.com/dwenzel/t3import_export/issues/180))
* `DataSourceQueue` and `DataTargetQueue` are adapters on the queue
  components of the core and use the table `tx_t3importexport_queue_item`.
  Records read from the queue no longer contain `uid`. The target enqueues
  only the field `data` of a record, as string (`['data' => '<data>']`, the
  record of the migrated items); other fields are ignored. With
  `allowUpdate: true`, records already in the queue are set back to new;
  strings and integers in `allowUpdate`, which had practically no effect
  before, are treated as false. The
  `identifier` of the queue no longer needs the prefix `import.` or
  `export.`. See
  [DEPRECATIONS.md](Documentation/DEPRECATIONS.md#queue)
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* `DataSourceDB` is an adapter on `DataSourceDBAL` of the core and stays the
  default source. Configurations of earlier versions return the same
  records; SQL in the options is read by a deprecated fallback with the
  query builder of TYPO3. Without `connection`, the connection of the table
  mapping is used, and the restrictions of TYPO3 are not applied, as before.
  The class extends `DataSourceDBAL`; its constructor takes
  `Dbal\Typo3ConnectionProvider`, and `getDatabase()` is removed.
  `getRecords()` is declared as `iterable` (an array, a generator with
  `cursorField`), and `isConfigurationValid()` also checks the options of
  the core and rejects an empty `table`; invalid configurations throw the
  code 1796500001. See
  [DEPRECATIONS.md](Documentation/DEPRECATIONS.md#datasourcedb)
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))
* `DataTargetDB` is an adapter on `DataTargetDBAL` of the core.
  Configurations and records of earlier versions write the same rows; a
  record with `__identity` updates the row with this `uid`. The rows of a
  batch are written in one transaction and rolled back if the task fails;
  before, each row was committed at once. Values are converted to the
  column types, as before. Values must be scalar or `null` (arrays in JSON
  columns and `DateTime` objects in date and time columns (as the Doctrine type of
  the column expects) are
  converted as before),
  field names plain names, and records not empty (`PersistenceException`
  1647701464). The class extends `DataTargetDBAL` instead of
  `AbstractComponent`; its constructor takes `Dbal\Typo3ConnectionProvider`,
  `getDatabase()` is removed, and invalid configurations throw the code
  1796600001. With an ID map, a record skipped by `skip` fails the task
  (1796600002) after its batch was committed; use `disable`. See
  [DEPRECATIONS.md](Documentation/DEPRECATIONS.md#datatargetdb)
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))

### Deprecated

* `Persistence\DataSourceCSV`, `DataSourceXML`, `DataTargetCSV`,
  `DataSourceDummy` and `DataTargetDummy`; use the classes of the core
* `Domain\Model\DataStream` and `DataStreamInterface`; use the data stream of
  the core
* `DataTargetFileStream::TEMP_DIRECTORY`
* `DataSourceQueue`: option `batchSize` (use `limit`) and the constants
  `KEY_BATCH_SIZE` and `DEFAULT_BATCH_SIZE`
* `DataTargetQueue`: `allowUpdate` as string or integer (treated as false;
  use a boolean) and
  the queue taken from the field `identifier` of the record
* `DataSourceDB`: SQL in the options (`where` as string, `join`, `alias`,
  `groupBy`, SQL in `fields` and `orderBy`) and the connection selected by
  `identifier`; use the options of the core, see
  [DEPRECATIONS.md](Documentation/DEPRECATIONS.md#sql-in-the-options-of-datasourcedb)
* `DataTargetDB`: a record with `__identity` and another `uid` (changes
  the `uid` of the row) and the option `identifier`, which has no effect;
  see [DEPRECATIONS.md](Documentation/DEPRECATIONS.md#records-of-datatargetdb)
* `Component\PreProcessor\ValidateXML`, `Component\Finisher\ValidateXML`
  and `Component\PreProcessor\XMLMapper`; use `ValidateXMLPreProcessor`,
  `ValidateXMLFinisher` and `XMLMapperPreProcessor` of the core, see
  [DEPRECATIONS.md](Documentation/DEPRECATIONS.md#xml-components)
  ([#154](https://github.com/dwenzel/t3import_export/issues/154))

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
* `Domain\Model\QueueItem`, `Domain\Repository\QueueItemRepository` and
  the TCA of the table `tx_t3importexport_domain_model_queueitem` (list of
  queue items in the backend); use the queue of the core and the commands
  `queue:status` and `queue:release`. The table stays defined through 4.x
  for the upgrade wizard and will be removed in 5.0
  ([#132](https://github.com/dwenzel/t3import_export/issues/132))

### Fixed

* `LookUpDB` sorts the rows for `keepOrder` in PHP instead of adding the
  MySQL-specific `ORDER BY FIELD()` to the WHERE clause, so it works on all
  databases and together with AND/OR constraints
  ([#180](https://github.com/dwenzel/t3import_export/issues/180))
* `LookUpDB` does not create invalid SQL (`IN ()`) for empty values and
  returns no result for the record; an IN constraint after AND/OR is joined
  with AND ([#180](https://github.com/dwenzel/t3import_export/issues/180))
* `LookUpDB` with `singleRow` does not read from an empty result
  ([#180](https://github.com/dwenzel/t3import_export/issues/180))
* `LookUpDB` failed on TYPO3 14 because `Persistence\Query\QueryFacade`
  called the removed `QueryBuilder::execute()`
  ([#165](https://github.com/dwenzel/t3import_export/issues/165))
* `SetL10nParent` uses `uid` when `parent.identityField` is not set
  ([#133](https://github.com/dwenzel/t3import_export/issues/133))
* `UpdateTable` applies the configured `types` and accepts fields without a
  type ([#133](https://github.com/dwenzel/t3import_export/issues/133))
* `GenerateFileReference` does not create duplicate reference records for
  array records and skips records without identity
  ([#133](https://github.com/dwenzel/t3import_export/issues/133))

### Security

* `LookUpDB`: values from records are quoted in the `IN` constraint
* Symbolic links are resolved before the allowed directories are checked; a
  link inside an allowed directory that points outside of it is rejected
  (`Resource\Typo3PathResolver`)
* The real path of an `EXT:` path must be inside the directory of the
  extension or of an allowed directory; a symbolic link that points outside
  of them is rejected (`Resource\Typo3PathResolver`)
* The file type rules apply to the name of a symbolic link and of its target
  (`Resource\Typo3PathResolver`); a link whose target has no allowed file
  type (e.g. `data.csv` pointing to `data.csv.1`) is rejected
* ValidateXML: schemas cannot include files or URLs outside the allowed
  locations
