<?php

namespace CPSIT\T3importExport\Tests\Unit\Component\Initializer;

use CPSIT\T3importExport\Component\Initializer\UpdateTable;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
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
class UpdateTableTest extends TestCase
{
    protected UpdateTable $subject;
    protected ConnectionPool&MockObject $connectionPool;
    protected Connection&MockObject $connection;
    protected DatabaseConnectionService&MockObject $connectionService;

    protected function setUp(): void
    {
        // Create connection mock
        $this->connection = $this->createMock(Connection::class);

        // Create connection pool mock
        $this->connectionPool = $this->createMock(ConnectionPool::class);
        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);

        // Create connection service mock
        $this->connectionService = $this->createMock(DatabaseConnectionService::class);
        $this->connectionService->method('getDatabase')->willReturn($this->connection);

        $this->subject = new UpdateTable($this->connectionPool, $this->connectionService);
    }

    #[DataProvider('validConfigurationDataProvider')]
    public function testIsConfigurationValidReturnsTrueForValidConfiguration($configuration): void
    {
        $this->assertTrue(
            $this->subject->isConfigurationValid($configuration)
        );
    }

    #[DataProvider('invalidConfigurationDataProvider')]
    public function testIsConfigurationValidReturnsFalseForInvalidConfiguration($configuration): void
    {
        $this->assertFalse(
            $this->subject->isConfigurationValid($configuration)
        );
    }


    public static function invalidConfigurationDataProvider(): array
    {
        return [
            'empty configuration' => [
                []
            ],
            'table value is integer' => [
                [
                    UpdateTable::KEY_TABLE => 3,
                    UpdateTable::KEY_WHERE => [],
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'table is float' => [
                [
                    UpdateTable::KEY_TABLE => 1.5,
                    UpdateTable::KEY_WHERE => [],
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'table is array' => [
                [
                    UpdateTable::KEY_TABLE => [],
                    UpdateTable::KEY_WHERE => [],
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'table is empty string' => [
                [
                    UpdateTable::KEY_TABLE => '',
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'where is integer' => [
                [
                    UpdateTable::KEY_TABLE => 'foo',
                    UpdateTable::KEY_WHERE => 1,
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
            'where is float' => [
                [
                    UpdateTable::KEY_TABLE => 'foo',
                    UpdateTable::KEY_WHERE => 1.7,
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ],
        ];
    }

    public static function validConfigurationDataProvider(): array
    {
        return [
            'minimal' => [
                [
                    UpdateTable::KEY_TABLE => 'foo',
                    UpdateTable::KEY_SET_FIELDS => ['baz' => 5]
                ]
            ]
        ];
    }
}
