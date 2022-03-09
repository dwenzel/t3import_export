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
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseTrait;
use PHPUnit\Framework\TestCase;

/**
 * Class DataTargetDBTest
 *
 * @package CPSIT\T3importExport\Tests\Unit\Persistence
 */
class DataTargetDBTest extends TestCase
{
    use MockDatabaseTrait;

    /**
     * @var DataTargetDB
     */
    protected $subject;

    /**
     * set up subject
     */
    public function setUp()
    {
        $this->mockConnectionService();
        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);

        $this->subject = new DataTargetDB($this->connectionPool, $this->connectionService);
    }

    public function invalidConfigurationDataProvider(): array
    {
        return [
            'missing field table' => [
                []
            ]
        ];
    }

    /**
     * @param array $invalidConfig
     * @dataProvider invalidConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsFalseForInvalidConfig(array $invalidConfig): void
    {
        self::assertFalse(
            $this->subject->isConfigurationValid($invalidConfig)
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
            DataTargetDB::FIELD_TABLE => 'foo',
            DataTargetDB::FIELD_UNSET_KEYS => []
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
            DataTargetDB::FIELD_TABLE => 'foo',
            DataTargetDB::FIELD_UNSET_KEYS => 'bar,baz'
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

        $tableName = 'baz';
        $keyToUnset = 'foo';
        $configuration = [
            DataTargetDB::FIELD_TABLE => $tableName,
            DataTargetDB::FIELD_UNSET_KEYS => $keyToUnset
        ];

        $record = [
            $keyToUnset => 'bar'
        ];
        $expectedRecord = [];
        $this->connection->expects($this->once())
            ->method('insert')
            ->with(
                ...[
                    $tableName,
                    $expectedRecord
                ]
            );

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
        $tableName = 'baz';
        $configuration = [
            DataTargetDB::FIELD_TABLE => $tableName,
        ];

        $identity = 'foo';
        $record = [
            DataTargetDB::DEFAULT_IDENTITY_FIELD => $identity,
            'barField' => 'boom'
        ];

        $expectedIdentifiers = ['uid' => $identity];
        $expectedRecord = [
            'barField' => 'boom'
        ];
        $this->connection->expects($this->once())
            ->method('update')
            ->with(
                ...[
                $tableName,
                $expectedRecord,
                $expectedIdentifiers
            ]);

        $this->subject->persist(
            $record,
            $configuration
        );
    }
}
