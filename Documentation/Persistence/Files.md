Files
=====

The file sources and targets of `cpsit/import-export-core`
(`DataSourceCSV`, `DataSourceXML`, `DataTargetCSV`) do not open files
themselves. The extension registers the services they use in
`Configuration/Services.yaml`.

## Paths

`file` is resolved by `CPSIT\T3importExport\Resource\Typo3PathResolver`,
like other file names in TYPO3:

| Path | Resolved to |
|---|---|
| `EXT:my_extension/Resources/Private/Import/news.csv` | the file in the extension |
| `fileadmin/import/news.csv` | relative to the public directory |
| `/var/www/project/var/import/news.csv` | absolute paths only inside the project directory, or in `$GLOBALS['TYPO3_CONF_VARS']['BE']['lockRootPath']` |

Paths with `..`, stream wrappers (`php://`, `phar://` and others) and
absolute paths outside of the allowed directories are rejected.

## URLs

`url` is loaded with the HTTP client of TYPO3 (`$GLOBALS['TYPO3_CONF_VARS']['HTTP']`,
e.g. proxy and timeout). Only `http` and `https` are supported.

A configuration with `url` makes the server request this address. Tasks are
configured by administrators and integrators; if your configuration files
come from elsewhere, restrict the outgoing requests of the server, or replace
`CPSIT\ImportExportCore\Resource\ResourceLoaderInterface` in your
`Services.yaml` with a loader without `HttpResourceLoader`:

```yaml
services:
  CPSIT\ImportExportCore\Resource\ResourceLoaderInterface: '@CPSIT\ImportExportCore\Resource\FileResourceLoader'
```

## Large files

The sources read the file while the records are processed, so the file is not
loaded into memory at once (except `DataSourceXML` with `expression`, which
loads the whole document). The sources of the core can also resume an interrupted run from a
checkpoint; the extension does not store checkpoints yet, so a run starts at
the beginning of the file.
