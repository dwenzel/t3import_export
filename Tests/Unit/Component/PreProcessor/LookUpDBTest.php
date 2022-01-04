<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\LookUpDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseConnectionServiceTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;

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
 * Class LookUpDBTest
 *
 * @package CPSIT\T3importExport\Tests\Service\PreProcessor
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\LookUpDB
 */
class LookUpDBTest extends TestCase
{
    use MockDatabaseConnectionServiceTrait;

    /**
     * @var LookUpDB|MockObject
     */
    protected LookUpDB $subject;

    /**
     * @var array
     */
    protected array $queryResult = [];

    /** @noinspection ReturnTypeCanBeDeclaredInspection */
    public function setUp()
    {
        /**
         * fixme: we mock the subject in order to prevent access to method performQuery
         * which uses now invalid database methods
         */

        $this->subject = $this->getMockBuilder(LookUpDB::class)
            ->setMethods(['performQuery'])
            ->getMock();
        $this->subject->method('performQuery')
            ->willReturn($this->queryResult);
        $this->mockDatabaseConnectionService();
    }

    public function testProcessSetsDatabase(): void
    {
        $configuration = [
            'identifier' => 'fooDatabase',
            'select' => []
        ];
        $this->connectionService->expects($this->once())
            ->method('getDatabase')
            ->with(...[$configuration['identifier']]);
        $record = [];

        $this->subject->process($configuration, $record);
    }

    public function testProcessGetsQueryConfiguration(): void
    {
        $configuration = [
            'select' => [
                'table' => 'fooTable'
            ],
            'targetField' => 'bar'
        ];
        $expectedQueryConfiguration = [
            'fields' => '*',
            'where' => '',
            'groupBy' => '',
            'orderBy' => '',
            'limit' => '',
            'table' => 'fooTable'
        ];
        $record = [];

        $this->subject->expects($this->once())
            ->method('performQuery')
            ->with($expectedQueryConfiguration);


        $this->subject->process($configuration, $record);
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTargetFieldIsNotSet(): void
    {
        $mockConfiguration = ['foo'];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTargetFieldIsNotString(): void
    {
        $mockConfiguration = [
            'targetField' => 1,
            'fields' => []
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfTableIsNotSet(): void
    {
        $mockConfiguration = [
            'select' => []
        ];
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
            'select' => [
                'table' => 1
            ]
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfSourceIsNotSet(): void
    {
        $mockConfiguration = [
            'targetField' => 'foo'
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsFalseIfSourceIsNotArray(): void
    {
        $mockConfiguration = [
            'targetField' => 'foo',
            'source' => 'invalidStringValue'
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
            'select' => [
                'table' => 'tableName'
            ],
            'targetField' => 'bar'
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
    public function tesIsConfigurationValidReturnsFalseForInvalidIdentifier(): void
    {
        $mockConfiguration = [
            'identifier' => [],
            'select' => [
                'table' => 'fooTable'
            ],
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
            'select' => [
                'table' => 'fooTable'
            ],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }

    public function testConstructorSetsDefaultDatabase(): void
    {
        $GLOBALS['TYPO3_DB'] = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->subject->__construct();

        $this->assertAttributeSame(
            $GLOBALS['TYPO3_DB'],
            'database',
            $this->subject
        );
    }
}
