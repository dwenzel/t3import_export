Components
==========

Import and export tasks are performed by a data transfer processor.

Each task has a data source and target and may request sub-tasks which will be
performed by components.

Currently the following types of components are implemented:

* [Initializers](./Components/Initializer.md)
* [PreProcessors](./Components/PREPROCESSOR.md)
* [Converters](./Components/CONVERTER.md)
* [PostProcessors](./Components/POSTPROCESSOR.md)
* [Finishers](./Components/Finisher.md)

A set of standard components is included. You may write your own components and
include them by configuration (YAML or TypoScript).

There exist interfaces for each type of component. Your custom class **must**
implement one of them.

Please see [Overview](./OVERVIEW.md) for the general application flow.
A more in-depth description of components can be found in the correspondent
sections linked above. There are references for single components too.

## Disable Components
Any component can be disabled with the option `disable` in its `config`.

### Manually
```yaml
import:
  tasks:
    example:
      finishers:
        30:
          class: 'FooFinisher'
          config:
            disable: 1
```
`disable: 1` and `disable: true` disable the finisher with key `30`.

### By Message
```yaml
          config:
            disable:
              if:
                result:
                  hasMessage: '12345,9876'
```
Disables the component if the task result has a message with one of the IDs
`12345` or `9876`. Messages are added to the task result by components. For
instance finisher ValidateXML adds the message with the ID `1508776068` when
the validation fails.

### By Expression
```yaml
          config:
            disable:
              expression: "record['type'] == 'news'"
```
The expression is evaluated with the
[Symfony ExpressionLanguage](https://symfony.com/doc/current/reference/formats/expression_language.html).
The component is disabled if the result is true. Variables:

* `record`: the current record (array). Use `??` for fields that may be
  missing: `(record['hidden'] ?? 0) == 1`
* `result`: the task result or `null`, e.g. `result != null and result.hasMessageWithId(12345)`

An invalid expression throws an `InvalidConfigurationException`.

### By Rendering Content (deprecated)
If the value is an array and can be interpreted as a content object, it will be
rendered. The result of the rendering will be interpreted as boolean.

Rendering content with TypoScript will be removed in version 4.0 (see
[#75](https://github.com/dwenzel/t3import_export/issues/75)). Use an
expression instead.

```typo3_typoscript
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
If content of `disable` renders to `1`, the component is disabled. The content
object renderer receives the current record. Data that needs a frontend page
(`getData` with `TSFE:` or `page:`, references like `< lib.foo`) is not
available.
