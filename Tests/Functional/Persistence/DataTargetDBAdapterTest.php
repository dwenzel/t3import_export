<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Persistence;

use CPSIT\ImportExportCore\Domain\Model\TaskResult;
use CPSIT\ImportExportCore\Exception\PersistenceException;
use CPSIT\ImportExportCore\Factory\TransferTaskFactory;
use CPSIT\T3importExport\Persistence\DataSourceDB;
use CPSIT\T3importExport\Persistence\DataTargetDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use CPSIT\T3importExport\Service\TransferService;
use CPSIT\T3importExport\Tests\Functional\Fixtures\Classes\DataTargetDBBefore40;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use Doctrine\DBAL\Platforms\SQLitePlatform;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * DataTargetDB as adapter on DataTargetDBAL of the core writes the rows that
 * the target wrote before 4.0 (#132). The same records are written with the
 * class of earlier versions (fixture `DataTargetDBBefore40`) and with the
 * adapter, and the rows of the table are compared.
 */
#[CoversClass(DataTargetDB::class)]
class DataTargetDBAdapterTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string TABLE = 'tt_content';
    private const string FIXTURE = __DIR__ . '/../Fixtures/dataTargetDBContent.csv';
    private const string SECOND_CONNECTION = 'Second';

    /**
     * @var list<string>
     */
    private array $deprecations = [];

    #[\Override]
    protected function setUp(): void
    {
        parent::setUp();
        $this->importCSVDataSet(self::FIXTURE);
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
                $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][self::TABLE]
            );
        }
        parent::tearDown();
    }

    /**
     * All rows of the table, without the restrictions of TYPO3
     *
     * @return list<array<string, mixed>>
     */
    private function rows(string $table = self::TABLE): array
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable($table);

        return $connection->executeQuery(
            'SELECT * FROM ' . $connection->quoteIdentifier($table) . ' ORDER BY ' . $connection->quoteIdentifier('uid')
        )->fetchAllAssociative();
    }

    /**
     * Empties the table and its sequence and imports the fixture again
     */
    private function resetTable(): void
    {
        $connection = $this->get(ConnectionPool::class)->getConnectionForTable(self::TABLE);
        $connection->truncate(self::TABLE);
        if ($connection->getDatabasePlatform() instanceof SQLitePlatform) {
            try {
                $connection->executeStatement('DELETE FROM sqlite_sequence WHERE name = ?', [self::TABLE]);
            } catch (\Throwable) {
                // the table has no sequence
            }
        }
        $this->importCSVDataSet(self::FIXTURE);
    }

    private function createTargetOfBefore40(): DataTargetDBBefore40
    {
        $connectionPool = $this->get(ConnectionPool::class);

        return new DataTargetDBBefore40($connectionPool, new DatabaseConnectionService($connectionPool));
    }

    /**
     * Writes the records with the target and returns the results of persist()
     * (or the class and code of the exception) and the rows of the table.
     *
     * @param array<array-key, mixed> $configuration
     * @param list<array<array-key, mixed>> $records
     * @return array{list<mixed>, list<array<string, mixed>>}
     */
    private function write(DataTargetDB|DataTargetDBBefore40 $target, array $configuration, array $records): array
    {
        $results = [];
        foreach ($records as $record) {
            try {
                $results[] = $target->persist($record, $configuration);
            } catch (\Throwable $exception) {
                $results[] = [$exception::class, $exception->getCode()];
            }
        }
        $target->persistAll(null, $configuration);

        return [$results, $this->rows()];
    }

    /**
     * Writes the records as before 4.0 and with the adapter (each on the rows
     * of the fixture) and returns both results.
     *
     * @param array<array-key, mixed> $configuration
     * @param list<array<array-key, mixed>> $records
     * @return array{array{list<mixed>, list<array<string, mixed>>}, array{list<mixed>, list<array<string, mixed>>}}
     */
    private function writeAsBefore40AndWithAdapter(array $configuration, array $records): array
    {
        $before40 = $this->write($this->createTargetOfBefore40(), $configuration, $records);
        $this->resetTable();
        $adapter = $this->write($this->get(DataTargetDB::class), $configuration, $records);

        return [$before40, $adapter];
    }

    /**
     * @return array<string, array{array<array-key, mixed>, list<array<array-key, mixed>>}>
     */
    public static function recordsDataProvider(): array
    {
        $table = ['table' => self::TABLE];

        return [
            'insert' => [
                $table,
                [
                    ['pid' => 3, 'header' => 'New', 'bodytext' => 'Body', 'colPos' => 2, 'sorting' => 128],
                    ['pid' => 3, 'header' => 'Other'],
                ],
            ],
            'insert with uid' => [
                $table,
                [['uid' => 10, 'pid' => 3, 'header' => 'Ten']],
            ],
            'values of different types' => [
                $table,
                [['pid' => '4', 'header' => 1.5, 'bodytext' => null, 'colPos' => '1', 'hidden' => true, 'sorting' => 12]],
            ],
            'booleans in string columns' => [
                $table,
                [['header' => false, 'bodytext' => true], ['__identity' => 1, 'header' => false, 'bodytext' => true]],
            ],
            'booleans in integer columns' => [
                $table,
                [['header' => 'x', 'colPos' => false, 'hidden' => true]],
            ],
            'strings in integer columns (e.g. from CSV)' => [
                $table,
                [
                    ['header' => 'x', 'colPos' => '', 'sorting' => '12abc', 'hidden' => '1'],
                    ['__identity' => 1, 'colPos' => '', 'sorting' => ' 7 '],
                ],
            ],
            'numbers in string columns' => [
                $table,
                [['header' => 12, 'bodytext' => 1.25]],
            ],
            'floats in integer columns' => [
                $table,
                [['__identity' => 1, 'sorting' => 2.5, 'colPos' => -1.5]],
            ],
            'update by __identity' => [
                $table,
                [['__identity' => 1, 'header' => 'Updated', 'hidden' => 1]],
            ],
            'update by __identity as string' => [
                $table,
                [['__identity' => '2', 'header' => 'Updated', 'bodytext' => null]],
            ],
            'update by __identity of a row that does not exist' => [
                $table,
                [['__identity' => 99, 'header' => 'Missing']],
            ],
            'empty __identity' => [
                $table,
                [
                    ['__identity' => '', 'header' => 'A'],
                    ['__identity' => 0, 'header' => 'B'],
                    ['__identity' => null, 'header' => 'C'],
                    ['__identity' => '0', 'header' => 'D'],
                ],
            ],
            '__identity and the same uid' => [
                $table,
                [['__identity' => 1, 'uid' => '1', 'header' => 'Same']],
            ],
            '__identity and another uid (fallback)' => [
                $table,
                [['__identity' => 1, 'uid' => 20, 'header' => 'Moved']],
            ],
            '__identity and uid null (fallback)' => [
                $table,
                [['__identity' => 1, 'uid' => null, 'header' => 'Null']],
            ],
            'insert and update of the inserted row' => [
                $table,
                [
                    ['uid' => 30, 'pid' => 3, 'header' => 'Inserted'],
                    ['__identity' => 30, 'header' => 'Updated'],
                    ['__identity' => 3, 'pid' => 4],
                ],
            ],
            'unsetKeys' => [
                ['table' => self::TABLE, 'unsetKeys' => 'foo, bar'],
                [['header' => 'New', 'foo' => 'x', 'bar' => 'y'], ['__identity' => 1, 'header' => 'Updated', 'foo' => 'x']],
            ],
            'unsetKeys removes __identity' => [
                ['table' => self::TABLE, 'unsetKeys' => '__identity'],
                [['__identity' => 1, 'header' => 'Inserted']],
            ],
            'skip ifEmpty' => [
                ['table' => self::TABLE, 'skip' => ['ifEmpty' => ['field' => 'header']]],
                [['header' => '', 'pid' => 5], ['header' => 'Written', 'pid' => 5], ['pid' => 6]],
            ],
            'skip ifNotEmpty' => [
                ['table' => self::TABLE, 'skip' => ['ifNotEmpty' => ['field' => '__identity']]],
                [['__identity' => 1, 'header' => 'Skipped'], ['header' => 'Written']],
            ],
            'options without effect' => [
                ['table' => self::TABLE, 'pid' => 123, 'mode' => 'replace', 'updateField' => 'header'],
                [['header' => 'New'], ['__identity' => 2, 'header' => 'Updated']],
            ],
            'unknown column' => [
                $table,
                [['header' => 'New', 'unknown' => 'x']],
            ],
            'update of an unknown column' => [
                $table,
                [['__identity' => 1, 'unknown' => 'x']],
            ],
        ];
    }

    /**
     * @param array<array-key, mixed> $configuration
     * @param list<array<array-key, mixed>> $records
     */
    #[Test]
    #[DataProvider('recordsDataProvider')]
    public function adapterWritesTheRowsOfBefore40(array $configuration, array $records): void
    {
        [$before40, $adapter] = $this->writeAsBefore40AndWithAdapter($configuration, $records);

        self::assertSame($before40[0], $adapter[0], 'results of persist()');
        self::assertSame($before40[1], $adapter[1], 'rows');
    }

    #[Test]
    public function changingTheUidIsDeprecated(): void
    {
        $subject = $this->get(DataTargetDB::class);

        $subject->persist(['__identity' => 1, 'uid' => 20], ['table' => self::TABLE]);
        $subject->persistAll();

        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('"__identity" and another "uid"', $this->deprecations[0]);
    }

    #[Test]
    public function identifierHasNoEffectAndIsDeprecated(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $configuration = ['table' => self::TABLE, 'identifier' => self::SECOND_CONNECTION];
        $subject = $this->get(DataTargetDB::class);

        $subject->persist(['header' => 'New'], $configuration);

        self::assertTrue($this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->isTransactionActive());
        self::assertFalse($this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION)->isTransactionActive());
        $subject->persistAll();
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('"identifier" of DataTargetDB has no effect', $this->deprecations[0]);
    }

    #[Test]
    public function tableMappingSelectsTheConnection(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][self::TABLE] = self::SECOND_CONNECTION;
        $subject = $this->get(DataTargetDB::class);

        $subject->persist(['header' => 'New'], ['table' => self::TABLE]);

        self::assertTrue($this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION)->isTransactionActive());
        self::assertFalse($this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->isTransactionActive());
        $subject->persistAll();
        self::assertSame('New', $this->rows()[3]['header']);
    }

    #[Test]
    public function connectionSelectsTheConnection(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $subject = $this->get(DataTargetDB::class);

        $subject->persist(['header' => 'New'], ['table' => self::TABLE, 'connection' => self::SECOND_CONNECTION]);

        self::assertTrue($this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION)->isTransactionActive());
        self::assertFalse($this->get(ConnectionPool::class)->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)->isTransactionActive());
        $subject->persistAll();
        self::assertSame([], $this->deprecations);
    }

    #[Test]
    public function failRollsBackTheRowsOfTheBatchIncludingTheFallback(): void
    {
        $subject = $this->get(DataTargetDB::class);
        $expected = $this->rows();

        $subject->persist(['header' => 'New'], ['table' => self::TABLE]);
        $subject->persist(['__identity' => 1, 'header' => 'Updated'], ['table' => self::TABLE]);
        $subject->persist(['__identity' => 2, 'uid' => 20, 'header' => 'Moved'], ['table' => self::TABLE]);
        $subject->fail(new \RuntimeException('failed'));
        $subject->fail(new \RuntimeException('failed again'));
        $subject->persistAll();

        self::assertSame($expected, $this->rows());
    }

    #[Test]
    public function aFailingTaskRollsBackItsBatch(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataSourceDBPages.csv');
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataTargetDBCategory.csv');
        $expected = $this->rows('sys_category');
        $task = $this->get(TransferTaskFactory::class)->create('pagesToCategories', [
            'source' => [
                'class' => DataSourceDB::class,
                'config' => ['table' => 'pages', 'fields' => 'uid,pid,title', 'orderBy' => 'uid'],
            ],
            'target' => [
                'class' => DataTargetDB::class,
                'config' => ['table' => 'sys_category'],
            ],
        ]);

        try {
            $this->get(TransferService::class)->run([$task]);
            self::fail('The task should fail.');
        } catch (PersistenceException $exception) {
            // uid 3 exists: pages 1 and 2 were written before 4.0
            self::assertSame(DataTargetDB::PERSISTENCE_EXCEPTION_CODE, $exception->getCode());
        }

        self::assertSame($expected, $this->rows('sys_category'));
        self::assertFalse($this->get(ConnectionPool::class)->getConnectionForTable('sys_category')->isTransactionActive());
    }

    #[Test]
    public function aTaskWritesTheRowsOfBefore40(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataSourceDBPages.csv');
        $source = ['table' => 'pages', 'fields' => 'pid,title', 'orderBy' => 'uid'];
        $target = ['table' => self::TABLE];
        $records = array_map(
            static fn(array $page): array => ['pid' => $page['pid'], 'header' => $page['title']],
            $this->get(DataSourceDB::class)->getRecords($source)
        );
        $expected = $this->write($this->createTargetOfBefore40(), $target, $records)[1];
        $this->resetTable();
        $task = $this->get(TransferTaskFactory::class)->create('pagesToContent', [
            'source' => ['class' => DataSourceDB::class, 'config' => $source],
            'preProcessors' => [
                10 => [
                    'class' => 'CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor',
                    'config' => ['rules' => [['rule' => 'rename', 'field' => 'header', 'from' => 'title']]],
                ],
            ],
            'target' => ['class' => DataTargetDB::class, 'config' => $target],
        ]);

        $this->get(TransferService::class)->run([$task]);

        self::assertCount(11, $expected);
        self::assertSame($expected, $this->rows());
    }

    #[Test]
    public function disabledTargetWritesNothing(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataSourceDBPages.csv');
        $expected = $this->rows();
        $task = $this->get(TransferTaskFactory::class)->create('disabledTarget', [
            'source' => ['class' => DataSourceDB::class, 'config' => ['table' => 'pages', 'fields' => 'pid']],
            'target' => ['class' => DataTargetDB::class, 'config' => ['table' => self::TABLE, 'disable' => 1]],
        ]);

        $this->get(TransferService::class)->run([$task]);

        self::assertSame($expected, $this->rows());
    }

    #[Test]
    public function failRollsBackAFallbackThatStartedTheBatch(): void
    {
        $subject = $this->get(DataTargetDB::class);
        $expected = $this->rows();

        $subject->persist(['__identity' => 2, 'uid' => 20, 'header' => 'Moved'], ['table' => self::TABLE]);
        self::assertTrue($this->get(ConnectionPool::class)->getConnectionForTable(self::TABLE)->isTransactionActive());
        $subject->fail(new \RuntimeException('failed'));

        self::assertFalse($this->get(ConnectionPool::class)->getConnectionForTable(self::TABLE)->isTransactionActive());
        self::assertSame($expected, $this->rows());
    }

    #[Test]
    public function fallbackStartsANewBatchAfterFlushBatch(): void
    {
        $subject = $this->get(DataTargetDB::class);
        $first = ['header' => 'First batch'];
        $moved = ['__identity' => 2, 'uid' => 20, 'header' => 'Moved'];

        $subject->persist($first, ['table' => self::TABLE]);
        $subject->flushBatch(new TaskResult());
        $subject->persist($moved, ['table' => self::TABLE]);
        $subject->persistAll();

        self::assertSame('20', $subject->getTargetId($moved));
        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(1794700024);
        $subject->getTargetId($first);
    }

    /**
     * With an ID map, the processor asks for the ID of each written record
     * (the extension does not store the map yet, the core uses NullIdMap).
     */
    #[Test]
    public function idMapGetsTheUidOfRecordsWithUnsetKeys(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataSourceDBPages.csv');
        $task = $this->get(TransferTaskFactory::class)->create('idMapWithUnsetKeys', [
            'source' => ['class' => DataSourceDB::class, 'config' => ['table' => 'pages', 'fields' => 'uid,pid,title', 'orderBy' => 'uid', 'limit' => 2]],
            'idMap' => ['namespace' => 'pages', 'sourceIdField' => 'uid'],
            'preProcessors' => [
                10 => [
                    'class' => 'CPSIT\\ImportExportCore\\Processor\\ValueRulesPreProcessor',
                    'config' => ['rules' => [['rule' => 'rename', 'field' => 'header', 'from' => 'title']]],
                ],
            ],
            'target' => ['class' => DataTargetDB::class, 'config' => ['table' => self::TABLE, 'unsetKeys' => 'uid']],
        ]);

        $this->get(TransferService::class)->run([$task]);

        self::assertSame(['Root', 'Subpage 1'], \array_slice(array_column($this->rows(), 'header'), 3));
    }

    /**
     * A skipped record has no ID; the processor asks for it nevertheless.
     */
    #[Test]
    public function idMapWithSkipThrowsAClearException(): void
    {
        $this->importCSVDataSet(__DIR__ . '/../Fixtures/dataSourceDBPages.csv');
        $task = $this->get(TransferTaskFactory::class)->create('idMapWithSkip', [
            'source' => ['class' => DataSourceDB::class, 'config' => ['table' => 'pages', 'fields' => 'uid,pid', 'orderBy' => 'uid', 'limit' => 2]],
            'idMap' => ['namespace' => 'pages', 'sourceIdField' => 'uid'],
            'target' => ['class' => DataTargetDB::class, 'config' => ['table' => self::TABLE, 'unsetKeys' => 'uid', 'skip' => ['ifEmpty' => ['field' => 'header']]]],
        ]);

        $this->expectException(\LogicException::class);
        $this->expectExceptionCode(DataTargetDB::SKIPPED_RECORD_HAS_NO_ID_EXCEPTION_CODE);

        $this->get(TransferService::class)->run([$task]);
    }
}
