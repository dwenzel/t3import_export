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
    * [DataSourceQueue](./Persistence/Queue.md#datasourcequeue)
    * DataSourceDynamicRepository
* Targets
    * [DataTargetDB](./Persistence/DataTargetDB.md)
    * [DataTargetCSV](./Persistence/DataTargetCSV.md)
    * [DataTargetQueue](./Persistence/Queue.md#datatargetqueue)
    * DataTargetFileStream
    * DataTargetRepository
    * DataTargetXMLStream
* [ID map and state components](./Persistence/IdMap.md)

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

`DataSourceDB` and `DataTargetDB` are adapters on `DataSourceDBAL` and
`DataTargetDBAL`. They accept the options of earlier versions and use the
connection of the table mapping if `connection` is not set. The notes above
apply to them as well, as they did before 4.0.

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

ID map of the core
------------------

The ID map of the core (`CPSIT\ImportExportCore\Dbal\State\DbalIdMap`)
stores which source record became which target record in the table
`tx_t3importexport_id_map`: namespace, source ID, target ID and the checksum
of the record. Like the queue table below, the table is defined in
`ext_tables.sql` and created by TYPO3, not by the `SchemaInstaller` of the
core; its structure follows `DbalIdMap::getTables()`, which a functional
test compares. The table has no TCA.

On MySQL and MariaDB, the columns `namespace` and `source_id` have the
binary collation `utf8mb4_bin`, so source IDs that differ in case, such as
`ABC` and `abc`, have their own mappings. `utf8mb4_bin` is a PAD SPACE
collation: trailing spaces are ignored, and source IDs that differ only in
trailing spaces (`abc` and `abc `) still share one mapping. The core uses
`utf8mb4_0900_bin` (MySQL) or `utf8mb4_nopad_bin` (MariaDB) for tables it
creates itself, which compare trailing spaces too; `ext_tables.sql` needs one
collation that exists on both servers, and TYPO3 applies a collation only
together with a character set. The source ID is taken from the record as
read from the source, before the pre-processors; if trailing spaces are
significant, the source has to provide distinct IDs. PostgreSQL and SQLite
compare the values byte by byte.

`CPSIT\T3importExport\Dbal\IdMapFactory` creates the ID map on the
connection the table is mapped to in
`$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']` (`Default` without
mapping). It is registered as `IdMapInterface` of the core, so

* the processor stores the target IDs of tasks with the option `idMap`, and
* `TargetIdPreProcessor`, `ResolveReferencePreProcessor` and
  `UnchangedRecordFilter` of the core read them.

See [ID map and state components](./Persistence/IdMap.md) for the options
and an example.

The checkpoints of the core (resuming interrupted tasks) are not stored:
the processor uses the `NullCheckpointStore` of the core.

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

`DataSourceQueue` and `DataTargetQueue` of the extension use this queue, see
[Queue](./Persistence/Queue.md). The commands `t3import-export:queue:status`
and `t3import-export:queue:release` show and release the items.

The queue table of earlier versions
(`tx_t3importexport_domain_model_queueitem`) is no longer used. Its TCA, the
model `Domain\Model\QueueItem` and `Domain\Repository\QueueItemRepository`
are removed in 4.0. The table stays defined in `ext_tables.sql` through 4.x,
so that the upgrade wizard below can read it; it will be removed in 5.0.

### Migrating the queue items of earlier versions

The upgrade wizard `t3importExport_queueItemMigration`
(`CPSIT\T3importExport\Upgrades\QueueItemMigration`) copies the items of
`tx_t3importexport_domain_model_queueitem` into `tx_t3importexport_queue_item`.
Run it after updating the database schema and before running any task with
version 4.0, in the Install Tool (Upgrade > Upgrade Wizard) or on the command
line:

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
  and data, one is copied: a new or processing item is preferred over a
  finished or failed one, then the lowest `uid`. The wizard reports the
  number of migrated items, of items already in the queue table of the core
  and of legacy duplicates that were not copied.
* Items without identifier and items whose data is not valid UTF-8 (cannot be
  stored as JSON) are skipped; the wizard lists their `uid`.
* The wizard can be run again (for example after
  `upgrade:mark:undone t3importExport_queueItemMigration`); it adds only the
  missing items. It does not copy later changes of the status in the legacy
  table to items that were migrated before. It reports an update as necessary
  as long as an item of the legacy table is missing in the queue table of the
  core.
* The legacy table is read but not changed. If it does not exist, the wizard
  reports that there is nothing to migrate. Both tables are read in batches
  of 500 rows and written in chunks of at most 100 rows (and about 1 MB of
  data) on the connections TYPO3 assigns to them (table mapping), which may
  differ.
* Run the wizard while no import or export task uses the queue, and before
  running tasks with version 4.0.
