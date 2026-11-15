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

### Content objects (removed)
Content objects (`disable = TEXT`) are not supported any more since version
4.0 ([#75](https://github.com/dwenzel/t3import_export/issues/75)). A component
with such a configuration throws an `InvalidConfigurationException` (code
1794781862). Use an expression instead.
