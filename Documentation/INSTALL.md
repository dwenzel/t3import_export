Installation
============

## System Requirements

### Minimum Requirements
* **PHP**: 8.3 or 8.4
* **TYPO3**: 14.3 LTS
* **Memory**: 256 MB (512 MB recommended for large datasets)
* **Disk Space**: Adequate space for temporary files during processing

### Dependencies
Installed by Composer:
* **Import-Export-Core**: cpsit/import-export-core ^0.7
* **Extension tools**: dwenzel/t3extension-tools
* **Symfony Components**: symfony/expression-language

## Installation

### Via Composer (Recommended)

```bash
# Install the extension
composer require cpsit/t3import_export

# Create the tables of the extension and clear caches
vendor/bin/typo3 extension:setup
vendor/bin/typo3 cache:flush
```

### Manual Installation

1. Download the extension from GitHub or Packagist
2. Extract it to a local path repository of your project, e.g. `packages/`
3. Require it with Composer: `composer require cpsit/t3import_export:@dev`

## Post-Installation Setup

### 1. Activate Extension
In Composer mode the extension is active after `composer require`. Create its
tables with `vendor/bin/typo3 extension:setup` or in the Install Tool
(Maintenance > Analyze Database Structure).

### 2. Backend Modules
The extension adds two modules to the backend:
- **System > Imports**
- **System > Exports**

