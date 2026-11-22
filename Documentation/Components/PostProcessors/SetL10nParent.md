PostProcessor GenerateFileReference
===================================

Generates and updates file references.

### Configuration Example

```yaml
import:
  tasks:
    example:
      postProcessors:
        - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\SetL10nParent"
          config:
            subject:
              parentField: ln10_parent
            parent:
              table: tx_foo_bar
              identityField: uid
              matchField: import_id
              prefix: RECORD_DE-
```

Finds a localization parent record for `subject` by matching the value
of `subject.parentField` with
the value of `parent.matchField`. An optional value of `parent.prefix` of is
added to the value of
`source.identityField` before matching `parent.matchField`.
`subject.parentField` will be set to the `parent.identityField`

### Options

| option                      | type   | required | description                                                                                               |
|-----------------------------|--------|----------|-----------------------------------------------------------------------------------------------------------|
| config.subject.parentField  | string | yes      | Source field name                                                                                         |
| config.parent.table         | string | yes      | Table name for lookup of localization parent                                                              |
| config.parent.matchField    | string | yes      | Match field for lookup of localization parent                                                             |
| config.parent.identityField | string | no       | Field name to lookup for localization parent.<br/> If not set `uid` will be used.<br/> Must not be empty. |
| config.parent.prefix        | string | no       | Optional prefix for lookup field. Must not be empty.                                                      |

### Example records

**Source**

```php
$subject = [
   'sys_language_uid' => 1,
   'import_id' => 'RECORD_EN-5',
   'l10_parent' => 4
]

$parent = [
  'uid' => 17,
  'import_id' => 'RECORD_DE-4',
  'sys_language_uid' => 0
]
```

**Result**

```php
$subject = [
   'uid' => '<will be set to new internal uid',
   'sys_language_uid' => 1,
   'import_id' => 'RECORD_EN-5',
   'sys_language_uid' => 1,
   'l10n_parent' => 17
]

```
