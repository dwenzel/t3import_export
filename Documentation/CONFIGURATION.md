Configuration
=============

Tasks and sets are configured in YAML files. The TypoScript configuration
(`module.tx_t3importexport.settings`) was removed in version 4.0, see
[Deprecations](./DEPRECATIONS.md#typoscript-configuration). The command
`t3import-export:convert-typoscript` converts an existing TypoScript
configuration to YAML.

YAML files in the configured directories
(`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']`)
are loaded automatically, see [YAML configuration](./YAML_CONFIGURATION.md#configuring-the-directories).
Commands also accept a file with `--yaml-config`. The path is relative to the
working directory; it is read as is and is not restricted to the allowed
directories (command line only).

## Tasks
Import tasks are described below `import.tasks`, export tasks below
`export.tasks`:

```yaml
import:
  tasks:
    event:
      label: Incremental import of events
      description: Imports recent events from foo.com.
      # ...
```

Identifiers **must** be unique and **must not** contain white space or dots (.)

Each configuration of a task **must** contain the keys
 * source
 * target

 and **may** contain the keys

 * label
 * description
 * initializers
 * preProcessors
 * converters
 * postProcessors
 * finishers

 If *label* and *description* are set they will appear in the Backend module. Otherwise only the key is shown.

 For *description* HTML markup is allowed.

 The keys *initializers*, *preProcessors*, *converters*, *postProcessors* and
 *finishers* contain a list of components, which are processed in the given
 order. With integer keys (`10`, `20`), the components run in the order of
 the keys, see
 [Deprecations](./DEPRECATIONS.md#changes-of-cpsitimport-export-core-06):

```yaml
      preProcessors:
        - class: "First\\PreProcessor"
          config:
            # configuration for the first pre-processor
        - class: "Second\\PreProcessor"
          config:
            # configuration for the second pre-processor
```

## Sets
Import sets are described below `import.sets`, export sets below
`export.sets`. Identifiers **must** be unique.

Each configuration of a set **must** contain the key
 * tasks (comma separated identifiers or a list)

 and **may** contain the keys

 * label
 * description

 If label and description are set they will appear in the Backend module. Otherwise only the key is shown.

Configuration for import set with identifier *fullImport*:

```yaml
import:
  sets:
    fullImport:
      label: Full Import
      description: This is a really huge import set.
      tasks: event,internet,allTheRest
```

## YAML Configuration

The extension supports modern YAML configuration files that can be loaded via command line or programmatically. YAML configuration offers better readability and is easier to manage for complex setups.

### Basic YAML Structure

```yaml
import:
  tasks:
    taskName:
      label: "Task Label"
      description: "Task Description"
      source:
        class: "SourceClass"
        config:
          # source-specific configuration
      target:
        class: "TargetClass"
        config:
          # target-specific configuration
      # Optional component configurations
      initializers:
        - class: "InitializerClass"
          config:
            # initializer configuration
      preProcessors:
        - class: "PreProcessorClass"
          config:
            # preprocessor configuration
      converters:
        - class: "ConverterClass"
          config:
            # converter configuration
      postProcessors:
        - class: "PostProcessorClass"
          config:
            # postprocessor configuration
      finishers:
        - class: "FinisherClass"
          config:
            # finisher configuration
  sets:
    setName:
      label: "Set Label"
      description: "Set Description"
      tasks: "task1,task2,task3"

export:
  tasks:
    # export tasks configuration
  sets:
    # export sets configuration
```

### YAML Task Example

Complete example of a YAML import task:

```yaml
import:
  tasks:
    newsImport:
      label: "News Import from CSV"
      description: "Import news articles from external CSV file with field mapping and validation"

      # Initialize by clearing target table
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\TruncateTables"
          config:
            tables: "tx_news_domain_model_news"

      # Data source configuration
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "fileadmin/imports/news.csv"
          delimiter: ";"
          enclosure: "\""

      # Data transformation
      preProcessors:
        - class: "CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor"
          config:
            rules:
              - {rule: copy, field: title, from: headline}
              - {rule: copy, field: bodytext, from: content}
              - {rule: copy, field: author, from: author_name}
              - {rule: copy, field: datetime, from: publish_date}
        # parses the date with strtotime()
        - class: "CPSIT\\ImportExportCore\\Processor\\StringToTimePreProcessor"
          config:
            fields: "datetime"

      # Target configuration
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "tx_news_domain_model_news"

      # Post-processing
      postProcessors:
        - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\RecreateSlug"
          config:
            tableName: "tx_news_domain_model_news"
            slugField: "path_segment"

      # Finalize with cache clearing
      finishers:
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            pages: "42"
```

### YAML Set Example

```yaml
import:
  tasks:
    importCategories:
      label: "Import News Categories"
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "fileadmin/imports/categories.csv"
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "sys_category"

    importNews:
      label: "Import News Articles"
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "fileadmin/imports/news.csv"
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "tx_news_domain_model_news"

  sets:
    fullNewsImport:
      label: "Complete News Import"
      description: "Import categories first, then news articles to ensure proper relationships"
      tasks: "importCategories,importNews"
```

### Loading YAML Configuration

#### Command Line Usage

```bash
# Execute specific set from YAML file
vendor/bin/typo3 t3import-export:import-set fullNewsImport --yaml-config fileadmin/config/news-import.yaml

# Execute export set from YAML
vendor/bin/typo3 t3import-export:export-set newsExport --yaml-config fileadmin/config/news-export.yaml

# Execute a single export task from YAML
vendor/bin/typo3 t3import-export:export-task exportNews --yaml-config fileadmin/config/news-export.yaml
```

#### Programmatic Loading

```php
use CPSIT\ImportExportCore\Configuration\YamlConfigurationLoader;
use CPSIT\ImportExportCore\Service\YamlConfigurationParser;

$parser = new YamlConfigurationParser();
$loader = new YamlConfigurationLoader($parser);
$configuration = $loader->load('/path/to/config.yaml');

// Structure: ['import' => ['tasks' => ..., 'sets' => ...], 'export' => [...]]
```

## Configuration Validation

The extension validates the configuration of tasks and sets:

### Common Validation Rules

1. **Required Fields**: `source` and `target` are mandatory for all tasks
2. **Class Validation**: All component classes must exist and implement the
   interface of their type
3. **Component Configuration**: Each component checks its configuration
   (`isConfigurationValid()`); an invalid configuration throws an
   `InvalidConfigurationException` when the task is created
4. **Identifier Uniqueness**: Task and set identifiers must be unique within their scope;
   a later file with the same identifier is merged into the earlier one
5. **Sets**: A set that refers to an undefined task throws an
   `InvalidConfigurationException`

There is no schema for the YAML files; unknown keys are ignored.

## Environment-Specific Configuration

### Development Configuration

```yaml
# development.yaml
import:
  tasks:
    debugTask:
      label: "Debug Import Task"
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "fileadmin/dev/test-data.csv"
      target:
        class: "CPSIT\\ImportExportCore\\Persistence\\NullDataTarget"
```

### Production Configuration

```yaml
# production.yaml
import:
  tasks:
    productionImport:
      label: "Production Data Import"
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\TruncateTables"
          config:
            tables: "target_table"
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          # absolute path in an allowed directory (default: var/import/)
          file: "/var/www/html/var/import/production-data.csv"
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "target_table"
      finishers:
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            all: 1
```

## Best Practices

### Configuration Management

1. **Separate Environments**: Use different configuration files for dev/staging/production
2. **Version Control**: Store configuration files in version control
3. **Documentation**: Document complex transformations and business logic
4. **Modular Design**: Break complex imports into smaller, manageable tasks

### Security Considerations

1. **Files and URLs**: Files are read and written only in the allowed
   directories, URLs are loaded only from the allowed hosts, see
   [Files](./Persistence/Files.md)
2. **Database Access**: Limit database permissions for import/export operations;
   SQL in options (`where` of `DataSourceDB`, `DeleteFromTable`, `LookUpDB`)
   is used as it is, so configurations must come from trusted sources
3. **Validation**: Always validate external data before processing
4. **Logging**: Enable appropriate logging for audit trails

### Performance Optimization

1. **Memory Management**: The records of a task are read before they are
   processed; split large datasets with a queue (`limit` of `DataSourceQueue`)
2. **Batch Processing**: The processor writes in batches of 100 records;
   `DataTargetDB` commits one transaction per batch
3. **Index Usage**: Ensure proper database indexes for lookups
4. **Cache Management**: Clear only necessary caches in finishers
