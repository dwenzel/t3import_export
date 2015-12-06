<?php
defined('TYPO3_MODE') or die();

class_alias(
	CPSIT\T3import\PreProcessor\PreProcessorInterface::class,
	CPSIT\ZewImports\PreProcessor\PreProcessorInterface::class
);

# register extbase command controller task for import
$GLOBALS['TYPO3_CONF_VARS']['SC_OPTIONS']['extbase']['commandControllers'][] = 'CPSIT\\T3import\\Command\\ImportCommandController';
