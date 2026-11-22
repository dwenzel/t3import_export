# Import & Export Documentation

[![TYPO3 14.3](https://img.shields.io/badge/TYPO3-14.3-blue.svg?style=flat-square)](https://get.typo3.org/version/14)
[![PHP 8.3 | 8.4](https://img.shields.io/badge/PHP-8.3%20%7C%208.4-blue.svg?style=flat-square)](https://www.php.net/)

## Table of Contents

### 📋 Getting Started
- [Overview](#overview)
- [Installation](./INSTALL.md)
- [Quick Start Guide](#quick-start-guide)
- [System Requirements](#system-requirements)

### 🏗️ Core Concepts
- [Architecture Overview](./OVERVIEW.md)
- [Configuration](./CONFIGURATION.md)
- [Tasks and Sets](#tasks-and-sets)
- [Processing Flow](#processing-flow)

### 🔧 Configuration
- [Deprecations](./DEPRECATIONS.md)
- [YAML Configuration](#yaml-configuration)
- [Environment Setup](#environment-setup)
- [Configuration Examples](#configuration-examples)

### 🧩 Components
- [Component Overview](./COMPONENT.md)
- [Component Types](#component-types)
- [Custom Components](#custom-components)

#### Component Types
- **[Initializers](./Components/INITIALIZER.md)** - Pre-execution setup
  - [TruncateTables](./Components/Initializers/TruncateTables.md)
  - [DeleteFromTable](./Components/Initializers/DeleteFromTable.md)
  - [InsertMultiple](./Components/Initializers/InsertMultiple.md)
  - [UpdateTable](./Components/Initializers/UpdateTable.md)

- **[PreProcessors](./Components/PREPROCESSOR.md)** - Data transformation
  - Pre-processors of `cpsit/import-export-core`, e.g. `ValueRulesPreProcessor`, `SetValuesPreProcessor`, `CleanPreProcessor`: see [Deprecations](./DEPRECATIONS.md#deprecated-in-32)
  - [LookUpDB](./Components/PreProcessors/LOOK_UP_DB.md)
  - [EvaluateExpressionsPreProcessor](./Components/PreProcessors/EVALUATE_EXPRESSIONS.md) (core)
  - [ValidateXMLPreProcessor](./Components/PreProcessors/VALIDATE_XML.md) (core)
  - [XMLMapperPreProcessor](./Components/PreProcessors/XML_MAPPER.md) (core)

- **[Converters](./Components/CONVERTER.md)** - Format transformation
  - [ArrayToDomainObject](./Components/Converters/ArrayToDomainObject.md)

- **[PostProcessors](./Components/POSTPROCESSOR.md)** - Object enhancement
  - [GenerateFileReference](./Components/PostProcessors/GenerateFileReferenc.md)
  - [SetL10nParent](./Components/PostProcessors/SetL10nParent.md)
  - [RecreateSlug](./Components/PostProcessors/RecreateSlug.md)

- **[Finishers](./Components/FINISHER.md)** - Post-execution tasks
  - [ClearCache](./Components/Finishers/ClearCache.md)
  - [ValidateXMLFinisher](./Components/Finishers/ValidateXML.md) (core)
  - [MoveFile](./Components/Finishers/MoveFile.md)
  - [WriteFile](./Components/Finishers/WriteFile.md)
  - [DownloadFileStream](./Components/Finishers/DownloadFileStream.md)

### 💾 Data Sources & Targets
- [Data Persistence Overview](./Persistence.md)
- **Data Sources**
  - [CSV Files](./Persistence/DataSourceCSV.md)
  - [XML Files](./Persistence/DataSourceXML.md)
  - [Database](./Persistence/DataSourceDB.md)
  - [Repository](./Persistence/DataSourceRepository.md)
  - [Queue](./Persistence/Queue.md)
- **Data Targets**
  - [Database](./Persistence/DataTargetDB.md)
  - [Queue](./Persistence/Queue.md#datatargetqueue)
  - [CSV Files](./Persistence/DataTargetCSV.md)
  - [XML Streams](./Persistence/DataTargetXMLStream.md)
  - [Repository](./Persistence/DataTargetRepository.md)
  - [File Streams](./Persistence/DataTargetFileStream.md)
- [Files: paths and URLs](./Persistence/Files.md)

### 🔌 Services & Utilities
- [Database Connection Service](./Service/DatabaseConnectionService.md)
- [Translation Service](#translation-service)
- [Configuration Validation](#configuration-validation)
- [Message Container](#message-container)

### 🚀 Execution Methods
- [Backend Module](#backend-module)
- [Command Line Interface](#command-line-interface)
- [Scheduler Integration](#scheduler-integration)
- [Queue Processing](./Persistence/Queue.md)

### 🛠️ Development
- [Development Guide](./DEVELOPMENT.md)
- [Components and the core library](./CORE_MIGRATION.md)
- [Creating Custom Components](#creating-custom-components)
- [Testing](#testing)
- [Contributing](#contributing)

### 📚 Examples & Use Cases
- [Common Use Cases](#common-use-cases)
- [Configuration Examples](#configuration-examples)
- [Migration Scenarios](#migration-scenarios)
- [Troubleshooting](#troubleshooting)

### 🔍 Reference
- [YAML Configuration Reference](#yaml-configuration-reference)
- [API Documentation](#api-documentation)
- [Error Codes](#error-codes)

---

## Overview

The **Import & Export** extension provides a comprehensive, flexible framework for importing and exporting data in TYPO3 14.3. It supports multiple data formats, sources, and destinations with powerful transformation capabilities through a modular component architecture.

### Key Features

- **Multiple Data Formats**: CSV, XML, Database, Repository objects
- **Flexible Architecture**: Modular component system for data transformation
- **TYPO3 14.3**: Compatible with TYPO3 14.3 LTS
- **Command Line Support**: CLI execution for automation and scheduling
- **Backend Integration**: User-friendly backend module interface
- **YAML Configuration**: Tasks and sets are configured in YAML files
- **Queue System**: Asynchronous processing capabilities
- **Multi-language Support**: Localization and translation workflows

## Quick Start Guide

### 1. Installation

```bash
composer require cpsit/t3import_export
```

Activate the extension in the TYPO3 Extension Manager.

### 2. Basic Configuration

Create a YAML file in one of the configured directories (see [YAML configuration](./YAML_CONFIGURATION.md)) with an import task:

```yaml
import:
  tasks:
    newsImport:
      label: "News Import"
      description: "Import news from CSV file"
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "fileadmin/imports/news.csv"
          delimiter: ","
          enclosure: "\""
      preProcessors:
        - class: "CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor"
          config:
            rules:
              - {rule: copy, field: headline, from: title}
              - {rule: copy, field: content, from: bodytext}
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "tx_news_domain_model_news"
```

### 3. Execution

Execute via command line:
```bash
vendor/bin/typo3 t3import-export:import-task newsImport
```

Or via YAML configuration:
```bash
vendor/bin/typo3 t3import-export:import-set --yaml-config path/to/config.yaml
```

## System Requirements

- **TYPO3**: 14.3 LTS
- **PHP**: 8.3 or 8.4
- **Dependencies**:
  - symfony/yaml (for YAML configuration)
  - TYPO3 core extensions (extbase, fluid)

## Tasks and Sets

### Tasks
A **Task** is a single import or export operation with:
- One data source
- One data target
- Optional processing components (Initializers, PreProcessors, Converters, PostProcessors, Finishers)

### Sets
A **Set** is a collection of tasks that are executed sequentially. Sets allow you to:
- Group related operations
- Ensure proper execution order
- Manage complex data workflows

## Processing Flow

Each task execution follows this flow:

1. **Initialize**: Execute Initializers (e.g., clear tables, prepare data)
2. **Build Queue**: Load data from source into processing queue
3. **Process Records**: For each record:
   - Apply PreProcessors (transform data)
   - Apply Converters (change format)
   - Apply PostProcessors (enhance objects)
   - Persist to target
4. **Finalize**: Execute Finishers (e.g., clear cache, validate results)

## YAML Configuration

Tasks and sets are configured in YAML files:

```yaml
import:
  tasks:
    taskName:
      label: "Task Label"
      description: "Task Description"
      source:
        class: "SourceClass"
        config: {}
      target:
        class: "TargetClass"
        config: {}
      # Optional components
      initializers: []
      preProcessors: []
      converters: []
      postProcessors: []
      finishers: []
  sets:
    setName:
      label: "Set Label"
      description: "Set Description"
      tasks: "task1,task2,task3"
```

## Component Types

### Initializers
Execute before processing starts. Common uses:
- Clear target tables
- Delete specific records
- Insert default data
- Update table structures

### PreProcessors
Transform raw data before conversion. Common uses:
- Map field names
- Transform field values
- Database lookups
- Data validation and cleaning

### Converters
Change data format. Common uses:
- Convert arrays to domain objects
- Generate XML streams
- Transform data structures

### PostProcessors
Enhance processed objects. Common uses:
- Generate file references
- Set localization relationships
- Create URL slugs
- Handle translations

### Finishers
Execute after processing completes. Common uses:
- Clear TYPO3 caches
- Validate generated files
- Move files to final location
- Send notifications

## Backend Module

Access the import/export module in the TYPO3 backend:

1. Navigate to **System > Import/Export**
2. Choose **Import** or **Export** module
3. Select configured tasks or sets
4. Execute with real-time progress feedback

## Command Line Interface

Available commands:

- `t3import-export:import-task <taskId>` - Execute single import task
- `t3import-export:import-set <setId>` - Execute import set
- `t3import-export:export-set <setId>` - Execute export set
- `t3import-export:export-task <taskId>` - Execute single export task
- `t3import-export:queue:status` - Show the number of queue items by status,
  list items with `--queue` or `--status`
- `t3import-export:queue:release --queue <queue>` (or `--all`) - Set queue
  items in processing back to new, see [Queue](./Persistence/Queue.md#commands)

Options:
- `--yaml-config <file>` - Use YAML configuration file (`import-set`,
  `export-set` and `export-task`); the path is relative to the working
  directory and is not restricted to the allowed directories

## Common Use Cases

### Content Migration
- Migrate from legacy CMS systems
- Update database structures
- Transform content formats
- Synchronize multi-site content

### Data Integration
- Import from external APIs
- Synchronize with ERP systems
- Process CSV/XML data feeds
- Integrate with third-party services

### Maintenance Operations
- Bulk content updates
- Database cleanup
- Asset management
- Automated backups

## Configuration Examples

### CSV Import with Field Mapping
```yaml
import:
  tasks:
    productImport:
      source:
        class: "CPSIT\\ImportExportCore\\File\\DataSourceCSV"
        config:
          file: "fileadmin/imports/products.csv"
          delimiter: ";"
      preProcessors:
        - class: "CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor"
          config:
            rules:
              - {rule: copy, field: product_name, from: name}
              - {rule: copy, field: product_price, from: price}
              - {rule: copy, field: product_desc, from: description}
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetDB"
        config:
          table: "tx_shop_domain_model_product"
```

### XML Export with Validation
```yaml
export:
  tasks:
    newsExport:
      source:
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceDB"
        config:
          table: "tx_news_domain_model_news"
          where: "hidden = 0"
      target:
        class: "CPSIT\\T3importExport\\Persistence\\DataTargetXMLStream"
        config:
          output: file            # write to a temporary file
          rootNodeName: "news"
      finishers:
        # store the temporary file as fileadmin/exports/news.xml
        - class: "CPSIT\\T3importExport\\Component\\Finisher\\WriteFile"
          config:
            target:
              name: "news.xml"
              directory: "exports"
              conflictMode: replace
        - class: "CPSIT\\ImportExportCore\\File\\ValidateXMLFinisher"
          config:
            file: "fileadmin/exports/news.xml"
            schema:
              file: "fileadmin/schemas/news.xsd"
```

## Troubleshooting

### Common Issues

1. **Memory Limits**: For large datasets, increase PHP memory limit or use queue processing
2. **File Permissions**: Ensure TYPO3 can read source files and write to target locations
3. **Database Connections**: Verify database credentials for external sources
4. **Component Configuration**: Check component configuration syntax and required parameters

### Log Files

Check TYPO3 log files for detailed error information:
- `var/log/typo3_*.log`
- Component-specific error messages
- SQL query logs for database operations

## API Documentation

For detailed API documentation, refer to the inline code documentation and the `Documentation/UML/` directory containing UML diagrams.

## Contributing

Please refer to [DEVELOPMENT.md](./DEVELOPMENT.md) for guidelines on:
- Setting up development environment
- Coding standards
- Testing procedures
- Contribution workflow

---

## Support

- **Issues**: Report bugs and feature requests on GitHub
- **Documentation**: This documentation covers all aspects of the extension
- **Community**: Join TYPO3 community channels for general support

For enterprise support and custom development, contact CPS IT GmbH.
