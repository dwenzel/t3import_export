Pre Processor UnsetEmptyFields
==============================

This is a pre processor for import and export tasks.
It un-sets any field which is configured in the configuration and empty in the incoming record.

The following values are considered to be empty:
* "" (an empty string)
* 0 (0 as an integer)
* 0.0 (0 as a float)
* "0" (0 as a string)
* null
* false
* [] (an empty array)

### Example
**Configuration**
```TypoScript
module.tx_t3importexport.settings.import.tasks.events {
  [...]
  preProcessors.30 {
    class = CPSIT\T3importExport\Component\PreProcessor\UnsetEmptyFields
    config {
      fields = foo,bar
    }
  }
  [...]
}
```

Unset field _foo_ and _bar_ of the record, if they are empty (and keep all others even if they are empty)

**Record**

before
```php
[
  foo => [],
  bar => '',
  baz => '',
  boom => 15
]
```

after
```php
[
  baz => '',
  boom => 15
]
```

### Options

| option              | type   | description         |
| --------------------| ------ | ------------------- |
| config.fields   | string | comma separated list of field names which should be unset when empty |
