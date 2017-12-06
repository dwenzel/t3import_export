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

* **config.setfields** *array* 

	array of fields and values to use as *update* query.


### Optional

* **config.identifier** *string* 

    Identifier for a database registered with the [DatabaseConnectionService](../../Service/DATABASE_CONNECTION_SERVICE.md). 
    If not set the default TYPO3 database will be used.


### Example

One use case is to set all records defined by a where clause to hidden at the beginning of an import.

```
initializers {
	10 {
		class = CPSIT\T3importExport\Component\Initializer\UpdateTable
		config {
			table = pages
			where = pid = 1
			setfields {
				hidden = 1
			}
		}
	}
}
```

While importing data, all received records can be set/reset to hidden=0.

```
preProcessors {
	10 {
	    class = CPSIT\T3importExport\Component\PreProcessor\SetFieldValue
	    config {
	        targetField = hidden
	        value = 0
	    }
	}
}
```

So the import can take care syncing deleted records from the data souce.
