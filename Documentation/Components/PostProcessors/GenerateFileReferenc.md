PostProcessor GenerateFileReference
===================================

Generates and updates file references.

### Example

```yaml
import:
  tasks:
    example:
      postProcessors:
        - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\GenerateFileReference"
          config:
            sourceField: image
            targetField: importedImage
            targetPage: 52
```

Generates a file reference in `targetField` for the file id found in
`sourceField`. The file reference record is created in `targetPage`.
The file must exist (i.e., a record with this uid in sys_file).
Existing references in `targetField` are updated.

### Options

| option             | type    | required | description                      |
|--------------------|---------|----------|----------------------------------|
| config.sourceField | string  | yes      | source field name                |
| config.targetField | string  | yes      | target field name                |
| config.targetPage  | integer | no       | ID of the page in which the file |
|                    |         |          | reference should be stored.      |
