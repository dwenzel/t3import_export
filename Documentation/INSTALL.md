Installation
============

## System Requirements

### Minimum Requirements
* **PHP**: 8.4 or higher
* **TYPO3**: 13.4 LTS
* **Memory**: 256 MB (512 MB recommended for large datasets)
* **Disk Space**: Adequate space for temporary files during processing

### Dependencies
* **TYPO3 Core Extensions**: extbase, fluid
* **Symfony Components**: symfony/yaml (for YAML configuration support)
* **Import-Export-Core**: cpsit/import-export-core (automatically installed)

## Installation

### Via Composer (Recommended)

```bash
# Install the extension
composer require cpsit/t3import_export

# Clear caches
vendor/bin/typo3 cache:flush
```

### Manual Installation

1. Download the extension from GitHub or Packagist
2. Extract to `packages/` directory
3. Run composer install in the extension directory
4. Activate the extension in TYPO3 Extension Manager

## Post-Installation Setup

### 1. Activate Extension
After installation, activate the extension in the TYPO3 Extension Manager:
- Login to TYPO3 Backend
- Navigate to **Admin Tools > Extensions**
- Find **t3import_export** and activate it

### 2. Configure Backend Modules
The extension automatically adds import/export modules to the backend. Access them via:
- **System > Import/Export > Import Module**
- **System > Import/Export > Export Module**

### 3. Command Line Setup
Verify CLI commands are available:
```bash
vendor/bin/typo3 list t3import-export
```

Expected output:
```
t3import-export:export-set    Execute export set
t3import-export:import-set    Execute import set
t3import-export:import-task   Execute import task
```

### 4. File Permissions
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

### Development Environment
```bash
# Install with development dependencies
composer require --dev cpsit/t3import_export

# Enable debug mode in TypoScript
module.tx_t3importexport.settings.debug = 1
```

### Production Environment
```bash
# Install for production
composer require --no-dev cpsit/t3import_export

# Optimize autoloader
composer dump-autoload --optimize
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
Create a simple test configuration to verify installation:

```typoscript
module.tx_t3importexport.settings.import.tasks.test {
    label = Installation Test
    source {
        class = CPSIT\T3importExport\Persistence\DataSourceDummy
    }
    target {
        class = CPSIT\T3importExport\Persistence\DataTargetDummy
    }
}
```

Execute test task:
```bash
vendor/bin/typo3 t3import-export:import-task test
```

### Check Backend Module
1. Login to TYPO3 Backend
2. Navigate to **System > Import/Export**
3. Verify modules load without errors
4. Check that test task appears in task list

## Troubleshooting Installation

### Common Issues

**Extension not found in Extension Manager:**
- Clear all caches: `vendor/bin/typo3 cache:flush`
- Check composer installation: `composer show cpsit/t3import_export`

**Command line tools not available:**
- Verify TYPO3 installation: `vendor/bin/typo3 --version`
- Check extension is active in Extension Manager

**Permission errors:**
- Verify web server has read/write access to required directories
- Check TYPO3 file permissions configuration

**Memory issues with large datasets:**
```php
// In LocalConfiguration.php or .htaccess
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

### From Version 1.x to 2.x
1. **Backup Configuration**: Export current TypoScript configuration
2. **Update Composer**: `composer update cpsit/t3import_export`
3. **Review Breaking Changes**: Check CHANGELOG.md for breaking changes
4. **Update Configuration**: Migrate configuration to new format if needed
5. **Test**: Verify all tasks work as expected

### Database Schema Updates
The extension includes database migrations. After update:
```bash
vendor/bin/typo3 database:updateschema
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
-- Remove configuration
DELETE FROM sys_template WHERE config LIKE '%t3import_export%';

-- Remove queue items (if using queue)
DROP TABLE IF EXISTS tx_t3importexport_domain_model_queueitem;
```

## Sources and Support

* **Packagist**: [cpsit/t3import_export](https://packagist.org/packages/cpsit/t3import_export)
* **GitHub**: [dwenzel/t3import_export](https://github.com/dwenzel/t3import_export)
* **Documentation**: This documentation package
* **Issues**: GitHub Issues for bug reports and feature requests

For enterprise support and custom development, contact CPS IT GmbH.
