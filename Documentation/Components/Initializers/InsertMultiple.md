InsertMultiple
==============

Inserts multiple rows into a database table.
Use this initializer if you have to insert a few records into a table.
****
## Configuration

### required

* **class** *string*

    fully qualified class name, i.e. *CPSIT\T3importExport\Component\Initializer\InsertMultiple*
* **config.table** *string*

        name of the table into which records are inserted
* **config.fields** *string*

    comma separated list of fields to insert
* **config.rows** *array*

    Records to insert. Each entry **must** be a comma separated list of values.
    Their order **must** match *config.fields*

### optional
* **config.disable** *boolean*

    If the value is 1, the initializer will not be called.
    See [Components](../../COMPONENT.md#disable-components) for `if.result.hasMessage` and `expression`.

### Database connection

The initializer uses the connection that the table is mapped to in
`$GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']` (`Default` without
mapping). An option `identifier` is ignored.

### Example
```yaml
import:
  tasks:
    exampleTask:
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\InsertMultiple"
          config:
            # initializer is disabled if the expression is true
            disable:
              expression: "false"  # your condition
            table: sys_category
            fields: uid,pid,title,parent
            rows:
              10: 15,10,Urgent,0
              20: 16,10,Cool,15
              30: 17,10,Nasty,15
```
