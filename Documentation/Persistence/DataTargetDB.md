DataTargetDB
=============

Persists records in a database.

**Note!**
If a record has a field with the special key `__identity` its value will be set to the `uid` field of this record when creating or updating.


## Configuration

## required
| property               | type   | default | description       | 
| -----------------------|:------:|---------|-------------------|
| **class**              | string | none    | fully qualified class name, i.e. *CPSIT\T3importExport\Persistence\DataTargetDB* |
| **config.table**       | string | none    | target table in database |

## optional
| property               | type   | default | description       | 
| -----------------------|:------:|---------|-------------------|
| **config.identifier**  | string | none    | Identifier of a database connection registered with the [DatabaseConnectionService](../Service/DatabaseConnectionService.md) |
| **config.unsetKeys**   | string | none    | A comma separated list of field names. If set any of this fields  |

## Examples

### Simple
Persist records into table _tt_content_ of TYPO3 DB 
```
module.tx_t3importexport.settings.import.tasks.exampleTask {
  source {
    class = CPSIT\T3importExport\Persistence\DataTargetDB
    config {
      table = tt_content
    }
  }
}
```

### Extended
1. Save into a the table _foo_ of a database registered with identifier _bar_
```
module.tx_t3importexport.settings.import.tasks.exampleTask {
  source {
    identifier = bar
    class = CPSIT\T3importExport\Persistence\DataTargetDB
    config {
      table = foo
    }
  }
}
```

2. unset keys _foo_ and _bar_ of each record before persisting
```
module.tx_t3importexport.settings.import.tasks.exampleTask {
  source {
    class = CPSIT\T3importExport\Persistence\DataTargetDB
    config {
      table = tt_content
      unsetKeys = foo,bar
    }
  }
}
```

