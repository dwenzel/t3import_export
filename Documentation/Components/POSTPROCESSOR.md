Post Processor
==============

PostProcessors are performed for *each record* of a task *after* Initializers, PreProcessors and Converters and *before* Finishers.
They receive their configuration and an object (or array containing the **current** record).

PostProcessors are optional. You may configure as many as you need.

Available PostProcessors:
* [GenerateFileReference](./PostProcessors/GenerateFileReferenc.md)
* [RecreateSlug](./PostProcessors/RecreateSlug.md)
* SetHiddenProperties
* [SetL10nParent](./PostProcessors/SetL10nParent.md)
* [TranslateObject](./PostProcessors/TranslateObject.md)

You may add your own PostProcessors. They **must** implement the PostProcessorInterface and **may** inherit from AbstractPostProcessor.

PostProcessors are configured as a list below `postProcessors` of a task (`import.tasks.<task identifier>.postProcessors` or `export.tasks.<task identifier>.postProcessors`):

```yaml
import:
  tasks:
    exampleTask:
      postProcessors:
        - class: "Vendor\\Extension\\Component\\MyComponent"
          config:
            # configuration of the component
```
