LookUpDB
========
The LookUpDB PreProcessor gets a value from the source record, creates a sql lookup query and sets a new value based on the lookup result to the processed record.


### Example
```
class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB	
config {
	select {

		# the database table where we want to look for an entry
		table = lookup_tablename
		
		# the column we like to use as result of the lookup proprocessor
		fields = uid
		
		where {
			AND {
			
				# the sql where clause, in this case we like to compare the title column of the lookup table with a value
				condition = title=

				# the attribute of our source record we want to copmpare with the title column
				value = source_fieldname
				
			}
		}
		singleRow = true
	}
	
	# the attribute containing the lookup value in our processed record after the pre processing
	targetField = target_fieldname

	fields {

		# map the uid from the lookup record to the attribute in our pre processor record
		uid {
			mapTo = target_fieldname
		}
	}
}
```
