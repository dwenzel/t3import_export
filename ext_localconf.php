<?php
defined('TYPO3') or die();

# register extbase command controllers for import and export

// register custom implementation of PersistentObjectConverter
\CPSIT\T3importExport\Configuration\Extension::registerIcons();

// Bootstrap extension: loads YAML configuration from
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']
// (default EXT:t3import_export/Configuration/ImportExport, set in Extension::boot() if not configured)
(new \CPSIT\T3importExport\Extension())->boot();
