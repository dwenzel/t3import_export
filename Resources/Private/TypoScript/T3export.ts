# employee.ts
# import configuration for employees
module.tx_t3importexport.settings.importProcessor.tasks {
    employee {
        source {
            identifier = test
            config {
                table = tx_t3events_domain_model_person_other
                fields = name,first_name,last_name,pid,person_type,gender,birthday
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
            # set language
            2 {
                class = CPSIT\T3importExport\Component\PreProcessor\StringToTime
                config.fields = birthday
            }
        }
        // types = https://typo3.org/api/typo3cms/class_t_y_p_o3_1_1_c_m_s_1_1_extbase_1_1_property_1_1_type_converter_1_1_abstract_type_converter.html
        converters {
            1 {
                class = CPSIT\T3importExport\Component\Converter\ArrayToDataStream
                //class = CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
                config {
                    targetClass = CPSIT\T3importExport\Domain\Model\XMLStream
                    allowProperties = name,firstName,lastName,birthday,personType
                    properties {
                        birthday {
                            type = DateTime
                            typeConverter {
                                class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
                                options {
                                    dateFormat = U
                                }
                            }
                        }
                        personType {
                            type = integer
                        }
                    }
                }
            }
        }
        # property mapping configuration
        propertyMapping {
            allowProperties = name,firstName,lastName,birthday,personType
            properties {
                birthday {
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
}