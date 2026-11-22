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

### Migrating the queue items of earlier versions

The upgrade wizard `t3importExport_queueItemMigration`
(`CPSIT\T3importExport\Upgrades\QueueItemMigration`) copies the items of
`tx_t3importexport_domain_model_queueitem` into `tx_t3importexport_queue_item`.
Run it after updating the database schema, in the Install Tool (Upgrade >
Upgrade Wizard) or on the command line:

```bash
vendor/bin/typo3 upgrade:run t3importExport_queueItemMigration
```

| Legacy table | Queue table of the core |
|---|---|
| `identifier` | `queue` |
| `data` | `data`: the record `{"data": "<data>"}` as JSON |
| – | `checksum`: computed by the core for queue and record |
| `status` 0 (new), 1 (processing) | `status` 0 (new) |
| `status` 2 (finished), 3 (failed) | `status` 2 (finished), 3 (failed) |
| other `status` values | `status` 3 (failed) |
| `created_date` | `created_at` |
| latest of `created_date`, `started_date`, `finished_date` | `updated_at` |

* Items of all statuses are copied. Finished and failed items are kept so
  that the core recognises records that were queued before as duplicates:
  the checksum is the one the core computes when the record
  `['data' => <data>]` is written into the same queue
  (`QueueItem::checksum()`). The checksum of the legacy table is not used,
  since the core computes it differently.
* The record is the one the queue source of earlier versions returned
  (without `uid`), so the data stays a string; it is not decoded.
* Items in processing become new: they were claimed by a run that did not
  finish.
* Items that are already in the queue table of the core (same queue and
  checksum) are not changed. Of several legacy items with the same identifier
  and data, the first one (lowest `uid`) is copied.
* Items without identifier and items whose data is not valid UTF-8 (cannot be
  stored as JSON) are skipped; the wizard lists their `uid`.
* The wizard can be run again (for example after
  `upgrade:mark:undone t3importExport_queueItemMigration`); it adds only the
  missing items. It reports an update as necessary as long as an item of the
  legacy table is missing in the queue table of the core.
* The legacy table is read but not changed. Both tables are read and written
  in batches of 500 rows on the connections TYPO3 assigns to them (table
  mapping), which may differ.
* Run the wizard while no import or export task uses the queue.
