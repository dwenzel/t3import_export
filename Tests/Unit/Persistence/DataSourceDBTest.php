<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\ImportExportCore\Dbal\DataSourceDBAL;
use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\IdentifiableInterface;
use CPSIT\T3importExport\Dbal\Typo3ConnectionProvider;
use CPSIT\T3importExport\Exception\MissingDatabaseException;
use CPSIT\T3importExport\Persistence\DataSourceDB;
use CPSIT\T3importExport\Utility\DeprecationUtility;
use Doctrine\DBAL\DriverManager;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Test for DataSourceDB
 *
 * Queries of the SQL fallback use the query builder of TYPO3 and are tested
 * in Tests/Functional/Persistence/DataSourceDBAdapterTest.
 */
#[CoversClass(DataSourceDB::class)]
class DataSourceDBTest extends TestCase
{
    protected DataSourceDB $subject;
    protected ConnectionPool&Stub $connectionPool;

    /**
     * @var array<string, Connection>
     */
    private array $connections = [];

    /**
     * @var list<string>
     */
    private array $deprecations = [];

    protected function setUp(): void
    {
        DeprecationUtility::reset();
        $GLOBALS['TYPO3_CONF_VARS']['DB'] = [
            'Connections' => [
                'Default' => ['driver' => 'pdo_sqlite'],
                'Legacy' => ['driver' => 'pdo_sqlite'],
            ],
            'TableMapping' => [
                'tx_legacy_record' => 'Legacy',
            ],
        ];
        $this->connections = [
            'Default' => $this->createConnection('Default'),
            'Legacy' => $this->createConnection('Legacy'),
        ];
        $this->connectionPool = $this->createStub(ConnectionPool::class);
        $this->connectionPool->method('getConnectionByName')
            ->willReturnCallback(fn(string $name): Connection => $this->connections[$name]);
        $this->subject = new DataSourceDB(new Typo3ConnectionProvider($this->connectionPool));
        set_error_handler(function (int $errno, string $message): bool {
            $this->deprecations[] = $message;
            return true;
        }, E_USER_DEPRECATED);
    }

    protected function tearDown(): void
    {
        restore_error_handler();
        DeprecationUtility::reset();
        unset($GLOBALS['TYPO3_CONF_VARS']['DB']);
    }

    /**
     * Creates a connection to a database in memory with the tables `pages`
     * and `tx_legacy_record`, whose titles name the connection.
     */
    private function createConnection(string $name): Connection
    {
        /** @var Connection $connection */
        $connection = DriverManager::getConnection([
            'driver' => 'pdo_sqlite',
            'memory' => true,
            'wrapperClass' => Connection::class,
        ]);
        foreach (['pages', 'tx_legacy_record'] as $table) {
            $connection->executeStatement('CREATE TABLE ' . $table . ' (uid INTEGER PRIMARY KEY, pid INTEGER, title VARCHAR(255))');
            $connection->executeStatement(
                'INSERT INTO ' . $table . ' (uid, pid, title) VALUES (1, 0, ?), (2, 1, ?), (3, 1, ?)',
                [$name . ' 1', $name . ' 2', $name . ' 3']
            );
        }

        return $connection;
    }

    #[Test]
    public function isDataSourceOfTheCoreAndIdentifiable(): void
    {
        self::assertInstanceOf(DataSourceDBAL::class, $this->subject);
        self::assertInstanceOf(IdentifiableInterface::class, $this->subject);
    }

    #[Test]
    public function isConfigurationValidReturnsFalseForEmptyConfiguration(): void
    {
        $this->assertFalse($this->subject->isConfigurationValid([]));
    }

