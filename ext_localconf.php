<?php
defined('TYPO3') or die();

// Bootstrap extension: loads YAML configuration from
// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['yamlConfigurationDirectories']
// (default EXT:t3import_export/Configuration/ImportExport, set in Extension::boot() if not configured)
(new \CPSIT\T3importExport\Extension())->boot();

// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets']
// (default false: GenerateFileResource and GenerateUploadFile do not write html, htm, xhtml, xht, shtml, shtm, js, mjs)
