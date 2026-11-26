DeleteFromTable
===============

Deletes record from a database table.
Use this initializer if you have to delete records determined by *where* clause from table.


## Configuration

### Required

* **class** *string*

    fully qualified class name, i.e. *CPSIT\T3importExport\Component\Initializer\DeleteFromTable*

* **config.table** *string*

  name of the table from which records should be deleted

* **config.where** *string*

  where clause for *delete* query.


### Optional

* **config.disable** *boolean*

    If the value is 1, the initializer will not be called.
    See [Components](../../COMPONENT.md#disable-components) for `if.result.hasMessage` and `expression`.

### Database connection

The initializer uses the connection that the table is mapped to in
`$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']` (`Default` without
mapping). An option `identifier` is ignored.

The `where` clause is SQL and used as it is; the configuration must come from
trusted sources.


### Example
```yaml
import:
  tasks:
    exampleTask:
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\DeleteFromTable"
          config:
            # initializer is disabled if the expression is true
            disable:
              expression: "false"  # your condition
            table: sys_category
            where: 'title LIKE "%foo%"'
```
