# event.ts
# import configuration for events
module.tx_t3import.settings.importProcessor.tasks {
	# unique identifier for this task
	event {
		source {
			// fully qualified class name of the data source. Default is
			#class = CPSIT\T3import\Persistence\DataSourceDB
			identifier = zew
			config {
				# configuration for query to remote database
				table = veranstaltung JOIN veranstaltungstyp2veranstaltung as MM ON (veranstaltung.lfdnr = MM.veranstaltung AND MM.veranstaltungstyp not in('7','1'))
				fields = lfdnr,titel,vontermin,bistermin,themeninhalt,allgemeine_info,ort,sprache
				orderBy =lfdnr
				limit = 5
			}
		}
		target {
			// fully qualified class name of the data target. Default is
			#class = CPSIT\T3import\Persistence\DataTargetRepository
			object {
				class = Webfox\T3events\Domain\Model\Event
			}
		}
		converters {
			1 {
				class = CPSIT\T3import\Component\Converter\ArrayToDomainObject
				config {
					targetClass = Webfox\T3events\Domain\Model\Event
					allowProperties = headline,subtitle,description,performances,eventType,eventLocation,genre,speakers,zewId,keywords,departments,tags
					properties {
						eventType {
							allowProperties = zewId
						}
						performances {
							allowAllProperties = 1
							# configuration for all children in object storage field
							children {
								maxItems = 5
								allowProperties = date,endDate,priceNotice,eventLocation
								properties {
									date {
										typeConverter {
											# use a type converter different from default PersistentObjectConverter
											class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
											options {
												dateFormat = U
											}
										}
									}
									endDate {
										typeConverter {
											class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
											options {
												dateFormat = U
											}
										}
									}
								}
							}

						}
						genre {
							allowAllProperties = 1
						}
						speakers {
							allowAllProperties = 1
						}
						departments {
							allowAllProperties = 1
						}
						tags {
							allowAllProperties = 1
						}
					}
				}
			}
		}
		# legacy configuration - see above for current!
		# target class for this import task
		class = Webfox\T3events\Domain\Model\Event
		sourceQueryConfiguration {
			# database connection as registered
			identifier = zew
			# configuration for query to remote database
			table = veranstaltung JOIN veranstaltungstyp2veranstaltung as MM ON (veranstaltung.lfdnr = MM.veranstaltung AND MM.veranstaltungstyp not in('7','1'))
			fields = lfdnr,titel,vontermin,bistermin,themeninhalt,allgemeine_info,ort,sprache
			orderBy =lfdnr
			limit = 5
		}
		# property mapping configuration
		propertyMapping {
			allowProperties = headline,subtitle,description,performances,eventType,eventLocation,genre,speakers,zewId,keywords,departments,tags
			properties {
				eventType {
					allowProperties = zewId
				}
				performances {
					allowAllProperties = 1
					# configuration for all children in object storage field
					children {
						maxItems = 5
						allowProperties = date,endDate,priceNotice,eventLocation
						properties {
							date {
								typeConverter {
									# use a type converter different from default PersistentObjectConverter
									class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
									options {
										dateFormat = U
									}
								}
							}
							endDate {
								typeConverter {
									class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
									options {
										dateFormat = U
									}
								}
							}
						}
					}

				}
				genre {
					allowAllProperties = 1
				}
				speakers {
					allowAllProperties = 1
				}
				departments {
					allowAllProperties = 1
				}
				tags {
					allowAllProperties = 1
				}
			}
		}
		preProcessors {
			# concatenate some fields to another field and wrap them
			1 {
				class = CPSIT\T3import\Component\PreProcessor\ConcatenateFields
				config {
					targetField = description
					fields {
						themeninhalt {
							wrap = <div class="topic"><h3>Themeninhalt</h3>|</div>
						}
						allgemeine_info {
							wrap = <div class="general-info"><h3>Zusatzinformation</h3>|</div>
						}
						ort {
							wrap = <div class="location"><h3>Ort</h3>|</div>
						}
					}
				}
			}
			# set language
			2 {
				class =CPSIT\T3import\Component\PreProcessor\MapFieldValues
				config {
					fields {
						sprache {
							targetField = languageUid
							values {
								DE = 0
								EN = 1
							}
						}
					}
				}
			}
			# look up related records from remote database
			3 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					identifier = zew
					select {
						table = veranstaltungstyp2veranstaltung
						fields = veranstaltungstyp,veranstaltungssubtyp
						where {
							AND {
								value = lfdnr
								condition = veranstaltung=
							}
						}
						singleRow = 1
					}
					fields {
						veranstaltungstyp.mapTo = event_type
						veranstaltungssubtyp.mapTo = event_sub_type
					}
				}
			}
			# match records with local records
			4 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					targetField = event_type
					select {
						table = tx_t3events_domain_model_eventtype
						fields = uid
						where {
							AND {
								value = event_type
								condition = deleted=0 AND hidden=0 AND zew_id=
							}
						}
						# default is multiple row for target field
						singleRow = 1
					}
					fields {
						uid.mapTo = event_type
					}
				}
			}
			# look up related records from remote database
			5 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					disable = TEXT
					disable {
						value = 1
						if.value = 0
						if.equals.field = event_sub_type
					}
					targetField = genre
					select {
						table = tx_t3events_domain_model_genre
						fields = uid
						where {
							AND {
								value = event_sub_type
								condition = deleted=0 AND hidden=0 AND zew_id=
							}
						}
					}
					fields {
						uid.mapTo = __identity
					}
				}
			}
			# relation between event and speaker
			# speaker = referent2veranstaltung (referent is in mitarbeiter)
			6 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					identifier = zew
					targetField = speakers_employee
					select {
						table = referent2veranstaltung
						fields = referent,veranstaltung,verantwortlicherodermitarbeiter
						where {
							AND {
								value = lfdnr
								condition = verantwortlicherodermitarbeiter="MA" AND veranstaltung=
							}
						}
					}
					fields {
						referent.mapTo = referent_id
					}
				}
			}
			# relation between event and speaker
			# speaker = referent2veranstaltung (referent is in verantwortlicher)
			7 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					identifier = zew
					targetField = speakers_responsible
					select {
						table = referent2veranstaltung
						fields = referent,veranstaltung,verantwortlicherodermitarbeiter
						where {
							AND {
								value = lfdnr
								condition = verantwortlicherodermitarbeiter="VA" AND veranstaltung=
							}
						}
					}
					fields {
						referent.mapTo = referent_id
					}
				}
			}
			# look up existing person
			8 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					targetField = speakers_employee
					select {
						table = tx_t3events_domain_model_person
						fields = uid
						where {
							AND {
								condition = deleted=0 AND hidden=0 AND
							}
							IN {
								field = zew_id
								values {
									field = speakers_employee
									prefix = MITARBEITER
									value = referent_id
								}
							}
						}
					}
					fields {
						uid.mapTo = __identity
					}
				}
			}
			# look up existing person
			9 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					targetField = speakers_responsible
					select {
						table = tx_t3events_domain_model_person
						fields = uid
						where {
							AND {
								condition = deleted=0 AND hidden=0 AND
							}
							IN {
								field = zew_id
								values {
									field = speakers_responsible
									prefix = VERANTWORTLICHER
									value = referent_id
								}
							}
						}
					}
					fields {
						uid.mapTo = __identity
					}
				}
			}
			# add speakers
			10 {
				class = CPSIT\T3import\Component\PreProcessor\AddArrays
				config {
					targetField = speakers
					fields = speakers_responsible,speakers_employee
				}
			}
			# lookup existing events
			11 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					targetField = uid
					select {
						table = tx_t3events_domain_model_event
						fields = uid
						where {
							AND {
								value = lfdnr
								prefix = EVENT
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
			# remove existing performances
			12 {
				class = CPSIT\ZewImports\PreProcessor\RemovePerformances
			}
			13 {
				class = CPSIT\ZewImports\PreProcessor\GeneratePerformances
				config {
					fields {
						date = vontermin
						endDate = bistermin
					}
				}
			}
			# get relation to department from source db
			14 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					identifier = zew
					targetField = departments
					select {
						table = veranstaltung2abteilung
						fields = abteilung
						where {
							AND {
								value = lfdnr
								condition = veranstaltung=
							}
						}
					}
					fields {
						abteilung.mapTo = department_id
					}
				}
			}
			# lookup and map to existing local department
			15 {
				class = CPSIT\T3import\Component\PreProcessor\LookUpDB
				config {
					targetField = departments
					select {
						table = tx_zewpersonaltool_domain_model_department
						fields = uid
						where {
							AND {
								condition = deleted=0 AND hidden=0 AND
							}
							IN {
								field = zew_id
								values {
									field = departments
									value = department_id
								}
							}
						}
					}
					fields {
						uid.mapTo = __identity
					}
				}
			}
			17 {
				class = CPSIT\T3import\Component\PreProcessor\MapFields
				config.fields {
					titel = headline
					event_type = eventType
				}
			}
			18 {
				class = CPSIT\T3import\Component\PreProcessor\RenderContent
				config.fields{
					zewId = TEXT
					zewId.value{
						field = lfdnr
						stdWrap.wrap = EVENT|
					}
				}
			}
		}
		postProcessors {
			1 {
				class = CPSIT\T3import\Component\PostProcessor\SetHiddenProperties
				config {
					fields {
						languageUid = 1
					}
					children {
						performances = 1
					}
				}
			}
		}
	}
}