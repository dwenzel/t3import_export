# T3Import_Export

[![TYPO3 13.4](https://img.shields.io/badge/TYPO3-13.4-blue.svg?style=flat-square)](https://get.typo3.org/version/13)
[![PHP 8.4](https://img.shields.io/badge/PHP-8.4-blue.svg?style=flat-square)](https://www.php.net/)
[![code coverage](https://codecov.io/gh/dwenzel/t3import_export/branch/master/graph/badge.svg)](https://codecov.io/gh/dwenzel/t3import_export)
[![Build Status](https://travis-ci.org/dwenzel/t3import_export.svg?branch=master)](https://travis-ci.org/dwenzel/t3import_export)

The **T3Import_Export** extension provides a comprehensive, flexible framework for importing and exporting data in TYPO3 13.4. It supports multiple data formats, sources, and destinations with powerful transformation capabilities through a modular component architecture.

Imports data from different sources into TYPO3 and exports from TYPO3 to different targets. Possible data sources and targets are databases, and files (XML, CSV...). Import and export tasks are described via TypoScript or modern YAML configuration and can be grouped to sets. Tasks can be executed manually via backend module or command line or automatically as a scheduler task.

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
- [TypoScript Configuration](./Documentation/CONFIGURATION.md)
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
  - [MapFields](./Documentation/Components/PreProcessors/MAP_FIELDS.md)
  - [MapFieldValues](./Documentation/Components/PreProcessors/MAP_FIELD_VALUES.md)
  - [LookUpDB](./Documentation/Components/PreProcessors/LOOK_UP_DB.md)
  - [StringToTime](./Documentation/Components/PreProcessors/STRING_TO_TIME.md)
  - [SetFieldValue](./Documentation/Components/PreProcessors/SET_FIELD_VALUE.md)
  - [ConcatenateFields](./Documentation/Components/PreProcessors/CONCATENATE_FIELDS.md)
  - [AddArrays](./Documentation/Components/PreProcessors/ADD_ARRAYS.md)
  - [Clean](./Documentation/Components/PreProcessors/CLEAN.md)
  - [XMLMapper](./Documentation/Components/PreProcessors/XML_MAPPER.md)
  - [RenderContent](./Documentation/Components/PreProcessors/RENDER_CONTENT.md)
  - [RemoveFields](./Documentation/Components/PreProcessors/REMOVE_FIELDS.md)
  - [UnsetEmptyFields](./Documentation/Components/PreProcessors/UnsetEmptyFields.md)
  - [ImplodeArray](./Documentation/Components/PreProcessors/IMPLODE_ARRAY.md)

- **[Converters](./Documentation/Components/CONVERTER.md)** - Format transformation
  - [ArrayToDomainObject](./Documentation/Components/Converters/ArrayToDomainObject.md)

- **[PostProcessors](./Documentation/Components/POSTPROCESSOR.md)** - Object enhancement
  - [GenerateFileReference](./Documentation/Components/PostProcessors/GenerateFileReferenc.md)
  - [SetL10nParent](./Documentation/Components/PostProcessors/SetL10nParent.md)
  - [RecreateSlug](./Documentation/Components/PostProcessors/RecreateSlug.md)

- **[Finishers](./Documentation/Components/FINISHER.md)** - Post-execution tasks
  - [ClearCache](./Documentation/Components/Finishers/ClearCache.md)
  - [ValidateXML](./Documentation/Components/Finishers/ValidateXML.md)
  - [MoveFile](./Documentation/Components/Finishers/MoveFile.md)
  - [WriteFile](./Documentation/Components/Finishers/WriteFile.md)
  - [DownloadFileStream](./Documentation/Components/Finishers/DownloadFileStream.md)

### 💾 Data Sources & Targets
- [Data Persistence Overview](./Documentation/Persistence.md)
- **Data Sources**
  - [CSV Files](./Documentation/Persistence/DataSourceCSV.md)
  - [XML Files](./Documentation/Persistence/DataSourceXML.md)
  - [Database Tables](./Documentation/Persistence/DataTargetDB.md)
- **Data Targets**
  - [Database Tables](./Documentation/Persistence/DataTargetDB.md)
  - [CSV Files](./Documentation/Persistence/DataTargetCSV.md)

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

The **T3Import_Export** extension provides a comprehensive, flexible framework for importing and exporting data in TYPO3 13.4. It supports multiple data formats, sources, and destinations with powerful transformation capabilities through a modular component architecture.

### Key Features

- **Multiple Data Formats**: CSV, XML, Database, Repository objects
- **Flexible Architecture**: Modular component system for data transformation
- **TYPO3 13.4 Ready**: Full compatibility with latest TYPO3 LTS
- **Command Line Support**: CLI execution for automation and scheduling
- **Backend Integration**: User-friendly backend module interface
- **YAML Configuration**: Modern configuration format alongside TypoScript
- **Queue System**: Asynchronous processing capabilities
- **Multi-language Support**: Localization and translation workflows

## Installation

### Via Composer (Recommended)

```bash
composer require cpsit/t3import_export
```

Activate the extension in the TYPO3 Extension Manager.

For detailed installation instructions, troubleshooting, and environment setup, see [Installation Guide](./Documentation/INSTALL.md).

## Quick Start Guide

### 1. Basic Configuration

Create a simple import task in TypoScript:

```typoscript
module.tx_t3importexport.settings.import.tasks.newsImport {
    label = News Import
    description = Import news from CSV file
    
    source {
        class = CPSIT\T3importExport\Persistence\DataSourceCSV
        config {
            file = fileadmin/imports/news.csv
            delimiter = ,
            enclosure = "
        }
    }
    
    preProcessors {
        10 {
            class = CPSIT\T3importExport\Component\PreProcessor\MapFields
            config {
                map {
                    title = headline
                    bodytext = content
                }
            }
        }
    }
    
    target {
        class = CPSIT\T3importExport\Persistence\DataTargetDB
        config {
            table = tx_news_domain_model_news
        }
    }
}
```

### 2. YAML Configuration Alternative

```yaml
import:
  tasks:
    newsImport:
      label: "News Import"
      description: "Import news from CSV file"
      source:
        class: "CPSIT\\T3importExport\\Persistence\\DataSourceCSV"
        config:
          file: "fileadmin/imports/news.csv"
          delimiter: ","
          enclosure: "\""
      preProcessors:
        - class: "CPSIT\\T3importExport\\Component\\PreProcessor\\MapFields"
          config:
            map:
              title: "headline"
              bodytext: "content"
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

- **TYPO3**: 13.4 LTS
- **PHP**: 8.4+
- **Dependencies**: 
  - symfony/yaml (for YAML configuration)
  - TYPO3 core extensions (extbase, fluid)

## Architecture Overview

For detailed architecture information, see [Architecture Overview](./Documentation/OVERVIEW.md).

## Configuration Overview

Configuration can be done via TypoScript or modern YAML format. Both approaches provide the same functionality, with YAML offering better readability and structure for complex configurations.

- **[TypoScript Configuration](./Documentation/CONFIGURATION.md)** - Traditional TYPO3 configuration
- **[YAML Configuration](./Documentation/YAML_CONFIGURATION.md)** - Modern structured configuration

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

Options:
- `--yaml-config <file>` - Use YAML configuration file
- `--dry-run` - Simulate execution without persisting data

## Scheduler Integration

The extension integrates with TYPO3 Scheduler for automated execution:

1. Create new Scheduler task
2. Select **Import/Export Task** type
3. Configure task or set to execute
4. Set execution schedule

## Configuration Examples

### CSV Import with Field Mapping
```typoscript
module.tx_t3importexport.settings.import.tasks.productImport {
    source {
        class = CPSIT\T3importExport\Persistence\DataSourceCSV
        config {
            file = fileadmin/imports/products.csv
            skipLines = 1
            delimiter = ;
        }
    }
    preProcessors {
        10 {
            class = CPSIT\T3importExport\Component\PreProcessor\MapFields
            config {
                map {
                    name = product_name
                    price = product_price
                    description = product_desc
                }
            }
        }
    }
    target {
        class = CPSIT\T3importExport\Persistence\DataTargetDB
        config {
            table = tx_shop_domain_model_product
        }
    }
}
```

### XML Export with Validation
```typoscript
module.tx_t3importexport.settings.export.tasks.newsExport {
    source {
        class = CPSIT\T3importExport\Persistence\DataSourceDB
        config {
            table = tx_news_domain_model_news
            where = hidden = 0
        }
    }
    target {
        class = CPSIT\T3importExport\Persistence\DataTargetXMLStream
        config {
            file = fileadmin/exports/news.xml
            rootElement = news
        }
    }
    finishers {
        10 {
            class = CPSIT\T3importExport\Component\Finisher\ValidateXML
            config {
                schemaFile = fileadmin/schemas/news.xsd
            }
        }
    }
}
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

1. **Memory Limits**: For large datasets, increase PHP memory limit or use queue processing
2. **File Permissions**: Ensure TYPO3 can read source files and write to target locations  
3. **Database Connections**: Verify database credentials for external sources
4. **Component Configuration**: Check component configuration syntax and required parameters

### Debug Mode

Enable debug output in configuration:
```typoscript
module.tx_t3importexport.settings.debug = 1
```

### Log Files

Check TYPO3 log files for detailed error information:
- `var/log/typo3_*.log`
- Component-specific error messages
- SQL query logs for database operations

## API Documentation

For detailed API documentation, refer to the inline code documentation and the `Documentation/UML/` directory containing UML diagrams.

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