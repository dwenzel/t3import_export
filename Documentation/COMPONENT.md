Components
==========

Import and export tasks are performed by a data transfer processor.

Each task has a data source and target and may request sub-tasks which will be performed by components.

Currently the following types of components are implemented:

* [Initializers](./Components/Initializer.md)
* [PreProcessors](./Components/PREPROCESSOR.md)
* [Converters](./Components/CONVERTER.md)
* [PostProcessors](./Components/POSTPROCESSOR.md)
* [Finishers](./Components/Finisher.md)

A set of standard components is included. You may write your own components and include them by configuration in TypoScript. 

There exist interfaces for each type of component. Your custom class **must** implement one of them.

Please see [Overview](./OVERVIEW.md) for the general application flow. 
A more in-depth description of components can be found in the correspondend sections linked above. There are references for single components too.

## Disable Components
Any component can be disabled.

### Manually
```typo3_typoscript
module.tx_t3importexport.settings.import.tasks.example {
  finishers.30 {
    class = FooFinisher
    config {
      disable = 1
    }
  }
}
```
Disable finisher with key `30` manually

### By Message
```typo3_typoscript
module.tx_t3importexport.settings.import.tasks.example {
  finishers.30 {
    class = FooFinisher
    config {
      disable {
        if.result.hasMessage = 12345,9876
      }
    }
  }
}
```
Disable finisher with key `30` if the task result has a message with one of the IDs  `12345` or `9876`
Messages are added to the task result by components. For instance finisher ValidateXML adds the message with the ID `1508776068` when the validation fails.

### By Rendering Content
If the value is an array and can be interpreted as content object it will be rendered. The result of the rendering will be interpreted as boolean. 

### Example
```
module.tx_t3importexport.settings.import.tasks.exampleTask.preProcessors {
  10 {
    class = FooPreProcessor
    config {
        disable = TEXT
        disable {
            // any TypoScript valid for TEXT object
        }
    }
  }
}
```
If content of `disable` renders to an expression which can be interpreted as `true`, the component is disabled.
Note: The content object renderer receives the current record. Thus conditions (or any other manipulation) for any field of the record are possible too.
