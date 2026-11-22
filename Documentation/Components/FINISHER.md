Finisher
========

Finishers are performed after all Initiallizers, PreProcessors, Converters and
PostProcessors. Every finisher receives all records *and* the result containing
the result of processing and conversion.

Finishers are optional. You may configure as many as you need.

Available Finishers:

* [ClearCache](./Finishers/ClearCache.md)
* DownloadFileStream
* [MoveFile](./Finishers/MoveFile.md)
* [WriteFile](./Finishers/WriteFile.md)
* [ValidateXML](./Finishers/ValidateXML.md)

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
