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
Commands also accept a file with `--yaml-config`.

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
 order:

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
 * tasks

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
          pid: 123

      # Post-processing
      postProcessors:
        - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\RecreateSlug"
          config:
            field: "path_segment"

      # Finalize with cache clearing
      finishers:
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            cacheGroups: "pages,news"
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
2. **Class Validation**: All component classes must exist and implement proper interfaces
3. **Configuration Structure**: Component configurations must follow expected schema
4. **Identifier Uniqueness**: Task and set identifiers must be unique within their scope

### YAML Schema Validation

YAML configurations are automatically validated against internal schemas. Invalid configurations will generate clear error messages indicating the specific validation failures.

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
            confirmDeletion: true
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "/secure/imports/production-data.csv"
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "target_table"
      finishers:
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            cacheGroups: "all"
```

## Best Practices

### Configuration Management

1. **Separate Environments**: Use different configuration files for dev/staging/production
2. **Version Control**: Store configuration files in version control
3. **Documentation**: Document complex transformations and business logic
4. **Modular Design**: Break complex imports into smaller, manageable tasks

### Security Considerations

1. **File Paths**: Use absolute paths or EXT: syntax for security
2. **Database Access**: Limit database permissions for import/export operations
3. **Validation**: Always validate external data before processing
4. **Logging**: Enable appropriate logging for audit trails

### Performance Optimization

1. **Memory Management**: Use queue processing for large datasets
2. **Batch Processing**: Configure appropriate batch sizes
3. **Index Usage**: Ensure proper database indexes for lookups
4. **Cache Management**: Clear only necessary caches in finishers
