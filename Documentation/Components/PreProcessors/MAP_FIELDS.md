MapFields
=========
Maps attributes of the data source record to new attributes in the processed record.

It's good to use in combination with the [PreProcessor RemoveFields](REMOVE_FIELDS.md), if the originals of the mapped attributes are not needed under the former name.


### Example


```
preProcessors {
	41 {
		class = CPSIT\T3importExport\Component\PreProcessor\MapFields
		config {
			fields {
				title = header
				actors = actors
				are_effects_retroactive = effectsretroactive
				authors = authors
				date_effect = dateofeffect
				date_publication = dateofpublication
				full_text_source_in_english = fulltextsourceen
				leading_country = leadingcountry
			}
		}
	}
}
```