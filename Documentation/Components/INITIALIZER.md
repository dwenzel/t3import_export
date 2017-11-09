Initializer
===========

Initializers are performed for each task *before* any other transformation (i.e. PreProcessors, Converter, PostProcessors and Finishers).
They receive their configuration and an array containing **all** records which where fetched from source for this task.

Currently the following initializers exist:
* [DeleteFromTable](./Initializers/DeleteFromTable.md)
* [InsertMultiple](./Initializers/InsertMultiple.md)
* [TruncateTables](./Initializers/TruncateTables.md)
* [UpdateTable](./Initializers/UpdateTable.md)

You may add your own initializers. They **must** implement the InitializerInterface and **may** inherit from AbstractInitializer.

Initializers for import tasks are configured at the TypoScript path:

```
module.tx_t3importexport.settings.import.tasks.\<task identifier\>.initializers
```
The path for export tasks is:
```
module.tx_t3importexport.settings.export.tasks.\<task identifier\>.initializers
```
