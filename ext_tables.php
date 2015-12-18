<?php
if (!defined('TYPO3_MODE')) {
	die ('Access denied.');
}

if (TYPO3_MODE === 'BE') {
	\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::addModule(
		'T3import',
		'',
		'',
		\TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extPath($_EXTKEY) . 'Configuration/BackendModule/',
		array(
			'access' => 'user,group',
			'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_mod.xlf',
		)
	);

	\TYPO3\CMS\Extbase\Utility\ExtensionUtility::registerModule(
		'CPSIT.' . $_EXTKEY,
		'system',
		'Import',
		'',
		array(
			'Import' => 'index,importTask,importSet',
		),
		array(
			'access' => 'user,group',
			'icon' => 'EXT:' . $_EXTKEY . '/ext_icon.gif',
			'labels' => 'LLL:EXT:' . $_EXTKEY . '/Resources/Private/Language/locallang_import.xlf',
		)
	);
}
