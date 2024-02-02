<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\TruncateTables;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use CPSIT\T3importExport\Tests\Unit\Traits\MockDatabaseTrait;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
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
    use MockDatabaseTrait;

    protected TruncateTables $subject;

    protected function setUp(): void
    {
        $this->mockConnectionPool()
            ->mockConnection();
        $this->subject = new TruncateTables($this->connectionPool);
    }

    /**
     * @covers ::isConfigurationValid
     * @dataProvider invalidConfigurationDataProvider
     */
    public function testIsConfigurationValidReturnsFalseForInvalidConfiguration($configuration): void
    {
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    public function invalidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                []
            ],
            'tables value is integer' => [
                [TruncateTables::KEY_TABLES => 3]
            ],
            'tables is float' => [
                [TruncateTables::KEY_TABLES => 1.5]
            ],
            'tables is array' => [
                [TruncateTables::KEY_TABLES => []]
            ],
            'tables is empty string' => [
                [TruncateTables::KEY_TABLES => '']
            ]
        ];
    }

    /**
     * @covers ::isConfigurationValid
     */
    public function testIsConfigurationValidReturnsTrueForValidConfiguration(): void
    {
        $validConfiguration = [
            'tables' => 'tableName',
        ];

        $this->assertTrue(
            $this->subject->isConfigurationValid($validConfiguration)
        );
    }

    public function testProcessTruncatesTables(): void
    {
        $tableName = 'fooTable';
        $config = [
            'tables' => $tableName
        ];
        $records = [];
        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with(...[$tableName])
            ->willReturn($this->connection);

        $this->connection->expects($this->once())
            ->method('truncate')
            ->with(...[$tableName]);
        $this->subject->process($config, $records);
    }
}
