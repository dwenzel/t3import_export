# Components and the core library

Overview of the extension's components with respect to
[cpsit/import-export-core](https://github.com/CPS-IT/import-export-core) 0.2:
which can move to the core, which are replaced by core components, which stay
in the extension and which are removed. The direction is described in the
core's `Documentation/Architecture.md` ("Direction for 0.2.0").

A component moves only when its behaviour is covered by tests. The tests are
ported with the component, so that the core version is checked against the
same cases.

State: 2026-11-16. Line coverage of unit and functional tests together,
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
| `PreProcessor\AddArrays` | 100 % | – | [core#68] |
| `PreProcessor\StringToTime` | 100 % | – | [core#68] |
| `PreProcessor\UnsetEmptyFields` | 100 % | undefined key, [#131] | [core#68] |
| `PreProcessor\XMLMapper` | 99 % | – | [core#68] |
| `PreProcessor\Clean` | 0 % | tests, [#130] | [core#68] |
| `PreProcessor\ImplodeArray` | 0 % | tests, [#130] | [core#68] |
| `PreProcessor\EvaluateExpressions`, `Expression\ExpressionEvaluator` | 100 % | moved in 4.0 | [core#24] |
| `PreProcessor\ValidateXML` | 0 % | tests, [#130]; path abstraction | [core#25] |
| `Finisher\ValidateXML` | 97 % | path abstraction | [core#25] |
| `Converter\ArrayToXMLStream` | 81 % | Extbase property mapper for `targetClass` separated | [core#25] |
| `Persistence\DataSourceCSV` | 100 % | path abstraction | [core#25] |
| `Persistence\DataSourceXML` | 73 % | tests, [#130]; path abstraction | [core#25] |
| `Persistence\DataTargetCSV` | 0 % | tests, [#130]; path abstraction | [core#25] |
| `Persistence\DataTargetFileStream` | 34 % | tests, [#130]; path abstraction | [core#25] |
| `Persistence\DataTargetXMLStream` | 0 % | test covers the wrong class, [#130] | [core#25] |
| `Validation\Configuration\MappingConfigurationValidator`, `IssetValidator`, `NotEmptyValidator` | 100 / 100 / 38 % | tests for `NotEmptyValidator`, [#130] | [core#24] |

## Replace with core components

The extension's tests define the behaviour that the core component must have.
Tracked in [#132].

| Extension | Coverage | Core | Differences |
|---|---|---|---|
| `PreProcessor\MapFields` | 100 % | `ValueRulesPreProcessor` `copy` / `rename` | – |
| `PreProcessor\MapFieldValues` | 65 % | `ValueRulesPreProcessor` `map`, `otherwise` | to be checked, tests in [#130] |
| `PreProcessor\RemoveFields` | 94 % | `ValueRulesPreProcessor` `remove` | `children` not supported, [core#69] |
| `PreProcessor\ConcatenateFields` | 92 % | `ValueRulesPreProcessor` `join` | `wrap` / `noTrimWrap` not supported, [core#69] |
| `PreProcessor\SetFieldValue` | 100 % | `SetValuesPreProcessor` | – |
| `PreProcessor\UnsetEmptyFields` | 100 % | `ValueRulesPreProcessor` `remove` with `when: {empty: true}` | core "empty" is missing, `null` or `''`; the extension uses PHP `empty()` |
| `Persistence\DataSourceDB` | 94 % | `DataSourceDBAL` | `{expression: ...}` query values |
| `Persistence\DataTargetDB` | 70 % | `DataTargetDBAL`, upsert by ID map | – |
| `Persistence\DataSourceQueue`, `DataTargetQueue`, `Domain\Repository\QueueItemRepository` | 100 / 92 / 74 % | core queue, `DbalQueueRepository` | table, migration of queue items |
| `Persistence\DataSourceDummy`, `DataTargetDummy` | no tests | `NullComponent` | – |
| `PreProcessor\LookUpDB` | 47 % | partly `ResolveReferencePreProcessor` | general lookups by query |
| `Service\DataTransferProcessor`, `Domain\Model\TransferTask`, `TransferSet`, `DataStream`, `Dto\TaskDemand`, task and set factories, component factories, `Factory\FactoryFactory`, `ComponentFactoryMap` | 97–100 % | core processor, models, factory and registry | [#71] |

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
| `PreProcessor\RenderContent`, `RenderContentTrait` (frontend rendering) | deprecated in 3.1, removed on the TYPO3 15 branch ([#75], [#121]) | `ValueRulesPreProcessor`, `SetValuesPreProcessor`, `EvaluateExpressions`; no replacement for `parseFunc`, `typolink`, `crop` |
| Content objects in `disabled` | as above | `disabled.expression`, later the core condition evaluator ([core#24]) |
| Content objects in `DataSourceDB` query values | as above | `{expression: ...}` |
| Constructor arguments of `ConcatenateFields` | as above | – |
| TypoScript configuration, `TypoScriptConverter`, `ConvertTypoScriptCommand` | deprecated in 3.1 ([#75]) | YAML; the converter may stay one more major version for the migration |
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
