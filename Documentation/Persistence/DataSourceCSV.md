DataSourceCSV
=============

Reads data from CSV sources into an array of records.  

**Note:** Current implementation handles only small to medium data sizes. Depending on your environment large imports may hit memory or time limits.


## Configuration
In order to keep the configuration lean a few defaults are assumed.
* First line contains field names
* fields are separated by comma `,`
* fields are enclosed by quotation marks `"`
* special characters are escaped by a backslash `\ `

## required
* **class** fully qualified class name, i.e. *CPSIT\T3importExport\Persistence\DataSourceCSV*
* **config.url** or **config.file** One of them must be set (see below)
* **config.url** *string* URL where to fetch CSV file from. Protocol must be present.
* **config.file** *string* Path to CSV file. Allowed are absolute and relative paths which can be reached by the script. 
`EXT:extension_name/path` expressions are evaluated.

## optional
| property               | type   | default | description       | 
| -----------------------|:------:|---------|-------------------|
| **config.fields**      | string | none    | A comma separated list of field names. If set, the first line **must not** contain field names and will be interpreted as record |
| **config.delimiter**   | char   | `,`     | Field delimiter character |
| **config.enclosure**   | char   | `"`     | Field enclosure character |
| **config.escape**      | char   | `\ `    | Escape character |

### Example

#### Simple
Read items from local file:
```
module.tx_t3importexport.settings.import.tasks.exampleTask {
  source {
    class = CPSIT\T3importExport\Persistence\DataSourceCSV
    config {
      file = local/file/path.csv
    }
  }
}
```
#### Extended
```
module.tx_t3importexport.settings.import.tasks.exampleTask {
  source {
    class = CPSIT\T3importExport\Persistence\DataSourceCSV
    config {
      file = local/file/path.csv
      fields = foo,bar,baz
      delimiter = ;
      enclosure = $
      escape = %
    }
  }
}
```

