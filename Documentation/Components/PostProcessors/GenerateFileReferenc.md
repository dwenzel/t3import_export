PostProcessor GenerateFileReference
===================================

Generates and updates file references.

### Example

```typo3_typoscript
module.tx_t3importexport.settings.import.tasks.example {
  postProcessors {
    10 {
      class = CPSIT\T3importExport\Component\PostProcessor\GenerateFileReference
      config {
        sourceField = image
        targetField = importedImage
        targetPage = 52
      }
    }
  }
}
```

Generates a file reference in `targetField` for the file id found in
`sourceField`. The file reference record is created in `targetPage`.
The file must exist (i.e., a record with this uid in sys_file).
Existing references in `targetField` are updated.

Records converted to arrays (for example for `DataTargetDB`) get a record in
`sys_file_reference` instead. It uses `tableName`, `fieldName`, `crop` and
`targetPage`; the uid of the record is read from `identityField`. Records
without this field are skipped. No record is created if a reference with the
same file, uid, `tableName` and `fieldName` exists already. The target field
is set to `1`.

```yaml
postProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\GenerateFileReference"
    config:
      sourceField: "imageId"
      targetField: "image"
      identityField: "uid"
      tableName: "tt_content"
      fieldName: "image"
      targetPage: 52
```

### Options

| option               | type    | required | description                                                               |
|----------------------|---------|----------|---------------------------------------------------------------------------|
| config.sourceField   | string  | yes      | source field name (uid of the file in `sys_file`)                         |
| config.targetField   | string  | yes      | target field name                                                         |
| config.targetPage    | integer | no       | ID of the page in which the file reference should be stored               |
| config.identityField | string  | no       | field with the uid of the record, default `__identity`                    |
| config.tableName     | string  | no       | table of the record (`tablenames` of the reference), default empty        |
| config.fieldName     | string  | no       | field of the record (`fieldname` of the reference), default empty         |
| config.crop          | string  | no       | crop configuration of the reference (array records only), default empty   |
