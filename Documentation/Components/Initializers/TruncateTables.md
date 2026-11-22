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
    See [Components](../../COMPONENT.md#disable-components) for `if.result.hasMessage` and `expression`.
* **identifier** *string*

    Identifier for a database registered with the [DatabaseConnectionService](../../Service/DATABASE_CONNECTION_SERVICE.md)
    If not set the default TYPO3 database will be used.

### Example
```yaml
import:
  tasks:
    exampleTask:
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\TruncateTables"
          config:
            # initializer is disabled if the expression is true
            disable:
              expression: "false"  # your condition
            # all tables will be truncated!
            tables: >-
              tx_t3events_domain_model_event,tx_t3events_domain_model_performance,
              tx_t3events_event_person_mm,tx_t3events_event_genre_mm,tx_t3events_event_audience_mm,
              tx_t3events_event_department_mm,tx_t3events_event_tag_mm,tx_t3events_event_venue_mm
```
