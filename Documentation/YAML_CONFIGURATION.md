# YAML Configuration Guide

This guide provides comprehensive information about using YAML configuration files with the Import & Export extension.

## Overview

YAML is the only configuration format for tasks and sets since version 4.0. It offers:

- **Better Readability**: Clear hierarchical structure
- **Easier Maintenance**: Version control friendly
- **Environment Management**: Easy environment-specific configurations

Tasks and sets from YAML files in the configured directories
(`$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']`)
are available on the command line and in the backend modules (Import, Export).
Tasks and sets in TypoScript (`module.tx_t3importexport.settings`) are not
read any more since version 4.0; convert them with
`t3import-export:convert-typoscript`, see [Deprecations](./DEPRECATIONS.md#typoscript-configuration).

### Configuring the directories

Set the directories in the system configuration, e.g. in
`config/system/additional.php`:

```php
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories'] = [
    'EXT:my_sitepackage/Configuration/ImportExport',
];
```

- The configured list replaces the default
  (`EXT:t3import_export/Configuration/ImportExport`).
- All `*.yaml` files in each directory are loaded while the extension boots
  (`ext_localconf.php` of t3import_export). Directories added later, e.g. in the
  `ext_localconf.php` of an extension that depends on t3import_export and is
  therefore loaded after it, are not read.
- The files of a directory are loaded in alphabetical order; subdirectories
  and `*.yml` files are not read. A later file is merged into the
  configuration of the earlier ones.

## YAML Structure Reference

### Complete Configuration Schema

```yaml
import:
  tasks:
    taskIdentifier:
      label: "Human-readable task name"
      description: "Detailed task description"

      # Optional: Initialize before processing
      initializers:
        - class: "Initializer\\ClassName"
          config:
            # initializer-specific configuration

      # Required: Data source
      source:
        class: "DataSource\\ClassName"
        config:
          # source-specific configuration

      # Optional: Transform data before conversion
      preProcessors:
        - class: "PreProcessor\\ClassName"
          config:
            # preprocessor-specific configuration

      # Optional: Convert data format
      converters:
        - class: "Converter\\ClassName"
          config:
            # converter-specific configuration

      # Required: Data target
      target:
        class: "DataTarget\\ClassName"
        config:
          # target-specific configuration

      # Optional: Enhance objects after conversion
      postProcessors:
        - class: "PostProcessor\\ClassName"
          config:
            # postprocessor-specific configuration

      # Optional: Finalize after processing
      finishers:
        - class: "Finisher\\ClassName"
          config:
            # finisher-specific configuration

  sets:
    setIdentifier:
      label: "Human-readable set name"
      description: "Detailed set description"
      tasks: "task1,task2,task3"

export:
  # Same structure as import
  tasks: {}
  sets: {}
```

## Data Sources Configuration

### CSV Data Source

```yaml
source:
  class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
  config:
    file: "fileadmin/imports/data.csv"          # Required: File path
    delimiter: ","                              # Optional: Field delimiter (default: ,)
    enclosure: "\""                             # Optional: Field enclosure (default: ")
    escape: "\\"                                # Optional: Escape character (default: \)
    header: true                                # Optional: First row holds the field names (default: true without fields)
    encoding: "UTF-8"                           # Optional: File encoding (default: UTF-8)
```

### XML Data Source

```yaml
source:
  class: "CPSIT\\ImportExportCore\\File\\DataSourceXML"
  config:
    file: "fileadmin/imports/data.xml"          # Required: File path (or url)
    element: "record"                           # Name of the record elements (or expression)
    # expression: "//data/record"               # XPath expression for the record elements
    namespaces:                                 # Optional: XML namespaces
      ns1: "http://example.com/namespace1"
      ns2: "http://example.com/namespace2"
    lists: ["category"]                         # Optional: child elements that are always lists
```

See [DataSourceXML](./Persistence/DataSourceXML.md).

### Database Data Source

```yaml
source:
  class: "CPSIT\\T3importExport\\Persistence\\DataSourceDB"
  config:
    table: "source_table"                       # Required: Source table name
    fields: ["uid", "title"]                    # Optional: fields, default all
    where:                                      # Optional: field => value
      hidden: 0
      deleted: 0
    orderBy:                                    # Optional: field => ASC/DESC
      sorting: ASC
      title: ASC
    limit: 1000                                 # Optional: maximum number of records
    connection: "Default"                       # Optional: connection, default: table mapping
```

The options of earlier versions (`where` as SQL string, `join`, `groupBy`)
are still read, but deprecated; see
[DataSourceDB](./Persistence/DataSourceDB.md).

### Repository Data Source

```yaml
source:
  class: "CPSIT\\T3importExport\\Persistence\\DataSourceDynamicRepository"
  config:
    class: "Vendor\\Extension\\Domain\\Model\\Item"  # Required: domain model; its repository is used
    storagePids: "12,13"                        # Optional: storage pages
    languageId: 1                               # Optional: language
    limit: 100                                  # Optional: maximum number of records
    offset: 0                                   # Optional
```

The records are arrays of the properties of the domain objects.

## Data Targets Configuration

### Database Data Target

```yaml
target:
  class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
  config:
    table: "target_table"                       # Required: Target table name
    unsetKeys: "foo,bar"                        # Optional: Fields removed before writing
    connection: "Default"                       # Optional: Database connection name (default: table mapping)
    key: ["import_id"]                          # Optional: Fields that identify a row to update
```

A record with `__identity` updates the row with this `uid`. Fields such as
`pid` must be set in the record. See [DataTargetDB](Persistence/DataTargetDB.md).

### CSV Data Target

```yaml
target:
  class: "CPSIT\\ImportExportCore\\File\\DataTargetCSV"
  config:
    file: "fileadmin/exports/data.csv"          # Required: Output file path, replaced if it exists
    fields: "uid,title"                         # Optional: fields to write (default: fields of the first record)
    delimiter: ","                              # Optional: Field delimiter
    enclosure: "\""                             # Optional: Field enclosure
    header: true                                # Optional: Write the field names (default: true)
    bom: false                                  # Optional: UTF-8 byte order mark (default: false)
```

See [DataTargetCSV](./Persistence/DataTargetCSV.md).

### XML Stream Data Target

```yaml
target:
  class: "CPSIT\\T3importExport\\Persistence\\DataTargetXMLStream"
  config:
    output: "file"                              # write to a temporary file (default: the output)
    rootNodeName: "data"                        # Optional: Root element name (default: rows)
    rootAttributes:                             # Optional: Attributes of the root element
      version: "1.0"
    header: '<?xml version="1.0" encoding="UTF-8"?>'  # Optional: first line
    # template: "EXT:my_extension/Resources/Private/Export/feed.xml"  # Optional: template with {{CONTENT}}
```

The records must be converted to XML first, e.g. with the converter
`ArrayToXMLStream`. The finisher `WriteFile` stores the temporary file in a
storage, `DownloadFileStream` sends it to the browser.

## Component Configuration Examples

### Initializers

#### Truncate Tables
```yaml
initializers:
  - class: "CPSIT\\T3importExport\\Component\\Initializer\\TruncateTables"
    config:
      tables: "table1,table2"                   # Comma-separated table names
```

#### Delete from Table
```yaml
initializers:
  - class: "CPSIT\\T3importExport\\Component\\Initializer\\DeleteFromTable"
    config:
      table: "target_table"
      where: "pid = 123 AND sys_language_uid = 0" # SQL, used as it is
```

### PreProcessors

#### Copy, Rename and Map Values
Pre-processors of `cpsit/import-export-core`, see [Deprecations](./DEPRECATIONS.md#deprecated-in-32)
for the replacements of the pre-processors removed in 4.0.
```yaml
preProcessors:
  - class: "CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor"
    config:
      rules:
        - {rule: copy, field: new_title, from: source_title}
        - {rule: rename, field: new_field_name, from: old_field_name}
        - {rule: map, field: hidden, values: {active: 0, inactive: 1}, otherwise: 1}
        - {rule: remove, fields: [internal_note]}
```

#### Database Lookup
```yaml
preProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\LookUpDB"
    config:
      targetField: "category_uid"               # Removed from the record if nothing is found
      select:
        table: "sys_category"                   # Lookup table
        fields: "uid"                           # Columns to read
        where:
          AND:
            condition: "title="                 # SQL condition, followed by the quoted value
            value: "category_name"              # Record field with the lookup value
        singleRow: true                         # Use only the first row
      fields:
        uid:
          mapTo: "category_uid"                 # Record field for the column uid
```

See [LookUpDB](./Components/PreProcessors/LOOK_UP_DB.md) for the `IN`
constraint and the other options.

#### String to Time Conversion
```yaml
preProcessors:
  - class: "CPSIT\\ImportExportCore\\Processor\\StringToTimePreProcessor"
    config:
      fields: "publish_date,update_date"        # Comma-separated field names, parsed with strtotime()
      multipleRowFields: "dates"                # Optional: fields with a list of child rows
```

#### Set Field Values
```yaml
preProcessors:
  - class: "CPSIT\\ImportExportCore\\Processor\\SetValuesPreProcessor"
    config:
      values:                                   # existing values are replaced
        pid: 123
```

### PostProcessors

#### Generate File Reference
```yaml
postProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\GenerateFileReference"
    config:
      sourceField: "image_id"                   # Field with the uid of the file (sys_file)
      targetField: "image"                      # Field for the file reference
      targetPage: 42                            # Optional: page of the reference record
```

See [GenerateFileReference](./Components/PostProcessors/GenerateFileReferenc.md)
for array records.

#### Set Localization Parent
```yaml
postProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\SetL10nParent"
    config:
      subject:
        parentField: "l10n_parent"              # Field with the value to match
      parent:
        table: "tx_myext_record"                # Table of the localization parent
        matchField: "import_id"                 # Field compared with the value
        identityField: "uid"                    # Optional: written to l10n_parent
```

#### Recreate Slug
```yaml
postProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\RecreateSlug"
    config:
      tableName: "tx_news_domain_model_news"    # Optional: table (default: pages)
      slugField: "path_segment"                 # Optional: slug field (default: slug)
```

The slug is generated from the fields set in the TCA of the slug field.

### Finishers

#### Clear Cache
```yaml
finishers:
  - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
    config:
      pages: "1,3,5"                            # Comma-separated page IDs
      # all: 1                                  # or clear the caches of all pages
```

#### Validate XML
```yaml
finishers:
  - class: "CPSIT\\ImportExportCore\\File\\ValidateXMLFinisher"
    config:
      file: "fileadmin/exports/data.xml"        # File to validate (or url)
      schema:
        file: "fileadmin/schemas/data.xsd"      # Optional: XSD schema file (or url)
```

#### Move File
```yaml
finishers:
  - class: "CPSIT\\T3importExport\\Component\\Finisher\\MoveFile"
    config:
      source:
        name: "data.csv"                        # File in the storage
        directory: "temp"                       # Optional
        storage: 1                              # Optional (default: 1)
      target:
        name: "data-processed.csv"
        directory: "archive"                    # Optional, created if missing
        conflictMode: overrideExistingFile      # Optional: cancel, renameNewFile (default), overrideExistingFile
```

## Advanced Configuration Patterns

### Multi-Environment Setup

Files are merged in the order they are loaded: maps are merged, lists and
scalar values of the later file replace those of the earlier one. With
`--yaml-config` only the given file is read.

#### Base Configuration (base.yaml)
```yaml
import:
  tasks:
    baseTask:
      label: "Base Import Task"
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          delimiter: ","
          enclosure: "\""
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
```

#### Development Override (development.yaml)
```yaml
import:
  tasks:
    baseTask:
      source:
        config:
          file: "fileadmin/dev/test-data.csv"
      target:
        config:
          table: "dev_target_table"
```

#### Production Override (production.yaml)
```yaml
import:
  tasks:
    baseTask:
      source:
        config:
          # absolute path in an allowed directory (default: var/import/)
          file: "/var/www/html/var/import/production-data.csv"
      target:
        config:
          table: "target_table"
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\TruncateTables"
          config:
            tables: "target_table"
```

### Complex Data Transformation Pipeline

```yaml
import:
  tasks:
    complexTransform:
      label: "Complex Data Transformation"
      description: "Multi-step data transformation with validation and cleanup"

      # Clear target before import
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\TruncateTables"
          config:
            tables: "target_table"

      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "fileadmin/imports/complex-data.csv"

      preProcessors:
        # Step 1: Trim values
        10:
          class: "CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor"
          config:
            rules:
              - {rule: trim, field: name}
              - {rule: trim, field: desc}

        # Step 2: Map field names
        20:
          class: "CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor"
          config:
            rules:
              - {rule: rename, field: title, from: name}
              - {rule: rename, field: description, from: desc}
              - {rule: rename, field: category_name, from: cat}

        # Step 3: Convert dates with strtotime()
        30:
          class: "CPSIT\\ImportExportCore\\Processor\\StringToTimePreProcessor"
          config:
            fields: "publish_date"

        # Step 4: Lookup categories
        40:
          class: "CPSIT\\T3importExport\\Component\\PreProcessor\\LookUpDB"
          config:
            targetField: "category_uid"
            select:
              table: "sys_category"
              fields: "uid"
              where:
                AND:
                  condition: "title="
                  value: "category_name"
              singleRow: true
            fields:
              uid:
                mapTo: "category_uid"

        # Step 5: Set default values and remove fields
        50:
          class: "CPSIT\\ImportExportCore\\Processor\\SetValuesPreProcessor"
          config:
            values:
              pid: 123
        60:
          class: "CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor"
          config:
            rules:
              - {rule: remove, fields: [category_name]}

      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "tx_news_domain_model_news"

      postProcessors:
        # Generate URL slugs
        10:
          class: "CPSIT\\T3importExport\\Component\\PostProcessor\\RecreateSlug"
          config:
            tableName: "tx_news_domain_model_news"
            slugField: "path_segment"

      finishers:
        # Clear the caches of the news pages
        10:
          class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            pages: "123"
```

## Configuration Validation

There is no schema for the YAML files. Unknown keys are ignored. When a task
is created, the following cases throw an `InvalidConfigurationException`:

```yaml
# Invalid: Missing required fields
import:
  tasks:
    invalidTask:
      label: "Invalid Task"
      # ERROR: Missing 'source' and 'target'

# Invalid: Wrong class name
import:
  tasks:
    invalidTask:
      source:
        class: "NonExistentClass"              # ERROR: Class doesn't exist
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"

# Invalid: Malformed configuration of a component
import:
  tasks:
    invalidTask:
      source:
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceDB"
        config:
          table: ""                             # ERROR: Empty table name
```

A set that refers to an undefined task throws an
`InvalidConfigurationException` as well.

## Best Practices

### 1. File Organization

```
Configuration/ImportExport/
├── 10-import-tasks.yaml       # Import tasks
├── 20-export-tasks.yaml       # Export tasks
└── 30-sets.yaml               # Sets
```

Files are loaded in alphabetical order; subdirectories are not read. Use a
separate configured directory for environment-specific files, e.g. one that
is added in `config/system/additional.php` only on production.

### 2. Configuration Naming

```yaml
# Good: Descriptive task names
import:
  tasks:
    importNewsFromExternalAPI:
      label: "Import News from External API"

    migrateUserDataFromLegacySystem:
      label: "Migrate User Data from Legacy System"

# Bad: Generic names
import:
  tasks:
    task1:
      label: "Import"

    import2:
      label: "Another Import"
```

### 3. Documentation and Comments

```yaml
import:
  tasks:
    complexImport:
      label: "Complex Data Import"
      description: |
        This task imports data from the external CRM system.

        Processing steps:
        1. Clean and validate incoming data
        2. Map legacy field names to new schema
        3. Resolve category references via database lookup
        4. Generate file references for images
        5. Clear relevant caches

        Data source: CRM export via SFTP
        Schedule: Daily at 2:00 AM
        Dependencies: Categories must be imported first

      # Clear target table to ensure clean state
      initializers:
        - class: "CPSIT\\T3importExport\\Component\\Initializer\\TruncateTables"
          config:
            tables: "crm_contacts"
```

### 4. Error Handling

```yaml
import:
  tasks:
    robustImport:
      label: "Import with skipped rows"

      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "fileadmin/imports/data.csv"
          # false (default): rows with a wrong number of values are skipped
          # and reported; true: such a row ends the task
          strict: false

      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "tx_myext_record"
          # do not write records without title
          skip:
            ifEmpty:
              field: title
```

Skipped rows and other problems are added as messages to the task result;
later components can be disabled by their IDs, see
[Components](./COMPONENT.md#by-message).

## Command Line Usage

### Execute with YAML Configuration

```bash
# Execute specific export task from YAML file
vendor/bin/typo3 t3import-export:export-task taskName --yaml-config config.yaml

# Execute set from YAML file
vendor/bin/typo3 t3import-export:import-set setName --yaml-config config.yaml

# Use environment-specific configuration
vendor/bin/typo3 t3import-export:import-set fullImport --yaml-config config/production.yaml
```
