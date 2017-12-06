Post Processor
==============

PostProcessors are performed for *each record* of a task *after* Initializers, PreProcessors and Converters and *before* Finishers.
They receive their configuration and an object (or array containing the **current** record).

PostProcessors are optional. You may configure as many as you need.

Available PreProcessors:
* [GenerateFileReference](./PostProcessors/GenerateFileReferenc.md)
* SetHiddenProperties
* TranslateObject

You may add your own PostProcessors. They **must** implement the PostProcessorInterface and **may** inherit from AbstractPostProcessor.

PostProcessors for import tasks are configured at the TypoScript path:

```
module.tx_t3importexport.settings.import.tasks.<task identifier>.postProcessors
```
The path for export tasks is:
```
module.tx_t3importexport.settings.export.tasks.<task identifier>.postProcessors
```
