# employee.ts
# import configuration for employees
module.tx_t3importexport.settings.importProcessor.tasks {
    xmlTEST {
        source {
            // for XML or other files
            class = CPSIT\T3importExport\Persistence\DataSourceDB
            identifier = test
            config {
                table = tx_extensionmanager_domain_model_extension
                fields = extension_key,repository,version,title,description,state,category,last_updated
                limit= 10,11
                //decoderClass = CPSIT\T3importExport\Domain\coder\XMLDecoder
            }
            # unique identifier of remote database connection
            # this connection has to be registered by the
            # DatabaseConnectionService
        }
        target {
            // fully qualified class name of the data target. Default is
            class = CPSIT\T3importExport\Persistence\DataTargetXMLStream
            object {
                class = CPSIT\T3importExport\Domain\Model\DataStream
            }

            config {
                # default is <?xml version="1.0" encoding="UTF-8"?>
                header = <?xml version="1.0" encoding="UTF-8"?>
                # default is rows
                rootNodeName = events
                // output are: direct (outputBuffer)|file (temp file)
                // direct is default
                output = file
                // cleared record memory if needed after every persist
                flush = true
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
            2 {
                class = CPSIT\T3importExport\Component\PreProcessor\RemoveFields
                config.fields {
                    extension_key = true
                    last_updated = true
                    static {
                        otherField = true
                    }
                    performances {
                        children {
                            uid = true
                            time = true
                            publishers {
                                children {
                                    uid = true
                                }
                            }
                        }
                    }
                }
            }
            3 {
                class = CPSIT\T3importExport\Component\PreProcessor\XMLMapper
                config.fields {
                    repository = @attribute
                    version = @attribute
                    state = @attribute
                    static {
                        mapTo = statistic
                        someField = @attribute
                    }
                    performances {
                        mapTo = schedules
                        children {
                            mapTo = schedule
                            location = @attribute
                            publishers {
                                mapTo = authors
                                children {
                                    mapTo = author
                                    pubId = @attribute
                                    rank = @attribute
                                }
                            }
                        }
                    }
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
                class = CPSIT\T3importExport\Component\Converter\ArrayToXMLStream
                config {
                    targetClass = CPSIT\T3importExport\Domain\Model\DataStream
                    # default node name is row
                    nodeName = event
                    fields {

                    }
                }
            }
        }
    }
}