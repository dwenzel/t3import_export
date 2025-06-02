<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Persistence;

use CPSIT\ImportExportCore\Exception\InvalidConfigurationException;
use CPSIT\ImportExportCore\Exception\MissingDatabaseException;
use CPSIT\T3importExport\Persistence\DataSourceDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/**
 * Test for DataSourceDB
 */
#[CoversClass(DataSourceDB::class)]
class DataSourceDBTest extends TestCase
{
    protected DataSourceDB $subject;
    protected DatabaseConnectionService|MockObject $connectionService;
    protected ConnectionPool|MockObject $connectionPool;
    protected Connection|MockObject $connection;

    protected function setUp(): void
    {
        $this->connectionService = $this->createMock(DatabaseConnectionService::class);
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->connection = $this->createMock(Connection::class);

        $this->subject = new DataSourceDB($this->connectionPool, $this->connectionService);
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
    public function isConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $configuration = ['table' => 'pages'];
        $this->assertTrue($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function isConfigurationValidReturnsTrueForComplexValidConfiguration(): void
    {
        $configuration = [
            'table' => 'pages',
            'fields' => 'uid,title,hidden',
            'where' => 'hidden=0',
            'orderBy' => 'title'
        ];
        $this->assertTrue($this->subject->isConfigurationValid($configuration));
    }

    #[Test]
    public function getDatabaseReturnsConnection(): void
    {
        $this->connectionService->expects($this->once())
            ->method('getDatabase')
            ->with(null)
            ->willReturn($this->connection);

        $result = $this->subject->getDatabase();
        $this->assertSame($this->connection, $result);
    }

    #[Test]
    public function getDatabaseUsesIdentifierWhenSet(): void
    {
        $identifier = 'external_db';
        $this->subject->setIdentifier($identifier);

        $this->connectionService->expects($this->once())
            ->method('getDatabase')
            ->with($identifier)
            ->willReturn($this->connection);

        $result = $this->subject->getDatabase();
        $this->assertSame($this->connection, $result);
    }

    #[Test]
    public function getDatabaseThrowsExceptionWhenConnectionServiceFails(): void
    {
        $this->connectionService->expects($this->once())
            ->method('getDatabase')
            ->willThrowException(new MissingDatabaseException('Database not found'));

        $this->expectException(MissingDatabaseException::class);
        $this->subject->getDatabase();
    }

    #[Test]
    public function getRecordsThrowsExceptionForInvalidConfiguration(): void
    {
        $invalidConfiguration = ['fields' => 'uid,title']; // missing table

        $this->expectException(InvalidConfigurationException::class);
        $this->subject->getRecords($invalidConfiguration);
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
