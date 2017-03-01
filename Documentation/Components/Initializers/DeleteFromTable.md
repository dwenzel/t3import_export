DeleteFromTable
===============

Deletes record from a database table. 
Use this initializer if you have to delete records determined by *where* clause from table.
****
## Configuration

### required

* **class** *string*

    fully qualified class name, i.e. *CPSIT\T3importExport\Component\Initializer\DeleteFromTable*
* **config.table** *string*

        name of the table from which records should be deleted
* **config.where** *string* 

        where clause for *delete* query.

### optional
* **config.disable** *boolean*

    If the value is 1, the initializer will not be called. 
    If the value is an array and can be interpreted as content object it will be rendered. The result of the rendering
    will be interpreted as boolean. 
* **config.identifier** *string* 

    Identifier for a database registered with the [DatabaseConnectionService](../../Service/DATABASE_CONNECTION_SERVICE.md). 
    If not set the default TYPO3 database will be used.
### Example
```
module.tx_t3importexport.settings.import.tasks.exampleTask.initializers {
  1 {
    class = CPSIT\T3importExport\Component\Initializer\DeleteFromTable
    config {
      # initializer is disabled if condition is true
      disable = TEXT
      disable {
        value = 1
        if {
          # your TypoScript condition
        }
      }
      # identifier = fooDatabaseConnection
      
      table = sys_category
      where = title LIKE "%foo%"
    }
  }
}
```
