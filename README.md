# Import & Export

[![TYPO3 14.3](https://img.shields.io/badge/TYPO3-14.3-blue.svg?style=flat-square)](https://get.typo3.org/version/14)
[![PHP 8.3 | 8.4](https://img.shields.io/badge/PHP-8.3%20%7C%208.4-blue.svg?style=flat-square)](https://www.php.net/)
[![Tests](https://github.com/dwenzel/t3import_export/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/dwenzel/t3import_export/actions/workflows/tests.yml)

The **Import & Export** extension provides a comprehensive, flexible framework for importing and exporting data in TYPO3 14.3. It supports multiple data formats, sources, and destinations with powerful transformation capabilities through a modular component architecture.

Imports data from different sources into TYPO3 and exports from TYPO3 to different targets. Possible data sources and targets are databases, and files (XML, CSV...). Import and export tasks are described in YAML files and can be grouped to sets. Tasks can be executed manually via backend module or command line or automatically as a scheduler task.

## Support matrix

| TYPO3 | Branch              | Latest release | Status |
|-------|---------------------|----------------|--------|
| 14.3  | `main`              | 4.0.0          | maintained |
| 13.4  | `support/typo3-v13` | 3.3.1          | bugfixes |
| 12.4  | `support/typo3-v12` | 2.0.5          | bugfixes |
| 11.5  | `support/typo3-v11` | 1.1.4          | bugfixes |
| 9.5, 10.4 | none            | 1.0.5          | not maintained |
| 6.2 - 8.7 | none            | 0.14.0         | not maintained |

See [CONTRIBUTING.md](CONTRIBUTING.md) for the branching model and the release
process.

## Table of Contents

### 📋 Getting Started
- [Overview](#overview)
- [Installation](#installation)
- [Quick Start Guide](#quick-start-guide)
- [System Requirements](#system-requirements)

### 🏗️ Core Concepts
- [Architecture Overview](#architecture-overview)
- [Configuration](#configuration-overview)
- [Tasks and Sets](#tasks-and-sets)
- [Processing Flow](#processing-flow)

### 🔧 Configuration
- [Configuration](./Documentation/CONFIGURATION.md)
- [YAML Configuration](./Documentation/YAML_CONFIGURATION.md)
- [Configuration Examples](#configuration-examples)

### 🧩 Components
- [Component Overview](./Documentation/COMPONENT.md)
- [Component Types](#component-types)

#### Component Types
- **[Initializers](./Documentation/Components/INITIALIZER.md)** - Pre-execution setup
  - [TruncateTables](./Documentation/Components/Initializers/TruncateTables.md)
  - [DeleteFromTable](./Documentation/Components/Initializers/DeleteFromTable.md)
  - [InsertMultiple](./Documentation/Components/Initializers/InsertMultiple.md)
  - [UpdateTable](./Documentation/Components/Initializers/UpdateTable.md)

- **[PreProcessors](./Documentation/Components/PREPROCESSOR.md)** - Data transformation
  - Pre-processors of `cpsit/import-export-core`, e.g. `ValueRulesPreProcessor`, `SetValuesPreProcessor`, `CleanPreProcessor`: see [Deprecations](./Documentation/DEPRECATIONS.md#deprecated-in-32)
  - [LookUpDB](./Documentation/Components/PreProcessors/LOOK_UP_DB.md)
  - [ValidateXMLPreProcessor](./Documentation/Components/PreProcessors/VALIDATE_XML.md) (core)
  - [XMLMapperPreProcessor](./Documentation/Components/PreProcessors/XML_MAPPER.md) (core)

- **[Converters](./Documentation/Components/CONVERTER.md)** - Format transformation
  - [ArrayToDomainObject](./Documentation/Components/Converters/ArrayToDomainObject.md)

- **[PostProcessors](./Documentation/Components/POSTPROCESSOR.md)** - Object enhancement
  - [GenerateFileReference](./Documentation/Components/PostProcessors/GenerateFileReferenc.md)
  - [SetL10nParent](./Documentation/Components/PostProcessors/SetL10nParent.md)
  - [RecreateSlug](./Documentation/Components/PostProcessors/RecreateSlug.md)

- **[Finishers](./Documentation/Components/FINISHER.md)** - Post-execution tasks
  - [ClearCache](./Documentation/Components/Finishers/ClearCache.md)
  - [ValidateXMLFinisher](./Documentation/Components/Finishers/ValidateXML.md) (core)
  - [MoveFile](./Documentation/Components/Finishers/MoveFile.md)
  - [WriteFile](./Documentation/Components/Finishers/WriteFile.md)
  - [DownloadFileStream](./Documentation/Components/Finishers/DownloadFileStream.md)

### 💾 Data Sources & Targets
- [Data Persistence Overview](./Documentation/Persistence.md)
- **Data Sources**
  - [CSV Files](./Documentation/Persistence/DataSourceCSV.md)
  - [XML Files](./Documentation/Persistence/DataSourceXML.md)
  - [Database Tables](./Documentation/Persistence/DataSourceDB.md)
  - [Queue](./Documentation/Persistence/Queue.md#datasourcequeue)
- **Data Targets**
  - [Database Tables](./Documentation/Persistence/DataTargetDB.md)
  - [CSV Files](./Documentation/Persistence/DataTargetCSV.md)
  - [Queue](./Documentation/Persistence/Queue.md#datatargetqueue)
- [ID map and state components](./Documentation/Persistence/IdMap.md)
- [Files: paths and URLs](./Documentation/Persistence/Files.md)

### 🔌 Services & Utilities
- [Database Connection Service](./Documentation/Service/DatabaseConnectionService.md)

### 🚀 Execution Methods
- [Backend Module](#backend-module)
- [Command Line Interface](#command-line-interface)
- [Scheduler Integration](#scheduler-integration)

### 🛠️ Development
- [Development Guide](./Documentation/DEVELOPMENT.md)

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

## Installation

### Via Composer (Recommended)

```bash
composer require cpsit/t3import_export
```

In Composer mode the extension is active after installation. Update the
database schema afterwards (`vendor/bin/typo3 extension:setup`).

For detailed installation instructions, troubleshooting, and environment setup, see [Installation Guide](./Documentation/INSTALL.md).

## Quick Start Guide

### 1. Basic Configuration

Create a YAML file in one of the configured directories (see [YAML configuration](./Documentation/YAML_CONFIGURATION.md)) with an import task:

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

### 2. Execution

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
- **Dependencies** (installed by Composer):
  - cpsit/import-export-core ^0.7
  - dwenzel/t3extension-tools
  - symfony/expression-language

## Architecture Overview

For detailed architecture information, see [Architecture Overview](./Documentation/OVERVIEW.md).

## Configuration Overview

Tasks and sets are configured in YAML files. The TypoScript configuration
(`module.tx_t3importexport.settings`) was removed in version 4.0; the command
`t3import-export:convert-typoscript` converts it to YAML, see
[Deprecations](./Documentation/DEPRECATIONS.md#typoscript-configuration).

- **[Configuration](./Documentation/CONFIGURATION.md)** - Tasks and sets
- **[YAML Configuration](./Documentation/YAML_CONFIGURATION.md)** - Reference and examples

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

1. **Read**: The records of all tasks are read from their sources
2. **Initialize**: Execute Initializers (e.g., clear tables, prepare data)
3. **Process Records**: For each record, in batches of 100:
   - Apply PreProcessors (transform data)
   - Apply Converters (change format)
   - Apply PostProcessors (enhance objects)
   - Persist to target
4. **Finalize**: Execute Finishers (e.g., clear cache, validate results)

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

1. Navigate to **System > Imports** or **System > Exports**
2. Select a configured task or set
3. Execute it

## Command Line Interface

Available commands:

- `t3import-export:import-task <taskId>` - Execute single import task
- `t3import-export:import-set <setId>` - Execute import set
- `t3import-export:export-set <setId>` - Execute export set
- `t3import-export:export-task <taskId>` - Execute single export task
- `t3import-export:queue:status` - Show the number of queue items by status,
  see [Queue](./Documentation/Persistence/Queue.md#commands)
- `t3import-export:queue:release` - Set queue items in processing back to new
- `t3import-export:convert-typoscript` - Convert tasks and sets from
  TypoScript to YAML (command line only)

The commands have the short aliases `import:task`, `import:set`,
`export:task`, `export:set`, `queue:status` and `queue:release`.

Options:
- `--yaml-config <file>` - Use YAML configuration file (`import-set`,
  `export-set` and `export-task`); the path is relative to the working
  directory and is not restricted to the allowed directories

## Scheduler Integration

The commands can be run by the TYPO3 Scheduler:

1. Create a new Scheduler task of the type **Execute console commands**
2. Select a command, e.g. `t3import-export:import-set`
3. Enter the task or set identifier as argument
4. Set the execution schedule

`t3import-export:convert-typoscript` is not offered by the Scheduler.

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
          where:
            hidden: 0
            deleted: 0
      converters:
        # converts each record into an XML element <item>
        - class: "CPSIT\\T3importExport\\Component\\Converter\\ArrayToXMLStream"
          config:
            targetClass: "CPSIT\\ImportExportCore\\Domain\\Model\\DataStream"
            nodeName: "item"
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

## Troubleshooting

### Common Issues

1. **Memory Limits**: The records of a task are read before they are processed; for large datasets, increase the PHP memory limit or split the data with a queue
2. **File Permissions**: Ensure TYPO3 can read source files and write to target locations
3. **Database Connections**: Verify database credentials for external sources
4. **Component Configuration**: Check component configuration syntax and required parameters

### Log Files

Check TYPO3 log files for detailed error information:
- `var/log/typo3_*.log`
- Component-specific error messages
- The deprecation log for deprecated options

## API Documentation

For detailed API documentation, refer to the inline code documentation. The
UML diagrams in `Documentation/UML/` describe an earlier version and are not
updated for 4.0.

## Contributing

Please refer to [DEVELOPMENT.md](./Documentation/DEVELOPMENT.md) for guidelines on:
- Setting up development environment
- Coding standards
- Testing procedures
- Contribution workflow

## Credits

Thanks for contribution and feedback:

* [Sebastian Kreideweiss](https://github.com/kreidewe)
* [Jan-Henrik Hempel](https://github.com/motordigital)
* [Benjamin Rannow](https://github.com/brannow)
* [Nicole Cordes](https://github.com/IchHabRecht)
* [Vladimir Falcón Piva](https://github.com/vladimirfalconpiva)
* [Benjamin Schütz](https://github.com/schuetzbenjamin)

## Support

- **Issues**: Report bugs and feature requests on GitHub
- **Documentation**: This documentation covers all aspects of the extension
- **Community**: Join TYPO3 community channels for general support

This extension has been developed at [CPS IT GmbH](https://cps-it.de).

For enterprise support and custom development, contact CPS IT GmbH.
