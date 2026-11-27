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
  `https://data.example.org/images/`. Required for local files and `EXT:`
  paths: without a local source path, values of records can only be URLs.
* **config.separator** *string* separator of multiple values (default: `,`)
* **config.multipleRows** *bool* process all values of the source field
  instead of only the first one

## Source files

A source is either a local file or an http(s) URL.

A source which is not allowed throws an exception (codes 1795400201 to
1795400204, 1795400103 for target names) and stops the task.
`GenerateFileResource` checks the source and the target name also if a file
with the same name exists in the target directory already. Records processed before are already
imported, and files copied for them stay in the target directory. A source
which is allowed but cannot be read (missing file, HTTP error) is not copied;
the field is set to `null` (`GenerateFileResource`) or `''`
(`GenerateUploadFile`) and the task goes on.

### Local files

Local files and `EXT:` paths are read only with a local `config.sourcePath`
(not a URL). The joined path must start with the source path, and the file
must be below the directory of the source path:

* the source path itself if it ends with `/` or is an existing directory,
  e.g. `fileadmin/import/` or `fileadmin/import`;
* else (a prefix of file names, e.g. `fileadmin/import/img_`) its parent
  directory, here `fileadmin/import/`.

Without `config.sourcePath`, or with a URL as source path, a value of a record
which is a local path or an `EXT:` path is refused (code 1795400201); a file
outside of the directory of the source path is refused with code 1795400202.
Symbolic links are resolved: a link which leads out of the directory is
refused.

In addition, local files are read only from allowed directories. By default these are
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
the directory of the source path (see above), and that directory must be
inside the extension. A value of a record which is an `EXT:` path is refused.

Upgrade note: configurations whose records contain local paths need a
`config.sourcePath` now, e.g. `sourcePath: 'fileadmin/import/'` with values
relative to it (each value is appended to the source path). Absolute paths
in records need a source path such as `/var/data/import/`.

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
* IPv6 addresses need brackets: `[::1]` (default port), `[::1]:8080`.
  `*:port` (all hosts with one port) is not supported.
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
are refused. The last file extension must not be empty and must consist of
letters and digits only (e.g. `page.html;a=b`, `page%2ehtml` and `README` are
refused). Compressed SVG files (`.svgz`, in any position of the name, e.g.
`image.svgz.txt`) are refused. All these names are
refused with code 1795400103.

Files of the types `html`, `htm`, `xhtml`, `xht`, `shtml`, `shtm`, `js` and
`mjs` are refused by default, in any position of the name and without case
(e.g. `PAGE.HTML`, `image.jpg.html`, `page.html.zzz`; web servers may serve
a file by any of its extensions), also for names taken from URLs. To write
them, e.g. for trusted sources, allow them explicitly:

```php
// config/system/additional.php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets'] = true;
```

Only the boolean `true` allows them. `shtml` and `shtm` stay refused by the
default `fileDenyPattern` of TYPO3. The setting applies to
`GenerateFileResource` and `GenerateUploadFile` only; the rules of
`WriteFile`, `MoveFile` and `DataTargetCSV` do not change. The content of
these files is not sanitized.

SVG files are sanitized with the SVG sanitizer of TYPO3. A file counts as SVG
if its name has the extension `svg` in any position (e.g. `image.svg.txt`),
or if the root element of the content is an `svg`
element (also with a namespace prefix), whatever its name: the first element
after a byte order mark, whitespace, the XML declaration, processing
instructions, comments and the document type, within the first megabyte. The
root element is read with XMLReader (no network access, no DTD loading, no
entity substitution); for content XMLReader cannot read, a simple scan is
used, which treats a document type with an internal subset as unknown. If the
root element is unknown, the MIME type `image/svg+xml` (detected with `finfo`)
decides. This is a best effort. If the sanitizer leaves an empty file, e.g.
for content that is no valid SVG, a warning is logged (1795300502).

HTML, XHTML and XML files with another root element are not sanitized, also if
they contain an inline `svg` element. If XML content contains elements of the
XHTML or SVG namespace or an `xml-stylesheet` processing instruction, a warning
is logged (1795300503) and the file is written unchanged: browsers can run
scripts in such files. This check is a limit, not a protection; allow such
files only from trusted sources and check them before they are served from a
public directory.

Upgrade note: target file names containing `?` or `#` are now refused
(exception code 1795400103), like names with a colon and names ending in a dot
or space. This affects `file` of `DataTargetCSV`, `target.name` of `WriteFile`
and `MoveFile`, and the names of local source files of `GenerateFileResource`
and `GenerateUploadFile` (e.g. `report#1.csv`, `a?b.csv`). Rename such files
or targets before upgrading.

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
