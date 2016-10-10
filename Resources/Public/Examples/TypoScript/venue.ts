# venue.ts
# import configuration for venues (source table venues)
module.tx_t3importexport.settings.import.tasks {
	venue {
		class = Webfox\T3events\Domain\Model\EventLocation
		sourceQueryConfiguration {
			identifier = zew
			table = venues
			fields = id,name_de,name_en,street,zip_code,town_de,town_en,website_url_de,website_url_en
			where = id !=0 AND name_de !=''
			limit = 500
		}
		propertyMapping {
			allowProperties = name,address,zip,place,details,www,country,zewId
		}
		preProcessors {
			# look up existing event location
			1 {
				class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB
				config {
					targetField = zewId
					select {
						table = tx_t3events_domain_model_eventlocation
						fields = uid
						where {
							AND {
								condition = deleted=0 AND hidden=0 AND
							}
							IN {
								field = zew_id
								values {
									field = zew_id
									prefix = VENUE
									value = id
								}
							}
						}
					}
					fields {
						uid.mapTo = __identity
					}
				}
			}
			2 {
				class = CPSIT\T3importExport\Component\PreProcessor\RenderContent
				config.fields{
					zewId = TEXT
					zewId.value{
						field = id
						stdWrap.wrap = VENUE|
					}
				}
			}
			# match existing event location
			3 {
				class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB
				config {
					targetField = uid
					select {
						table = tx_t3events_domain_model_eventlocation
						fields = uid
						where {
							AND {
								value = id
								prefix = VENUE
								condition = deleted=0 AND hidden=0 AND zew_id=
							}
						}
						singleRow = 1
					}
					fields {
						uid.mapTo = __identity
					}
				}
			}
			#map fields if language is not english (i.e. german)
			4 {
				class = CPSIT\T3importExport\Component\PreProcessor\MapFields
				config {
					disable = TEXT
					disable {
						value = 1
						if.value = 1
						if.equals.field = languageUid
					}
					fields {
						name_de = name
						street = address
						zip_code = zip
						town_de = place
						website_url_de = www
					}
				}
			}
		}
	}
}
