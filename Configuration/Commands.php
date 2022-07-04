<?php
declare(strict_types=1);

/** @var \CPSIT\T3importExport\Configuration\Extension $configuration */
$configuration = \TYPO3\CMS\Core\Utility\GeneralUtility::makeInstance(\CPSIT\T3importExport\Configuration\Extension::class);

return $configuration->getCommandsToRegister();
