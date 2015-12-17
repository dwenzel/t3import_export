# seminar.ts
# import configuration for seminars (source table seminars)
module.tx_t3import.settings.importProcessor.tasks {
	seminar {
		# get all published seminars
		class = Webfox\T3events\Domain\Model\Event
		sourceQueryConfiguration {
			identifier = zew
			table = seminars
			where = published=1
			fields = id,title_de,title_en,title_2_en,title_2_de,description_de,description_en,type
			groupBy =
			orderBy = id
			limit = 5000
		}
		propertyMapping {
			allowProperties(
				headline,subtitle,description,performances,eventType,eventLocation,genre,speakers,zewId,keywords,departments,tags
				starttime,endtime
			)
			properties {
				eventType {
					allowProperties = zewId
				}
				performances {
					allowAllProperties = 1
					children {
						maxItems = 5
						allowProperties = date,endDate,priceNotice,eventLocation
						properties {
							date {
								typeConverter {
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
			# get seminar dates from source db (performances)
			1 {
				class = CPSIT\T3import\PreProcessor\LookUpSourceDB
				config {
					targetField = performances
					select {
						table = seminar_dates
						fields = id,begin,end,fee_netto_value,fee_currency,fee_tax_rate,fee_legacy_text,venue_id,allow_signup,is_fully_booked,is_cancelled
						where {
							AND {
								value = id
								condition = seminar_id=
							}
						}
					}
					fields {
						begin.mapTo = date
						end.mapTo = endDate
						fee_legacy_text.mapTo = fee_legacy_text
						fee_netto_value.mapTo = fee_netto_value
						fee_tax_rate.mapTo = fee_tax_rate
						fee_currency.mapTo = fee_currency
						venue_id.mapTo = venue_id
						allow_signup.mapTo = allow_signup
						is_fully_booked.mapTo = is_fully_booked
						is_cancelled.mapTo = is_cancelled
						id.mapTo = id
					}
				}
			}
			# convert date fields
			2 {
				class = CPSIT\T3import\PreProcessor\StringToTime
				config {
					fields = date,endDate
					multipleRowFields = performances
				}
			}
			# match local EventType
			3 {
				class = CPSIT\T3import\PreProcessor\LookUpLocalDB
				config {
					targetField = event_type
					select {
						table = tx_t3events_domain_model_eventtype
						fields = uid
						where {
							AND {
								condition = deleted=0 AND hidden=0 AND zew_id=1
							}
						}
						singleRow = 1
					}
					fields {
						uid.mapTo = event_type
					}
				}
			}
			# match local event location
			4 {
				class = CPSIT\T3import\PreProcessor\LookUpLocalDB
				config {
					targetField = eventLocation
					childRecords = performances
					select {
						table = tx_t3events_domain_model_eventlocation
						fields = uid
						where {
							AND {
								value = venue_id
								prefix = VENUE
								condition = deleted=0 AND hidden=0 AND zew_id=
							}
						}
						singleRow = 1
					}
					fields {
						uid.mapTo = eventLocation
					}
				}
			}
			# Relations between seminar and speaker
			5 {
				class = CPSIT\T3import\PreProcessor\LookUpSourceDB
				config {
					targetField = speakers
					select {
						table = seminars_speakers
						fields = *
						where {
							AND {
								value = id
								condition = seminar_id=
							}
						}
					}
					fields {
						speaker_id.mapTo = referent_id
					}
				}
			}
			# look up existing person
			6 {
				class = CPSIT\T3import\PreProcessor\LookUpLocalDB
				config {
					targetField = speakers
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
									field = speakers
									prefix = SPEAKER
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
			# match existing event
			7 {
				class = CPSIT\T3import\PreProcessor\LookUpLocalDB
				config {
					targetField = uid
					select {
						table = tx_t3events_domain_model_event
						fields = uid
						where {
							AND {
								value = id
								prefix = SEMINAR
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
			8 {
				class = CPSIT\ZewImports\PreProcessor\RemovePerformances
			}
			# generate performances
			9 {
				class = CPSIT\ZewImports\PreProcessor\GeneratePerformances
				config {
					fields {
						date = date
						endDate = endDate
						eventLocation = eventLocation
					}
				}
			}
			# match genres
			10 {
				class = CPSIT\T3import\PreProcessor\LookUpLocalDB
				config {
					targetField = genre
					select {
						table = tx_t3events_domain_model_genre
						fields = uid
						where {
							AND {
								value = type
								condition = deleted=0 AND hidden=0 AND zew_seminar_type=
							}
						}
					}
					fields {
						uid.mapTo = __identity
					}
				}
			}
			# guess language
			11 {
				class = CPSIT\ZewImports\PreProcessor\GuessSeminarLanguage
			}
			#map fields if language is english (1)
			12 {
				class = CPSIT\T3import\PreProcessor\MapFields
				config {
					disable = TEXT
					disable {
						value = 1
						if.value = 0
						if.equals.field = languageUid
					}
					fields {
						#sourceField = targetField
						title_en = headline
						title_2_en = subtitle
						description_en = description
					}
				}
			}
			#map fields if language is not english (i.e. german)
			13 {
				class = CPSIT\T3import\PreProcessor\MapFields
				config {
					disable = TEXT
					disable {
						value = 1
						if.value = 1
						if.equals.field = languageUid
					}
					fields {
						#sourceField = targetField
						title_de = headline
						title_2_de = subtitle
						description_de = description
					}
				}
			}
			# map fields (always)
			14 {
				class = CPSIT\T3import\PreProcessor\MapFields
				config.fields {
					event_type = eventType
				}
			}
			# render field values
			15 {
				class = CPSIT\T3import\PreProcessor\RenderContent
				config {
					fields {
						zewId = TEXT
						zewId {
							value.field = id
							stdWrap.wrap = SEMINAR|
						}
						starttime = COA
						starttime {
							10 = TEXT
							10 {
								value = 01.08.{field:season_begin}
								insertData = 1
							}
							stdWrap.strtotime = 1
						}
						endtime = COA
						endtime {
							10 = TEXT
							10 {
								value = 31.07.{field:season_begin}
								insertData = 1
							}
							stdWrap.strtotime = + 1 year
						}
						performances {
							multipleRows = 1
							fields {
								priceNotice = COA
								priceNotice {
									10 = TEXT
									10 {
										current = 1
										setCurrent {
											field = fee_netto_value
											dataWrap = | + ({field:fee_tax_rate} * {field:fee_netto_value})
										}
										prioriCalc = 1
										numberFormat = 1
										numberFormat {
											dec_point = ,
											decimals = 2
										}
										if {
											value = 0
											isGreaterThan.field = fee_netto_value
										}
									}
									20 = TEXT
									20 {
										field = fee_currency
										noTrimWrap = | ||
										if {
											isTrue.field = fee_netto_value
											value = 0
											isGreaterThan.field = fee_netto_value
										}
									}
									30 = TEXT
									30 {
										current = 1
										setCurrent {
											field = fee_tax_rate
											dataWrap = | *100
											if {
												isTrue.field = fee_netto_value
												value = 0
												isGreaterThan.field = fee_netto_value
											}
										}
										prioriCalc = 1
										numberFormat = 1
										numberFormat {
											dec_point = ,
											decimals = 2
										}
										noTrimWrap = | (inkl. | % UmSt.)|
										if {
											value = 0
											isGreaterThan.field = fee_netto_value
											isTrue.field = fee_netto_value
										}
									}
									40 = TEXT
									40 {
										current = 1
										setCurrent {
											field = fee_legacy_text
											if {
												isTrue.field = fee_legacy_text
												value = 0.0
												equals.field = fee_netto_value
											}
										}
									}
								}

								# status code is a bit mask of three fields
								status_code = TEXT
								status_code {
									current = 1
									setCurrent {
										field = is_cancelled
										dataWrap = | + ({field:is_fully_booked}*2) + ({field:allow_signup}*4)
									}
									prioriCalc = 1
								}

								status = COA
								status {
									10 = TEXT
									10 {
										# bookable (buchbar)
										value = 3
										if {
											value = 4
											isInList.field = status_code
										}
									}
									20 = TEXT
									20 {
										# sold out (ausgebucht)
										value = 4
										if {
											value = 2,6
											isInList.field = status_code
										}
									}
									30 = TEXT
									30 {
										# cancelled (abgesagt)
										value = 5
										if {
											value = 1,3,5,7
											isInList.field = status_code
										}
									}
									40 = TEXT
									40 {
										# unknown
										value = 10
										if {
											value = 0
											equals.field = status_code
										}
									}
								}
								externalProviderLink = TEXT
								externalProviderLink {
									value.field = id
									stdWrap.wrap = http://www.zew.de/cake/Visitors/register/|/lang:de
									if {
										value = 3
										equals.field = status
									}
								}
							}
						}

					}
				}
			}
			# relation between seminar and tags
			16 {
				class = CPSIT\T3import\PreProcessor\LookUpSourceDB
				config {
					targetField = tags
					select {
						table = seminars_tags
						fields = tag_id
						where {
							AND {
								value = id
								condition = seminar_id=
							}
						}
					}
					fields {
						tag_id.mapTo = tag_id
					}
				}
			}
			# lookup existing tags
			17 {
				class = CPSIT\T3import\PreProcessor\LookUpLocalDB
				config {
					#disable = 1
					targetField = tags
					select {
						table = tx_zewtags_domain_model_tag
						fields = uid
						where {
							AND {
								condition = deleted=0 AND hidden=0 AND
							}
							IN {
								field = zew_id
								values {
									field = tags
									value = tag_id
								}
							}
						}
					}
					fields {
						uid.mapTo = __identity
					}
				}
			}

		}
		postProcessors {
			1 {
				class = CPSIT\T3import\PostProcessor\SetHiddenProperties
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