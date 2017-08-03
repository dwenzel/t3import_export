DatabaseConnectionService
=========================
Allows to register a database connection. This connection then can be used by its identifier. Thus no connection details have to be provided in TypoScript.

## Example
The example code below should be placed e.g. in a `AdditionalConfiguration.php` file. Never disclose any database credentials!

```PHP
# register databases depending on application context
if(\TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext()->isDevelopment()) {
  # DEV
  \CPSIT\T3importExport\Service\DatabaseConnectionService::register(
    'xyz',              // identifer
    '192.168.1.123',    // host (default is 127.0.0.1)
    'db-345',           // database name
    'foo',              // user name
    'hoUMmSeUpwAU',     // password
    3322                // port (default is 3306)
  );
} elseif(\TYPO3\CMS\Core\Utility\GeneralUtility::getApplicationContext()->isProduction()) {
  # LIVE
  \CPSIT\T3importExport\Service\DatabaseConnectionService::register(
    'xyz',
    'other.host.com',
    'db-live-23',
    'bar',
    'MEErencRutru',
    3377
  );
} else {
  #local dev
  \CPSIT\T3importExport\Service\DatabaseConnectionService::register(
    'xyz',
    '192.168.1.123',
    'db-345',
    'foo',
    'hoUMmSeUpwAU',
    33306
  );
}
```
The database connection can now be used via identifier:
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
