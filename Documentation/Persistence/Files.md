Files
=====

The file sources and targets of `cpsit/import-export-core`
(`DataSourceCSV`, `DataSourceXML`, `DataTargetCSV`) do not open files
themselves. The extension registers the services they use in
`Configuration/Services.yaml`.

## Paths

`file` is resolved by `CPSIT\T3importExport\Resource\Typo3PathResolver`,
like other file names in TYPO3, and must be in one of the allowed
directories:

| Path | Resolved to |
|---|---|
| `fileadmin/import/news.csv` | relative to the public directory |
| `/var/www/project/var/import/news.csv` | absolute path |
| `EXT:my_extension/Resources/Private/Import/news.csv` | the file in the extension; only for reading |

Allowed directories, by default `fileadmin/` of the public directory and
`import/` of the var directory (`var/import/` in Composer installations):

```php
// config/system/additional.php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories'] = [
    'fileadmin/import/',                                        // relative to the public directory
    \TYPO3\CMS\Core\Core\Environment::getVarPath() . '/import', // absolute
];
```

Rejected, with an exception before the file is opened or created:

* paths outside of the allowed directories, e.g. `config/system/settings.php`
  or other files of the public directory
* `EXT:` paths in targets
* paths with `..`, and stream wrappers (`php://`, `phar://` and others)
* file names matching `$GLOBALS['TYPO3_CONF_VARS']['BE']['fileDenyPattern']`,
  by default executable files such as `.php` or `.phar`, also as `a.php.csv`
* with the feature `security.system.enforceAllowedFileExtensions`: file
  extensions that are not in `textfile_ext`, `mediafile_ext` or
  `miscfile_ext` of `$GLOBALS['TYPO3_CONF_VARS']['SYS']`
