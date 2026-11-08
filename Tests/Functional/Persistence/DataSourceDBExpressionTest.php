<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\T3importExport\Persistence\DataSourceDB;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Query values of DataSourceDB from expressions (#79)
 */
class DataSourceDBExpressionTest extends FunctionalTestCase
{
    protected array $testExtensionsToLoad = [
        't3import_export',
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/pages.csv');
    }

    #[Test]
    public function getRecordsUsesQueryValuesFromExpressions(): void
    {
        $subject = $this->get(DataSourceDB::class);
        $configuration = [
            'table' => 'pages',
            'fields' => 'uid,title',
            'where' => ['expression' => "'pid = ' ~ (0 + 1)"],
            'orderBy' => 'uid',
            'limit' => ['expression' => '1'],
        ];

        $records = $subject->getRecords($configuration);

        self::assertSame([['uid' => 2, 'title' => 'Subpage 1']], $records);
    }
}