    #[Test]
    public function isConfigurationValidReturnsFalseWhenTableIsMissing(): void
    {
        $configuration = ['fields' => 'uid,title'];
        $this->assertFalse($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function isConfigurationValidReturnsFalseWhenTableIsNotString(): void
    {
        $configuration = ['table' => 123];
        $this->assertFalse($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function isConfigurationValidReturnsFalseWhenTableIsEmpty(): void
    {
        $this->assertFalse($this->subject->isConfigurationValid(['table' => '']));
    }

    #[Test]
    public function getRecordsThrowsExceptionWhenTableIsEmpty(): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(DataSourceDB::INVALID_CONFIGURATION_EXCEPTION_CODE);
        $this->subject->getRecords(['table' => '', 'where' => 'pid = 1']);
    }

    #[Test]
    public function isConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $configuration = ['table' => 'pages'];
        $this->assertTrue($this->subject->isConfigurationValid($configuration));
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function validConfigurationDataProvider(): array
    {
        return [
            'options before 4.0' => [[
                'table' => 'pages',
                'fields' => 'uid,title,hidden',
                'where' => 'hidden=0',
                'orderBy' => 'title',
            ]],
            'join and group by' => [[
                'table' => 'pages',
                'alias' => 'child',
                'fields' => 'child.pid, COUNT(*) AS children',
                'join' => ['table' => 'pages', 'alias' => 'parent', 'condition' => 'parent.uid = child.pid'],
                'groupBy' => 'child.pid',
                'limit' => '10',
            ]],
            'options of the core' => [[
                'table' => 'pages',
                'fields' => ['uid', 'title'],
                'where' => ['pid' => 1],
                'filter' => ['field' => 'uid', 'op' => '>', 'value' => 1],
                'orderBy' => ['title' => 'DESC'],
                'connection' => 'Legacy',
            ]],
            'cursor field' => [['table' => 'pages', 'fields' => 'uid,title', 'cursorField' => 'uid']],
            'content object, reported when reading' => [['table' => 'pages', 'where' => ['_typoScriptNodeValue' => 'TEXT']]],
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[Test]
    #[DataProvider('validConfigurationDataProvider')]
    public function isConfigurationValidReturnsTrueForValidConfigurations(array $configuration): void
    {
        self::assertTrue($this->subject->isConfigurationValid($configuration));
        self::assertSame([], $this->deprecations);
    }

    /**
     * @return array<string, array{array<string, mixed>}>
     */
    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'SQL and where as map' => [['table' => 'pages', 'groupBy' => 'pid', 'where' => ['pid' => 1]]],
            'SQL and filter' => [['table' => 'pages', 'where' => 'pid = 1', 'filter' => ['field' => 'uid', 'op' => '>', 'value' => 1]]],
            'SQL and cursor field' => [['table' => 'pages', 'where' => 'pid = 1', 'cursorField' => 'uid']],
            'SQL and empty connection' => [['table' => 'pages', 'where' => 'pid = 1', 'connection' => '']],
            'empty connection' => [['table' => 'pages', 'connection' => '']],
            'cursor field and sort order' => [['table' => 'pages', 'orderBy' => 'uid', 'cursorField' => 'uid']],
            'invalid filter' => [['table' => 'pages', 'filter' => 'pid = 1']],
        ];
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function isConfigurationValidReturnsFalseForInvalidConfigurations(array $configuration): void
    {
        self::assertFalse($this->subject->isConfigurationValid($configuration));
    }

    /**
     * @param array<string, mixed> $configuration
     */
    #[Test]
    #[DataProvider('invalidConfigurationDataProvider')]
    public function getRecordsThrowsExceptionForInvalidConfigurations(array $configuration): void
    {
        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(DataSourceDB::INVALID_CONFIGURATION_EXCEPTION_CODE);
        $this->subject->getRecords($configuration);
    }

    #[Test]
    public function getRecordsThrowsExceptionForInvalidConfiguration(): void
    {
        $invalidConfiguration = ['fields' => 'uid,title']; // missing table

        $this->expectException(InvalidConfigurationException::class);
        $this->subject->getRecords($invalidConfiguration);
    }

    #[Test]
    public function getRecordsThrowsExceptionForContentObjectValue(): void
    {
        $configuration = [
            'table' => 'tx_foo',
            'where' => ['_typoScriptNodeValue' => 'TEXT', 'value' => 'pid = 1'],
        ];

        $this->expectException(InvalidConfigurationException::class);
        $this->expectExceptionCode(DataSourceDB::CONTENT_OBJECT_NOT_SUPPORTED_EXCEPTION_CODE);
        $this->expectExceptionMessage('Query value "where"');
        $this->subject->getRecords($configuration);
    }

    #[Test]
    public function getRecordsReturnsArrayOfRecordsWithOptionsBefore40(): void
    {
        $records = $this->subject->getRecords([
            'table' => 'pages',
            'fields' => 'uid, title',
            'orderBy' => 'uid DESC',
            'limit' => '2',
        ]);

        self::assertSame(
            [
                ['uid' => 3, 'title' => 'Default 3'],
                ['uid' => 2, 'title' => 'Default 2'],
            ],
            $records
        );
        self::assertSame([], $this->deprecations);
    }

    #[Test]
    public function getRecordsAcceptsOptionsOfTheCore(): void
    {
        $records = $this->subject->getRecords([
            'table' => 'pages',
            'fields' => ['uid'],
            'where' => ['pid' => 1],
            'orderBy' => ['uid' => 'ASC'],
        ]);

        self::assertSame([['uid' => 2], ['uid' => 3]], $records);
    }

    #[Test]
    public function getRecordsEvaluatesExpressions(): void
    {
        $records = $this->subject->getRecords([
            'table' => 'pages',
            'fields' => 'uid',
            'orderBy' => 'uid',
            'limit' => ['expression' => '1 + 1'],
        ]);

        self::assertSame([['uid' => 1], ['uid' => 2]], $records);
    }

    #[Test]
    public function getRecordsReturnsGeneratorOfTheCoreWithCursorField(): void
    {
        $records = $this->subject->getRecords([
            'table' => 'pages',
            'fields' => 'uid',
            'cursorField' => 'uid',
            'pageSize' => 2,
        ]);

        self::assertInstanceOf(\Generator::class, $records);
        self::assertSame([['uid' => 1], ['uid' => 2], ['uid' => 3]], iterator_to_array($records, false));
        self::assertSame('3', $this->subject->getCursor());
    }

    #[Test]
    public function getRecordsReturnsEmptyArrayForFailingQuery(): void
    {
        self::assertSame([], $this->subject->getRecords(['table' => 'tx_missing_table', 'fields' => 'uid']));
    }

    #[Test]
    public function getRecordsUsesTheConnectionOfTheTableMapping(): void
    {
        $records = $this->subject->getRecords(['table' => 'tx_legacy_record', 'fields' => 'title', 'limit' => 1]);

        self::assertSame([['title' => 'Legacy 1']], $records);
    }

    #[Test]
    public function getRecordsUsesTheConfiguredConnection(): void
    {
        $records = $this->subject->getRecords(['table' => 'pages', 'fields' => 'title', 'limit' => 1, 'connection' => 'Legacy']);

        self::assertSame([['title' => 'Legacy 1']], $records);
    }

    #[Test]
    public function getRecordsUsesConnectionOfTheIdentifierAndWritesDeprecation(): void
    {
        $this->subject->setIdentifier('Legacy');

        $records = $this->subject->getRecords(['table' => 'pages', 'fields' => 'title', 'limit' => 1]);

        self::assertSame([['title' => 'Legacy 1']], $records);
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('"identifier"', $this->deprecations[0]);
    }

    #[Test]
    public function configuredConnectionTakesPrecedenceOverIdentifier(): void
    {
        $this->subject->setIdentifier('Legacy');

        $records = $this->subject->getRecords(['table' => 'pages', 'fields' => 'title', 'limit' => 1, 'connection' => 'default']);

        self::assertSame([['title' => 'Default 1']], $records);
        self::assertCount(1, $this->deprecations);
        self::assertStringContainsString('"identifier" is ignored', $this->deprecations[0]);
    }

    #[Test]
    public function identifierDefaultUsesTheConnectionOfTheTableMapping(): void
    {
        $this->subject->setIdentifier('Default');

        $records = $this->subject->getRecords(['table' => 'tx_legacy_record', 'fields' => 'title', 'limit' => 1]);

        self::assertSame([['title' => 'Legacy 1']], $records);
        self::assertSame([], $this->deprecations);
    }

    #[Test]
    public function getRecordsThrowsExceptionForIdentifierOfMissingConnection(): void
    {
        $this->subject->setIdentifier('external_db');

        $this->expectException(MissingDatabaseException::class);
        $this->expectExceptionCode(DataSourceDB::MISSING_DATABASE_EXCEPTION_CODE);
        $this->subject->getRecords(['table' => 'pages']);
    }

    #[Test]
    public function identifierCanBeSetAndGet(): void
    {
        $identifier = 'test_db';
        $this->subject->setIdentifier($identifier);
        $this->assertSame($identifier, $this->subject->getIdentifier());
    }

    #[Test]
    public function identifierInitiallyReturnsNull(): void
    {
        $this->assertNull($this->subject->getIdentifier());
    }
}
