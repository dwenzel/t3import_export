<?php
defined('TYPO3') or die();

// Bootstrap extension: loads YAML configuration from
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']
// (default EXT:t3import_export/Configuration/ImportExport, set in Extension::boot() if not configured)
// File sources and targets (Documentation/Persistence/Files.md):
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['fileDirectories'] (default fileadmin/ and var/import/)
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowedHosts'] (default: no URLs; `host` default port, `host:port`, `*.domain`, `*`)
(new \CPSIT\T3importExport\Extension())->boot();
