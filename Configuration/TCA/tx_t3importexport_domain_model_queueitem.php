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
                'type' => 'datetime',
                'size' => 10,
                'readOnly' => true,
            ],
        ],
        'started_date' => [
            'exclude' => true,
            'label' => $ll . 'label.started_date',
            'config' => [
                'type' => 'datetime',
                'size' => 10,
                'readOnly' => true,
            ],
        ],
        'finished_date' => [
            'exclude' => true,
            'label' => $ll . 'label.finished_date',
            'config' => [
                'type' => 'datetime',
                'size' => 10,
                'readOnly' => true,
            ],
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
                        'label' => $ll . 'label.status.item.'. QueueItem::STATUS_NEW,
                        'value' => QueueItem::STATUS_NEW,
                    ],
                    [
                        'label' => $ll . 'label.status.item.' . QueueItem::STATUS_PROCESSING,
                        'value' => QueueItem::STATUS_PROCESSING,
                    ],
                    [
                        'label' => $ll . 'label.status.item.'. QueueItem::STATUS_FINISHED,
                        'value' => QueueItem::STATUS_FINISHED,
                    ],
                    [
                        'label' => $ll . 'label.status.item.' . QueueItem::STATUS_FAILED,
                        'value' => QueueItem::STATUS_FAILED,
                    ],
                ],
                'readOnly' => true,
            ],
        ]
    ],
];
