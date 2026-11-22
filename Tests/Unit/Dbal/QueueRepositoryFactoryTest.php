<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Unit\Dbal;

use CPSIT\ImportExportCore\Dbal\Queue\DbalQueueRepository;
use CPSIT\T3importExport\Dbal\QueueRepositoryFactory;
use CPSIT\T3importExport\Dbal\Typo3ConnectionProvider;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use TYPO3\CMS\Core\Database\Connection;
use TYPO3\CMS\Core\Database\ConnectionPool;

#[CoversClass(QueueRepositoryFactory::class)]
class QueueRepositoryFactoryTest extends TestCase
{
    #[Test]
    public function tableIsTheQueueTableOfTheExtension(): void
    {
        self::assertSame('tx_t3importexport_queue_item', QueueRepositoryFactory::TABLE);
    }

    #[Test]
    public function createReturnsRepositoryOnTheConnectionOfTheQueueTable(): void
    {
        $connectionPool = $this->createMock(ConnectionPool::class);
        $connectionPool->expects($this->once())
            ->method('getConnectionForTable')
            ->with(QueueRepositoryFactory::TABLE)
            ->willReturn($this->createStub(Connection::class));
        $subject = new QueueRepositoryFactory(new Typo3ConnectionProvider($connectionPool));

        $repository = $subject->create();

        self::assertInstanceOf(DbalQueueRepository::class, $repository);
    }

    #[Test]
    public function createdRepositoryUsesTheQueueTable(): void
    {
        $connectionPool = $this->createStub(ConnectionPool::class);
        $connectionPool->method('getConnectionForTable')->willReturn($this->createStub(Connection::class));
        $subject = new QueueRepositoryFactory(new Typo3ConnectionProvider($connectionPool));

        $tables = $subject->create()->getTables();

        self::assertCount(1, $tables);
        self::assertSame(QueueRepositoryFactory::TABLE, $tables[0]->getObjectName()->toString());
    }
}
