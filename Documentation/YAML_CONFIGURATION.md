# YAML Configuration Guide

This guide provides comprehensive information about using YAML configuration files with the T3Import_Export extension.

## Overview

YAML configuration provides a modern, structured alternative to TypoScript configuration. It offers:

- **Better Readability**: Clear hierarchical structure
- **Easier Maintenance**: Version control friendly
- **Validation Support**: Built-in schema validation
- **Environment Management**: Easy environment-specific configurations

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
  class: "CPSIT\\T3importExport\\Persistence\\DataSourceCSV"
  config:
    file: "fileadmin/imports/data.csv"          # Required: File path
    delimiter: ","                              # Optional: Field delimiter (default: ,)
    enclosure: "\""                             # Optional: Field enclosure (default: ")
    escape: "\\"                                # Optional: Escape character (default: \)
    skipLines: 1                                # Optional: Skip header lines (default: 0)
    encoding: "UTF-8"                           # Optional: File encoding (default: UTF-8)
```

### XML Data Source

```yaml
source:
  class: "CPSIT\\T3importExport\\Persistence\\DataSourceXML"
  config:
    file: "fileadmin/imports/data.xml"          # Required: File path
    xpath: "//record"                           # Optional: XPath expression for records
    rootElement: "data"                         # Optional: Root element name
    namespaces:                                 # Optional: XML namespaces
      ns1: "http://example.com/namespace1"
      ns2: "http://example.com/namespace2"
```

### Database Data Source

```yaml
source:
  class: "CPSIT\\T3importExport\\Persistence\\DataSourceDB"
  config:
    table: "source_table"                       # Required: Source table name
    where: "hidden = 0 AND deleted = 0"        # Optional: WHERE clause
    orderBy: "sorting ASC, title ASC"          # Optional: ORDER BY clause
    limit: 1000                                 # Optional: LIMIT clause
    connection: "Default"                       # Optional: Database connection name
    pid: 123                                    # Optional: Page ID filter
```

### Repository Data Source

```yaml
source:
  class: "CPSIT\\T3importExport\\Persistence\\DataSourceRepository"
  config:
    repository: "Vendor\\Extension\\Domain\\Repository\\ModelRepository"
    method: "findAll"                           # Optional: Repository method (default: findAll)
    arguments:                                  # Optional: Method arguments
      - "argument1"
      - "argument2"
```

## Data Targets Configuration

### Database Data Target

```yaml
target:
  class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
  config:
    table: "target_table"                       # Required: Target table name
    pid: 123                                    # Optional: Page ID for new records
    mode: "insert"                              # Optional: insert|update|replace (default: insert)
    updateField: "uid"                          # Optional: Field for update mode
    connection: "Default"                       # Optional: Database connection name
```

### CSV Data Target

```yaml
target:
  class: "CPSIT\\T3importExport\\Persistence\\DataTargetCSV"
  config:
    file: "fileadmin/exports/data.csv"          # Required: Output file path
    delimiter: ","                              # Optional: Field delimiter
    enclosure: "\""                             # Optional: Field enclosure
    headers: true                               # Optional: Include headers (default: true)
    mode: "w"                                   # Optional: File mode (w=overwrite, a=append)
```

### XML Stream Data Target

```yaml
target:
  class: "CPSIT\\T3importExport\\Persistence\\DataTargetXMLStream"
  config:
    file: "fileadmin/exports/data.xml"          # Required: Output file path
    rootElement: "data"                         # Optional: Root element name
    recordElement: "record"                     # Optional: Record element name
    encoding: "UTF-8"                           # Optional: XML encoding
    formatOutput: true                          # Optional: Pretty print XML
```

## Component Configuration Examples

### Initializers

#### Truncate Tables
```yaml
initializers:
  - class: "CPSIT\\T3importExport\\Component\\Initializer\\TruncateTables"
    config:
      tables: "table1,table2"                   # Comma-separated table names
      confirmDeletion: false                    # Optional: Require confirmation
```

#### Delete from Table
```yaml
initializers:
  - class: "CPSIT\\T3importExport\\Component\\Initializer\\DeleteFromTable"
    config:
      table: "target_table"
      where: "pid = 123 AND sys_language_uid = 0"
```

### PreProcessors

#### Map Fields
```yaml
preProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\MapFields"
    config:
      map:
        old_field_name: "new_field_name"
        another_old: "another_new"
        source_title: "title"
```

#### Map Field Values
```yaml
preProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\MapFieldValues"
    config:
      field: "status"
      map:
        "active": "1"
        "inactive": "0"
        "draft": "2"
```

#### Database Lookup
```yaml
preProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\LookUpDB"
    config:
      sourceField: "category_name"              # Field containing lookup value
      targetField: "category_uid"               # Field to store result
      table: "sys_category"                     # Lookup table
      field: "title"                            # Field to search in
      returnField: "uid"                        # Field to return
```

#### String to Time Conversion
```yaml
preProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\StringToTime"
    config:
      fields: "publish_date,update_date"        # Comma-separated field names
      format: "Y-m-d H:i:s"                     # PHP date format
      timezone: "Europe/Berlin"                 # Optional: Timezone
```

#### Set Field Value
```yaml
preProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\SetFieldValue"
    config:
      field: "pid"
      value: "123"
      overwrite: false                          # Optional: Overwrite existing values
