StringToTime
============

Processes attributes with the PHP function [strtotime()](https://secure.php.net/manual/de/function.strtotime.php) to create timestamps from text based date fields.

All date fields, which should be processed are added as a comma separated list to config.fields.


### Example

```
preProcessors {
	30 {
		class = CPSIT\T3importExport\Component\PreProcessor\StringToTime
		config {
			fields = created_at, published_at, updated_at
		}
	}
}
```

In many cases it makes sense to map the fields afterwards and remove the origial ones.


```
preProcessors {
	40 {
		class = CPSIT\T3importExport\Component\PreProcessor\MapFields
		config {
			fields {
				created_at = crdate
				published_at = lastUpdated
				updated_at = tstamp
			}
		}
	}
	50 {
		class = CPSIT\T3importExport\Component\PreProcessor\RemoveFields
		config {
			fields {
				published_at = true
				created_at = true
				updated_at = true
			}
		}
	}
}
```
