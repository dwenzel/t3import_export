# employee.ts
# import configuration for employees
module.tx_t3importexport.settings.importProcessor.tasks {
    employee {
        source {
            identifier = test
            config {
                table = tx_t3events_domain_model_person_other
                fields = name,first_name,last_name
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
                }
            }
        }
        converters {
            1 {
                class = CPSIT\T3importExport\Component\Converter\ArrayToDataStream
                config {
                    targetClass = CPSIT\T3importExport\Domain\Model\DataStream
                }
            }
        }
    }
}