# employee.ts
# import configuration for employees
module.tx_t3importexport.settings.importProcessor.tasks {
    employee {
        source {
            identifier = test
            config {
                table = tx_t3events_domain_model_person_other
                fields = name,first_name,last_name,pid,person_type,gender
            }
            # unique identifier of remote database connection
            # this connection has to be registered by the
            # DatabaseConnectionService
        }
        target {
            // fully qualified class name of the data target. Default is
            class = CPSIT\T3importExport\Persistence\DataTargetStreamRepository
            object {
                class = CPSIT\T3importExport\Domain\Model\XMLStream
            }
            // DataTargetStreamRepository
            // memory_saving default false (faster but consume more memory)
            // if true, it writes files and clear context
            // higher CPU and file-I/O usage but less Memory (ram) intensive
            config {
                memory_saving = true
            }
        }
        preProcessors {
            # set language
            1 {
                class = CPSIT\T3importExport\Component\PreProcessor\MapFields
                config.fields {
                    first_name = firstName
                    last_name = lastName
                    person_type = personType
                }
            }
        }
        converters {
            1 {
                class = CPSIT\T3importExport\Component\Converter\ArrayToDataStream
                //class = CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
                config {
                    targetClass = CPSIT\T3importExport\Domain\Model\XMLStream
                    allowProperties = name,firstName,lastName,birthday,performances
                    properties {
                        birthday {
                            typeConverter {
                                class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
                                options {
                                    dateFormat = U
                                }
                            }
                        }
                        performances {
                            allowAllProperties = 1
                            # configuration for all children in object storage field
                            children {
                                maxItems = 5
                                allowProperties = uuid,pid
                                properties {
                                    uuid {
                                        typeConverter {
                                            # use a type converter different from default PersistentObjectConverter
                                            class = TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter
                                        }
                                    }
                                    pid {
                                        typeConverter {
                                            class = TYPO3\CMS\Extbase\Property\TypeConverter\IntegerConverter
                                        }
                                    }
                                }
                            }

                        }
                    }
                }
            }
        }
    }
}