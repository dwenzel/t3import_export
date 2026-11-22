ID map and state components
===========================

The ID map records which source record became which target record. With it,
a task that runs repeatedly can update the records of earlier runs instead
of inserting them again, skip records that did not change, and replace IDs
of the source with the IDs of imported records.

Since 4.0 the ID map of `cpsit/import-export-core` is stored in the table
`tx_t3importexport_id_map`, see
[Persistence](../Persistence.md#id-map-of-the-core). The processor and the
components below get this ID map; nothing has to be configured besides the
task.

The core describes the ID map in its documentation (`Documentation/Developer/State.md`
and `Documentation/Configuration/Components.md` of `cpsit/import-export-core`).

Recording the target IDs
------------------------

With the task option `idMap`, the processor stores a mapping for each
written record:

```yaml
idMap:
  namespace: pages          # e.g. a table or entity type
  sourceIdField: uid        # field of the source record holding its ID
  targetIdField: __identity # optional: field in which TargetIdPreProcessor sets the target ID
```

| Option | Type | Description |
|---|---|---|
| `namespace` | string | required; mappings are unique per namespace and source ID |
| `sourceIdField` | string | required; field of the source record that holds its ID. A record without a value fails the task (`UnexpectedValueException`, code 1794600032) |
| `targetIdField` | string | field in which `TargetIdPreProcessor` sets the target ID, if it is not `sourceIdField`. It is left out of the checksum, like `sourceIdField` |

* The target ID is the one the target reports (`IdentifyingTargetInterface`):
  the `uid` for `DataTargetDB`, the value of `idField` for `DataTargetDBAL`.
  A task with `idMap` and a target without this interface fails
  (`InvalidConfigurationException`).
* The checksum is computed from the record after the pre-processors, without
  `sourceIdField` and `targetIdField`; `UnchangedRecordFilter` compares it.
* The mappings of a batch are stored after the batch was written. A record
  cannot refer to another record of the same batch through the ID map. Use a
  second task for such references, e.g. the parent of a record of the same
  table.
* `DataTargetDB` has no `uid` for a record skipped by `skip`; with `idMap`,
  such a record fails the task (`LogicException`, code 1796600002), see
  [DataTargetDB](./DataTargetDB.md#records). Use `disable` instead.

TargetIdPreProcessor
--------------------

Updates records written by an earlier run: sets the target ID of the source
record in `targetIdField`. If there is no mapping, `targetIdField` is
removed, so the target inserts the record.

* **class**: `CPSIT\ImportExportCore\State\Component\TargetIdPreProcessor`
* **key in the task**: `preProcessors`

| Option | Type | Default | Description |
|---|---|---|---|
| `namespace` | string | – | required; as in `idMap` |
| `sourceIdField` | string | – | required; as in `idMap` |
| `targetIdField` | string | `sourceIdField` | field for the target ID |

With `DataTargetDB`, set the target ID in `__identity`: the target updates
the row with this `uid`, and inserts records without `__identity`. Remove the
`uid` of the source with `unsetKeys`.

With `DataTargetDBAL`, the target ID can be set in `uid` itself (the
default of `targetIdField`, if `sourceIdField` is `uid`); the target updates
by `key: [uid]`.

ResolveReferencePreProcessor
----------------------------

Replaces source IDs in a field with the target IDs of the ID map, e.g. the
parent of a record or the categories of a record imported by another task.

* **class**: `CPSIT\ImportExportCore\State\Component\ResolveReferencePreProcessor`
* **key in the task**: `preProcessors`

| Option | Type | Default | Description |
|---|---|---|---|
| `field` | string | – | required; field holding one ID, a list (array) of IDs, or IDs separated by `separator` |
| `namespace` | string | – | required; namespace of the referenced records |
| `separator` | string | – | separator of IDs in a string, e.g. `,` |
| `onMissing` | string | `fail` | an ID without mapping: `fail` fails the task (`UnexpectedValueException`, code 1794600012), `keep` keeps the source ID, `empty` removes it; `keep` and `empty` log a warning |

Empty values (`null`, `''`, `0`, `'0'`, `[]`) are not resolved.

UnchangedRecordFilter
---------------------

Skips records that were written before and did not change since: the ID map
holds a mapping for the source ID, and its checksum equals the checksum of
the record after the pre-processors.

* **class**: `CPSIT\ImportExportCore\State\Component\UnchangedRecordFilter`
* **key in the task**: `filters`

| Option | Type | Default | Description |
|---|---|---|---|
| `namespace` | string | – | required; as in `idMap` |
| `sourceIdField` | string | – | required; as in `idMap` |
| `targetIdField` | string | – | as in `idMap`; required if `TargetIdPreProcessor` sets the target ID in another field, otherwise the records are written again in every run |

* The filter runs after the pre-processors, so values that pre-processors
  add are part of the checksum. Changes of converters, post-processors or of
  the target configuration are not detected: after changing them, run the
  task once without the filter.
* Changes of the records in TYPO3 are not detected either: a record changed
  in the backend is not written again as long as its source record does not
  change.
* The ID map is read and written per record: a task with `idMap`,
  `TargetIdPreProcessor` and `UnchangedRecordFilter` needs about four queries
  per written record (two `get()`, and `set()` with `has()` and an insert or
  update), besides the queries of the target.

Example
-------

Pages of a legacy database are imported as categories, then content elements
as categories below the category of their page. A second run updates the
categories and writes only changed records.

```yaml
tasks:
  pagesToCategories:
    source:
      class: CPSIT\T3importExport\Persistence\DataSourceDB
      config:
        table: pages
        connection: Legacy
        fields: uid,title
    idMap:
      namespace: legacy_pages
      sourceIdField: uid
      targetIdField: __identity
    preProcessors:
      10:
        class: CPSIT\ImportExportCore\State\Component\TargetIdPreProcessor
        config:
          namespace: legacy_pages
          sourceIdField: uid
          targetIdField: __identity
    filters:
      10:
        class: CPSIT\ImportExportCore\State\Component\UnchangedRecordFilter
        config:
          namespace: legacy_pages
          sourceIdField: uid
          targetIdField: __identity
    target:
      class: CPSIT\T3importExport\Persistence\DataTargetDB
      config:
        table: sys_category
        unsetKeys: uid

  contentToCategories:
    source:
      class: CPSIT\T3importExport\Persistence\DataSourceDB
      config:
        table: tt_content
        connection: Legacy
        fields: uid,pid,header
    idMap:
      namespace: legacy_content
      sourceIdField: uid
      targetIdField: __identity
    preProcessors:
      10:
        class: CPSIT\ImportExportCore\Processor\ValueRulesPreProcessor
        config:
          rules:
            - {rule: rename, field: title, from: header}
            - {rule: rename, field: parent, from: pid}
      20:
        class: CPSIT\ImportExportCore\State\Component\ResolveReferencePreProcessor
        config:
          field: parent
          namespace: legacy_pages
      30:
        class: CPSIT\ImportExportCore\State\Component\TargetIdPreProcessor
        config:
          namespace: legacy_content
          sourceIdField: uid
          targetIdField: __identity
    target:
      class: CPSIT\T3importExport\Persistence\DataTargetDB
      config:
        table: sys_category
        unsetKeys: uid
```

Run both tasks in one set, or `pagesToCategories` first.

Removing mappings
-----------------

The extension has no command to remove mappings. To import the records of a
namespace again as new records, delete its rows from the table, e.g.

```sql
DELETE FROM tx_t3importexport_id_map WHERE namespace = 'legacy_pages';
```

Mappings of rows deleted from the target table are not removed;
`TargetIdPreProcessor` then sets the `uid` of a row that does not exist, and
`DataTargetDB` writes nothing for the record, as for any `__identity` without
row. To insert the record again, delete its mapping:

```sql
DELETE FROM tx_t3importexport_id_map
WHERE namespace = 'legacy_pages' AND source_id = '42';
```

Rows marked as deleted in TYPO3 (`deleted = 1`) still exist: they are
updated, but stay deleted, unless the record sets `deleted`.

Case of source IDs
------------------

On MySQL and MariaDB, the table gets the collation of the database (e.g.
`utf8mb4_unicode_ci`), so `namespace` and `source_id` are compared without
case and without trailing spaces: the source IDs `ABC` and `abc` share one
mapping and one target record. SQLite compares them with case. Integer IDs
are not affected. Use source IDs that differ in more than case, or normalise
them (e.g. to lower case) before the ID map is used. A binary collation for
the table is proposed in the core
([CPS-IT/import-export-core#116](https://github.com/CPS-IT/import-export-core/issues/116));
the table of the extension follows the definition of the core.
