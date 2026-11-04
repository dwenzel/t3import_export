<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\InsertMultiple;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseTrait;
use PHPUnit\Framework\TestCase;

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
 *
 * @package CPSIT\T3importExport\Tests\Service\Initializer
 * @coversDefaultClass \CPSIT\T3importExport\Component\Initializer\InsertMultiple
 */
class InsertMultipleTest extends TestCase
{

    use MockDatabaseTrait;

    protected InsertMultiple $subject;

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp(): void
    {
        $this->subject = new InsertMultiple();
        $this->mockConnectionService();
        $this->mockConnection();
    }

    /**
     */
    public function testProcessSetsDatabase(): void
    {
        $this->markTestIncomplete('Class depends on DataBaseConnectionService, restore test after rewrite of this class');

        $configuration = [
            'table' => 'foo',
            'fields' => 'bar',
            'rows' => [],
            'identifier' => 'fooDatabase'
        ];
        $mockDatabase = $this->getMock(
            DatabaseConnection::class, ['exec_INSERTmultipleRows'], [], '', false);
        /** @var DatabaseConnectionService $connectionService |\PHPUnit_Framework_MockObject_MockObject */
        $connectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
            ['getDatabase'], [], '', false);
        $connectionService->expects($this->once())
            ->method('getDatabase')
            ->with($configuration['identifier'])
            ->will($this->returnValue($mockDatabase));

        $record = [];
        $this->subject->injectDatabaseConnectionService($connectionService);

        $this->subject->process($configuration, $record);
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTableIsNotSet(): void
    {
        $mockConfiguration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTableIsNotString(): void
    {
        $mockConfiguration = [
            'table' => 1
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldsIsNotSet(): void
    {
        $mockConfiguration = [
            'table' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfFieldsIsNotString(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'fields' => 1
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfRowsIsNotSet(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'fields' => 'bar'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfRowsIsNotArray(): void
    {
        $mockConfiguration = [
            'table' => 'foo',
            'fields' => 'bar',
            'rows' => 'baz'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $validConfiguration = [
            'table' => 'tableName',
            'fields' => 'foo,bar',
            'rows' => [
                '1' => 'bar,baz'
            ]
        ];
        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }

    public function testProcessInsertsMultipleRecordsIntoTable(): void
    {
        $this->markTestIncomplete('Class depends on DataBaseConnectionService, restore test after rewrite of this class');

        $tableName = 'fooTable';
        $fields = 'foo,bar';
        $rows = [
            '10' => 'baz,boom',
            '20' => 'boing,peng'
        ];
        $config = [
            'table' => $tableName,
            'fields' => $fields,
            'rows' => $rows
        ];
        $records = [];

        $this->subject->process($config, $records);
    }
}
