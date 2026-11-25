PreProcessor
=============

PreProcessors are performed for *each record* of a task *after* Initializers and before Converters, PostProcessors and Finishers.
They receive their configuration and an array containing the **current** record.
PreProcessors prepare the incoming record array for either the following components.
PreProcessors are optional. You may configure as many as you need.

Available PreProcessors:
* [EvaluateExpressionsPreProcessor](./PreProcessors/EVALUATE_EXPRESSIONS.md) (core)
* [LookUpDB](./PreProcessors/LOOK_UP_DB.md)
* `TargetIdPreProcessor`, `ResolveReferencePreProcessor` (core): see
  [ID map and state components](../Persistence/IdMap.md)
* `RelationListPreProcessor` (core): see [Persistence](../Persistence.md#database-components-of-the-core)
* [ValidateXMLPreProcessor](./PreProcessors/VALIDATE_XML.md) (core; replaces the deprecated `ValidateXML`)
* [XMLMapperPreProcessor](./PreProcessors/XML_MAPPER.md) (core; replaces the deprecated `XMLMapper`)

The pre-processors `AddArrays`, `Clean`, `ConcatenateFields`, `ImplodeArray`,
`MapFields`, `MapFieldValues`, `RemoveFields`, `SetFieldValue`, `StringToTime`
and `UnsetEmptyFields` were removed in 4.0. Use the pre-processors of
`cpsit/import-export-core` (namespace `CPSIT\ImportExportCore\Processor\`)
instead, see [Deprecations](../DEPRECATIONS.md#deprecated-in-32). Lists in
their configuration (e.g. `rules` of `ValueRulesPreProcessor`) must be lists.

You may add your own PreProcessors. They **must** implement the PreProcessorInterface and **may** inherit from AbstractPreProcessor.

PreProcessors are configured as a list below `preProcessors` of a task (`import.tasks.<task identifier>.preProcessors` or `export.tasks.<task identifier>.preProcessors`):

```yaml
import:
  tasks:
    exampleTask:
      preProcessors:
        - class: "Vendor\\Extension\\Component\\MyComponent"
          config:
            # configuration of the component
```
