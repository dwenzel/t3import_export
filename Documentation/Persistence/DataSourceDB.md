DataSourceDB
============

Reads records from a database table.

## Configuration

### Required
* **class** *CPSIT\T3importExport\Persistence\DataSourceDB*
* **config.table** *string* table name

### Optional
| property            | type   | description |
|---------------------|--------|-------------|
| **config.fields**   | string | comma separated list of fields, default `*` |
| **config.where**    | string | WHERE clause (SQL) |
| **config.groupBy**  | string | GROUP BY clause |
| **config.orderBy**  | string | comma separated list of fields, each optionally followed by `ASC` or `DESC` |
| **config.limit**    | string | LIMIT clause |
| **config.join**     | array  | joined table: `table`, `alias`, `condition`; set `type: selectJoin` |

The database connection is selected with **identifier** next to `class`
(not in `config`); default is `Default`.

### Values from expressions
The string values above (`where`, `limit`, ...) may be given as an array with
the key `expression`. The
expression is evaluated with the
[Symfony ExpressionLanguage](https://symfony.com/doc/current/reference/formats/expression_language.html)
before the query is built; the functions of
[EvaluateExpressions](../Components/PreProcessors/EVALUATE_EXPRESSIONS.md)
(`date()`, `strtotime()`, ...) are available. The result is used as SQL, as if
it was written in the configuration.

```yaml
source:
  class: 'CPSIT\T3importExport\Persistence\DataSourceDB'
  config:
    table: 'tx_news_domain_model_news'
    where:
      expression: "'deleted = 0 AND tstamp > ' ~ strtotime('-1 day')"
    orderBy: 'tstamp DESC'
```

Values rendered as TypoScript content objects (`where = TEXT`, `where { ... }`)
are deprecated and will be removed in version 4.0
([#75](https://github.com/dwenzel/t3import_export/issues/75)).
