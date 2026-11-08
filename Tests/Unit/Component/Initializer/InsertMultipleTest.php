<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\InsertMultiple;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

/***************************************************************
 *  Copyright notice
 *  (c) 2015 Dirk Wenzel <dirk.wenzel@cps-it.de>
 *  All rights reserved
 *  This script is part of the TYPO3 project. The TYPO3 project is
 *  free software; you can redistribute it and/or modify
 *  it under the terms of the GNU General Public License as published by
 *  the Free Software Foundation; either version 3 of the License, or
 *  (at your option) any later version.
 *  The GNU General Public License can be found at
 *  http://www.gnu.org/copyleft/gpl.html.
 *  This script is distributed in the hope that it will be useful,
 *  but WITHOUT ANY WARRANTY; without even the implied warranty of
 *  MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *  This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/

/**
 * Class InsertMultipleTest
 */
#[CoversClass(\CPSIT\T3importExport\Component\Initializer\InsertMultiple::class)]
class InsertMultipleTest extends TestCase
{
    protected InsertMultiple $subject;
    protected Connection&MockObject $connection;
    protected ConnectionPool&MockObject $connectionPool;
    protected DatabaseConnectionService&MockObject $connectionService;

    protected function setUp(): void
    {
        // Create connection mock
        $this->connection = $this->createMock(Connection::class);

        // Create connection pool mock
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);

        // Create connection service mock (deprecated but required by constructor)
        $this->connectionService = $this->createMock(DatabaseConnectionService::class);

        // Create subject with required dependencies
        $this->subject = new InsertMultiple(
            $this->connectionPool,
            $this->connectionService
        );
    }

    #[Test]
    public function testProcessUsesConnectionPool(): void
    {
        $configuration = [
            'table' => 'test_table',
            'fields' => 'field1,field2',
            'rows' => [
                'value1,value2',
                'value3,value4',
            ],
        ];

        $expectedFields = ['field1', 'field2'];
        $expectedValues = [
            ['value1', 'value2'],
            ['value3', 'value4'],
        ];

        // Test that ConnectionPool is used to get connection for the table
        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with('test_table')
            ->willReturn($this->connection);

        // Test that bulkInsert is called with correct parameters
        $this->connection->expects($this->once())
            ->method('bulkInsert')
            ->with('test_table', $expectedValues, $expectedFields)
            ->willReturn(2); // Return number of affected rows

        $records = [];
        $result = $this->subject->process($configuration, $records);

        $this->assertTrue($result);
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfTableIsNotSet(): void
    {
        $mockConfiguration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfTableIsNotString(): void
    {
        $mockConfiguration = [
            'table' => 1,
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfFieldsIsNotSet(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfFieldsIsNotString(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'fields' => 1,
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfRowsIsNotSet(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'fields' => 'bar',
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsFalseIfRowsIsNotArray(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'fields' => 'bar',
            'rows' => 'baz',
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    #[Test]
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $validConfiguration = [
            'table' => 'tableName',
            'fields' => 'foo,bar',
            'rows' => [
                '1' => 'bar,baz',
            ],
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }

    #[Test]
    public function testProcessInsertsMultipleRecordsIntoTable(): void
    {
        $tableName = 'fooTable';
        $fields = 'foo,bar';
        $rows = [
            '10' => 'baz,boom',
            '20' => 'boing,peng',
        ];
        $config = [
            'table' => $tableName,
            'fields' => $fields,
            'rows' => $rows,
        ];
        
        $expectedFields = ['foo', 'bar'];
        $expectedValues = [
            ['baz', 'boom'],
            ['boing', 'peng'],
        ];

        // Test that ConnectionPool gets the correct connection
        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with($tableName)
            ->willReturn($this->connection);

        // Test that bulkInsert is called with correct data
        $this->connection->expects($this->once())
            ->method('bulkInsert')
            ->with($tableName, $expectedValues, $expectedFields)
            ->willReturn(2); // Return number of affected rows

        $records = [];
        $result = $this->subject->process($config, $records);

        $this->assertTrue($result);
    }

    #[Test]
    public function testProcessReturnsFalseWhenBulkInsertThrowsException(): void
    {
        $configuration = [
            'table' => 'test_table',
            'fields' => 'field1,field2',
            'rows' => [
                'value1,value2',
            ],
        ];

        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with('test_table')
            ->willReturn($this->connection);

        // Simulate an exception during bulkInsert
        $this->connection->expects($this->once())
            ->method('bulkInsert')
            ->willThrowException(new \Exception('Database error'));

        $records = [];
        $result = $this->subject->process($configuration, $records);

        $this->assertFalse($result);
    }
}
