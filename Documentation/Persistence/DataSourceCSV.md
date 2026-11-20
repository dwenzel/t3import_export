DataSourceCSV
=============

Reads records from a CSV file, one record per row. Since 4.0 the source of
`cpsit/import-export-core` is used:

* **class**: `CPSIT\ImportExportCore\File\DataSourceCSV`
* Options: [core reference](https://github.com/CPS-IT/import-export-core/blob/main/Documentation/Configuration/Components.md#datasourcecsv)

The file is read row by row, so large files are not held in memory, see
[Files](./Files.md).

## Options

| Option | Type | Default | Description |
|---|---|---|---|
| `file` | string | – | Path of the file, see [Files](./Files.md#paths). Either `file` or `url`. |
| `url` | string | – | URL of the file (http or https), from an [allowed host](./Files.md#urls) |
| `fields` | list or comma-separated string | – | Field names, in the order of the columns. Without `fields`, the first row holds the field names. |
| `header` | bool | true without `fields`, false with `fields` | Whether the first row holds field names |
| `delimiter` | char | `,` | |
| `enclosure` | char | `"` | |
| `escape` | char | `\` | `''` for none |
| `encoding` | string | `UTF-8` | Encoding of the file, e.g. `Windows-1252`; values are converted to UTF-8 |
| `strict` | bool | false | A row with a wrong number of values ends the task. Otherwise the row is skipped and reported with its row number. |
| `recordNumberField` | string | – | Adds the number of the record as this field, e.g. as source ID |

## Example

```yaml
source:
  class: 'CPSIT\ImportExportCore\File\DataSourceCSV'
  config:
    file: 'fileadmin/import/news.csv'
    fields: 'title,teaser,date'
    delimiter: ';'
    encoding: 'Windows-1252'
```

## Deprecated class

`CPSIT\T3importExport\Persistence\DataSourceCSV` is deprecated since 4.0
and extends the class of the core. Replace the class name; the options are
the same. Differences to the class before 4.0: see
[Deprecations](../DEPRECATIONS.md#files).
