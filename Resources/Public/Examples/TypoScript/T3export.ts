# employee.ts
# import configuration for employees
module.tx_t3importexport.settings.export.tasks {
    xmlKursnet {
        source {
            // for XML or other files
            class = CPSIT\T3importExport\Persistence\DataSourceDynamicRepository
            config {
                class = CPSIT\IhkofEvents\Domain\Model\Schedule
                constraints {
                    AND {
                        greaterThan {
                            date = NOW
                        }
                        equals {
                            event\.eventType = 2
                        }
                    }
                }
                storagePids = 4086
                #limit = 0
                #offset = 0
            }
        }
        preProcessors {
            1 {
                class = CPSIT\T3importExport\Component\PreProcessor\PerformanceToKursnetArray
                config {
                    class = CPSIT\IhkofEvents\Domain\Model\Schedule
                    fields {
                        SUPPLIER_ID_REF = 164382
                        SUPPLIER_ALT_PID = IHK-OF-3317
                        /*
                        0|Keine Zuordnung möglich
                        100|Allgemeinbildung
                        101|Berufliche Grundqualifikation
                        102|Berufsausbildung
                        103|Gesetzlich/gesetzesähnlich geregelte Fortbildung/Qualifizierung
                        104|Fortbildung/Qualifizierung
                        105|Nachholen des Berufsabschlusses
                        106|Rehabilitation
                        107|Studienangebot - grundständig
                        108|Studienangebot - weiterfährend
                        109|Umschulung
                        110|Integrationssprachkurse (BAMF)
                        */
                        EDUCATION_TYPE = 109
                    }
                }
            }
            2 {
                class = CPSIT\T3importExport\Component\PreProcessor\XMLMapper
                config.fields {
                    mode = @attribute
                    SUPPLIER_ID_REF {
                        type = @attribute
                        content = @value
                    }
                    SERVICE_DETAILS {
                        KEYWORD = @separateRow
                        SERVICE_MODULE {
                            EDUCATION {
                                type = @attribute
                                DEGREE {
                                    type = @attribute
                                    DEGREE_EXAM {
                                        type = @attribute
                                    }
                                }
                                EXTENDED_INFO {
                                    INSTITUTION {
                                        type = @attribute
                                        content = @value
                                    }
                                    INSTRUCTION_FORM {
                                        type = @attribute
                                        content = @value
                                    }
                                    EDUCATION_TYPE {
                                        type = @attribute
                                        content = @value
                                    }
                                }
                                MODULE_COURSE {
                                    DURATION {
                                        type = @attribute
                                    }
                                    EXTENDED_INFO {
                                        SEGMENT_TYPE {
                                            type = @attribute
                                        }
                                    }
                                }
                            }
                        }
                    }
                }
            }
        }

        converters {
            1 {
                class = CPSIT\T3importExport\Component\Converter\ArrayToXMLStream
                config {
                    targetClass = CPSIT\T3importExport\Domain\Model\DataStream
                    # default node name is row
                    nodeName = SERVICE
                    fields {
                    }
                }
            }
        }

        target {
            // fully qualified class name of the data target. Default is
            class = CPSIT\T3importExport\Persistence\DataTargetXMLStream
            object {
                class = CPSIT\T3importExport\Domain\Model\DataStream
            }

            config {
                # use template instead of dynamic rendering
                # CONTENT placeholder is important otherwise the tpl logic didn't work
                template = EXT:t3import_export/Resources/Public/Template/skeleton.tpl.xml
                # NOW is an placeholder for the current date
                templateReplace {
                    currentTime = NOW
                    supplierId = 32248
                }

                # default is <?xml version="1.0" encoding="UTF-8"?>
                #header = <?xml version="1.0" encoding="UTF-8" standalone="yes"?>
                # default is rows
                #rootNodeName = OPENQCAT
                #rootAttributes {
                #    xmlns:xsi = http://www.w3.org/2001/XMLSchema-instance
                #    version = 1.1
                #    xsi:noNamespaceSchemaLocation = openQ-cat.V1.1.xsd
                #}
                // output are: direct (outputBuffer)|file (temp file)
                // direct is default
                output = file
                // cleared record memory if needed after every persist
                flush = true
            }
        }

        finishers {
            1 {
                class = CPSIT\T3importExport\Component\Finisher\DownloadFileStream
                config {
                    fileExt = xml
                    filename = textExport_kursnet
                    type = text/xml
                }
            }
        }
    }
}
