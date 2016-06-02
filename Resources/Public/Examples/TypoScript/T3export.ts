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
    }
}