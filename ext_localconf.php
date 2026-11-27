<?php
defined('TYPO3') or die();

# register extbase command controllers for import and export
\CPSIT\T3importExport\Configuration\Extension::registerLegacyCommands();

// register custom implementation of PersistentObjectConverter
\CPSIT\T3importExport\Configuration\Extension::registerIcons();

// $GLOBALS['TYPO3_CONF_VARS']['EXTCONF']['t3import_export']['allowActiveContentTargets']
// (default false: GenerateFileResource and GenerateUploadFile do not write html, htm, xhtml, xht, shtml, shtm, js, mjs)
