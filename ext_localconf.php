<?php
defined('TYPO3_MODE') or die();

# register extbase command controllers for import and export
\CPSIT\T3importExport\Configuration\Extension::registerLegacyCommands();

// register custom implementation of PersistentObjectConverter
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\CPSIT\T3importExport\Property\TypeConverter\PersistentObjectConverter::class);
\CPSIT\T3importExport\Configuration\Extension::registerIcons();
