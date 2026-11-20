DataTargetCSV
=============

Writes records into a CSV file, one row per record. Since 4.0 the target of
`cpsit/import-export-core` is used:

* **class**: `CPSIT\ImportExportCore\File\DataTargetCSV`
* Options: [core reference](https://github.com/CPS-IT/import-export-core/blob/main/Documentation/Configuration/Components.md#datatargetcsv)

The file is created, or replaced, when the first record is written. Missing
directories are created.

## Options

| Option | Type | Default | Description |
|---|---|---|---|
| `file` | string | – | Path of the file, see [Files](./Files.md#paths) |
| `fields` | list or comma-separated string | fields of the first record | Fields to write, in this order |
| `header` | bool | true | Writes the field names in the first row |
| `delimiter` | char | `,` | |
| `enclosure` | char | `"` | |
| `escape` | char | `\` | `''` for none |
| `bom` | bool | false | Starts the file with a UTF-8 byte order mark, e.g. for spreadsheet applications |

## Example

```yaml
target:
  class: 'CPSIT\ImportExportCore\File\DataTargetCSV'
  config:
    file: 'fileadmin/export/news.csv'
    fields: 'uid,title,datetime'
    delimiter: ';'
    bom: true
```

## Deprecated class

`CPSIT\T3importExport\Persistence\DataTargetCSV` is deprecated since 4.0
and extends the class of the core. It keeps the defaults of the class before
4.0: `bom: true`, and `header` only with `fields`. When replacing the
class, set these options if the file must not change. See
[Deprecations](../DEPRECATIONS.md#files).
