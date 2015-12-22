# speaker.ts
# import configuration for speakers (source table speakers)
module.tx_t3import.settings.importProcessor.tasks {
	speaker {
		class = Webfox\T3events\Domain\Model\Person
		sourceQueryConfiguration {
			identifier = zew
			table = speakers
			fields = id,name,gender,details_de,curriculum_de,curriculum_en,employee_code,portrait_filename
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
				class = CPSIT\T3import\Component\PreProcessor\LookUpLocalDB
				config {
					targetField = uid
					select {
						table = tx_t3events_domain_model_person
						fields = uid
						where {
							AND {
								value = id
								prefix = SPEAKER
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
			# set person type
			3 {
				class = CPSIT\T3import\Component\PreProcessor\SetFieldValue
				config {
					targetField = personType
					value = 2
				}
			}
			# map gender
			4 {
				class = CPSIT\T3import\Component\PreProcessor\MapFieldValues
				config {
					fields {
						gender {
							targetField = gender
							# sourceValue = targetValue
							values {
								m = 0
								f = 1
							}
						}
					}
				}
			}
			5 {
				class = CPSIT\T3import\Component\PreProcessor\MapFields
				config.fields {
					details_de = details
					curriculum_de = curriculum
					curriculum_en = englishCurriculum
					employee_code = shortIdentifier
					portrait_filename = imageFileName
				}
			}
			6 {
				class = CPSIT\T3import\Component\PreProcessor\RenderContent
				config.fields {
					zewId = TEXT
					zewId.value {
						field = id
						stdWrap.wrap = SPEAKER|
					}
				}
			}
		}
	}
}