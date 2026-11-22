Persistence
===========

Import and export tasks use data sources and targets.

A set of standard sources and targets is included. You may write your own and include them by configuration in YAML.

There exist interfaces for sources and targets. Your custom class **must** implement one of them.

Please see [Overview](./OVERVIEW.md) for the general application flow and detailed description referenced below (non-linked components are not yet documented)
* Sources
    * [DataSourceCSV](./Persistence/DataSourceCSV.md)
    * [DataSourceXML](./Persistence/DataSourceXML.md)
    * [DataSourceDB](./Persistence/DataSourceDB.md)
    * DataSourceDynamicRepository
* Targets
    * [DataTargetDB](./Persistence/DataTargetDB.md)
    * [DataTargetCSV](./Persistence/DataTargetCSV.md)
    * DataTargetFileStream
    * DataTargetRepository
    * DataTargetXMLStream

Database components of the core
-------------------------------

The DBAL components of `cpsit/import-export-core` can be used in tasks:
`CPSIT\ImportExportCore\Dbal\DataSourceDBAL`, `DataTargetDBAL` and
`RelationListPreProcessor`. Their options are described in the core
documentation. They get their connections from
`CPSIT\T3importExport\Dbal\Typo3ConnectionProvider`, which is registered as
`ConnectionProviderInterface` of the core:

* `connection` names a connection in
  `$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']`.
* Without `connection`, the components use the core name `default`, which
  stands for the TYPO3 connection `Default`. `default` and `Default` are the
  same connection.
  A TYPO3 connection named `default` (lower case) cannot be used, since the
  name always refers to `Default`.
* The core components do not read the table mapping of TYPO3
  (`$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']`). For a table mapped
  to another connection, set `connection` to the name of that connection.
  `Typo3ConnectionProvider::getConnectionNameForTable()` returns the name for
  a table.
* The core components build their queries with the query builder of
  Doctrine DBAL, not with the one of TYPO3, and write without DataHandler.
  The restrictions of TYPO3 (`deleted`, `hidden`, start and end time,
  workspaces) do not apply, `tstamp` and `crdate` are not set, and neither
  the reference index nor caches are updated. Filter deleted or hidden
  records in `where` or `filter`, and set system fields in the records
  yourself.

```yaml
source:
  class: CPSIT\ImportExportCore\Dbal\DataSourceDBAL
  config:
    table: tx_myext_legacy_record
    connection: Legacy
    where:
      deleted: 0
    cursorField: uid
target:
  class: CPSIT\ImportExportCore\Dbal\DataTargetDBAL
  config:
    table: tx_myext_record
    key: [import_id]
    idField: uid
```

Queue of the core
-----------------

The queue repository of the core
(`CPSIT\ImportExportCore\Dbal\Queue\DbalQueueRepository`) stores its items in
the table `tx_t3importexport_queue_item`. The table is defined in
`ext_tables.sql` and created by TYPO3 like any other table of the extension,
not by the `SchemaInstaller` of the core. Its structure follows
`DbalQueueRepository::getTables()`; a functional test compares both. The
table has no TCA and is not shown in the backend.

`CPSIT\T3importExport\Dbal\QueueRepositoryFactory` creates the repository on
the connection the table is mapped to in
`$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']` (`Default` without
mapping). The repository is registered as `QueueRepositoryInterface` of the
core.

With `cpsit/import-export-core` 0.6.0, `createdAt` and `updatedAt` of the
queue items read in a TYPO3 process are 1970-01-01 (the values stored in the
table are correct), until the core accepts `\DateTimeInterface` for these
values; TYPO3 replaces the Doctrine type `datetime_immutable` with a mutable
type.

The queue table of earlier versions
(`tx_t3importexport_domain_model_queueitem`) and its components are not
changed yet.
