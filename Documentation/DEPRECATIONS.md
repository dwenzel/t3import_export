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
