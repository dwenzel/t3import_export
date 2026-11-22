<?php

declare(strict_types=1);

namespace CPSIT\T3importExport\Tests\Functional\Dbal;

use CPSIT\ImportExportCore\Dbal\Queue\DbalQueueRepository;
use CPSIT\ImportExportCore\Queue\QueueItem;
use CPSIT\ImportExportCore\Queue\QueueItemStatus;
use CPSIT\ImportExportCore\Queue\QueueRepositoryInterface;
use CPSIT\T3importExport\Dbal\QueueRepositoryFactory;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\Test;
use TYPO3\CMS\Core\Database\ConnectionPool;
use TYPO3\TestingFramework\Core\Functional\FunctionalTestCase;

/**
 * The queue repository of the core on the queue table of the extension.
 */
#[CoversClass(QueueRepositoryFactory::class)]
class CoreQueueRepositoryTest extends FunctionalTestCase
{
    // typo3/cms-install is required by t3extension_tools
    protected array $coreExtensionsToLoad = ['install'];

    protected array $testExtensionsToLoad = [
        't3extension_tools',
        't3import_export',
    ];

    private const string QUEUE = 'import.events';

    private const string SECOND_CONNECTION = 'Second';

    #[\Override]
    protected function tearDown(): void
    {
        if (isset($GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION])) {
            $this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION)->close();
            unset(
                $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION],
                $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][QueueRepositoryFactory::TABLE]
            );
        }
        parent::tearDown();
    }

    private function createRepository(): DbalQueueRepository
    {
        return $this->get(QueueRepositoryFactory::class)->create();
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function fetchRows(): array
    {
        return $this->get(ConnectionPool::class)
            ->getConnectionByName(ConnectionPool::DEFAULT_CONNECTION_NAME)
            ->select(['queue', 'checksum', 'data', 'status', 'claim_token', 'created_at'], QueueRepositoryFactory::TABLE, [], [], ['id' => 'ASC'])
            ->fetchAllAssociative();
    }

    /**
     * @param list<QueueItem> $items
     * @return list<array<mixed>>
     */
    private static function data(array $items): array
    {
        return array_map(static fn (QueueItem $item): array => $item->data, $items);
    }

    #[Test]
    public function queueRepositoryOfTheContainerIsBuiltByTheFactory(): void
    {
        $repository = $this->get(QueueRepositoryInterface::class);

        self::assertInstanceOf(DbalQueueRepository::class, $repository);
        self::assertSame($repository, $this->get(DbalQueueRepository::class));
        self::assertSame(QueueRepositoryFactory::TABLE, $repository->getTables()[0]->getObjectName()->toString());
    }

    #[Test]
    public function enqueueStoresRecordInQueueTableOnce(): void
    {
        $repository = $this->createRepository();
        $record = ['id' => 7, 'title' => 'Concert'];

        self::assertTrue($repository->enqueue(self::QUEUE, $record));
        self::assertFalse($repository->enqueue(self::QUEUE, $record));

        $rows = $this->fetchRows();
        self::assertCount(1, $rows);
        self::assertSame(self::QUEUE, $rows[0]['queue']);
        self::assertSame(QueueItem::checksum(self::QUEUE, $record), $rows[0]['checksum']);
        self::assertSame($record, json_decode((string)$rows[0]['data'], true));
        self::assertSame(QueueItemStatus::New->value, (int)$rows[0]['status']);
        self::assertNotEmpty($rows[0]['created_at']);
        self::assertSame(1, $repository->count(self::QUEUE, QueueItemStatus::New));
    }

    #[Test]
    public function equalRecordsInDifferentQueuesAreSeparateItems(): void
    {
        $repository = $this->createRepository();

        self::assertTrue($repository->enqueue(self::QUEUE, ['id' => 1]));
        self::assertTrue($repository->enqueue('other', ['id' => 1]));

        self::assertSame(1, $repository->count(self::QUEUE, QueueItemStatus::New));
        self::assertSame(1, $repository->count('other', QueueItemStatus::New));
    }

    #[Test]
    public function claimReturnsNewItemsInOrderAndMarksThemAsProcessing(): void
    {
        $repository = $this->createRepository();
        $before = time();
        foreach ([1, 2, 3] as $id) {
            $repository->enqueue(self::QUEUE, ['id' => $id]);
        }

        $claimed = $repository->claim(self::QUEUE, 2);

        self::assertSame([['id' => 1], ['id' => 2]], self::data($claimed));
        foreach ($claimed as $item) {
            self::assertSame(QueueItemStatus::Processing, $item->status);
            self::assertSame(self::QUEUE, $item->queue);
        }
        // TYPO3 registers a mutable type for `datetime_immutable`; core 0.6.1
        // keeps the timestamps (core#106)
        self::assertGreaterThanOrEqual($before, $claimed[0]->createdAt->getTimestamp());
        self::assertLessThanOrEqual(time(), $claimed[0]->createdAt->getTimestamp());
        self::assertGreaterThanOrEqual($before, $claimed[0]->updatedAt->getTimestamp());
        self::assertSame(2, $repository->count(self::QUEUE, QueueItemStatus::Processing));
        self::assertSame(1, $repository->count(self::QUEUE, QueueItemStatus::New));
        self::assertSame([['id' => 3]], self::data($repository->claim(self::QUEUE, 2)));
        self::assertSame([], $repository->claim(self::QUEUE, 2));
    }

    #[Test]
    public function completeAndFailSetTheStatusOfClaimedItems(): void
    {
        $repository = $this->createRepository();
        $repository->enqueue(self::QUEUE, ['id' => 1]);
        $repository->enqueue(self::QUEUE, ['id' => 2]);
        [$first, $second] = $repository->claim(self::QUEUE, 2);

        $repository->complete($first);
        $repository->fail($second);

        self::assertSame(1, $repository->count(self::QUEUE, QueueItemStatus::Finished));
        self::assertSame(1, $repository->count(self::QUEUE, QueueItemStatus::Failed));
        self::assertSame(0, $repository->count(self::QUEUE, QueueItemStatus::Processing));
        self::assertSame(
            [QueueItemStatus::Finished->value, QueueItemStatus::Failed->value],
            array_map(static fn (array $row): int => (int)$row['status'], $this->fetchRows())
        );
    }

    #[Test]
    public function enqueueWithRequeueSetsFinishedItemBackToNew(): void
    {
        $repository = $this->createRepository();
        $repository->enqueue(self::QUEUE, ['id' => 1]);
        $repository->complete($repository->claim(self::QUEUE, 1)[0]);

        self::assertFalse($repository->enqueue(self::QUEUE, ['id' => 1]));
        self::assertTrue($repository->enqueue(self::QUEUE, ['id' => 1], true));

        self::assertSame(1, $repository->count(self::QUEUE, QueueItemStatus::New));
        self::assertCount(1, $this->fetchRows());
    }

    #[Test]
    public function releaseSetsItemsInProcessingBackToNew(): void
    {
        $repository = $this->createRepository();
        $repository->enqueue(self::QUEUE, ['id' => 1]);
        $repository->enqueue(self::QUEUE, ['id' => 2]);
        $repository->claim(self::QUEUE, 2);

        self::assertSame(2, $repository->release(self::QUEUE));

        self::assertSame(2, $repository->count(self::QUEUE, QueueItemStatus::New));
        self::assertSame(['', ''], array_column($this->fetchRows(), 'claim_token'));
    }

    #[Test]
    public function repositoryUsesTheConnectionOfTheTableMapping(): void
    {
        $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][self::SECOND_CONNECTION]
            = $GLOBALS['TYPO3_CONF_VARS']['DB']['Connections'][ConnectionPool::DEFAULT_CONNECTION_NAME];
        $GLOBALS['TYPO3_CONF_VARS']['DB']['TableMapping'][QueueRepositoryFactory::TABLE] = self::SECOND_CONNECTION;
        $second = $this->get(ConnectionPool::class)->getConnectionByName(self::SECOND_CONNECTION);
        $repository = $this->createRepository();

        // `Second` points to the same database as `Default`; that a rollback
        // of `Second` removes the item proves that the repository uses it
        $second->beginTransaction();
        $repository->enqueue(self::QUEUE, ['id' => 1]);
        self::assertSame(1, $repository->count(self::QUEUE, QueueItemStatus::New));
        $second->rollBack();

        self::assertSame([], $this->fetchRows());
    }
}
