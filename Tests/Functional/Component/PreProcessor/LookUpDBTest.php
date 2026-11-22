<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\LookUpDB;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Utility\GeneralUtility;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * Values of the IN constraint of LookUpDB are quoted
 */
class LookUpDBTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    protected const QUERY_CONFIGURATION = [
        'table' => 'pages',
        'where' => [
            'IN' => [
                'field' => 'title',
                'values' => [
                    'field' => 'titles',
                ],
            ],
        ],
    ];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../../Fixtures/pages.csv');
    }

    #[Test]
    public function inConstraintFindsRecordsByValues(): void
    {
        $record = ['titles' => ['Root', 'Subpage 2']];

        self::assertSame([['uid' => 1], ['uid' => 3]], $this->findPagesByConstraint($record));
    }

    #[Test]
    public function inConstraintTreatsValuesWithQuotesAndSqlAsPlainValues(): void
    {
        $record = [
            'titles' => [
                'Root',
                '") OR ("1"="1',
                "') OR ('1'='1",
            ],
        ];

        self::assertSame([['uid' => 1]], $this->findPagesByConstraint($record));
    }

    /**
     * Builds the WHERE clause with LookUpDB and executes it
     */
    protected function findPagesByConstraint(array $record): array
    {
        $subject = GeneralUtility::makeInstance(LookUpDB::class);
        $method = new \ReflectionMethod($subject, 'parseQueryConstraints');
        $queryConfiguration = $method->invoke($subject, $record, self::QUERY_CONFIGURATION);

        $queryBuilder = $this->get(ConnectionPool::class)->getQueryBuilderForTable('pages');

        return $queryBuilder->select('uid')
            ->from('pages')
            ->where($queryConfiguration['where'])
            ->orderBy('uid')
            ->executeQuery()
            ->fetchAllAssociative();
    }
}
