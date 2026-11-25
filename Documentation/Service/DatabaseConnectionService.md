DatabaseConnectionService
=========================
Provides access to pre-defined database connections.

Note: previous versions of this service allowed to register a connection. This is no longer the case.
Configure the connection in `$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']`
beforehand, e.g. in `config/system/additional.php`. Never disclose any
database credentials!

```php
$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']['xyz'] = [
    'driver' => 'mysqli',
    'host' => 'legacy-db',
    'dbname' => 'legacy',
    'user' => 'import',
    'password' => getenv('LEGACY_DB_PASSWORD'),
];
```

`getDatabase()` is deprecated (use the `ConnectionPool` of TYPO3). Since 4.0
components select a connection with `config.connection` (`DataSourceDB`,
`DataTargetDB` and the DBAL components of the core); the table initializers,
`LookUpDB` and `SetL10nParent` use the connection of the table mapping.

## Example

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
For the default connection, the identifier can be omitted.