* in targets (`DataTargetCSV`): file names that are not allowed for written
  files, see [Names of written files](#names-of-written-files)
* file types the component does not read or write: `DataSourceCSV` and
  `DataTargetCSV` accept `csv`, `tsv` and `txt`, `DataSourceXML` accepts
  `xml` (core `FileTypePolicy`)

The rules apply when the task runs, in the backend module, the command and
the scheduler. They do not depend on the backend user: file mounts and
permissions of backend users do not apply to tasks.

Symbolic links are resolved before the check: the real path of the file, or
of its nearest existing parent directory, must be inside the real path of an
allowed directory, so an allowed directory may be a link (e.g. a `fileadmin`
on a shared volume), but a link inside it must not lead out of it. The real
path of an `EXT:` path must be inside the real path of the extension (or of
an allowed directory). The file type rules apply to the name of a link and of
its target, so a link whose target has no allowed file type (e.g. `data.csv`
pointing to a versioned file `data.csv.20261122`) is rejected.

## URLs

URLs are loaded only from the allowed hosts. The `url` of the data sources
and of the XML components (`ValidateXML` and the XML components of the core)
and the source URLs of `GenerateFileResource` and `GenerateUploadFile` are
loaded by `HttpResourceLoader` of the core with the HTTP
client of TYPO3 (`$GLOBALS['TYPO3_CONF_VARS']['HTTP']`, e.g. proxy and
timeout). No host is allowed by default:

```php
// config/system/additional.php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = [
    'data.example.org',
    '*.example.com', // subdomains of example.com
];
```

An entry checks the host and the port:

| Entry | Allows |
|---|---|
| `data.example.org` | this host with the default port of the scheme (80 for `http`, 443 for `https`) |
| `data.example.org:8443` | this host with port 8443 only |
| `*.example.com` | the subdomains of example.com with the default port |
| `*.example.com:8080` | the subdomains of example.com with port 8080 |
| `*` | all hosts and ports, including internal addresses |

Host names are not resolved or checked against IP ranges, so do not list
names which resolve to internal addresses.

No request is sent to a host or port that is not allowed. The loader of the
core checks the host name of each URL; the HTTP client of the extension
(`Resource\AllowedHostsClient`) checks the host and port of each request.
What happens then:

| Case | Result |
|---|---|
| URL of `GenerateFileResource`, `GenerateUploadFile`, `ValidateXML`, `ResourcePathConfigurationValidator` with a host or port that is not allowed | the extension checks the URL first: `MissingResourceException` (code 1795200302), the task stops (the validator marks the configuration invalid) |
| `url` of the data sources and of the XML components of the core with a host that is not allowed | `MissingResourceException` (code 1795200302) |
| `url` of the data sources and of the XML components of the core with a port that is not allowed | `MissingResourceException` (code 1795200301, request failed) |

The URL is not checked for its file type, since it often has no file
extension.

For the `url` of these components, redirects are followed by the loader,
not by the client. The extension passes the PSR-18 client of TYPO3 (Guzzle)
to the loader, and Guzzle's `sendRequest()` does not follow redirects, so
the settings `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['allow_redirects']` have
no effect here. The loader

* follows redirects (301, 302, 303, 307, 308) with a GET request, at most
  five for one URL; more fail the request,
* checks the target of each redirect like the URL itself: it must be an
  `http` or `https` URL with an allowed host and port, otherwise the
  redirect is not followed:
  * a host that is not allowed throws a `MissingResourceException` (code
    1795200302); in `GenerateFileResource` and `GenerateUploadFile` the task
    stops,
  * a port that is not allowed fails the request (code 1795200301); the data
    sources throw the exception, `GenerateFileResource` and
    `GenerateUploadFile` log a warning (1795300501), do not copy the file and
    go on with the next record,
* does not follow a redirect from `https` to `http`.

A redirect to another allowed host is followed; allow every host a URL may
redirect to. Default headers of the client are sent with each request, also
to the target of a redirect.

### Timeout and size

Neither the loader nor the extension limits how long a download takes or
how large it is:

* TYPO3 sets `$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout']` to `0` by
  default, which means no time limit for the request; only the connection
  is limited (`connect_timeout`, 10 seconds). A server that sends slowly or
  does not end the response keeps the task waiting.
* The HTTP client (Guzzle) receives the whole response body before the
  loader returns it. It keeps the body in a `php://temp` stream: up to 2 MB
  in memory, beyond that in a temporary file of the system. There is no
  maximum size. `GenerateFileResource` and `GenerateUploadFile` then copy the
  body into a temporary file next to the target file (`.<name>.<random>.part`
  in the target directory), which replaces the target file when it is
  complete.

Set a timeout for tasks that load URLs, e.g. in `config/system/additional.php`
(it applies to all HTTP requests of TYPO3):

```php
$GLOBALS['TYPO3_CONF_VARS']['HTTP']['timeout'] = 30;
```

Allow only hosts you trust to deliver files of a reasonable size, and do not
use `['*']` for URLs that come from imported records, e.g. the source field
of `GenerateFileResource`.

## Other components

The same rules apply to the components of the extension that read or write
files themselves:

| Component | Path | Rules |
|---|---|---|
| `PreProcessor\ValidateXML`, `Finisher\ValidateXML` (deprecated) | `file` or `url` of the document, `schema.file` / `schema.url` | allowed directories and file types, allowed hosts |
| `Persistence\DataTargetXMLStream` | `template` | allowed directories and file types |
| `PreProcessor\GenerateFileResource`, `PreProcessor\GenerateUploadFile` | source file (`sourcePath` and the record field) | allowed directories and file types, allowed hosts for URLs |
| `PreProcessor\GenerateFileResource`, `PreProcessor\GenerateUploadFile` | target file in the storage (`storageId`, `targetDirectoryPath`) | file names of written files (see [below](#names-of-written-files)); the storage is not limited to the allowed directories |
| `Validation\Configuration\ResourcePathConfigurationValidator` | `file`, `url` | a path or host that is not allowed makes the configuration invalid |

`GenerateFileResource` and `GenerateUploadFile` build the source path from
`sourcePath` and a value of the record. Local files and `EXT:` paths are
only read below the configured `sourcePath`: the file must be below the
directory of `sourcePath` (the path itself if it ends with a slash or is an
existing directory, else, for a file name prefix, its parent directory;
symbolic links are resolved), in addition to the rules
above. For an `EXT:` source path, the directory must be inside the
extension. Without `sourcePath`, the values of a record can only be URLs; a
value that names a local file or an `EXT:` path is refused (code
1795300101).

If `sourcePath` is a URL, the value of the record cannot change its scheme,
host, port or user information (code 1795200302). The path of a URL
`sourcePath` is not a boundary.

A path or URL that is not allowed throws the exception described above, a
file that does not exist is handled as before. Relative source paths of
`GenerateFileResource` and `GenerateUploadFile` are relative to the public
directory (before: to the working directory of the PHP process).

The XML components of the core, `ValidateXMLPreProcessor` and
`ValidateXMLFinisher`, load the document and the schemas with the resource
loader, like the file sources (see [XML components](../DEPRECATIONS.md#xml-components)).

Schemas that the schema of `ValidateXML` names in `xs:include`, `xs:import`,
`xs:redefine` or `xs:override` are loaded with the same rules, relative to
the location of the schema that names them: files from the allowed
directories (or `EXT:` paths) with the file type `xsd`, URLs from the allowed
hosts. A schema location that is not allowed throws a
`MissingResourceException` (code 1795300201). libxml loads no other files or
URLs while validating, so external entities and external DTDs of the
validated documents are not loaded.

Not covered: the temporary files of `DataTargetFileStream` and
`DataTargetXMLStream` (fixed directory `transient/t3import_export/` of the var
directory, outside of the public directory in Composer installations), the
YAML configuration directories
(`yamlConfigurationDirectories`, set by the administrator) and the file
arguments of the console commands.

## Names of written files

The extension checks the names of the files it writes itself, of the
file targets of the core (`DataTargetCSV`) and the target names of the
finishers `WriteFile` and `MoveFile`, in addition to the file type rules
above (`Typo3PathResolver::assertTargetFileName()`):

* the default `fileDenyPattern` of TYPO3 applies in addition to a configured
  one, so an empty or weaker configured pattern does not allow e.g. `.php`
  files
* server configuration files are refused: `.htaccess`, `.htpasswd` and other
  `.ht*` files, `.user.ini`, `php.ini`, `web.config`
* names that are not valid UTF-8, names with `?`, `#`, `:` or control
  characters and names ending in a dot or a space are refused

A name that is not allowed throws an `InvalidArgumentException` (code
1795400103, or 1795300103 for the `fileDenyPattern` configured in TYPO3).
The target name of `GenerateFileResource` and `GenerateUploadFile` is the
name of the source file; for a URL the last segment of its path, without
query and fragment (`https://example.org/image.jpg?size=large` is written to
`image.jpg`).

`GenerateFileResource` and `GenerateUploadFile` write the content of a
local file or URL into a temporary file in the target directory
(`.<name>.<random>.part`), which replaces the target file when it is
complete. If the source cannot be read or the file cannot be written, the
target file is not changed.

SVG files (by file extension, by the MIME type of the content, or markup
with an `svg` element within the first megabyte) are sanitized with the SVG
sanitizer of TYPO3 before they replace the target file; the detection is a
best effort. Compressed SVG files (`.svgz`) are refused (code 1795400103).
Other HTML, XHTML and XML files are not sanitized.

## Large files

The sources read the file while the records are processed, so the file is not
loaded into memory at once (except `DataSourceXML` with `expression`, which
loads the whole document). The sources of the core can also resume an interrupted run from a
checkpoint; the extension does not store checkpoints yet, so a run starts at
the beginning of the file.
