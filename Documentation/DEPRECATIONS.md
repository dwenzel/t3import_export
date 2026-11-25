Deprecations
============

TypoScript configuration and rendering content with TypoScript were deprecated
in version 3.1 and are removed in version 4.0 (together with support for
TYPO3 14). Reasons and plan:
[#75](https://github.com/dwenzel/t3import_export/issues/75).

Deprecated features write a message to the TYPO3 deprecation log (each message
once per request). Enable the log in the Install Tool (Settings > Configuration
Presets > Debug, or `$GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']`).

## Removed in 4.0

### TypoScript configuration

Tasks and sets are read from YAML files only. The TypoScript configuration
(`module.tx_t3importexport.settings`) is not read any more
([#75](https://github.com/dwenzel/t3import_export/issues/75)).

| Removed | Replacement |
|---|---|
| Tasks and sets in TypoScript (`module.tx_t3importexport.settings.import` and `.export`) | YAML files in the configured directories, see [YAML configuration](./YAML_CONFIGURATION.md). Convert them with `t3import-export:convert-typoscript`, see [Migration](#migration). |
| Notice about tasks and sets from TypoScript in the backend modules | none |
| `module.tx_t3importexport.settings.debug` (debug output in the backend modules) | none |
| `ext_typoscript_setup.typoscript` (template paths of the backend modules) | none, the default paths of the extension are used |
| Static template "Import Export: Examples" and the TypoScript examples in `Resources/Public/Examples/TypoScript/` | the YAML examples in `Resources/Public/Examples/Yaml/` |
| `Domain\Factory\TransferSetFactory::withSettings()` | none; the tasks of a set are looked up in the YAML configuration |
| `BaseController::mergeYamlConfiguration()`, `getTypoScriptIdentifiers()`, `getTransferSetFactory()` and the property `$typoScriptIdentifiers` | `BaseController::getConfiguration()` returns the tasks and sets of the module from YAML |
| Tasks and sets in `$this->settings` of the controllers | the property `$configuration` (tasks and sets of the module from YAML); `$this->settings` holds only what Extbase reads from TypoScript and no tasks or sets |
| Template variable `{settings}` in the index templates of the backend modules (not assigned any more) | `{tasks}` and `{sets}` |

The task and set actions of the backend modules redirect to the list with an
error message if the identifier is not configured (before: a PHP warning for
tasks, an empty run for sets).

The command `t3import-export:convert-typoscript` stays: it reads TypoScript
files, not the configuration of the site, and helps with the migration.

### Rendering content with TypoScript

Rendering content with TypoScript needs the TypoScriptFrontendController,
which TYPO3 14 removed. These features are removed:

| Removed | Replacement |
|---|---|
| Pre-processor `RenderContent` | Pre-processor [EvaluateExpressionsPreProcessor](./Components/PreProcessors/EVALUATE_EXPRESSIONS.md) of the core (contains a migration table) |
| `disable` rendered as content object (`disable = TEXT`) | `disable.expression`, see [Components](./COMPONENT.md#by-expression). A content object throws an `InvalidConfigurationException` (code 1794781862). |
| `DataSourceDB` query values rendered as content objects | `{expression: ...}`, see [DataSourceDB](./Persistence/DataSourceDB.md). A content object throws an `InvalidConfigurationException` (code 1794781881). |
| `ConcatenateFields` constructor arguments (`ContentObjectRenderer`, `TypoScriptService`) | none |
| `RenderContentTrait`; `AbstractComponent` and `DataSourceDB` do not implement `RenderContentInterface` any more | none |

Features of content objects such as `parseFunc`, `typolink`, `crop` or data
from a frontend page (`TSFE:`, `page:`) have no replacement. Use your own
pre-processor if you need them.

### Pre-processors replaced by the core

The pre-processors `AddArrays`, `Clean`, `ConcatenateFields`, `ImplodeArray`,
`MapFields`, `MapFieldValues`, `RemoveFields`, `SetFieldValue`, `StringToTime`
and `UnsetEmptyFields` are removed
([#132](https://github.com/dwenzel/t3import_export/issues/132)). The
replacements, their differences and a migration example are listed in
[Deprecated in 3.2](#deprecated-in-32).

The pre-processors of the core support `disable` like the components of the
extension (core [#24](https://github.com/CPS-IT/import-export-core/issues/24)).

### Processor and factories of the core

The processor, the task and set models and the factories moved into
`cpsit/import-export-core` 0.2
([#71](https://github.com/dwenzel/t3import_export/issues/71), core
[UPGRADE.md](https://github.com/CPS-IT/import-export-core/blob/main/UPGRADE.md)).

| Removed | Replacement |
|---|---|
| `Service\DataTransferProcessor` | `Service\TransferService::run($tasks)` (extension), which uses `DataTransferProcessor` and `DemandProcessor` of the core |
| `Domain\Model\TransferTask`, `TransferSet`, `Dto\TaskDemand`, `Dto\DemandInterface` | the classes with the same names in `CPSIT\ImportExportCore\Domain\Model\` |
| `Domain\Factory\TransferTaskFactory` | `CPSIT\ImportExportCore\Factory\TransferTaskFactory::create($identifier, $settings)` |
| `Factory\FactoryFactory`, `ComponentFactoryMap`, `AbstractFactory`, `FactoryInterface`, `FactoryMapInterface`, the component factories (`PreProcessorFactory` etc.), `DataSourceFactory`, `DataTargetFactory` | `CPSIT\ImportExportCore\Factory\ComponentFactory` with the defaults `DataSourceDB` (source) and `DataTargetRepository` (target) |

`Domain\Factory\TransferSetFactory` stays; `get($settings, $identifier)`
returns the set of the core.

Changes for configurations and custom components:

* `isDisabled()` takes `?ResultInterface` instead of `?TaskResult` as third
  parameter. `AbstractComponent` implements `DisableableInterface`.
* A component configured without `config` receives an empty configuration
  when it is created. Components whose `isConfigurationValid([])` is false
  throw an `InvalidConfigurationException`; add the configuration.
* A set that refers to an undefined task throws an
  `InvalidConfigurationException`; before, the task was skipped.
* Pre-processors, converters, post-processors, initializers and finishers of
  the extension keep working: the core wraps them in adapters. Initializers
  and finishers receive an empty list of records (none of the extension uses
  it). Changes of post-processors to the source record are discarded.
* Core components such as `CPSIT\ImportExportCore\Processor\ValueRulesPreProcessor`
  can be used in tasks.
* `Persistence\CompletableSourceInterface`: `complete(ResultInterface $result)`
  (called by the core processor) and `fail()` without arguments.
* `DataSourceDynamicRepository` returns arrays of the properties of the domain
  objects (before, the processor converted them).

### Expressions and conditions of the core

Expressions and the option `disable` are evaluated by
`cpsit/import-export-core` (core [#24](https://github.com/CPS-IT/import-export-core/issues/24)).
The configuration does not change.

| Removed | Replacement |
|---|---|
| Pre-processor `EvaluateExpressions` | `CPSIT\ImportExportCore\Processor\EvaluateExpressionsPreProcessor`, same configuration |
| `Expression\ExpressionEvaluator`, `Expression\ExpressionEvaluatorInterface` | the classes with the same names in `CPSIT\ImportExportCore\Expression\` |

`AbstractComponent::isDisabled()` uses `CPSIT\ImportExportCore\Condition\ConditionEvaluator`.
Message IDs in `disable.if.result.hasMessage` may now also be a single integer.

### Queue

The queue source and target use the queue of `cpsit/import-export-core` and
the table `tx_t3importexport_queue_item`
([#132](https://github.com/dwenzel/t3import_export/issues/132)). See
[Queue](./Persistence/Queue.md).

| Removed | Replacement |
|---|---|
| `Domain\Model\QueueItem` | `CPSIT\ImportExportCore\Queue\QueueItem`, `QueueItemStatus` |
| `Domain\Repository\QueueItemRepository` | `CPSIT\ImportExportCore\Queue\QueueRepositoryInterface` (`DbalQueueRepository` on `tx_t3importexport_queue_item`) |
| TCA of `tx_t3importexport_domain_model_queueitem` (list of queue items in the backend) and its labels in `locallang_db.xlf` | the commands `t3import-export:queue:status` and `t3import-export:queue:release` |
| Configuration of `DataSourceQueue` and `DataTargetQueue`: `identifier` must begin with `import.` or `export.` | any non-empty `identifier` |

Breaking changes:

* Records read by `DataSourceQueue` no longer contain `uid` (before:
  `['uid' => ..., 'data' => ...]`, now `['data' => ...]`). Remove mappings
  or components that use `uid` of the queue item; the queue item cannot be
  changed by its `uid` any more.
* `DataTargetQueue` enqueues only the field `data` of a record, as string
  (`['data' => '<data>']`). Before, all fields of the record were written as
  columns of the queue table, e.g. `status`, `checksum` or `created_date`;
  they are ignored now. Records without a scalar `data` are not written
  (`persist()` returns false).
* `allowUpdate` has a new meaning and accepts booleans only: with
  `allowUpdate: true`, a record that is already in the queue is set back to
  new, also if it was finished or failed. Before, only strings were valid
  (e.g. `'1'`), and the columns of the existing item were updated from the
  record; its status was not changed unless the record contained it, so the
  option had practically no effect. Strings and integers are now treated as
  false (deprecated, see
  [Options of the queue source and target](#options-of-the-queue-source-and-target)).
  Set `allowUpdate: true` only if items that are in the queue should be
  processed again on every run that writes them.
* A record that is already in the queue and is not updated makes `persist()`
  return false instead of null.
* `DataSourceQueue::getRecords()` returns a `Generator` instead of an array.
  The items are claimed when the records are iterated, not when
  `getRecords()` is called. For tasks nothing changes: `TransferService`
  reads the records of all tasks (`buildQueue()`) before it processes them,
  as before. If the task fails, the claimed items are marked as failed.
* The constructors of `DataSourceQueue` and `DataTargetQueue` take
  `CPSIT\ImportExportCore\Queue\QueueRepositoryInterface` instead of
  `QueueItemRepository`. Both extend the classes of the core
  (`CPSIT\ImportExportCore\Persistence\DataSourceQueue`, `DataTargetQueue`).
  Subclasses must follow the changed signatures:
  `DataTargetQueue::persist(array|object $object, ?array $configuration = null): bool`,
  `persistAll(mixed $result = null, ?array $configuration = null): void` and
  `DataSourceQueue::fail(?\Throwable $error = null): void`. The properties
  `$targetClass` and `DataSourceQueue::$claimedUids` and the method
  `DataSourceQueue::release()` are removed.
* The checksum is computed by the core from queue and record
  (`QueueItem::checksum()`), not as `sha1(data . identifier)`.
* The column `data` of the table `tx_t3importexport_queue_item` holds the
  record as JSON (`{"data":"<data>"}`), not the data itself. Adapt code that
  reads the table directly. The column of the queue is `queue` (before:
  `identifier`), the times are `created_at` and `updated_at` (DATETIME).
* The table `tx_t3importexport_domain_model_queueitem` is no longer used. Its
  items are copied into the new table by the upgrade wizard
  `t3importExport_queueItemMigration`, see [Migration](#migration). The table
  stays defined through 4.x for the wizard and will be removed in 5.0.

### Changes of `cpsit/import-export-core` 0.6

Changes of core 0.6
([UPGRADE.md](https://github.com/CPS-IT/import-export-core/blob/main/UPGRADE.md#060))
that concern tasks of the extension:

* The components of a task (`initializers`, `preProcessors`, `converters`,
  `postProcessors`, `finishers`, `filters`) run in the order of their integer
  keys; string keys follow in the order of the configuration
  ([core #81](https://github.com/CPS-IT/import-export-core/issues/81)).
  Before, they ran in the order of the merged configuration, and a component
  added by a later file under a new key ran last. Check lists whose integer
  keys are not in ascending order. Use keys such as `10`, `20`, so that later
  files can add components in between.
* If a task fails, the processor calls `fail()` on the source and the target
  of the core (`Persistence\FailureAwareInterface`) before it passes the
  exception on: `DataTargetDBAL` rolls back the rows of the current batch
  ([core #102](https://github.com/CPS-IT/import-export-core/issues/102)).
  Of the sources and targets of the extension, `DataSourceQueue` marks the
  claimed items as failed, as it did before, and `DataTargetDB` rolls back
  the rows of the current batch (see [DataTargetDB](#datatargetdb)).
* `Dbal\DataTargetDBAL` has no methods `quoteColumns()` and `types()` any
  more ([core #98](https://github.com/CPS-IT/import-export-core/issues/98));
  subclasses that call or override them must drop or copy them.

### Changes of `cpsit/import-export-core` 0.7

4.0 requires `cpsit/import-export-core` ^0.7 (core
[UPGRADE.md](https://github.com/CPS-IT/import-export-core/blob/main/UPGRADE.md)).
Changes that concern tasks of the extension:

* If a task fails, the processor calls `fail()` first on the target, then on
  the source ([core #109](https://github.com/CPS-IT/import-export-core/issues/109)).
  `DataTargetDB` rolls back the rows of the current batch before
  `DataSourceQueue` marks the claimed items as failed, so the items stay
  failed when both use the same connection.
* `DataSourceQueue` marks items as finished or failed only while they are
  still claimed by the run
  ([core #109](https://github.com/CPS-IT/import-export-core/issues/109),
  [#115](https://github.com/CPS-IT/import-export-core/issues/115)). Items
  released (`queue:release`) or requeued while a task runs stay new and are
  processed again.

### DataSourceDB

`DataSourceDB` is an adapter on `DataSourceDBAL` of the core
([#132](https://github.com/dwenzel/t3import_export/issues/132)). It stays
the default source of a task. Configurations of earlier versions return the
same records: options without SQL are read by the core, SQL in the options
by a deprecated fallback (see
[SQL in the options of DataSourceDB](#sql-in-the-options-of-datasourcedb)).
As before, the restrictions of TYPO3 (`deleted`, `hidden`, start and end
time) are not applied, and the connection of the table mapping is used.

Breaking changes for code that uses the class:

* `DataSourceDB` extends `CPSIT\ImportExportCore\Dbal\DataSourceDBAL`. The
  constructor takes `Dbal\Typo3ConnectionProvider` (and an optional clock)
  instead of `ConnectionPool` and `DatabaseConnectionService`. The methods
  `getDatabase()`, `getDataBase()` and `getDatabaseConnectionService()` and
  the properties `$database`, `$connectionPool` and `$connectionService`
  (of `DatabaseTrait`) are removed. Get the connection from
  `Typo3ConnectionProvider` or from the `ConnectionPool` of TYPO3.
* `getRecords()` is declared with the return type `iterable`. It returns an
  array as before; with the new option `cursorField` it returns a
  `Generator`.
* `isConfigurationValid()` checks the options of the core, e.g. `filter`,
  `where` as map and `cursorField`. Options of the core cannot be combined
  with SQL in the options. A content object or an expression that cannot be
  evaluated is reported by `getRecords()`, as before.
* Invalid configurations make `getRecords()` throw an
  `InvalidConfigurationException` with the code 1796500001: an invalid
  configuration of the core, or SQL in the options combined with `where` as
  map, `filter`, `cursorField`, `pageSize` or `timezone`. Keys of the core
  that were ignored before 4.0 are checked now; values such as
  `pageSize: '10'`, `connection: ''`, an unknown `timezone`, a `filter` that
  is not a condition or `cursorField` together with `orderBy` make the
  configuration invalid. Remove such keys or correct their values.
* An empty `table` makes the configuration invalid and `getRecords()` throw
  the code 1796500001 (before: 1642072670 from `getRecords()`).
* If both `config.connection` and `identifier` are set, `connection` is used
  and a message is written to the deprecation log.
* `config.connection` selects the connection. It had no effect before, but
  was shown in an example of the documentation; remove it or check that it
  names the connection of the table.

### DataTargetDB

`DataTargetDB` is an adapter on `DataTargetDBAL` of the core
([#132](https://github.com/dwenzel/t3import_export/issues/132)).
Configurations and records of earlier versions write the same rows: a
record with `__identity` updates the row with this `uid` (nothing is
written if there is no such row), other records are inserted; `unsetKeys`
and `skip` work as before. As before, the DataHandler is not used (no
system fields, no reference index, no cache clearing), and the connection
of the table mapping is used. A functional test compares the rows with
those of the class of earlier versions.

Changed behaviour:

* The rows of a batch are written in one transaction, which is committed
  after the batch. If a task fails, the rows of the current batch are rolled
  back; rows of earlier batches stay. Before, each row was committed when it
  was written, so the rows written before the error stayed in the table.
  Code that calls `persist()` itself must call `persistAll()` or
  `flushBatch()` afterwards, otherwise the rows are not committed.
* Values must be scalar or `null` (arrays in JSON columns and
  `DateTime` objects in date and time columns (as the Doctrine type of
  the column expects) are converted and
  written as before; other arrays and objects are rejected); field names
  must be plain names (letters, digits, underscore); a record must not be
  empty. Such records throw a
  `PersistenceException` (code 1647701464, as other write errors before).
  Before, an array was written as the string `Array`, a field with another
  name was written if the column existed, and an empty record inserted an
  empty row on MySQL (an SQL error on other platforms). Remove or convert
  such fields before the target, e.g. with `unsetKeys` or a pre-processor.
* Values are converted to the types of their columns, as before: e.g. `''`
  and `'2.5'` become `0` and `2` in an integer column, `false` becomes `''`
  and `true` `'1'` in a string column. A record with only `__identity` does
  not change the row (before: an SQL error).
* With an ID map (`idMap` of the task, core), the processor asks the target
  for the `uid` of each record. A record skipped by `skip` has none: the task
  fails with a `LogicException` (code 1796600002). The processor asks for
  the IDs after `flushBatch()`, so the batch with the skipped record is
  already committed when the task fails. Use `disable` instead of `skip` in
  tasks with an ID map. Before 4.0, an ID map could not be used
  with `DataTargetDB`.
* The configuration is checked like the one of `DataTargetDBAL`: `table`
  must be a plain name, `connection` must not be empty, `key` must be a list
  of field names and `idField` a plain name. An invalid configuration makes
  `isConfigurationValid()` return false and `persist()` throw an
  `InvalidConfigurationException` with the code 1796600001.
* `config.connection` selects the connection. It had no effect before, but
  was shown in an example of the documentation; remove it or check that it
  names the connection of the table. The options `pid`, `mode` and
  `updateField` of that example still have no effect.

Breaking changes for code that uses the class:

* `DataTargetDB` extends `CPSIT\ImportExportCore\Dbal\DataTargetDBAL`
  instead of `Component\AbstractComponent`; it implements
  `DisableableInterface` with `Component\DisableableTrait`, so `disable`
  works as before. It is `BatchAwareInterface`, `FailureAwareInterface` and
  `IdentifyingTargetInterface` of the core.
* The constructor takes `Dbal\Typo3ConnectionProvider` instead of
  `ConnectionPool` and `DatabaseConnectionService`. The methods
  `getDatabase()`, `getDataBase()` and `getDatabaseConnectionService()` and
  the properties of `DatabaseTrait` are removed.
* `persist()` is declared as `persist(array|object $object, ?array
  $configuration = null): bool`. It returns `false` for a skipped record and
  `true` otherwise, as before (the core returns whether a row was
  inserted). Without configuration, the configuration of the component is
  used. Objects throw an `UnexpectedValueException` (before: a `TypeError`).
* `persistAll()` and `flushBatch()` commit the rows; `fail()` rolls them
  back.
* The constants `MISSING_CONNECTION_MESSAGE` and `MISSING_CONNECTION_CODE`
  are not used any more. A connection that is not configured throws a
  `MissingResourceException` (code 1795351767).

## Deprecated in 4.0

### Files

The file sources and targets moved into `cpsit/import-export-core` 0.3
([#144](https://github.com/dwenzel/t3import_export/issues/144), core
[#25](https://github.com/CPS-IT/import-export-core/issues/25)). The classes of
the extension extend them and will be removed in 5.0.

| Deprecated | Replacement |
|---|---|
| `Persistence\DataSourceCSV` | `CPSIT\ImportExportCore\File\DataSourceCSV` |
| `Persistence\DataSourceXML` | `CPSIT\ImportExportCore\File\DataSourceXML` |
| `Persistence\DataTargetCSV` | `CPSIT\ImportExportCore\File\DataTargetCSV` |
| `Persistence\DataSourceDummy` | `CPSIT\ImportExportCore\Persistence\NullDataSource` |
| `Persistence\DataTargetDummy` | `CPSIT\ImportExportCore\Persistence\NullDataTarget` |

Paths are resolved as before (`EXT:`, relative to the public directory,
absolute inside the project), see [Files](./Persistence/Files.md). Changes
that also apply to the deprecated classes:

* `DataSourceCSV`: a row with more or fewer values than fields is skipped and
  reported as error 1795200501 (before: PHP error). The constructor takes a
  `ResourceLoaderInterface` instead of a `ConfigurationValidatorInterface`.
  The file is no longer checked when the configuration is validated, but when
  it is read.
* `DataSourceXML`:
  * an empty element becomes `''` instead of `[]`
  * text next to attributes or child elements is kept in `@value` (before:
    lost); a record element with only text becomes `['@value' => 'text']`
    (before: `[0 => 'text']`)
  * child elements in other namespaces are included
  * an empty or invalid file throws a `ParseException` (before: no records,
    or a PHP exception)
  * `expression` or `element` is required; `element` reads large files
    element by element
* `DataTargetCSV`: writes each record when it is persisted, instead of all
  records in `persistAll()`. With `fields`, a file with only the header row is
  written if there are no records (before: no file).

Rules for files and URLs (see [Files](./Persistence/Files.md)); they apply
to the classes of the core and the deprecated ones:

* Files must be in an allowed directory, by default `fileadmin/` and
  `var/import/` (before: the whole project). Configure others in
  `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories']`.
* `EXT:` paths can only be read.
* TYPO3's `fileDenyPattern` and, if enabled, `enforceAllowedFileExtensions`
  apply.
* The CSV components accept only `csv`, `tsv` and `txt` files, the XML source
  only `xml` files.
* URLs are loaded only from the hosts in
  `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']`;
  by default from none (before: all hosts).
* The rules also apply to `ValidateXML` (pre-processor and finisher),
  `DataTargetXMLStream` (`template`), `GenerateFileResource` and
  `GenerateUploadFile` (source file; the target file only for its file type)
  and `ResourcePathConfigurationValidator`. Relative source paths of
  `GenerateFileResource` and `GenerateUploadFile` are relative to the public
  directory. See [Files](./Persistence/Files.md#other-components).
* `DataTargetFileStream` and `DataTargetXMLStream` write their temporary file
  into `transient/t3import_export/` of the var directory (before:
  `typo3temp/tx_importexport_/` of the public directory, which the web server
  may deliver). The constant `DataTargetFileStream::TEMP_DIRECTORY` is no longer
  used and deprecated; subclasses can override `getTemporaryDirectory()`.

Changes when replacing the deprecated classes with those of the core:

* `DataSourceXML` of the core evaluates a relative `expression` relative to
  the document; the deprecated class relative to the root element. Use an
  absolute expression, e.g. `//item` or `/rss/channel/item`.
* `DataTargetCSV` of the core writes no byte order mark (`bom: false`) and
  always a header row (`header: true`); the deprecated class writes a byte
  order mark, and the header only with `fields`. Set `bom` and `header` to keep
  the file as it is.

### Data stream

`ArrayToXMLStream`, `DataTargetFileStream` and `DataTargetXMLStream` use the
data stream of `cpsit/import-export-core`
([#71](https://github.com/dwenzel/t3import_export/issues/71)). The classes of
the extension extend those of the core and will be removed in 5.0.

| Deprecated | Replacement |
|---|---|
| `Domain\Model\DataStream` | `CPSIT\ImportExportCore\Domain\Model\DataStream` |
| `Domain\Model\DataStreamInterface` | `CPSIT\ImportExportCore\Domain\Model\DataStreamInterface` |

`targetClass: CPSIT\T3importExport\Domain\Model\DataStream` keeps working
and writes a message to the deprecation log. Changes:

* The content is kept in a PSR-7 stream (`getStream()`, `setStream()`),
  in memory up to 2 MB and in a temporary file beyond. `getStreamBuffer()`
  and `setStreamBuffer()` are deprecated in the core and still work.
* `getStreamBuffer()` returns `null` for an empty stream, also before
  anything was set (before: an `Error`, because the property was not
  initialised). `setStreamBuffer('')` and `setStreamBuffer(null)` both set
  an empty stream.
* `ArrayToXMLStream` writes into the stream of the target class and rewinds
  it. It checks the interface of the core, which the deprecated interface
  extends.
* `DataTargetFileStream` and `DataTargetXMLStream` accept every object with
  the interface of the core. They rewind the stream if it is seekable and
  read it in chunks of 8 KB. With `flush`, the stream is closed and replaced
  by an empty one (before: the buffer was set to `null`). A stream that is
  not readable throws a `RuntimeException` (code 1795500101).
* Classes that extend `Domain\Model\DataStream`: the property `$buffer` is
  gone, overrides of `setStreamBuffer()` and `getStreamBuffer()` are no
  longer called (the components use `getStream()`), and constructors must
  call `parent::__construct()`.
* Custom classes that implement `Domain\Model\DataStreamInterface` must
  implement `getStream()` and `setStream()`; extend the `DataStream` of the
  core instead.

### Options of the queue source and target

`DataSourceQueue` and `DataTargetQueue` accept the options of earlier
versions and write a message to the deprecation log
([#132](https://github.com/dwenzel/t3import_export/issues/132)). They will
not be accepted in 5.0.

| Deprecated | Replacement |
|---|---|
| `DataSourceQueue`: `batchSize` | `limit` (integer); `batchSize` is used if `limit` is not set |
| `DataSourceQueue::KEY_BATCH_SIZE`, `DEFAULT_BATCH_SIZE` | `KEY_LIMIT`, `DEFAULT_LIMIT` |
| `DataTargetQueue`: `allowUpdate` as string or integer (e.g. `'1'`) | `allowUpdate: true` or `false`. Strings and integers are treated as false: before 4.0 they had practically no effect, while `allowUpdate: true` now sets items that are in the queue back to new. Remove the option to keep the previous behaviour. |
| `DataTargetQueue`: field `identifier` in the record, used as queue | the option `identifier` of the target |

```yaml
# before
source:
  class: CPSIT\T3importExport\Persistence\DataSourceQueue
  config:
    identifier: import.events
    batchSize: 50
target:
  class: CPSIT\T3importExport\Persistence\DataTargetQueue
  config:
    identifier: import.events
    allowUpdate: '1'

# after: allowUpdate removed, as '1' had practically no effect before;
# set allowUpdate: true only if items in the queue should be set back to new
source:
  class: CPSIT\T3importExport\Persistence\DataSourceQueue
  config:
    identifier: import.events
    limit: 50
target:
  class: CPSIT\T3importExport\Persistence\DataTargetQueue
  config:
    identifier: import.events
```

### SQL in the options of DataSourceDB

SQL in the options of `DataSourceDB` is read by a fallback with the query
builder of TYPO3, which builds the same query as before 4.0, and writes a
message to the deprecation log
([#132](https://github.com/dwenzel/t3import_export/issues/132)). The
fallback will be removed in 5.0. It concerns:

* `where` as string
* `join`, `alias` and `groupBy`
* field names with table or alias (`p.uid`), `AS` or functions in `fields`
  and `orderBy`
* `limit` with offset (`10, 20`) or a value that is not a positive integer
* a `table` that is not a plain name

Comma-separated `fields` and `orderBy` with plain field names and a numeric
`limit` are not deprecated; they are converted into the options of the core.

Selecting the connection with `identifier` of the component is deprecated
as well; use `config.connection`.

| Deprecated | Replacement |
|---|---|
| `where: 'pid = 1 AND deleted = 0'` | `where: {pid: 1, deleted: 0}` |
| `where: 'pid IN (1, 2)'` | `where: {pid: [1, 2]}` |
| `where: 'tstamp > 1700000000'`, `<`, `<>`, `IS NULL` | `filter: {field: tstamp, op: '>', value: 1700000000}`; groups with `all`, `any`, `not` |
| `where` with `strtotime()` in an expression | `filter` with a relative date, e.g. `value: {date: '-P1D', from: now}` |
| `join` to filter by a related table | `filter: {exists: {table: ..., match: {...}}}` |
| `join` to read fields of a related table | a pre-processor: `LookUpDB` (fields of a related record) or `RelationListPreProcessor` of the core (IDs of an MM relation) |
| `groupBy`, `alias`, SQL in `fields` or `orderBy` | no replacement in the core; a view in the database, or a pre-processor |
| `identifier: Legacy` next to `class` | `config.connection: Legacy` |

```yaml
# before
source:
  class: CPSIT\T3importExport\Persistence\DataSourceDB
  identifier: Legacy
  config:
    table: tx_news_domain_model_news
    fields: 'uid,title'
    where: 'pid IN (42,43) AND hidden = 0 AND deleted = 0'
    orderBy: 'datetime DESC'

# after
source:
  class: CPSIT\T3importExport\Persistence\DataSourceDB
  config:
    table: tx_news_domain_model_news
    connection: Legacy
    fields: [uid, title]
    where:
      pid: [42, 43]
      hidden: 0
      deleted: 0
    orderBy:
      datetime: DESC
```

The values of `where` and `filter` are passed to the database as
parameters. Keep SQL in configurations of the fallback free of values from
records or user input; the configuration must come from trusted sources, as
before.

### Records of DataTargetDB

A record for `DataTargetDB` with `__identity` and another `uid` changes the
`uid` of the row `__identity`. The core cannot change the field that
identifies a row, so a fallback writes the row with the connection of TYPO3,
as before 4.0, in the transaction of the batch, and writes a message to the
deprecation log
([#132](https://github.com/dwenzel/t3import_export/issues/132)). The
fallback will be removed in 5.0. Change such `uid` values in the database
directly, or remove `uid` from the record (e.g. with `unsetKeys: uid`).

The option `identifier` in the configuration of `DataTargetDB` never had an
effect. It writes a message to the deprecation log and will not be accepted
in 5.0; use `connection` to select a connection.

| Deprecated | Replacement |
|---|---|
| `__identity: 12` with `uid: 34` in a record | update `uid` in the database directly; remove `uid` from the record |
| `config.identifier: Legacy` | `config.connection: Legacy` |

### XML components

`ValidateXML` (pre-processor and finisher) and `XMLMapper` moved into
`cpsit/import-export-core` 0.6
([#154](https://github.com/dwenzel/t3import_export/issues/154), core
[#25](https://github.com/CPS-IT/import-export-core/issues/25)). The classes
of the extension write a message to the deprecation log, work as before
through 4.x and will be removed in 5.0.

| Deprecated | Replacement |
|---|---|
| `Component\PreProcessor\ValidateXML` | `CPSIT\ImportExportCore\Processor\ValidateXMLPreProcessor` |
| `Component\PreProcessor\XMLMapper` | `CPSIT\ImportExportCore\Processor\XMLMapperPreProcessor` |
| `Component\Finisher\ValidateXML` | `CPSIT\ImportExportCore\File\ValidateXMLFinisher` |

The extension registers the classes of the core, so that they read files
and URLs with the rules of the extension (see
[Files](./Persistence/Files.md)): `EXT:` paths, paths relative to the public
directory, the allowed directories (`fileDirectories`) and the allowed hosts
(`allowedHosts`). This also applies to the schemas named in `xs:include`,
`xs:import`, `xs:redefine` and `xs:override`.

The options keep their names, so in most tasks only `class` changes:

| Deprecated class, option | Core class, option |
|---|---|
| `ValidateXML` (pre-processor), `fields: 'a,b'` | `ValidateXMLPreProcessor`, `fields: 'a,b'` or `fields: [a, b]` |
| `ValidateXML` (pre-processor), `identifier` (default `uid`) | `ValidateXMLPreProcessor`, `identifier` (default `uid`, must be a non-empty string) |
| `ValidateXML` (pre-processor or finisher), `schema: {file: ...}` | the same; with the finisher also `schema: {url: ...}` (the deprecated finisher ignores `schema.url`) |
| `ValidateXML` (pre-processor), `schema: {url: ...}` | `ValidateXMLPreProcessor`, `schema: {url: ...}`; `schema` must name exactly one of `file` and `url` |
| `ValidateXML` (finisher), `file` or `url` | `ValidateXMLFinisher`, `file` or `url` (exactly one of them) |
| `ValidateXML` (finisher), `target.schema` (only checked, not used) | none, remove it |
| `XMLMapper`, `fields` | `XMLMapperPreProcessor`, `fields` (not empty) |

```yaml
# before
preProcessors:
  10:
    class: "CPSIT\\T3importExport\\Component\\PreProcessor\\ValidateXML"
    config:
      fields: "body"
      schema:
        file: "EXT:my_extension/Resources/Private/Schema/body.xsd"
  20:
    class: "CPSIT\\T3importExport\\Component\\PreProcessor\\XMLMapper"
    config:
      fields:
        id: "@attribute"
finishers:
  10:
    class: "CPSIT\\T3importExport\\Component\\Finisher\\ValidateXML"
    config:
      file: "fileadmin/exports/news.xml"
      schema:
        file: "fileadmin/schemas/news.xsd"

# after
preProcessors:
  10:
    class: "CPSIT\\ImportExportCore\\Processor\\ValidateXMLPreProcessor"
    config:
      fields: "body"
      schema:
        file: "EXT:my_extension/Resources/Private/Schema/body.xsd"
  20:
    class: "CPSIT\\ImportExportCore\\Processor\\XMLMapperPreProcessor"
    config:
      fields:
        id: "@attribute"
finishers:
  10:
    class: "CPSIT\\ImportExportCore\\File\\ValidateXMLFinisher"
    config:
      file: "fileadmin/exports/news.xml"
      schema:
        file: "fileadmin/schemas/news.xsd"
```

Differences when replacing the deprecated classes with those of the core:

* Schema files:
  * the schema itself must have the file type `xsd` (before: only the
    included schemas). If the schema or a schema it includes has another
    type, an `InvalidConfigurationException` (1795200801) is thrown (before:
    `MissingResourceException` 1795300201 for included schemas)
  * with the TYPO3 feature `security.system.enforceAllowedFileExtensions`,
    `xsd` must be in `$GLOBALS['TYPO3_CONF_VARS']['SYS']['miscfile_ext']`;
    otherwise the schema is rejected (`MissingResourceException` 1646301113
    of the pre-processor, 1795200901 of the finisher). This also applies to
    the deprecated classes
  * a schema is loaded and checked once per task. A schema that is not a
    valid XML schema stops the task with a `ParseException` (pre-processor
    1795200903, finisher 1795200904); before, the pre-processor logged an
    error for each record and the finisher reported the file as invalid
  * a schema that is missing, empty or outside of the allowed directories
    and hosts, or that includes such a schema, throws a `MissingResourceException` with the code 1646301113
    (pre-processor) or 1795200901 (finisher). Before: 1646301113 or 1508914547
    for the schema itself, 1795300201 for included schemas, and the
    `InvalidArgumentException` of the path rules (1795300101 to 1795300103)
    for a schema path that is not allowed
  * `schemaLocation` in `xs:include` and the other elements is resolved
    relative to the naming schema only. Use relative locations in schemas
    named by an `EXT:` path or a path relative to the public directory;
    `EXT:` locations in `schemaLocation`, and relative locations in schemas
    named by an absolute path, cannot be loaded. An empty `schemaLocation`
    throws a `ParseException`
  * schemas that import a URL, e.g. `http://www.w3.org/2001/xml.xsd`, need
    the host in `allowedHosts` (as before) or a local copy of the imported
    schema next to the schema
* `ValidateXMLPreProcessor`:
  * `fields` may also be a list
  * a missing field or a value that is neither a string nor a `DOMDocument`
    makes the record invalid without a PHP warning; a missing identifier
    field is reported as empty ID
  * the record is passed on in any case, with `xmlValidationFailed: true` if
    a field is invalid; the deprecated class returned `false` in addition
  * the message of error 1646304431 has no indentation and the libxml
    message is trimmed; its description is `XML is invalid` (before: `XML ist
    invalid`)
  * the libxml error handling of PHP is restored after each record
* `ValidateXMLFinisher`:
  * `file` must have the file type `xml`; exactly one of `file` and `url` is
    required. Paths and hosts are checked when the task runs (before: also
    when the configuration was validated). A file that is missing or not
    allowed is logged as error 1508914547, like an empty file
  * the file is read element by element instead of being loaded into memory
  * the document is not validated against a DTD it contains. Without a
    schema, a well-formed document is valid (before: a document without
    `DOCTYPE` was reported as invalid)
  * the additional information of notice 1508776068 is a list of strings
    (`Line 3, column 5: ...`) instead of `LibXMLError` objects; the title of
    notice 1508914030 is `Validation succeeded`
  * error 1508774170 (`target.schema` not a string) is not used
* `XMLMapperPreProcessor`:
  * `fields` must not be empty; every rule must be `@attribute`, `@value`,
    `@cdata`, `@separateRow` or a combination separated by `|`; `mapTo`
    must be a non-empty string and `'@separateRow'` as key must have the
    value `true` (before: `'@separateRow': '@separateRow'`)
  * a field that is missing in the record is skipped (before: `@value` was
    set to `null` with a PHP warning); `@separateRow` is only added to
    arrays
* The classes of the core implement the interfaces of the core
  (`process(array $record): array`, `finish(TaskContext $context)`) and are
  `final`. Subclasses of the deprecated classes have to be changed into
  own components or decorators.

## Deprecated in 3.1

| Deprecated | Replacement |
|---|---|
| Tasks and sets in TypoScript (`module.tx_t3importexport.settings`) | YAML files, see [YAML configuration](./YAML_CONFIGURATION.md). The backend modules list tasks and sets from YAML since 3.1 and show a notice for tasks and sets from TypoScript. |
| Pre-processor `RenderContent` | Pre-processor [EvaluateExpressions](./Components/PreProcessors/EVALUATE_EXPRESSIONS.md) (contains a migration table; in 4.0 `EvaluateExpressionsPreProcessor` of the core) |
| `disable` rendered as content object (`disable = TEXT`) | `disable.expression`, see [Components](./COMPONENT.md#by-expression) |
| `DataSourceDB` query values rendered as content objects | `{expression: ...}`, see [DataSourceDB](./Persistence/DataSourceDB.md) |
| `ConcatenateFields` constructor arguments (`ContentObjectRenderer`, `TypoScriptService`) | none, the arguments are ignored |
| `RenderContentTrait::getTypoScriptFrontendController()`, `assertTypoScriptFrontendController()` (since 3.0.1) | none |

`disable = 1` and `disable.if.result.hasMessage` are not deprecated.

## Deprecated in 3.2

These pre-processors have equivalents in `cpsit/import-export-core` 0.2 and are
removed in version 4.0 ([#132](https://github.com/dwenzel/t3import_export/issues/132)).
They keep working in 3.x and do not write to the deprecation log, because 3.x
requires core 0.1, which does not contain the replacements yet. Switch to the
core components when updating to 4.0.

| Deprecated | Replacement (core 0.2) | Differences |
|---|---|---|
| `AddArrays` | `Processor\AddArraysPreProcessor` | same configuration; a target value that is no array becomes the first element |
| `Clean` | `Processor\CleanPreProcessor` | same configuration; unknown options and incomplete `str_replace` options are rejected, `false` disables an option |
| `ImplodeArray` | `Processor\ImplodeArrayPreProcessor` | same configuration |
| `StringToTime` | `Processor\StringToTimePreProcessor` | same configuration |
| `UnsetEmptyFields` | `Processor\UnsetEmptyFieldsPreProcessor` | same configuration |
| `SetFieldValue` | `Processor\SetValuesPreProcessor` | `values: {field: value}`, several fields at once |
| `MapFields` | `Processor\ValueRulesPreProcessor`, rule `copy` | one rule per field |
| `MapFieldValues` | `Processor\ValueRulesPreProcessor`, rules `copy` and `map` | see below |
| `RemoveFields` | `Processor\ValueRulesPreProcessor`, rule `remove` | nested fields as paths: `address.street`, `children.*.hidden` |
| `ConcatenateFields` | `Processor\ValueRulesPreProcessor`, rules `wrap` and `join` | see below |

Data sources and targets (`DataSourceDB`, `DataTargetDB`, queue, dummies) and
`LookUpDB` are not deprecated yet; they are decided with the adoption of core
0.2 ([#71](https://github.com/dwenzel/t3import_export/issues/71)).

### Migrating to `ValueRulesPreProcessor`

The rules are applied in the given order. A value is empty if the field is
missing, `null` or `''`.

```yaml
preProcessors:
  # before
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\MapFields"
    config:
      fields:
        title: header
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\MapFieldValues"
    config:
      fields:
        category:
          targetField: pid
          values: {1: 16, 2: 14}
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\ConcatenateFields"
    config:
      targetField: address
      fields:
        street: {wrap: "|<br>"}
        zip: {}
        city: {noTrimWrap: "| ||"}
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\RemoveFields"
    config:
      fields:
        category: 1
        children: {children: {hidden: 1}}

  # after (4.0)
  - class: "CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor"
    config:
      rules:
        - {rule: copy, field: header, from: title}
        - {rule: copy, field: pid, from: category, when: {field: category, in: [1, 2]}}
        - {rule: map, field: pid, values: {1: 16, 2: 14}, when: {field: category, in: [1, 2]}}
        - {rule: wrap, field: street, suffix: "<br>"}
        - {rule: wrap, field: city, prefix: " "}
        - {rule: join, field: address, from: [address, street, zip, city], separator: ""}
        - {rule: remove, fields: [category, children.*.hidden]}
```

* `MapFieldValues`: copy the field to the target field, then map the target
  field. `MapFieldValues` does not touch the target field if no value
  matches; the condition `in` with the mapped values does the same. If the
  target field is the source field, the `copy` rule and the conditions are
  not needed: unmapped values are kept (or replaced by `otherwise`). `map`
  compares the values as strings (`5` and `'5'` match, `'1.0'` and `'1'` do
  not), while `MapFieldValues` compares with `==` and takes the last matching
  value.
* `ConcatenateFields`: wrap each field, then join the target field and the
  fields with an empty separator, because `ConcatenateFields` appends to the
  target field. `wrap` replaces the value of the source field, like
  `ConcatenateFields` does. `wrap` treats `'0'` and `0` as not empty and wraps
  them; `ConcatenateFields` does not.
* `RemoveFields`: a field inside a nested array is a path with dots, `*`
  stands for every element of a list (the `children` option).

## Migration

1. Move the tasks and sets from TypoScript to a YAML file in one of the
   configured directories (`yamlConfigurationDirectories`). The command
   `t3import-export:convert-typoscript` converts them, see below. Version 4.0
   ignores tasks and sets in TypoScript.
2. Replace content objects by expressions (`RenderContent` ->
   `EvaluateExpressions`, `disable = TEXT` -> `disable.expression`,
   `DataSourceDB` values -> `{expression: ...}`).
3. If the queue was used: update the database schema and, before running
   any task, run the upgrade wizard `t3importExport_queueItemMigration`
   (`vendor/bin/typo3 upgrade:run t3importExport_queueItemMigration`). It
   copies the queue items into the queue table of the core, see
   [Persistence](./Persistence.md#migrating-the-queue-items-of-earlier-versions).
   Check tasks that use `uid` of the records of `DataSourceQueue` or fields
   other than `data` in records for `DataTargetQueue`, and replace the
   deprecated options, see [Queue](#queue). Use the commands
   `t3import-export:queue:status` and `t3import-export:queue:release` instead
   of the list of queue items in the backend.
4. Replace SQL in the options of `DataSourceDB` and `identifier` by the
   options of the core, see
   [SQL in the options of DataSourceDB](#sql-in-the-options-of-datasourcedb).
   Until 5.0 the configurations of earlier versions work unchanged.
5. Check tasks with `DataTargetDB` for records with arrays or field names
   that are not plain names, and for code that calls `persist()` without
   `persistAll()`, see [DataTargetDB](#datatargetdb).
6. Check the deprecation log after running the tasks.

## Converting TypoScript settings to YAML

```bash
vendor/bin/typo3 t3import-export:convert-typoscript \
    EXT:my_sitepackage/Configuration/TypoScript/setup.typoscript \
    --constants=EXT:my_sitepackage/Configuration/TypoScript/constants.typoscript \
    --constants=config/sites/main/settings.yaml \
    --output=packages/my_sitepackage/Configuration/ImportExport/tasks.yaml
```

* **files** (arguments): TypoScript files, read in the given order. `@import`
  with `EXT:` paths (also with wildcards) is resolved; relative imports are not.
  Only `module.tx_t3importexport.settings` (tasks and sets of `import` and
  `export`) is converted; everything else is ignored.
* **--constants** (`-c`, repeatable): files with constants. TypoScript files,
  or YAML files such as site settings (a top-level `settings` key is used if
  present). `{$...}` in the settings is replaced by these values; TypoScript
  constants take precedence over YAML values.
* **--output** (`-o`): YAML file to write (extension `.yaml` or `.yml`) in an
  existing directory; symbolic links and `..` segments are not allowed.
  Without it, the YAML is written to the standard output.

The command is meant for the command line; the scheduler does not offer it.
Files with a stream wrapper (e.g. `ftp://`) are not read or written.

The command lists content objects (e.g. `TEXT`) and constants without a value.
Content objects are copied as they are; they work in 3.x only and have to be
replaced by expressions for version 4.0. TypoScript conditions (`[...]`)
are not supported; check settings inside conditions by hand. Check the result
before removing the TypoScript configuration.
