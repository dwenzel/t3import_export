MapFieldValues
==============
todo


### Example
```
preProcessors {
	22 {
		class = CPSIT\T3importExport\Component\PreProcessor\MapFieldValues	
		config {
			fields {

				# the attribute anme of the processed record with the value to map
				datasource_fieldname {
				
					# set the new attributes which will include the mapped value, here the pid
					targetField = pid
					
					# define n mappings, here from a source int to a target int
					values {
						1 = 16 # sets pid to 16, if datasource_fieldname has the value 1
						2 = 14 # sets pid to 2, if datasource_fieldname has the value 2
					}
				}
			}
		}
	}
}
