ValidateXMLPreProcessor
=======================

Checks that fields of a record hold well-formed XML and, with `schema`, that
the XML is valid against an XML schema (XSD).

Use `CPSIT\ImportExportCore\Processor\ValidateXMLPreProcessor` of
`cpsit/import-export-core`. The pre-processor of the extension,
`CPSIT\T3importExport\Component\PreProcessor\ValidateXML`, is deprecated
since 4.0 and will be removed in 5.0, see
[Deprecations](../../DEPRECATIONS.md#xml-components).

## Example

```yaml
preProcessors:
  10:
    class: "CPSIT\\ImportExportCore\\Processor\\ValidateXMLPreProcessor"
    config:
      fields: "body,teaser"
      identifier: "id"
      schema:
        file: "EXT:my_extension/Resources/Private/Schema/body.xsd"
```

## Options

| option             | type                       | required | description |
|--------------------|----------------------------|----------|-------------|
| config.fields      | list or comma-separated string | yes  | Fields to check. |
| config.identifier  | string                     |          | Field with the ID of the record, used in the messages. Default `uid`. |
| config.schema.file | string                     |          | Path to an XML schema (type `xsd`). |
| config.schema.url  | string                     |          | URL of an XML schema, from the allowed hosts. |

Paths and URLs follow the rules of the extension, see
[Files](../../Persistence/Files.md). Schemas named in `xs:include`,
`xs:import`, `xs:redefine` or `xs:override` are loaded with the same rules,
relative to the schema that names them; they must be `xsd` files. Use
relative locations in schemas named by an `EXT:` path or a path relative to
the public directory. With the feature
`security.system.enforceAllowedFileExtensions`, `xsd` must be an allowed
file extension of TYPO3 (e.g. in `$GLOBALS['TYPO3_CONF_VARS']['SYS']['miscfile_ext']`).

## Result

A field is valid if it holds a string or a `DOMDocument` with XML. A missing
field, an empty string or a value of another type is invalid. At the first
invalid field, the record gets the field `xmlValidationFailed` with the value
`true`; the remaining fields are not checked. The record is passed on in any
case; a filter or a `disable` condition on `xmlValidationFailed` can leave it
out.

Each error of the XML parser or the schema validation is logged as error
1646304431 (`Validation Error`), with the field, the record ID and the error
in the message.

The schema is loaded and checked once per task. If the schema or a schema it
includes cannot be loaded, is empty or is outside of the allowed directories
and hosts, a `MissingResourceException` (1646301113) is thrown; if it has a
file type other than `xsd`, an `InvalidConfigurationException` (1795200801);
if the schema is not a valid XML schema, a `ParseException` (1795200903).
All of them stop the task.
