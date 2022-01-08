<?php

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
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseConnectionServiceTrait;
use CPSIT\T3importExport\Tests\Unit\Traits\MockObjectManagerTrait;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;

class DeleteFromTableTest extends TestCase
{
    use MockDatabaseConnectionServiceTrait,
        MockObjectManagerTrait;

    protected DeleteFromTable $subject;

    protected ConnectionPool $connectionPool;

    public function setUp()
    {
        $this->mockDatabaseConnectionService();
        $this->mockConnection();
        $this->connectionPool = $this->getMockBuilder(ConnectionPool::class)
            ->getMock();
        $this->subject = new DeleteFromTable($this->connectionPool, $this->connectionService);
    }


    /**
     * @test
     */
    public function processSetsDatabase(): void
    {
        $this->markTestIncomplete('Class depends on DataBaseConnectionService, restore test after rewrite of this class');
        $configuration = [
            'table' => 'foo',
            'fields' => 'bar',
            'rows' => [],
            'identifier' => 'fooDatabase'
        ];
        $this->connectionService->expects($this->once())
            ->method('getDatabase')
            ->with($configuration['identifier'])
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
            'table' => 1
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfWhereIsNotSet(): void
    {
        $mockConfiguration = [
            'table' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfWhereIsNotString(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'where' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testIsConfigurationValidReturnsFalseIfIdentifierIsNotString(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'where' => 'bar',
            'identifier' => 2
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $validConfiguration = [
            'table' => 'tableName',
            'where' => 'foo,bar',
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfDatabaseIsNotRegistered(): void
    {
        $mockConfiguration = [
            'identifier' => 'missingDatabaseIdentifier',
            'table' => 'fooTable',
            'where' => 'bar',
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     */
    public function constructorSetsDefaultDatabase(): void
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $GLOBALS['TYPO3_DB'] = $connection;
        $this->subject->__construct();

        $this->assertSame(
            $GLOBALS['TYPO3_DB'],
            $this->subject->getDataBase()
        );
    }

    /**
     * @test
     */
    public function processDeletesRecordsFromTable(): void
    {
        $this->markTestSkipped('Class depends on DataBaseConnectionService, restore test after rewrite of this class');

        $tableName = 'fooTable';
        $where = 'foo=bar';
        $config = [
            'table' => $tableName,
            'where' => $where,
        ];
        $records = [];
        $mockDatabase = $this->getMock(
            DatabaseConnection::class, ['exec_DELETEquery']
        );
        $mockDatabase->expects($this->once())
            ->method('exec_DELETEquery')
            ->with($tableName);
        $this->subject->_set('database', $mockDatabase);

        $this->subject->process($config, $records);
    }

}
