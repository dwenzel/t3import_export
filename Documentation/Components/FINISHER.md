Finisher
========

Finishers are performed after all Initializers, PreProcessors, Converters and
PostProcessors. Finishers of the extension receive their configuration, an
empty array of records (the processor of `cpsit/import-export-core` does not
pass the records to them) and the task result (`TaskResult` of the core).

Finishers are optional. You may configure as many as you need.

Available Finishers:

* [ClearCache](./Finishers/ClearCache.md)
* [DownloadFileStream](./Finishers/DownloadFileStream.md)
* [MoveFile](./Finishers/MoveFile.md)
* [WriteFile](./Finishers/WriteFile.md)
* [ValidateXMLFinisher](./Finishers/ValidateXML.md) (core; replaces the deprecated `ValidateXML`)

You may add your own Finishers. They **must** implement the FinisherInterface
and **may** inherit from AbstractFinisher.

Finishers are configured as a list below `finishers` of a task (`import.tasks.<task identifier>.finishers` or `export.tasks.<task identifier>.finishers`):

```yaml
import:
  tasks:
    exampleTask:
      finishers:
        - class: "Vendor\\Extension\\Component\\MyComponent"
          config:
            # configuration of the component
```
