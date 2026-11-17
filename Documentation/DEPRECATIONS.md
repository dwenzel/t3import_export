Deprecations
============

TypoScript configuration and rendering content with TypoScript are deprecated
since version 3.1 and will be removed in version 4.0 (together with support
for TYPO3 14). Reasons and plan:
[#75](https://github.com/dwenzel/t3import_export/issues/75).

Deprecated features write a message to the TYPO3 deprecation log (each message
once per request). Enable the log in the Install Tool (Settings > Configuration
Presets > Debug, or `$GLOBALS['TYPO3_CONF_VARS']['LOG']['TYPO3']['CMS']['deprecations']`).

## Deprecated in 3.1

| Deprecated | Replacement |
|---|---|
| Tasks and sets in TypoScript (`module.tx_t3importexport.settings`) | YAML files, see [YAML configuration](./YAML_CONFIGURATION.md). The backend modules list tasks and sets from YAML since 3.1 and show a notice for tasks and sets from TypoScript. |
| Pre-processor `RenderContent` | Pre-processor [EvaluateExpressions](./Components/PreProcessors/EVALUATE_EXPRESSIONS.md) (contains a migration table) |
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
Content objects are copied as they are (they still work in 3.x) and have to be
replaced by expressions before version 4.0. TypoScript conditions (`[...]`)
are not supported; check settings inside conditions by hand. Check the result
before removing the TypoScript configuration.
