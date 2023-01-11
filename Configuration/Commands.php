<?php
declare(strict_types=1);
if ((TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(TYPO3\CMS\Core\Information\Typo3Version::class))->getMajorVersion() < 10) {
    /** @var \CPSIT\T3importExport\Configuration\Extension $configuration */
    $configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\CPSIT\T3importExport\Configuration\Extension::class);
    return $configuration->getCommandsToRegister();
}

