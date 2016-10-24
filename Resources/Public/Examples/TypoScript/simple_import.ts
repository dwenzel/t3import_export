# employee.ts
# import configuration for employees
module.tx_t3importexport.settings.import.tasks {
    employee {
        source {
            // need to be a registration in ext_localconf for this identifier
            identifier = test
            config {
                table = tx_t3events_domain_model_person_other
                fields = name,first_name,last_name
            }
        }
        target {
            // fully qualified class name of the data target. Default is
            //class = CPSIT\T3importExport\Persistence\DataTargetRepository
        }
        preProcessors {
            # set language
            1 {
                class = CPSIT\T3importExport\Component\PreProcessor\MapFields
                config.fields {
                    first_name = firstName
                    last_name = lastName
                }
            }
        }
        converters {
            1 {
                class = CPSIT\T3importExport\Component\Converter\ArrayToXML
            }
        }
    }
}
