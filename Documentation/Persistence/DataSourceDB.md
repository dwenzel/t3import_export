DataSourceDB
============

Reads records from a database table. It is the default source of a task.

Since 4.0, `DataSourceDB` is an adapter on `DataSourceDBAL` of
`cpsit/import-export-core`. It accepts the options of the core and the
options of earlier versions
([#132](https://github.com/dwenzel/t3import_export/issues/132)).

## Configuration

### Required
* **class** *CPSIT\T3importExport\Persistence\DataSourceDB*
* **config.table** *string* table name

### Options of the core
| property                | type                 | description |
|-------------------------|----------------------|-------------|
| **config.connection**   | string               | name of the connection in `$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']`; default: the connection of the table mapping, see below |
| **config.fields**       | list of strings      | fields to read, default all fields |
| **config.where**        | map field => value   | equality conditions combined with AND; `null` means IS NULL, a list means IN (...) |
| **config.filter**       | condition            | further conditions: comparisons, NULL checks, `all`/`any`/`not` groups, relative dates and `exists`/`notExists` on a related table |
| **config.orderBy**      | map field => ASC/DESC | sort order |
| **config.limit**        | int                  | maximum number of records |
| **config.cursorField**  | string               | a unique, sortable field (e.g. `uid`); records are read in pages and an interrupted task continues after the last processed record |
| **config.pageSize**     | int                  | records per query with `cursorField`, default 1000 |
| **config.timezone**     | string               | time zone of the relative dates in `filter` |

The options are described in the
[documentation of the core](https://github.com/CPS-IT/import-export-core/blob/main/Documentation/Configuration/Components.md#datasourcedbal).
The values are passed to the database as parameters; table and field names
must be plain names (letters, digits, underscore).

```yaml
source:
  class: 'CPSIT\T3importExport\Persistence\DataSourceDB'
  config:
    table: 'tx_news_domain_model_news'
    fields: ['uid', 'title', 'datetime']
    where:
      pid: [42, 43]
      hidden: 0
      deleted: 0
    orderBy:
      datetime: DESC
```

### Options of earlier versions
| property            | type   | description |
|---------------------|--------|-------------|
| **config.fields**   | string | comma separated list of fields, default `*` |
| **config.where**    | string | WHERE clause (SQL), deprecated |
| **config.groupBy**  | string | GROUP BY clause (SQL), deprecated |
| **config.orderBy**  | string | comma separated list of fields, each optionally followed by `ASC` or `DESC` |
| **config.limit**    | string | LIMIT clause |
| **config.alias**    | string | alias of the table, deprecated |
| **config.join**     | array  | joined table (inner join): `table`, `alias`, `condition` (SQL), deprecated |

Comma-separated `fields` and `orderBy` with plain field names and a numeric
`limit` are converted into the options of the core. Empty values are ignored,
as before.

SQL in the options cannot be expressed with the options of the core: a
`where` string, `join`, `groupBy`, `alias`, field names with table or alias
(`p.uid`), `AS` or functions in `fields` or `orderBy`, a `limit` with offset
(`10, 20`) and a table name that is not a plain name. Such a configuration
is read by a fallback with the query builder of TYPO3, which builds the same
query as before 4.0, and a message is written to the deprecation log. The
fallback will be removed in 5.0. It cannot be combined with `where` as map,
`filter`, `cursorField`, `pageSize` or `timezone`.

The SQL of the configuration is used as it is, as before 4.0. Configurations
must therefore come from trusted sources only; do not put values from
records or from user input into them.

The options `type` and `leftJoin` had no effect on this source and are
ignored.

See [DEPRECATIONS.md](../DEPRECATIONS.md#sql-in-the-options-of-datasourcedb)
for the migration.

### Database connection

* `connection` names a connection in
  `$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']`. `default` stands for
  `Default`.
* Without `connection`, the connection that the table is mapped to in
  `$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']` is used (`Default`
  without mapping), as before 4.0.
* `identifier` next to `class` (not in `config`) selects a connection by
  name, as before 4.0 (deprecated, use `connection`). `identifier: Default`
  uses the table mapping. A connection that is not configured throws a
  `MissingDatabaseException` (code 1449363030).

### Records

The records are read without the restrictions of TYPO3: deleted and hidden
records and records outside their start and end time are read, as before
4.0. Filter them in `where` or `filter`, e.g. `where: {deleted: 0, hidden: 0}`.

The records have the same fields and types as before 4.0; both the core and
the fallback use the connection of TYPO3. `getRecords()` returns an array of
records. With `cursorField` it returns a generator, so that an interrupted
task can continue after the last processed record.

A query that fails returns no records, as before 4.0.

### Values from expressions
The values above (`where`, `limit`, ...) may be given as an array with
the key `expression`. The
expression is evaluated with the
[Symfony ExpressionLanguage](https://symfony.com/doc/current/reference/formats/expression_language.html)
before the query is built; the functions of
[EvaluateExpressionsPreProcessor](../Components/PreProcessors/EVALUATE_EXPRESSIONS.md)
(`date()`, `strtotime()`, ...) are available. The result is used as if it was
written in the configuration; a string in `where` is SQL and read by the
fallback.

```yaml
source:
  class: 'CPSIT\T3importExport\Persistence\DataSourceDB'
  config:
    table: 'tx_news_domain_model_news'
    where:
      expression: "'deleted = 0 AND tstamp > ' ~ strtotime('-1 day')"
    orderBy: 'tstamp DESC'
```

With the options of the core, use a relative date in `filter` instead:

```yaml
source:
  class: 'CPSIT\T3importExport\Persistence\DataSourceDB'
  config:
    table: 'tx_news_domain_model_news'
    where:
      deleted: 0
    filter:
      field: tstamp
      op: '>'
      value: {date: '-P1D', from: now, format: timestamp}
    orderBy:
      tstamp: DESC
```

Values rendered as TypoScript content objects (`where = TEXT`, `where { ... }`)
are not supported any more since version 4.0
([#75](https://github.com/dwenzel/t3import_export/issues/75)). They throw an
`InvalidConfigurationException` (code 1794781881).
