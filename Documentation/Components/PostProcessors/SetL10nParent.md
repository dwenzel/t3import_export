PostProcessor SetL10nParent
===========================

Sets the localization parent of a converted record (an array, e.g. for
`DataTargetDB`).

### Configuration Example

```yaml
import:
  tasks:
    example:
      postProcessors:
        - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\SetL10nParent"
          config:
            subject:
              parentField: l10n_parent
            parent:
              table: tx_foo_bar
              identityField: uid
              matchField: import_id
              prefix: RECORD_DE-
```

Reads the value of `subject.parentField` of the converted record, puts
`parent.prefix` in front of it and looks for a row in `parent.table` whose
`parent.matchField` has this value. If a row is found, the field
`l10n_parent` of the converted record is set to the value of
`parent.identityField` of that row (and `l10n_source` with
`setL10nSource`). If the value is empty or no row is found, the record is
not changed.

The field that is set is always `l10n_parent`, also if `subject.parentField`
names another field. The query uses the connection that `parent.table` is
mapped to and the default restrictions of TYPO3: deleted and hidden parents,
and parents outside their start and end time, are not found.

For domain objects (after `ArrayToDomainObject`) use
[TranslateObject](./TranslateObject.md).

### Options

| option                      | type   | required | description |
|-----------------------------|--------|----------|-------------|
| config.subject.parentField  | string | yes      | field of the converted record with the value to match |
| config.parent.table         | string | yes      | table of the localization parent |
| config.parent.matchField    | string | yes      | field of the parent table that is compared with the value |
| config.parent.identityField | string | yes      | field of the parent row that is written to `l10n_parent`, e.g. `uid`. Without it the configuration is invalid. |
| config.parent.prefix        | string | no       | prefix put in front of the value before matching. Must not be empty. |
| config.setL10nSource        | bool   | no       | also set `l10n_source` |

### Example records

**Converted record**

```php
$record = [
   'sys_language_uid' => 1,
   'import_id' => 'RECORD_EN-5',
   'l10n_parent' => 4,
];
```

**Parent row** in `tx_foo_bar`

```php
$parent = [
  'uid' => 17,
  'import_id' => 'RECORD_DE-4',
  'sys_language_uid' => 0,
];
```

**Result**

```php
$record = [
   'sys_language_uid' => 1,
   'import_id' => 'RECORD_EN-5',
   'l10n_parent' => 17,
];
```
