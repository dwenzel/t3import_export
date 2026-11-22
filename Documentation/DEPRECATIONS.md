Deprecations
============

TypoScript configuration and rendering content with TypoScript are deprecated
since version 3.1 and will be removed in version 4.0 (together with support
for TYPO3 14). Reasons and plan:
[#75](https://github.com/dwenzel/t3import_export/issues/75).

Deprecated features write a message to the TYPO3 deprecation log (each message
once per request). Enable the log in the Install Tool (Settings > Configuration
Presets > Debug, or `$GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']`).

## Removed in 4.0

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

4.0 requires `cpsit/import-export-core` 0.2, which contains the processor, the
task and set models and the factories
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
  by an empty one (before: the buffer was set to `null`).
* Custom classes that implement `Domain\Model\DataStreamInterface` must
  implement `getStream()` and `setStream()`; extend the `DataStream` of the
  core instead.

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
   `t3import-export:convert-typoscript` converts them, see below.
2. Replace content objects by expressions (`RenderContent` ->
   `EvaluateExpressions`, `disable = TEXT` -> `disable.expression`,
   `DataSourceDB` values -> `{expression: ...}`).
3. Check the deprecation log after running the tasks.

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
* **--output** (`-o`): YAML file to write. Without it, the YAML is written to
  the standard output.

The command lists content objects (e.g. `TEXT`) and constants without a value.
Content objects are copied as they are; they work in 3.x only and have to be
replaced by expressions for version 4.0. TypoScript conditions (`[...]`)
are not supported; check settings inside conditions by hand. Check the result
before removing the TypoScript configuration.
