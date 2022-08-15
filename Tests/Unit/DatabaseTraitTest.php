<?php

namespace CPSIT\T3importExport\Tests\Unit;

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

use CPSIT\T3importExport\DatabaseTrait;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Core\Database\DatabaseConnection;

/**
 * Class DatabaseTraitTest
 *
 * @package CPSIT\T3importExport\Tests\Unit
 */
class DatabaseTraitTest extends TestCase
{
    /**
     * @var DatabaseTrait
     */
    protected $subject;

    /**
     * @var Connection|MockObject
     */
    protected Connection $connection;

    protected DatabaseConnectionService $connectionService;

    /**
     * @var ConnectionPool|MockObject
     */
    protected ConnectionPool $connectionPool;

    protected $backupGlobals = true;

    /**
     * set up subject
     */
    public function setUp(): void
    {
        $this->subject = $this->getObjectForTrait(DatabaseTrait::class);
        $this->connectionPool = $this->getMockBuilder(ConnectionPool::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->connectionService = $this->getMockBuilder(DatabaseConnectionService::class)
            ->getMock();
    }

    public function testConstructorGetsDatabaseConnectionFromGlobals(): void
    {
        $GLOBALS['TYPO3_DB'] = $this->connection;

        $this->subject->__construct();
        $this->assertSame(
            $this->connection,
            $this->subject->getDataBase()
        );
    }
    public function testConstructorSetsConnectionService(): void
    {
        $this->subject->__construct(null, $this->connectionService);
        $this->assertSame(
            $this->connectionService,
            $this->subject->getDatabaseConnectionService()
        );
    }
}
