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
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseTrait;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\Query\QueryBuilder;

class DeleteFromTableTest extends TestCase
{
    use MockDatabaseTrait;

    protected DeleteFromTable $subject;
    protected QueryBuilder $queryBuilder;

    public function setUp(): void
    {
        $this->mockConnectionService();
        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);
        $this->queryBuilder = $this->getMockBuilder(QueryBuilder::class)
            ->disableOriginalConstructor()
            ->onlyMethods(
                [
                    'delete',
                    'where',
                    'execute'
                ]
            )
            ->getMock();
        $this->subject = new DeleteFromTable($this->connectionPool, $this->connectionService);
    }


    /**
     * @test
     */
    public function processSetsDatabase(): void
    {
        $configuration = [
            'table' => 'foo',
            'fields' => 'bar',
        ];
        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with(...[$configuration['table']])
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
    public function constructorSetsDefaultDatabase(): void
    {
        $connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject->__construct();

        $this->assertSame(
            $connection,
            $this->subject->getDataBase()
        );
    }

    /**
     * @test
     */
    public function processDeletesRecordsFromTable(): void
    {
        $tableName = 'fooTable';
        $where = 'foo=bar';
        $config = [
            'table' => $tableName,
            'where' => $where,
        ];
        $records = [];
        $this->connection->expects($this->atLeastOnce())
            ->method('createQueryBuilder')
            ->willReturn($this->queryBuilder);

        $this->queryBuilder->expects($this->once())
            ->method('delete')
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects(($this->once()))
            ->method('where')
            ->with(...[$config['where']])
            ->willReturn($this->queryBuilder);
        $this->queryBuilder->expects($this->once())
            ->method('execute');

        $this->subject->process($config, $records);
    }

}
