<?php

use CPSIT\T3importExport\Configuration\Extension;
use CPSIT\T3importExport\Domain\Model\QueueItem;

$ll = 'LLL:EXT:t3import_export/Resources/Private/Language/locallang_db.xlf:';
return [
    'ctrl' => [
        'title' => $ll . 'tx_t3importexport_domain_model_queueitem',
        'label' => 'queue',
        'tstamp' => 'tstamp',
        'crdate' => 'crdate',
        'searchFields' => 'queue,checksum,status',
        'typeicon_classes' => [
            'default' => Extension::SVG_ICON_IDENTIFIER_JOBS,
        ],
        'adminOnly' => true,
        'rootLevel' => 1
    ],
    'interface' => [
        'showRecordFieldList' => '
            created_date,
            started_date,
            finished_date,
            queue,
            checksum,
            data,
            status
        ',
    ],
    'types' => [
        '1' => [
            'showitem' => '
                status,
                queue,
                created_date,
                started_date,
                finished_date,
                checksum,
                data,
          '
        ],
    ],
    'columns' => [
        'created_date' => [
            'exclude' => true,
            'label' => $ll . 'label.created_date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'started_date' => [
            'exclude' => true,
            'label' => $ll . 'label.started_date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'finished_date' => [
            'exclude' => true,
            'label' => $ll . 'label.finished_date',
            'config' => [
                'type' => 'input',
                'renderType' => 'inputDateTime',
                'size' => 10,
                'eval' => 'datetime',
                'readOnly' => true,
            ],
        ],
        'queue' => [
            'label' => $ll. 'label.queue',
            'config' => [
                'items' => [],
                'type' => 'select',
                'renderType' => 'selectSingle',
                'foreign_table' => 'tx_t3importexport_domain_model_queue',
            ]
        ],
        'data' => [
            'label' => $ll . 'label.data',
            'config' => [
                'type' => 'text',
                'width' => 200,
                'eval' => 'trim',
                'readOnly' => true,
            ],
        ],
        'checksum' => [
            'label' => $ll . 'label.checksum',
            'config' => [
                'type' => 'input',
                'width' => 200,
                'eval' => 'trim',
                'readOnly' => true,
            ],
        ],
        'status' => [
            'label' => $ll . 'label.status',
            'config' => [
                'type' => 'select',
                'renderType' => 'selectSingle',
                'items' => [
                    [
                        $ll . 'label.status.item.'. QueueItem::STATUS_NEW,
                        QueueItem::STATUS_NEW,
                    ],
                    [
                        $ll . 'label.status.item.' . QueueItem::STATUS_PROCESSING,
                        QueueItem::STATUS_PROCESSING,
                    ],
                    [
                        $ll . 'label.status.item.'. QueueItem::STATUS_FINISHED,
                        QueueItem::STATUS_FINISHED,
                    ],
                    [
                        $ll . 'label.status.item.' . QueueItem::STATUS_FAILED,
                        QueueItem::STATUS_FAILED,
                    ],
                ],
                'readOnly' => true,
            ],
        ]
    ],
];