```

### PostProcessors

#### Generate File Reference
```yaml
postProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\GenerateFileReference"
    config:
      sourceField: "image_path"                 # Field containing file path
      targetField: "image"                      # FAL field to populate
      storage: "1"                              # Storage UID
      folder: "user_upload/images/"             # Target folder
```

#### Set Localization Parent
```yaml
postProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\SetL10nParent"
    config:
      parentField: "l10n_parent"
      languageField: "sys_language_uid"
      identifierField: "import_id"              # Field to match records
```

#### Recreate Slug
```yaml
postProcessors:
  - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\RecreateSlug"
    config:
      field: "path_segment"                     # Slug field
      sourceFields: "title,subtitle"           # Fields to generate slug from
      separator: "-"                            # Optional: Separator character
```

### Finishers

#### Clear Cache
```yaml
finishers:
  - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
    config:
      cacheGroups: "pages,news"                # Comma-separated cache groups
      tags: "tag1,tag2"                         # Optional: Specific cache tags
```

#### Validate XML
```yaml
finishers:
  - class: "CPSIT\\T3importExport\\Component\\Finisher\\ValidateXML"
    config:
      file: "fileadmin/exports/data.xml"        # File to validate
      schemaFile: "fileadmin/schemas/data.xsd"  # XSD schema file
      onError: "continue"                       # continue|stop on validation error
```

#### Move File
```yaml
finishers:
  - class: "CPSIT\\T3importExport\\Component\\Finisher\\MoveFile"
    config:
      sourceFile: "fileadmin/temp/data.csv"
      targetFile: "fileadmin/archive/data-processed.csv"
      overwrite: true                           # Optional: Overwrite target
```

## Advanced Configuration Patterns

### Multi-Environment Setup

#### Base Configuration (base.yaml)
```yaml
import:
  tasks:
    baseTask:
      label: "Base Import Task"
      source:
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceCSV"
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
          file: "/secure/imports/production-data.csv"
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
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceCSV"
        config:
          file: "fileadmin/imports/complex-data.csv"
          skipLines: 1
          
      preProcessors:
        # Step 1: Clean and validate data
        - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\Clean"
          config:
            fields: "title,description"
            trim: true
            removeEmptyValues: true
            
        # Step 2: Map field names
        - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\MapFields"
          config:
            map:
              name: "title"
              desc: "description"
              cat: "category_name"
              
        # Step 3: Convert dates
        - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\StringToTime"
          config:
            fields: "publish_date"
            format: "d.m.Y H:i"
            
        # Step 4: Lookup categories
        - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\LookUpDB"
          config:
            sourceField: "category_name"
            targetField: "category_uid"
            table: "sys_category"
            field: "title"
            returnField: "uid"
            
        # Step 5: Set default values
        - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\SetFieldValue"
          config:
            field: "pid"
            value: "123"
            
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "tx_news_domain_model_news"
          
      postProcessors:
        # Generate URL slugs
        - class: "CPSIT\\T3importExport\\Component\\PostProcessor\\RecreateSlug"
          config:
            field: "path_segment"
            sourceFields: "title"
            
      finishers:
        # Clear relevant caches
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\ClearCache"
          config:
            cacheGroups: "pages,news"
```

## Configuration Validation

### Schema Validation

YAML configurations are automatically validated against predefined schemas. Common validation errors include:

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

# Invalid: Malformed configuration
import:
  tasks:
    invalidTask:
      source:
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceCSV"
        config:
          file: ""                              # ERROR: Empty file path
```

### Error Messages

The extension provides detailed error messages for configuration issues:

```
Configuration Error in task 'newsImport':
- Missing required field 'source.config.file'
- Invalid class 'InvalidClassName' in preProcessors[0]
- Field 'target.config.table' cannot be empty
```

## Best Practices

### 1. File Organization

```
Configuration/
├── base/
│   ├── import-tasks.yaml       # Base import configurations
│   └── export-tasks.yaml       # Base export configurations
├── environments/
│   ├── development.yaml        # Development overrides
│   ├── staging.yaml           # Staging overrides
│   └── production.yaml        # Production overrides
└── sets/
    ├── full-import.yaml       # Complete import sets
    └── maintenance.yaml       # Maintenance tasks
```

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
      label: "Robust Import with Error Handling"
      
      # Continue processing even if individual records fail
      source:
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceCSV"
        config:
          file: "fileadmin/imports/data.csv"
          continueOnError: true
          
      # Validate data before processing
      preProcessors:
        - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\ValidateFields"
          config:
            required: "title,email"
            patterns:
              email: "/^[^@]+@[^@]+\.[^@]+$/"
              
      finishers:
        # Log results for monitoring
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\LogResults"
          config:
            logFile: "var/log/import-results.log"
```

## Command Line Usage

### Execute with YAML Configuration

```bash
# Execute specific task from YAML file
vendor/bin/typo3 t3import-export:import-task taskName --yaml-config config.yaml

# Execute set from YAML file
vendor/bin/typo3 t3import-export:import-set setName --yaml-config config.yaml

# Use environment-specific configuration
vendor/bin/typo3 t3import-export:import-set fullImport --yaml-config config/production.yaml

# Dry run mode (validate without executing)
vendor/bin/typo3 t3import-export:import-task taskName --yaml-config config.yaml --dry-run
```

### Configuration Validation

```bash
# Validate YAML configuration
vendor/bin/typo3 t3import-export:validate-config --yaml-config config.yaml

# Show resolved configuration
vendor/bin/typo3 t3import-export:show-config --yaml-config config.yaml
```