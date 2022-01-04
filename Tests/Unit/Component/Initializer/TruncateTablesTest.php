<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\TruncateTables;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseConnectionServiceTrait;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\DatabaseConnection;

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
class TruncateTablesTest extends TestCase
{
    use MockDatabaseConnectionServiceTrait;

    protected TruncateTables $subject;

    public function setUp()
    {
        $this->subject = new TruncateTables();
        $this->mockDatabaseConnectionService();
        $this->mockConnection();
    }

    /**
     */
    public function processSetsDatabase(): void
    {
        $this->markTestIncomplete('Class depends on DataBaseConnectionService, restore test after rewrite of this class');
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
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTablesIsNotSet(): void
    {
        $mockConfiguration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTablesIsNotString(): void
    {
        $mockConfiguration = [
            'tables' => 1
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
        $this->markTestIncomplete('Class depends on DataBaseConnectionService, restore test after rewrite of this class');

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

        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseForInvalidIdentifier(): void
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
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfDatabaseIsNotRegistered(): void
    {
        $mockConfiguration = [
            'identifier' => 'missingDatabaseIdentifier',
            'tables' => 'fooTable'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testConstructorSetsDefaultDatabase(): void
    {
        $GLOBALS['TYPO3_DB'] = $this->connection;
        $this->subject->__construct();

        self::assertAttributeSame(
            $GLOBALS['TYPO3_DB'],
            'database',
            $this->subject
        );
    }

    public function testProcessTruncatesTables(): void
    {
        $this->markTestIncomplete('Class depends on DataBaseConnectionService, restore test after rewrite of this class');

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
