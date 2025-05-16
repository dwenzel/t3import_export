<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Component\PreProcessor;

use CPSIT\T3importExport\Component\PreProcessor\LookUpDB;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

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
 * @coversDefaultClass \CPSIT\T3importExport\Component\PreProcessor\LookUpDB
 */
class LookUpDBTest extends TestCase
{
    /**
     * @var LookUpDB|MockObject
     */
    protected LookUpDB $subject;

    /**
     * @var ConnectionPool&MockObject
     */
    protected ConnectionPool $connectionPool;

    /**
     * @var DatabaseConnectionService&MockObject
     */
    protected DatabaseConnectionService $connectionService;

    /**
     * @var Connection&MockObject
     */
    protected Connection $connection;

    protected array $queryResult = [];

    /**
     * @noinspection ReturnTypeCanBeDeclaredInspection
     */
    protected function setUp(): void
    {
        /**
         * fixme: we mock the subject in order to prevent access to method performQuery
         * which uses now invalid database methods
         */
        // Create connection mock
        $this->connection = $this->createMock(Connection::class);

        // Create connection pool mock
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);

        // Create connection service mock
        $this->connectionService = $this->createMock(DatabaseConnectionService::class);
        $this->connectionService->method('getDatabase')->willReturn($this->connection);

        $this->subject = new LookUpDB(
            $this->connectionPool,
            $this->connectionService
        );
    }

    public function testProcess(): void
    {
        /**
         * @see LookUpDB::process() for details
         */
        $this->markTestIncomplete('method process is still to complex to test it. ');
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
            'fields' => [],
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
            'select' => [],
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
                'table' => 1,
            ],
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
            'targetField' => 'foo',
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
            'source' => 'invalidStringValue',
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
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
                'table' => 'fooTable',
            ],
        ];
        $this->assertFalse(
            $this->subject->isConfigurationValid($mockConfiguration)
        );
    }
}
