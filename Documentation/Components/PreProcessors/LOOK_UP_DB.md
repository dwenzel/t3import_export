LookUpDB
========
todo


### Example
```
class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB	
config {
	select {
		table = tx_perfar_domain_model_countries
		fields = uid
		where {
			AND {
				condition = title=
				value = country
			}
		}
		singleRow = true
	}
	targetField = tx_perfar_countries
	fields {
		uid {
			mapTo = tx_perfar_countries
		}
	}
}
```