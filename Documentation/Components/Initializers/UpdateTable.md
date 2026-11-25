UpdateTable
===============

Updates records from a database table.
Use this initializer if you have to update some records with a specific set of *fields* determined by *where* clause from table.


## Configuration

### Required

* **class** *string*

    fully qualified class name, i.e. *CPSIT\T3importExport\Component\Initializer\UpdateTable*

* **config.table** *string*

  name of the table from which records should be updated

* **config.where** *string*

  where clause for the *update* query.

* **config.setFields** *array*

  array of fields and values to use as *update* query.


### Optional

* **config.types** *array*

    Parameter type for fields in *setFields*: `int`, `bool` or `null`.
    Fields without a type are bound as strings.

### Database connection

The initializer uses the connection that the table is mapped to in
`$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']` (`Default` without
mapping). An option `identifier` is ignored.

The `where` clause is SQL and used as it is; the configuration must come from
trusted sources. The restrictions of TYPO3 (`deleted`, `hidden`) are not
applied.


### Example

One use case is to set all records defined by a where clause to hidden at the beginning of an import.

```yaml
import:
  tasks:
    example:
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\UpdateTable"
          config:
            table: pages
            where: "pid = 1"
            setFields:
              hidden: 1
            types:
              hidden: int
```

While importing data, all received records can be set/reset to hidden=0.

```yaml
      preProcessors:
        - class: "CPSIT\\ImportExportCore\\Processor\\SetValuesPreProcessor"
          config:
            values:
              hidden: 0
```

So the import can take care syncing deleted records from the data souce.
