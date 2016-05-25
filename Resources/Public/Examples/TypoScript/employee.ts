# employee.ts
# import configuration for employees
module.tx_t3importexport.settings.importProcessor.tasks {
	employee {
		# target class for this import task
		class = Webfox\T3events\Domain\Model\Person
		sourceQueryConfiguration {
			# unique identifier of remote database connection
			# this connection has to be registered by the
			# DatabaseConnectionService
			identifier = zew
			table = mitarbeiter
			fields = nr,nachname,vorname,geschlecht,kurz,titel_1,leblauf_de,leblauf_en
		}
		# property mapping configuration
		# unknown properties are skipped by default
		propertyMapping {
			allowProperties (
				firstName,lastName,name,gender,shortIdentifier,curriculum,
				details,englishCurriculum,personType,zewId
			)
		}
		# register pre-processors
		preProcessors {
			# match existing persons from local database (TYPO3)
			1 {
				class = CPSIT\T3importExport\Component\PreProcessor\LookUpLocalDB
				config {
					targetField = uid
					select {
						table = tx_t3events_domain_model_person
						fields = uid
						where {
							AND {
								value = nr
								prefix = MITARBEITER
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
			2 {
				class = CPSIT\T3importExport\Component\PreProcessor\SetFieldValue
				config {
					targetField = personType
					value = 1
				}
			}
			# map gender
			3 {
				class = CPSIT\T3importExport\Component\PreProcessor\MapFieldValues
				config {
					fields {
						gender {
							targetField = gender
							# source value = target value
							values {
								m = 0
								w = 1
							}
						}
					}
				}
			}
			# render content
			4 {
				class = CPSIT\T3importExport\Component\PreProcessor\RenderContent
				config {
					fields {
						zewId = TEXT
						zewId {
							value.field = nr
							stdWrap.wrap = MITARBEITER|
						}
					}
				}
			}
			# concatenate some fields to another and wrap them
			5 {
				class = CPSIT\T3importExport\Component\PreProcessor\ConcatenateFields
				config {
					targetField = name
					# fields must be array with field names as keys
					fields {
						titel_1 {
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
			# map field names
			6 {
				class = CPSIT\T3importExport\Component\PreProcessor\MapFields
				config.fields {
					nachname = lastName
					vorname = firstName
					leblauf_de= curriculum
					leblauf_en = englishCurriculum
					kurz = shortIdentifier
				}
			}
		}
	}
}
