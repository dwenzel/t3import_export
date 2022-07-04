DatabaseConnectionService
=========================
Provides access to pre-defined database connections.

Note: previous versions of this service allowed to register a connection. This is no longer the case.
Please register you database before-hand.

## Example
The example code below should be placed e.g. in a `AdditionalConfiguration.php` file. Never disclose any database credentials!

The database connection can be used via identifier:
```TypoScript
module.tx_t3importexport.settings.importProcessor.tasks {
 exampleTask {
 [...]
   source {
     identifier = xyz
     config {
       table = seminars
       fields = id,title_de,title_en,title_2_en,title_2_de,description_de,description_en,type,season_begin
       orderBy = id
       where = TEXT
       where {
         wrap = published=1 AND edited >='|'
         value = midnight
         strtotime = 1
         strftime = %Y-%m-%d
       }
       limit = 5000
     }
   }
 }
 [...]
}
```
For the default connection, the identifier can be ommited.
