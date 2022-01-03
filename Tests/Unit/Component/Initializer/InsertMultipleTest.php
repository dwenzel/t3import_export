<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\InsertMultiple;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\AccessibleObjectInterface;
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

    /**
     * @var \CPSIT\T3importExport\Component\Initializer\InsertMultiple|AccessibleObjectInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(InsertMultiple::class,
            ['dummy'], [], '', false);
    }

    /**
     * @test
     * @covers ::injectDatabaseConnectionService
     */
    public function injectDatabaseConnectionServiceForObjectSetsConnectionService()
    {
        /** @var DatabaseConnectionService $expectedConnectionService */
        $expectedConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
            ['dummy'], [], '', false);

        $this->subject->injectDatabaseConnectionService($expectedConnectionService);

        $this->assertSame(
            $expectedConnectionService,
            $this->subject->_get('connectionService')
        );
    }

    /**
     * @test
     */
    public function processSetsDatabase()
    {
        $configuration = [
            'table' => 'foo',
            'fields' => 'bar',
            'rows' => [],
            'identifier' => 'fooDatabase'
        ];
        $mockDatabase = $this->getMock(
            DatabaseConnection::class, ['exec_INSERTmultipleRows'], [], '', false);
        /** @var DatabaseConnectionService $connectionService|\PHPUnit_Framework_MockObject_MockObject */
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfTableIsNotSet()
    {
        $mockConfiguration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfTableIsNotString()
    {
        $mockConfiguration = [
            'table' => 1
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldsIsNotSet()
    {
        $mockConfiguration = [
            'table' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfFieldsIsNotString()
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfRowsIsNotSet()
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfRowsIsNotArray()
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
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfIdentifierIsNotString()
    {
        $mockConfiguration = [
            'table' => 'foo',
            'fields' => 'bar',
            'rows' => [],
            'identifier' => 2
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration()
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

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfDatabaseIsNotRegistered()
    {
        /** @var DatabaseConnectionService $mockConnectionService */
        $mockConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
            [], [], '', false);

        $this->subject->injectDatabaseConnectionService($mockConnectionService);

        $mockConfiguration = [
            'identifier' => 'missingDatabaseIdentifier',
            'table' => 'fooTable',
            'fields' => 'bar',
            'rows' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @test
     */
    public function constructorSetsDefaultDatabase()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(
            DatabaseConnection::class, [], [], '', false
        );
        $this->subject->__construct();

        $this->assertSame(
            $GLOBALS['TYPO3_DB'],
            $this->subject->_get('database')
        );
    }

    /**
     * @test
     */
    public function processInsertsMultipleRecordsIntoTable()
    {
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
        $mockDatabase = $this->getMock(
            DatabaseConnection::class, ['exec_INSERTmultipleRows']
        );
        $mockDatabase->expects($this->once())
            ->method('exec_INSERTmultipleRows')
            ->with($tableName);
        $this->subject->_set('database', $mockDatabase);

        $this->subject->process($config, $records);
    }
}
