TruncateTables
==============

Truncates (empties) database tables.

## Configuration

### required

* **class** fully qualified class name, i.e. *CPSIT\T3importExport\Component\Initializer\TruncateTables*
* **config.tables** comma separated list of table names
    All tables will be truncated.
    
### optional
* **config.disable** *boolean*

    If the value is 1, the initializer will not be called. 
    If the value is an array and can be interpreted as content object it will be rendered. The result of the rendering
    will be interpreted as boolean. 
* **identifier** *string* 

    Identifier for a database registered with the [DatabaseConnectionService](../../Service/DATABASE_CONNECTION_SERVICE.md) 
    If not set the default TYPO3 database will be used.
    
### Example
```
module.tx_t3importexport.settings.import.tasks.exampleTask.initializers {
  1 {
    class = CPSIT\T3importExport\Component\Initializer\TruncateTables
    config {
      # initializer is disabled if condition is true
      disable = TEXT
      disable {
        value = 1
        if {
          # your TypoScript condition
        }
      }
      # all tables will be truncated!
      tables (
        tx_t3events_domain_model_event,tx_t3events_domain_model_performance,
        tx_t3events_event_person_mm,tx_t3events_event_genre_mm,tx_t3events_event_audience_mm,
        tx_t3events_event_department_mm,tx_t3events_event_tag_mm,tx_t3events_event_venue_mm
      )
    }
  }
}
```
