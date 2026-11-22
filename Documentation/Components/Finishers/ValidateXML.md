ValidateXMLFinisher
===================

Validates an XML file after the task, e.g. the file written by the target,
and optionally against an XML schema (XSD). The finisher does **not** stop
the task or change the file if the validation fails. Instead a message is
logged (see _Messages_).

Use `CPSIT\ImportExportCore\File\ValidateXMLFinisher` of
`cpsit/import-export-core`. The finisher of the extension,
`CPSIT\T3importExport\Component\Finisher\ValidateXML`, is deprecated since
4.0 and will be removed in 5.0, see
[Deprecations](../../DEPRECATIONS.md#xml-components).

## Examples

### Validate XML from file

```yaml
import:
  tasks:
    exampleTask:
      finishers:
        10:
          class: "CPSIT\\ImportExportCore\\File\\ValidateXMLFinisher"
          config:
            file: fileadmin/foo/bar/Export.xml
            schema:
              file: EXT:my_extension/Resources/Private/Schema/Export.xsd
```

### Validate XML from URL

The host must be in the allowed hosts (see [Files](../../Persistence/Files.md)).

```yaml
import:
  tasks:
    exampleTask:
      finishers:
        10:
          class: "CPSIT\\ImportExportCore\\File\\ValidateXMLFinisher"
          config:
            url: https://typo3.org/xml-feeds/rss.xml
```

### Options

| option             | type   | required | description |
|--------------------|--------|----------|-------------|
| config.file        | string | *        | Path to the XML file (type `xml`): `EXT:` path, path relative to the public directory or absolute path in the allowed directories. |
| config.url         | string | *        | URL of the XML file, from the allowed hosts. |
| config.schema.file | string |          | Path to an XML schema (type `xsd`) to validate against. |
| config.schema.url  | string |          | URL of an XML schema to validate against. |

\* Exactly one of **config.file** and **config.url** must be set.

Paths and URLs follow the rules of the extension, see
[Files](../../Persistence/Files.md). Schemas named in `xs:include`,
`xs:import`, `xs:redefine` or `xs:override` are loaded with the same rules,
relative to the schema that names them; they must be `xsd` files. Use
relative locations in schemas named by an `EXT:` path or a path relative to
the public directory. With the feature
`security.system.enforceAllowedFileExtensions`, `xsd` must be an allowed
file extension of TYPO3 (e.g. in `$GLOBALS['TYPO3_CONF_VARS']['SYS']['miscfile_ext']`).

### Validation

The file is read element by element, so large files are not held in memory.
Without a schema, a well-formed document is valid. A DTD in the document is
not used, and external DTDs and external entities are not loaded.

A schema that cannot be loaded, is empty, includes a schema that cannot be
loaded or is not a valid XML schema stops the task with an exception (see
_Errors_).

### Messages

**Notices**

| ID         | title                | message                                    | additional information |
|------------|----------------------|--------------------------------------------|------------------------|
| 1508776068 | Validation failed    | XML is invalid. There were [count] errors. | Validation errors (`Line 3, column 5: ...`) |
| 1508914030 | Validation succeeded | XML is valid.                              | |

**Errors**

| ID         | title          | message                                   |
|------------|----------------|-------------------------------------------|
| 1508914547 | Empty resource | Could not load resource or resource empty |

**Exceptions**

| Code       | Exception                          | Cause |
|------------|------------------------------------|-------|
| 1795200901 | `MissingResourceException`         | The schema or a schema it includes cannot be loaded, is empty or is not allowed. |
| 1795200904 | `ParseException`                   | The schema is not a valid XML schema. |
| 1795200801 | `InvalidConfigurationException`    | `file`, the schema or a schema it includes has a file type that is not allowed. |

## Deprecated: Component\Finisher\ValidateXML

`CPSIT\T3importExport\Component\Finisher\ValidateXML` takes the same `file`,
`url` and `schema.file` options; `schema.url` is not used. It loads the whole
document into memory and validates it against the DTD of the document
(libxml option `LIBXML_DTDVALID`): without a schema, a document without
`DOCTYPE` is reported as invalid. It logs error 1508774170 if
`target.schema` is not a string. The differences are listed in
[Deprecations](../../DEPRECATIONS.md#xml-components).
