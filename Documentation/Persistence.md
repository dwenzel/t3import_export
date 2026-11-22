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
* The core components do not read the table mapping of TYPO3
  (`$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']`). For a table mapped
  to another connection, set `connection` to the name of that connection.
  `Typo3ConnectionProvider::getConnectionNameForTable()` returns the name for
  a table.

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
