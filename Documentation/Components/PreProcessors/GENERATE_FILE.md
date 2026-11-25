GenerateFileResource and GenerateUploadFile
===========================================

Both PreProcessors copy files into a storage of TYPO3. The source of the files
is taken from a field of the record.

* `GenerateFileResource` copies the file, adds it to the file index and sets a
  file object in the target field.
* `GenerateUploadFile` copies the file and sets its path in the target field.

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
  e.g. `fileadmin/import/` or `https://data.example.org/images/`
* **config.separator** *string* separator of multiple values (default: `,`)
* **config.multipleRows** *bool* process all values of the source field
  instead of only the first one

## Source files

A source is either a local file or an http(s) URL. Values which are not
allowed are not copied; the task fails with an exception (codes 1795400201 to
1795400204).

### Local files

Local files are read only from allowed directories. By default these are
`fileadmin/` of the public directory and `import/` of the `var` directory of
TYPO3. The list can be changed in the system configuration, e.g. in
`config/system/additional.php`:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileSourceDirectories'] = [
    'fileadmin/import',
    '/var/data/import',
];
```

A configured list replaces the default. Relative directories are relative to
the public directory.

Paths with `..` segments, control characters and stream wrappers (e.g.
`php://`, `phar://`, `compress.zlib://`, `data:`) are not allowed. Symbolic
links are resolved: the real path must be inside the real path of an allowed
directory. An allowed directory may itself be a symbolic link.

`EXT:` paths are allowed; the file must be inside the directory of the
extension.

### URLs

URLs are loaded only with `http` and `https` and only from allowed hosts. No
host is allowed by default, so URLs are refused until the hosts are
configured:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = [
    'data.example.org',
    '*.cdn.example.com',
];
```

`*.example.com` allows the subdomains of `example.com`, `*` allows all hosts.

If `config.sourcePath` is a URL, the value of the record must not change its
scheme, host or port (e.g. with `@` or a host suffix): the joined URL is
parsed and checked.

URLs are loaded with the HTTP settings of TYPO3
(`$GLOBALS['TYPO3_CONF_VARS']['HTTP']`, e.g. proxy and timeout). Redirects are
followed by the extension, at most 5; the target of each redirect must be an
http(s) URL of an allowed host, and a redirect from https to http is not
followed. If a URL cannot be loaded, the file is not copied and an existing
target file is not changed.

### Target files

The target file has the name of the source file. Names which match the
`fileDenyPattern` of TYPO3 or its default pattern, server configuration files
(e.g. `.htaccess`), names with a colon and names ending in a dot or space are
refused. SVG files are sanitized like TYPO3 does for uploaded files.

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
