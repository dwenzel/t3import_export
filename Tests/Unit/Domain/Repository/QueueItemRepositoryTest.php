<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Domain\Repository;

use CPSIT\T3importExport\Domain\Model\QueueItem;
use CPSIT\T3importExport\Domain\Repository\QueueItemRepository;
use CPSIT\T3importExport\Domain\Repository\QueueRepository;
use CPSIT\ImportExportCore\Exception\InvalidArgumentException;
use CPSIT\T3importExport\Service\DatabaseConnectionService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\CMS\Extbase\Persistence\PersistenceManagerInterface;

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
class QueueItemRepositoryTest extends TestCase
{
    protected QueueItemRepository $subject;

    protected ConnectionPool&MockObject $connectionPool;
    protected DatabaseConnectionService&MockObject $connectionService;
    protected Connection&MockObject $connection;
    protected PersistenceManagerInterface&MockObject $persistenceManager;

    protected function mockConnection(): void
    {
        $this->connection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'createQueryBuilder',
                'delete',
                'count',
                'insert',
                'truncate',
                'update',
                'select',
                'quoteIdentifier',
                'quoteIdentifiers',
            ])
            ->getMock();
    }

    protected function mockConnectionPool(): void
    {
        $this->connectionPool = $this->getMockBuilder(ConnectionPool::class)
            ->onlyMethods([
                'getConnectionForTable',
            ])
            ->getMock();
    }

    protected function mockConnectionService(): void
    {
        $this->mockConnection();
        $this->mockConnectionPool();

        $this->connectionService = $this->getMockBuilder(DatabaseConnectionService::class)
            ->disableOriginalConstructor()
            ->onlyMethods([
                'isRegistered',
                'getDatabase',
                'getConnectionPool',
            ])
            ->getMock();
        $this->connectionService->method('getDatabase')->willReturn($this->connection);
        $this->connectionService->method('getConnectionPool')->willReturn($this->connectionPool);
    }

    protected function mockPersistenceManager(): void
    {
        $this->persistenceManager = $this->createMock(PersistenceManagerInterface::class);
        if (method_exists($this, 'injectPersistenceManager')) {
            $this->subject->injectPersistenceManager($this->persistenceManager);
        }
    }

    protected function setUp(): void
    {
        $this->mockConnectionService();

        $this->connectionPool->method('getConnectionForTable')
            ->willReturn($this->connection);
        $this->subject = new QueueItemRepository($this->connectionPool);
    }

    #[Test]
    public function testIsNewReturnsTrueForNonExistingRecord(): void
    {
        $record = [
            QueueItem::FIELD_IDENTIFIER => 'import.foo',
            QueueItem::FIELD_CHECKSUM => 'bar',
        ];

        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with(...[QueueItem::TABLE])
            ->willReturn($this->connection);

        $this->connection->expects($this->once())
            ->method('count')
            ->willReturn(0);

        self::assertTrue(
            $this->subject->isNew($record)
        );
    }

    #[Test]
    public function testIsNewReturnsFalseForExistingRecord(): void
    {
        $record = [
            QueueItem::FIELD_IDENTIFIER => 'import.foo',
            QueueItem::FIELD_CHECKSUM => 'bar',
        ];

        $this->connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with(...[QueueItem::TABLE])
            ->willReturn($this->connection);

        $this->connection->expects($this->once())
            ->method('count')
            ->willReturn(1);

        self::assertFalse(
            $this->subject->isNew($record)
        );
    }

    #[Test]
    public function testAddRecordDelegatesValidRecordToConnection(): void
    {
        $identifier = 'import.foo';
        $data = json_encode(['bar']);
        $checksum = sha1($identifier . $data);

        $required = [
            QueueItem::FIELD_IDENTIFIER => $identifier,
            QueueItem::FIELD_DATA => $data,
            QueueItem::FIELD_CHECKSUM => $checksum,
            QueueItem::FIELD_CREATED => time(),
        ];
        $validRecord = array_merge(
            QueueItemRepository::TEMPLATE_QUEUE_ITEM,
            $required
        );

        $expectedIdentifiers = [
            QueueItem::FIELD_IDENTIFIER => $identifier,
            QueueItem::FIELD_CHECKSUM => $checksum,
        ];

        $this->connection->expects($this->once())
            ->method('count')
            ->with(...['uid', QueueItem::TABLE, $expectedIdentifiers])
            ->willReturn(0);

        $this->connection->expects($this->once())
            ->method('insert')
            ->with(...[QueueItem::TABLE, $validRecord]);

        $this->subject->add($validRecord);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('validRecordDataProvider')]
    public function testUpdateDelegatesValidRecordToConnection(array $validRecord, array $expectedIdentifiers): void
    {
        $this->connection->expects($this->once())
            ->method('count')
            ->with(...['uid', QueueItem::TABLE, $expectedIdentifiers])
            ->willReturn(1);

        $this->connection->expects($this->once())
            ->method('update')
            ->with(...[QueueItem::TABLE, $validRecord]);

        $this->subject->update($validRecord);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('validRecordDataProvider')]
    public function testUpdateThrowsExceptionForNewRecord(array $validRecord, array $expectedIdentifiers): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1_644_911_540);
        $this->connection->expects($this->once())
            ->method('count')
            ->with(...['uid', QueueItem::TABLE, $expectedIdentifiers])
            ->willReturn(0);

        $this->subject->update($validRecord);
    }

    public static function validRecordDataProvider(): array
    {
        $identifier = 'import.foo';

        $data = json_encode(['bar']);
        $checksum = sha1($identifier . $data);
        $required = [
            QueueItem::FIELD_IDENTIFIER => $identifier,
            QueueItem::FIELD_DATA => $data,
            QueueItem::FIELD_CHECKSUM => $checksum,
            QueueItem::FIELD_CREATED => time(),
        ];
        $validRecord = array_merge(
            QueueItemRepository::TEMPLATE_QUEUE_ITEM,
            $required
        );

        $expectedIdentifiers = [
            QueueItem::FIELD_IDENTIFIER => $identifier,
            QueueItem::FIELD_CHECKSUM => $checksum,
        ];

        return [
            'record with checksum and identifier' => [
                $validRecord, $expectedIdentifiers,
            ],
        ];
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('validRecordDataProvider')]
    public function testRemoveThrowsExceptionForNonExistingRecord(array $validRecord, array $expectedIdentifiers): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionCode(1_644_911_540);
        $this->connection->expects($this->once())
            ->method('count')
            ->with(...['uid', QueueItem::TABLE, $expectedIdentifiers])
            ->willReturn(0);

        $this->subject->remove($validRecord);
    }

    /**
     * @throws InvalidArgumentException
     */
    #[DataProvider('validRecordDataProvider')]
    public function testRemoveDelegatesValidRecordToConnection(array $validRecord, array $expectedIdentifiers): void
    {
        $this->connection->expects($this->once())
            ->method('count')
            ->with(...['uid', QueueItem::TABLE, $expectedIdentifiers])
            ->willReturn(1);

        $this->connection->expects($this->once())
            ->method('delete')
            ->with(...[QueueItem::TABLE, $expectedIdentifiers])
            ->willReturn(1);

        self::assertTrue(
            $this->subject->remove($validRecord)
        );
    }
}
