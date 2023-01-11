<?php
defined('TYPO3_MODE') or die();


if ((TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Information\Typo3Version::class))->getMajorVersion() < 10) {
    # register extbase command controllers for import and export
    \CPSIT\T3importExport\Configuration\Extension::registerLegacyCommands();
}

// register custom implementation of PersistentObjectConverter
\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerTypeConverter(\CPSIT\T3importExport\Property\TypeConverter\PersistentObjectConverter::class);
\CPSIT\T3importExport\Configuration\Extension::registerIcons();
