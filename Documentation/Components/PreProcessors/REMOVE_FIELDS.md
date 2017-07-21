RemoveFields
============
Removes attributes from the record. This is useful for cleaning up e.g. import data or after the usage of [PreProcessor MapFields](MAP_FIELDS.md).

### Example

```
preProcessors {
	61 {
		class = CPSIT\T3importExport\Component\PreProcessor\RemoveFields
		config {
			fields {
				nid = true
				node_type = true
				keywords = true
				title = true
				authors = true
				full_text_source_in_english = true
				leading_country = true
			}
		}
	}
}

```