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

Select the connection with `config.connection` (see
[DataSourceDB](../Persistence/DataSourceDB.md#database-connection) and
[DataTargetDB](../Persistence/DataTargetDB.md#database-connection)).
Selecting it with `identifier` next to `class` is deprecated for
`DataSourceDB` since 4.0; `config.identifier` of `DataTargetDB` never had an
effect.

```yaml
import:
  tasks:
    exampleTask:
      # [...]
      source:
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceDB"
        config:
          table: seminars
          connection: xyz
          fields: [id, title_de, title_en, description_de, description_en, type, season_begin]
          where:
            published: 1
          # edited today or later
          filter:
            field: edited
            op: '>='
            value: {date: 'P0D', from: today, format: date}
          orderBy:
            id: ASC
          limit: 5000
```
Without `connection`, the connection of the table mapping is used.
