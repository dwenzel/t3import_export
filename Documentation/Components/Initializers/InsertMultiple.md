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
    If the value is an array and can be interpreted as content object it will be rendered. The result of the rendering
    will be interpreted as boolean. 
* **config.identifier** *string* 

    Identifier for a database registered with the [DatabaseConnectionService](../../Service/DATABASE_CONNECTION_SERVICE.md) 
    If not set the default TYPO3 database will be used.
### Example
```
module.tx_t3importexport.settings.import.tasks.exampleTask.initializers {
  1 {
    class = CPSIT\T3importExport\Component\Initializer\InsertMultiple
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
      fields = uid,pid,title,parent
      rows {
        10 = 15,10,Urgent,0
        20 = 16,10,Cool,15
        30 = 17,10,Nasty,15
      }
    }
  }
}
```
