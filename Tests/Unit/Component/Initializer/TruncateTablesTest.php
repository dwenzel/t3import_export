<?php
namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\TruncateTables;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

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
 * Class TruncateTablesTest
 *
 * @package CPSIT\T3importExport\Tests\Service\Initializer
 * @coversDefaultClass \CPSIT\T3importExport\Component\Initializer\TruncateTables
 */
class TruncateTablesTest extends UnitTestCase
{

    /**
     * @var \CPSIT\T3importExport\Component\Initializer\TruncateTables
     */
    protected $subject;

    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(TruncateTables::class,
            ['getQueryConfiguration'], [], '', false);
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
            'identifier' => 'fooDatabase'
        ];
        /** @var DatabaseConnectionService $connectionService */
        $connectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
            ['getDatabase'], [], '', false);
        $connectionService->expects($this->once())
            ->method('getDatabase')
            ->with($configuration['identifier']);
        $record = [];
        $this->subject->injectDatabaseConnectionService($connectionService);

        $this->subject->process($configuration, $record);
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseIfTablesIsNotSet()
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
    public function isConfigurationValidReturnsFalseIfTablesIsNotString()
    {
        $mockConfiguration = [
            'tables' => 1
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
        /** @var DatabaseConnectionService $mockConnectionService */
        $mockConnectionService = $this->getAccessibleMock(DatabaseConnectionService::class,
            [], [], '', false);
        $validDatabaseIdentifier = 'fooDatabaseIdentifier';
        $validConfiguration = [
            'identifier' => $validDatabaseIdentifier,
                'tables' => 'tableName',
        ];
        DatabaseConnectionService::register(
            $validDatabaseIdentifier,
            'hostname',
            'databaseName',
            'userName',
            'password'
        );
        $this->subject->injectDatabaseConnectionService($mockConnectionService);

        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }

    /**
     * @test
     * @covers ::isConfigurationValid
     */
    public function isConfigurationValidReturnsFalseForInvalidIdentifier()
    {
        $mockConfiguration = [
            'identifier' => [],
            'tables' => 'fooTable'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
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
            'tables' => 'fooTable'
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
    public function processTruncatesTables()
    {
        $tableName = 'fooTable';
        $config = [
            'tables' => $tableName
        ];
        $records = [];
        $mockDatabase = $this->getMock(
            DatabaseConnection::class, ['exec_TRUNCATEquery']
        );
        $mockDatabase->expects($this->once())
            ->method('exec_TRUNCATEquery')
            ->with($tableName);
        $this->subject->_set('database', $mockDatabase);

        $this->subject->process($config, $records);
    }
}
