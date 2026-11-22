<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceDB;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * DataSourceDB reads records with plain query values (#165)
 */
class DataSourceDBQueryTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    #[Test]
    public function getRecordsUsesWhereString(): void
    {
        $subject = $this->get(DataSourceDB::class);
        $configuration = [
            'table' => 'pages',
            'fields' => 'uid,title',
            'where' => 'pid = 1 AND is_siteroot = 0',
            'orderBy' => 'uid desc',
        ];

        self::assertSame(
            [
                ['uid' => 3, 'title' => 'Subpage 2'],
                ['uid' => 2, 'title' => 'Subpage 1'],
            ],
            $subject->getRecords($configuration)
        );
    }

    #[Test]
    public function getRecordsUsesJoin(): void
    {
        $subject = $this->get(DataSourceDB::class);
        $configuration = [
            'table' => 'pages',
            'alias' => 'child',
            'fields' => 'child.uid,parent.title',
            'join' => [
                'table' => 'pages',
                'alias' => 'parent',
                'condition' => 'parent.uid = child.pid',
            ],
            'where' => 'child.uid = 3',
        ];

        self::assertSame(
            [['uid' => 3, 'title' => 'Root']],
            $subject->getRecords($configuration)
        );
    }
}
