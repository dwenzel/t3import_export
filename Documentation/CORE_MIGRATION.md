# Components and the core library

Overview of the extension's components with respect to
[cpsit/import-export-core](https://github.com/CPS-IT/import-export-core):
which can move to the core, which are replaced by core components, which stay
in the extension and which are removed. The plan was made for core 0.2 (see
the core's `Documentation/Architecture.md`); the column "Prerequisite" and
"Differences" show the state of version 4.0, which requires core ^0.7.

A component moves only when its behaviour is covered by tests. The tests are
ported with the component, so that the core version is checked against the
same cases.

Coverage state: 2026-11-16. Line coverage of unit and functional tests together,
measured on `feature/116-typo3-v15` with TYPO3 14.3 and PHP 8.4 (63.9 % in
total). To measure it again in the DDEV bundle:

```bash
ddev xdebug on
ddev ext test:coverage
ddev xdebug off
```

## Move to the core

Generic components without TYPO3 dependencies (apart from
`GeneralUtility::trimExplode()`).

| Component | Coverage | Prerequisite | Issue |
|---|---|---|---|
| `PreProcessor\AddArrays` | 100 % | removed in 4.0, core `AddArraysPreProcessor` | [core#68] |
| `PreProcessor\StringToTime` | 100 % | removed in 4.0, core `StringToTimePreProcessor` | [core#68] |
| `PreProcessor\UnsetEmptyFields` | 100 % | removed in 4.0, core `UnsetEmptyFieldsPreProcessor` | [core#68] |
| `PreProcessor\XMLMapper` | 99 % | moved in 4.0 (core 0.6), deprecated | [core#25] |
| `PreProcessor\Clean` | 0 % | removed in 4.0, core `CleanPreProcessor` | [core#68] |
| `PreProcessor\ImplodeArray` | 0 % | removed in 4.0, core `ImplodeArrayPreProcessor` | [core#68] |
| `PreProcessor\EvaluateExpressions`, `Expression\ExpressionEvaluator` | 100 % | moved in 4.0, removed from the extension | [core#24] |
| `PreProcessor\ValidateXML` | 0 % | moved in 4.0 (core 0.6), deprecated | [core#25] |
| `Finisher\ValidateXML` | 97 % | moved in 4.0 (core 0.6), deprecated | [core#25] |
| `Converter\ArrayToXMLStream` | 81 % | Extbase property mapper for `targetClass` separated | [core#25] |
| `Persistence\DataSourceCSV` | 100 % | moved in 4.0 (core 0.3), deprecated subclass | [core#25] |
| `Persistence\DataSourceXML` | 73 % | moved in 4.0 (core 0.3), deprecated subclass | [core#25] |
| `Persistence\DataTargetCSV` | 0 % | moved in 4.0 (core 0.3), deprecated subclass | [core#25] |
| `Persistence\DataTargetFileStream` | 34 % | tests, [#130]; path abstraction | [core#25] |
| `Persistence\DataTargetXMLStream` | 0 % | test covers the wrong class, [#130] | [core#25] |
| `Validation\Configuration\MappingConfigurationValidator`, `IssetValidator`, `NotEmptyValidator` | 100 / 100 / 38 % | tests for `NotEmptyValidator`, [#130] | [core#24] |

## Replace with core components

The extension's tests define the behaviour that the core component must have.
Tracked in [#132].

| Extension | Coverage | Core | Differences |
|---|---|---|---|
| `PreProcessor\MapFields` | 100 % | `ValueRulesPreProcessor` `copy` / `rename` | removed in 4.0 |
| `PreProcessor\MapFieldValues` | 65 % | `ValueRulesPreProcessor` `map`, `otherwise` | removed in 4.0; values are compared as strings, see [Deprecations](./DEPRECATIONS.md#migrating-to-valuerulespreprocessor) |
| `PreProcessor\RemoveFields` | 94 % | `ValueRulesPreProcessor` `remove` | removed in 4.0; nested fields as paths (`children.*.hidden`), [core#69] |
| `PreProcessor\ConcatenateFields` | 92 % | `ValueRulesPreProcessor` `wrap` and `join` | removed in 4.0, [core#69] |
| `PreProcessor\SetFieldValue` | 100 % | `SetValuesPreProcessor` | removed in 4.0 |
| `PreProcessor\UnsetEmptyFields` | 100 % | `UnsetEmptyFieldsPreProcessor` (same `empty()` semantics) | removed in 4.0; `ValueRulesPreProcessor` `remove` with `when: {empty: true}` treats only a missing field, `null` or `''` as empty |
| `Persistence\DataSourceDB` | 94 % | `DataSourceDBAL` | adapter in 4.0, stays the default source; `{expression: ...}` query values; SQL in the options (`where` string, `join`, `groupBy`) by a deprecated fallback |
| `Persistence\DataTargetDB` | 70 % | `DataTargetDBAL`, upsert by ID map | adapter in 4.0 (`__identity` -> `uid`, batches in transactions); a `uid` change with `__identity` by a deprecated fallback; ID map table and state components in 4.0 [#132] |
| `Persistence\DataSourceQueue`, `DataTargetQueue`, `Domain\Repository\QueueItemRepository` | 100 / 92 / 74 % | core queue, `DbalQueueRepository` | replaced in 4.0: source and target are adapters, the repository is removed, items are migrated by an upgrade wizard |
| `Persistence\DataSourceDummy`, `DataTargetDummy` | no tests | `NullDataSource`, `NullDataTarget` (4.0, deprecated) | – |
| `PreProcessor\LookUpDB` | 47 % | partly `ResolveReferencePreProcessor` | stays in 4.x; general lookups by query have no core equivalent |
| `Service\DataTransferProcessor`, `Domain\Model\TransferTask`, `TransferSet`, `DataStream`, `Dto\TaskDemand`, task and set factories, component factories, `Factory\FactoryFactory`, `ComponentFactoryMap` | 97–100 % | core processor, models, factory and registry | replaced in 4.0 (`DataStream` deprecated subclass), [#71] |

## Stays in the extension

Extbase, FAL, DataHandler, caches and the backend. Missing tests are tracked in
[#133].

| Component | Coverage |
|---|---|
| `Converter\ArrayToDomainObject` | 92 % |
| `Property\TypeConverter\PersistentObjectConverter` | 24 % |
| `Property\PropertyMappingConfigurationBuilder` | 74 % |
| `Persistence\DataSourceDynamicRepository` | 10 % |
| `Persistence\DataTargetRepository` | 92 % |
| `PostProcessor\TranslateObject`, `Service\TranslationService` | 87 / 92 % |
| `PostProcessor\SetL10nParent` | 0 % |
| `PostProcessor\RecreateSlug` | 0 % |
| `PostProcessor\SetHiddenProperties` | 94 % |
| `PostProcessor\GenerateFileReference` | 49 % |
| `PreProcessor\GenerateFileResource`, `GenerateUploadFile`, `GenerateFileTrait` | 100 % |
| `Finisher\WriteFile` | 100 % |
| `Finisher\MoveFile` | 4 % |
| `Finisher\DownloadFileStream` | 0 % |
| `Finisher\ClearCache` | 100 % |
| `Initializer\DeleteFromTable`, `InsertMultiple`, `TruncateTables` | 100 % |
| `Initializer\UpdateTable` | 28 % |
| Controllers, commands | 20–77 % |

The table initializers could later be implemented on the core's DBAL layer.

## Removed

| Component | State | Replacement |
|---|---|---|
| `PreProcessor\RenderContent`, `RenderContentTrait` (frontend rendering) | deprecated in 3.1, removed in 4.0 ([#75], [#121]) | `ValueRulesPreProcessor`, `SetValuesPreProcessor`, `EvaluateExpressionsPreProcessor`; no replacement for `parseFunc`, `typolink`, `crop` |
| Content objects in `disable` | as above | `disable.expression`, evaluated by the core condition evaluator ([core#24]) |
| Content objects in `DataSourceDB` query values | as above | `{expression: ...}` |
| Constructor arguments of `ConcatenateFields` | as above; `ConcatenateFields` is removed in 4.0 | – |
| TypoScript configuration | deprecated in 3.1, removed in 4.0 ([#75]) | YAML |
| `TypoScriptConverter`, `ConvertTypoScriptCommand` | kept in 4.0 for the migration ([#75]) | – |
| `RenderContentInterface` in the core | [core#70] | – |

See also [Deprecations](./DEPRECATIONS.md).

[#71]: https://github.com/dwenzel/t3import_export/issues/71
[#75]: https://github.com/dwenzel/t3import_export/issues/75
[#121]: https://github.com/dwenzel/t3import_export/pull/121
[#130]: https://github.com/dwenzel/t3import_export/issues/130
[#131]: https://github.com/dwenzel/t3import_export/issues/131
[#132]: https://github.com/dwenzel/t3import_export/issues/132
[#133]: https://github.com/dwenzel/t3import_export/issues/133
[core#24]: https://github.com/CPS-IT/import-export-core/issues/24
[core#25]: https://github.com/CPS-IT/import-export-core/issues/25
[core#68]: https://github.com/CPS-IT/import-export-core/issues/68
[core#69]: https://github.com/CPS-IT/import-export-core/issues/69
[core#70]: https://github.com/CPS-IT/import-export-core/issues/70
