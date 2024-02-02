<?php

namespace CPSIT\T3importExport\Tests\Unit\Traits;

use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\MockObject\MockBuilder;
use TYPO3\CMS\Core\Database\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use TYPO3\CMS\Core\Database\ConnectionPool;

/***************************************************************
 *  Copyright notice
 *
 *  (c) 2022 Dirk Wenzel <wenzel@cps-it.de>
 *  All rights reserved
 *
 * The GNU General Public License can be found at
 * http://www.gnu.org/copyleft/gpl.html.
 * A copy is found in the text file GPL.txt and important notices to the license
 * from the author is found in LICENSE.txt distributed with these scripts.
 * This script is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * This copyright notice MUST APPEAR in all copies of the script!
 ***************************************************************/
trait MockDatabaseTrait
{
    /**
     * @var ConnectionPool|MockObject
     */
    protected ConnectionPool $connectionPool;

    /**
     * @var DatabaseConnectionService|MockObject
     */
    protected DatabaseConnectionService $connectionService;
    /**
     * @var Connection|MockObject
     */
    protected Connection $connection;

//    /**
//     * Returns a builder object to create mock objects using a fluent interface.
//     *
//     * @param string|string[] $className
//     */
//    abstract public function getMockBuilder($className): MockBuilder;


    protected function mockConnectionService(): self
    {
        $this->mockConnection();
        $this->mockConnectionPool();

        $this->connectionService = $this->getMockBuilder(DatabaseConnectionService::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'isRegistered',
                    'getDatabase',
                    'getConnectionForTable',
                    'getConnectionPool'
                ])
            ->getMock();
        $this->connectionService->method('getDatabase')->willReturn($this->connection);
        $this->connectionService->method('getConnectionPool')->willReturn($this->connectionPool);

        return $this;
    }

    protected function mockConnection(): self
    {
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(
                [
                    'createQueryBuilder',
                    'delete',
                    'where',
                    'execute',
                    'count',
                    'insert',
                    'truncate',
                    'update',
                    'select',
                    'quoteIdentifier',
                    'quoteIdentifiers'
                ]
            )
            ->getMock();

        return $this;
    }

    protected function mockConnectionPool(): self
    {
        $this->connectionPool = $this->getMockBuilder(ConnectionPool::class)
            ->setMethods([
                'getConnectionForTable',

            ])
            ->getMock();

        return $this;
    }
}
