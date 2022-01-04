<?php

namespace CPSIT\T3importExport\Tests\Unit\Traits;

use CPSIT\T3importExport\Service\DatabaseConnectionService;
use CPSIT\T3importExport\Tests\Unit\Component\Initializer\DeleteFromTableTest;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;

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
trait MockDatabaseConnectionServiceTrait
{

    /**
     * @var DatabaseConnectionService|MockObject
     */
    protected DatabaseConnectionService $connectionService;
    /**
     * @var Connection|MockObject
     */
    protected $connection;

    protected function mockDatabaseConnectionService(): void
    {
        $this->connectionService = $this->getMockBuilder(DatabaseConnectionService::class)
            ->disableOriginalConstructor()
            ->setMethods(['isRegistered', 'getDatabase'])
            ->getMock();
        $this->subject->injectDatabaseConnectionService($this->connectionService);
    }

    protected function mockConnection(): void
    {
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->getMock();
    }
}
