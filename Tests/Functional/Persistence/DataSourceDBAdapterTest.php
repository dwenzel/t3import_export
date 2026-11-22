<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\ImportExportCore\Factory\ComponentRegistry;
use CPSIT\ImportExportCore\Persistence\DataSourceInterface;
use CPSIT\T3importExport\Persistence\DataSourceDB;
use CPSIT\T3importExport\Persistence\Query\SelectQuery;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use Doctrine\DBAL\Driver\Exception as DriverException;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * DataSourceDB as adapter on DataSourceDBAL of the core returns the records
 * that the source returned before 4.0, for the options of the core and for
 * the SQL fallback (#132).
 */
#[CoversClass(DataSourceDB::class)]
class DataSourceDBAdapterTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string SECOND_CONNECTION = 'Second';

    /**
     * @var list<string>
     */
    private array $deprecations = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataSourceDBPages.csv');
        DeprecationUtility::reset();
        set_error_handler(function (int $errno, string $message): bool {
            $this->deprecations[] = $message;
            return true;
        }, E_USER_DEPRECATED);
    }

    #[\Override]
    protected function tearDown(): void
    {
        restore_error_handler();
        DeprecationUtility::reset();
        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION])) {
            $this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION)->close();
            unset(
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION],
                $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages']
            );
        }
        parent::tearDown();
    }

    /**
     * Reads the records as DataSourceDB did before 4.0 (3.x and main before
     * the adapter): `SelectQuery` with the query builder of TYPO3, without
     * restrictions; a failing query returns no records.
     *
     * @param array<string, mixed> $configuration
     * @return list<array<string, mixed>>
     */
    private function readAsBefore40(array $configuration): array
    {
        $query = $this->get(SelectQuery::class);
        try {
            return $query->withConfiguration($configuration)
                ->setQuery()
                ->build()
                ->executeQuery()
                ->fetchAllAssociative();
        } catch (DriverException) {
            return [];
        }
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function optionsOfTheCoreDataProvider(): array
    {
        return [
            'table only' => [['table' => 'pages']],
            'fields' => [['table' => 'pages', 'fields' => 'uid, pid,title', 'orderBy' => 'uid']],
            'all fields' => [['table' => 'pages', 'fields' => '*', 'orderBy' => 'uid']],
            'sort order' => [['table' => 'pages', 'fields' => 'uid,title,sorting', 'orderBy' => 'sorting DESC, uid']],
            'limit as string' => [['table' => 'pages', 'fields' => 'uid', 'orderBy' => 'uid desc', 'limit' => '3']],
            'limit as integer' => [['table' => 'pages', 'fields' => 'uid', 'orderBy' => 'uid', 'limit' => 2]],
            'empty values' => [['table' => 'pages', 'fields' => '', 'where' => '', 'groupBy' => '', 'orderBy' => 'uid', 'limit' => '']],
            'ignored type and join without table' => [['table' => 'pages', 'type' => 'selectJoin', 'join' => ['alias' => 'p'], 'fields' => 'uid', 'orderBy' => 'uid']],
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[Test]
    #[DataProvider('optionsOfTheCoreDataProvider')]
    public function optionsWithoutSqlAreReadByTheCoreWithTheRecordsOfBefore40(array $configuration): void
    {
        $expected = $this->readAsBefore40($configuration);

        $records = $this->get(DataSourceDB::class)->getRecords($configuration);

        self::assertNotSame([], $expected);
        self::assertSame($expected, $records);
        self::assertSame([], $this->deprecations);
    }

    /**
     * @return array<string, array{array<string, mixed>, string}>
     */
    public static function sqlDataProvider(): array
    {
        return [
            'where string' => [
                ['table' => 'pages', 'fields' => 'uid,title', 'where' => 'pid = 1 AND is_siteroot = 0', 'orderBy' => 'uid desc'],
                '"where"',
            ],
            'where string with OR and IN' => [
                ['table' => 'pages', 'where' => 'uid IN (1, 4, 6) OR (pid = 2 AND sorting > 256)', 'orderBy' => 'uid'],
                '"where"',
            ],
            'join with alias' => [
                [
                    'table' => 'pages',
                    'alias' => 'child',
                    'fields' => 'child.uid,parent.title',
                    'join' => ['table' => 'pages', 'alias' => 'parent', 'condition' => 'parent.uid = child.pid'],
                    'where' => 'parent.pid = 1',
                    'orderBy' => 'child.uid DESC',
                ],
                '"alias", "where", "join", "fields", "orderBy"',
            ],
            'join without alias' => [
                [
                    'table' => 'pages',
                    'fields' => 'pages.uid',
                    'join' => ['table' => 'sys_category', 'alias' => 'category', 'condition' => 'category.pid = pages.uid'],
                ],
                '"join", "fields"',
            ],
            'group by' => [
                ['table' => 'pages', 'fields' => 'pid', 'groupBy' => 'pid', 'orderBy' => 'pid'],
                '"groupBy"',
            ],
            'field with alias' => [
                ['table' => 'pages', 'fields' => 'uid AS id, title', 'where' => 'deleted = 0', 'orderBy' => 'uid'],
                '"where", "fields"',
            ],
            'limit with offset' => [
                ['table' => 'pages', 'fields' => 'uid', 'orderBy' => 'uid', 'limit' => '2, 3'],
                '"limit"',
            ],
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[Test]
    #[DataProvider('sqlDataProvider')]
    public function sqlIsReadByTheFallbackWithTheRecordsOfBefore40AndDeprecated(array $configuration, string $options): void
    {
        $expected = $this->readAsBefore40($configuration);

        $records = $this->get(DataSourceDB::class)->getRecords($configuration);

        self::assertSame($expected, $records);
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('SQL in the options ' . $options . ' of DataSourceDB is deprecated', $this->deprecations[0]);
    }

    #[Test]
    public function fallbackReadsRecordsOfJoinAndGroupBy(): void
    {
        $subject = $this->get(DataSourceDB::class);

        self::assertSame(
            [['uid' => 8, 'title' => 'Subpage 2'], ['uid' => 7, 'title' => 'Subpage 1'], ['uid' => 6, 'title' => 'Subpage 1']],
            $subject->getRecords(self::sqlDataProvider()['join with alias'][0])
        );
        self::assertSame(
            [['pid' => 0], ['pid' => 1], ['pid' => 2], ['pid' => 3]],
            $subject->getRecords(self::sqlDataProvider()['group by'][0])
        );
    }

    /**
     * The source did not apply the restrictions of TYPO3 (deleted, hidden,
     * start and end time) before 4.0; neither does the core nor the
     * fallback.
     */
    #[Test]
    public function restrictionsOfTypo3AreNotApplied(): void
    {
        $subject = $this->get(DataSourceDB::class);
        $core = ['table' => 'pages', 'fields' => 'uid', 'orderBy' => 'uid'];
        $fallback = ['table' => 'pages', 'fields' => 'uid', 'where' => 'pid > 0', 'orderBy' => 'uid'];

        self::assertSame([1, 2, 3, 4, 5, 6, 7, 8], array_column($subject->getRecords($core), 'uid'));
        self::assertSame([2, 3, 4, 5, 6, 7, 8], array_column($subject->getRecords($fallback), 'uid'));
        self::assertSame($this->readAsBefore40($core), $subject->getRecords($core));
        self::assertSame($this->readAsBefore40($fallback), $subject->getRecords($fallback));
    }

    #[Test]
    public function recordsHaveTheTypesOfBefore40(): void
    {
        $configuration = ['table' => 'pages', 'fields' => 'uid,title,hidden,starttime', 'orderBy' => 'uid', 'limit' => 1];

        $records = $this->get(DataSourceDB::class)->getRecords($configuration);

        self::assertSame($this->readAsBefore40($configuration), $records);
        self::assertIsArray($records);
        self::assertSame(
            array_map(get_debug_type(...), $this->readAsBefore40($configuration)[0]),
            array_map(get_debug_type(...), $records[0])
        );
    }

    #[Test]
    public function expressionsAreEvaluatedBeforeTheQueryIsBuilt(): void
    {
        $configuration = [
            'table' => 'pages',
            'fields' => 'uid,title',
            'where' => ['expression' => "'pid = ' ~ (0 + 1)"],
            'orderBy' => 'uid',
            'limit' => ['expression' => '2'],
        ];

        $records = $this->get(DataSourceDB::class)->getRecords($configuration);

        self::assertSame([['uid' => 2, 'title' => 'Subpage 1'], ['uid' => 3, 'title' => 'Subpage 2']], $records);
    }

    #[Test]
    public function optionsOfTheCoreCanBeUsed(): void
    {
        $records = $this->get(DataSourceDB::class)->getRecords([
            'table' => 'pages',
            'fields' => ['uid'],
            'where' => ['pid' => 1, 'deleted' => 0, 'hidden' => 0],
            'filter' => ['field' => 'sorting', 'op' => '>', 'value' => 256],
            'orderBy' => ['uid' => 'ASC'],
        ]);

        self::assertSame([['uid' => 2]], $records);
        self::assertSame([], $this->deprecations);
    }

    #[Test]
    public function tableMappingSelectsTheConnection(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping']['pages'] = self::SECOND_CONNECTION;
        $subject = $this->get(DataSourceDB::class);
        $core = ['table' => 'pages', 'fields' => 'uid', 'orderBy' => 'uid'];
        $fallback = ['table' => 'pages', 'fields' => 'uid', 'where' => 'pid = 1', 'orderBy' => 'uid'];

        self::assertSame($this->readAsBefore40($core), $subject->getRecords($core));
        self::assertSame($this->readAsBefore40($fallback), $subject->getRecords($fallback));
    }

    #[Test]
    public function identifierSelectsTheConnection(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $subject = $this->get(DataSourceDB::class);
        $subject->setIdentifier(self::SECOND_CONNECTION);

        $records = $subject->getRecords(['table' => 'pages', 'fields' => 'uid', 'where' => 'pid = 2', 'orderBy' => 'uid']);

        self::assertSame([['uid' => 6], ['uid' => 7]], $records);
        self::assertCount(2, $this->deprecations);
        self::assertStringContainsString('"identifier"', $this->deprecations[1]);
    }

    #[Test]
    public function failingQueryReturnsNoRecordsAsBefore40(): void
    {
        $subject = $this->get(DataSourceDB::class);

        self::assertSame([], $subject->getRecords(['table' => 'pages', 'where' => 'no_such_field = 1']));
        self::assertSame([], $subject->getRecords(['table' => 'tx_no_such_table']));
    }

    #[Test]
    public function dataSourceDBIsTheDefaultSource(): void
    {
        self::assertSame(
            DataSourceDB::class,
            $this->get(ComponentRegistry::class)->resolve(DataSourceInterface::class)
        );
    }
}
