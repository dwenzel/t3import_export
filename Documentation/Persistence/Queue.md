Queue
=====

A queue stores records between two tasks: one task writes records into a
queue (`DataTargetQueue`), another one reads them in portions
(`DataSourceQueue`), e.g. once per scheduler run.

Since 4.0 the queue of `cpsit/import-export-core` is used. The items are
stored in the table `tx_t3importexport_queue_item`, see
[Persistence](../Persistence.md#queue-of-the-core). The source and target of
the extension are adapters on those of the core
(`CPSIT\ImportExportCore\Persistence\DataSourceQueue`, `DataTargetQueue`) and
accept the options of earlier versions.

Items have one of the statuses new, processing (claimed by a run), finished
and failed.

DataTargetQueue
---------------

Writes the field `data` of each record into a queue.

* **class**: `CPSIT\T3importExport\Persistence\DataTargetQueue`

| Option | Type | Default | Description |
|---|---|---|---|
| `identifier` | string | – | The queue, e.g. `import.events` |
| `allowUpdate` | bool | false | `true`: a record that is already in the queue is set back to new, also if it was finished or failed. Strings and integers (e.g. `'1'`) are deprecated and treated as false. |

* The target enqueues the record `['data' => '<data>']`. The value of `data`
  is written as string; other fields of the record are ignored. Records
  without a field `data`, with an array or `null` in it, and objects are not
  written.
* A record is in the queue if an item with the same `data` exists in the
  same queue, whatever its status. Such records are skipped, or set back to
  new with `allowUpdate`. Items migrated from the queue table of earlier
  versions count as well.
* Prepare the field `data` with pre-processors, e.g. encode the record as
  JSON into `data`.
* `allowUpdate` changed its meaning in 4.0. Before, only strings were valid
  and the option had practically no effect on items in the queue. Now
  `allowUpdate: true` sets them back to new, so that they are read again,
  every time the record is written. Use it only if this is wanted.
* The table stores the record as JSON in the column `data`
  (`{"data":"<data>"}`).

```yaml
target:
  class: CPSIT\T3importExport\Persistence\DataTargetQueue
  config:
    identifier: import.events
```

DataSourceQueue
---------------

Reads new items of a queue and claims them (status processing). After the
task the claimed items are marked as finished. If the task fails, they are
marked as failed.

* **class**: `CPSIT\T3importExport\Persistence\DataSourceQueue`

| Option | Type | Default | Description |
|---|---|---|---|
| `identifier` | string | – | The queue |
| `limit` | int | 10 | Items per run |
| `batchSize` | int | – | Deprecated, use `limit`; used if `limit` is not set |

* The records are those written into the queue, e.g. `['data' => '<data>']`.
  They have no `uid`.
* Items in the order they were added. Items that are claimed by a run that
  does not end (e.g. a killed process) stay in processing; release them with
  `queue:release`.
* Failed items are not read again. Write them into the queue again with
  `allowUpdate`, or set them back to new in the table.

```yaml
source:
  class: CPSIT\T3importExport\Persistence\DataSourceQueue
  config:
    identifier: import.events
    limit: 50
```

Commands
--------

The queue items are not shown in the backend. The following commands show and
release them.

### `t3import-export:queue:status` (alias `queue:status`)

Shows the number of items by status for each queue. With `--queue` or
`--status` the items are listed as well, with the time they were added and
last changed (the values stored in the table).

| Option | Description |
|---|---|
| `--queue`, `-q` | Only this queue; can be repeated |
| `--status`, `-s` | Only items with this status: `new`, `processing`, `finished`, `failed` (or `0` to `3`) |
| `--limit`, `-l` | Maximum number of items to list, default 50 |

```bash
vendor/bin/typo3 t3import-export:queue:status
vendor/bin/typo3 t3import-export:queue:status --queue import.events --status failed
```

Returns 0, or 2 for an invalid status or limit.

### `t3import-export:queue:release` (alias `queue:release`)

Sets the items in processing back to new, so that the next run reads them.
Use it after a run was interrupted. Do not use it while a task reads from the
queue: its items would be read a second time.

| Option | Description |
|---|---|
| `--queue`, `-q` | Release the items of this queue; can be repeated |
| `--all`, `-a` | Release the items of all queues |

```bash
vendor/bin/typo3 t3import-export:queue:release --queue import.events
vendor/bin/typo3 t3import-export:queue:release --all
```

Returns 0, or 2 without `--queue` and `--all`, or with both.

Changes in 4.0
--------------

See [Deprecations](../DEPRECATIONS.md#queue). The items of the queue table
of earlier versions are copied by an upgrade wizard, see
[Persistence](../Persistence.md#migrating-the-queue-items-of-earlier-versions).
