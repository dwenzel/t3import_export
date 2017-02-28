DataSourceXML
=============

Reads data from XML sources into an array of records.  
**Note:** Current implementation handles only small to medium data sizes (since it uses PHPs \SimpleXMLElement class). Depending on your environment large imports may hit memory or time limits.

## Configuration
## required
* **class** fully qualified class name, i.e. *CPSIT\T3importExport\Persistence\DataSourceXML*
* **config.url** or **config.file** One of them must be set (see below)
* **config.expression** *string* A valid xpath expression. Nodes selected by this expression are be converted to records for import
* **config.url** *string* URL where to fetch XML from. Protocol must be present.
* **config.file** *string* Path to XML file. Allowed are absolute and relative path which can be reached by the script. 
`EXT:extension_name/path` expressions are evaluated.

### Example
Fetch all items of the RSS feed as records:
```
module.tx_t3importexport.settings.import.tasks.exampleTask {
  source {
    class = CPSIT\T3importExport\Persistence\DataSourceXML
    config {
      url = https://typo3.org/xml-feeds/rss.xml
      expression = //item
    }
  }
}
```
