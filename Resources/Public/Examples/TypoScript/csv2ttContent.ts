# csv2ttContent.ts
# configuration example for import of a CSV file into tt_content

module.tx_t3importexport.settings.import.tasks.csv2ttContent {
    label = CSV to tt_content
    description (
    Configuration example for an import task. This task uses a CSV file as data source and imports into the table tt_content.<br />
    In order to validate the import please adapt the target page id in TypoScript to an existing value.
    )
    source {
        class = CPSIT\T3importExport\Persistence\DataSourceCSV
        config {
            file = EXT:t3import_export/Resources/Public/Examples/CSV/csv2ttContent.csv
        }
    }

    preProcessors {
        1 {
            class = CPSIT\T3importExport\Component\PreProcessor\SetFieldValue
            config {
                targetField = pid
                # target page id
                value = 1
            }
        }
    }
    target {
        class = CPSIT\T3importExport\Persistence\DataTargetDB
        config {
            table = tt_content
        }
    }
}