<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\LookUpDB;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * LookUpDB::process() runs its queries against the database (#165)
 */
class LookUpDBProcessTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected LookUpDB $subject;

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
        $this->subject = $this->get(LookUpDB::class);
    }

    #[Test]
    public function processSetsSingleRowFieldsFromSelectQuery(): void
    {
        $configuration = [
            'targetField' => 'parent',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid,title',
                'where' => [
                    'AND' => [
                        'condition' => 'slug=',
                        'value' => 'parentSlug',
                    ],
                ],
                'singleRow' => 1,
            ],
            'fields' => [
                'uid' => ['mapTo' => 'parentUid'],
                'title' => ['mapTo' => 'parentTitle'],
            ],
        ];
        $record = ['parentSlug' => '/subpage-2'];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame(3, $record['parentUid']);
        self::assertSame('Subpage 2', $record['parentTitle']);
    }

    #[Test]
    public function processSetsAllRowsInTargetField(): void
    {
        $configuration = [
            'targetField' => 'children',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid',
                'where' => [
                    'AND' => [
                        'condition' => 'pid=',
                        'value' => 'uid',
                    ],
                ],
                'orderBy' => 'uid desc',
            ],
            'fields' => [
                'uid' => ['mapTo' => 'childUid'],
            ],
        ];
        $record = ['uid' => 1];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame(
            [['childUid' => 3], ['childUid' => 2]],
            $record['children']
        );
    }

    #[Test]
    public function processRemovesTargetFieldIfNothingIsFound(): void
    {
        $configuration = [
            'targetField' => 'children',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid',
                'where' => [
                    'AND' => [
                        'condition' => 'pid=',
                        'value' => 'uid',
                    ],
                ],
            ],
        ];
        $record = ['uid' => 3, 'children' => 'foo'];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertArrayNotHasKey('children', $record);
    }

    #[Test]
    public function processRunsSelectJoinQuery(): void
    {
        $configuration = [
            'targetField' => 'parent',
            'select' => [
                'type' => 'selectJoin',
                'table' => 'pages',
                'fields' => 'parent.title',
                'leftJoin' => [
                    'fromAlias' => 'child',
                    'table' => 'pages',
                    'alias' => 'parent',
                    'condition' => 'parent.uid = child.pid',
                ],
                'where' => [
                    'AND' => [
                        'condition' => 'child.uid=',
                        'value' => 'uid',
                    ],
                ],
                'singleRow' => 1,
            ],
            'fields' => [
                'title' => ['mapTo' => 'parentTitle'],
            ],
        ];
        $record = ['uid' => 2];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame('Root', $record['parentTitle']);
    }

    #[Test]
    public function processKeepsOrderOfInValues(): void
    {
        $configuration = [
            'targetField' => 'pages',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid',
                'where' => [
                    'IN' => [
                        'field' => 'title',
                        'keepOrder' => 1,
                        'values' => [
                            'field' => 'titles',
                        ],
                    ],
                    'AND' => [
                        'condition' => 'pid>=',
                        'value' => 'minPid',
                    ],
                ],
            ],
            'fields' => [
                'uid' => ['mapTo' => 'uid'],
            ],
        ];
        $record = [
            'titles' => ['Subpage 2', 'Root', 'Subpage 1'],
            'minPid' => 0,
        ];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame(
            [['uid' => 3], ['uid' => 1], ['uid' => 2]],
            $record['pages']
        );
    }

    #[Test]
    public function processKeepsOrderOfInValuesForSingleRow(): void
    {
        $configuration = [
            'targetField' => 'page',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid',
                'where' => [
                    'AND' => [
                        'condition' => 'pid=',
                        'value' => 'pid',
                    ],
                    'IN' => [
                        'field' => 'slug',
                        'keepOrder' => 1,
                        'values' => [
                            'field' => 'slugs',
                            'prefix' => '/',
                        ],
                    ],
                ],
                'singleRow' => 1,
            ],
            'fields' => [
                'uid' => ['mapTo' => 'pageUid'],
            ],
        ];
        $record = [
            'pid' => 1,
            'slugs' => ['subpage-2', 'subpage-1'],
        ];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame(3, $record['pageUid']);
    }

    #[Test]
    public function processRemovesTargetFieldForEmptyInValues(): void
    {
        $configuration = [
            'targetField' => 'pages',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid',
                'where' => [
                    'IN' => [
                        'field' => 'title',
                        'keepOrder' => 1,
                        'values' => [
                            'field' => 'titles',
                        ],
                    ],
                ],
            ],
            'fields' => [
                'uid' => ['mapTo' => 'uid'],
            ],
        ];
        $record = ['titles' => [], 'pages' => 'foo'];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertArrayNotHasKey('pages', $record);
    }

    #[Test]
    public function processFindsOrConstraintForEmptyInValues(): void
    {
        $configuration = [
            'targetField' => 'pages',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid',
                'where' => [
                    'IN' => [
                        'field' => 'title',
                        'values' => [
                            'field' => 'titles',
                        ],
                    ],
                    'OR' => [
                        'condition' => 'slug=',
                        'value' => 'slug',
                    ],
                ],
            ],
            'fields' => [
                'uid' => ['mapTo' => 'uid'],
            ],
        ];
        $record = ['titles' => [], 'slug' => '/subpage-1'];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame([['uid' => 2]], $record['pages']);
    }

    #[Test]
    public function processLeavesRecordUnchangedForSingleRowWithoutResult(): void
    {
        $configuration = [
            'targetField' => 'parent',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid',
                'where' => [
                    'AND' => [
                        'condition' => 'slug=',
                        'value' => 'parentSlug',
                    ],
                ],
                'singleRow' => 1,
            ],
            'fields' => [
                'uid' => ['mapTo' => 'parentUid'],
            ],
        ];
        $record = ['parentSlug' => '/missing'];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame(['parentSlug' => '/missing'], $record);
    }

    #[Test]
    public function processDoesNotSetTargetFieldForEmptyResult(): void
    {
        $configuration = [
            'targetField' => 'children',
            'select' => [
                'table' => 'pages',
                'fields' => 'uid',
                'where' => [
                    'AND' => [
                        'condition' => 'pid=',
                        'value' => 'uid',
                    ],
                ],
            ],
            'fields' => [
                'uid' => ['mapTo' => 'childUid'],
            ],
        ];
        $record = ['uid' => 3];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame(['uid' => 3], $record);
    }

    #[Test]
    public function processDoesNotSortRowsForKeepOrderWithGroupBy(): void
    {
        $configuration = [
            'targetField' => 'pages',
            'select' => [
                'table' => 'pages',
                'fields' => 'pid',
                'where' => [
                    'IN' => [
                        'field' => 'title',
                        'keepOrder' => 1,
                        'values' => [
                            'field' => 'titles',
                        ],
                    ],
                ],
                'groupBy' => 'pid',
                'orderBy' => 'pid',
            ],
            'fields' => [
                'pid' => ['mapTo' => 'pid'],
            ],
        ];
        $record = ['titles' => ['Subpage 2', 'Root', 'Subpage 1']];

        self::assertTrue($this->subject->process($configuration, $record));
        self::assertSame([['pid' => 0], ['pid' => 1]], $record['pages']);
    }
}
