<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

/**
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use CPSIT\T3importExport\Component\Initializer\DeleteFromTable;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class DeleteFromTableTest extends TestCase
{
    protected DeleteFromTable $subject;
    protected QueryBuilder $queryBuilder;
    protected ConnectionPool&MockObject $connectionPool;
    protected DatabaseConnectionService&MockObject $connectionService;
    protected Connection&MockObject $connection;

    protected function setUp(): void
    {
        // Create connection mock
        $this->connection = $this->createMock(Connection::class);

        // Set the global TYPO3_DB variable
        $GLOBALS['TYPO3_DB'] = $this->connection;

        // Create connection pool mock
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);

        // Create connection service mock
        $this->connectionService = $this->createMock(DatabaseConnectionService::class);
        $this->connectionService->method('getDatabase')->willReturn($this->connection);
        // Note: getConnectionPool is a protected method, so we don't need to mock it

        // Create query builder mock
        $this->queryBuilder = $this->createMock(QueryBuilder::class);

        $this->subject = new DeleteFromTable($this->connectionPool, $this->connectionService);
    }

    protected function tearDown(): void
    {
        // Clean up global variable
        unset($GLOBALS['TYPO3_DB']);
        parent::tearDown();
    }

    public function testProcessSetsDatabase(): void
    {
        $configuration = [
            'table' => 'foo',
            'fields' => 'bar',
            'where' => 'id=1',
        ];
        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with($configuration['table'])
            ->willReturn($this->connection);

        $record = [];
        $this->subject->process($configuration, $record);
    }

    public function testIsConfigurationValidReturnsFalseIfTableIsNotSet(): void
    {
        $mockConfiguration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfTableIsNotString(): void
    {
        $mockConfiguration = [
            'table' => 1,
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfWhereIsNotSet(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfWhereIsNotString(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'where' => [],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $validConfiguration = [
            'table' => 'tableName',
            'where' => 'foo,bar',
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }

    public function testConstructorSetsDefaultDatabase(): void
    {
        // First unset the one created in setUp
        unset($GLOBALS['TYPO3_DB']);

        // Create a custom connection for this test
        $connection = $this->createMock(Connection::class);
        $GLOBALS['TYPO3_DB'] = $connection;

        // Create a new instance without mocks to test the constructor behavior
        $emptyConnectionPool = $this->createMock(ConnectionPool::class);
        $emptyConnectionService = $this->createMock(DatabaseConnectionService::class);
        $localSubject = new DeleteFromTable($emptyConnectionPool, $emptyConnectionService);

        $this->assertSame(
            $connection,
            $localSubject->getDataBase()
        );

        // Note: tearDown will clean up $GLOBALS['TYPO3_DB']
    }

    public function testProcessDeletesRecordsFromTable(): void
    {
        $tableName = 'fooTable';
        $where = 'foo=bar';
        $config = [
            'table' => $tableName,
            'where' => $where,
        ];
        $records = [];

        $this->connection->expects($this->once())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('delete')
            ->with($tableName)
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('where')
            ->with($config['where'])
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('executeStatement');

        $this->subject->process($config, $records);
    }
}
