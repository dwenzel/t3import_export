<?php
namespace CPSIT\T3importExport\Tests\Unit\Persistence;

/***************************************************************
 *  Copyright notice
 *  (c) 2016 Dirk Wenzel <dirk.wenzel@cps-it.de>
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
use CPSIT\T3importExport\Persistence\DataTargetDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class DataTargetDBTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence
 */
class DataTargetDBTest extends UnitTestCase
{
    /**
     * @var DataTargetDB
     */
    protected $subject;

    /**
     * set up subject
     */
    public function setUp()
    {
        $this->subject = $this->getAccessibleMock(
            DataTargetDB::class, ['dummy'], [], '', false
        );
    }

    /**
     * @return \PHPUnit_Framework_MockObject_MockObject
     */
    protected function mockDatabase()
    {
        $mockDatabase = $this->getMock(
            DatabaseConnection::class,
            ['exec_INSERTquery', 'exec_UPDATEquery'],
            [],
            '', false
        );

        $this->inject(
            $this->subject,
            'database',
            $mockDatabase
        );

        return $mockDatabase;
    }

    /**
     * @test
     */
    public function constructorSetsDatabase()
    {
        $GLOBALS['TYPO3_DB'] = $this->getMock(
            DatabaseConnection::class, [], [], '', false
        );

        $this->subject->__construct();
        $this->assertAttributeSame(
            $GLOBALS['TYPO3_DB'],
            'database',
            $this->subject
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseForMissingTable()
    {
        $configuration = [];
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function isConfigurationValidReturnsFalseIfUnsetKeysIsNotString()
    {
        $configuration = [
            'table' => 'foo',
            'unsetKeys' => []
        ];

        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }


    /**
     * @test
     */
    public function isConfigurationValidReturnsTrueForValidConfiguration()
    {
        $configuration = [
            'table' => 'foo',
            'unsetKeys' => 'bar,baz'
        ];

        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    /**
     * @test
     */
    public function persistUnSetsConfiguredKeys()
    {
        $mockDatabase = $this->mockDatabase();

        $tableName = 'baz';
        $keyToUnset = 'foo';
        $configuration = [
            'table' => $tableName,
            'unsetKeys' => $keyToUnset
        ];

        $record = [
            $keyToUnset => 'bar'
        ];
        $expectedRecord = [];
        $mockDatabase->expects($this->once())
            ->method('exec_INSERTquery')
            ->with($tableName, $expectedRecord);

        $this->subject->persist(
            $record,
            $configuration
        );
    }

    /**
     * @test
     */
    public function persistUpdatesRecordsWithIdentityKey()
    {
        $mockDatabase = $this->mockDatabase();

        $tableName = 'baz';
        $configuration = [
            'table' => $tableName,
        ];

        $identity = 'foo';
        $record = [
            '__identity' => $identity,
            'barField' => 'boom'
        ];

        $expectedWhereClause = 'uid = ' . $identity;
        $expectedRecord = [
            'barField' => 'boom'
        ];
        $mockDatabase->expects($this->once())
            ->method('exec_UPDATEquery')
            ->with($tableName, $expectedWhereClause, $expectedRecord);

        $this->subject->persist(
            $record,
            $configuration
        );
    }

    /**
     * @test
     */
    public function getDatabaseReturnsDatabaseFromConnectionService()
    {
        $identifier = 'foo';
        $this->subject->setIdentifier($identifier);
        /** @var DatabaseConnectionService | \PHPUnit_Framework_MockObject_MockObject $mockConnectionService */
        $mockConnectionService = $this->getMock(
            DatabaseConnectionService::class, ['getDatabase'], [], '', false
        );
        $mockDataBase = $this->getMock(
            DatabaseConnection::class);
        $this->subject->injectDatabaseConnectionService($mockConnectionService);
        $mockConnectionService->expects($this->once())
            ->method('getDatabase')
            ->with($identifier)
            ->will($this->returnValue($mockDataBase));

        $this->assertSame(
            $mockDataBase,
            $this->subject->getDatabase()
        );
    }
}
