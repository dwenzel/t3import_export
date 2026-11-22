DatabaseConnectionService
=========================
Provides access to pre-defined database connections.

Note: previous versions of this service allowed to register a connection. This is no longer the case.
Please register you database before-hand.

## Example
The example code below should be placed e.g. in a `AdditionalConfiguration.php` file. Never disclose any database credentials!

The database connection can be used via identifier. Since 4.0 this is
deprecated for `DataSourceDB`; use `config.connection` (see
[DataSourceDB](../Persistence/DataSourceDB.md#database-connection)).
`DataTargetDB` never used `identifier`; since 4.0 `config.identifier`
writes a message to the deprecation log, and `config.connection` selects
the connection (see
[DataTargetDB](../Persistence/DataTargetDB.md#database-connection)):
```yaml
import:
  tasks:
    exampleTask:
      # [...]
      source:
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceDB"
        identifier: xyz
        config:
          table: seminars
          fields: id,title_de,title_en,title_2_en,title_2_de,description_de,description_en,type,season_begin
          orderBy: id
          where:
            expression: "'published=1 AND edited >= \\'' ~ date('Y-m-d', strtotime('midnight')) ~ '\\''"
          limit: 5000
```
For the default connection, the identifier can be ommited.
