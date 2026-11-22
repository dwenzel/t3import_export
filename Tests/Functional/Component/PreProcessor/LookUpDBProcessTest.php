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
}
