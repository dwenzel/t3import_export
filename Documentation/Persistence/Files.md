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

`url` is loaded with the HTTP client of TYPO3 (`$GLOBALS['TYPO3_CONF_VARS']['HTTP']`,
e.g. proxy, timeout and redirects), only from the allowed hosts. No host is
allowed by default:

```php
// config/system/additional.php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] = [
    'data.example.org',
    '*.example.com', // subdomains of example.com
];
```

`['*']` allows all hosts. A URL with another host throws a
`MissingResourceException` (code 1795200302) before a request is sent. The
URL is not checked for its file type, since it often has no file extension.

## Other components

The same rules apply to the components of the extension that read or write
files themselves:

| Component | Path | Rules |
|---|---|---|
| `PreProcessor\ValidateXML`, `Finisher\ValidateXML` (deprecated) | `file` or `url` of the document, `schema.file` / `schema.url` | allowed directories and file types, allowed hosts |
| `Persistence\DataTargetXMLStream` | `template` | allowed directories and file types |
| `PreProcessor\GenerateFileResource`, `PreProcessor\GenerateUploadFile` | source file (`sourcePath` and the record field) | allowed directories and file types, allowed hosts for URLs |
| `PreProcessor\GenerateFileResource`, `PreProcessor\GenerateUploadFile` | target file in the storage (`storageId`, `targetDirectoryPath`) | file types only (`fileDenyPattern`, allowed file extensions); the storage is not limited to the allowed directories |
| `Validation\Configuration\ResourcePathConfigurationValidator` | `file`, `url` | a path or host that is not allowed makes the configuration invalid |

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

## Large files

The sources read the file while the records are processed, so the file is not
loaded into memory at once (except `DataSourceXML` with `expression`, which
loads the whole document). The sources of the core can also resume an interrupted run from a
checkpoint; the extension does not store checkpoints yet, so a run starts at
the beginning of the file.
