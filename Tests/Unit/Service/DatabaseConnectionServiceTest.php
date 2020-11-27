<?php

namespace CPSIT\T3importExport\Tests\Service;

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

use CPSIT\T3importExport\MissingDatabaseException;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use TYPO3\CMS\Core\Tests\UnitTestCase;

/**
 * Class DatabaseConnectionServiceTest
 *
 * @package CPSIT\T3importExport\Tests\Service
 */
class DatabaseConnectionServiceTest extends TestCase
{
    /**
     * @var DatabaseConnectionService
     */
    protected $subject;

    /**
     * @var ConnectionPool|MockObject
     */
    protected $connectionPool;

    /**
     * set up the subject
     */
    public function setUp()
    {
        $this->connectionPool = $this->getMockBuilder(ConnectionPool::class)
            ->setMethods(['getConnectionByName'])
            ->getMock();
        $this->subject = new DatabaseConnectionService($this->connectionPool);
    }

    /**
     * @test
     */
    public function registerAddsDatabaseConnection()
    {
        $identifier = 'foo';
        $hostName = 'fooBar';
        $databaseName = 'bar';
        $userName = 'baz';
        $password = 'boom';
        $port = 123;

        DatabaseConnectionService::register(
            $identifier,
            $hostName,
            $databaseName,
            $userName,
            $password,
            $port
        );

        $expectedInstance = $this->subject->getDatabase($identifier);
        $this->assertInstanceOf(
            DatabaseConnection::class,
            $expectedInstance
        );
    }

    public function testGetDatabaseThrowsExceptionForMissingDatabase()
    {
        $this->connectionPool->expects(self::atLeastOnce())
            ->method('getConnectionByName')
            ->willThrowException(new \Exception());

        $this->expectException(MissingDatabaseException::class);
        $this->subject->getDatabase('nonExistingIdentifier');
    }

    public function testGetConnectionPoolReturnsInstanceOfConnectionPool(): void
    {
        self::assertInstanceOf(
            ConnectionPool::class,
            DatabaseConnectionService::getConnectionPool()
        );
    }

    public function connectionNamesDataProvider(): array
    {
        return [
            'empty' => [[], 'foo', false],
            'unknown identifier' => [['default'], 'bar', false],
            'known identifier' => [['default'], 'default', true],
        ];
    }

    /**
     * @param array $connectionNames
     * @param string $identifier
     * @param bool $expected
     * @dataProvider connectionNamesDataProvider
     */
    public function testIsRegisteredChecksConnectionNamesFromConnectionPool(
        array $connectionNames,
        string $identifier,
        bool $expected
    ): void
    {
        $this->connectionPool->expects(self::atLeastOnce())
            ->method('getConnectionNames')
            ->willReturn($connectionNames);

        self::assertSame(
            $expected,
            $this->subject::isRegistered($identifier)
        );
    }
}
