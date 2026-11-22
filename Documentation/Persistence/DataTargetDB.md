DataTargetDB
============

Writes records into a database table.

Since 4.0, `DataTargetDB` is an adapter on `DataTargetDBAL` of
`cpsit/import-export-core`. It accepts the options and records of earlier
versions and writes the same rows
([#132](https://github.com/dwenzel/t3import_export/issues/132)).

## Records

* A record with a non-empty field `__identity` updates the row whose `uid`
  is the value of `__identity`. `__identity` is not written as a column. If
  there is no such row, nothing is written, as before 4.0.
* Other records are inserted. An empty `__identity` (`''`, `0`, `'0'`,
  `null`) is removed before the record is inserted.
* A record with `__identity` and the same `uid` updates the row as well. A
  record with `__identity` and another `uid` changes the `uid` of the row;
  this is done by a fallback with the connection of TYPO3 and is deprecated,
  see [DEPRECATIONS.md](../DEPRECATIONS.md#records-of-datatargetdb).
* The fields of a record are written as they are. The target does not use
  the DataHandler: `pid`, `tstamp`, `crdate` and other system fields are not
  set, the reference index and caches are not updated, and no hooks are
  called, as before 4.0. Set the fields you need in the record, e.g. with a
  pre-processor.
* Values must be scalar or `null`, except arrays in JSON columns and
  `DateTime` objects in date and time columns (as the Doctrine type of
  the column expects), which are converted
  and written as before 4.0. Other arrays and objects are rejected. Field
  names must be plain names (letters, digits, underscore). Values are
  passed to the database as parameters.
* Values are converted to the types of their columns, as the connection of
  TYPO3 did before 4.0: e.g. `''` and `'2.5'` become `0` and `2` in an
  integer column, `false` becomes `''` in a string column. Field names are
  matched with the columns in their case, as before; the `uid` taken from
  `__identity` is not converted.
* With an ID map (`idMap` of the task), the processor asks for the `uid` of
  each written record; it gets it for inserted and updated records, also
  with `unsetKeys`. A record skipped by `skip` has no `uid`, and the task
  fails (`LogicException`, code 1796600002). The processor asks for the IDs
  after `flushBatch()`, so the batch with the skipped record is already
  committed when the task fails. Use `disable` instead of `skip` in tasks
  with an ID map.

## Configuration

### Required
* **class** *CPSIT\T3importExport\Persistence\DataTargetDB*
* **config.table** *string* table name, a plain name (letters, digits,
  underscore), optionally with schema (`schema.table`)

### Optional
| property                       | type            | description |
|--------------------------------|-----------------|-------------|
| **config.unsetKeys**           | string          | comma separated list of fields that are removed from each record before it is written |
| **config.skip.ifEmpty.field**  | string          | a record is not written if this field is empty |
| **config.skip.ifNotEmpty.field** | string        | a record is not written if this field is not empty |
| **config.connection**          | string          | name of the connection in `$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']`; default: the connection of the table mapping, see below |
| **config.key**                 | list of strings | option of the core: fields that identify an existing row for records without `__identity`, e.g. `[import_source, import_id]`. A record with the same values updates the row, other records are inserted. Without `key`, records without `__identity` are inserted |
| **config.idField**             | string          | option of the core: primary key column, default `uid` |
| **config.disable**             |                 | disables the target for a record, see [Components](../COMPONENT.md#disable-components) |

`persist()` returns `false` for a record that is skipped and `true` for a
record that is inserted or updated.

The options `identifier` (in `config`), `pid`, `mode` and `updateField` were
shown in examples of the documentation but never had an effect. They are
still ignored; `identifier` writes a message to the deprecation log, since
it will not be accepted in 5.0.

### Database connection

* `connection` names a connection in
  `$GLOBALS['TYPO3_CONF_VARS']['DB']['Connections']`. `default` stands for
  `Default`. A connection that is not configured throws a
  `MissingResourceException` (code 1795351767).
* Without `connection`, the connection that the table is mapped to in
  `$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']` is used (`Default`
  without mapping), as before 4.0.

### Batches and transactions

The processor writes the records of a task in batches (100 records by
default). The rows of a batch are written in one transaction on each
connection used:

* After each batch, the transaction is committed (`flushBatch()`); the last
  batch is committed by `persistAll()`.
* If the task fails, the processor calls `fail()`: the transaction of the
  current batch is rolled back. Rows of batches committed before stay in the
  table. Before 4.0, each row was committed when it was written, so the rows
  of the failed batch written before the error stayed in the table.
* A second `fail()`, and `persistAll()` or `flushBatch()` after `fail()`, do
  nothing.
* Code that calls `persist()` itself must call `persistAll()` (or
  `flushBatch()`) afterwards; otherwise the rows are not committed.
* Other components that write on the same connection during a batch join
  its transaction and are rolled back with it, e.g. the `sys_file_reference`
  rows of `GenerateFileReference` or log entries in a table of that
  connection. Files written to disk stay.

Queue items of `DataSourceQueue` are marked as failed after the rollback,
also if the queue table uses the same connection, when the task runs with
`TransferService` (commands and backend modules).

### Errors

* An invalid configuration throws an `InvalidConfigurationException` (code
  1796600001). `isConfigurationValid()` returns false in these cases, so the
  task cannot be created: `table` is missing, is not a string or not a plain
  name; `unsetKeys` is not a string; `skip` is invalid; `connection` is
  empty; `key` is not a list of field names; `idField` is not a plain name.
* A record that cannot be written throws a `PersistenceException` (code
  1647701464), as before 4.0: an unknown field, an SQL error, and since 4.0
  also a field name that is not a plain name, a value that is not scalar or
  `null` and an empty record. The original exception is the previous
  exception.

## Examples

### Simple

Write records into the table `tt_content`:

```yaml
import:
  tasks:
    exampleTask:
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: tt_content
```

### Remove fields

Remove the fields `foo` and `bar` of each record before it is written:

```yaml
import:
  tasks:
    exampleTask:
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: tt_content
          unsetKeys: foo,bar
```

### Skip records

Skip a record if its field `foo` is not empty:

```yaml
import:
  tasks:
    exampleTask:
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: tt_content
          skip:
            ifNotEmpty:
              field: foo
```

Skip a record if its field `foo` is empty:

```yaml
import:
  tasks:
    exampleTask:
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: tt_content
          skip:
            ifEmpty:
              field: foo
```

### Update by an import ID

Update the row with the same `import_source` and `import_id`, or insert the
record if there is none:

```yaml
import:
  tasks:
    exampleTask:
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: tx_myext_record
          connection: Legacy
          key: [import_source, import_id]
```
