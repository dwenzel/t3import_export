Finisher ValidateXML
====================

Validates XML. The XML is loaded from a resource (file or URL).
Note: This finisher will **not** stop the execution of the current task, if validation fails. Instead a message is logged. (see: _Messages_)

## Examples

### Validate XML from file 
```
module.tx_t3importexport.settings.import.tasks.exampleTask {
  preProcessors.20 {
    class = CPSIT\T3importExport\Component\Finisher\ValidateXML
    config {
      file = fileadmin/foo/bar/Export.xml
    }
  }
}
```
### Validate XML from URL   

```
module.tx_t3importexport.settings.import.tasks.exampleTask {
  preProcessors.20 {
    class = CPSIT\T3importExport\Component\Finisher\ValidateXML
    config {
      url = https://typo3.org/xml-feeds/rss.xml
    }
  }
}
```
### Options
| option                    | type    | required |description         |
| --------------------------| ------- |-----------|----------- |
| config.url                | string  | *       | URL where to fetch XML from. Protocol must be present. |
| config.file               | string  | *       | Path to XML file. Allowed are absolute and relative path which can be reached by the script. `EXT:extension_name/path` expressions are evaluated.|

\* **config.url** or **config.file** One of them must be set (see below)

### Messages

**Notices**

| ID          | title               | message                     | additional information             |
| ------------|---------------------|-------------------------|--------------------------|
| 1508776068  | Validation failed   | XML is invalid. There were [count] errors.| Validation errors|
| 1508914030  | Validation succeed  | XML is valid.           |    |

**Errors**

| ID          | title               | message                 |
| ------------|---------------------|-------------------------|
| 1508774170  | Invalid type for target schema | config['target']['schema'] must be a string, [type] given.] |
| 1508914547  | Empty resource                 | Could not load resource or resource empty |

