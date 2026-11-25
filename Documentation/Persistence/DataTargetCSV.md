DataTargetCSV
=============

Writes data to a CSV file.

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
* **config.file** *string* Path of the CSV file. Relative paths are relative to the
  public directory. See [Written files](#written-files).

## Written files
The file is written only if
* its extension is one of the allowed data file types, by default `csv`, `tsv`,
  `txt`, `xml`, `json`, `yaml` and `yml`, and its name does not match the
  `fileDenyPattern` of TYPO3 (`$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']`)
  or its default pattern, contains no colon, `?` or `#` and does not end in a dot
  or space,
* it is inside one of the allowed directories, by default `fileadmin/` and
  `typo3temp/` of the public directory and the `var` directory of TYPO3.

`EXT:` paths, paths with `..` segments and stream wrappers (`scheme://`) are
not allowed. Symbolic links are resolved: the real path must be inside the real
path of an allowed directory. An allowed directory may itself be a symbolic
link, e.g. a `fileadmin` that links to a shared directory.

Both lists can be changed in the system configuration, e.g. in
`config/system/additional.php`:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetExtensions'] = ['csv', 'txt'];
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileTargetDirectories'] = [
    'fileadmin/export',
    '/var/data/export',
];
```

A configured list replaces the default. Relative directories are relative to the
public directory. A file which is not allowed is not written; the task fails
with an exception (codes 1795400101, 1795400102, 1795400103).

## optional
| property               | type   | default | description       |
| -----------------------|:------:|---------|-------------------|
| **config.fields**      | string | all     | A comma separated list of field names. |
| **config.delimiter**   | char   | `,`     | Field delimiter character |
| **config.enclosure**   | char   | `"`     | Field enclosure character |
| **config.escape**      | char   | `\ `    | Escape character |

### Example

#### Simple
Read items from local file:
```
module.tx_t3importexport.settings.export.tasks.exampleTask {
  target {
    class = CPSIT\T3importExport\Persistence\DataTargetCSV
    config {
      file = fileadmin/export/path.csv
    }
  }
}
```
#### Extended
```
module.tx_t3importexport.settings.export.tasks.exampleTask {
  target {
    class = CPSIT\T3importExport\Persistence\DataSourceCSV
    config {
      file = fileadmin/export/path.csv
      fields = foo,bar,baz
      delimiter = ;
      enclosure = $
      escape = %
    }
  }
}
```

