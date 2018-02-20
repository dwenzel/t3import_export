<?php
defined('TYPO3_MODE') or die();

# register extbase command controllers for import and export
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['t3importExportImport'] = \CPSIT\T3importExport\Command\ImportCommandController::class;
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers']['t3importExportExport'] = \CPSIT\T3importExport\Command\ExportCommandController::class;

// register custom implementation of PersistentObjectConverter
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\CPSIT\T3importExport\Property\TypeConverter\PersistentObjectConverter::class);
