<?php
defined('TYPO3') or die();

# register extbase command controllers for import and export
\CPSIT\T3importExport\Configuration\Extension::registerLegacyCommands();

// register custom implementation of PersistentObjectConverter
\CPSIT\T3importExport\Configuration\Extension::registerIcons();
