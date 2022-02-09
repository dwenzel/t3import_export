<?php
if (!defined('TYPO3_MODE')) {
    die('Access denied.');
}

\CPSIT\T3importExport\Configuration\Extension::registerAndConfigureModules();

if (TYPO3_MODE === 'BE') {
    \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::allowTableOnStandardPages('tx_t3importexport_domain_model_exporttarget');
}
