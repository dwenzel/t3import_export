DataSourceXML
=============

Reads records from an XML file, one record per element. Since 4.0 the source
of `cpsit/import-export-core` is used:

* **class**: `CPSIT\ImportExportCore\File\DataSourceXML`
* Options: [core reference](https://github.com/CPS-IT/import-export-core/blob/main/Documentation/Configuration/Components.md#datasourcexml)

With `element`, the file is read element by element, so large files are not
held in memory. With `expression`, the whole document is loaded and the
records are selected with XPath. See [Files](./Files.md).

Each record element becomes an array: attributes in `@attributes`, child
elements as fields named by their local name (lists for repeated elements,
strings for elements with only text), and text next to attributes or child
elements in `@value`.

## Options

| Option | Type | Description |
|---|---|---|
| `file` | string | Path of the file, see [Files](./Files.md#paths). Either `file` or `url`. |
| `url` | string | URL of the file (http or https), from an [allowed host](./Files.md#urls) |
| `element` | string | Name of the record elements, e.g. `item`. Either `element` or `expression`. |
| `expression` | string | XPath expression selecting the record elements, e.g. `//channel/item` |
| `namespaces` | map | Prefixes and URIs of namespaces used in `element` or `expression` |
| `lists` | list | Child elements that are always lists, also with one or no element |
| `recordNumberField` | string | Adds the number of the record as this field |

## Example

```yaml
source:
  class: 'CPSIT\ImportExportCore\File\DataSourceXML'
  config:
    url: 'https://typo3.org/rss.xml'
    element: 'item'
    lists: ['category']
```

## Deprecated class

`CPSIT\T3importExport\Persistence\DataSourceXML` is deprecated since 4.0
and extends the class of the core. It evaluates a relative `expression`
relative to the root element, as before. Differences to the class before 4.0:
see [Deprecations](../DEPRECATIONS.md#files).
