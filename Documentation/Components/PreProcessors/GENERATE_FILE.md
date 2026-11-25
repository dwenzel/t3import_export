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
  e.g. `fileadmin/import/`, `EXT:my_sitepackage/Resources/Private/Import/` or
  `https://data.example.org/images/`
* **config.separator** *string* separator of multiple values (default: `,`)
* **config.multipleRows** *bool* process all values of the source field
  instead of only the first one

## Source files

A source is either a local file or an http(s) URL.

A source which is not allowed throws an exception (codes 1795400201 to
1795400204) and stops the task. Records processed before are already
imported, and files copied for them stay in the target directory. A source
which is allowed but cannot be read (missing file, HTTP error) is not copied;
the field is set to `null` (`GenerateFileResource`) or `''`
(`GenerateUploadFile`) and the task goes on.

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

A configured list replaces the default list, it does not extend it: add
`fileadmin` again if it is still needed. Relative directories are relative to
the public directory.

Paths with `..` segments, control characters and stream wrappers (e.g.
`php://`, `phar://`, `compress.zlib://`, `data:`) are not allowed. Symbolic
links are resolved: the real path must be inside the real path of an allowed
directory. An allowed directory may itself be a symbolic link.

`EXT:` paths are allowed only as `config.sourcePath`, e.g.
`EXT:my_sitepackage/Resources/Private/Import/`. The file must then be below
the directory of the source path (the source path itself if it ends with `/`,
else its parent directory), and that directory must be inside the extension.
A value of a record which is an `EXT:` path is refused.

### URLs

URLs are loaded only with `http` and `https` and only from allowed hosts. No
host is allowed by default, so URLs are refused until the hosts are
configured:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = [
    'data.example.org',
    'files.example.org:8443',
    '*.cdn.example.com',
];
```

* `example.org` allows this host with the default port of the scheme only
  (80 for http, 443 for https).
* `example.org:8443` allows this host with port 8443 only. List an entry for
  each port which is needed.
* `*.example.com` allows the subdomains of `example.com` (not `example.com`
  itself), `*.example.com:8443` with port 8443.
* `*` allows every host and port, including internal addresses such as
  `127.0.0.1` or `169.254.169.254`.

Host names are compared as they are written; there is no check of IP
addresses or DNS. Do not list names which resolve to internal addresses.

The path of a URL `config.sourcePath` is not a boundary: a value of a record
may contain `../` and reach other paths on the same host. If `config.sourcePath`
is a URL, the value cannot change its scheme, host or port (e.g. with `@` or a
host suffix): the joined URL is parsed and checked.

URLs are loaded with the HTTP settings of TYPO3
(`$GLOBALS['TYPO3_CONF_VARS']['HTTP']`, e.g. proxy and timeout). Redirects are
followed by the extension, not by the HTTP client, at most 5; the target of
each redirect must be an http(s) URL of an allowed host and port, and a
redirect from https to http is not followed. If a URL cannot be loaded, the
file is not copied and an existing target file is not changed.

### Target files

The target file has the name of the source file; for URLs the last segment of
the path, without query and fragment. Names which match the `fileDenyPattern`
of TYPO3 or its default pattern, server configuration files (e.g.
`.htaccess`), names with a colon, `?` or `#` and names ending in a dot or space
are refused. Compressed SVG files (`.svgz`) are refused.

SVG files are sanitized with the SVG sanitizer of TYPO3. A file counts as SVG
if its extension is `svg` or if the content has the MIME type `image/svg+xml`,
whatever its name. Other file types, e.g. HTML or XML files, are not
sanitized; check them before they are served from a public directory.

## Example

```
preProcessors {
    10 {
        class = CPSIT\T3importExport\Component\PreProcessor\GenerateFileResource
        config {
            sourceField = image
            targetField = image
            sourcePath = fileadmin/import/images/
            storageId = 1
            targetDirectoryPath = user_upload/images/
            multipleRows = 1
        }
    }
}
```
