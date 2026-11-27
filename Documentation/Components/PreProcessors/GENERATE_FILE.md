GenerateFileResource and GenerateUploadFile
===========================================

Both pre-processors copy files into a storage of TYPO3. The source of the
files is taken from a field of the record.

* `GenerateFileResource` copies the file, adds it to the file index and sets a
  file object in the target field. A file with the same name that is already
  in the target directory is used instead; the rules for the source and the
  target name below apply in this case too.
* `GenerateUploadFile` copies the file and sets its path in the target field
  (base path of the storage, `targetDirectoryPath` and file name), or `''` if
  it could not be copied.

## Configuration

### Required
* **class** *string*
  *CPSIT\T3importExport\Component\PreProcessor\GenerateFileResource* or
  *CPSIT\T3importExport\Component\PreProcessor\GenerateUploadFile*
* **config.sourceField** *string* field which contains the file (one or more
  paths or URLs, separated by `config.separator`)
* **config.targetField** *string* field which receives the result
* **config.storageId** *int* uid of the storage
* **config.targetDirectoryPath** *string* existing directory in the storage

### Optional
* **config.sourcePath** *string* prefix for each value of the source field,
  e.g. `fileadmin/import/`, `EXT:my_sitepackage/Resources/Private/Import/` or
  `https://data.example.org/images/`
* **config.separator** *string* separator of multiple values (default: `,`)
* **config.multipleRows** *bool* process all values of the source field
  instead of only the first one

## Source files

A source is either a local file or an http(s) URL. The rules are those of
[Files](../../Persistence/Files.md).

A source which is not allowed throws an exception and stops the task: an
`InvalidArgumentException` (codes 1795300101 to 1795300103) for a file, a
`MissingResourceException` (code 1795200302) for a URL. Records processed
before are already imported, and files copied for them stay in the target
directory. A source which is allowed but cannot be read (missing file, HTTP
error) is not copied; the field is set to `null` (`GenerateFileResource`) or
`''` (`GenerateUploadFile`) and the task goes on. A URL that cannot be loaded
is logged as warning (1795300501).

### Local files

Local files are read only from the allowed directories
(`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories']`,
by default `fileadmin/` of the public directory and `import/` of the `var`
directory), see [Paths](../../Persistence/Files.md#paths). Relative paths are
relative to the public directory.

Local files and `EXT:` paths are read only below the configured
`config.sourcePath`, e.g. `fileadmin/import/images/` or
`EXT:my_sitepackage/Resources/Private/Import/`. The file must be below the
directory of the source path (the source path itself if it ends with `/`,
else its parent directory: `fileadmin/import/img` allows files below
`fileadmin/import/`). Symbolic links are resolved. For an `EXT:` source path
the directory must be inside the extension. Without `config.sourcePath`, the
values of a record can only be URLs; a value which names a local file or an
`EXT:` path is refused (code 1795300101).

### URLs

URLs are loaded only with `http` and `https` and only from the hosts and
ports in `$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts']`
(default: none), see [URLs](../../Persistence/Files.md#urls):

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = [
    'data.example.org',       // default port of the scheme (80, 443)
    'files.example.org:8443', // port 8443 only
    '*.cdn.example.com',      // subdomains, default port
];
```

IPv6 addresses need brackets: `[::1]`, `[::1]:8080`. `*` allows every host
and port, including internal addresses; host names are not resolved or
checked against IP ranges.

The path of a URL `config.sourcePath` is not a boundary: a value of a record
may contain `../` and reach other paths on the same host. If
`config.sourcePath` is a URL, the value cannot change its scheme, host, port
or user information (e.g. with `@` or a host suffix).

Redirects are followed by the loader of the core, at most 5; the target of
each redirect must be an http(s) URL of an allowed host and port, and a
redirect from https to http is not followed. A redirect to a host that is not
allowed throws an exception (code 1795200302) and stops the task; a redirect
to a port that is not allowed is logged as warning (1795300501), and the file
is not copied.

### Target files

The target file has the name of the source file; for URLs the last segment
of the path, without query and fragment. The name must be allowed for written
files (see [Names of written files](../../Persistence/Files.md#names-of-written-files)):
the default `fileDenyPattern` of TYPO3 applies in addition to a configured
one, server configuration files (e.g. `.htaccess`, `.user.ini`), names with
`?`, `#`, `:` or control characters and names ending in a dot or space are
refused (code 1795400103). Compressed SVG files (`.svgz`) are refused.

The content is written to a temporary file in the target directory, which
replaces the target file when it is complete; if the source cannot be read or
the file cannot be written, the target file is not changed.

SVG files are sanitized with the SVG sanitizer of TYPO3. A file counts as SVG,
whatever its name, if its extension is `svg`, if the content has the MIME
type `image/svg+xml` (detected with `finfo`), or if the content is markup
(it starts with `<` after a byte order mark and whitespace) with an `svg`
element within the first megabyte, e.g. after a long comment. This is a best
effort. Other HTML, XHTML and XML files are not sanitized; check them before
they are served from a public directory.

## Example

```yaml
preProcessors:
  - class: 'CPSIT\T3importExport\Component\PreProcessor\GenerateFileResource'
    config:
      sourceField: 'image'
      targetField: 'image'
      sourcePath: 'fileadmin/import/images/'
      storageId: 1
      targetDirectoryPath: 'user_upload/images/'
      multipleRows: true
```
