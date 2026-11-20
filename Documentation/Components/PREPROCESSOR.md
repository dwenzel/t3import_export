PreProcessor
=============

PreProcessors are performed for *each record* of a task *after* Initializers and before Converters, PostProcessors and Finishers.
They receive their configuration and an array containing the **current** record.
PreProcessors prepare the incoming record array for either the following components.
PreProcessors are optional. You may configure as many as you need.

Available PreProcessors:
* [EvaluateExpressionsPreProcessor](./PreProcessors/EVALUATE_EXPRESSIONS.md) (core)
* [LookUpDB](./PreProcessors/LOOK_UP_DB.md)
* [XMLMapper](./PreProcessors/XML_MAPPER.md)

The pre-processors `AddArrays`, `Clean`, `ConcatenateFields`, `ImplodeArray`,
`MapFields`, `MapFieldValues`, `RemoveFields`, `SetFieldValue`, `StringToTime`
and `UnsetEmptyFields` were removed in 4.0. Use the pre-processors of
`cpsit/import-export-core` (namespace `CPSIT\ImportExportCore\Processor\`)
instead, see [Deprecations](../DEPRECATIONS.md#deprecated-in-32). Lists in
their configuration (e.g. `rules` of `ValueRulesPreProcessor`) must be lists:
in TypoScript, number the entries from 0 without gaps.

You may add your own PreProcessors. They **must** implement the PreProcessorInterface and **may** inherit from AbstractInitializer.

PreProcessors for import tasks are configured at the TypoScript path:

```
module.tx_t3importexport.settings.import.tasks.\<task identifier\>.preProcessors
```
The path for export tasks is:
```
module.tx_t3importexport.settings.export.tasks.\<task identifier\>.preProcessors
```
