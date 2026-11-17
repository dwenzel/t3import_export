PreProcessor
=============

PreProcessors are performed for *each record* of a task *after* Initializers and before Converters, PostProcessors and Finishers.
They receive their configuration and an array containing the **current** record.
PreProcessors prepare the incoming record array for either the following components.
PreProcessors are optional. You may configure as many as you need.

Available PreProcessors:
* [AddArrays](./PreProcessors/ADD_ARRAYS.md) (deprecated)
* [Clean](./PreProcessors/CLEAN.md) (deprecated)
* [ConcatenateFields](./PreProcessors/CONCATENATE_FIELDS.md) (deprecated)
* [EvaluateExpressions](./PreProcessors/EVALUATE_EXPRESSIONS.md)
* [ImplodeArray](./PreProcessors/IMPLODE_ARRAY.md) (deprecated)
* [LookUpDB](./PreProcessors/LOOK_UP_DB.md)
* [MapFields](./PreProcessors/MAP_FIELDS.md) (deprecated)
* [MapFieldValues](./PreProcessors/MAP_FIELD_VALUES.md) (deprecated)
* [RemoveFields](./PreProcessors/REMOVE_FIELDS.md) (deprecated)
* [RenderContent](./PreProcessors/RENDER_CONTENT.md) (deprecated)
* [SetFieldValue](./PreProcessors/SET_FIELD_VALUE.md) (deprecated)
* [StringToTime](./PreProcessors/STRING_TO_TIME.md) (deprecated)
* [UnsetEmptyFields](./PreProcessors/UnsetEmptyFields.md) (deprecated)
* [XMLMapper](./PreProcessors/XML_MAPPER.md)

You may add your own PreProcessors. They **must** implement the PreProcessorInterface and **may** inherit from AbstractInitializer.

PreProcessors for import tasks are configured at the TypoScript path:

```
module.tx_t3importexport.settings.import.tasks.\<task identifier\>.preProcessors
```
The path for export tasks is:
```
module.tx_t3importexport.settings.export.tasks.\<task identifier\>.preProcessors
```
