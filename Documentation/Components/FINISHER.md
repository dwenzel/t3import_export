Finisher
========

Finishers are performed after all Initiallizers, PreProcessors, Converters and PostProcessors.
Every finisher receives all records *and* the result containing the result of processing and conversion.

Finishers are optional. You may configure as many as you need.

Available Finishers:

* [ClearCache](./Finishers/ClearCache.md)
* DownloadFileStream
* [MoveFile](./Finishers/MoveFile.md)
* [WriteFile](./Finishers/WriteFile.md)
* [ValidateXML](./Finishers/ValidateXML.md)

You may add your own Finishers. They **must** implement the FinisherInterface and **may** inherit from AbstractFinisher.

Finishers for import tasks are configured at the TypoScript path:

```
module.tx_t3importexport.settings.import.tasks.\<task identifier\>.finishers
```
The path for export tasks is:
```
module.tx_t3importexport.settings.export.tasks.\<task identifier\>.finishers
```
