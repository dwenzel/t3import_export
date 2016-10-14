# responsible.ts
# import configuration for responsibles (source table verantwortlicher)
module.tx_t3importexport.settings.import.tasks {
	responsible {
		class = DWenzel\T3events\Domain\Model\Person
		sourceQueryConfiguration {
			identifier = zew
			table = verantwortlicher
			fields = lfdnr,nachname,vorname,titel,firma,url
		}
		propertyMapping {
			allowProperties (
				name,gender,details,curriculum,englishCurriculum,shortIdentifier,imageFileName,
				personType,zewId
			)
		}
		preProcessors {
			# match existing persons
			1 {
				class = CPSIT\T3importExport\Component\PreProcessor\LookUpDB
				config {
					targetField = uid
					select {
						table = tx_t3events_domain_model_person
						fields = uid
						where {
							AND {
								value = lfdnr
								prefix = VERANTWORTLICHER
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
			2 {
				class = CPSIT\T3importExport\Component\PreProcessor\RenderContent
				config {
					fields {
						personType = TEXT
						personType.value = 3
						zewId = TEXT
						zewId {
							value.field = lfdnr
							stdWrap.wrap = VERANTWORTLICHER|
						}
						lastName = TEXT
						lastName.value.field = nachname
						firstName = TEXT
						firstName.value.field = vorname
						details = TEXT
						details.value.field = firma
						externalLink = TEXT
						externalLink.value.field = url
					}
				}
			}
			# concatenate vorname,nachname,titel to name
			3 {
				class = CPSIT\T3importExport\Component\PreProcessor\ConcatenateFields
				config {
					targetField = name
					# field must be array with field names as key
					fields {
						titel {
							foo= 1
						}
						vorname {
							noTrimWrap = | ||
						}
						nachname {
							noTrimWrap = | ||
						}
					}
				}
			}
		}
	}
}
