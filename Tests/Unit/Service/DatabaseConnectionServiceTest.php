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
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use TYPO3\CMS\Core\Database\DatabaseConnection;
use PHPUnit\Framework\TestCase;

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
     * set up the subject
     */
    public function setUp(): void
    {
        $this->markTestSkipped('DataBaseConnectionService must be rewritten');
        $this->subject = $this->getAccessibleMock(
            DatabaseConnectionService::class, ['dummy']
        );
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

    /**
     * @test
     * @expectedException \CPSIT\T3importExport\MissingDatabaseException
     * @expectedExceptionCode 1449363030
     */
    public function getDatabaseThrowsExceptionForMissingDatabase()
    {
        $this->subject->getDatabase('nonExistingIdentifier');
    }
}
