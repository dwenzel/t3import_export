<?php
defined('TYPO3') or die();

# register extbase command controllers for import and export

// register custom implementation of PersistentObjectConverter
\CPSIT\T3importExport\Configuration\Extension::registerIcons();

// Configure YAML configuration directories
$GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories'] = [
    'EXT:t3import_export/Configuration/ImportExport',
];

// Bootstrap extension
(new \CPSIT\T3importExport\Extension())->boot();
