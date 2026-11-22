XMLMapper
=========

Marks fields of a record for writing as XML with `DataTargetXMLStream`: as
attribute, as text or CDATA section of the parent element, under another
element name, or as separate row.

Use `CPSIT\ImportExportCore\Processor\XMLMapperPreProcessor` of
`cpsit/import-export-core`. The pre-processor of the extension,
`CPSIT\T3importExport\Component\PreProcessor\XMLMapper`, is deprecated since
4.0 and will be removed in 5.0, see
[Deprecations](../../DEPRECATIONS.md#xml-components).

## Example

```yaml
preProcessors:
  10:
    class: "CPSIT\\ImportExportCore\\Processor\\XMLMapperPreProcessor"
    config:
      fields:
        id: "@attribute"
        teaser: "@cdata"
        author:
          mapTo: "creator"
        categories:
          children:
            uid: "@attribute"
```

The record `{id: 1, teaser: 'Text', author: 'Jane', categories: [{uid: 4}]}`
becomes:

```yaml
teaser: {'@value': 'Text', '@cdata': true}
author: {'@value': 'Jane', '@mapTo': 'creator'}
categories: [{'@attribute': {uid: 4}}]
'@attribute': {id: 1}
```

## Options

`fields` (map of field => rule, required, not empty). Rules:

| Rule | Effect |
|---|---|
| `'@attribute'` | the field is moved into `@attribute` of its parent |
| `'@value'` | the field is moved into `@value` of its parent, i.e. it becomes the text of the parent element |
| `'@cdata'` | the field becomes `{@value: value, @cdata: true}`; if the field is missing and the parent has `@value`, the parent gets `@cdata: true` |
| `'@value\|@cdata'` | `@value`, then `@cdata` |
| `'@separateRow'` | the field (an array) gets `@separateRow: true` |
| a map of field => rule | the rules apply inside the field. A field with a string or number becomes `{@value: value}` first. In the map, `mapTo: name` adds `@mapTo: name` (the element name in the XML), `'@separateRow': true` adds `@separateRow: true`, and `children: {field: rule}` applies the rules to every array in the field, e.g. to every element of a list |

Fields that are missing in the record are skipped.
