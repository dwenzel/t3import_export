PostProcessor TranslateObject
=============================

Makes the converted domain object a translation of an existing record.

### Configuration Example

```yaml
postProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\TranslateObject"
    config:
      parentField: "parent_import_id"   # field of the source record with the parent's uid
      language: 1                       # language uid of the translation
```

Looks up the record whose uid is the value of `parentField` in the source
record (default language, storage page and enable fields ignored). If it is
found, the converted object gets this record as translation origin and the
language `language`. If `parentField` is empty or no record is found, the
object is not changed.

### Options

| option             | type    | required | description                                      |
|--------------------|---------|----------|--------------------------------------------------|
| config.parentField | string  | yes      | field of the source record with the parent's uid |
| config.language    | integer | yes      | language uid of the translation                  |

### Requirements

The domain model must declare a property for the translation origin field of
its table (`ctrl.transOrigPointerField` in TCA, e.g. `l10n_parent` →
property `l10nParent`). Extbase only persists declared properties.

If the property is missing, an `InvalidColumnMapException` with code
`1794749385` is thrown. Up to version 3.1.1, the import went on without an
error, but the translation was saved without a link to its origin.
