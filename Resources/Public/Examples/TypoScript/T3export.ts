# employee.ts
# import configuration for employees
module.tx_t3importexport.settings.importProcessor.tasks {
    xmlTEST {
        queue {
            size = 10
            // todo: rename to batchSize
            importBatchSize = 1000
        }
        source {
            // for XML or other files
            class = CPSIT\T3importExport\Persistence\DataSourceDB
            identifier = test
            config {
                table = tx_extensionmanager_domain_model_extension
                fields = extension_key,repository,version,title,description,state,category,last_updated
                limit= 10,5984
                //decoderClass = CPSIT\T3importExport\Domain\coder\XMLDecoder
            }
            # unique identifier of remote database connection
            # this connection has to be registered by the
            # DatabaseConnectionService
        }
        target {
            // fully qualified class name of the data target. Default is
            class = CPSIT\T3importExport\Persistence\DataTargetRepository
            object {
                class = CPSIT\T3importExport\Domain\Model\ExportTarget
            }
        }
        preProcessors {
            # set language
            1 {
                class = CPSIT\T3importExport\Component\PreProcessor\MapFields
                config.fields {
                    extension_key = extensionKey
                    last_updated = lastUpdated
                }
            }
            # set language
            /*
            2 {
                class = CPSIT\T3importExport\Component\PreProcessor\StringToTime
                config.fields = lastUpdated
            }
            */
        }
        // types = https://typo3.org/api/typo3cms/class_t_y_p_o3_1_1_c_m_s_1_1_extbase_1_1_property_1_1_type_converter_1_1_abstract_type_converter.html
        converters {
            1 {
                class = CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
                //class = CPSIT\T3importExport\Component\Converter\ArrayToDomainObject
                config {
                    targetClass = CPSIT\T3importExport\Domain\Model\ExportTarget
                    allowProperties = extensionKey,repository,version,title,description,state,category,lastUpdated
                    properties {
                        lastUpdated {
                            type = DateTime
                            typeConverter {
                                class = TYPO3\CMS\Extbase\Property\TypeConverter\DateTimeConverter
                                options {
                                    dateFormat = U
                                }
                            }
                        }
                        state {
                            type = integer
                        }
                    }
                }
            }
        }
        # property mapping configuration
        propertyMapping {
            allowProperties = extensionKey,repository,version,title,description,state,category,lastUpdated
            properties {
                lastUpdated {
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