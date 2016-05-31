<?php

if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$ll = 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_db.xlf:';
return [
    'ctrl' => [
        'title' => $ll . 'tx_t3importexport_domain_model_queueitem',
        'label' => 'data_source_index',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => TRUE,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'searchFields' => 'description,',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3import_export') . 'Resources/Public/Icons/tx_t3events_domain_model_queueitem.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden,data_source_index',
    ],
    'types' => [
        '1' => [
            'showitem' => '
    	;;;;1-1-1,
    	data_source_index, queue;;1,
    	,--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,hidden,starttime,endtime\''],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'data_source_index' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queueitem.data_source_index',
            'config' => [
                'type' => 'input',
                'readOnly' => true
            ],
        ],
        'queue' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queueitem.queue',
            'config' => [
                'type' => 'passthrough'
            ],
        ],
        'description' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queueitem.description',
            'config' => [
                'type' => 'text',
                'cols' => 32,
                'rows' => 10,
                'eval' => 'trim'
            ],
            'defaultExtras' => 'richtext[]'
        ]
    ]
];