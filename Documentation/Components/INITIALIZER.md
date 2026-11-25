Initializer
===========

Initializers are performed for each task *before* any other transformation
(i.e. PreProcessors, Converter, PostProcessors and Finishers), after the
records of the task were read from the source. Initializers of the extension
receive their configuration and an empty array of records: the processor of
`cpsit/import-export-core` does not pass the records to them.

Currently the following initializers exist:
* [DeleteFromTable](./Initializers/DeleteFromTable.md)
* [InsertMultiple](./Initializers/InsertMultiple.md)
* [TruncateTables](./Initializers/TruncateTables.md)
* [UpdateTable](./Initializers/UpdateTable.md)

You may add your own initializers. They **must** implement the
`InitializerInterface` and **may** inherit from AbstractInitializer.

Initializers are configured as a list below `initializers` of a task (`import.tasks.<task identifier>.initializers` or `export.tasks.<task identifier>.initializers`):

```yaml
import:
  tasks:
    exampleTask:
      initializers:
        - class: "Vendor\\Extension\\Component\\MyComponent"
          config:
            # configuration of the component
```
