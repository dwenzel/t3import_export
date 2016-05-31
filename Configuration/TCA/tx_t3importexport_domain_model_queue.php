<?php
if (!defined('TYPO3_MODE')) {
    die ('Access denied.');
}
$ll = 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_db.xlf:';
return [
    'ctrl' => [
        'title' => $ll . 'tx_t3importexport_domain_model_queue',
        'label' => 'task_identifier',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'cruser_id' => 'cruser_id',
        'dividers2tabs' => TRUE,
        'delete' => 'deleted',
        'enablecolumns' => [
            'disabled' => 'hidden'
        ],
        'searchFields' => 'headline,subtitle,teaser,description,keywords,image,genre,venue,event_type,performances,organizer,',
        'iconfile' => \TYPO3\CMS\Core\Utility\ExtensionManagementUtility::extRelPath('t3import_export') . 'Resources/Public/Icons/tx_t3events_domain_model_queue.gif'
    ],
    'interface' => [
        'showRecordFieldList' => 'hidden, task_identifier, queue_items',
    ],
    'types' => [
        '1' => [
            'showitem' => '
    	;;;;1-1-1,
    	task_identifier, queue_items;;1,
    	,--div--;LLL:EXT:cms/locallang_ttc.xml:tabs.access,hidden,starttime,endtime'],
    ],
    'columns' => [
        'hidden' => [
            'exclude' => 1,
            'label' => 'LLL:EXT:lang/locallang_general.xml:LGL.hidden',
            'config' => [
                'type' => 'check',
            ],
        ],
        'task_identifier' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queue.task_identifier',
            'config' => [
                'type' => 'input',
                'readOnly' => true
            ],
        ],
        'lock_key' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queue.lock_key',
            'config' => [
                'type' => 'input',
                'readOnly' => true
            ],
        ],
        'config' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queue.config',
            'config' => [
                'type' => 'text',
                'format' => 'javascript',
                'readOnly' => true,
                'eval' => 'trim',
                'cols' => 40,
                'rows' => 5
            ],
        ],
        'size' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queue.size',
            'config' => [
                'type' => 'input',
                'readOnly' => true
            ],
        ],
        'offset' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queue.offset',
            'config' => [
                'type' => 'input',
                'readOnly' => true
            ],
        ],
        'queue_items' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queue.queue_item',
            'config' => [
                'type' => 'inline',
                'foreign_table' => 'tx_t3importexport_domain_model_queueitem',
                'foreign_field' => 'queue',
                'maxitems' => 9999,
                'appearance' => [
                    'expandSingle' => 1,
                    'levelLinksPosition' => 'bottom',
                    'showSynchronizationLink' => 1,
                    'showPossibleLocalizationRecords' => 1,
                    'showAllLocalizationLink' => 1,
                    'newRecordLinkAddTitle' => 1,
                    'useSortable' => 1,
                    'enabledControls' => [
                        'info' => FALSE,
                    ],
                ],
                'noIconsBelowSelect' => TRUE,
            ],
        ],
        'description' => [
            'exclude' => 0,
            'label' => $ll . 'tx_t3importexport_domain_model_queue.description',
            'config' => [
                'type' => 'text',
                'cols' => 32,
                'rows' => 10,
                'eval' => 'trim',
                'default' => ''
            ],
            'defaultExtras' => 'richtext[]'
        ]
    ]
];