They list the tasks and sets of the YAML configuration (see
[YAML configuration](./YAML_CONFIGURATION.md#configuring-the-directories)).

### 3. Command Line Setup
Verify CLI commands are available:
```bash
vendor/bin/typo3 list t3import-export
```

Expected output (aliases in brackets):
```
t3import-export:convert-typoscript  Converts tasks and sets from TypoScript (module.tx_t3importexport.settings) to YAML
t3import-export:export-set          [export:set] Performs pre-defined export set.
t3import-export:export-task         [export:task] Performs pre-defined export task.
t3import-export:import-set          [import:set] Performs pre-defined import sets.
t3import-export:import-task         [import:task] Performs pre-defined import task.
t3import-export:queue:release       [queue:release] Sets queue items in processing back to new.
t3import-export:queue:status        [queue:status] Shows the number of queue items by status and lists items.
```

### 4. Files and URLs
Files are read and written only in the allowed directories (by default
`fileadmin/` and `var/import/`), URLs are loaded only from the allowed hosts
(by default none). See [Files](./Persistence/Files.md) to configure them.

Ensure proper file permissions for:
- **Import directories**: Readable by web server
- **Export directories**: Writable by web server
- **Temporary directories**: Read/write access

Example setup:
```bash
# Create import/export directories
mkdir -p fileadmin/imports fileadmin/exports

# Set permissions (adjust for your server setup)
chown -R www-data:www-data fileadmin/imports fileadmin/exports
chmod -R 755 fileadmin/imports fileadmin/exports
```

## Environment-Specific Configuration

### Production Environment
```bash
# Install the locked versions without development dependencies
composer install --no-dev --optimize-autoloader
```

### Docker/DDEV Setup
For Docker-based development:
```bash
# In DDEV environment
ddev composer require cpsit/t3import_export
ddev exec vendor/bin/typo3 cache:flush
```

## Verification

### Test Installation
Create a simple test configuration to verify installation, e.g. in
`Configuration/ImportExport/test.yaml` of your site package (the directory must
be configured, see [YAML configuration](./YAML_CONFIGURATION.md#configuring-the-directories)):

```yaml
import:
  tasks:
    test:
      label: "Installation Test"
      source:
        class: "CPSIT\\ImportExportCore\\Persistence\\NullDataSource"
      target:
        class: "CPSIT\\ImportExportCore\\Persistence\\NullDataTarget"
```

Execute test task:
```bash
vendor/bin/typo3 t3import-export:import-task test
```

### Check Backend Module
1. Login to TYPO3 Backend
2. Navigate to **System > Imports**
3. Verify the module loads without errors
4. Check that test task appears in task list

## Troubleshooting Installation

### Common Issues

**Extension not found:**
- Clear all caches: `vendor/bin/typo3 cache:flush`
- Check composer installation: `composer show cpsit/t3import_export`

**Command line tools not available:**
- Verify TYPO3 installation: `vendor/bin/typo3 --version`
- Check that the extension is installed: `composer show cpsit/t3import_export`

**Permission errors:**
- Verify web server has read/write access to required directories
- Check TYPO3 file permissions configuration

**Memory issues with large datasets:**
```php
// e.g. in config/system/additional.php
ini_set('memory_limit', '512M');
ini_set('max_execution_time', 300);
```

### Debug Installation
Enable verbose output for troubleshooting:
```bash
# Check extension status
vendor/bin/typo3 extension:list | grep t3import_export

# Verify dependencies
composer show --tree cpsit/t3import_export

# Test basic functionality
vendor/bin/typo3 t3import-export:import-task --help
```

## Upgrade Instructions

### From Version 3.x to 4.x
4.0 requires TYPO3 14.3 and `cpsit/import-export-core` ^0.7. The steps are
listed in [Deprecations](./DEPRECATIONS.md#migration); in short:

Tasks and sets in TypoScript (`module.tx_t3importexport.settings`) are not
read any more. Convert them to YAML with `t3import-export:convert-typoscript`
before updating, see [Deprecations](./DEPRECATIONS.md#removed-in-40).

Replace the pre-processors removed in 4.0 by those of the core, see
[Deprecations](./DEPRECATIONS.md#deprecated-in-32). Files are read only in
the allowed directories and URLs only from the allowed hosts, see
[Files](./Persistence/Files.md).

Update the database schema: 4.0 adds the tables `tx_t3importexport_queue_item`
(queue) and `tx_t3importexport_id_map` (ID map, see
[ID map and state components](./Persistence/IdMap.md)).

```bash
vendor/bin/typo3 extension:setup
```

(or `database:updateschema` of typo3-console, or the Install Tool).

If the queue (`DataTargetQueue`, `DataSourceQueue`) was used, run the upgrade
wizard that copies the queue items into the queue table of the core after
updating the database schema and before running any task with 4.0. Later
changes in the legacy table are not copied by a second run.

```bash
vendor/bin/typo3 upgrade:run t3importExport_queueItemMigration
```

See [Persistence](./Persistence.md#migrating-the-queue-items-of-earlier-versions).
The legacy table stays until 5.0. Records read from the queue no longer
contain `uid`, see [Deprecations](./DEPRECATIONS.md#queue).

### Earlier versions
For updates between earlier major versions (e.g. 1.x to 2.x, 2.x to 3.x),
see the commit log of the versions up to 3.2.0 in [ChangeLog](../ChangeLog),
and [CHANGELOG.md](../CHANGELOG.md) and [Deprecations](./DEPRECATIONS.md) for
later versions. Update one major version at a time and check the tasks after
each step.

### Database Schema Updates
After an update, create new tables and columns:
```bash
vendor/bin/typo3 extension:setup
```

## Uninstallation

### Remove Extension
```bash
# Remove via composer
composer remove cpsit/t3import_export

# Clear caches
vendor/bin/typo3 cache:flush
```

### Clean Database (Optional)
To remove all extension data:
```sql
-- Remove queue items (if using queue)
DROP TABLE IF EXISTS tx_t3importexport_queue_item;
DROP TABLE IF EXISTS tx_t3importexport_domain_model_queueitem;

-- Remove the ID map
DROP TABLE IF EXISTS tx_t3importexport_id_map;

-- Remove the export targets
DROP TABLE IF EXISTS tx_t3importexport_domain_model_exporttarget;
```

## Sources and Support

* **Packagist**: [cpsit/t3import_export](https://packagist.org/packages/cpsit/t3import_export)
* **GitHub**: [dwenzel/t3import_export](https://github.com/dwenzel/t3import_export)
* **Documentation**: This documentation package
* **Issues**: GitHub Issues for bug reports and feature requests

For enterprise support and custom development, contact CPS IT GmbH.
